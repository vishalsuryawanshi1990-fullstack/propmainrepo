<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Roles per doc 01 (Guest is unauthenticated, so it is not a stored role).
     *
     * @var array<int, string>
     */
    protected array $roles = ['buyer', 'seller', 'agent', 'moderator', 'admin'];

    /**
     * Permissions grouped by the role that gets them, additively
     * (agent inherits seller's permissions, admin gets everything).
     *
     * @var array<string, array<int, string>>
     */
    protected array $permissionsByRole = [
        'buyer' => [
            'properties.favorite',
            'properties.report',
            'chats.participate',
            'unlocks.spend',
        ],
        'seller' => [
            'properties.create',
            'properties.update-own',
            'properties.delete-own',
        ],
        'agent' => [
            'properties.bulk-upload',
            'agent-profile.manage',
        ],
        'moderator' => [
            'properties.moderate',
            'kyc.review',
            'reports.manage',
        ],
        'admin' => [
            'users.manage',
            'coupons.manage',
            'scratch-rewards.manage',
            'cms.manage',
            'audit-logs.view',
            'analytics.view',
            'settings.manage',
        ],
    ];

    public function run(): void
    {
        foreach (array_unique(array_merge(...array_values($this->permissionsByRole))) as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        foreach ($this->roles as $roleName) {
            $role = Role::firstOrCreate(['name' => $roleName]);

            $permissions = match ($roleName) {
                'buyer' => $this->permissionsByRole['buyer'],
                'seller' => [...$this->permissionsByRole['buyer'], ...$this->permissionsByRole['seller']],
                'agent' => [...$this->permissionsByRole['buyer'], ...$this->permissionsByRole['seller'], ...$this->permissionsByRole['agent']],
                'moderator' => $this->permissionsByRole['moderator'],
                'admin' => Permission::pluck('name')->all(),
            };

            $role->syncPermissions($permissions);
        }
    }
}
