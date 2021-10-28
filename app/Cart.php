<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Cart extends Authenticatable
{
    // protected $connection = 'tenant';
	protected $table = 'tbl_cart';
	public $primaryKey = 'id';

}
?>