<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\SwitchDB;

class OrderReview extends Authenticatable
{
    // protected $connection = 'tenant';
	protected $table = 'tbl_order_review';
	public $primaryKey = 'id';
}
?>