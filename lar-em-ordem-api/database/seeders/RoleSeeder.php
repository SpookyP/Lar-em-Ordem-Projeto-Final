<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        //Atividades Permitidas
        $permissions = [
            'properties.viewAny',
            'properties.view',
            'properties.create',
            'properties.update',
            'properties.delete',
            'view vault documents',
        ];
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // 2. Create Roles & Assign Permissions
        $SURole = Role::firstOrCreate(['name' => 'SU']);
        $SURole->givePermissionTo(Permission::all());

        $residentRole = Role::firstOrCreate(['name' => 'resident']);
        $residentRole->givePermissionTo([
            'properties.viewAny',
            'properties.view',
            'properties.create']);

        $service_providerRole = Role::firstOrCreate(['name' => 'service_provider']);
        $service_providerRole->givePermissionTo(['properties.viewAny']);

        $partnerRole = Role::firstOrCreate(['name' => 'partner']);
        $partnerRole->givePermissionTo(['properties.viewAny']);
        
        $condominium_adminRole = Role::firstOrCreate(['name' => 'condominium_admin']);
        $condominium_adminRole->givePermissionTo(['properties.viewAny']);
    }
}
