<?php

use App\Models\ReclassificationApplication;
use App\Models\ReclassificationSection;
use App\Models\ReclassificationStatusTrail;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

beforeEach(function () {
    $this->withoutMiddleware(ValidateCsrfToken::class);
});

function createSubmittedApplication(User $faculty, string $status): ReclassificationApplication
{
    $application = ReclassificationApplication::create([
        'faculty_user_id' => $faculty->id,
        'cycle_year' => '2025-2026',
        'status' => $status,
        'current_step' => match ($status) {
            'dean_review' => 'dean',
            'hr_review' => 'hr',
            'vpaa_review', 'vpaa_approved' => 'vpaa',
            'president_review' => 'president',
            'rejected_final', 'finalized' => 'finalized',
            default => 'faculty',
        },
        'submitted_at' => now(),
    ]);

    ReclassificationSection::create([
        'reclassification_application_id' => $application->id,
        'section_code' => '1',
        'title' => 'Section I',
        'is_complete' => true,
        'points_total' => 10,
    ]);

    return $application;
}

test('submitted summary allows return request during president review stage', function () {
    $faculty = User::factory()->create([
        'role' => 'faculty',
        'status' => 'active',
    ]);

    $application = createSubmittedApplication($faculty, 'president_review');

    $response = $this->actingAs($faculty)->get(route('reclassification.submitted-summary.show', $application));

    $response->assertOk()->assertSee('Request Return');
});

test('submitted summary renders status trail history for faculty', function () {
    $faculty = User::factory()->create([
        'role' => 'faculty',
        'status' => 'active',
    ]);
    $dean = User::factory()->create([
        'role' => 'dean',
        'status' => 'active',
    ]);

    $application = createSubmittedApplication($faculty, 'hr_review');

    ReclassificationStatusTrail::create([
        'reclassification_application_id' => $application->id,
        'actor_user_id' => $faculty->id,
        'actor_role' => 'faculty',
        'from_status' => 'draft',
        'to_status' => 'dean_review',
        'from_step' => 'faculty',
        'to_step' => 'dean',
        'action' => 'submit',
        'note' => 'Submitted by faculty.',
    ]);
    ReclassificationStatusTrail::create([
        'reclassification_application_id' => $application->id,
        'actor_user_id' => $dean->id,
        'actor_role' => 'dean',
        'from_status' => 'dean_review',
        'to_status' => 'hr_review',
        'from_step' => 'dean',
        'to_step' => 'hr',
        'action' => 'forward',
        'note' => 'Forwarded to HR.',
    ]);

    $response = $this->actingAs($faculty)->get(route('reclassification.submitted-summary.show', $application));

    $response->assertOk()
        ->assertSee('Status Trail History')
        ->assertSee('Forwarded to HR.')
        ->assertSee($dean->name);
});

test('submitted summary shows final rejection reason details', function () {
    $faculty = User::factory()->create([
        'role' => 'faculty',
        'status' => 'active',
    ]);
    $hr = User::factory()->create([
        'role' => 'hr',
        'status' => 'active',
    ]);

    $application = createSubmittedApplication($faculty, 'rejected_final');
    $application->update([
        'rejection_finalized_by_user_id' => $hr->id,
        'rejection_final_reason' => 'Missing required supporting documents.',
        'rejection_finalized_at' => now(),
    ]);

    $response = $this->actingAs($faculty)->get(route('reclassification.submitted-summary.show', $application));

    $response->assertOk()
        ->assertSee('Final rejection reason:')
        ->assertSee('Missing required supporting documents.')
        ->assertSee($hr->name);
});
