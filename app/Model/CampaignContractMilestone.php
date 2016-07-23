<?php
/**
 * Created by PhpStorm.
 * User: songxun
 * Date: 7/23/2016
 * Time: 10:52 AM
 */

namespace App\Model;


class CampaignContractMilestone {
    protected $table = 'campaign_contract';
    public function campaign_contract() {
        return $this->belongsTo('App\Model\CampaignContract', 'contract_id', 'uuid');
    }
}