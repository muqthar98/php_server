<?php
namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'tbl_users';
    protected $primaryKey  = 'id';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
  
    public static function get_random_string($field_code='user_id')
	{
        $random_unique  =  sprintf('%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));

        $user = User::where('user_id', '=', $random_unique)->first();
        if ($user != null) {
            $this->get_random_string();
        }
        return $random_unique;
    }

    public static function distance($lat1, $lon1, $lat2, $lon2, $unit) {
        if (empty($lat1) && empty($lon1) && empty($lat2) && empty($lon2)) {
          return 0;
        }else
        if (($lat1 == $lat2) && ($lon1 == $lon2)) {
          return 0;
        }
        else {
            $theta = $lon1 - $lon2;
            $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
            $dist = acos($dist);
            $dist = rad2deg($dist);
            $miles = $dist * 60 * 1.1515;
            $unit = strtoupper($unit);
        
            if ($unit == "K") {
              return ($miles * 1.609344);
            } else if ($unit == "N") {
              return ($miles * 0.8684);
            } else {
              return $miles;
            }
        }
      }

}
