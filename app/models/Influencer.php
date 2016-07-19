<?php 
namespace Model;
class Influencer extends \Illuminate\Database\Eloquent\Model
{

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */

	protected $table = 'influencer';
    protected $fillable = ['user_id', 'gender', 'country',
                            'city', 'description','profile_img',
                            'rate'];

    public function toProfileArray(){
        $data = $this->toArray();
        
        $user = $this->user;
        $user_data = [
            'uuid' => $user->uuid,
            'email' => $user->email,
            'name' => $user->name,
            'user_type'=> $user->user_type,
            'social_id' => $user->social_id,
            'social_token' => $user->social_token,
            'reach_num' => $user->reach_num,
            'num_photos' => $user->num_photos
        ];

        $data = array_merge($data, $user_data);
        return $data;
    }
}