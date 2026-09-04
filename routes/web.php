<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Session\Middleware\StartSession;
use App\Http\Middleware\VerifyCsrfToken;

// The legacy pages remain the source of truth while Laravel owns the entry point.
Route::any('/{legacyPath?}', function (?string $legacyPath = null) {
    $path = trim($legacyPath ?: 'index.php', '/');
    $path = str_ends_with($path, '.php') ? $path : $path . '.php';

    if ($path === '' || str_contains($path, '..') || !preg_match('/^[a-zA-Z0-9_\/-]+\.php$/', $path)) {
        abort(404);
    }

    $file = base_path('legacy/' . $path);
    if (!is_file($file)) {
        abort(404);
    }

    $previousDirectory = getcwd();
    chdir(dirname($file));
    try {
        require $file;
    } finally {
        chdir($previousDirectory);
    }
})->where('legacyPath', '.*')->withoutMiddleware([
    StartSession::class,
    VerifyCsrfToken::class,
]);
