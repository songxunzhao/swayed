<?php 
namespace Model;
class Influencer extends \Illuminate\Database\Eloquent\Model
{

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
    public function user() {
        return $this->belongsTo('Model\User', 'user_id', 'uuid');
    }
	protected $table = 'influencer';
    protected $fillable = ['user_id', 'social_id',
                            'social_token', 'gender', 'country',
                            'city', 'description','profile_img',
                            'rate', 'reach_num','num_photos'];

    public function toProfileArray(){
        $data = $this->toArray();
        
        $user = $this->user;
        $user_data = [
            'uuid' => $user->uuid,
            'email' => $user->email,
            'name' => $user->name,
            'user_type'=> $user->user_type
        ];

        $data = array_merge($data, $user_data);
        return $data;
    }
}