<?php
/**
 * County logo lookup. Logos live in assets/images/<county>_county.png
 * (e.g. "Kwale" -> kwale_county.png, "Uasin Gishu" -> uasin_gishu_county.png).
 * Returns a path relative to the project root, or '' if the file has not been added yet.
 */
if (!function_exists('county_logo_path')) {
    function county_logo_path(?string $county): string
    {
        $slug = strtolower((string)(trim((string)$county)));
        $slug = preg_replace('/\s*\(county\)\s*/i', '', $slug);
        $slug = trim((string)(preg_replace('/[^a-z0-9]+/', '_', $slug)), '_');
        if ($slug === '') return '';
        foreach (['png', 'jpg', 'jpeg', 'webp', 'svg'] as $ext) {
            $rel = "assets/images/{$slug}_county.$ext";
            if (is_file(__DIR__ . '/../' . $rel)) return $rel;
        }
        return '';
    }
}
if (!function_exists('county_logo_img')) {
    /** <img> tag for use on pages one folder below the root (../assets/...); $prefix lets callers override */
    function county_logo_img(?string $county, string $prefix = '../', string $style = 'height:70px'): string
    {
        $p = county_logo_path($county);
        return $p ? '<img src="' . htmlspecialchars((string)($prefix . $p)) . '" alt="' . htmlspecialchars((string)$county) . ' County" style="' . $style . '">' : '';
    }
}
if (!function_exists('moh_logo_img')) {
    function moh_logo_img(string $prefix = '../', string $style = 'height:70px'): string
    {
        return '<img src="' . htmlspecialchars((string)($prefix)) . 'assets/images/Government of Kenya.png" alt="Government of Kenya" style="' . $style . '">';
    }
}
