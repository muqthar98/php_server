<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class UserDeliveryAddress extends Authenticatable
{
	protected $table = 'tbl_user_delivery_address';
	public $primaryKey = 'id';

	public static function get_random_string($field_code='delivery_address_id')
	{
        $random_unique  =  sprintf('%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));

        $user = UserDeliveryAddress::where('delivery_address_id', '=', $random_unique)->first();
        if ($user != null) {
            $this->get_random_string();
        }
        return $random_unique;
    }
}
?>