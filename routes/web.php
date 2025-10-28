<?php

use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('uploads.index');
});

// Upload Routes

Route::controller(UploadController::class)->group(function () {
    Route::get('/uploads', action: 'index')->name('uploads.index');
    Route::post('/uploads', 'store')->name('uploads.store');
    Route::get('/uploads/history', 'history')->name('uploads.history');
    Route::delete('/uploads/{id}', 'destroy')->name('uploads.destroy');
});