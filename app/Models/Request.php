<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    protected $fillable = [
        'datums', 'aptiekas_id', 'artikula_id', 'daudzums', 'izrakstitais_daudzums',
        'pazinojuma_datums', 'statuss', 'aizliegums', 'iepircejs', 'piegades_datums', 'piezimes'
    ];

    public function pharmacy()
    {
        return $this->belongsTo(Pharmacy::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
