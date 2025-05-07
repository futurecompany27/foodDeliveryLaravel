<?php 
namespace App\Http\Controllers;
// use App\Http\Controllers\Controller;

class AuthorizePaymentController extends Controller{
    public function paymentTest(){
        return response()->json(['message' => 'Welcome to Payment', 'success' => true], 200);
    }
}

?>