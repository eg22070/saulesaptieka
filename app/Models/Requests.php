<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Requests extends Model
{
    protected $table = 'pieprasijumi';
    protected $fillable = [
        'datums', 'aptiekas_id', 'artikula_id', 'daudzums', 'izrakstitais_daudzums',
        'pazinojuma_datums', 'statuss', 'aizliegums', 'iepircejs', 'piegades_datums', 'piezimes', 'completed', 'completed_at', 'who_completed', 'cito', 
    ];
    protected $casts = [
        'datums' => 'date',
        'completed' => 'boolean',
        'completed_at' => 'datetime', // This is crucial
    ];
    public function aptiekas()
    {
        return $this->belongsTo(Pharmacy::class, 'aptiekas_id');
    }

    public function artikuli()
    {
        return $this->belongsTo(Product::class, 'artikula_id');
    }
    public function completer()
    {
        return $this->belongsTo(User::class, 'who_completed');
    }
}
