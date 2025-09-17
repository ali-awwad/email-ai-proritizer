<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmailController;

Route::get('/', function () {
    return view('welcome');
});

// Microsoft Graph Email Routes
Route::get('/emails', [EmailController::class, 'index'])->name('emails.index');
Route::get('/auth/microsoft', [EmailController::class, 'authenticate'])->name('auth.microsoft');
Route::get('/auth/microsoft/callback', [EmailController::class, 'callback'])->name('auth.microsoft.callback');
Route::post('/auth/logout', [EmailController::class, 'logout'])->name('auth.logout');

// Email API Routes
Route::get('/api/emails/refresh', [EmailController::class, 'refresh'])->name('emails.refresh');
Route::post('/api/emails/cache/clear', [EmailController::class, 'clearCache'])->name('emails.cache.clear');
Route::get('/api/emails/cache/stats', [EmailController::class, 'cacheStats'])->name('emails.cache.stats');
