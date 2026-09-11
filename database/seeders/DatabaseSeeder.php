<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeding order matters:
 *   1. roles, so users can be assigned one
 *   2. PLOs, because competencies and CLOs map to them
 *   3. competencies and the curriculum
 *   4. the academic calendar, which generates deadline schedules
 *   5. demo accounts and the worked example portfolio
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            PloSeeder::class,
            CompetencySeeder::class,
            CurriculumSeeder::class,
            AcademicYearSeeder::class,
            DemoDataSeeder::class,
        ]);
    }
}
