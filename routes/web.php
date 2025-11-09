<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function() {
    return redirect()->route('file.upload.index');
});

// Route::middleware(['auth'])->group(function () {
    Route::get('/file-upload', [\App\Http\Controllers\FileUploadController::class, 'index'])->name('file.upload.index');
    Route::get('/file-upload/create', [\App\Http\Controllers\FileUploadController::class, 'create'])->name('file.upload.create');
    Route::post('/upload-file', [\App\Http\Controllers\FileUploadController::class, 'save'])->name('file.upload.save');

    // import logs
    Route::get('/uploads/{upload}/logs', [\App\Http\Controllers\ImportLogController::class, 'index'])->name('uploads.logs');
// });
