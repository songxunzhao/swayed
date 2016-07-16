<?php 
namespace Model;
class User extends \Illuminate\Database\Eloquent\Model
{

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    
	protected $table = 'user';
    protected $hidden = ['password', 'created_at', 'updated_at'];

    public function influencer()
    {
        return $this->hasOne('Model\Influencer');
    }

    public function brand()
    {
        return $this->hasOne('Model\Brand');
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
}