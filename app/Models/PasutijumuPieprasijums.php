<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasutijumuPieprasijums extends Model
{
    protected $table = 'pasutijumu_pieprasijumi';

    protected $fillable = [
        'datums',
        'completed',
        'completed_at',
        'created_by',
        'who_completed',
        'aizliegums_by_artikuls',
    ];

    protected $casts = [
        'datums' => 'date',
        'completed' => 'boolean',
        'completed_at' => 'datetime',
        'aizliegums_by_artikuls' => 'array',
    ];

    public function pasutijumi()
    {
        return $this->hasMany(Pasutijums::class, 'pieprasijuma_id');
    }

    public function brivieArtikuli()
    {
        return $this->hasMany(PasutijumuPieprasijumaBrivaisArtikuls::class, 'pieprasijuma_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function completer()
    {
        return $this->belongsTo(User::class, 'who_completed');
    }
}
