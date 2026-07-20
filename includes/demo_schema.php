<?php
/**
 * includes/demo_schema.php
 * ========================
 * Ensures all tables/columns used by the demo-access system exist:
 *   - countries          (dropdown source on the landing page)
 *   - demo_requests      (every demo signup)
 *   - login_logs         (every login — powers the analytics dashboard)
 *   - tblusers.must_change_password  (force password change on first login)
 *   - tblusers.is_demo               (flags demo accounts)
 *
 * Safe to call on every request — everything is IF NOT EXISTS / guarded.
 * Usage:  include_once 'demo_schema.php';  ensureDemoSchema($conn);
 */

function ensureDemoSchema(mysqli $conn): void
{
    // ── countries ────────────────────────────────────────────
    $conn->query("
        CREATE TABLE IF NOT EXISTS countries (
            id   INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            iso2 CHAR(2)      NOT NULL DEFAULT '',
            region VARCHAR(40) DEFAULT '',
            sort_order INT DEFAULT 100
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Seed once (only if empty)
    $res = $conn->query("SELECT COUNT(*) c FROM countries");
    if ($res && (int)$res->fetch_assoc()['c'] === 0) {
        seedCountries($conn);
    }

    // ── demo_requests ────────────────────────────────────────
    $conn->query("
        CREATE TABLE IF NOT EXISTS demo_requests (
            id           INT AUTO_INCREMENT PRIMARY KEY,
            first_name   VARCHAR(80)  NOT NULL,
            last_name    VARCHAR(80)  NOT NULL,
            clinic_name  VARCHAR(160) NOT NULL,
            email        VARCHAR(120) NOT NULL,
            phone        VARCHAR(40)  NOT NULL,
            country      VARCHAR(80)  NOT NULL,
            plan         VARCHAR(40)  DEFAULT 'professional',
            token        VARCHAR(64)  NOT NULL,
            user_id      INT NULL,
            status       ENUM('pending','approved','rejected') DEFAULT 'pending',
            created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
            notified_at  TIMESTAMP    NULL,
            INDEX idx_email (email),
            INDEX idx_token (token),
            INDEX idx_country (country)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    addColumnIfMissing($conn, 'demo_requests', 'user_id', 'INT NULL');

    // ── login_logs ───────────────────────────────────────────
    $conn->query("
        CREATE TABLE IF NOT EXISTS login_logs (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            user_id    INT NULL,
            username   VARCHAR(100),
            userrole   VARCHAR(100),
            is_demo    TINYINT(1) DEFAULT 0,
            country    VARCHAR(80) DEFAULT '',
            ip_address VARCHAR(64) DEFAULT '',
            user_agent VARCHAR(255) DEFAULT '',
            success    TINYINT(1) DEFAULT 1,
            login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user (user_id),
            INDEX idx_time (login_time),
            INDEX idx_demo (is_demo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // ── tblusers extra columns ───────────────────────────────
    addColumnIfMissing($conn, 'tblusers', 'must_change_password', 'TINYINT(1) NOT NULL DEFAULT 0');
    addColumnIfMissing($conn, 'tblusers', 'is_demo',              'TINYINT(1) NOT NULL DEFAULT 0');

    // ── 'Demo' user role ─────────────────────────────────────
    $r = $conn->query("SELECT COUNT(*) c FROM userroles WHERE role = 'Demo'");
    if ($r && (int)$r->fetch_assoc()['c'] === 0) {
        $conn->query("INSERT INTO userroles (id, role, descr)
                      SELECT COALESCE(MAX(id),0)+1, 'Demo', 'Demo/evaluation user — full read access to explore the system'
                      FROM userroles");
    }
}

function addColumnIfMissing(mysqli $conn, string $table, string $column, string $definition): void
{
    $t = $conn->real_escape_string($table);
    $c = $conn->real_escape_string($column);
    $res = $conn->query("SHOW COLUMNS FROM `$t` LIKE '$c'");
    if ($res && $res->num_rows === 0) {
        $conn->query("ALTER TABLE `$t` ADD COLUMN `$c` $definition");
    }
}

function seedCountries(mysqli $conn): void
{
    // African countries first (sort_order 1), then the rest of the world (sort_order 50)
    $africa = [
        ['Kenya','KE'],['Tanzania','TZ'],['Uganda','UG'],['Ethiopia','ET'],['Rwanda','RW'],
        ['Burundi','BI'],['South Sudan','SS'],['Somalia','SO'],['Nigeria','NG'],['Ghana','GH'],
        ['South Africa','ZA'],['Mozambique','MZ'],['Angola','AO'],['Zambia','ZM'],['Zimbabwe','ZW'],
        ['Malawi','MW'],['Botswana','BW'],['Namibia','NA'],['Lesotho','LS'],['Eswatini','SZ'],
        ['Democratic Republic of the Congo','CD'],['Republic of the Congo','CG'],['Cameroon','CM'],
        ['Senegal','SN'],['Ivory Coast','CI'],['Mali','ML'],['Burkina Faso','BF'],['Niger','NE'],
        ['Chad','TD'],['Sudan','SD'],['Egypt','EG'],['Libya','LY'],['Tunisia','TN'],['Algeria','DZ'],
        ['Morocco','MA'],['Mauritania','MR'],['Gambia','GM'],['Guinea','GN'],['Guinea-Bissau','GW'],
        ['Sierra Leone','SL'],['Liberia','LR'],['Togo','TG'],['Benin','BJ'],['Gabon','GA'],
        ['Equatorial Guinea','GQ'],['Central African Republic','CF'],['Eritrea','ER'],['Djibouti','DJ'],
        ['Madagascar','MG'],['Mauritius','MU'],['Seychelles','SC'],['Comoros','KM'],['Cape Verde','CV'],
        ['São Tomé and Príncipe','ST'],
    ];
    $world = [
        ['Afghanistan','AF'],['Albania','AL'],['Argentina','AR'],['Armenia','AM'],['Australia','AU'],
        ['Austria','AT'],['Azerbaijan','AZ'],['Bahrain','BH'],['Bangladesh','BD'],['Belarus','BY'],
        ['Belgium','BE'],['Bolivia','BO'],['Bosnia and Herzegovina','BA'],['Brazil','BR'],['Bulgaria','BG'],
        ['Cambodia','KH'],['Canada','CA'],['Chile','CL'],['China','CN'],['Colombia','CO'],
        ['Costa Rica','CR'],['Croatia','HR'],['Cuba','CU'],['Cyprus','CY'],['Czech Republic','CZ'],
        ['Denmark','DK'],['Dominican Republic','DO'],['Ecuador','EC'],['El Salvador','SV'],['Estonia','EE'],
        ['Finland','FI'],['France','FR'],['Georgia','GE'],['Germany','DE'],['Greece','GR'],
        ['Guatemala','GT'],['Haiti','HT'],['Honduras','HN'],['Hungary','HU'],['Iceland','IS'],
        ['India','IN'],['Indonesia','ID'],['Iran','IR'],['Iraq','IQ'],['Ireland','IE'],
        ['Israel','IL'],['Italy','IT'],['Jamaica','JM'],['Japan','JP'],['Jordan','JO'],
        ['Kazakhstan','KZ'],['Kuwait','KW'],['Kyrgyzstan','KG'],['Laos','LA'],['Latvia','LV'],
        ['Lebanon','LB'],['Lithuania','LT'],['Luxembourg','LU'],['Malaysia','MY'],['Maldives','MV'],
        ['Malta','MT'],['Mexico','MX'],['Moldova','MD'],['Mongolia','MN'],['Montenegro','ME'],
        ['Myanmar','MM'],['Nepal','NP'],['Netherlands','NL'],['New Zealand','NZ'],['Nicaragua','NI'],
        ['North Macedonia','MK'],['Norway','NO'],['Oman','OM'],['Pakistan','PK'],['Panama','PA'],
        ['Papua New Guinea','PG'],['Paraguay','PY'],['Peru','PE'],['Philippines','PH'],['Poland','PL'],
        ['Portugal','PT'],['Qatar','QA'],['Romania','RO'],['Russia','RU'],['Saudi Arabia','SA'],
        ['Serbia','RS'],['Singapore','SG'],['Slovakia','SK'],['Slovenia','SI'],['South Korea','KR'],
        ['Spain','ES'],['Sri Lanka','LK'],['Sweden','SE'],['Switzerland','CH'],['Syria','SY'],
        ['Taiwan','TW'],['Tajikistan','TJ'],['Thailand','TH'],['Timor-Leste','TL'],['Turkey','TR'],
        ['Turkmenistan','TM'],['Ukraine','UA'],['United Arab Emirates','AE'],['United Kingdom','GB'],
        ['United States','US'],['Uruguay','UY'],['Uzbekistan','UZ'],['Venezuela','VE'],['Vietnam','VN'],
        ['Yemen','YE'],
    ];

    $stmt = $conn->prepare("INSERT IGNORE INTO countries (name, iso2, region, sort_order) VALUES (?,?,?,?)");
    foreach ($africa as $c) {
        $region = 'Africa'; $so = 1;
        $stmt->bind_param('sssi', $c[0], $c[1], $region, $so);
        $stmt->execute();
    }
    foreach ($world as $c) {
        $region = 'World'; $so = 50;
        $stmt->bind_param('sssi', $c[0], $c[1], $region, $so);
        $stmt->execute();
    }
    $stmt->close();
}
