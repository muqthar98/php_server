<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class ProductPriceUnit extends Authenticatable
{
    // protected $connection = 'tenant';
	protected $table = 'tbl_product_price_unit';
	public $primaryKey = 'id';

}
?>