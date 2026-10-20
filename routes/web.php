<?php

use Illuminate\Support\Facades\Route;

// Only actual browser pages load Vue. Unknown APIs and files keep real errors.
$userPages = 'dashboard|analytics|customers|accounts|transactions|wallet|payouts|api-keys|webhooks|documentation|help|settings|activate';
$adminPages = 'dashboard|merchants|providers|transactions|payouts|reports|settings|notifications|support|customers|business-activations|withdrawal-settings|activity-logs|security';

Route::view('/', 'app');
Route::view('/admin-panel', 'app');
Route::view('/admin-panel/{page}', 'app')->where('page', $adminPages);
Route::view('/user', 'app');
Route::view('/user/{page}', 'app')->where('page', $userPages.'|login|register|forgot-password|reset-password');

foreach (['login', 'register', 'forgot-password', 'reset-password'] as $page) {
    Route::get('/'.$page, fn () => redirect('/user/'.$page.(request()->getQueryString() ? '?'.request()->getQueryString() : ''), 302));
}
Route::redirect('/admin', '/admin-panel/dashboard');
Route::redirect('/merchant', '/user/dashboard');
Route::get('/admin/{page}', fn (string $page) => redirect('/admin-panel/'.$page, 302))->where('page', $adminPages);
Route::get('/merchant/{page}', fn (string $page) => redirect('/user/'.$page, 302))->where('page', $userPages);

// Static web servers block these legacy public document paths too.
Route::get('/storage/activations/{path}', fn () => abort(404))->where('path', '.*');
