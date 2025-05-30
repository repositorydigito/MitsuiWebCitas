<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class ShieldSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpiar cache de permisos
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear rol de super admin
        $superAdminRole = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web'
        ]);

        // Crear rol de panel user
        $panelUserRole = Role::firstOrCreate([
            'name' => 'panel_user',
            'guard_name' => 'web'
        ]);

        // Crear algunos permisos básicos
        $permissions = [
            'view_any_user',
            'view_user',
            'create_user',
            'update_user',
            'delete_user',
            'delete_any_user',
            'view_any_role',
            'view_role',
            'create_role',
            'update_role',
            'delete_role',
            'delete_any_role',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
        }

        // Asignar todos los permisos al super admin
        $superAdminRole->givePermissionTo(Permission::all());

        // Crear usuario super admin si no existe
        $superAdmin = User::firstOrCreate([
            'email' => 'admin@mitsui.com'
        ], [
            'name' => 'Super Admin',
            'password' => bcrypt('password123')
        ]);

        // Asignar rol de super admin
        $superAdmin->assignRole($superAdminRole);

        $this->command->info('Shield seeder completado exitosamente!');
        $this->command->info('Usuario Super Admin creado:');
        $this->command->info('Email: admin@mitsui.com');
        $this->command->info('Password: password123');
    }
}
