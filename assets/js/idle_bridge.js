/*
 * idle_bridge.js - keeps the inactivity timer honest for the module shells
 * (sidenav.php pages that show their content inside an iframe).
 *
 * Problems it fixes:
 *  - activity inside the content iframe never reached the shell's timer, so the
 *    shell logged the user out while they were working (pages still visible);
 *  - the server-side session was only refreshed on full page loads.
 *
 * It (1) listens for activity in the shell AND in every page loaded in
 * #contentFrame, (2) resets the shell's own timer (window.resetTimer), (3) pings
 * includes/keepalive.php at most once a minute while the user is active, and
 * (4) if the server says the session is gone, sends the whole browser window to
 * the landing page (public/signout.php?timeout=1 -> index.php?timeout=1).
 */
(function () {
  var PING_EVERY = 60000, lastPing = 0, leaving = false;

  function toLanding() {
    if (leaving) return;
    leaving = true;
    (window.top || window).location.href = '../public/signout.php?timeout=1';
  }

  function ping() {
    fetch('../public/keepalive.php?activity=1', { credentials: 'same-origin', cache: 'no-store' })
      .then(function (r) { return r.json(); })
      .then(function (j) { if (!j || j.status !== 'success') toLanding(); })
      .catch(function () { /* offline blip - ignore */ });
  }

  function activity() {
    if (typeof window.resetTimer === 'function') window.resetTimer();
    var now = Date.now();
    if (now - lastPing > PING_EVERY) { lastPing = now; ping(); }
  }

  function hook(doc) {
    ['mousemove', 'keydown', 'click', 'scroll', 'touchstart'].forEach(function (ev) {
      doc.addEventListener(ev, activity, { passive: true });
    });
  }

  hook(document);

  document.addEventListener('DOMContentLoaded', function () {
    var frame = document.getElementById('contentFrame');
    if (!frame) return;
    frame.addEventListener('load', function () {
      try { hook(frame.contentDocument); } catch (e) { /* cross-origin */ }
      activity();   // a page load inside the frame is activity too
    });
  });
})();
