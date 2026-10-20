<?php
// Browser-reachable session keep-alive (includes/ is blocked from the web, so the
// copy in includes/keepalive.php can never be called by the pages' JavaScript).
require __DIR__ . '/../includes/keepalive.php';
