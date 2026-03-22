<?php

use App\Models\Department;
use App\Models\ReclassificationApplication;
use App\Models\ReclassificationEvidence;
use App\Models\ReclassificationSection;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->withoutMiddleware(ValidateCsrfToken::class);
});

function createEvidenceForApplication(User $faculty, string $status): ReclassificationEvidence
{
    $application = ReclassificationApplication::create([
        'faculty_user_id' => $faculty->id,
        'cycle_year' => '2025-2026',
        'status' => $status,
        'current_step' => match ($status) {
            'dean_review' => 'dean',
            'hr_review' => 'hr',
            'vpaa_review' => 'vpaa',
            'president_review' => 'president',
            default => 'faculty',
        },
    ]);

    $section = ReclassificationSection::create([
        'reclassification_application_id' => $application->id,
        'section_code' => '1',
        'title' => 'Section I',
        'is_complete' => true,
        'points_total' => 0,
    ]);

    return ReclassificationEvidence::create([
        'reclassification_application_id' => $application->id,
        'reclassification_section_id' => $section->id,
        'uploaded_by_user_id' => $faculty->id,
        'disk' => 'public',
        'path' => 'test-evidence.pdf',
        'original_name' => 'test-evidence.pdf',
        'status' => 'pending',
    ]);
}

test('inactive users are blocked from authenticated routes', function () {
    $inactiveFaculty = User::factory()->create([
        'role' => 'faculty',
        'status' => 'inactive',
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($inactiveFaculty)->get('/dashboard');

    $response->assertRedirect(route('login', absolute: false));
    $this->assertGuest();
});

test('dean cannot review evidence outside their department', function () {
    $deanDepartment = Department::create(['name' => 'TEST-DEAN-DEPT']);
    $facultyDepartment = Department::create(['name' => 'TEST-FACULTY-DEPT']);

    $dean = User::factory()->create([
        'role' => 'dean',
        'status' => 'active',
        'department_id' => $deanDepartment->id,
    ]);
    $faculty = User::factory()->create([
        'role' => 'faculty',
        'status' => 'active',
        'department_id' => $facultyDepartment->id,
    ]);

    $evidence = createEvidenceForApplication($faculty, 'dean_review');

    $response = $this->actingAs($dean)->post(route('reclassification.evidence.accept', $evidence), [
        'review_note' => 'Looks good.',
    ]);

    $response->assertForbidden();
});

test('dean can review evidence for own department at dean stage', function () {
    $department = Department::create(['name' => 'TEST-SHARED-DEPT']);

    $dean = User::factory()->create([
        'role' => 'dean',
        'status' => 'active',
        'department_id' => $department->id,
    ]);
    $faculty = User::factory()->create([
        'role' => 'faculty',
        'status' => 'active',
        'department_id' => $department->id,
    ]);

    $evidence = createEvidenceForApplication($faculty, 'dean_review');

    $response = $this->actingAs($dean)->post(route('reclassification.evidence.accept', $evidence), [
        'review_note' => 'Approved by dean.',
    ]);

    $response->assertOk()->assertJsonPath('evidence.status', 'accepted');
});

test('hr cannot review evidence while submission is in dean stage', function () {
    $department = Department::create(['name' => 'TEST-HR-DEPT']);

    $hr = User::factory()->create([
        'role' => 'hr',
        'status' => 'active',
        'department_id' => null,
    ]);
    $faculty = User::factory()->create([
        'role' => 'faculty',
        'status' => 'active',
        'department_id' => $department->id,
    ]);

    $evidence = createEvidenceForApplication($faculty, 'dean_review');

    $response = $this->actingAs($hr)->post(route('reclassification.evidence.accept', $evidence), [
        'review_note' => 'HR attempt.',
    ]);

    $response->assertForbidden();
});

test('hr can review evidence during hr stage', function () {
    $department = Department::create(['name' => 'TEST-HR-STAGE-DEPT']);

    $hr = User::factory()->create([
        'role' => 'hr',
        'status' => 'active',
        'department_id' => null,
    ]);
    $faculty = User::factory()->create([
        'role' => 'faculty',
        'status' => 'active',
        'department_id' => $department->id,
    ]);

    $evidence = createEvidenceForApplication($faculty, 'hr_review');

    $response = $this->actingAs($hr)->post(route('reclassification.evidence.accept', $evidence), [
        'review_note' => 'HR approved.',
    ]);

    $response->assertOk()->assertJsonPath('evidence.status', 'accepted');
});

test('api and web evidence review endpoints use aligned middleware/controller wiring', function () {
    $routes = collect(Route::getRoutes()->getRoutes());

    $webAccept = $routes->first(fn ($route) => $route->uri() === 'reclassification/evidences/{evidence}/accept');
    $apiAccept = $routes->first(fn ($route) => $route->uri() === 'api/reclassification/evidences/{evidence}/accept');

    expect($webAccept)->not->toBeNull();
    expect($apiAccept)->not->toBeNull();
    expect($webAccept->getActionName())->toBe($apiAccept->getActionName());
    expect($apiAccept->middleware())->toContain('auth:sanctum');
    expect($apiAccept->middleware())->toContain('active_user');
});




