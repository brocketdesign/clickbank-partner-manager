<?php
// Minimal frontend handler: serve the application HTML page only.
// The actual submission is handled by the JSON API at /api/partners/apply.
require_once __DIR__ . '/config.php';

$landing = __DIR__ . '/apply-cb-partner.html';
if (file_exists($landing)) {
    header('Content-Type: text/html; charset=utf-8');
    echo file_get_contents($landing);
    exit;
}

http_response_code(404);
echo "Page not found.";
exit;
