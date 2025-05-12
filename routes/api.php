<?php

use App\Http\Controllers\admins\AdminController;
use App\Http\Controllers\admins\chefDocumentsController;
use App\Http\Controllers\admins\kitchentypeController;
use App\Http\Controllers\admins\regionController;
use App\Http\Controllers\admins\shefTypesController;
use App\Http\Controllers\admins\taxController;
use App\Http\Controllers\AuthorizePaymentController;
use App\Http\Controllers\chefs\ChefController;
use App\Http\Controllers\drivers\DriverController;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\users\CartController;
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

Route::get('/test', function () {
    return response()->json(['message' => 'Welcome to HomePlate', 'success' => true], 200);
});

Route::prefix('chef')->group(function () {
    Route::controller(ChefController::class)->group(function () {
        Route::post('/ChefRegisteration', 'ChefRegisteration');
        Route::post('/ChefLogin', 'ChefLogin');
        Route::post('/chefRegisterationRequest', 'chefRegisterationRequest');
    });
    Route::controller(kitchentypeController::class)->group(function () {
        Route::get('/getKitchenTypes', 'getKitchenTypes'); //without Auth can access // chef panel
    });

    Route::group(['middleware' => 'auth.chef'], function ($router) {
        Route::controller(commonFunctions::class)->group(function () {
            Route::get("/getAllBankList", 'getAllBankList'); // chef panel
            Route::post("/getDocumentListAccToChefTypeAndState", 'getDocumentListAccToChefTypeAndState'); // chef panel
            Route::post('/sendPasswordResetLink', 'sendPasswordResetLink'); // chef panel
            Route::post('/changePasswordwithToken', 'changePasswordwithToken'); // chef panel
        });
        Route::controller(OrderController::class)->group(function () {
            // Route::post('/placeOrders', 'placeOrders');
            // Route::post('/acceptOrRejectOrder', 'acceptOrRejectOrder');
            Route::get('/trackOrder', 'trackOrder');
            Route::get('/getAllTransactions', 'getAllTransactions');
            // Route::post('/calculate_tax', 'calculate_tax');
        });
        Route::controller(UserController::class)->group(function () {
            Route::get('/getChefReview', 'getChefReview');
            Route::post('/getAllFoodReview', 'getAllFoodReview');
        });
        Route::controller(AdminController::class)->group(function () {
            Route::get('/getAdminSettings', 'getAdminSettings');
            Route::get('/getAllUsers', 'getAllUsers');
        });
        Route::controller(notificationController::class)->group(function () {
            Route::post('/getUnreadNotificationAccordingToUserTypes', 'getUnreadNotificationAccordingToUserTypes'); // chef panel
            Route::post('/deleteNotification', 'deleteNotification');
            Route::post('/markAsReadNotification', 'markAsReadNotification');
        });

        Route::controller(stripeController::class)->group(function () {
            Route::post('/stripe-checkout-transaction', 'stripeCheckout'); // chef panel
            Route::post('/retriveCertificatePaymentStatus', 'retriveCertificatePaymentStatus'); // chef panel
            Route::post('/storeTransaction', 'storeTransaction'); // chef panel
        });
        Route::controller(ChefController::class)->group(function () {
            Route::get('/chefProfile', 'chefProfile');
            Route::post('/chefLogout',  'chefLogout');
            Route::post('/chefRefreshToken',  'chefRefreshToken');
            Route::post('/getFoodItemsForCustomer', 'getFoodItemsForCustomer'); //without Auth can access make by sarita
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
            Route::post('/deleteChefSchedule', 'deleteChefSchedule');

            Route::post('/AddContactData', 'AddContactData');
            Route::post('/chefAddNewOrUpdateFoodItem', 'chefAddNewOrUpdateFoodItem');
            Route::post('/getMyFoodItems', 'getMyFoodItems'); //make by husain
            Route::post('/getFoodItem', 'getFoodItem');
            Route::get('/getFoodItemWithoutStatus', 'getFoodItemWithoutStatus');
            Route::post('/updateFoodItemAppprovedStatus', 'updateFoodItemAppprovedStatus');

            Route::post('/updateWeekAvailibilty', 'updateWeekAvailibilty'); // for foodItems
            Route::post('/updateChefAvailibilty', 'updateChefAvailibilty');

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

            Route::post('/deleteMyFoodItem', 'deleteMyFoodItem');
            Route::post('/addChefSuggestions', 'addChefSuggestions');
            Route::post('/updateChefTaxInformation', 'updateChefTaxInformation');
            Route::post('/getChefOrders', 'getChefOrders');
            Route::post('/getChefSubOrder', 'getChefSubOrder');

            Route::post('/updateChefOrderStatus', 'updateChefOrderStatus');

            Route::post('/addChefStory', 'addChefStory');
            Route::get('/getAllChefsStory', 'getAllChefsStory');

            Route::post('/updateFoodCertificateStatus', 'updateFoodCertificateStatus'); // Chef food_certificate status update api
            Route::post('/foodLicense', 'foodLicense');
            // Route::get('/chefFoodLicense', 'chefFoodLicense');
            Route::get('/getFoodLicenseList', 'getFoodLicenseList');
            Route::get('/getFoodLicenseData', 'getFoodLicenseData');
            Route::get('/generatePDF', 'generatePDF');
            Route::post('/deleteChef', 'deleteChef');

            Route::get('/packageInstructionPDF', 'packageInstructionPDF'); // chef panel
            Route::post('/deletePendingRequest', 'deletePendingRequest');
            Route::get('/orderInvoicePDF', 'orderInvoicePDF');
        });
        Route::controller(notificationController::class)->group(function () {
            Route::post('/getUnreadNotificationAccordingToUserTypes', 'getUnreadNotificationAccordingToUserTypes'); // chef panel
            Route::post('/deleteNotification', 'deleteNotification');
            Route::post('/markAsReadNotification', 'markAsReadNotification');
        });
        Route::controller(taxController::class)->group(function () {
            Route::get('/getTaxType', 'getTaxType');
        });
        Route::controller(shefTypesController::class)->group(function () {
            Route::get('/getAllShefTypes', 'getAllShefTypes');
            Route::get('/getAllShefSubTypes', 'getAllShefSubTypes');
        });
    });
});




Route::prefix('driver')->group(function () {
    Route::controller(DriverController::class)->group(function () {
        Route::post('/driverRegisteraion', 'driverRegisteraion');
        Route::post('/driverLogin', 'driverLogin');
        Route::post('/driverForgetPassword', 'driverForgetPassword');
    });

    Route::group(['middleware' => 'auth.driver'], function ($router) {
        Route::controller(DriverController::class)->group(function () {
            Route::get('/driverProfile', 'driverProfile');
            Route::post('/driverLogout', 'driverLogout');
            Route::post('/driverRefreshToken', 'driverRefreshToken');
            Route::post('/getMyDetails', 'getMyDetails');
            Route::post('/updatePersonalDetails', 'updatePersonalDetails');
            Route::post('/updateDrivingLicence', 'updateDrivingLicence');
            Route::post('/updateTaxationNo', 'updateTaxationNo');
            // Route::post('/updateAddress', 'updateAddress');
            Route::post('/updateCriminialReport', 'updateCriminialReport');
            Route::post('/updateDriverBankDetails', 'updateDriverBankDetails');
            Route::post('/driverScheduleAnCall', 'driverScheduleAnCall');
            Route::post('/AddDriverContactData', 'AddDriverContactData');
            Route::post('/driverUpdateEmail', 'driverUpdateEmail');
            Route::post('/VerifyDriverEmail', 'VerifyDriverEmail');
            Route::post('/updateLatLongAndGetListOfOrdersForDriver', 'updateLatLongAndGetListOfOrdersForDriver');
            Route::get('/getAllDriver', 'getAllDriver'); // admin panel
            Route::post('/deleteDriver', 'deleteDriver');
            Route::post('/addDriverContact', 'addDriverContact');

            Route::get('/getDriverContact', 'getDriverContact');
            Route::post('/addDriverSuggestions', 'addDriverSuggestions');
            Route::get('/getDriverSuggestions', 'getDriverSuggestions');
            Route::post('/updateDriverProfileStatus', 'updateDriverProfileStatus');
            Route::post('/sendDriverProfileForReview', 'sendDriverProfileForReview');
            Route::post('/driverRequestForUpdate', 'driverRequestForUpdate');
            Route::get('/getDriverFinalReview', 'getDriverFinalReview');

            Route::post('/driverChangeOrderStatus', 'driverChangeOrderStatus');
            Route::get('/driverCurrentOrder', 'driverCurrentOrder');
            Route::get('/driverAcceptedOrder', 'driverAcceptedOrder');
            Route::get('/driverCompletedOrder', 'driverCompletedOrder');
            Route::post('/getDriverOrders', 'getDriverOrders');
        });
        Route::controller(taxController::class)->group(function () {
            Route::get('/getTaxType', 'getTaxType');
        });
    });
});




Route::prefix('user')->group(function () {
    Route::controller(UserController::class)->group(function () {
        Route::post('/UserRegisteration', 'UserRegisteration');
        Route::post('/UserLogin', 'UserLogin');
        Route::post('/googleSigin', 'googleSigin');
        Route::post('/getChefsByPostalCode', 'getChefsByPostalCode'); //without Auth can access
        Route::get('/getChefReview', 'getChefReview'); //without Auth can access // chef panel
        Route::post('/getChefDetails', 'getChefDetails'); // user controller api same name api in chef controller
        Route::post('/getCountOftheChefAvailableForNext30Days', 'getCountOftheChefAvailableForNext30Days'); //without Auth can access
        Route::post('/recordNotFoundSubmit', 'recordNotFoundSubmit');
        Route::post('/changePassword', 'changePassword'); //without token
        Route::post('/getChefsByPostalCodeAndCuisineTypes', 'getChefsByPostalCodeAndCuisineTypes');
        Route::post('/calculateDistanceUsingTwoLatlong', 'calculateDistanceUsingTwoLatlong');
        Route::controller(stripeController::class)->group(function () {
            Route::post('/retriveStripePaymentStatus', 'retriveStripePaymentStatus');
        });
        Route::controller(OrderController::class)->group(function () {
            Route::post('/placeOrders', 'placeOrders');
        });
    });
    Route::controller(ChefController::class)->group(function () {
        Route::post('/getFoodItemsForCustomer', 'getFoodItemsForCustomer'); //without Auth can access make by sarita
    });
    Route::controller(kitchentypeController::class)->group(function () {
        Route::get('/getKitchenTypes', 'getKitchenTypes'); //without Auth can access
    });
    Route::controller(commonFunctions::class)->group(function () {
        Route::get("/getAllFoodTypes", 'getAllFoodTypes'); //without Auth can access
        Route::get("/getAllAllergens", 'getAllAllergens'); //without Auth can access
        Route::get("/getAllSiteSettings", 'getAllSiteSettings'); //without Auth can access
        Route::get("/getSiteFeedback", 'getSiteFeedback'); //without Auth can access\
    });

    Route::group(['middleware' => 'auth.user'], function () {
        Route::controller(AdminController::class)->group(function () {
            Route::post("/getAdminOrderDetailsById", 'getAdminOrderDetailsById'); //without Auth can access
            Route::post('/ChefReviewInAdmin', 'ChefReviewInAdmin');
        });
        Route::controller(OrderController::class)->group(function () {
            // Route::post('/placeOrders', 'placeOrders');
            Route::post('/acceptOrRejectOrder', 'acceptOrRejectOrder');
            Route::get('/trackOrder', 'trackOrder');
            Route::get('/getAllTransactions', 'getAllTransactions');
            Route::post('/calculate_tax', 'calculate_tax');
            Route::post('/checkAndMakeChefInactive', 'checkAndMakeChefInactive');

        });
        Route::controller(CartController::class)->group(function () {
            Route::post('/addToCart', 'addToCart');
            Route::post('/getMyCart', 'getMyCart');
            Route::post('/changeQuantity', 'changeQuantity');
            Route::post('/removeItemFromCart', 'removeItemFromCart');
            Route::post('/addBeforeLoginCartData', 'addBeforeLoginCartData');
            Route::post('/updateCartDeliveryDate', 'updateCartDeliveryDate');
        });
        Route::controller(UserController::class)->group(function () {
            Route::get('/userProfile', 'userProfile');
            Route::post('/userLogout', 'userLogout');
            Route::post('/userRefreshToken', 'userRefreshToken');

            Route::post('/getUserDetails', 'getUserDetails');
            Route::post('/updateUserDetail', 'updateUserDetail');
            Route::post('/updateUserDetailStatus', 'updateUserDetailStatus');
            Route::post('/updatePaymentMethod', 'updatePaymentMethod');

            Route::get('/getRecordNotFound', 'getRecordNotFound');

            Route::post('/addUpdateShippingAddress', 'addUpdateShippingAddress');
            Route::post('/getAllShippingAdressOfUser', 'getAllShippingAdressOfUser');
            Route::post('/changeDefaultShippingAddress', 'changeDefaultShippingAddress');
            Route::post('/deleteShippingAddress', 'deleteShippingAddress');

            Route::post('/addUserContacts', 'addUserContacts');
            Route::post('/updateContactStatus', 'updateContactStatus');
            Route::get('/getUserContact', 'getUserContact');

            Route::post('/ChefReview', 'ChefReview');
            Route::post('/deleteChefReview', 'deleteChefReview');

            Route::post('/VerifyUserEmail', 'VerifyUserEmail');

            Route::post('/addOrUpdateFoodReview', 'addOrUpdateFoodReview');
            Route::post('/getAllFoodReview', 'getAllFoodReview'); // chef panel
            Route::post('/updateUserFoodReviewStatus', 'updateUserFoodReviewStatus');
            Route::post('/deleteUserFoodReview', 'deleteUserFoodReview');
            Route::post('/getAllUserFoodReviewsbyStatus', 'getAllUserFoodReviewsbyStatus'); // use only admin

            Route::post('/updateUserChefReviewStatus', 'updateUserChefReviewStatus');
            Route::post('/deleteUserChefReview', 'deleteUserChefReview');
            Route::post('/getAllUserChefReviewsbyStatus', 'getAllUserChefReviewsbyStatus');

            Route::post('/getUserOrders', 'getUserOrders');
            Route::post('/getUserOrderDetails', 'getUserOrderDetails');

            Route::get('/userOrderInvoicePDF', 'userOrderInvoicePDF');
            Route::post('/searchFood', 'searchFood');

            Route::post('/getPostalCode', 'getPostalCode');
            Route::post('/updatePostalCode', 'updatePostalCode');

        });
    });
    Route::controller(taxController::class)->group(function () {
        Route::get('/getTaxType', 'getTaxType');
    });
    Route::controller(stripeController::class)->group(function () {
        Route::post('/create-checkout-session', 'createSession');
        // Route::post('/retriveStripePaymentStatus', 'retriveStripePaymentStatus');
        Route::post('/payment-sheet', 'paymentSheet');
    });
});




Route::prefix('admin')->group(function () {
    Route::controller(AdminController::class)->group(function () {
        Route::get('/adminRegistration', 'adminRegistration');
        Route::post('/adminLogin', 'adminLogin');
    });

    Route::group(['middleware' => 'auth.admin'], function ($router) {
        Route::controller(DriverController::class)->group(function () {
            Route::get('/getAllDriver', 'getAllDriver'); // admin panel
            Route::get('/getDriverSuggestions', 'getDriverSuggestions');
            Route::post('/deleteDriver', 'deleteDriver');
            Route::get('/getDriverContact', 'getDriverContact');
            Route::post('/updateDriverProfileStatus', 'updateDriverProfileStatus');
            Route::get('/getDriverFinalReview', 'getDriverFinalReview');
            Route::post('/getDriverOrders', 'getDriverOrders');
        });
        Route::controller(OrderController::class)->group(function () {
            // Route::post('/placeOrders', 'placeOrders');
            // Route::post('/acceptOrRejectOrder', 'acceptOrRejectOrder');
            Route::get('/trackOrder', 'trackOrder');
            Route::get('/getAllTransactions', 'getAllTransactions');
            // Route::post('/calculate_tax', 'calculate_tax');
        });
        Route::controller(commonFunctions::class)->group(function () {
            Route::get("/getAllScheduleCall", 'getAllScheduleCall'); // admin panel
            Route::get("/getAllDriverScheduleCall", 'getAllDriverScheduleCall'); // admin panel
        });
        Route::controller(UserController::class)->group(function () {
            Route::get('/getUserContact', 'getUserContact');
            Route::post('/updateUserDetailStatus', 'updateUserDetailStatus');
            Route::get('/getRecordNotFound', 'getRecordNotFound');
            Route::get('/getChefReview', 'getChefReview');
            Route::post('/getAllUserFoodReviewsbyStatus', 'getAllUserFoodReviewsbyStatus');
            Route::post('/addOrUpdateFoodReview', 'addOrUpdateFoodReview');
            Route::post('/updateContactStatus', 'updateContactStatus');
        });
        Route::controller(ChefController::class)->group(function () {
            Route::post('/deleteChefSchedule', 'deleteChefSchedule');
            Route::post('/getAllPendingRequest', 'getAllPendingRequest');
            Route::get('/getChefRegisterationRequest', 'getChefRegisterationRequest');
            Route::post('/updateChefDetailsStatus', 'updateChefDetailsStatus');
            Route::post('/getMyFoodItems', 'getMyFoodItems');
            Route::post('/deleteChef', 'deleteChef');
            Route::post('/updateFoodCertificateStatus', 'updateFoodCertificateStatus');
            Route::post('/getChefOrders', 'getChefOrders');
            Route::get('/orderInvoicePDF', 'orderInvoicePDF'); //chefcontroller
            Route::get('/getFoodLicenseList', 'getFoodLicenseList');
            Route::post('/updateFoodItemAppprovedStatus', 'updateFoodItemAppprovedStatus');
            Route::post('/getChefDetails', 'getChefDetails');
            Route::post('/updateChefOrderStatus', 'updateChefOrderStatus');
        });
        Route::controller(AdminController::class)->group(function () {
            Route::get('/adminProfile', 'adminProfile');
            Route::post('/adminLogout', 'adminLogout');
            Route::post('/adminRefreshToken', 'adminRefreshToken');
            Route::post('/updateSiteSettings', 'updateSiteSettings');
            Route::post('/deleteSiteSettings', 'deleteSiteSettings');
            Route::get('/getSiteSettings', 'getSiteSettings');

            Route::get('/getAllContactData', 'getAllContactData');
            Route::post('/updateContactDataStatus', 'updateContactDataStatus');
            Route::post('/deleteContactData', 'deleteContactData');

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
            Route::post('/updateChangeRequestStatus', 'updateChangeRequestStatus'); // Function not found
            Route::get('/getAllRequestForChefReviewDeletion', 'getAllRequestForChefReviewDeletion');
            Route::post('/updateStatusOfChefReviewDeleteRequest', 'updateStatusOfChefReviewDeleteRequest');
            Route::get('/getAllBlackListRequestByChef', 'getAllBlackListRequestByChef');
            Route::post('/blacklistUserOnChefRequest', 'blacklistUserOnChefRequest');
            Route::post('/unBlackListUser', 'unBlackListUser');
            //new
            Route::get('/getAllChefSuggestions', 'getAllChefSuggestions');
            Route::get('/getAdminDshboardCount', 'getAdminDshboardCount');

            Route::get('/getAllSubOrderDetails', 'getAllSubOrderDetails');
            Route::post('/getAllOrderDetails', 'getAllOrderDetails');
            Route::post('/getAdminOrderDetailsById', 'getAdminOrderDetailsById');
            Route::post('/getAdminSubOrderDetailsById', 'getAdminSubOrderDetailsById');

            Route::post('/addUpdateTemplateForFoodPackagingInstruction', 'addUpdateTemplateForFoodPackagingInstruction');
            Route::post('/updateTemplateStatus', 'updateTemplateStatus');
            Route::post('/deleteTemplate', 'deleteTemplate');
            Route::get('/getTemplates', 'getTemplates');

            Route::get('/templatePDF', 'templatePDF');
            Route::get('/getChefAcceptedSuborder', 'getChefAcceptedSuborder');
            Route::get('/getSubOrderByDriver', 'getSubOrderByDriver');
            Route::get('/getSubOrderAcceptedByChef', 'getSubOrderAcceptedByChef');
            Route::post('/storeChefChecklist', 'storeChefChecklist');
            Route::get('/getChefChecklist', 'getChecklist');
            Route::post('/storeDriverChecklist', 'storeDriverChecklist');
            Route::post('/ChefReviewInAdmin', 'ChefReviewInAdmin');
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
        Route::controller(notificationController::class)->group(function () {
            Route::post('/getUnreadNotificationAccordingToUserTypes', 'getUnreadNotificationAccordingToUserTypes'); // chef panel
            Route::post('/deleteNotification', 'deleteNotification');
            Route::post('/markAsReadNotification', 'markAsReadNotification');
        });
        Route::controller(taxController::class)->group(function () {
            Route::post('/addTaxType', 'addTaxType');
            Route::post('/updateTaxType', 'updateTaxType');
            Route::post('/deleteTaxType', 'deleteTaxType');
            Route::get('/getTaxType', 'getTaxType');
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
        Route::controller(kitchentypeController::class)->group(function () {
            Route::post('/addKitchenTypes', 'addKitchenTypes');
            Route::get('/getKitchenTypes', 'getKitchenTypes'); //without Auth can access // chef panel
            Route::post('/updateKitchenTypes', 'updateKitchenTypes');
            Route::post('/deleteKitchenTypes', 'deleteKitchenTypes');
            Route::post('/updateKitchentypeStatus', 'updateKitchentypeStatus');
        });
    });
});



Route::controller(otpController::class)->group(function () {
    Route::post('/sendOTP', 'sendOTP'); // without token
    Route::post('/verifyOtp', 'verifyOtp'); // without token Used in Driver/Delivery Panel
    Route::post('/driverSendOTP', 'driverSendOTP'); // Used in Driver/Delivery Panel
    Route::post('/DriverOtp', 'DriverOtp'); // Used in Driver/Delivery Panel
});


Route::controller(commonFunctions::class)->group(function () {
    Route::get("/getAllBankList", 'getAllBankList'); // chef panel
    Route::post("/getDocumentListAccToChefTypeAndState", 'getDocumentListAccToChefTypeAndState'); // chef panel
    Route::get("/getAllSiteSettings", 'getAllSiteSettings'); //without Auth can access // chef panel
    Route::get("/getAllFoodTypes", 'getAllFoodTypes'); //without Auth can access
    Route::get("/getAllHeatingInstructions", 'getAllHeatingInstructions');
    Route::get("/getAllAllergens", 'getAllAllergens'); //without Auth can access
    Route::get("/getAllDietaries", 'getAllDietaries');
    Route::get("/getAllIngredients", 'getAllIngredients');
    Route::post("/giveSiteFeedback", "giveSiteFeedback");
    Route::get("/getSiteFeedback", 'getSiteFeedback'); //without Auth can access
    Route::post("/get_lat_long", 'get_lat_long');
    Route::post("/updateSiteFeedbackStatus", 'updateSiteFeedbackStatus');
    Route::post("/updateScheduleCallStatus", 'updateScheduleCallStatus');
    Route::get("/getAllScheduleCall", 'getAllScheduleCall'); // admin panel

    Route::get("/getAllDriverScheduleCall", 'getAllDriverScheduleCall'); // admin panel
    Route::post("/updateDriverScheduleCallStatus", 'updateDriverScheduleCallStatus');

    Route::post('/sendPasswordResetLink', 'sendPasswordResetLink'); // chef panel
    Route::post('/changePasswordwithToken', 'changePasswordwithToken'); // chef panel
    Route::post('/verifyToken', 'verifyToken');

    Route::get('/additional-link', 'getHowToApply');
    Route::get('/getOrderStatus', 'getOrderStatus');
    Route::get("getBankDetail", 'getBankDetail'); // chef panel

});

// Routes for authorize
Route::group(['middleware' => 'auth.user'], function () {
    Route::prefix('/authorize-payment')->group(function(){
        Route::get('', [AuthorizePaymentController::class, 'paymentTest']);
        Route::post('accept-payment', [AuthorizePaymentController::class, 'createAnAcceptPaymentTransaction']);
        Route::post('paypal-payment', [AuthorizePaymentController::class, 'paypalTransaction']);
        Route::post('paypal-payment-status', [AuthorizePaymentController::class, 'checkPaymentStatus']);
    });
});