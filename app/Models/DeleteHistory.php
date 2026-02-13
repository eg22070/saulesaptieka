<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeleteHistory extends Model
{
    protected $fillable = [
        'request_id',
        'datums',
        'aptiekas_id',
        'artikula_id',
        'daudzums',
        'izrakstitais_daudzums',
        'statuss',
        'aizliegums',
        'iepircejs',
        'piegades_datums',
        'piezimes',
        'completed',
        'deleted_at',
    ];
}
