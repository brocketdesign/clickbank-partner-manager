<?php
// Serve dynamic snippet.js with correct headers and caching.
header('Content-Type: application/javascript; charset=utf-8');
// Cache for 1 hour (can be adjusted) - use short TTL so changes propagate during development
header('Cache-Control: public, max-age=3600');

$base = __DIR__ . '/snippet.js';
if (file_exists($base)) {
    // Read and return the existing snippet.js file (avoid executing it)
    echo file_get_contents($base);
    exit;
}

// Fallback: embedded snippet if file missing
$snippet = <<<'JS'
(function(){
  console.warn('snippet.js not found on server.');
})();
JS;

echo $snippet;
exit;
