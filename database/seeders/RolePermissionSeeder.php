<?php

namespace Database\Seeders;

use App\Support\Enums\Role as RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Roles and the permissions behind them.
 *
 * Permissions are coarse on purpose: the interesting authorization questions
 * ("is this my portfolio?", "has the deadline locked?") are answered by
 * PortfolioPolicy and DeadlineService, not by permission names.
 */
class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'portfolio.edit_own',
            'portfolio.submit_own',
            'portfolio.view_any',
            'portfolio.evaluate',
            'evidence.rate',
            'program.view_attainment',
            'program.manage_cqi',
            'system.manage_calendar',
            'system.manage_users',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $map = [
            RoleEnum::Student->value => ['portfolio.edit_own', 'portfolio.submit_own'],
            RoleEnum::Faculty->value => ['portfolio.view_any', 'portfolio.evaluate', 'evidence.rate'],
            RoleEnum::Chair->value => ['portfolio.view_any', 'portfolio.evaluate', 'evidence.rate',
                'program.view_attainment', 'program.manage_cqi', 'system.manage_calendar'],
            RoleEnum::Admin->value => $permissions,
        ];

        foreach ($map as $role => $granted) {
            Role::findOrCreate($role, 'web')->syncPermissions($granted);
        }
    }
}
