<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class UsersCoupon extends Authenticatable
{
	// protected $connection = '/tenant';
	protected $table = 'tbl_users_coupon';
	public $primaryKey = 'id';

}
?>