<?php
namespace App\Model;
class User extends \Illuminate\Database\Eloquent\Model
{

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table = 'user';
    protected $hidden = ['password', 'created_at', 'updated_at'];
    protected $fillable = ['name', 'social_id', 'social_token'];

    public function influencer()
    {
        return $this->hasOne('App\Model\Influencer', 'user_id', 'uuid');
    }

    public function brand()
    {
        return $this->hasOne('App\Model\Brand', 'user_id', 'uuid');
    }
    public function toProfileArray(){

        $data = $this->toArray();

        if($this->user_type == "brand")
        {
            if($this->brand)
                $data = array_merge($data, $this->brand->toArray());
        }
        else if($this->user_type == "influencer")
        {
            if($this->influencer)
                $data = array_merge($data, $this->influencer->toArray());
        }
        return $data;
    }
    public function toSummaryBrandArray() {
        $data = [];
        $data['name'] = $this->name;
        if($this->user_type == "brand" && $this->brand)
        {
            $data['profile_img'] = $this->brand->profile_img;
            $data['description'] = $this->brand->description;
            $data['website'] = $this->brand->website;
        }
        return $data;
    }
}