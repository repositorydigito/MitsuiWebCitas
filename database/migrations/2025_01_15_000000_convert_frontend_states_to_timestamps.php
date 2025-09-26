<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Convertir la estructura de frontend_states de array a objeto con timestamps
        DB::statement("
            UPDATE appointments 
            SET frontend_states = CASE 
                WHEN frontend_states IS NULL THEN NULL
                WHEN JSON_TYPE(frontend_states) = 'ARRAY' THEN 
                    CASE 
                        WHEN JSON_CONTAINS(frontend_states, '\"cita_confirmada\"') AND JSON_CONTAINS(frontend_states, '\"en_trabajo\"') THEN 
                            JSON_OBJECT(
                                'cita_confirmada', DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s'),
                                'en_trabajo', DATE_FORMAT(DATE_ADD(created_at, INTERVAL 1 HOUR), '%Y-%m-%d %H:%i:%s')
                            )
                        WHEN JSON_CONTAINS(frontend_states, '\"cita_confirmada\"') THEN 
                            JSON_OBJECT('cita_confirmada', DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s'))
                        ELSE frontend_states
                    END
                ELSE frontend_states
            END
            WHERE frontend_states IS NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir la estructura de objeto con timestamps a array simple
        DB::statement("
            UPDATE appointments 
            SET frontend_states = CASE 
                WHEN frontend_states IS NULL THEN NULL
                WHEN JSON_TYPE(frontend_states) = 'OBJECT' THEN 
                    CASE 
                        WHEN JSON_EXTRACT(frontend_states, '$.en_trabajo') IS NOT NULL THEN 
                            JSON_ARRAY('cita_confirmada', 'en_trabajo')
                        WHEN JSON_EXTRACT(frontend_states, '$.cita_confirmada') IS NOT NULL THEN 
                            JSON_ARRAY('cita_confirmada')
                        ELSE frontend_states
                    END
                ELSE frontend_states
            END
            WHERE frontend_states IS NOT NULL
        ");
    }
};