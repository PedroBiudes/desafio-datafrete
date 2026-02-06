<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DistanceController;
use App\Http\Controllers\Api\ImportController;

Route::get('/distances', [DistanceController::class, 'index']);
Route::post('/distances', [DistanceController::class, 'store']);

Route::get('/imports', [ImportController::class, 'index']);
Route::get('/imports/{id}', [ImportController::class, 'show']);
Route::post('/imports', [ImportController::class, 'store']);
