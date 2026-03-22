<?php

use App\Support\ReclassificationWorkflowRules;

test('workflow rules expose expected role and transition maps', function () {
    expect(ReclassificationWorkflowRules::reviewStatusForRole('dean'))->toBe('dean_review');
    expect(ReclassificationWorkflowRules::reviewStatusForRole('hr'))->toBe('hr_review');
    expect(ReclassificationWorkflowRules::reviewStatusForRole('vpaa'))->toBe('vpaa_review');
    expect(ReclassificationWorkflowRules::reviewStatusForRole('president'))->toBeNull();

    expect(ReclassificationWorkflowRules::forwardTransitionFor('dean_review'))->toBe([
        'next_status' => 'hr_review',
        'next_step' => 'hr',
    ]);
    expect(ReclassificationWorkflowRules::forwardTransitionFor('hr_review'))->toBe([
        'next_status' => 'vpaa_review',
        'next_step' => 'vpaa',
    ]);
    expect(ReclassificationWorkflowRules::forwardTransitionFor('vpaa_review'))->toBe([
        'next_status' => 'vpaa_approved',
        'next_step' => 'vpaa',
    ]);
});

test('workflow rules require a valid returned_from value for resubmission', function () {
    expect(ReclassificationWorkflowRules::submissionTargetFor('draft', null))->toBe([
        'status' => 'dean_review',
        'step' => 'dean',
    ]);

    expect(ReclassificationWorkflowRules::submissionTargetFor('returned_to_faculty', 'hr'))->toBe([
        'status' => 'hr_review',
        'step' => 'hr',
    ]);

    expect(ReclassificationWorkflowRules::submissionTargetFor('returned_to_faculty', 'unknown'))->toBeNull();
});
