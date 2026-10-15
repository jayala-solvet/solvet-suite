<?php

namespace App\Modules\Customers\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = ['name', 'tax_id', 'contact', 'email', 'phone', 'address'];
}
