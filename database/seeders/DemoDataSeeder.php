<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\CapstoneRecord;
use App\Models\Competency;
use App\Models\CourseEvidenceRecord;
use App\Models\CurriculumCourse;
use App\Models\DesignElement;
use App\Models\FacultyEvaluation;
use App\Models\FacultyEvaluationScore;
use App\Models\IndirectAssessment;
use App\Models\OjtRecord;
use App\Models\OjtSupervisorEvaluation;
use App\Models\Plo;
use App\Models\ProfessionalDevelopmentRecord;
use App\Models\Program;
use App\Models\Project;
use App\Models\Reflection;
use App\Models\Student;
use App\Models\TechnicalCompetencyRecord;
use App\Models\User;
use App\Services\PloAttainmentService;
use App\Services\PortfolioProvisioningService;
use App\Support\Enums\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * DEMO DATA
 * -----------------------------------------------------------------------------
 * Enough real-looking data to demonstrate every screen: accounts for all four
 * roles, a cohort of students, and one fully accomplished fourth-year portfolio
 * with validated evidence so the attainment dashboard is not empty on first run.
 *
 * Every account uses the password `password`. Change or remove this seeder
 * before the system is used with real student records.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $program = Program::where('code', 'BSCPE')->firstOrFail();
        $year = AcademicYear::where('is_current', true)->firstOrFail();

        // --- Staff accounts ---------------------------------------------------
        $admin = $this->user('System Administrator', 'admin@usl.edu.ph', Role::Admin, employeeNo: 'ADM-001');
        $chair = $this->user('Maria Bernadette Lasam', 'chair@usl.edu.ph', Role::Chair, 'Engr.', 'FAC-100');
        $faculty = [
            $this->user('Paolo Villanueva', 'villanueva@usl.edu.ph', Role::Faculty, 'Engr.', 'FAC-101'),
            $this->user('Cristina Domingo', 'domingo@usl.edu.ph', Role::Faculty, 'Engr.', 'FAC-102'),
            $this->user('Jerome Tan', 'tan@usl.edu.ph', Role::Faculty, 'Engr.', 'FAC-103'),
        ];

        // --- Student cohort ----------------------------------------------------
        $roster = [
            ['Costales', 'Irish Jane', 'Viernes', 4, '2101391', 'student@usl.edu.ph'],
            ['Bautista', 'Marco', 'Reyes', 4, '2101402', 'bautista@usl.edu.ph'],
            ['Gaddi', 'Hannah', 'Lopez', 4, '2101415', 'gaddi@usl.edu.ph'],
            ['Ramos', 'Kenneth', 'Aquino', 3, '2201188', 'ramos@usl.edu.ph'],
            ['Pascual', 'Divine Grace', 'Santos', 3, '2201193', 'pascual@usl.edu.ph'],
            ['Tolentino', 'Jay-ar', 'Cruz', 2, '2301244', 'tolentino@usl.edu.ph'],
            ['Ancheta', 'Lyka', 'Martinez', 1, '2401507', 'ancheta@usl.edu.ph'],
        ];

        $provisioning = app(PortfolioProvisioningService::class);
        $students = collect();

        foreach ($roster as $index => [$last, $first, $middle, $level, $number, $email]) {
            $user = $this->user("{$first} {$last}", $email, Role::Student);

            $student = Student::updateOrCreate(
                ['student_number' => $number],
                [
                    'user_id' => $user->id,
                    'program_id' => $program->id,
                    'last_name' => $last,
                    'first_name' => $first,
                    'middle_name' => $middle,
                    'year_level' => $level,
                    'section' => 'BSCpE-'.$level,
                    'admitted_year' => 2026 - $level + 1,
                    'adviser_id' => $faculty[$index % count($faculty)]->id,
                    'standing' => 'active',
                ]
            );

            $provisioning->ensureFor($student, $year);
            $students->push($student);
        }

        // --- One fully worked portfolio, plus three years of history ----------
        $this->backfillEarlierYears($students->first(), $faculty);
        $this->fillPortfolio($students->first(), $year, $faculty[0]);

        // Recompute so the chair's dashboard has numbers on first load.
        $attainment = app(PloAttainmentService::class);
        foreach ($students as $student) {
            if ($portfolio = $student->portfolioFor($year)) {
                $attainment->recomputeForPortfolio($portfolio);
            }
        }
    }

    /**
     * Give the showcase student the three earlier years of their programme, each
     * with a few validated course-evidence rows.
     *
     * The Year 1-4 columns in the matrix and dashboard are then computed by the
     * same code path as everything else — no hand-written snapshots — so the
     * exported document shows a realistic progression (I, D, A, P) instead of a
     * row of dashes.
     */
    protected function backfillEarlierYears(Student $student, array $faculty): void
    {
        $provisioning = app(PortfolioProvisioningService::class);
        $plos = Plo::pluck('id', 'number');

        // [academic year label, year level, [[course code, output, score, PLOs, level], ...]]
        $history = [
            ['2023-2024', 1, [
                ['COME 1022', 'Temperature converter program and report', 85, [1, 13], 1],
                ['CIRC 1064', '4-bit adder schematic and simulation log', 88, [1, 3, 13], 1],
                ['PROG 1052', 'Library catalogue console application', 84, [1, 5], 1],
                ['ENGL 1013', 'Technical briefing and oral presentation', 87, [10], 1],
            ]],
            ['2024-2025', 2, [
                ['COME 1033', 'Sorting benchmark and complexity analysis', 88, [1, 2, 13], 2],
                ['ECIR 1014', 'Network theorem laboratory report', 86, [1, 2], 2],
                ['COME 1044', 'Software design specification, inventory system', 89, [3, 10], 2],
                ['WEB 1043', 'Deployed course-project web application', 90, [5, 14], 2],
                ['COME 1051', 'Schematic set to documentation standard', 85, [5, 10], 2],
            ]],
            ['2025-2026', 3, [
                ['MICRO 1014', 'Sensor interfacing firmware and demo video', 91, [3, 5, 13], 3],
                ['COME 1104', 'LAN configuration log and security review', 87, [3, 6, 13], 3],
                ['CTRL 1014', 'Closed-loop motor speed controller', 89, [1, 2, 13], 3],
                ['RESM 1013', 'Research proposal with literature review', 90, [2, 4, 10], 3],
                ['COME 1193', 'Workplace hazard assessment exercise', 88, [6, 8], 3],
                ['COME 1081', 'Verilog ALU description and testbench', 86, [3, 5, 13], 3],
            ]],
        ];

        foreach ($history as [$label, $level, $rows]) {
            $academicYear = AcademicYear::where('label', $label)->first();

            if (! $academicYear) {
                continue;
            }

            $portfolio = $provisioning->ensureFor($student, $academicYear, $level);

            foreach ($rows as $index => [$code, $output, $score, $ploNumbers, $attained]) {
                $course = CurriculumCourse::where('code', $code)->first();
                $evaluator = $faculty[$index % count($faculty)];

                $record = CourseEvidenceRecord::updateOrCreate(
                    ['portfolio_id' => $portfolio->id, 'output_title' => $output],
                    [
                        'curriculum_course_id' => $course?->id,
                        'clo_statement' => $course?->learningOutcomes()->value('statement') ?? 'Course learning outcome',
                        'assessment_activity' => 'Course project',
                        'score' => $score,
                        'score_max' => 100,
                        'claimed_level' => $attained,
                        'validated_level' => $attained,
                        'evaluator_id' => $evaluator->id,
                        'evaluated_on' => $academicYear->ends_on->copy()->subMonths(2),
                    ]
                );

                $record->plos()->sync(collect($ploNumbers)->map(fn ($n) => $plos[$n])->all());
            }

            $portfolio->update([
                'status' => 'validated',
                'submitted_at' => $academicYear->ends_on->copy()->subMonth(),
                'validated_at' => $academicYear->ends_on->copy()->subWeeks(2),
                'validated_by' => $faculty[0]->id,
                'completion_percent' => 100,
            ]);

            app(PloAttainmentService::class)->recomputeForPortfolio($portfolio);
        }
    }

    protected function user(string $name, string $email, Role $role, ?string $title = null, ?string $employeeNo = null): User
    {
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'title' => $title,
                'employee_no' => $employeeNo,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        $user->syncRoles([$role->value]);

        return $user;
    }

    /**
     * Populate a fourth-year portfolio end to end: profile answers, course
     * evidence validated by faculty, a design project across all 20 steps, a
     * reflection, OJT with a submitted supervisor rating, a capstone, and a
     * faculty assessment.
     */
    protected function fillPortfolio(Student $student, AcademicYear $year, User $evaluator): void
    {
        $portfolio = $student->portfolioFor($year);
        $plos = Plo::pluck('id', 'number');

        // --- Section 1 and 2: narrative answers --------------------------------
        $portfolio->entryFor(1)?->update([
            'payload' => [
                'career_goals' => 'Work as an embedded systems and IoT engineer, then lead hardware product development for a Philippine technology company.',
                'specializations' => 'Embedded systems, IoT, digital systems (FPGA)',
                'professional_interests' => 'Robotics competitions, open hardware, renewable-energy monitoring devices.',
            ],
            'status' => 'submitted',
        ]);

        $portfolio->entryFor(2)?->update([
            'payload' => [
                'career_goals' => 'Convert the OJT placement into a graduate offer in embedded or IoT engineering.',
                'technical_goals' => 'Become fluent in RTOS-based firmware, PCB design, and secure IoT transport (MQTT over TLS).',
                'professional_goals' => 'Lead the capstone team and improve design documentation to industry standard.',
                'skills_to_develop' => 'KiCad, FreeRTOS, basic penetration testing for embedded devices.',
                'certifications' => 'Cisco CCNA, a short IoT security course.',
                'research_interests' => 'Low-power sensor networks for agricultural monitoring.',
                'industry_interests' => 'Embedded hardware startups; telecom and IoT divisions of local electronics firms.',
                'short_term_goals' => 'Ship a working capstone prototype with a full test report by second semester.',
                'long_term_goals' => 'Extend the capstone into a publishable paper or a startup prototype.',
            ],
            'status' => 'submitted',
        ]);

        // --- Section 4: course evidence, validated -----------------------------
        $evidence = [
            ['COME 1134', 'Design and implement an embedded system meeting real-time constraints',
                'Embedded project', 'RTOS-based irrigation controller firmware', 94, [3, 5, 13, 14], 4],
            ['COME 1204', 'Apply DSP techniques to filter sensor noise',
                'Laboratory project', 'Noise-filter design and MATLAB analysis', 89, [1, 4, 5], 3],
            ['COME 1104', 'Design, configure and secure a small network',
                'Lab practical', 'Segmented LAN with firewall rules and test log', 91, [3, 6, 13], 3],
            ['COME 1144', 'Evaluate architecture decisions against performance requirements',
                'Written analysis', 'Cache configuration study with benchmark data', 87, [1, 2, 13], 3],
            ['EMGT 1013', 'Apply engineering management principles to a project plan',
                'Project plan', 'Capstone Gantt chart and bill of materials', 88, [11], 3],
        ];

        foreach ($evidence as [$code, $clo, $activity, $output, $score, $ploNumbers, $level]) {
            $course = CurriculumCourse::where('code', $code)->first();

            $record = CourseEvidenceRecord::updateOrCreate(
                ['portfolio_id' => $portfolio->id, 'output_title' => $output],
                [
                    'curriculum_course_id' => $course?->id,
                    'clo_statement' => $clo,
                    'assessment_activity' => $activity,
                    'score' => $score,
                    'score_max' => 100,
                    'claimed_level' => $level,
                    'validated_level' => $level,
                    'evaluator_id' => $evaluator->id,
                    'evaluated_on' => now()->subWeeks(3),
                    'reflection_note' => 'Would prototype the hardware constraints earlier in the schedule next time.',
                ]
            );

            $record->plos()->sync(collect($ploNumbers)->map(fn ($n) => $plos[$n])->all());
        }

        // --- Section 5: technical competencies ---------------------------------
        $stages = [
            'Embedded C' => 'application',
            'Microcontroller programming' => 'integration',
            'Real-time systems' => 'application',
            'HDL (Verilog / VHDL)' => 'skill',
            'Network design and configuration' => 'application',
            'IoT system design' => 'integration',
            'Signal processing' => 'skill',
            'Version control (Git)' => 'application',
        ];

        foreach ($stages as $name => $stage) {
            if ($competency = Competency::where('name', $name)->first()) {
                TechnicalCompetencyRecord::updateOrCreate(
                    ['portfolio_id' => $portfolio->id, 'competency_id' => $competency->id],
                    [
                        'claimed_stage' => $stage,
                        'validated_stage' => $stage,
                        'evidence_note' => 'Demonstrated in the capstone prototype and OJT work.',
                        'evaluator_id' => $evaluator->id,
                    ]
                );
            }
        }

        // --- Section 6: design project across all 20 steps ----------------------
        $project = Project::updateOrCreate(
            ['portfolio_id' => $portfolio->id, 'title' => 'Solar-Powered Smart Irrigation Controller'],
            [
                'curriculum_course_id' => CurriculumCourse::where('code', 'COME 1134')->value('id'),
                'kind' => 'design_project',
                'role' => 'Team lead',
                'team_size' => 4,
                'started_on' => now()->subMonths(6),
                'completed_on' => now()->subMonth(),
                'summary' => 'A field-deployable irrigation controller that reads soil moisture and drives a solenoid valve, with remote monitoring over MQTT.',
            ]
        );

        $entries = [
            'problem_identification' => 'Smallholder vegetable farms near Tuguegarao over- and under-water crops because nobody can check soil moisture at 5am every day.',
            'problem_statement' => 'Design a low-cost controller that measures soil moisture and automates valve control, with remote monitoring.',
            'requirements' => 'Solar powered; moisture accuracy within 5%; wireless range 50 m; valve response under 10 s; cost per node under PHP 3,500.',
            'literature' => 'Reviewed six published IoT-irrigation designs and two commercial products to compare sensor types and transport protocols.',
            'concept_generation' => 'Three concepts: LoRa mesh, WiFi/MQTT direct to cloud, Bluetooth-to-gateway relay.',
            'alternatives' => 'Scored the three on cost, range, power draw and setup complexity in a weighted decision matrix.',
            'design_selection' => 'Chose WiFi/MQTT for cost and simplicity, accepting shorter range as the trade-off.',
            'system_architecture' => 'ESP32 node to Mosquitto broker on a cloud VM, dashboard subscribing over TLS, relay-driven solenoid valve.',
            'hardware_design' => 'Capacitive moisture sensor, ESP32, relay module, 12 V solenoid, 6 V solar panel with LiFePO4 cell, IP65 enclosure.',
            'software_design' => 'FreeRTOS tasks for sensing, publishing and valve control; threshold automation with a manual override.',
            'prototype' => 'One field-deployable unit built and run in a demo plot for three weeks.',
            'testing' => 'Sensor checked against a manual meter (within 4%); valve response averaged 6.2 s over 40 trials.',
            'validation' => 'Fourteen-day field run against a manually irrigated control plot showed 18% less water used.',
            'evaluation' => 'Met every requirement except range: 38 m through foliage against a 50 m target.',
            'cost_analysis' => 'Bill of materials totalled PHP 3,180 per unit, inside the budget.',
            'safety' => 'IP65 enclosure against water ingress; low-voltage DC throughout to avoid shock hazard.',
            'environmental' => 'Solar powered, so no grid dependency; reduced water draw supports sustainable farming.',
            'ethical' => 'Farm location data is transmitted to the cloud, so access control was added to protect the farmer.',
            'improvements' => 'Add a LoRa fallback for larger plots, and move the moisture threshold to a per-crop profile.',
            'final_reflection' => 'See the reflection in Section 12.',
        ];

        $position = 1;
        foreach (config('portfolio.design_elements') as $key => $label) {
            DesignElement::updateOrCreate(
                ['project_id' => $project->id, 'element_key' => $key],
                ['position' => $position++, 'content' => $entries[$key] ?? null]
            );
        }

        // --- Section 12: reflection ---------------------------------------------
        Reflection::updateOrCreate(
            ['portfolio_id' => $portfolio->id, 'subject' => $project->title],
            [
                'reflectable_type' => Project::class,
                'reflectable_id' => $project->id,
                'answers' => [
                    'learned' => 'How to carry a project from an open-ended problem through trade-offs to a field-tested prototype, and how far real conditions sit from lab conditions.',
                    'problem_solved' => 'Manual irrigation on smallholder farms, which wastes water and stresses crops when nobody can check the soil daily.',
                    'skills_developed' => 'FreeRTOS task design, MQTT over TLS, solar power budgeting, and leading a four-person team through a full design cycle.',
                    'plos_addressed' => 'PLO 2, 3, 4, 5, 6, 7, 9, 10, 11, 13 and 14.',
                    'evidence' => 'The working prototype, the 14-day validation log showing 18% water savings, the design report, and the defense.',
                    'difficulties' => 'WiFi did not reach the 50 m target once the node sat behind dense foliage. I had underestimated attenuation.',
                    'overcame' => 'Moved the gateway to the field edge as an interim fix and documented a LoRa fallback rather than promising a fix we could not build in time.',
                    'improve' => 'Run the range test in week two instead of week ten, when changing protocol would still have been possible.',
                    'additional_skills' => 'RF and antenna design, and a more structured field-testing methodology.',
                    'future_application' => 'The capstone extends this system with a LoRa fallback, and I now test real-world constraints before committing to a design.',
                ],
                'completed_at' => now()->subWeeks(2),
            ]
        );

        // --- Section 9: OJT with a supervisor rating ---------------------------
        $ojt = OjtRecord::updateOrCreate(
            ['portfolio_id' => $portfolio->id],
            [
                'company_name' => 'Northlink Electronics Corporation',
                'company_address' => 'Carig Sur, Tuguegarao City, Cagayan',
                'industry' => 'Electronics manufacturing',
                'supervisor_name' => 'Engr. Ronald Miguel',
                'supervisor_position' => 'Test Engineering Supervisor',
                'supervisor_email' => 'rmiguel@northlink.example',
                'started_on' => now()->subMonths(9),
                'ended_on' => now()->subMonths(7),
                'required_hours' => 240,
                'completed_hours' => 248,
                'objectives' => 'Learn production test workflows and contribute to test automation.',
                'responsibilities' => 'Wrote Python test scripts for board-level functional testing, maintained the test jig, documented failure modes for the QA team.',
                'work_outputs' => 'Automated test script suite that cut per-board test time from 95 to 61 seconds; failure-mode documentation adopted by QA.',
                'student_reflection' => 'Production constraints matter more than elegance. A test that runs in half the time is worth more to the line than a cleverer one that does not.',
            ]
        );

        $supervisor = OjtSupervisorEvaluation::issueToken($ojt);
        $supervisor->update([
            'technical_competency' => 4, 'professional_behavior' => 4, 'communication' => 3,
            'teamwork' => 4, 'safety' => 4, 'engineering_analysis' => 3,
            'documentation' => 4, 'professional_growth' => 4,
            'strengths' => 'Picked up the test framework quickly and worked well with the line technicians.',
            'areas_for_improvement' => 'Speak up earlier when a specification looks wrong; the instinct was right but came late.',
            'signed_by' => 'Engr. Ronald Miguel, Test Engineering Supervisor',
            'submitted_at' => now()->subMonths(6),
        ]);

        // --- Section 10: capstone -----------------------------------------------
        CapstoneRecord::updateOrCreate(
            ['portfolio_id' => $portfolio->id],
            [
                'project_id' => $project->id,
                'title' => 'LoRa-Backed Irrigation Network for Smallholder Farms',
                'problem_definition' => 'The WiFi-based controller cannot cover plots beyond 40 m, which excludes most target farms.',
                'literature_review' => 'Compared LoRaWAN, plain LoRa point-to-point and NB-IoT on cost, range and gateway requirements in the Philippine 433/868 MHz allocation.',
                'requirements' => 'Cover 500 m through foliage; battery life at least 90 days; per-node cost under PHP 4,000; no monthly subscription.',
                'system_architecture' => 'LoRa nodes to a single gateway, gateway bridging to MQTT, dashboard unchanged from the previous build.',
                'design' => 'Star topology with duty-cycled nodes, gateway on mains with battery backup, over-the-air threshold updates.',
                'implementation' => 'Four nodes and one gateway built; firmware in C with FreeRTOS; gateway bridge in Python.',
                'testing' => 'Range tested at 100 m intervals through maize and banana cover; packet delivery measured at each distance.',
                'validation' => 'Held above 95% delivery to 520 m through foliage; node current draw projects to 104 days on a 3,000 mAh cell.',
                'cost_analysis' => 'PHP 3,640 per node and PHP 6,200 for the gateway, amortised across four nodes.',
                'risk_assessment' => 'Gateway is a single point of failure; documented a fallback to local threshold control when the link drops.',
                'ethics' => 'Location and yield data stay on the farmer\'s own gateway; nothing leaves the farm without explicit opt-in.',
                'sustainability' => 'Solar nodes, replaceable cells rather than sealed packs, and enclosures chosen for field repair rather than disposal.',
                'documentation_note' => 'Full design report, schematics, firmware repository and deployment guide submitted with the defense.',
                'proposal_defended_on' => now()->subMonths(4),
                'final_defended_on' => now()->subWeeks(3),
                'adviser_name' => $evaluator->displayName(),
            ]
        );

        // --- Section 11: professional development ------------------------------
        ProfessionalDevelopmentRecord::updateOrCreate(
            ['portfolio_id' => $portfolio->id, 'title' => 'Regional ICpEP.SE Embedded Systems Workshop'],
            [
                'kind' => 'workshop',
                'organizer' => 'ICpEP.SE Region 2',
                'held_on' => now()->subMonths(5),
                'hours' => 16,
                'competency_demonstrated' => 'Configured FreeRTOS task priorities and diagnosed a priority-inversion bug in the workshop exercise, then applied the same approach to fix a scheduling stall in the capstone firmware.',
                'claimed_level' => 3,
                'validated_level' => 3,
                'evaluator_id' => $evaluator->id,
            ]
        );

        // --- Faculty assessment (Section X) -------------------------------------
        $evaluation = FacultyEvaluation::updateOrCreate(
            ['portfolio_id' => $portfolio->id, 'artifact_assessed' => 'Capstone final defense, COME 1173'],
            [
                'evaluator_id' => $evaluator->id,
                'evaluated_on' => now()->subWeeks(3),
                'overall_comment' => 'Strong integrative work. Range validation was rigorous. Risk documentation should be produced earlier rather than at write-up.',
            ]
        );

        $ratings = [
            'technical_knowledge' => [4, null],
            'problem_solving' => [4, null],
            'engineering_design' => [4, null],
            'tool_usage' => [4, null],
            'investigation' => [3, 'Field methodology sound; a second gateway position would have strengthened the claim.'],
            'communication' => [4, null],
            'teamwork' => [4, null],
            'ethics' => [3, 'Data-ownership position is good; write it up as an explicit access-control policy.'],
            'project_management' => [3, 'Schedule slipped roughly a week around the enclosure fabrication.'],
            'professionalism' => [4, null],
        ];

        foreach ($ratings as $criterion => [$rating, $comment]) {
            FacultyEvaluationScore::updateOrCreate(
                ['faculty_evaluation_id' => $evaluation->id, 'criterion' => $criterion],
                ['rating' => $rating, 'comment' => $comment]
            );
        }

        $evaluation->update(['overall_rating' => $evaluation->computeOverall()]);

        // --- Indirect evidence, reported separately -----------------------------
        foreach ([2 => 3, 3 => 4, 9 => 4, 10 => 3, 14 => 4] as $number => $level) {
            IndirectAssessment::updateOrCreate(
                ['portfolio_id' => $portfolio->id, 'plo_id' => $plos[$number], 'source' => 'employer_feedback'],
                ['level' => $level, 'comment' => 'From the OJT supervisor evaluation.', 'collected_on' => now()->subMonths(6)]
            );
        }

        $portfolio->update([
            'status' => 'submitted',
            'submitted_at' => now()->subWeeks(2),
        ]);
    }
}
