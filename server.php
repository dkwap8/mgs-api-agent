<?php

// Router for php artisan serve: preserve static assets, protect legacy uploads.
$publicPath = __DIR__.'/public';
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '');
if (preg_match('#^/storage/activations(?:/|$)#i', str_replace('\\', '/', $uri))) {
    http_response_code(404);
    return;
}
if ($uri !== '/' && is_file($publicPath.$uri)) {
    return false;
}
require $publicPath.'/index.php';
