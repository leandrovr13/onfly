<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TravelOrder extends Model
{
    use HasFactory;

    /**
     * Campos que podem ser preenchidos em massa.
     */
    protected $fillable = [
        'user_id',
        'destination',
        'departure_date',
        'return_date',
        'status',
    ];

    /**
     * Datas que devem ser tratadas como instâncias de Carbon.
     */
    protected $casts = [
        'departure_date' => 'date',
        'return_date'    => 'date',
    ];

    /**
     * Usuário que criou o pedido de viagem.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
