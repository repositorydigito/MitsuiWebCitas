<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'vehicles';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'vehicle_id',
        'license_plate',
        'model',
        'year',
        'brand_code',
        'brand_name',
        'color',
        'vin',
        'engine_number',
        'mileage',
        'last_service_date',
        'last_service_mileage',
        'next_service_date',
        'next_service_mileage',
        'has_prepaid_maintenance',
        'prepaid_maintenance_expiry',
        'image_url',
        'user_id',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'last_service_date' => 'date',
        'next_service_date' => 'date',
        'prepaid_maintenance_expiry' => 'date',
        'has_prepaid_maintenance' => 'boolean',
        'mileage' => 'integer',
        'last_service_mileage' => 'integer',
        'next_service_mileage' => 'integer',
    ];

    /**
     * Get the user that owns the vehicle.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the brand name based on the brand code.
     *
     * @return string
     */
    public function getBrandNameAttribute($value)
    {
        if (! empty($value)) {
            return $value;
        }

        // Si no hay valor, determinar por el código
        return match ($this->brand_code) {
            'Z01' => 'TOYOTA',
            'Z02' => 'LEXUS',
            'Z03' => 'HINO',
            default => 'TOYOTA',
        };
    }

    /**
     * Set the brand name based on the brand code if not provided.
     *
     * @param  string|null  $value
     * @return void
     */
    public function setBrandNameAttribute($value)
    {
        if (! empty($value)) {
            $this->attributes['brand_name'] = $value;

            return;
        }

        // Si no hay valor, determinar por el código
        $this->attributes['brand_name'] = match ($this->attributes['brand_code'] ?? null) {
            'Z01' => 'TOYOTA',
            'Z02' => 'LEXUS',
            'Z03' => 'HINO',
            default => 'TOYOTA',
        };
    }

    /**
     * Get the appointments for the vehicle.
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Scope a query to only include active vehicles.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to filter by brand code.
     */
    public function scopeByBrand($query, $brandCode)
    {
        return $query->where('brand_code', $brandCode);
    }

    /**
     * Scope a query to search by license plate.
     */
    public function scopeSearchByPlate($query, $plate)
    {
        if (! $plate) {
            return $query;
        }

        return $query->where('license_plate', 'LIKE', "%{$plate}%");
    }

    /**
     * Boot the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // Antes de guardar, asegurarse de que brand_name esté establecido
        static::saving(function ($vehicle) {
            if (empty($vehicle->brand_name) && ! empty($vehicle->brand_code)) {
                $vehicle->brand_name = match ($vehicle->brand_code) {
                    'Z01' => 'TOYOTA',
                    'Z02' => 'LEXUS',
                    'Z03' => 'HINO',
                    default => 'TOYOTA',
                };
            }
        });
    }
}
