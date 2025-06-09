<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles; // <-- Importa el trait de roles

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable; // <-- Agrega HasRoles aquí

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'document_type',
        'document_number',
        'phone',
        'c4c_internal_id',
        'c4c_uuid',
        'is_comodin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_comodin' => 'boolean',
        ];
    }

    /**
     * Get the vehicles for the user.
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    /**
     * Verifica si el usuario es un cliente comodín
     */
    public function isComodin(): bool
    {
        return $this->is_comodin;
    }

    /**
     * Obtiene el documento completo formateado
     */
    public function getFullDocumentAttribute(): string
    {
        return $this->document_type . ': ' . $this->document_number;
    }

    /**
     * Obtiene el nombre para mostrar (usa email si no hay nombre)
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name ?: $this->email ?: $this->full_document;
    }

    /**
     * Scope para buscar por documento
     */
    public function scopeByDocument($query, string $documentType, string $documentNumber)
    {
        return $query->where('document_type', $documentType)
                    ->where('document_number', $documentNumber);
    }

    /**
     * Scope para buscar por ID interno de C4C
     */
    public function scopeByC4cInternalId($query, string $internalId)
    {
        return $query->where('c4c_internal_id', $internalId);
    }

    /**
     * Verifica si el usuario tiene datos reales de C4C
     */
    public function hasRealC4cData(): bool
    {
        return !$this->is_comodin && !empty($this->c4c_internal_id) && $this->c4c_internal_id !== '99911999';
    }
}
