<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Area extends Authenticatable
{
	// protected $connection = '/tenant';
	protected $table = 'tbl_area';
	public $primaryKey = 'id';
}
?>