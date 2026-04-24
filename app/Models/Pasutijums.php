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
        'farmaceita_nosaukums',
        'skaits',
        'pasutijuma_numurs',
        'receptes_numurs',
        'vards_uzvards',
        'talrunis_epasts',
        'arstniecibas_iestade',
        'arsts',
        'pasutijuma_datums',
        'komentari',
        'statuss',
        'hide_from_visiem',
        'pieprasijuma_id',
        'who_completed',
        'who_cancelled',
        'completed_at',
        'cancelled_at',
        'previous_artikuli_ids',
    ];

    protected $casts = [
        'datums' => 'date',
        'pasutijuma_datums' => 'date',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'previous_artikuli_ids' => 'array',
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
    public function canceller()
    {
        return $this->belongsTo(\App\Models\User::class, 'who_cancelled');
    }

    public function pieprasijums()
    {
        return $this->belongsTo(PasutijumuPieprasijums::class, 'pieprasijuma_id');
    }
}
