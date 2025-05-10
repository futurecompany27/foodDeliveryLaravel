<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;
use App\Http\Controllers\Controller;
use App\Mail\OrderPlacedMailToUser;
use App\Models\Admin;
use App\Models\Cart;
use App\Models\Chef;
use App\Models\Order;
use App\Models\OrderItems;
use App\Models\OrderTrackDetails;
use App\Models\SubOrders;
use App\Models\User;
use App\Notifications\admin\newOrderPlacedForAdmin;
use App\Notifications\Chef\newOrderPlacedForChef;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class AuthorizePaymentController extends Controller
{
    public function paymentTest()
    {
        return response()->json(['message' => 'Welcome to Payment', 'success' => true], 200);
    }

    public function createAnAcceptPaymentTransaction(Request $laravelRequest)
    {
        $user = $laravelRequest->user();

        return $this->getTransactionListForCustomerRequest($user->id);
        $user_type = $laravelRequest->user_type;

        $opaqueData = $laravelRequest->opaqueData;
        $amount = $laravelRequest->amount;
        $txn_type = $laravelRequest->transaction_type;
        $txn_status = 'paid';
        $txn_remark = $laravelRequest->remark;

        $orderData = $laravelRequest->order_data;

        $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
        $merchantAuthentication->setName(env('AUTHORIZE_LOGIN_ID'));
        $merchantAuthentication->setTransactionKey(env('AUTHORIZE_TRANSACTION_KEY'));

        $refId = 'ref' . time();

        $opaquePayment = new AnetAPI\OpaqueDataType();
        $opaquePayment->setDataDescriptor($opaqueData['dataDescriptor']);
        $opaquePayment->setDataValue($opaqueData['dataValue']);

        $paymentType = new AnetAPI\PaymentType();
        $paymentType->setOpaqueData($opaquePayment);

        $customerData = new AnetAPI\CustomerDataType();
        $customerData->setId($user->id);
        $customerData->setEmail($user->email);

        $transactionRequest = new AnetAPI\TransactionRequestType();
        $transactionRequest->setTransactionType("authCaptureTransaction");
        $transactionRequest->setAmount($amount);
        $transactionRequest->setPayment($paymentType);
        $transactionRequest->setCustomer($customerData);

        $apiRequest = new AnetAPI\CreateTransactionRequest();
        $apiRequest->setMerchantAuthentication($merchantAuthentication);
        $apiRequest->setRefId($refId);
        $apiRequest->setTransactionRequest($transactionRequest);

        $controller = new AnetController\CreateTransactionController($apiRequest);
        $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::SANDBOX);

        if ($response !== null && $response->getMessages()->getResultCode() === "Ok") {
            $tresponse = $response->getTransactionResponse();
            if ($tresponse !== null && $tresponse->getResponseCode() === "1") {
                
                // Order data will only come for customer, or else for chef certificate no order will be generated
                if($orderData){
                    $txn_type = Transaction::TYPE_ORDER;
                    $this->addOrder($user ,$orderData, $tresponse->getTransId());
                }

                // Adding transaction
                $this->addTransaction($txn_type, $user_type, $user->id, $txn_remark, $txn_status, $amount, $tresponse->getTransId());
                return response()->json([
                    'success' => true,
                    'transaction_id' => $tresponse->getTransId(),
                    'auth_code' => $tresponse->getAuthCode(),
                    'message' => $tresponse->getMessages()[0]->getDescription(),
                    'tresponse' => $tresponse
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

    public function getTransactionList(){

    }

    private function addTransaction($transaction_type, $user_type, $user_id, $remark, $status, $amount, $tx_no)
    {
        $transaction = new Transaction();
        $transaction->user_type = $user_type;
        $transaction->transaction_type = $transaction_type;
        $transaction->user_id = $user_id;
        $transaction->remark = $remark;
        $transaction->status = $status;
        $transaction->amount = $amount;
        $transaction->txn_no = $tx_no;
        $transaction->save();

        return $transaction;
    }


    private function getTransactionListForCustomerRequest($customerProfileId)
    {
        /* Create a merchantAuthenticationType object with authentication details
       retrieved from the constants file */
        $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
        $merchantAuthentication->setName(env('AUTHORIZE_LOGIN_ID'));
        $merchantAuthentication->setTransactionKey(env('AUTHORIZE_TRANSACTION_KEY'));

        $request = new AnetAPI\GetUnsettledTransactionListRequest();
        $request->setMerchantAuthentication($merchantAuthentication);
        $controller = new AnetController\GetUnsettledTransactionListController($request);
        $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::SANDBOX);

        $controller = new AnetController\GetTransactionListController($request);

        $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::SANDBOX);

        if (($response != null) && ($response->getMessages()->getResultCode() == "Ok")) {
            $transIds = [];
            $res = $response->getTransactions();
            if($res){
                foreach($res as $tx){
                    $transIds[] = $tx->getTransId();
                }
            }   
            return $transIds;
        } else {
            echo "ERROR :  Invalid response\n";
            $errorMessages = $response->getMessages()->getMessage();
            echo "Response : " . $errorMessages[0]->getCode() . "  " . $errorMessages[0]->getText() . "\n";
        }

        return $response;
    }

    
}
