<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class GrantAdminRole extends Command
{
    protected $signature = 'admin:grant
        {email : Email address of the user}
        {--role=administrator : Admin role to assign}';

    protected $description =
        'Assign an administrative role to an existing user';

    public function handle(): int
    {
        $email = strtolower(
            trim(
                (string) $this->argument(
                    'email',
                ),
            ),
        );

        $roleName = trim(
            (string) $this->option(
                'role',
            ),
        );

        $allowedRoles = [
            'super-admin',
            'administrator',
            'order-manager',
            'support-agent',
        ];

        if (
            ! in_array(
                $roleName,
                $allowedRoles,
                true,
            )
        ) {
            $this->error(sprintf(
                'Unknown role "%s". Allowed roles: %s.',
                $roleName,
                implode(', ', $allowedRoles),
            ));

            return self::FAILURE;
        }

        $user = User::query()
            ->where('email', $email)
            ->first();

        if ($user === null) {
            $this->error(sprintf(
                'No user exists with email "%s".',
                $email,
            ));

            return self::FAILURE;
        }

        $role = Role::findByName(
            $roleName,
            'web',
        );

        $user->assignRole($role);

        $this->info(sprintf(
            'Assigned role "%s" to %s.',
            $roleName,
            $user->email,
        ));

        return self::SUCCESS;
    }
}
