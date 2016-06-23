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
    protected $fillable = ['user_id'];
}