<?php

use App\Http\Controllers\DroneController;
use App\Http\Controllers\FarmerController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ImportExportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PilotController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [HomeController::class, 'index'])->name('home');

Route::group(['middleware' => ['auth']], function () {
    Route::resource('roles', RoleController::class);
    Route::resource('permissions', PermissionController::class);
    Route::resource('users', UserController::class);
    Route::resource('products', ProductController::class);
    Route::get('farmers_list', [FarmerController::class, 'show_farmer_list']);
    Route::get('add_farmers', [FarmerController::class, 'add_farmers']);
    Route::post('submit_farmer_details', [FarmerController::class, 'submit_farmer_details']);
    Route::get('/get_area_details/{postcode}', [FarmerController::class, 'getPostalAddress']);
    Route::get('/export_farmer_details', [FarmerController::class, 'export_farmer_details'])->name('export_farmer_details.route');;
    Route::any('/common-district', [FarmerController::class, 'districtDetails']);
    Route::get('pilot_list', [PilotController::class, 'show_pilot_list']);
    Route::get('add_pilot', [PilotController::class, 'add_pilot']);
    Route::get('/show_import', [ImportExportController::class, 'ShowImportExcel']);
    Route::post('/import_data', [ImportExportController::class, 'ImportExcel']);
    Route::get('drone_list', [DroneController::class, 'show_drone_list']);
    Route::get('add_drone', [DroneController::class, 'add_drone']);
    Route::post('submit_drone_details', [DroneController::class, 'submit_drone_details']);

    Route::get('logout', [UserController::class, 'logout']);


});
