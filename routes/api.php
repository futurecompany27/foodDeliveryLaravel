<?php

use App\Http\Controllers\admins\chefDocumentsController;
use App\Http\Controllers\admins\kitchentypeController;
use App\Http\Controllers\admins\regionController;
use App\Http\Controllers\admins\shefTypesController;
use App\Http\Controllers\admins\taxController;
use App\Http\Controllers\chefs\ChefController;
use App\Http\Controllers\users\otpController;
use App\Http\Controllers\users\UserController;
use App\Http\Controllers\utility\commonFunctions;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Routes for users
Route::controller(UserController::class)->group(function () {
    Route::post('/UserRegisteration', 'UserRegisteration');
    Route::post('/UserLogin', 'UserLogin');
    Route::post('/getChefsByPostalCode', 'getChefsByPostalCode');
    Route::post('/getChefDetails', 'getChefDetails');
    Route::post('/googleSigin', 'googleSigin');
});

// Routes for chefs
Route::controller(ChefController::class)->group(function () {
    Route::post('/ChefRegisteration', 'ChefRegisteration');
    Route::post('/EditPersonalInfo', 'EditPersonalInfo');
    Route::post('/ChefLogin', 'ChefLogin');
    Route::post('/getChefDetails', 'getChefDetails');
    Route::post('/updateChefPrimaryEmail', 'updateChefPrimaryEmail');
    Route::post('/updateSocialMediaLinks', 'updateSocialMediaLinks');
    Route::post('/updateBankDetails', 'updateBankDetails');
    Route::post('/updateDocuments', 'updateDocuments');
    Route::post('/updateKitchen', 'updateKitchen');
    Route::post('/updateSpecialBenifits', 'updateSpecialBenifits');
    Route::post('/AddContactData', 'AddContactData');
});

///////////////////// Routes for admin /////////////////////////
Route::controller(regionController::class)->group(function () {
    Route::post('/addCountry', 'addCountry');
    Route::post('/addState', 'addState');
    Route::post('/addCity', 'addCity');
    Route::post('/addPincode', 'addPincode');
});

Route::controller(chefDocumentsController::class)->group(function () {
    Route::post('/addDocumentItemNameAccToChefType', 'addDocumentItemNameAccToChefType');
    Route::post('/addDynamicFieldsForChef', 'addDynamicFieldsForChef');
});

Route::controller(kitchentypeController::class)->group(function () {
    Route::post('/addKitchenTypes', 'addKitchenTypes');
    Route::get('/getKitchenTypes', 'getKitchenTypes');
});

Route::controller(taxController::class)->group(function () {
    Route::post('/addTaxType', 'addTaxType');
});

Route::controller(shefTypesController::class)->group(function () {
    Route::post('/addShefType', 'addShefType');
    Route::get('/getAllShefTypes', 'getAllShefTypes');
    Route::post('/addShefSubType', 'addShefSubType');
    Route::get('/getAllShefSubTypes', 'getAllShefSubTypes');
});

Route::controller(otpController::class)->group(function () {
    Route::post('/sendOTP', 'sendOTP');
    Route::post('/verifyOtp', 'verifyOtp');
});

/////////////// common api's ///////////////
Route::controller(commonFunctions::class)->group(function () {
    Route::get("/getAllBankList", 'getAllBankList');
    Route::post("/getDocumentListAccToChefTypeAndState", 'getDocumentListAccToChefTypeAndState');

});