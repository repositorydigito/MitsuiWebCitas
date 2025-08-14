<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentAdditionalService extends Model
{
    use HasFactory;

    protected $table = 'appointment_additional_service';

    protected $fillable = [
        'appointment_id',
        'additional_service_id',
        'notes',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    /**
     * Get the appointment that owns the additional service.
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Get the additional service details.
     */
    public function additionalService(): BelongsTo
    {
        return $this->belongsTo(AdditionalService::class);
    }
}