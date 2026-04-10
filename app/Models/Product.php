<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'artikuli';
    protected $fillable = ['nosaukums', 'id_numurs', 'valsts', 'snn', 'analogs', 'atzimes', 'atk',
    'info',
    'pielietojums',
    'atk_validity_days',
    'hide_from_kruzes',
    'hide_from_farmaceiti',
    'without_arst',
    'nemedikamenti',];
}
