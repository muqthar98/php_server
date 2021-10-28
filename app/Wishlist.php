<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Wishlist extends Authenticatable
{
    // protected $connection = 'tenant';
	protected $table = 'tbl_wishlist';
	public $primaryKey = 'id';

}
?>