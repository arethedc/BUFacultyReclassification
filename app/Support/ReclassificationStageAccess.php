<?php

namespace App\Support;

use App\Models\ReclassificationApplication;
use App\Models\User;

class ReclassificationStageAccess
{
    public static function reviewerOwnsApplicationStage(
        User $user,
        ReclassificationApplication $application,
        bool $allowVpaaApproved = true
    ): bool {
        if (!ReclassificationWorkflowRules::reviewerOwnsStatus($user->role, $application->status, $allowVpaaApproved)) {
            return false;
        }

        $actorRole = ReclassificationWorkflowRules::normalizeRole($user->role);
        if ($actorRole !== 'dean') {
            return true;
        }

        $application->loadMissing('faculty');

        $userDepartmentId = (int) ($user->department_id ?? 0);
        $facultyDepartmentId = (int) ($application->faculty?->department_id ?? 0);

        return $userDepartmentId > 0 && $userDepartmentId === $facultyDepartmentId;
    }
}
