<?php
/**
 * Created by PhpStorm.
 * User: songxun
 * Date: 7/23/2016
 * Time: 10:52 AM
 */

namespace Model;


class CampaignContractMilestone {
    protected $table = 'campaign_contract';
    public function campaign_contract() {
        return $this->belongsTo('Model\CampaignContract', 'contract_id', 'uuid');
    }
}