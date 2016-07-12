<?php 
namespace Model;
class Campaign extends \Illuminate\Database\Eloquent\Model
{

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'campaign';
    public function contract_list() {
        $this->hasMany('Model\CampaignContract', 'campaign_id', 'uuid');
    }

    public function toArray(){
        $data = parent::toArray();
        $data['hashtags'] = json_decode($this->hashtags);
        $data['detail_images'] = json_decode($this->detail_images);
        return $data;
    }
    public function toDetailArray() {
        $data = $this->toArray();

        $tag_list = [];
        $interests = CampaignInterest::where('campaign_id', $this->uuid)->get();
        foreach($interests as $interest) {
            $tag_list[] = $interest->tag;
        }
        $data['interest_tags'] = $tag_list;
        return $data;
    }
}