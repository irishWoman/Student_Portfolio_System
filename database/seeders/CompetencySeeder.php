<?php

namespace Database\Seeders;

use App\Models\Competency;
use App\Models\CompetencyCategory;
use App\Models\Plo;
use Illuminate\Database\Seeder;

/**
 * The technical competency catalogue used by Section 5, grouped into the seven
 * categories the department tracks. Each competency is linked to the PLOs it
 * contributes to, which is what lets the system answer "where is PLO 13
 * actually developed?" without a manual audit.
 */
class CompetencySeeder extends Seeder
{
    public function run(): void
    {
        $plos = Plo::pluck('id', 'number');
        $order = 1;

        foreach ($this->catalogue() as $key => $group) {
            $category = CompetencyCategory::updateOrCreate(
                ['key' => $key],
                ['name' => $group['name'], 'sort_order' => $order++]
            );

            $position = 1;

            foreach ($group['competencies'] as $name => $ploNumbers) {
                $competency = Competency::updateOrCreate(
                    ['competency_category_id' => $category->id, 'name' => $name],
                    ['sort_order' => $position++]
                );

                $competency->plos()->sync(
                    collect($ploNumbers)->map(fn ($n) => $plos[$n] ?? null)->filter()->all()
                );
            }
        }
    }

    protected function catalogue(): array
    {
        return [
            'programming' => [
                'name' => 'Programming and Software',
                'competencies' => [
                    'C and C++' => [1, 5, 13],
                    'Python' => [5, 13, 14],
                    'Java' => [5, 13],
                    'Assembly language' => [1, 13],
                    'Object-oriented design' => [3, 5],
                    'Data structures and algorithms' => [1, 2, 13],
                    'Version control (Git)' => [5, 9],
                    'Software testing and debugging' => [4, 5],
                ],
            ],
            'digital_systems' => [
                'name' => 'Digital Systems Design',
                'competencies' => [
                    'Combinational logic design' => [1, 3, 13],
                    'Sequential logic design' => [1, 3, 13],
                    'HDL (Verilog / VHDL)' => [3, 5, 13],
                    'FPGA implementation' => [3, 5, 13],
                    'Digital simulation and timing analysis' => [4, 5],
                ],
            ],
            'embedded' => [
                'name' => 'Embedded and Microprocessor Systems',
                'competencies' => [
                    'Microcontroller programming' => [3, 5, 13],
                    'Microprocessor architecture' => [1, 13],
                    'Sensor and actuator interfacing' => [3, 13],
                    'Real-time systems' => [3, 13],
                    'Embedded C' => [5, 13],
                ],
            ],
            'computer_systems' => [
                'name' => 'Computer Systems and Architecture',
                'competencies' => [
                    'Computer architecture and organisation' => [1, 13],
                    'Operating systems' => [1, 13],
                    'Memory and storage systems' => [1, 13],
                    'Performance analysis' => [2, 4],
                ],
            ],
            'networks' => [
                'name' => 'Networks and Security',
                'competencies' => [
                    'Network design and configuration' => [3, 13],
                    'TCP/IP and protocols' => [1, 13],
                    'Network security' => [6, 8, 13],
                    'Wireless and mobile communication' => [13, 14],
                ],
            ],
            'data_ai' => [
                'name' => 'Data and Intelligent Systems',
                'competencies' => [
                    'Data analysis and statistics' => [2, 4],
                    'Machine learning fundamentals' => [4, 14],
                    'Database design' => [3, 5],
                    'Signal processing' => [1, 4, 13],
                ],
            ],
            'iot_automation' => [
                'name' => 'IoT and Automation',
                'competencies' => [
                    'IoT system design' => [3, 14],
                    'Control systems' => [1, 3, 13],
                    'Industrial automation' => [3, 13],
                    'Cloud integration' => [5, 14],
                ],
            ],
        ];
    }
}
