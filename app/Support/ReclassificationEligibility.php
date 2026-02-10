<?php

namespace App\Support;

use App\Models\ReclassificationApplication;
use App\Models\User;

class ReclassificationEligibility
{
    public static function evaluate(ReclassificationApplication $application, User $user): array
    {
        $user->loadMissing(['facultyHighestDegree', 'facultyProfile']);
        $application->loadMissing(['sections.entries']);

        $degree = $user->facultyHighestDegree?->highest_degree;
        $degreeLabel = self::degreeLabel($degree);

        $hasMasters = in_array($degree, ['masters', 'doctorate'], true);
        $hasDoctorate = $degree === 'doctorate';

        $section3 = $application->sections->firstWhere('section_code', '3');
        $hasResearchEquivalent = $section3
            ? ($section3->entries->count() > 0 || (float) $section3->points_total > 0)
            : false;

        $missing = [];
        if (!$hasMasters) {
            $missing[] = 'Master’s degree is required.';
        }
        if (!$hasResearchEquivalent) {
            $missing[] = 'At least one research output is required.';
        }

        $canSubmit = empty($missing);

        return [
            'hasMasters' => $hasMasters,
            'hasDoctorate' => $hasDoctorate,
            'hasResearchEquivalent' => $hasResearchEquivalent,
            'hasAcceptedResearchOutput' => $hasResearchEquivalent,
            'missing' => $missing,
            'canSubmit' => $canSubmit,
            'degreeLabel' => $degreeLabel,
            'currentRank' => $user->facultyProfile?->teaching_rank ?? null,
        ];
    }

    private static function degreeLabel(?string $degree): string
    {
        if ($degree === 'masters') return 'Master’s Degree';
        if ($degree === 'doctorate') return 'Doctorate Degree';
        if ($degree === 'bachelors') return 'Bachelor’s Degree';
        return 'Not Set';
    }
}
