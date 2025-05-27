<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Renombrar tablas de español a inglés
        if (Schema::hasTable('bloqueos')) {
            Schema::rename('bloqueos', 'blockades');
        }

        if (Schema::hasTable('locales')) {
            Schema::rename('locales', 'premises');
        }

        if (Schema::hasTable('modelos')) {
            Schema::rename('modelos', 'models');
        }

        if (Schema::hasTable('modelo_anos')) {
            Schema::rename('modelo_anos', 'model_years');
        }

        if (Schema::hasTable('campanas')) {
            Schema::rename('campanas', 'campaigns');
        }

        if (Schema::hasTable('campana_modelos')) {
            Schema::rename('campana_modelos', 'campaign_models');
        }

        if (Schema::hasTable('campana_anos')) {
            Schema::rename('campana_anos', 'campaign_years');
        }

        if (Schema::hasTable('campana_locales')) {
            Schema::rename('campana_locales', 'campaign_premises');
        }

        if (Schema::hasTable('campana_imagenes')) {
            Schema::rename('campana_imagenes', 'campaign_images');
        }

        if (Schema::hasTable('vehiculos_express')) {
            Schema::rename('vehiculos_express', 'vehicles_express');
        }

        // Renombrar columnas en la tabla blockades
        if (Schema::hasTable('blockades')) {
            Schema::table('blockades', function (Blueprint $table) {
                if (Schema::hasColumn('blockades', 'fecha_inicio')) {
                    $table->renameColumn('fecha_inicio', 'start_date');
                }
                if (Schema::hasColumn('blockades', 'fecha_fin')) {
                    $table->renameColumn('fecha_fin', 'end_date');
                }
                if (Schema::hasColumn('blockades', 'hora_inicio')) {
                    $table->renameColumn('hora_inicio', 'start_time');
                }
                if (Schema::hasColumn('blockades', 'hora_fin')) {
                    $table->renameColumn('hora_fin', 'end_time');
                }
                if (Schema::hasColumn('blockades', 'todo_dia')) {
                    $table->renameColumn('todo_dia', 'all_day');
                }
            });
        }

        // Renombrar columnas en la tabla premises
        if (Schema::hasTable('premises')) {
            Schema::table('premises', function (Blueprint $table) {
                if (Schema::hasColumn('premises', 'horario_apertura')) {
                    $table->renameColumn('horario_apertura', 'opening_time');
                }
                if (Schema::hasColumn('premises', 'horario_cierre')) {
                    $table->renameColumn('horario_cierre', 'closing_time');
                }
            });
        }

        // Renombrar columnas en la tabla model_years
        if (Schema::hasTable('model_years')) {
            Schema::table('model_years', function (Blueprint $table) {
                if (Schema::hasColumn('model_years', 'modelo_id')) {
                    $table->renameColumn('modelo_id', 'model_id');
                }
                if (Schema::hasColumn('model_years', 'ano')) {
                    $table->renameColumn('ano', 'year');
                }
            });
        }

        // Renombrar columnas en la tabla campaigns
        if (Schema::hasTable('campaigns')) {
            Schema::table('campaigns', function (Blueprint $table) {
                if (Schema::hasColumn('campaigns', 'fecha_inicio')) {
                    $table->renameColumn('fecha_inicio', 'start_date');
                }
                if (Schema::hasColumn('campaigns', 'fecha_fin')) {
                    $table->renameColumn('fecha_fin', 'end_date');
                }
                if (Schema::hasColumn('campaigns', 'hora_inicio')) {
                    $table->renameColumn('hora_inicio', 'start_time');
                }
                if (Schema::hasColumn('campaigns', 'hora_fin')) {
                    $table->renameColumn('hora_fin', 'end_time');
                }
                if (Schema::hasColumn('campaigns', 'todo_dia')) {
                    $table->renameColumn('todo_dia', 'all_day');
                }
            });
        }

        // Renombrar columnas en la tabla campaign_models
        if (Schema::hasTable('campaign_models')) {
            Schema::table('campaign_models', function (Blueprint $table) {
                if (Schema::hasColumn('campaign_models', 'campana_id')) {
                    $table->renameColumn('campana_id', 'campaign_id');
                }
                if (Schema::hasColumn('campaign_models', 'modelo_id')) {
                    $table->renameColumn('modelo_id', 'model_id');
                }
            });
        }

        // Renombrar columnas en la tabla campaign_years
        if (Schema::hasTable('campaign_years')) {
            Schema::table('campaign_years', function (Blueprint $table) {
                if (Schema::hasColumn('campaign_years', 'campana_id')) {
                    $table->renameColumn('campana_id', 'campaign_id');
                }
                if (Schema::hasColumn('campaign_years', 'ano')) {
                    $table->renameColumn('ano', 'year');
                }
            });
        }

        // Renombrar columnas en la tabla campaign_premises
        if (Schema::hasTable('campaign_premises')) {
            Schema::table('campaign_premises', function (Blueprint $table) {
                if (Schema::hasColumn('campaign_premises', 'campana_id')) {
                    $table->renameColumn('campana_id', 'campaign_id');
                }
                if (Schema::hasColumn('campaign_premises', 'local_codigo')) {
                    $table->renameColumn('local_codigo', 'premise_code');
                }
            });
        }

        // Renombrar columnas en la tabla campaign_images
        if (Schema::hasTable('campaign_images')) {
            Schema::table('campaign_images', function (Blueprint $table) {
                if (Schema::hasColumn('campaign_images', 'campana_id')) {
                    $table->renameColumn('campana_id', 'campaign_id');
                }
            });
        }

        // Renombrar columnas en la tabla vehicles_express
        if (Schema::hasTable('vehicles_express')) {
            Schema::table('vehicles_express', function (Blueprint $table) {
                if (Schema::hasColumn('vehicles_express', 'ano')) {
                    $table->renameColumn('ano', 'year');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir el renombrado de columnas en vehicles_express
        if (Schema::hasTable('vehicles_express')) {
            Schema::table('vehicles_express', function (Blueprint $table) {
                if (Schema::hasColumn('vehicles_express', 'year')) {
                    $table->renameColumn('year', 'ano');
                }
            });
        }

        // Revertir el renombrado de columnas en campaign_images
        if (Schema::hasTable('campaign_images')) {
            Schema::table('campaign_images', function (Blueprint $table) {
                if (Schema::hasColumn('campaign_images', 'campaign_id')) {
                    $table->renameColumn('campaign_id', 'campana_id');
                }
            });
        }

        // Revertir el renombrado de columnas en campaign_premises
        if (Schema::hasTable('campaign_premises')) {
            Schema::table('campaign_premises', function (Blueprint $table) {
                if (Schema::hasColumn('campaign_premises', 'campaign_id')) {
                    $table->renameColumn('campaign_id', 'campana_id');
                }
                if (Schema::hasColumn('campaign_premises', 'premise_code')) {
                    $table->renameColumn('premise_code', 'local_codigo');
                }
            });
        }

        // Revertir el renombrado de columnas en campaign_years
        if (Schema::hasTable('campaign_years')) {
            Schema::table('campaign_years', function (Blueprint $table) {
                if (Schema::hasColumn('campaign_years', 'campaign_id')) {
                    $table->renameColumn('campaign_id', 'campana_id');
                }
                if (Schema::hasColumn('campaign_years', 'year')) {
                    $table->renameColumn('year', 'ano');
                }
            });
        }

        // Revertir el renombrado de columnas en campaign_models
        if (Schema::hasTable('campaign_models')) {
            Schema::table('campaign_models', function (Blueprint $table) {
                if (Schema::hasColumn('campaign_models', 'campaign_id')) {
                    $table->renameColumn('campaign_id', 'campana_id');
                }
                if (Schema::hasColumn('campaign_models', 'model_id')) {
                    $table->renameColumn('model_id', 'modelo_id');
                }
            });
        }

        // Revertir el renombrado de columnas en campaigns
        if (Schema::hasTable('campaigns')) {
            Schema::table('campaigns', function (Blueprint $table) {
                if (Schema::hasColumn('campaigns', 'start_date')) {
                    $table->renameColumn('start_date', 'fecha_inicio');
                }
                if (Schema::hasColumn('campaigns', 'end_date')) {
                    $table->renameColumn('end_date', 'fecha_fin');
                }
                if (Schema::hasColumn('campaigns', 'start_time')) {
                    $table->renameColumn('start_time', 'hora_inicio');
                }
                if (Schema::hasColumn('campaigns', 'end_time')) {
                    $table->renameColumn('end_time', 'hora_fin');
                }
                if (Schema::hasColumn('campaigns', 'all_day')) {
                    $table->renameColumn('all_day', 'todo_dia');
                }
            });
        }

        // Revertir el renombrado de columnas en model_years
        if (Schema::hasTable('model_years')) {
            Schema::table('model_years', function (Blueprint $table) {
                if (Schema::hasColumn('model_years', 'model_id')) {
                    $table->renameColumn('model_id', 'modelo_id');
                }
                if (Schema::hasColumn('model_years', 'year')) {
                    $table->renameColumn('year', 'ano');
                }
            });
        }

        // Revertir el renombrado de columnas en premises
        if (Schema::hasTable('premises')) {
            Schema::table('premises', function (Blueprint $table) {
                if (Schema::hasColumn('premises', 'opening_time')) {
                    $table->renameColumn('opening_time', 'horario_apertura');
                }
                if (Schema::hasColumn('premises', 'closing_time')) {
                    $table->renameColumn('closing_time', 'horario_cierre');
                }
            });
        }

        // Revertir el renombrado de columnas en blockades
        if (Schema::hasTable('blockades')) {
            Schema::table('blockades', function (Blueprint $table) {
                if (Schema::hasColumn('blockades', 'start_date')) {
                    $table->renameColumn('start_date', 'fecha_inicio');
                }
                if (Schema::hasColumn('blockades', 'end_date')) {
                    $table->renameColumn('end_date', 'fecha_fin');
                }
                if (Schema::hasColumn('blockades', 'start_time')) {
                    $table->renameColumn('start_time', 'hora_inicio');
                }
                if (Schema::hasColumn('blockades', 'end_time')) {
                    $table->renameColumn('end_time', 'hora_fin');
                }
                if (Schema::hasColumn('blockades', 'all_day')) {
                    $table->renameColumn('all_day', 'todo_dia');
                }
            });
        }

        // Revertir el renombrado de tablas
        if (Schema::hasTable('vehicles_express')) {
            Schema::rename('vehicles_express', 'vehiculos_express');
        }

        if (Schema::hasTable('campaign_images')) {
            Schema::rename('campaign_images', 'campana_imagenes');
        }

        if (Schema::hasTable('campaign_premises')) {
            Schema::rename('campaign_premises', 'campana_locales');
        }

        if (Schema::hasTable('campaign_years')) {
            Schema::rename('campaign_years', 'campana_anos');
        }

        if (Schema::hasTable('campaign_models')) {
            Schema::rename('campaign_models', 'campana_modelos');
        }

        if (Schema::hasTable('campaigns')) {
            Schema::rename('campaigns', 'campanas');
        }

        if (Schema::hasTable('model_years')) {
            Schema::rename('model_years', 'modelo_anos');
        }

        if (Schema::hasTable('models')) {
            Schema::rename('models', 'modelos');
        }

        if (Schema::hasTable('premises')) {
            Schema::rename('premises', 'locales');
        }

        if (Schema::hasTable('blockades')) {
            Schema::rename('blockades', 'bloqueos');
        }
    }
};
