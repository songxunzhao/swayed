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

    public function influencer()
    {
        return $this->hasOne('\Influencer');
    }

    public function brand()
    {
        return $this->hasOne('\Brand');
    }
    public toProfileArray(){

        $data = [
            'uuid' => $this->uuid
            'email' => $this->email,
            'name' => $this->name,
            'user_type'=> $this->user_type
        ];

        if($this->user_type == "brand")
        {
            $data = array_merge($data, $this->brand->toArray());
        }
        else if($this->user_type == "influencer")
        {
            $data = array_merge($data, $this->influencer->toArray());
        }
        return $data;
    }
}