<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\FarmerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/login', [AuthController::class, 'login']);

Route::group(['prefix' => 'users'], function () {
    Route::get('/', [UserController::class, 'index']);
    Route::post('/', [UserController::class, 'store']);
    Route::get('/{id}', [UserController::class, 'show']);
    Route::put('/{id}', [UserController::class, 'update']);
    Route::delete('/{id}', [UserController::class, 'destroy']);
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/test', [AuthController::class, 'test']);
    Route::get('farmers_list', [FarmerController::class, 'show_farmer_list']);
    Route::get('add_farmers', [FarmerController::class, 'add_farmers']);
    Route::get('logout', [AuthController::class, 'logout']);


    Route::group(['prefix' => 'users'], function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::get('/{id}', [UserController::class, 'show']);
        Route::post('update', [UserController::class, 'update']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
    });

    Route::any('/fetch-towns', [FarmerController::class, 'districtDetails']);
    Route::get('/get-locations', [FarmerController::class, 'location_datas']);


});
