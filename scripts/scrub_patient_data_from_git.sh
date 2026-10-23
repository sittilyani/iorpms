#!/usr/bin/env bash
# Removes patient-data database dumps, patient spreadsheets and a clinic config from the WHOLE git history.
#
# It never touches this working copy and never pushes. It makes a fresh bare mirror next to the repo,
# rewrites that mirror, verifies it, and prints the exact push commands.
#
#   1. commit your work first          (git add -A && git commit -m "...")
#   2. bash scripts/scrub_patient_data_from_git.sh
#   3. follow the commands it prints
#
# Needs only git (git filter-branch). Run from Git Bash.
set -euo pipefail

SRC="$(cd "$(dirname "$0")/.." && pwd)"
REMOTE_URL="$(git -C "$SRC" remote get-url origin)"
OUT="${1:-$SRC/../iorpms-clean.git}"

if [ -n "$(git -C "$SRC" status --porcelain)" ]; then
  echo "!! You have uncommitted changes in $SRC. Commit them first so they are included in the clean history."
  exit 1
fi
rm -rf "$OUT"
echo ">> Cloning a throwaway mirror to $OUT"
git clone --mirror --no-local "$SRC" "$OUT" >/dev/null 2>&1
cd "$OUT"
BEFORE=$(du -sh . | cut -f1)

# Everything below is removed from every commit. Schema scripts, migrations and the blank database
# (blankDB/methadone_blank_db.sql) are kept.
read -r -d '' FILTER <<'EOF' || true
git rm -r -q --cached --ignore-unmatch \
  backup/database backup/Ruiru.xlsx \
  sql_queries/Kisauni_MAT_clinic.sql sql_queries/Kombani_Mat_Clinic.sql sql_queries/Malindi_District_Hospital.sql \
  sql_queries/Ruiru_SubCounty_Hospital.sql sql_queries/ShimoLa_Tewa_Health_Centre.sql sql_queries/Thika_Level_5_Hospital.sql \
  sql_queries/methadone_karuri_march_2026.sql \
  reports/all_patients.xls includes/config-Kombani.php includes/mail_debug.log
git ls-files -z blankDB | grep -zv '^blankDB/methadone_blank_db\.sql$' | xargs -0 -r git rm -q --cached --ignore-unmatch
EOF

echo ">> Rewriting history (this takes a minute)..."
export FILTER_BRANCH_SQUELCH_WARNING=1
git filter-branch -f --index-filter "$FILTER" --prune-empty --tag-name-filter cat -- --all >/dev/null 2>&1

echo ">> Dropping the old objects"
rm -rf refs/original
git reflog expire --expire=now --all
git gc --prune=now --aggressive -q
AFTER=$(du -sh . | cut -f1)

echo ">> Verifying"
LEFT=$(git rev-list --all --objects | grep -E '^[0-9a-f]{40} (backup/database/|backup/Ruiru|sql_queries/(Kisauni|Kombani|Malindi|Ruiru|ShimoLa|Thika|methadone_karuri)|reports/all_patients|includes/config-Kombani|includes/mail_debug|blankDB/(Blank|methadone_(12|Malindi|malindi)|methadone \())' || true)
if [ -n "$LEFT" ]; then echo "!! Still present:"; echo "$LEFT"; exit 1; fi
LEFTSQL=$(git rev-list --all --objects | grep -iE '\.sql$' | awk '{print $2}' | sort -u | grep -vE '^(blankDB/methadone_blank_db|tableupdates/|triggers/|updates/|sql_queries/(add_pharmacy_indexes|dose_schedules|foreignKeys|indexKeys|missed_doses|run_[123]_|triggers))' || true)
if [ -n "$LEFTSQL" ]; then echo "!! Unexpected .sql files remain in history:"; echo "$LEFTSQL"; exit 1; fi
HITS=0
for sha in $(git rev-list --all --objects | grep -iE '\.sql$' | awk '{print $1}' | sort -u); do
  n=$(git cat-file -p "$sha" | grep -c -iE 'INSERT INTO `?(patients|pharmacy|photos|clinic_visits|tblusers)`? VALUES|INSERT INTO `(patients|pharmacy|photos|clinic_visits|tblusers)` \(' || true)
  HITS=$((HITS + n))
done
echo "   patient-data INSERT statements left in history: $HITS"
[ "$HITS" -eq 0 ] || { echo "!! patient data is still in history"; exit 1; }

git remote set-url origin "$REMOTE_URL"
echo
echo "OK - clean mirror ready: $OUT   (size $BEFORE -> $AFTER, $(git rev-list --all --count) commits)"
echo
echo "Next, to replace GitHub with the clean history:"
echo "  cd \"$OUT\""
echo "  git push --force --all origin"
echo "  git push --force --tags origin"
