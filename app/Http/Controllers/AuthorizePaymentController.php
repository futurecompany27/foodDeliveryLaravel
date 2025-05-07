<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

class AuthorizePaymentController extends Controller
{
    public function paymentTest()
    {
        return response()->json(['message' => 'Welcome to Payment', 'success' => true], 200);
    }

    public function createAnAcceptPaymentTransaction(Request $laravelRequest)
    {
        $user = $laravelRequest->user();
        $user_type = $laravelRequest->input('user_type');

        $opaqueData = $laravelRequest->input('opaqueData');
        $amount = $laravelRequest->input('amount');
        $txn_type = $laravelRequest->input('transaction_type');
        $txn_status = 'paid';
        $txn_remark = $laravelRequest->input('remark');

        $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
        $merchantAuthentication->setName(env('AUTHORIZE_LOGIN_ID'));
        $merchantAuthentication->setTransactionKey(env('AUTHORIZE_TRANSACTION_KEY'));

        $refId = 'ref' . time();

        $opaquePayment = new AnetAPI\OpaqueDataType();
        $opaquePayment->setDataDescriptor($opaqueData['dataDescriptor']);
        $opaquePayment->setDataValue($opaqueData['dataValue']);

        $paymentType = new AnetAPI\PaymentType();
        $paymentType->setOpaqueData($opaquePayment);

        $transactionRequest = new AnetAPI\TransactionRequestType();
        $transactionRequest->setTransactionType("authCaptureTransaction");
        $transactionRequest->setAmount($amount);
        $transactionRequest->setPayment($paymentType);

        // ✅ Rename to avoid variable conflict
        $apiRequest = new AnetAPI\CreateTransactionRequest();
        $apiRequest->setMerchantAuthentication($merchantAuthentication);
        $apiRequest->setRefId($refId);
        $apiRequest->setTransactionRequest($transactionRequest);

        $controller = new AnetController\CreateTransactionController($apiRequest);
        $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::SANDBOX);

        if ($response !== null && $response->getMessages()->getResultCode() === "Ok") {
            $tresponse = $response->getTransactionResponse();
            if ($tresponse !== null && $tresponse->getResponseCode() === "1") {
                return response()->json([
                    'success' => true,
                    'transaction_id' => $tresponse->getTransId(),
                    'auth_code' => $tresponse->getAuthCode(),
                    'message' => $tresponse->getMessages()[0]->getDescription(),
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $tresponse ? $tresponse->getErrors()[0]->getErrorText() : 'Transaction failed.',
                ], 400);
            }
        } else {
            $message = $response?->getMessages()?->getMessage()[0]?->getText() ?? 'Unknown error';
            return response()->json(['success' => false, 'error' => $message], 400);
        }
    }

    private function addTransaction(){

    }

    private function addOrder(){
        
    }
}
