<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\ApiUsersController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AssetOperatorController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\BatteryController;
use App\Http\Controllers\ChemicalController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CropController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FarmerController;
use App\Http\Controllers\MisController;
use App\Http\Controllers\ReimbursementController;
use App\Http\Controllers\SchemeController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\VehicleController;
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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post('/login', [AuthController::class, 'login']);
Route::post('/operator_login', [AuthController::class, 'operator_login']);


// Route::group(['prefix' => 'users'], function () {
//     Route::get('/', [UserController::class, 'index']);
//     Route::post('/', [UserController::class, 'store']);
//     Route::get('/{id}', [UserController::class, 'show']);
//     Route::put('/{id}', [UserController::class, 'update']);
//     Route::delete('/{id}', [UserController::class, 'destroy']);
// });

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

    Route::get('/get_all_users', [ApiUsersController::class, 'get_all_users']);
    Route::get('/roles_list', [ApiUsersController::class, 'roles_list']);
    Route::post('/create_user', [ApiUsersController::class, 'create_user']);
    Route::post('/edit_user', [ApiUsersController::class, 'edit_user']);




    Route::any('/fetch-towns', [FarmerController::class, 'districtDetails']);
    Route::any('/check-mobile', [FarmerController::class, 'check_mobile_number']);
    Route::any('/fetch-villages', [FarmerController::class, 'fetchVillages']);
    Route::get('/get-locations', [FarmerController::class, 'location_datas']);
    Route::get('/get-farmers', [FarmerController::class, 'fetch_farmer_list']);
    Route::get('/get-farmer-info', [FarmerController::class, 'get_farmer_info']);


    Route::post('submit_farmer_details', [FarmerController::class, 'submit_farmer_details']);
    Route::post('edit_farmer_details', [FarmerController::class, 'edit_farmer_details']);

    Route::post('submit_asset_details', [AssetController::class, 'submit_asset_details']);
    Route::post('edit_asset', [AssetController::class, 'edit_asset']);
    Route::post('delete_asset', [AssetController::class, 'delete_asset']);
    Route::get('show_asset_list', [AssetController::class, 'show_asset_list']);
    Route::get('available_asset_list', [AssetController::class, 'available_asset_list']);


    Route::post('test_upload', [AssetController::class, 'test_upload']);
    Route::get('get_all_operators', [AssetOperatorController::class, 'get_all_operators']);
    Route::get('fetch_operators_to_assign', [AssetOperatorController::class, 'fetch_operators_to_assign']);
    Route::get('get_operator_assigned_services', [AssetOperatorController::class, 'get_operator_assigned_services']);
    Route::get('get_operator_accepted_services', [AssetOperatorController::class, 'get_operator_accepted_services']);
    Route::post('submit_operator_details', [AssetOperatorController::class, 'submit_operator_details']);
    Route::post('edit_operator_details', [AssetOperatorController::class, 'edit_operator_details']);
    Route::post('delete_operator', [AssetOperatorController::class, 'delete_operator']);
    Route::post('submit_operator_order_request', [AssetOperatorController::class, 'submit_operator_order_request']);
    Route::post('start_spray', [AssetOperatorController::class, 'start_spray']);
    Route::post('complete_spray', [AssetOperatorController::class, 'complete_spray']);
    Route::post('mark_spray_successful', [AssetOperatorController::class, 'mark_spray_successful']);


    Route::post('clockIn', [AttendanceController::class, 'clockIn']);
    Route::post('clockOut', [AttendanceController::class, 'clockOut']);
    Route::post('get_user_attendance', [AttendanceController::class, 'get_user_attendance']);
    Route::get('autoClockOut', [AttendanceController::class, 'autoClockOut']);
    Route::get('fetch_operator_attendance', [AttendanceController::class, 'fetch_operator_attendance']);


    Route::post('submit_operator_reimbursement', [ReimbursementController::class, 'submit_operator_reimbursement']);
    Route::post('get_all_reimbursements', [ReimbursementController::class, 'get_all_reimbursements']);
    Route::post('edit_operator_reimbursement', [ReimbursementController::class, 'edit_operator_reimbursement']);
    Route::post('get_reimburse_dashboard_details', [ReimbursementController::class, 'get_reimburse_dashboard_details']);
    Route::post('final_ter_submit', [ReimbursementController::class, 'final_ter_submit']);


  
    Route::post('get_ter_list', [ReimbursementController::class, 'get_ter_list']);

    Route::post('update_ter_details', [ReimbursementController::class, 'update_ter_details']);








    Route::post('submit_vehicle_details', [VehicleController::class, 'submit_vehicle_details']);
    Route::post('edit_vehicle_details', [VehicleController::class, 'edit_vehicle_details']);
    Route::get('fetch_vehicle_list', [VehicleController::class, 'fetch_vehicle_list']);
    Route::post('delete_vehicle', [VehicleController::class, 'delete_vehicle']);
    Route::post('submit_battery_details', [BatteryController::class, 'submit_battery_details']);
    Route::post('edit_battery_details', [BatteryController::class, 'edit_battery_details']);
    Route::get('get_all_batteries', [BatteryController::class, 'get_all_batteries']);
    Route::get('get_battery_by_id', [BatteryController::class, 'get_battery_by_id']);
    Route::get('get_batteries_to_assign', [BatteryController::class, 'get_batteries_to_assign']);
    Route::post('submit_client_details', [ClientController::class, 'submit_client_details']);
    Route::get('get_all_clients_list', [ClientController::class, 'get_all_clients_list']);
    Route::get('get_all_base_clients', [ClientController::class, 'get_all_base_clients']);
    Route::post('update_base_client', [ClientController::class, 'update_base_client']);
    Route::post('update_regional_client', [ClientController::class, 'update_regional_client']);
    Route::post('create_regional_client', [ClientController::class, 'create_regional_client']);
    Route::post('get_base_client_details', [ClientController::class, 'get_base_client_details']);

    Route::post('submit_crop_prices', [CropController::class, 'submit_crop_prices']);
    Route::post('update_crop_prices', [CropController::class, 'update_crop_prices']);
    Route::get('get_crop_price_list', [CropController::class, 'get_crop_price_list']);
    Route::get('get_crops', [CropController::class, 'get_crops']);

    Route::post('get_crop_details', [CropController::class, 'get_crop_details']);
    Route::post('get_state_crop_details', [CropController::class, 'get_state_crop_details']);


    Route::post('get_state_crop_details', [CropController::class, 'get_state_crop_details']);


    Route::get('fetch_order_list', [ServiceController::class, 'fetch_order_list']);
    Route::get('fetch_single_order/{id}', [ServiceController::class, 'fetch_single_order']);

    Route::post('submit_order_details', [ServiceController::class, 'submit_order_details']);
    Route::post('apply_order_scheme', [ServiceController::class, 'apply_order_scheme']);
    Route::post('fetch_assigned_details', [ServiceController::class, 'fetch_assigned_details']);
    Route::post('submit_assigned_operator', [ServiceController::class, 'submit_assigned_operator']);
    Route::post('cancel_order', [ServiceController::class, 'cancel_order']);
    Route::get('get_order_timeline/{id}', [ServiceController::class, 'get_order_timeline']);


    Route::post('import_chemical', [ChemicalController::class, 'import_chemical']);

    Route::get('get_chemical_list', [ChemicalController::class, 'get_chemical_list']);






    Route::get('get_fleet_management_details', [DashboardController::class, 'get_fleet_management_details']);
    Route::get('get_cso_dashboard_details', [DashboardController::class, 'get_cso_dashboard_details']);
    Route::get('get_management_dashboard_details', [DashboardController::class, 'get_management_dashboard_details']);

    Route::get('download_service_report', [MisController::class, 'download_service_report']);
    Route::get('download_farmer_report', [MisController::class, 'download_farmer_report']);

    Route::get('download_farm_report', [MisController::class, 'download_farm_report']);







    // Routes for SchemeController
    Route::get('/get_scheme_list', [SchemeController::class, 'get_scheme_list']);
    Route::get('/schemes/{id}', [SchemeController::class, 'show']);
    Route::post('/submit_scheme_details', [SchemeController::class, 'submit_scheme_details']);
    Route::post('/update_scheme', [SchemeController::class, 'update_scheme']);
    Route::put('/schemes/{id}', [SchemeController::class, 'update']);
    Route::delete('/schemes/{id}', [SchemeController::class, 'destroy']);




});
Route::get('generate_invoice_pdf/{id}', [AssetOperatorController::class, 'generate_invoice_pdf']);
Route::get('send_invoice_sms', [AssetOperatorController::class, 'send_invoice_sms']);

Route::get('download_ter_list', [ReimbursementController::class, 'download_ter_list']);