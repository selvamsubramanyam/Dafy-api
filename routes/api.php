<?php

use App\Http\Controllers\ApiController;
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

Route::group([
    'middleware' => 'auth.apikey'
], function () {
    Route::post('/register', [ApiController::class,'register']);
    Route::post('/login', [ApiController::class,'login']);
    Route::post('/otplogin', [ApiController::class,'OtpLogin']);
    Route::post('/otpregister', [ApiController::class,'OtpRegister']);
    Route::post('/verifyotp', [ApiController::class,'VerifyLoginOtp']);
    Route::post('/updateuserprofile', [ApiController::class,'updateUserProfile']);
    Route::get('/detail', [ApiController::class,'detail'])
    ->middleware('auth:sanctum');
});