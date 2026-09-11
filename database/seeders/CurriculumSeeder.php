<?php

namespace Database\Seeders;

use App\Models\CourseLearningOutcome;
use App\Models\CurriculumCourse;
use App\Models\Plo;
use App\Models\Program;
use Illuminate\Database\Seeder;

/**
 * USL BS COMPUTER ENGINEERING CURRICULUM (version 2023)
 * -----------------------------------------------------------------------------
 * Transcribed from the official program checklist. The fourth digit of the
 * course number carries the unit count in this coding scheme (CALC 1014 = 4
 * units, COME 1011 = 1 unit), so units are derived rather than typed twice.
 *
 * Major courses are flagged: those are the ones expected to produce portfolio
 * evidence. General-education and PE courses are seeded for completeness of the
 * checklist but are not portfolio-bearing.
 *
 * A starter CLO is attached to each major course and mapped to PLOs, so the
 * system can answer "where is this outcome taught?" from day one. Faculty
 * replace these with their own CLOs per syllabus.
 */
class CurriculumSeeder extends Seeder
{
    public function run(): void
    {
        $program = Program::updateOrCreate(
            ['code' => 'BSCPE'],
            [
                'title' => 'Bachelor of Science in Computer Engineering',
                'department' => 'School of Engineering, Architecture and Information Technology Education',
                'curriculum_version' => '2023',
                'is_active' => true,
            ]
        );

        $plos = Plo::pluck('id', 'number');

        foreach ($this->courses() as [$yearLevel, $termKind, $code, $title]) {
            $course = CurriculumCourse::updateOrCreate(
                ['program_id' => $program->id, 'code' => $code],
                [
                    'title' => $title,
                    'units' => $this->unitsFromCode($code),
                    'year_level' => $yearLevel,
                    'term_kind' => $termKind,
                    'is_major' => $this->isMajor($code),
                ]
            );

            if ($mapping = $this->starterClo($code)) {
                $clo = CourseLearningOutcome::updateOrCreate(
                    ['curriculum_course_id' => $course->id, 'code' => 'CLO1'],
                    ['statement' => $mapping['statement']]
                );

                $clo->plos()->sync(
                    collect($mapping['plos'])
                        ->mapWithKeys(fn ($n) => [$plos[$n] => ['target_level' => $mapping['target'] ?? 2]])
                        ->all()
                );
            }
        }
    }

    /** The unit count is the fourth character of the course number. */
    protected function unitsFromCode(string $code): int
    {
        $number = preg_replace('/\D/', '', $code);

        return (int) (substr($number, 3, 1) ?: 3);
    }

    /** Subject prefixes that carry computer engineering content. */
    protected function isMajor(string $code): bool
    {
        $prefix = strtok($code, ' ');

        return in_array($prefix, [
            'COME', 'CIRC', 'ECIR', 'MICRO', 'CTRL', 'PROG', 'HWRE', 'WEB', 'CADD', 'DATA', 'NMAT', 'DMAT',
        ], true);
    }

    /**
     * Seed CLO and PLO mapping for the courses that carry the program's
     * technical spine. Keyed by course code.
     */
    protected function starterClo(string $code): ?array
    {
        return [
            'COME 1022' => ['statement' => 'Design and implement a structured program to solve a defined computational problem.', 'plos' => [1, 13], 'target' => 1],
            'PROG 1052' => ['statement' => 'Apply object-oriented principles to build a modular application.', 'plos' => [1, 5, 13], 'target' => 1],
            'HWRE 1013' => ['statement' => 'Identify, assemble and troubleshoot computer hardware components.', 'plos' => [5, 13], 'target' => 1],
            'COME 1033' => ['statement' => 'Implement and analyse the efficiency of fundamental data structures and algorithms.', 'plos' => [1, 2, 13], 'target' => 2],
            'ECIR 1014' => ['statement' => 'Analyse DC and AC electrical circuits using network theorems.', 'plos' => [1, 2], 'target' => 2],
            'CIRC 1014' => ['statement' => 'Analyse and build circuits using diodes, transistors and operational amplifiers.', 'plos' => [1, 3], 'target' => 2],
            'COME 1044' => ['statement' => 'Produce a software design specification for a multi-component system.', 'plos' => [3, 10], 'target' => 2],
            'WEB 1043' => ['statement' => 'Develop and deploy an online application using current web technologies.', 'plos' => [5, 14], 'target' => 2],
            'CIRC 1064' => ['statement' => 'Design, simulate and implement combinational and sequential logic circuits.', 'plos' => [1, 3, 13], 'target' => 2],
            'COME 1051' => ['statement' => 'Produce engineering drawings and schematics to professional documentation standards.', 'plos' => [5, 10], 'target' => 2],
            'CTRL 1014' => ['statement' => 'Model and analyse the stability and response of a feedback control system.', 'plos' => [1, 2, 13], 'target' => 3],
            'COME 1063' => ['statement' => 'Analyse digital and data communication systems and their performance limits.', 'plos' => [1, 13], 'target' => 3],
            'COME 1073' => ['statement' => 'Apply a scripting language to automate an engineering computation or data task.', 'plos' => [5, 13], 'target' => 3],
            'COME 1081' => ['statement' => 'Describe and simulate digital hardware behaviour using a hardware description language.', 'plos' => [3, 5, 13], 'target' => 3],
            'MICRO 1014' => ['statement' => 'Interface peripherals to a microprocessor and program the resulting system.', 'plos' => [3, 5, 13], 'target' => 3],
            'COME 1104' => ['statement' => 'Design, configure and secure a computer network for a specified requirement.', 'plos' => [3, 6, 13], 'target' => 3],
            'COME 1204' => ['statement' => 'Apply digital signal processing techniques to analyse and filter real signals.', 'plos' => [1, 4, 13], 'target' => 3],
            'COME 1113' => ['statement' => 'Build and evaluate a machine learning model on an engineering dataset.', 'plos' => [4, 14], 'target' => 3],
            'COME 1123' => ['statement' => 'Explain and demonstrate operating system resource management mechanisms.', 'plos' => [1, 13], 'target' => 3],
            'COME 1193' => ['statement' => 'Apply occupational health and safety principles to engineering work settings.', 'plos' => [6, 8], 'target' => 3],
            'RESM 1013' => ['statement' => 'Formulate a research problem and design an appropriate methodology.', 'plos' => [2, 4, 10], 'target' => 3],
            'COME 1163' => ['statement' => 'Perform assigned engineering work in an industry setting to professional standards.', 'plos' => [6, 8, 9, 10, 12, 14], 'target' => 3],
            'COME 1134' => ['statement' => 'Design and implement an embedded system meeting specified real-time constraints.', 'plos' => [3, 5, 13, 14], 'target' => 4],
            'COME 1093' => ['statement' => 'Design signal conditioning for sensors in a mixed-signal system.', 'plos' => [1, 3, 13], 'target' => 4],
            'COME 1144' => ['statement' => 'Evaluate computer architecture design decisions against performance requirements.', 'plos' => [1, 2, 13], 'target' => 4],
            'COME 1183' => ['statement' => 'Apply data mining techniques to extract usable knowledge from a dataset.', 'plos' => [4, 14], 'target' => 4],
            'COME 1151' => ['statement' => 'Produce a capstone project proposal with requirements, design and project plan.', 'plos' => [2, 3, 10, 11], 'target' => 4],
            'COME 1173' => ['statement' => 'Implement, test and defend a complete computer engineering capstone project.', 'plos' => [3, 4, 9, 10, 11, 13, 14], 'target' => 4],
            'COME 1213' => ['statement' => 'Evaluate an emerging technology and its application to computer engineering practice.', 'plos' => [12, 14], 'target' => 4],
            'LAPP 1012' => ['statement' => 'Explain the legal and ethical obligations of the registered computer engineer.', 'plos' => [6, 8], 'target' => 4],
            'EMGT 1013' => ['statement' => 'Apply engineering management principles to planning and resourcing a project.', 'plos' => [11], 'target' => 3],
            'ENGE 1013' => ['statement' => 'Evaluate engineering alternatives using economic decision criteria.', 'plos' => [11], 'target' => 3],
            'DATA 1013' => ['statement' => 'Apply statistical methods to analyse engineering data.', 'plos' => [2, 4], 'target' => 2],
            'NMAT 1014' => ['statement' => 'Apply numerical methods to solve engineering problems computationally.', 'plos' => [1, 5], 'target' => 2],
            'ENGL 1023' => ['statement' => 'Produce technical documents that communicate engineering work to defined audiences.', 'plos' => [10], 'target' => 2],
        ][$code] ?? null;
    }

    /** [year level, term, code, title] */
    protected function courses(): array
    {
        return [
            // ---- Year 1, First Semester -------------------------------------
            [1, 'first_semester', 'CALC 1014', 'Calculus 1 (Differential Calculus)'],
            [1, 'first_semester', 'CFED 1013', "God's Journey with His People"],
            [1, 'first_semester', 'CHEM 1014', 'Chemistry for Engineers'],
            [1, 'first_semester', 'COME 1011', 'Computer Engineering as a Discipline'],
            [1, 'first_semester', 'RZAL 1013', 'Life and Works of Rizal'],
            [1, 'first_semester', 'COME 1022', 'Programming Logic and Design'],
            [1, 'first_semester', 'MATH 1023', 'Mathematics of Engineering'],
            [1, 'first_semester', 'HIST 1013', 'Readings in Philippine History'],
            [1, 'first_semester', 'PHED 1012', 'Physical Education (Health and Wellness)'],
            [1, 'first_semester', 'PHYS 1003', 'General Physics'],

            // ---- Year 1, Second Semester ------------------------------------
            [1, 'second_semester', 'CFED 1023', 'Christian Morality in our Times'],
            [1, 'second_semester', 'GRBK 1013', 'Great Books'],
            [1, 'second_semester', 'SCTS 1013', 'Science, Technology and Society'],
            [1, 'second_semester', 'MATH 1013', 'Mathematics in the Modern World'],
            [1, 'second_semester', 'PHED 1022', 'Physical Education (Combative Sports)'],
            [1, 'second_semester', 'CALC 1024', 'Calculus 2 (Integral Calculus)'],
            [1, 'second_semester', 'DATA 1013', 'Engineering Data Analysis'],
            [1, 'second_semester', 'PHYS 1015', 'Physics for Engineers'],
            [1, 'second_semester', 'PROG 1052', 'Object Oriented Programming'],

            // ---- Year 1, Summer ---------------------------------------------
            [1, 'summer', 'CONW 1013', 'The Contemporary World'],
            [1, 'summer', 'HWRE 1013', 'Computer Hardware Fundamentals'],
            [1, 'summer', 'NSTP 1013', 'National Service Training Program 1'],

            // ---- Year 2, First Semester --------------------------------------
            [2, 'first_semester', 'CADD 1011', 'Computer-Aided Drafting'],
            [2, 'first_semester', 'CFED 1033', 'Catholic Foundation of Mission'],
            [2, 'first_semester', 'DEQN 1013', 'Differential Equations'],
            [2, 'first_semester', 'COME 1033', 'Data Structures and Algorithm'],
            [2, 'first_semester', 'ENTR 1023', 'The Entrepreneurial Mind'],
            [2, 'first_semester', 'DMAT 1043', 'Discrete Mathematics'],
            [2, 'first_semester', 'ECIR 1014', 'Fundamentals of Electrical Circuits'],
            [2, 'first_semester', 'ENGL 1013', 'Purposive Communication'],
            [2, 'first_semester', 'PHED 1032', 'Physical Education (Indoor Games)'],

            // ---- Year 2, Second Semester -------------------------------------
            [2, 'second_semester', 'CFED 1043', 'CICM Missionary Identity'],
            [2, 'second_semester', 'CIRC 1014', 'Fundamentals of Electronic Circuits'],
            [2, 'second_semester', 'NMAT 1014', 'Numerical Methods'],
            [2, 'second_semester', 'COME 1044', 'Software Design'],
            [2, 'second_semester', 'ENGL 1023', 'Advanced Technical Communication'],
            [2, 'second_semester', 'ENVI 1013', 'Environmental Science'],
            [2, 'second_semester', 'WEB 1043', 'Online Technology'],
            [2, 'second_semester', 'PHED 1042', 'Physical Education (Outdoor and Adventure Activities)'],

            // ---- Year 2, Summer ----------------------------------------------
            [2, 'summer', 'CIRC 1064', 'Logic Circuit and Design'],
            [2, 'summer', 'COME 1051', 'Computer Engineering Drafting and Design'],
            [2, 'summer', 'NSTP 1023', 'National Service Training Program 2'],

            // ---- Year 3, First Semester ---------------------------------------
            [3, 'first_semester', 'CFED 1051', 'CICM in Action: Justice, Peace, Integrity of Creation and Interreligious Dialogues'],
            [3, 'first_semester', 'PDEV 1013', 'Understanding the Self'],
            [3, 'first_semester', 'ENGE 1013', 'Engineering Economics'],
            [3, 'first_semester', 'CTRL 1014', 'Feedback and Control System'],
            [3, 'first_semester', 'COME 1063', 'Data and Digital Communications'],
            [3, 'first_semester', 'COME 1073', 'Cognate / Elective 1 (Python Programming)'],
            [3, 'first_semester', 'COME 1081', 'Introduction to HDL'],
            [3, 'first_semester', 'MICRO 1014', 'Microprocessor'],

            // ---- Year 3, Second Semester --------------------------------------
            [3, 'second_semester', 'CFED 1061', 'CICM in Action: Ecological Protection Management (DRRM)'],
            [3, 'second_semester', 'COME 1193', 'Basic Occupational Health and Safety'],
            [3, 'second_semester', 'COME 1104', 'Computer Network and Security'],
            [3, 'second_semester', 'COME 1204', 'Digital Signal and Processing'],
            [3, 'second_semester', 'RESM 1013', 'Methods of Research'],
            [3, 'second_semester', 'COME 1113', 'Cognate / Elective 2 (Introduction to Machine Learning)'],
            [3, 'second_semester', 'COME 1123', 'Operating System'],

            // ---- Year 3, Summer: the 240-hour immersion -----------------------
            [3, 'summer', 'COME 1163', 'On-the-Job Training (240 hours)'],

            // ---- Year 4, First Semester ---------------------------------------
            [4, 'first_semester', 'ARTS 1013', 'Art Appreciation'],
            [4, 'first_semester', 'CFED 1071', 'Embracing the CICM Mission A'],
            [4, 'first_semester', 'COME 1134', 'Embedded System'],
            [4, 'first_semester', 'COME 1093', 'Fundamentals of Mixed Signals and Sensors'],
            [4, 'first_semester', 'COME 1144', 'Computer Architecture and Organization'],
            [4, 'first_semester', 'COME 1183', 'Cognate / Elective 3 (Introduction to Data Mining)'],
            [4, 'first_semester', 'EMGT 1013', 'Engineering Management'],
            [4, 'first_semester', 'COME 1151', 'CpE Practice and Design 1'],

            // ---- Year 4, Second Semester --------------------------------------
            [4, 'second_semester', 'CFED 1081', 'Embracing the CICM Mission B'],
            [4, 'second_semester', 'ETHC 1013', 'Ethics'],
            [4, 'second_semester', 'ENTR 1013', 'Technopreneurship 101'],
            [4, 'second_semester', 'COME 1213', 'Emerging Technology in CpE'],
            [4, 'second_semester', 'COME 1173', 'CpE Practice and Design 2'],
            [4, 'second_semester', 'LANG 1013', 'Foreign Language'],
            [4, 'second_semester', 'LAPP 1012', 'CpE Laws and Professional Practice'],
            [4, 'second_semester', 'SEMI 1011', 'Seminars and Field Trips'],
        ];
    }
}
