<?php
/**
 * lang_switcher_snippet.php
 * ==========================
 * Compact EN / FR / PT tab strip for sidenav panels.
 * Include this inside any <div class="sidenav"> after its <h2> title block.
 * Requires: languages.php already included (sets $_SESSION['lang']).
 */
$_lc  = $_SESSION['lang'] ?? 'en';
$_lop = ['en' => '🇬🇧 EN', 'fr' => '🇫🇷 FR', 'pt' => '🇵🇹 PT'];
?>
<div style="display:flex;justify-content:center;gap:4px;padding:0 10px 12px;flex-wrap:wrap;">
<?php foreach ($_lop as $_lcode => $_llabel):
    $_lurl = '?' . http_build_query(array_merge(
        array_filter($_GET ?? [], fn($k) => $k !== 'lang', ARRAY_FILTER_USE_KEY),
        ['lang' => $_lcode]
    ));
    $_lst  = ($_lc === $_lcode)
        ? 'background:#fff;color:#1a2a6c;font-weight:700;'
        : 'background:rgba(255,255,255,.15);color:#fff;'; ?>
    <a href="<?= htmlspecialchars($_lurl) ?>"
       style="<?= $_lst ?>border:1px solid rgba(255,255,255,.4);padding:3px 9px;border-radius:12px;font-size:12px;font-weight:600;text-decoration:none;">
        <?= $_llabel ?>
    </a>
<?php endforeach; ?>
</div>
