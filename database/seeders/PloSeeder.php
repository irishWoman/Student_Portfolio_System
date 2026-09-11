<?php

namespace Database\Seeders;

use App\Models\Plo;
use App\Models\PloRubricDescriptor;
use App\Support\Enums\AttainmentLevel;
use Illuminate\Database\Seeder;

/**
 * The 14 Computer Engineering program learning outcomes, with a measurable
 * descriptor at each of the four levels.
 *
 * Descriptors are written so an evaluator can tell two adjacent levels apart
 * without consulting anyone: "with guidance" vs "independently" vs "in complex,
 * open-ended problems" is the axis that separates them throughout.
 */
class PloSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->plos() as $number => $plo) {
            $model = Plo::updateOrCreate(
                ['number' => $number],
                ['title' => $plo['title'], 'statement' => $plo['statement']]
            );

            foreach (AttainmentLevel::cases() as $level) {
                PloRubricDescriptor::updateOrCreate(
                    ['plo_id' => $model->id, 'level' => $level->value],
                    ['descriptor' => $this->descriptor($plo['verb'], $plo['object'], $level)]
                );
            }
        }
    }

    /**
     * Build a level descriptor from the outcome's own verb and object, so every
     * rubric row reads as a sentence about that specific outcome rather than a
     * generic scale copied fourteen times.
     */
    protected function descriptor(string $verb, string $object, AttainmentLevel $level): string
    {
        return match ($level) {
            AttainmentLevel::Introduced => "Recognises the need to {$verb} {$object}, and can do so in structured exercises with close guidance.",
            AttainmentLevel::Developing => "Can {$verb} {$object} in familiar problems, with occasional guidance and minor errors.",
            AttainmentLevel::Proficient => "Independently and correctly {$verb}s {$object} in realistic engineering work.",
            AttainmentLevel::Advanced => "Can {$verb} {$object} in complex, open-ended problems, and justifies the approach to others at a professional standard.",
        };
    }

    protected function plos(): array
    {
        return [
            1 => [
                'title' => 'Engineering Knowledge',
                'statement' => 'Apply knowledge of mathematics, natural sciences, and engineering fundamentals to the solution of complex computer engineering problems.',
                'verb' => 'apply', 'object' => 'mathematics, science and engineering fundamentals',
            ],
            2 => [
                'title' => 'Problem Analysis',
                'statement' => 'Identify, formulate, research literature, and analyse complex computer engineering problems reaching substantiated conclusions.',
                'verb' => 'analyse', 'object' => 'complex engineering problems',
            ],
            3 => [
                'title' => 'Design and Development of Solutions',
                'statement' => 'Design solutions for complex computer engineering problems and design systems, components, or processes that meet specified needs with appropriate consideration for public health and safety, cultural, societal, and environmental considerations.',
                'verb' => 'design', 'object' => 'systems and components that meet specified needs',
            ],
            4 => [
                'title' => 'Investigation',
                'statement' => 'Conduct investigations of complex problems using research-based knowledge and research methods, including design of experiments, analysis and interpretation of data, and synthesis of information.',
                'verb' => 'investigate', 'object' => 'problems using research methods and data analysis',
            ],
            5 => [
                'title' => 'Modern Tool Usage',
                'statement' => 'Create, select, and apply appropriate techniques, resources, and modern engineering and IT tools, with an understanding of their limitations.',
                'verb' => 'select and apply', 'object' => 'modern engineering and IT tools',
            ],
            6 => [
                'title' => 'The Engineer and Society',
                'statement' => 'Apply reasoning informed by contextual knowledge to assess societal, health, safety, legal, and cultural issues and the consequent responsibilities relevant to professional engineering practice.',
                'verb' => 'assess', 'object' => 'societal, health, safety and legal responsibilities',
            ],
            7 => [
                'title' => 'Environment and Sustainability',
                'statement' => 'Understand the impact of professional engineering solutions in societal and environmental contexts and demonstrate knowledge of and need for sustainable development.',
                'verb' => 'evaluate', 'object' => 'environmental impact and sustainability of solutions',
            ],
            8 => [
                'title' => 'Ethics',
                'statement' => 'Apply ethical principles and commit to professional ethics, responsibilities, and norms of engineering practice.',
                'verb' => 'apply', 'object' => 'professional and ethical principles',
            ],
            9 => [
                'title' => 'Individual and Team Work',
                'statement' => 'Function effectively as an individual, and as a member or leader in diverse teams and in multidisciplinary settings.',
                'verb' => 'contribute to', 'object' => 'team work as a member or leader',
            ],
            10 => [
                'title' => 'Communication',
                'statement' => 'Communicate effectively on complex engineering activities with the engineering community and with society at large, including writing effective reports and design documentation.',
                'verb' => 'communicate', 'object' => 'engineering work in writing and speech',
            ],
            11 => [
                'title' => 'Project Management and Finance',
                'statement' => 'Demonstrate knowledge and understanding of engineering management principles and economic decision-making and apply these to one\'s own work as a member and leader in a team.',
                'verb' => 'manage', 'object' => 'project scope, schedule and cost',
            ],
            12 => [
                'title' => 'Lifelong Learning',
                'statement' => 'Recognise the need for, and have the preparation and ability to engage in independent and lifelong learning in the broadest context of technological change.',
                'verb' => 'pursue', 'object' => 'independent learning beyond the curriculum',
            ],
            13 => [
                'title' => 'Specialised Computer Engineering Knowledge',
                'statement' => 'Apply specialised knowledge of digital systems, embedded systems, computer networks, and computer architecture to the design and analysis of computing systems.',
                'verb' => 'apply', 'object' => 'specialised computing and hardware knowledge',
            ],
            14 => [
                'title' => 'Innovation and Emerging Technologies',
                'statement' => 'Develop and implement computer engineering solutions using current and emerging technologies, demonstrating innovation and adaptability in professional practice.',
                'verb' => 'implement', 'object' => 'solutions using current and emerging technologies',
            ],
        ];
    }
}
