<?php

use App\Http\Controllers\Api\DefaultImagesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/default-images', [DefaultImagesController::class, 'index'])->name('api.default-images');
