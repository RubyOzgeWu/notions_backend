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


/* Database Routes */
// 取得資料庫 MetaData
Route::get('database/{databaseId}', [DatabaseController::class, 'indexDatabase']);
// 新增資料庫
// Route::post('database', [DatabaseController::class, 'storeDatabase']);

/* Database Page Routes */
// 取得資料庫所有文件
Route::post('database/{databaseId}/pages', [DbPageController::class, 'indexPages']);
// 新增資料庫文件
Route::post('database/{databaseId}/page', [DbPageController::class, 'storePage']);
// 取得資料庫文件
Route::get('database/page/{pageId}', [DbPageController::class, 'showPage']);
// 刪除資料庫文件
Route::delete('database/page/{pageId}', [DbPageController::class, 'destroyPage']);
// 更新資料庫文件
Route::patch('database/{databaseId}/page/{pageId}', [DbPageController::class, 'updatePage']);


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
