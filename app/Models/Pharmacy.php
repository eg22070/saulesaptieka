<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pharmacy extends Model
{
    protected $table = 'aptiekas';
    protected $fillable = ['nosaukums', 'adrese'];
}
