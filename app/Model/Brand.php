<?php 
namespace App\Model;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'brand';
    protected $fillable = ['user_id', 'description', 'website', 'profile_img'];
}