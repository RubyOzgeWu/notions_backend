<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DatabaseController;
use App\Http\Controllers\DbPageController;

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


// Database Routes
Route::get('database/{id}', [DatabaseController::class, 'indexDatabase']);
Route::post('database', [DatabaseController::class, 'storeDatabase']);

// Database Page Routes
Route::get('database/{databaseId}/page', [DbPageController::class, 'indexPages']);
Route::post('database/{databaseId}/page', [DbPageController::class, 'storePage']);
Route::get('database/page/{pageId}', [DbPageController::class, 'showPage']);
Route::delete('database/page/{pageId}', [DbPageController::class, 'destroyPage']);


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
