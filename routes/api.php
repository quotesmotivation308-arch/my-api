<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WordLearningController;
use App\Http\Controllers\Api\WordReviewController;
use App\Http\Controllers\Api\DeviceController;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/learning/words', [WordLearningController::class, 'store']);
    Route::get('/learning/words', [WordLearningController::class, 'index']);
    Route::delete('/learning/words/{wordId}', [WordLearningController::class, 'destroy']);

    Route::post('/learning/review', [WordReviewController::class, 'store']);
    Route::get('/learning/review-due', [WordReviewController::class, 'due']);

    Route::post('/devices', [DeviceController::class, 'store']);
});

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
