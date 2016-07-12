<?php 
namespace Model;
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
        $data = $this->toArray();
        $data['campaign'] = $this->campaign->toArray();

        return $data;
    }
}