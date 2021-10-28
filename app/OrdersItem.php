<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class OrdersItem extends Authenticatable
{
	protected $table = 'tbl_orders_item';
	public $primaryKey = 'id';

	public static function get_random_string($field_code='item_id')
	{
        $random_unique  =  sprintf('%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));

        $item = OrdersItem::where('item_id', '=', $random_unique)->first();
        if ($item != null) {
            $this->get_random_string();
        }
        return $random_unique;
    }
}
?>