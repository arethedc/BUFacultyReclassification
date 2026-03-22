<?php

namespace App\Support;

class ReclassificationWorkflowRules
{
    private const REVIEWER_ROLES = [
        'dean',
        'hr',
        'vpaa',
        'president',
    ];

    private const REVIEW_STATUS_BY_ROLE = [
        'dean' => 'dean_review',
        'hr' => 'hr_review',
        'vpaa' => 'vpaa_review',
    ];

    private const STAGE_OWNER_BY_STATUS = [
        'dean_review' => 'dean',
        'hr_review' => 'hr',
        'vpaa_review' => 'vpaa',
        'vpaa_approved' => 'vpaa',
        'president_review' => 'president',
    ];

    private const FORWARD_TRANSITIONS = [
        'dean_review' => ['next_status' => 'hr_review', 'next_step' => 'hr'],
        'hr_review' => ['next_status' => 'vpaa_review', 'next_step' => 'vpaa'],
        'vpaa_review' => ['next_status' => 'vpaa_approved', 'next_step' => 'vpaa'],
    ];

    private const STEP_BY_STATUS = [
        'draft' => 'faculty',
        'returned_to_faculty' => 'faculty',
        'dean_review' => 'dean',
        'hr_review' => 'hr',
        'vpaa_review' => 'vpaa',
        'vpaa_approved' => 'vpaa',
        'president_review' => 'president',
        'finalized' => 'finalized',
        'rejected_final' => 'finalized',
    ];

    private const RESUBMIT_TARGET_BY_RETURNED_FROM = [
        'dean' => ['status' => 'dean_review', 'step' => 'dean'],
        'hr' => ['status' => 'hr_review', 'step' => 'hr'],
        'vpaa' => ['status' => 'vpaa_review', 'step' => 'vpaa'],
        'president' => ['status' => 'president_review', 'step' => 'president'],
    ];

    private const RETURNABLE_STATUSES = [
        'dean_review',
        'hr_review',
        'vpaa_review',
        'vpaa_approved',
        'president_review',
    ];

    private const HR_FINAL_REJECTABLE_STATUSES = [
        'dean_review',
        'hr_review',
        'vpaa_review',
        'vpaa_approved',
        'president_review',
        'returned_to_faculty',
        'finalized',
    ];

    public static function normalizeRole(?string $role): string
    {
        return strtolower(trim((string) $role));
    }

    public static function normalizeStatus(?string $status): string
    {
        return strtolower(trim((string) $status));
    }

    public static function reviewerRoles(): array
    {
        return self::REVIEWER_ROLES;
    }

    public static function isReviewerRole(?string $role): bool
    {
        return in_array(self::normalizeRole($role), self::REVIEWER_ROLES, true);
    }

    public static function reviewStatusForRole(?string $role): ?string
    {
        return self::REVIEW_STATUS_BY_ROLE[self::normalizeRole($role)] ?? null;
    }

    public static function stageOwnerRoleForStatus(?string $status, bool $allowVpaaApproved = true): ?string
    {
        $normalizedStatus = self::normalizeStatus($status);
        if (!$allowVpaaApproved && $normalizedStatus === 'vpaa_approved') {
            return null;
        }

        return self::STAGE_OWNER_BY_STATUS[$normalizedStatus] ?? null;
    }

    public static function reviewerOwnsStatus(?string $role, ?string $status, bool $allowVpaaApproved = true): bool
    {
        $expected = self::stageOwnerRoleForStatus($status, $allowVpaaApproved);
        if (!$expected) {
            return false;
        }

        return self::normalizeRole($role) === $expected;
    }

    public static function forwardTransitionFor(?string $status): ?array
    {
        return self::FORWARD_TRANSITIONS[self::normalizeStatus($status)] ?? null;
    }

    public static function initialSubmissionTarget(): array
    {
        return [
            'status' => 'dean_review',
            'step' => 'dean',
        ];
    }

    public static function resubmitTargetFor(?string $returnedFrom): ?array
    {
        return self::RESUBMIT_TARGET_BY_RETURNED_FROM[self::normalizeRole($returnedFrom)] ?? null;
    }

    public static function submissionTargetFor(?string $applicationStatus, ?string $returnedFrom): ?array
    {
        $status = self::normalizeStatus($applicationStatus);
        if ($status === 'draft') {
            return self::initialSubmissionTarget();
        }

        if ($status === 'returned_to_faculty') {
            return self::resubmitTargetFor($returnedFrom);
        }

        return null;
    }

    public static function facultyReturnRequestStatuses(): array
    {
        return self::RETURNABLE_STATUSES;
    }

    public static function canFacultyRequestReturnFrom(?string $status): bool
    {
        return in_array(self::normalizeStatus($status), self::RETURNABLE_STATUSES, true);
    }

    public static function canReviewerReturnToFacultyFrom(?string $status): bool
    {
        return in_array(self::normalizeStatus($status), self::RETURNABLE_STATUSES, true);
    }

    public static function hrFinalRejectableStatuses(): array
    {
        return self::HR_FINAL_REJECTABLE_STATUSES;
    }

    public static function canHrFinalRejectFrom(?string $status): bool
    {
        return in_array(self::normalizeStatus($status), self::HR_FINAL_REJECTABLE_STATUSES, true);
    }

    public static function stepForStatus(?string $status): ?string
    {
        return self::STEP_BY_STATUS[self::normalizeStatus($status)] ?? null;
    }
}
