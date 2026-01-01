<?php
// Serve dynamic snippet.js with correct headers and caching.
header('Content-Type: application/javascript; charset=utf-8');

$base = __DIR__ . '/snippet.js';
if (file_exists($base)) {
    // Use file modification time for cache busting - forces fresh version on every update
    $mtime = filemtime($base);
    header('Cache-Control: public, max-age=31536000'); // Cache 1 year since we bust via mtime
    header('ETag: "' . md5_file($base) . '"');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s T', $mtime));
    
    // Check for If-None-Match and If-Modified-Since headers
    $etag = md5_file($base);
    if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === '"' . $etag . '"') {
        header('HTTP/1.1 304 Not Modified');
        exit;
    }
    if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $mtime) {
        header('HTTP/1.1 304 Not Modified');
        exit;
    }
    
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
