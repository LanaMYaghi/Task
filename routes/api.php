<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::post('register', [AuthController::class, 'register']);
Route::post('login',    [AuthController::class, 'login'])->middleware('throttle:5,1');

Route::group(['middleware' => ['auth:api', 'refresh.token']], function () {

    Route::post('logout',  [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::get('getMyInfo', [AuthController::class, 'me']);
    Route::post('updateMyProfile', [UserController::class, 'update']);
    Route::delete('deleteMyProfile', [UserController::class, 'deleteMyProfile']);




    Route::group(['middleware' => ['role:Admin']], function () {
        Route::get('indexUsers',         [UserController::class, 'index']);
        Route::delete('destroyUsers/{id}', [UserController::class, 'destroy']);
    });
});
