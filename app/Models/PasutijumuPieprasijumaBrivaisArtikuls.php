<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasutijumuPieprasijumaBrivaisArtikuls extends Model
{
    protected $table = 'pasutijumu_pieprasijuma_brivie_artikuli';

    protected $fillable = [
        'pieprasijuma_id',
        'artikula_id',
        'skaits',
        'arstniecibas_iestade',
        'arsts',
        'created_by',
    ];

    public function pieprasijums()
    {
        return $this->belongsTo(PasutijumuPieprasijums::class, 'pieprasijuma_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'artikula_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
