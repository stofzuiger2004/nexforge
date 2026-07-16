<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AdminAuthorizationSeeder extends Seeder
{
    public function run(): void
    {
        $registrar = app(
            PermissionRegistrar::class,
        );

        $registrar->forgetCachedPermissions();

        $permissionNames = [
            'admin.access',

            'orders.viewAny',
            'orders.view',
            'orders.viewCustomerData',

            'payments.view',
            'inventory.view',
        ];

        $permissions = collect(
            $permissionNames,
        )->mapWithKeys(
            static function (
                string $permissionName,
            ): array {
                $permission =
                    Permission::findOrCreate(
                        $permissionName,
                        'web',
                    );

                return [
                    $permissionName => $permission,
                ];
            },
        );

        $superAdmin = Role::findOrCreate(
            'super-admin',
            'web',
        );

        $administrator = Role::findOrCreate(
            'administrator',
            'web',
        );

        $orderManager = Role::findOrCreate(
            'order-manager',
            'web',
        );

        $supportAgent = Role::findOrCreate(
            'support-agent',
            'web',
        );

        /*
         * Explicit permissions are useful for direct package
         * permission checks, while Gate::before will also make
         * super-admin pass future Gate checks.
         */
        $superAdmin->syncPermissions(
            $permissions->values(),
        );

        $administrator->syncPermissions(
            $permissions->values(),
        );

        $orderManager->syncPermissions(
            $permissions
                ->only([
                    'admin.access',

                    'orders.viewAny',
                    'orders.view',
                    'orders.viewCustomerData',

                    'payments.view',
                    'inventory.view',
                ])
                ->values(),
        );

        $supportAgent->syncPermissions(
            $permissions
                ->only([
                    'admin.access',
                    'orders.viewAny',
                    'orders.view',
                    'orders.viewCustomerData',
                ])
                ->values(),
        );

        $registrar->forgetCachedPermissions();
    }
}
