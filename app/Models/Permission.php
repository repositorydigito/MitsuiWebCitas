<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    // El modelo Permission debe extender de Spatie\Permission\Models\Permission
    // para que funcione correctamente con Filament Shield
}
