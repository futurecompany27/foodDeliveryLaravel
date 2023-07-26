<?php

use App\Http\Controllers\admins\chefDocumentsController;
use App\Http\Controllers\admins\kitchentypeController;
use App\Http\Controllers\admins\regionController;
use App\Http\Controllers\admins\shefTypesController;
use App\Http\Controllers\admins\taxController;
use App\Http\Controllers\chefs\ChefController;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\users\cartController;
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
    Route::post('/getUserDetails', 'getUserDetails');
    Route::post('/getChefsByPostalCode', 'getChefsByPostalCode');
    Route::post('/getChefDetails', 'getChefDetails');
    Route::post('/googleSigin', 'googleSigin');
    Route::post('/recordNotFoundSubmit', 'recordNotFoundSubmit');
    Route::post('/addUpdateShippingAddress', 'addUpdateShippingAddress');
    Route::post('/getAllShippingAdressOfUser', 'getAllShippingAdressOfUser');
    Route::post('/changeDefaultShippingAddress', 'changeDefaultShippingAddress');
    Route::post('/deleteShippingAddress', 'deleteShippingAddress');
    Route::post('/updateUserDetail', 'updateUserDetail');
    Route::post('/storeNewPaymentDeatil', 'storeNewPaymentDeatil');

    Route::post('/ChefReview', 'ChefReview');
    Route::post('/deleteChefReview', 'deleteChefReview');

    Route::post('/getChefReview', 'getChefReview');

});

Route::controller(cartController::class)->group(function () {
    Route::post('/addToCart', 'addToCart');
    Route::post('/getMyCart', 'getMyCart');
    Route::post('/changeQuantity', 'changeQuantity');
    Route::post('/removeItemFromCart', 'removeItemFromCart');
});

Route::controller(otpController::class)->group(function () {
    Route::post('/sendOTP', 'sendOTP');
    Route::post('/verifyOtp', 'verifyOtp');
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
    Route::post('/chefScheduleAnCall', 'chefScheduleAnCall');
    Route::post('/AddContactData', 'AddContactData');
    Route::post('/chefAddNewOrUpdateFoodItem', 'chefAddNewOrUpdateFoodItem');
    Route::post('/getMyFoodItems', 'getMyFoodItems');
    Route::post('/getFoodItem', 'getFoodItem');
    Route::post('/updateWeekAvailibilty', 'updateWeekAvailibilty');
    Route::post('/addNewAlternativeContact', 'addNewAlternativeContact');
    Route::post('/updateStatusOfAlternativeContact', 'updateStatusOfAlternativeContact');
    Route::post('/getAllAlternativeContacts', 'getAllAlternativeContacts');
    Route::post('/changePasswordForChef', 'changePasswordForChef');
    Route::post('/sendProfileForReview', 'sendProfileForReview');
    Route::post('/requestForUpdate', 'requestForUpdate');
    Route::post('/getApprovedUpdaterequest', 'getApprovedUpdaterequest');
});

// Route for admin
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

/////////////// common api's ///////////////
Route::controller(commonFunctions::class)->group(function () {
    Route::get("/getAllBankList", 'getAllBankList');
    Route::post("/getDocumentListAccToChefTypeAndState", 'getDocumentListAccToChefTypeAndState');
    Route::get("/getAllFoodTypes", 'getAllFoodTypes');
    Route::get("/getAllHeatingInstructions", 'getAllHeatingInstructions');
    Route::get("/getAllAllergens", 'getAllAllergens');
    Route::get("/getAllDietaries", 'getAllDietaries');
    Route::get("/getAllIngredients", 'getAllIngredients');
    Route::get("/getAllSiteSettings", 'getAllSiteSettings');
    Route::post("/giveSiteFeedback", "giveSiteFeedback");
    Route::get("/getSiteFeedback", 'getSiteFeedback');
    Route::post('/addUserContacts', 'addUserContacts');
    Route::get('/getUserContact', 'getUserContact');
});



////////////// Routes for stripe ///////////
Route::post('/makePayment', [StripeController::class, 'makePayment']);