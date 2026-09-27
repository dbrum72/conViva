<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpensePaymentReceipt extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['path'];
}
