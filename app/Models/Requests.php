<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Requests extends Model
{
    protected $table = 'pieprasijumi';
    protected $fillable = [
        'datums', 'aptiekas_id', 'artikula_id', 'daudzums', 'izrakstitais_daudzums',
        'pazinojuma_datums', 'statuss', 'aizliegums', 'iepircejs', 'piegades_datums', 'piezimes', 'completed'
    ];

    public function aptiekas()
    {
        return $this->belongsTo(Pharmacy::class, 'aptiekas_id');
    }

    public function artikuli()
    {
        return $this->belongsTo(Product::class, 'artikula_id');
    }
}
