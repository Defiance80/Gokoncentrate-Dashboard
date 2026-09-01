<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Media Radar permissions, following the existing naming convention
     * (view_x / add_x / edit_x / ...) used by the rest of the dashboard.
     */
    private array $permissions = [
        'view_media_radar',
        'add_media_radar',
        'edit_media_radar',
        'delete_media_radar',
        'approve_media_radar',
        'manage_media_radar_rules',
        'manage_media_radar_sources',
        'manage_media_radar_settings',
    ];

    public function up(): void
    {
        $this->forgetPermissionCache();

        $created = [];

        foreach ($this->permissions as $name) {
            $created[] = Permission::firstOrCreate(['name' => $name], ['guard_name' => 'web', 'is_fixed' => true]);
        }

        foreach (Role::whereIn('name', ['admin', 'demo_admin'])->get() as $role) {
            $role->givePermissionTo($created);
        }

        $this->forgetPermissionCache();
    }

    public function down(): void
    {
        $this->forgetPermissionCache();

        $permissions = Permission::whereIn('name', $this->permissions)->get();

        foreach (Role::whereIn('name', ['admin', 'demo_admin'])->get() as $role) {
            foreach ($permissions as $permission) {
                $role->revokePermissionTo($permission);
            }
        }

        Permission::whereIn('name', $this->permissions)->delete();

        $this->forgetPermissionCache();
    }

    private function forgetPermissionCache(): void
    {
        if (class_exists(\Spatie\Permission\PermissionRegistrar::class)) {
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        }
    }
};
