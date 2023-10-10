<?php

use App\Http\Controllers\admins\AdminController;
use App\Http\Controllers\admins\chefDocumentsController;
use App\Http\Controllers\admins\kitchentypeController;
use App\Http\Controllers\admins\regionController;
use App\Http\Controllers\admins\shefTypesController;
use App\Http\Controllers\admins\taxController;
use App\Http\Controllers\chefs\ChefController;
use App\Http\Controllers\drivers\DriverController;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\users\cartController;
use App\Http\Controllers\users\OrderController;
use App\Http\Controllers\utility\otpController;
use App\Http\Controllers\users\UserController;
use App\Http\Controllers\utility\commonFunctions;
use App\Http\Controllers\utility\notificationController;
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

// Route for driver
Route::controller(DriverController::class)->group(function () {
    Route::post('/driverRegisteraion', 'driverRegisteraion');
    Route::post('/driverLogin', 'driverLogin');
    Route::post('/driverForgetPassword', 'driverForgetPassword');
    Route::post('/updatePersonalDetails', 'updatePersonalDetails');
    Route::post('/updateDrivingLicence', 'updateDrivingLicence');
    Route::post('/updateTaxationNo', 'updateTaxationNo');
    // Route::post('/updateAddress', 'updateAddress');
    Route::post('/updateCriminialReport', 'updateCriminialReport');
    Route::post('/updateDriverBankDetails', 'updateDriverBankDetails');
    Route::post('/driverScheduleAnCall', 'driverScheduleAnCall');
    Route::post('/AddDriverContactData', 'AddDriverContactData');
    Route::post('/getMyDetails', 'getMyDetails');
    Route::post('/driverUpdateEmail', 'driverUpdateEmail');
    Route::post('/VerifyDriverEmail', 'VerifyDriverEmail');

});

// Routes for users
Route::controller(UserController::class)->group(function () {
    Route::post('/UserRegisteration', 'UserRegisteration');
    Route::post('/UserLogin', 'UserLogin');
    Route::post('/getUserDetails', 'getUserDetails');
    Route::post('/updateUserDetail', 'updateUserDetail');
    Route::post('/updateUserDetailStatus', 'updateUserDetailStatus');

    Route::post('/getChefsByPostalCode', 'getChefsByPostalCode');
    Route::post('/getChefsByPostalCodeAndCuisineTypes', 'getChefsByPostalCodeAndCuisineTypes');
    Route::post('/getChefDetails', 'getChefDetails');
    Route::post('/googleSigin', 'googleSigin');
    Route::post('/recordNotFoundSubmit', 'recordNotFoundSubmit');

    Route::post('/addUpdateShippingAddress', 'addUpdateShippingAddress');
    Route::post('/getAllShippingAdressOfUser', 'getAllShippingAdressOfUser');
    Route::post('/changeDefaultShippingAddress', 'changeDefaultShippingAddress');
    Route::post('/deleteShippingAddress', 'deleteShippingAddress');

    Route::post('/addUserContacts', 'addUserContacts');
    Route::post('/updateContactStatus', 'updateContactStatus');
    Route::get('/getUserContact', 'getUserContact');

    Route::post('/ChefReview', 'ChefReview');
    Route::post('/deleteChefReview', 'deleteChefReview');
    Route::post('/getChefReview', 'getChefReview');

    Route::post('/getCountOftheChefAvailableForNext30Days', 'getCountOftheChefAvailableForNext30Days');
    Route::post('/VerifyUserEmail', 'VerifyUserEmail');

    Route::post('/addOrUpdateFoodReview', 'addOrUpdateFoodReview');
    Route::post('/getAllFoodReview', 'getAllFoodReview');
    Route::post('/updateUserFoodReviewStatus', 'updateUserFoodReviewStatus');
    Route::post('/deleteUserFoodReview', 'deleteUserFoodReview');
    Route::post('/getAllUserFoodReviewsbyStatus', 'getAllUserFoodReviewsbyStatus');

    Route::post('/updateUserChefReviewStatus', 'updateUserChefReviewStatus');
    Route::post('/deleteUserChefReview', 'deleteUserChefReview');
    Route::post('/getAllUserChefReviewsbyStatus', 'getAllUserChefReviewsbyStatus');

    Route::post('/calculateDistanceUsingTwoLatlong', 'calculateDistanceUsingTwoLatlong');
});

Route::controller(cartController::class)->group(function () {
    Route::post('/addToCart', 'addToCart');
    Route::post('/getMyCart', 'getMyCart');
    Route::post('/changeQuantity', 'changeQuantity');
    Route::post('/removeItemFromCart', 'removeItemFromCart');
    Route::post('/addBeforeLoginCartData', 'addBeforeLoginCartData');
    Route::post('/updateCartDeliveryDate', 'updateCartDeliveryDate');
});

Route::controller(otpController::class)->group(function () {
    Route::post('/sendOTP', 'sendOTP');
    Route::post('/verifyOtp', 'verifyOtp');
});

// Routes for chefs
Route::controller(ChefController::class)->group(function () {
    Route::post('/ChefRegisteration', 'ChefRegisteration');
    Route::post('/ChefLogin', 'ChefLogin');

    Route::post('/chefRegisterationRequest', 'chefRegisterationRequest');
    Route::get('/getChefRegisterationRequest', 'getChefRegisterationRequest');
    Route::post('/getChefDetails', 'getChefDetails');

    Route::post('/EditPersonalInfo', 'EditPersonalInfo');
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
    Route::post('/updateFoodItemAppprovedStatus', 'updateFoodItemAppprovedStatus');

    Route::post('/updateWeekAvailibilty', 'updateWeekAvailibilty'); // for foodItems
    Route::post('/updateChefAvailibilty', 'updateChefAvailibilty'); // for chef

    Route::post('/addNewAlternativeContact', 'addNewAlternativeContact');
    Route::post('/updateStatusOfAlternativeContact', 'updateStatusOfAlternativeContact');

    Route::post('/getAllAlternativeContacts', 'getAllAlternativeContacts');
    Route::post('/changePasswordForChef', 'changePasswordForChef');
    Route::post('/sendProfileForReview', 'sendProfileForReview');
    Route::post('/requestForUpdate', 'requestForUpdate');
    Route::post('/getAllPendingRequest', 'getAllPendingRequest');
    Route::post('/getApprovedUpdaterequest', 'getApprovedUpdaterequest');
    Route::post('/updateChefDetailsStatus', 'updateChefDetailsStatus');

    Route::post('/VerifyChefEmail', 'VerifyChefEmail');

    Route::post('/sendRequestForChefReviewDelete', 'sendRequestForChefReviewDelete');
    Route::post('/sendRequestForUserBlacklist', 'sendRequestForUserBlacklist');

    //new
    Route::post('/deleteMyFoodItem', 'deleteMyFoodItem');
    Route::post('/addChefSuggestions', 'addChefSuggestions');
    Route::post('/updateChefTaxInformation', 'updateChefTaxInformation');
});

// Route for admin
Route::controller(AdminController::class)->group(function () {
    Route::post('/adminRegistration', 'adminRegistration');
    Route::post('/adminLogin', 'adminLogin');

    Route::post('/updateSiteSettings', 'updateSiteSettings');
    Route::post('/deleteSiteSettings', 'deleteSiteSettings');
    Route::get('/getSiteSettings', 'getSiteSettings');

    Route::get('/getAllContactData', 'getAllContactData');
    Route::post('/updateContactDataStatus', 'updateContactDataStatus');

    Route::post('/addAdminSettings', 'addAdminSettings');
    Route::post('/updateAdminSettings', 'updateAdminSettings');
    Route::post('/deleteAdminSettings', 'deleteAdminSettings');
    Route::get('/getAdminSettings', 'getAdminSettings');

    Route::post('/addFoodTypes', 'addFoodTypes');
    Route::post('/updateFoodTypes', 'updateFoodTypes');
    Route::post('/deleteFoodTypes', 'deleteFoodTypes');

    Route::post('/addAllergies', 'addAllergies');
    Route::post('/updateAllergies', 'updateAllergies');
    Route::post('/deleteAllergies', 'deleteAllergies');

    Route::post('/addDietaries', 'addDietaries');
    Route::post('/updateDietaries', 'updateDietaries');
    Route::post('/deleteDietaries', 'deleteDietaries');

    Route::post('/addHeatingInstructions', 'addHeatingInstructions');
    Route::post('/updateHeatingInstructions', 'updateHeatingInstructions');
    Route::post('/deleteHeatingInstructions', 'deleteHeatingInstructions');
    Route::post('/updateHeatingInstructionsStatus', 'updateHeatingInstructionsStatus');

    Route::post('/addIngredients', 'addIngredients');
    Route::post('/updateIngredient', 'updateIngredient');
    Route::post('/deleteIngredient', 'deleteIngredient');
    Route::post('/updateIngredientStatus', 'updateIngredientStatus');

    // Route::post('/sendMessageToChef', 'sendMessageToChef');
    // Route::post('/updateMessageToChef', 'updateMessageToChef');
    // Route::post('/deleteMessageToChef', 'deleteMessageToChef');
    // Route::get('/getMessageToChef', 'getMessageToChef');

    Route::get('/getAllUsers', 'getAllUsers');
    Route::get('/getAllChefs', 'getAllChefs');

    Route::post('/sendMailToChef', 'sendMailToChef');

    Route::post('/updateChangeRequestStatus', 'updateChangeRequestStatus');

    Route::get('/getAllRequestForChefReviewDeletion', 'getAllRequestForChefReviewDeletion');
    Route::post('/updateStatusOfChefReviewDeleteRequest', 'updateStatusOfChefReviewDeleteRequest');

    Route::get('/getAllBlackListRequestByChef', 'getAllBlackListRequestByChef');
    Route::post('/blacklistUserOnChefRequest', 'blacklistUserOnChefRequest');
    Route::post('/unBlackListUser', 'unBlackListUser');
    //new
    Route::get('/getAllChefSuggestions', 'getAllChefSuggestions');
    Route::get('/getAdminDshboardCount', 'getAdminDshboardCount');
});

Route::controller(regionController::class)->group(function () {
    Route::post('/addCountry', 'addCountry');
    Route::post('/updateCountry', 'updateCountry');
    Route::get('/getCountry', 'getCountry');
    Route::post('/deleteCountry', 'deleteCountry');
    Route::post('/updateCountryStatus', 'updateCountryStatus');

    Route::post('/addState', 'addState');
    Route::post('/updateState', 'updateState');
    Route::get('/getState', 'getState');
    Route::post('/deleteState', 'deleteState');
    Route::post('/updateStateStatus', 'updateStateStatus');

    Route::post('/addCity', 'addCity');
    Route::post('/updateCity', 'updateCity');
    Route::get('/getCity', 'getCity');
    Route::post('/deleteCity', 'deleteCity');
    Route::post('/updateCityStatus', 'updateCityStatus');

    Route::post('/addPincode', 'addPincode');
    Route::post('/updatePincode', 'updatePincode');
    Route::post('/deletePincode', 'deletePincode');
    Route::get('/getPincode', 'getPincode');
    Route::post('/updatePincodeStatus', 'updatePincodeStatus');
});

Route::controller(chefDocumentsController::class)->group(function () {
    Route::post('/addDocumentItemNameAccToChefType', 'addDocumentItemNameAccToChefType');
    Route::post('/updateDocumentItemNameAccToChefType', 'updateDocumentItemNameAccToChefType');
    Route::post('/deleteDocumentItemNameAccToChefType', 'deleteDocumentItemNameAccToChefType');
    Route::get('/getDocumentListAccToChefType', 'getDocumentListAccToChefType');
    Route::post('/updateDocumentItemNameAccToChefTypeStatus', 'updateDocumentItemNameAccToChefTypeStatus');

    Route::post('/addDynamicFieldsForChef', 'addDynamicFieldsForChef');
    Route::post('/updateDynamicFieldsForChef', 'updateDynamicFieldsForChef');
    Route::post('/deleteDynamicFieldsForChef', 'deleteDynamicFieldsForChef');
    Route::get('/getDynamicFieldsForChef', 'getDynamicFieldsForChef');
});

Route::controller(kitchentypeController::class)->group(function () {
    Route::post('/addKitchenTypes', 'addKitchenTypes');
    Route::get('/getKitchenTypes', 'getKitchenTypes');
    Route::post('/updateKitchenTypes', 'updateKitchenTypes');
    Route::post('/deleteKitchenTypes', 'deleteKitchenTypes');
    Route::post('/updateKitchentypeStatus', 'updateKitchentypeStatus');
});

Route::controller(taxController::class)->group(function () {
    Route::post('/addTaxType', 'addTaxType');
    Route::post('/updateTaxType', 'updateTaxType');
    Route::post('/deleteTaxType', 'deleteTaxType');
    Route::get('/getTaxType', 'getTaxType');
});

Route::controller(shefTypesController::class)->group(function () {
    Route::post('/addShefType', 'addShefType');
    Route::post('/updateShefType', 'updateShefType');
    Route::post('/deleteShefType', 'deleteShefType');
    Route::get('/getAllShefTypes', 'getAllShefTypes');
    Route::post('/updateShefTypeStatus', 'updateShefTypeStatus');

    Route::post('/addShefSubType', 'addShefSubType');
    Route::post('/updateShefSubType', 'updateShefSubType');
    Route::post('/deleteShefSubType', 'deleteShefSubType');
    Route::get('/getAllShefSubTypes', 'getAllShefSubTypes');
    Route::post('/updateShefSubTypeStatus', 'updateShefSubTypeStatus');
});

Route::controller(OrderController::class)->group(function () {
    Route::post('/placeOrders', 'placeOrders');
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
    Route::post("/get_lat_long", 'get_lat_long');
    Route::post("/updateSiteFeedbackStatus", 'updateSiteFeedbackStatus');
    Route::post("/updateScheduleCallStatus", 'updateScheduleCallStatus');
    Route::get("/getAllScheduleCall", 'getAllScheduleCall');

    Route::post("/updateDriverScheduleCallStatus", 'updateDriverScheduleCallStatus');
    Route::get("/getAllDriverScheduleCall", 'getAllDriverScheduleCall');

    Route::get("/getAllChefs", 'getAllChefs');

    Route::post('/sendPasswordResetLink', 'sendPasswordResetLink');
    Route::post('/verifyToken', 'verifyToken');
    Route::post('/changePasswordwithToken', 'changePasswordwithToken');
});

/////////////// Notification Controller //////////////
Route::controller(notificationController::class)->group(function () {
    Route::post('/getUnreadNotificationAccordingToUserTypes', 'getUnreadNotificationAccordingToUserTypes');
});

////////////// Routes for stripe ///////////
Route::controller(stripeController::class)->group(function () {
    Route::post('/create-checkout-session', 'createSession');
    Route::post('/retriveStripePaymentStatus', 'retriveStripePaymentStatus');
});
