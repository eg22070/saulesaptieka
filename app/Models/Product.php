<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'artikuli';
    protected $fillable = ['nosaukums', 'id_numurs', 'valsts', 'snn', 'analogs', 'atzimes'];
}
