<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PasswordResetToken extends Model
{
    protected $fillable = [
        'email',
        'document_type',
        'document_number',
        'token',
        'created_at',
    ];

    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = 'email';
    protected $keyType = 'string';

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Verifica si el token ha expirado (30 minutos)
     */
    public function isExpired(): bool
    {
        return Carbon::parse($this->created_at)->addMinutes(30)->isPast();
    }

    /**
     * Scope para buscar tokens válidos
     */
    public function scopeValid($query)
    {
        return $query->where('created_at', '>', Carbon::now()->subMinutes(30));
    }

    /**
     * Elimina tokens expirados
     */
    public static function deleteExpired(): void
    {
        static::where('created_at', '<', Carbon::now()->subMinutes(30))->delete();
    }
}