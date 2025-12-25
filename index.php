<?php
// Serve the partner landing page at site root
$landing = __DIR__ . '/recrut-cb-partner.html';
if (file_exists($landing)) {
    header('Content-Type: text/html; charset=utf-8');
    readfile($landing);
    exit;
} else {
    http_response_code(500);
    echo "Landing page not found.";
    exit;
}
