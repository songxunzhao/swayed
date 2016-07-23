<?php
/**
 * Created by PhpStorm.
 * User: songxun
 * Date: 7/22/2016
 * Time: 9:34 AM
 */
namespace App\Helper;
use App\Loader\Config;
use PayPal\Service\AdaptivePaymentsService;
use PayPal\Types\AP\PayRequest;
use PayPal\Types\AP\ReceiverList;
use PayPal\Types\Common\RequestEnvelope;

class Paypal{
    public function __construct() {

    }
    public function createPayRequest($from_email, $to_email, $amount) {
        $ret = [];
        $requestEnvelope = new RequestEnvelope();
        $actionType = 'PAY_PRIMARY';
        $currencyCode = 'USD';
        $cancelUrl = '';
        $returnUrl = '';

        $receivers = [];

        $receiver = new Receiver();
        $receiver->email = '';
        $receiver->amount = 0;
        $receiver->primary = true;
        $receivers[] = $receiver;

        $receiver = new Receiver();
        $receiver->email = $to_email;
        $receiver->amount = $amount;
        $receiver->primary = false;
        $receivers[] = $receiver;

        $receiverList = new ReceiverList($receivers);

        $payRequest = new PayRequest($requestEnvelope, $actionType, $cancelUrl,
            $currencyCode, $receiverList, $returnUrl);

        $service = new AdaptivePaymentsService(Config::loadConfig('paypal'));
        try {
            /* wrap API method calls on the service object with a try catch */
            $response = $service->Pay($payRequest);
        } catch(Exception $ex) {
            exit;
        }
        $ack = strtoupper($response->responseEnvelope->ack);
        if($ack != "SUCCESS") {
            $ret['success'] = false;
        }
        else {
            $payKey = $response->payKey;
            $execURL = "ExecutePayment.php?payKey=$payKey";
            $ret['success'] = true;
            $ret['paykey'] = $payKey;
            print_r($response);
        }
        return $ret;

    }
    public function declinePayRequest() {

    }
}