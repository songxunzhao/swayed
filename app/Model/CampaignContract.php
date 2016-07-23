<?php
namespace App\Model;
class CampaignContract extends \Illuminate\Database\Eloquent\Model
{

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'campaign_contract';
    public function campaign() {
        return $this->belongsTo('Model\Campaign', 'campaign_id', 'uuid');
    }

    public function toArray() {
        $data = parent::toArray();
        if($this->campaign)
            $data['campaign'] = $this->campaign->toArray();

        return $data;
    }
    public static $STATUS_APPLIED = 1;
    public static $STATUS_DECLINED = 2;
    public static $STATUS_OFFER = 3;
    public static $STATUS_REJECTED = 4;
    public static $STATUS_PROGRESS = 5;
    public static $STATUS_CLOSED = 6;
}