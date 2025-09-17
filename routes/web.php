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
Route::get('/api/emails/refresh', [EmailController::class, 'refresh'])->name('emails.refresh');
