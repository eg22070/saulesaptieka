<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pasutijums extends Model
{
    use HasFactory;

    protected $table = 'pasutijumi';

    protected $fillable = [
        'created_by',
        'datums',
        'artikula_id',
        'skaits',
        'pasutijuma_numurs',
        'receptes_numurs',
        'vards_uzvards',
        'talrunis_epasts',
        'pasutijuma_datums',
        'komentari',
        'statuss',
        'hide_from_visiem',
        'who_completed',
        'completed_at',
    ];

    protected $casts = [
        'datums' => 'date',
        'pasutijuma_datums' => 'date',
        'completed_at' => 'datetime',
    ];

    public function product()
    {
        // Product model in your repo is App\Models\Product
        return $this->belongsTo(Product::class, 'artikula_id');
    }
    public function completer()
    {
        return $this->belongsTo(\App\Models\User::class, 'who_completed');
    }
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
