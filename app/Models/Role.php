<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    // El modelo Role debe extender de Spatie\Permission\Models\Role
    // para que funcione correctamente con Filament Shield
}
