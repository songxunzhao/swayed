<?php
namespace App\Model;
class Campaign extends \Illuminate\Database\Eloquent\Model
{

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'campaign';
    protected $fillable = ['main_image', 'name', 'objective',
        'allow_action', 'ban_action', 'detail_images', 'hash_tags'];

    public function brand() {
        return $this->belongsTo('Model\User', 'brand_id', 'uuid');
    }

    public function toArray()
    {
        $data = parent::toArray();
        $data['hashtags'] = json_decode($this->hashtags);
        $data['detail_images'] = json_decode($this->detail_images);

        $tag_list = [];
        $interests = CampaignInterest::where('campaign_id', $this->uuid)->get();
        foreach ($interests as $interest) {
            $tag_list[] = $interest->tag;
        }
        $data['interest_tags'] = $tag_list;

        if ($this->brand) {
            $data['brand'] = $this->brand->toSummaryBrandArray();
        }
        return $data;
    }
}