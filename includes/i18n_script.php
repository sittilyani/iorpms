<?php
/**
 * i18n_script.php
 * ================
 * Include this file inside <head> on any page that uses data-i18n attributes.
 * Requires $text (set by languages.php) to already be in scope.
 *
 * HOW TO USE ON A PAGE:
 * ---------------------
 * 1. Make sure languages.php is included before this file.
 * 2. Include this file inside <head>:
 *      <?php include '../includes/i18n_script.php'; ?>
 * 3. Add data-i18n="key" to any HTML element you want translated:
 *      <label data-i18n="client_name">Client Name</label>
 *      <th data-i18n="dosage">Dosage</th>
 *      <button data-i18n="dispense">Dispense</button>
 * 4. On language switch (via the header EN/FR/PT buttons) the page reloads
 *    with the new session language — PHP renders the text server-side AND
 *    this script swaps any data-i18n elements on the client side instantly.
 *
 * For elements whose text is set dynamically by JS, call applyI18n() again
 * after the dynamic content is inserted.
 */
global $text;
$lang_json = json_encode($text ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
?>
<script>
/* ── IORPMS offline translation bridge ───────────────────────────── */
var IORPMS_LANG = <?php echo $lang_json; ?>;

/**
 * Swap text of every [data-i18n] element using the current language dict.
 * Safe to call multiple times (e.g. after dynamic content injection).
 */
function applyI18n(root) {
    root = root || document;
    root.querySelectorAll('[data-i18n]').forEach(function(el) {
        var key = el.getAttribute('data-i18n');
        if (IORPMS_LANG[key] !== undefined) {
            // Preserve child elements (icons etc.) — only replace text nodes
            var hasChildren = el.children.length > 0;
            if (hasChildren) {
                // Find and update text nodes only
                el.childNodes.forEach(function(node) {
                    if (node.nodeType === 3 && node.textContent.trim()) {
                        node.textContent = IORPMS_LANG[key];
                    }
                });
            } else {
                el.textContent = IORPMS_LANG[key];
            }
        }
    });

    // data-i18n-title: translate title / placeholder / aria-label attributes
    root.querySelectorAll('[data-i18n-title]').forEach(function(el) {
        var key = el.getAttribute('data-i18n-title');
        if (IORPMS_LANG[key] !== undefined) el.title = IORPMS_LANG[key];
    });
    root.querySelectorAll('[data-i18n-placeholder]').forEach(function(el) {
        var key = el.getAttribute('data-i18n-placeholder');
        if (IORPMS_LANG[key] !== undefined) el.placeholder = IORPMS_LANG[key];
    });
}

document.addEventListener('DOMContentLoaded', function() {
    applyI18n();

    // ── Global Iframe Preservation on Language Switch ──
    const contentFrame = document.getElementById('contentFrame');
    const welcomeMessage = document.getElementById('welcomeMessage');

    if (contentFrame && welcomeMessage) {
        // Clear saved URL if coming from the main dashboard
        const referrer = document.referrer;
        if (referrer && referrer.indexOf('dashboard.php') !== -1) {
            sessionStorage.removeItem('activeTabUrl');
        }

        const savedUrl = sessionStorage.getItem('activeTabUrl');
        if (savedUrl && savedUrl !== 'about:blank') {
            contentFrame.style.display = 'block';
            welcomeMessage.style.display = 'none';
            contentFrame.src = savedUrl;

            // Highlight the correct sidebar link matching the saved page
            try {
                const navLinks = document.querySelectorAll('.nav-link[target="contentFrame"]');
                const savedPath = new URL(savedUrl).pathname;
                navLinks.forEach(link => {
                    const linkUrl = link.getAttribute('href');
                    if (linkUrl) {
                        // Normalize paths
                        const cleanLink = linkUrl.replace(/^\.\.\//, '').split('?')[0];
                        if (savedPath.indexOf(cleanLink) !== -1) {
                            link.classList.add('active');
                        } else {
                            link.classList.remove('active');
                        }
                    }
                });
            } catch(e) {}
        }

        // Listen for internal iframe loads to save current URL
        contentFrame.addEventListener('load', function() {
            try {
                const currentUrl = this.contentWindow.location.href;
                if (currentUrl && currentUrl.indexOf('about:blank') === -1) {
                    sessionStorage.setItem('activeTabUrl', currentUrl);
                }
            } catch (e) {
                // Ignore cross-origin errors (should not occur on same-origin)
            }
        });

        // Clear active tab on Home link click
        const homeLinks = document.querySelectorAll('.home-link');
        homeLinks.forEach(link => {
            link.addEventListener('click', function() {
                sessionStorage.removeItem('activeTabUrl');
            });
        });
    }
});
/* ─────────────────────────────────────────────────────────────────── */
</script>
