# fix-vue-routes.ps1
Write-Host "🔧 Fixing Vue Routes..." -ForegroundColor Green

# Step 1: Create app.blade.php
Write-Host "📝 Creating app.blade.php..." -ForegroundColor Yellow
$appBlade = @'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>MGS API Platform</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div id="app"></div>
</body>
</html>
'@

$appBlade | Out-File -FilePath resources/views/app.blade.php -Encoding UTF8

# Step 2: Update web.php
Write-Host "📝 Updating web.php..." -ForegroundColor Yellow
$webRoutes = @'
<?php

use Illuminate\Support\Facades\Route;

Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
'@

$webRoutes | Out-File -FilePath routes/web.php -Encoding UTF8

# Step 3: Build assets
Write-Host "📦 Building assets..." -ForegroundColor Yellow
npm run build

# Step 4: Clear cache
Write-Host "🧹 Clearing cache..." -ForegroundColor Yellow
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

Write-Host "`n✅ Fix completed!" -ForegroundColor Green
Write-Host "`nRun: php artisan serve" -ForegroundColor Cyan
Write-Host "Then open: http://localhost:8000" -ForegroundColor Cyan