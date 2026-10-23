<?php
/**
 * Print/report letterhead, injected without editing each page's markup.
 *
 *   require_once __DIR__ . '/../includes/print_header.php';
 *   print_header_start('form');      // clinical forms: Government of Kenya + county logo + facility details
 *   print_header_start('internal');  // internal reports/line lists: county logo + facility details only
 *   (MOH reports - 731, Form P - keep only the Government of Kenya logo and are not wrapped.)
 *
 * It buffers the page, inserts the letterhead right after <body>, and for 'internal' pages also
 * prefixes the facility details to the client-side "Export to Excel" tables.
 * Logos: assets/images/<county>_county.png (see includes/county_logo.php).
 */
require_once __DIR__ . '/county_logo.php';

function print_header_start(string $mode = 'form', string $title = ''): void
{
    // Globals are already gone when output buffers are flushed, but shutdown functions run first:
    // grab the page's mysqli connection there.
    $holder = new stdClass();
    register_shutdown_function(function () use ($holder) { global $conn; $holder->conn = $conn ?? null; });
    ob_start(function ($html) use ($mode, $title, $holder) {
        if (stripos($html, '<body') === false) { return $html; }
        // Many pages call $conn->close() before the output is flushed: use a fresh connection in that case,
        // and never let a failure here blank the page.
        try {
            $conn = $holder->conn ?? null;
            $alive = false;
            if ($conn instanceof mysqli) { try { $alive = @$conn->ping(); } catch (Throwable $e) { $alive = false; } } // throws once the page has closed it
            if (!$alive) {
                if (!defined('DB_HOST')) { return $html; }
                $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
                if ($conn->connect_error) { return $html; }
            }
            return print_header_render($conn, $html, $mode, $title);
        } catch (Throwable $e) {
            return $html;
        }
    });
}

function print_header_render(mysqli $conn, string $html, string $mode, string $title): string
{
    {
        $r = @$conn->query("SELECT fs.facilityname, fs.countyname, fs.subcountyname, fs.facilityphone, COALESCE(NULLIF(fs.mflcode,''), f.mflcode) AS mfl FROM facility_settings fs LEFT JOIN facilities f ON f.id = fs.facility_id LIMIT 1");
        $fac = $r ? $r->fetch_assoc() : null;
        if (!$fac) { return $html; }
        $e = fn($v) => htmlspecialchars((string)$v);
        $logoH = $mode === 'form' ? '70px' : '60px';
        $moh = ($mode === 'form' && stripos($html, 'Government of Kenya.png') === false)
            ? moh_logo_img('../', "height:$logoH") : '';
        $county = county_logo_img($fac['countyname'], '../', "height:$logoH");
        $details = '<div style="text-align:center;flex:1;min-width:220px">'
            . ($mode === 'form' ? '<div style="font-size:12px;letter-spacing:.06em;color:#2D008A;font-weight:700">REPUBLIC OF KENYA &middot; MINISTRY OF HEALTH</div>' : '')
            . '<div style="font-size:17px;font-weight:700;color:#2D008A">' . $e($fac['facilityname']) . '</div>'
            . '<div style="font-size:12px;color:#333">MFL ' . $e($fac['mfl']) . ' &middot; ' . $e($fac['countyname']) . ' County &middot; ' . $e($fac['subcountyname']) . ($fac['facilityphone'] ? ' &middot; Tel ' . $e($fac['facilityphone']) : '') . '</div></div>';
        $strip = '<div class="ph-strip" style="display:flex;align-items:center;justify-content:center;gap:18px;flex-wrap:wrap;padding:10px 16px;margin:0 0 12px;border-bottom:3px solid #2D008A;background:#fff">'
            . $moh . $details . $county . '</div>';
        $html = preg_replace('/<body[^>]*>/i', '$0' . str_replace('$', '\$', $strip), $html, 1);

        if ($mode === 'internal') {
            // Client-side Excel exports: prepend the facility details above the table
            $xl = '<div><b>' . $e($fac['facilityname']) . '</b><br>MFL ' . $e($fac['mfl']) . ' | ' . $e($fac['countyname']) . ' County | ' . $e($fac['subcountyname']) . '<br>' . $e($title ?: 'Report') . ' - generated ' . date('d M Y H:i') . '</div><br>';
            $html = str_replace('var html = table.outerHTML;', 'var html = ' . json_encode($xl) . ' + table.outerHTML;', $html);
        }
        return $html;
    }
}
