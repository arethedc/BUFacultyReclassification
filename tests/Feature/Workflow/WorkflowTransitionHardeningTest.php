<?php

use App\Models\ReclassificationApplication;
use App\Models\ReclassificationPeriod;
use App\Models\ReclassificationStatusTrail;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->withoutMiddleware(ValidateCsrfToken::class);
});

function createActivePeriod(User $creator, string $cycleYear = '2025-2026'): ReclassificationPeriod
{
    return ReclassificationPeriod::create([
        'name' => 'Test Active Period',
        'cycle_year' => $cycleYear,
        'status' => 'active',
        'is_open' => true,
        'created_by_user_id' => $creator->id,
    ]);
}

test('returned submission with invalid return source cannot be resubmitted', function () {
    $faculty = User::factory()->create([
        'role' => 'faculty',
        'status' => 'active',
    ]);

    $application = ReclassificationApplication::create([
        'faculty_user_id' => $faculty->id,
        'cycle_year' => '2025-2026',
        'status' => 'returned_to_faculty',
        'current_step' => 'faculty',
        'returned_from' => 'unknown_stage',
    ]);

    $response = $this->actingAs($faculty)->post(route('reclassification.submit', $application));

    $response->assertSessionHasErrors('submit');
    expect($application->fresh()->status)->toBe('returned_to_faculty');
});

test('faculty can request return while application is in president review', function () {
    $faculty = User::factory()->create([
        'role' => 'faculty',
        'status' => 'active',
    ]);

    $application = ReclassificationApplication::create([
        'faculty_user_id' => $faculty->id,
        'cycle_year' => '2025-2026',
        'status' => 'president_review',
        'current_step' => 'president',
    ]);

    $response = $this->actingAs($faculty)->post(route('reclassification.request-return', $application), [
        'return_request_reason' => 'Need to update a supporting document.',
    ]);

    $response->assertSessionHasNoErrors()->assertSessionHas('success');

    $fresh = $application->fresh();
    expect($fresh->faculty_return_requested_by_user_id)->toBe($faculty->id);
    expect($fresh->faculty_return_requested_at)->not->toBeNull();
    expect((string) $fresh->faculty_return_request_reason)->toContain('supporting document');

    $trail = ReclassificationStatusTrail::query()
        ->where('reclassification_application_id', $application->id)
        ->where('action', 'faculty_request_return')
        ->latest('id')
        ->first();

    expect($trail)->not->toBeNull();
});

test('faculty can cancel pending return request', function () {
    $faculty = User::factory()->create([
        'role' => 'faculty',
        'status' => 'active',
    ]);

    $application = ReclassificationApplication::create([
        'faculty_user_id' => $faculty->id,
        'cycle_year' => '2025-2026',
        'status' => 'president_review',
        'current_step' => 'president',
    ]);

    $requestResponse = $this->actingAs($faculty)->post(route('reclassification.request-return', $application), [
        'return_request_reason' => 'Please return so I can update evidence.',
    ]);
    $requestResponse->assertSessionHasNoErrors();

    $cancelResponse = $this->actingAs($faculty)->post(route('reclassification.cancel-return-request', $application));
    $cancelResponse->assertSessionHasNoErrors()->assertSessionHas('success');

    $fresh = $application->fresh();
    expect($fresh->faculty_return_requested_at)->toBeNull();
    expect($fresh->faculty_return_requested_by_user_id)->toBeNull();
    expect($fresh->faculty_return_request_reason)->toBeNull();

    $trail = ReclassificationStatusTrail::query()
        ->where('reclassification_application_id', $application->id)
        ->where('action', 'faculty_cancel_return_request')
        ->latest('id')
        ->first();

    expect($trail)->not->toBeNull();
    expect((string) data_get($trail?->meta, 'canceled_request_reason'))->toContain('update evidence');
});

test('reviewer return works without decision remark', function () {
    $hr = User::factory()->create([
        'role' => 'hr',
        'status' => 'active',
    ]);
    $period = createActivePeriod($hr);
    $faculty = User::factory()->create([
        'role' => 'faculty',
        'status' => 'active',
    ]);

    $application = ReclassificationApplication::create([
        'faculty_user_id' => $faculty->id,
        'period_id' => $period->id,
        'cycle_year' => (string) $period->cycle_year,
        'status' => 'hr_review',
        'current_step' => 'hr',
        'faculty_return_requested_at' => now(),
        'faculty_return_requested_by_user_id' => $faculty->id,
        'faculty_return_request_reason' => 'Please return for revisions.',
    ]);

    $response = $this->actingAs($hr)->post(route('reclassification.return', $application), []);
    $response->assertSessionHasNoErrors();

    $fresh = $application->fresh();
    expect($fresh->status)->toBe('returned_to_faculty');

    $trail = ReclassificationStatusTrail::query()
        ->where('reclassification_application_id', $application->id)
        ->where('action', 'return_to_faculty')
        ->latest('id')
        ->first();

    expect($trail)->not->toBeNull();
    expect(data_get($trail?->meta, 'decision_note'))->toBeNull();
});

test('reviewer forward works without decision remark', function () {
    $hr = User::factory()->create([
        'role' => 'hr',
        'status' => 'active',
    ]);
    $period = createActivePeriod($hr);
    $faculty = User::factory()->create([
        'role' => 'faculty',
        'status' => 'active',
    ]);

    $application = ReclassificationApplication::create([
        'faculty_user_id' => $faculty->id,
        'period_id' => $period->id,
        'cycle_year' => (string) $period->cycle_year,
        'status' => 'hr_review',
        'current_step' => 'hr',
        'submitted_at' => now(),
    ]);

    $response = $this->actingAs($hr)->post(route('reclassification.forward', $application), []);
    $response->assertSessionHasNoErrors();

    $fresh = $application->fresh();
    expect($fresh->status)->toBe('vpaa_review');
    expect($fresh->current_step)->toBe('vpaa');

    $trail = ReclassificationStatusTrail::query()
        ->where('reclassification_application_id', $application->id)
        ->where('action', 'forward')
        ->latest('id')
        ->first();
    expect($trail)->not->toBeNull();
    expect(data_get($trail?->meta, 'decision_note'))->toBeNull();
});

test('hr reject toggle keeps restorable workflow stage and status trail history', function () {
    $hr = User::factory()->create([
        'role' => 'hr',
        'status' => 'active',
    ]);
    $faculty = User::factory()->create([
        'role' => 'faculty',
        'status' => 'active',
    ]);

    $application = ReclassificationApplication::create([
        'faculty_user_id' => $faculty->id,
        'cycle_year' => '2025-2026',
        'status' => 'vpaa_review',
        'current_step' => 'vpaa',
    ]);

    $reject = $this->actingAs($hr)->post(route('reclassification.admin.submissions.toggle-reject', $application), [
        'reason' => 'Missing required evidence.',
    ]);
    $reject->assertSessionHasNoErrors()->assertSessionHas('success');

    $rejected = $application->fresh();
    expect($rejected->status)->toBe('rejected_final');
    expect($rejected->current_step)->toBe('finalized');
    expect($rejected->rejection_finalized_by_user_id)->toBe($hr->id);
    expect((string) $rejected->rejection_final_reason)->toContain('Missing required evidence');

    $rejectTrail = ReclassificationStatusTrail::query()
        ->where('reclassification_application_id', $application->id)
        ->where('action', 'reject_final')
        ->latest('id')
        ->first();
    expect($rejectTrail)->not->toBeNull();
    expect((string) data_get($rejectTrail?->meta, 'restorable_to_status'))->toBe('vpaa_review');
    expect((string) data_get($rejectTrail?->meta, 'restorable_to_step'))->toBe('vpaa');

    $reactivate = $this->actingAs($hr)->post(route('reclassification.admin.submissions.toggle-reject', $application), [
        'reason' => 'Evidence issue resolved.',
    ]);
    $reactivate->assertSessionHasNoErrors()->assertSessionHas('success');

    $restored = $application->fresh();
    expect($restored->status)->toBe('vpaa_review');
    expect($restored->current_step)->toBe('vpaa');
    expect($restored->rejection_finalized_by_user_id)->toBeNull();
    expect($restored->rejection_final_reason)->toBeNull();
    expect($restored->rejection_finalized_at)->toBeNull();

    $reactivateTrail = ReclassificationStatusTrail::query()
        ->where('reclassification_application_id', $application->id)
        ->where('action', 'reactivate_after_final_reject')
        ->latest('id')
        ->first();
    expect($reactivateTrail)->not->toBeNull();
    expect((string) data_get($reactivateTrail?->meta, 'restored_to_status'))->toBe('vpaa_review');
});

test('hr reject toggle requires reason', function () {
    $hr = User::factory()->create([
        'role' => 'hr',
        'status' => 'active',
    ]);
    $faculty = User::factory()->create([
        'role' => 'faculty',
        'status' => 'active',
    ]);

    $application = ReclassificationApplication::create([
        'faculty_user_id' => $faculty->id,
        'cycle_year' => '2025-2026',
        'status' => 'hr_review',
        'current_step' => 'hr',
    ]);

    $response = $this->actingAs($hr)->post(route('reclassification.admin.submissions.toggle-reject', $application), []);
    $response->assertSessionHasErrors('reason');
    expect($application->fresh()->status)->toBe('hr_review');
});

test('forwarding vpaa endorsement list does not close submission window', function () {
    $vpaa = User::factory()->create([
        'role' => 'vpaa',
        'status' => 'active',
    ]);
    $faculty = User::factory()->create([
        'role' => 'faculty',
        'status' => 'active',
    ]);
    $period = createActivePeriod($vpaa);

    $application = ReclassificationApplication::create([
        'faculty_user_id' => $faculty->id,
        'period_id' => $period->id,
        'cycle_year' => (string) $period->cycle_year,
        'status' => 'vpaa_approved',
        'current_step' => 'vpaa',
    ]);

    $response = $this->actingAs($vpaa)->post(route('reclassification.review.approved.forward'));
    $response->assertSessionHasNoErrors()->assertSessionHas('success');

    $application->refresh();
    $period->refresh();

    expect($application->status)->toBe('president_review');
    expect($application->current_step)->toBe('president');
    expect((bool) $period->is_open)->toBeTrue();
});

test('finalizing approved list does not close submission window', function () {
    Notification::fake();

    $president = User::factory()->create([
        'role' => 'president',
        'status' => 'active',
    ]);
    $faculty = User::factory()->create([
        'role' => 'faculty',
        'status' => 'active',
    ]);
    $period = createActivePeriod($president);

    $application = ReclassificationApplication::create([
        'faculty_user_id' => $faculty->id,
        'period_id' => $period->id,
        'cycle_year' => (string) $period->cycle_year,
        'status' => 'president_review',
        'current_step' => 'president',
        'submitted_at' => now(),
    ]);

    $response = $this->actingAs($president)->post(route('reclassification.review.approved.finalize'));
    $response->assertSessionHasNoErrors()->assertSessionHas('success');

    $application->refresh();
    $period->refresh();

    expect($application->status)->toBe('finalized');
    expect($application->current_step)->toBe('finalized');
    expect((bool) $period->is_open)->toBeTrue();
});
