<?php

namespace App\Support\Enums;

/**
 * Where a score came from. Two jobs:
 *   1. picks the weight in config('portfolio.assessment.weights')
 *   2. separates DIRECT evidence from INDIRECT evidence, which the
 *      specification requires never to be averaged together.
 */
enum AssessmentType: string
{
    // Direct assessment
    case Course = 'course';
    case Laboratory = 'laboratory';
    case Project = 'project';
    case DesignProject = 'design_project';
    case Research = 'research';
    case Ojt = 'ojt';
    case Capstone = 'capstone';

    // Indirect assessment
    case SelfAssessment = 'self_assessment';
    case ExitSurvey = 'exit_survey';
    case EmployerFeedback = 'employer_feedback';
    case AlumniFeedback = 'alumni_feedback';

    public function isDirect(): bool
    {
        return in_array($this, [
            self::Course, self::Laboratory, self::Project,
            self::DesignProject, self::Research, self::Ojt, self::Capstone,
        ], true);
    }

    public function weight(): float
    {
        return (float) (config('portfolio.assessment.weights')[$this->value] ?? 1.0);
    }

    public function label(): string
    {
        return str($this->value)->replace('_', ' ')->title()->toString();
    }
}
