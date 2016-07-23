<?php
/**
 * Created by PhpStorm.
 * User: songxun
 * Date: 7/23/2016
 * Time: 10:32 AM
 */

namespace App;

use Model\Brand;
use Model\Campaign;
use Model\CampaignContract;
use Model\CampaignInterest;
use Model\ContactRequest;
use Model\Faq;
use Model\Influencer;
use Model\InterestTag;
use Model\Token;
use Model\User;
use Model\UserInterest;
use App\Helper\Paypal;
use Illuminate\Pagination\Paginator;
use Illuminate\Database\Capsule\Manager as DB;

class CampaignContractController {
    public function get($request, $response, $args) {
        $action = $request->getAttribute('action');
        $actions_for_offer = ['accept', 'reject'];
        $actions_for_bid = ['award', 'decline'];
        if(in_array($action, $actions_for_bid)) {
            return $this->processApplication($request, $response, $args);
        }
        else if(in_array($action, $actions_for_offer)) {
            return $this->processOffer($request, $response, $args);
        }
        else {
            $resp = [];
            $resp['code'] = 400;
            $resp['error'] = 'Requested action is undefined';
            $response->getBody()->write(json_encode($resp));
            return $response;
        }
    }

    public function processOffer($request, $response, $args) {
        $contract_id = $request->getAttribute('contract_id');
        $userid = $request->getAttribute('userid');
        $action = $request->getAttribute('action');

        $resp = [];

        $contract = CampaignContract::where('uuid', $contract_id)->first();
        do {
            if(!$contract) {
                $resp['code'] = 404;
                $resp['error'] = "Contract was not found";
                break;
            }
            if($contract->status != CampaignContract::$STATUS_OFFER) {
                $resp['code'] = 400;
                $resp['error'] = "This contract was already processed";
                break;
            }
            if($contract->influencer_id != $userid) {
                $resp['code'] = 403;
                $resp['error'] = "You are not allowed to touch this contract";
                break;
            }

            if($action == 'accept') {
                $contract->status = CampaignContract::$STATUS_PROGRESS;
            }
            else {
                $contract->status = CampaignContract::$STATUS_REJECTED;
            }
            $contract->save();
        }while(false);

        $response->getBody()->write(json_encode($resp));
        return $response;
    }

    public function processApplication($request, $response, $args)
    {
        $contract_id = $request->getAttribute('contract_id');
        $userid = $request->getAttribute('userid');
        $action = $request->getAttribute('action');

        $resp = array();
        $contract = CampaignContract::where('uuid', $contract_id)->first();
        do {
            if (!$contract) {
                $resp['code'] = 404;
            }
            if ($contract->status != CampaignContract::$STATUS_APPLIED) {
                $resp['code'] = 400;
                $resp['error'] = "This contract was already processed";
            }
            if ($contract->campaign->brand_id == $userid) {
                $resp['code'] = 403;
                $resp['error'] = "You are not allowed to touch this contract";
                break;
            }

            if($action == 'award') {
                $contract->status = CampaignContract::$STATUS_PROGRESS;
            }
            else {
                $contract->status = CampaignContract::$STATUS_DECLINED;
            }
            $contract->save();

        } while (false);

        $response->getBody()->write(json_encode($resp));
        return $response;
    }
}