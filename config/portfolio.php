<?php

/**
 * PORTFOLIO DEFINITION FILE
 * =============================================================================
 * This file is the single source of truth for the *shape* of the portfolio:
 * which sections exist, what kind of editor each one uses, which year levels
 * they apply to, and what counts as "complete".
 *
 * Why config instead of 12 hand-written forms:
 *   - Five sections (1, 2, 7, 8, 11) are flat question-and-answer forms. They
 *     are rendered generically by App\Livewire\Portfolio\SectionForm from the
 *     `fields` array below, so adding a question is a one-line config change
 *     and needs no migration.
 *   - The other seven sections are tables, matrices or repeaters with their own
 *     database tables, so each has a dedicated Livewire component named here
 *     under `component`.
 *
 * Section keys are stable identifiers; do not renumber them after go-live
 * because section_entries rows reference them.
 * =============================================================================
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Assessment policy
    |--------------------------------------------------------------------------
    | These knobs drive App\Services\PloAttainmentService. Changing the target
    | here changes every dashboard and CQI flag consistently.
    */
    'assessment' => [
        // Mean score (1-4) at or above which a PLO is reported as attained.
        'target' => (float) env('PLO_ATTAINMENT_TARGET', 3.00),

        // Evidence rated below this on the Evidence Quality Scale is stored and
        // visible, but excluded from the attainment computation (Section VI).
        'min_evidence_quality' => (int) env('PLO_MIN_EVIDENCE_QUALITY', 3),

        // Weight by evidence source. Capstone and OJT are integrative and
        // carry double the weight of an ordinary course artifact.
        'weights' => [
            'course' => 1.0,
            'laboratory' => 1.0,
            'project' => 1.5,
            'design_project' => 1.5,
            'research' => 1.5,
            'ojt' => 2.0,
            'capstone' => 2.0,
        ],

        // A PLO with fewer valid direct-evidence records than this is reported
        // as "under-assessed" rather than passed or failed.
        'min_evidence_count' => 2,
    ],

    /*
    |--------------------------------------------------------------------------
    | Competency stage ladder (Section V of the specification)
    |--------------------------------------------------------------------------
    */
    'competency_stages' => [
        'knowledge' => 'Knowledge',
        'skill' => 'Skill',
        'application' => 'Application',
        'integration' => 'Integration',
        'professional_practice' => 'Professional Practice',
    ],

    /*
    |--------------------------------------------------------------------------
    | Deadline defaults
    |--------------------------------------------------------------------------
    | The portfolio is due before each academic year ends. Semester checkpoints
    | keep students from doing everything in the last week; the year-end
    | deadline is the one that locks the portfolio for faculty validation.
    */
    'deadlines' => [
        'grace_days' => 7,          // window in which a late submission is accepted with a flag
        'reminder_days' => [14, 7, 1], // days before due date that reminders fire
        'lock_on_due' => false,     // false = accept late with flag, true = hard lock
    ],

    /*
    |--------------------------------------------------------------------------
    | Sections
    |--------------------------------------------------------------------------
    | editor:    'fields'    -> rendered generically by SectionForm
    |            'component' -> dedicated Livewire class
    | years:     year levels where the section is required (1-4)
    | checkpoint:'semester' -> also has a mid-year checkpoint deadline
    |            'year'     -> only the year-end deadline
    */
    'sections' => [

        1 => [
            'key' => 'student_profile',
            'title' => 'Student Profile',
            'blurb' => 'Who you are and where you are heading. Update it at the start of every academic year.',
            'editor' => 'fields',
            'years' => [1, 2, 3, 4],
            'checkpoint' => 'year',
            'fields' => [
                // --- Contact & enrollment, as the printed form's top block ---------
                ['name' => 'address', 'label' => 'Address', 'type' => 'text', 'required' => true],
                ['name' => 'email_address', 'label' => 'E-mail address', 'type' => 'text', 'required' => true,
                 'help' => 'This can be the same address you sign in with.'],
                ['name' => 'contact_number', 'label' => 'Contact number/s', 'type' => 'text', 'required' => true],
                ['name' => 'term_year_started', 'label' => 'Term/Year started', 'type' => 'text', 'required' => true,
                 'help' => 'For example: 1st Semester, AY 2023-2024.'],

                // --- Personal data ---------------------------------------------------
                ['name' => 'personal_data_heading', 'label' => 'Personal data', 'type' => 'heading'],
                ['name' => 'gender', 'label' => 'Gender', 'type' => 'select', 'required' => true,
                 'options' => ['Male', 'Female']],
                ['name' => 'date_of_birth', 'label' => 'Date of birth', 'type' => 'date', 'required' => true],
                ['name' => 'birth_place', 'label' => 'Birth place', 'type' => 'text', 'required' => true],
                ['name' => 'religion', 'label' => 'Religion', 'type' => 'text', 'required' => false],
                ['name' => 'civil_status', 'label' => 'Civil status', 'type' => 'select', 'required' => true,
                 'options' => ['Single', 'Married', 'Widowed', 'Separated']],
                ['name' => 'citizenship', 'label' => 'Citizenship', 'type' => 'text', 'required' => true],
                ['name' => 'parents', 'label' => 'Parents/Guardian', 'type' => 'textarea', 'rows' => 2, 'required' => true],

                // --- Educational background -------------------------------------------
                ['name' => 'educational_background_heading', 'label' => 'Educational background', 'type' => 'heading'],
                ['name' => 'kinder_school', 'label' => 'Kinder 1-2', 'type' => 'text', 'required' => false],
                ['name' => 'elementary_school', 'label' => 'Grade 1-6', 'type' => 'text', 'required' => true],
                ['name' => 'junior_high_school', 'label' => 'Grade 7-10', 'type' => 'text', 'required' => true],
                ['name' => 'senior_high_school', 'label' => 'Grade 11-12', 'type' => 'text', 'required' => false],
                ['name' => 'tertiary_school', 'label' => 'Tertiary', 'type' => 'text', 'required' => false,
                 'help' => 'Write the program/school before taking up BSCpE at USLT, if any.'],

                // --- Where this year is heading ---------------------------------------
                ['name' => 'goals_heading', 'label' => 'Where you are heading', 'type' => 'heading'],
                ['name' => 'career_goals', 'label' => 'Career goals', 'type' => 'textarea', 'rows' => 3, 'required' => true,
                 'help' => 'What kind of engineer do you want to be, and where?'],
                ['name' => 'specializations', 'label' => 'Areas of specialization', 'type' => 'text', 'required' => true,
                 'help' => 'For example: embedded systems, IoT, digital systems.'],
                ['name' => 'professional_interests', 'label' => 'Professional interests', 'type' => 'textarea', 'rows' => 2, 'required' => false],

                // --- Personal reflection -----------------------------------------------
                ['name' => 'personal_reflection_heading', 'label' => 'Personal reflection', 'type' => 'heading'],
                ['name' => 'personal_reflection', 'label' => 'Your reflection', 'type' => 'textarea', 'rows' => 5, 'required' => true,
                 'help' => 'Write a personal reflection on your journey as a Computer Engineering student — your motivations, challenges, growth, and goals.'],
            ],
        ],

        2 => [
            'key' => 'personal_development_plan',
            'title' => 'Personal Development Plan',
            'blurb' => 'Your plan for this academic year. Written at the start, reviewed at year end.',
            'editor' => 'fields',
            'years' => [1, 2, 3, 4],
            'checkpoint' => 'semester',
            'fields' => [
                ['name' => 'career_goals', 'label' => 'Career goals for this year', 'type' => 'textarea', 'rows' => 3, 'required' => true],
                ['name' => 'technical_goals', 'label' => 'Technical competency goals', 'type' => 'textarea', 'rows' => 3, 'required' => true],
                ['name' => 'professional_goals', 'label' => 'Professional competency goals', 'type' => 'textarea', 'rows' => 3, 'required' => true],
                ['name' => 'skills_to_develop', 'label' => 'Skills to develop', 'type' => 'textarea', 'rows' => 2, 'required' => true],
                ['name' => 'certifications', 'label' => 'Certifications targeted', 'type' => 'textarea', 'rows' => 2, 'required' => false],
                ['name' => 'research_interests', 'label' => 'Research interests', 'type' => 'textarea', 'rows' => 2, 'required' => false],
                ['name' => 'industry_interests', 'label' => 'Industry interests', 'type' => 'textarea', 'rows' => 2, 'required' => false],
                ['name' => 'short_term_goals', 'label' => 'Short-term goals', 'type' => 'textarea', 'rows' => 2, 'required' => true],
                ['name' => 'long_term_goals', 'label' => 'Long-term goals', 'type' => 'textarea', 'rows' => 2, 'required' => true],
                ['name' => 'year_end_review', 'label' => 'Year-end review of this plan', 'type' => 'textarea', 'rows' => 4, 'required' => false,
                 'help' => 'Filled at the end of the year: what you met, what slipped, and why.'],
            ],
        ],

        3 => [
            'key' => 'plo_competency_matrix',
            'title' => 'PLO and Competency Matrix',
            'blurb' => 'Mark where each of the 14 PLOs sits this year. You propose the level, your evaluator confirms it.',
            'editor' => 'component',
            'component' => 'portfolio.plo-matrix',
            'years' => [1, 2, 3, 4],
            'checkpoint' => 'year',
        ],

        4 => [
            'key' => 'course_based_evidence',
            'title' => 'Course-Based Evidence',
            'blurb' => 'Link each major course output to its CLO, its PLOs, and the file that proves it.',
            'editor' => 'component',
            'component' => 'portfolio.course-evidence',
            'years' => [1, 2, 3, 4],
            'checkpoint' => 'semester',
        ],

        5 => [
            'key' => 'technical_competency',
            'title' => 'Technical Competency Portfolio',
            'blurb' => 'Your standing on each technical competency, from Knowledge through Professional Practice.',
            'editor' => 'component',
            'component' => 'portfolio.technical-competency',
            'years' => [1, 2, 3, 4],
            'checkpoint' => 'year',
        ],

        6 => [
            'key' => 'engineering_design',
            'title' => 'Engineering Design Portfolio',
            'blurb' => 'One major design project per year, documented across the full 20-step design cycle.',
            'editor' => 'component',
            'component' => 'portfolio.design-project',
            'years' => [2, 3, 4],
            'checkpoint' => 'year',
        ],

        7 => [
            'key' => 'research_investigation',
            'title' => 'Research and Investigation Portfolio',
            'blurb' => 'Evidence that you can investigate a problem, not just build for one.',
            'editor' => 'fields',
            'years' => [3, 4],
            'checkpoint' => 'year',
            'fields' => [
                ['name' => 'problem_formulation', 'label' => 'Research problem formulation', 'type' => 'textarea', 'rows' => 3, 'required' => true],
                ['name' => 'literature_review', 'label' => 'Literature review summary', 'type' => 'textarea', 'rows' => 4, 'required' => true],
                ['name' => 'methodology', 'label' => 'Research methodology', 'type' => 'textarea', 'rows' => 3, 'required' => true],
                ['name' => 'data_collection', 'label' => 'Data collection', 'type' => 'textarea', 'rows' => 2, 'required' => true],
                ['name' => 'data_analysis', 'label' => 'Data and statistical analysis', 'type' => 'textarea', 'rows' => 3, 'required' => true],
                ['name' => 'experimental_design', 'label' => 'Experimental design', 'type' => 'textarea', 'rows' => 2, 'required' => false],
                ['name' => 'results', 'label' => 'Results and interpretation', 'type' => 'textarea', 'rows' => 3, 'required' => true],
                ['name' => 'dissemination', 'label' => 'Presentation, paper, or dissemination', 'type' => 'textarea', 'rows' => 2, 'required' => false],
                ['name' => 'research_ethics', 'label' => 'Research ethics considerations', 'type' => 'textarea', 'rows' => 2, 'required' => true],
            ],
        ],

        8 => [
            'key' => 'professional_competency',
            'title' => 'Professional Competency Portfolio',
            'blurb' => 'The competencies that are not code: ethics, communication, leadership, judgement.',
            'editor' => 'fields',
            'years' => [1, 2, 3, 4],
            'checkpoint' => 'year',
            'fields' => [
                ['name' => 'ethics', 'label' => 'Professional ethics', 'type' => 'textarea', 'rows' => 2, 'required' => true],
                ['name' => 'communication', 'label' => 'Communication', 'type' => 'textarea', 'rows' => 2, 'required' => true],
                ['name' => 'teamwork', 'label' => 'Teamwork', 'type' => 'textarea', 'rows' => 2, 'required' => true],
                ['name' => 'leadership', 'label' => 'Leadership', 'type' => 'textarea', 'rows' => 2, 'required' => false],
                ['name' => 'project_management', 'label' => 'Project and time management', 'type' => 'textarea', 'rows' => 2, 'required' => true],
                ['name' => 'documentation', 'label' => 'Documentation', 'type' => 'textarea', 'rows' => 2, 'required' => false],
                ['name' => 'critical_thinking', 'label' => 'Problem solving and critical thinking', 'type' => 'textarea', 'rows' => 2, 'required' => true],
                ['name' => 'innovation', 'label' => 'Innovation and adaptability', 'type' => 'textarea', 'rows' => 2, 'required' => false],
                ['name' => 'safety', 'label' => 'Safety and professional responsibility', 'type' => 'textarea', 'rows' => 2, 'required' => true],
                ['name' => 'lifelong_learning', 'label' => 'Lifelong learning', 'type' => 'textarea', 'rows' => 2, 'required' => true],
            ],
        ],

        9 => [
            'key' => 'industry_ojt',
            'title' => 'Industry / OJT Portfolio',
            'blurb' => 'Your 240-hour immersion: company, work, supervisor rating, and what it taught you.',
            'editor' => 'component',
            'component' => 'portfolio.ojt-record',
            'years' => [4],
            'checkpoint' => 'year',
        ],

        10 => [
            'key' => 'capstone',
            'title' => 'Capstone Project Portfolio',
            'blurb' => 'CpE Practice and Design, from proposal through defense, mapped to PLOs.',
            'editor' => 'component',
            'component' => 'portfolio.capstone',
            'years' => [4],
            'checkpoint' => 'semester',
        ],

        11 => [
            'key' => 'professional_development',
            'title' => 'Professional Development',
            'blurb' => 'Seminars, competitions, certifications. A certificate alone earns nothing; describe what you can now do.',
            'editor' => 'component',
            'component' => 'portfolio.professional-development',
            'years' => [1, 2, 3, 4],
            'checkpoint' => 'year',
        ],

        12 => [
            'key' => 'reflection',
            'title' => 'Student Reflection',
            'blurb' => 'Ten questions after every major project. This is where assessors look first.',
            'editor' => 'component',
            'component' => 'portfolio.reflection',
            'years' => [1, 2, 3, 4],
            'checkpoint' => 'year',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Minimum evidence requirements per academic year (Section XII)
    |--------------------------------------------------------------------------
    | Curated, not exhaustive. PortfolioCompletionService checks against these.
    */
    'minimum_evidence' => [
        'technical_artifacts' => 3,
        'design_projects' => 1,
        'research_artifacts' => 1,
        'communication_artifacts' => 1,
        'teamwork_artifacts' => 1,
        'reflections_per_project' => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | The ten reflection prompts (Section 12), kept here so the wording is
    | identical in the form, the export, and any future report.
    |--------------------------------------------------------------------------
    */
    'reflection_prompts' => [
        'learned' => 'What did I learn?',
        'problem_solved' => 'What engineering problem did I solve?',
        'skills_developed' => 'What skills did I develop?',
        'plos_addressed' => 'Which PLOs did this activity address?',
        'evidence' => 'What evidence demonstrates my competency?',
        'difficulties' => 'What difficulties did I encounter?',
        'overcame' => 'How did I overcome them?',
        'improve' => 'What would I improve?',
        'additional_skills' => 'What additional skills do I need?',
        'future_application' => 'How will I apply this learning in future engineering work?',
    ],

    /*
    |--------------------------------------------------------------------------
    | The 20 design-cycle elements (Section 6), in order.
    |--------------------------------------------------------------------------
    */
    'design_elements' => [
        'problem_identification' => 'Problem identification',
        'problem_statement' => 'Problem statement',
        'requirements' => 'Requirements',
        'literature' => 'Literature / benchmarking',
        'concept_generation' => 'Concept generation',
        'alternatives' => 'Alternative solutions',
        'design_selection' => 'Design selection',
        'system_architecture' => 'System architecture',
        'hardware_design' => 'Hardware design',
        'software_design' => 'Software design',
        'prototype' => 'Prototype development',
        'testing' => 'Testing',
        'validation' => 'Validation',
        'evaluation' => 'Evaluation',
        'cost_analysis' => 'Cost analysis',
        'safety' => 'Safety considerations',
        'environmental' => 'Environmental considerations',
        'ethical' => 'Ethical considerations',
        'improvements' => 'Improvements',
        'final_reflection' => 'Final reflection',
    ],

    /*
    |--------------------------------------------------------------------------
    | Faculty assessment criteria (Section X)
    |--------------------------------------------------------------------------
    | Comments are required when a criterion is rated below `comment_below`.
    */
    'faculty_criteria' => [
        'technical_knowledge' => 'Technical knowledge',
        'problem_solving' => 'Problem solving',
        'engineering_design' => 'Engineering design',
        'tool_usage' => 'Tool usage',
        'investigation' => 'Investigation',
        'communication' => 'Communication',
        'teamwork' => 'Teamwork',
        'ethics' => 'Ethics',
        'project_management' => 'Project management',
        'professionalism' => 'Professionalism',
    ],
    'comment_below' => 3,
];
