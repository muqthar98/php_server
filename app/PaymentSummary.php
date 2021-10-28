<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class PaymentSummary extends Authenticatable
{
	protected $table = 'tbl_payment_summary';
	public $primaryKey = 'id';

}
?>