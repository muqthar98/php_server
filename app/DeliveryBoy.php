<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class DeliveryBoy extends Authenticatable
{
	protected $table = 'tbl_delivery_boy';
	public $primaryKey = 'id';

}
?>