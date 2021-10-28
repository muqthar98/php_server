<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\SwitchDB;

class Order extends Authenticatable
{
    // protected $connection = 'tenant';
	protected $table = 'tbl_orders';
    public $primaryKey = 'id';
    public static function get_random_string($field_code='order_id')
	{
        $random_unique  =  'VEGIORD'.mt_rand(1000000000, 9999999999);

        $Order = Order::where('order_id', '=', $random_unique)->first();
        if ($Order != null) {
            $this->get_random_string();
        }
        return $random_unique;
    }
}
?>