<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\SwitchDB;

class Product extends Authenticatable
{
    // protected $connection = 'tenant';
	protected $table = 'tbl_products';
	public $primaryKey = 'id';
}
?>