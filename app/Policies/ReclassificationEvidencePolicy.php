<?php

namespace App\Policies;

use App\Models\ReclassificationEvidence;
use App\Models\User;
use App\Support\ReclassificationStageAccess;
use App\Support\ReclassificationWorkflowRules;

class ReclassificationEvidencePolicy
{
    public function review(User $user, ReclassificationEvidence $evidence): bool
    {
        if (!ReclassificationWorkflowRules::isReviewerRole($user->role)) {
            return false;
        }

        $app = $evidence->application()->with('faculty')->first();
        if (!$app) {
            return false;
        }

        return ReclassificationStageAccess::reviewerOwnsApplicationStage($user, $app, false);
    }
}
