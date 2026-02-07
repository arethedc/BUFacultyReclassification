<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\FacultyProfile;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FacultyProfileController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $status = $request->get('status', 'active'); // active | inactive | all

        // ✅ guard allowed values (prevents invalid filters)
        if (!in_array($status, ['active', 'inactive', 'all'], true)) {
            $status = 'active';
        }

        $query = User::query()
            ->where('role', 'faculty')
            ->with(['department', 'facultyProfile']);

        // ✅ hide inactive by default
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        // ✅ search (name/email/employee no)
        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhereHas('facultyProfile', function ($fp) use ($q) {
                        $fp->where('employee_no', 'like', "%{$q}%");
                    });
            });
        }

        $faculty = $query
            ->latest()
            ->paginate(10)
            ->appends([
                'q' => $q,
                'status' => $status,
            ]);

return view('faculty_profiles.index', compact('faculty', 'q', 'status'));
    }

    public function edit(User $user)
    {
        abort_unless($user->role === 'faculty', 404);

        $user->load(['facultyProfile', 'department']);

        // ✅ Ensure profile exists
        $profile = $user->facultyProfile ?? FacultyProfile::create([
            'user_id' => $user->id,
            'employee_no' => 'TEMP-' . $user->id,
        ]);

        return view('faculty_profiles.edit', compact('user', 'profile'));
    }

    public function update(Request $request, User $user)
    {
        abort_unless($user->role === 'faculty', 404);

        $profile = FacultyProfile::firstOrCreate(
            ['user_id' => $user->id],
            ['employee_no' => 'TEMP-' . $user->id]
        );

        $data = $request->validate([
            'employee_no' => 'required|string|max:50|unique:faculty_profiles,employee_no,' . $profile->id,
            'employment_type' => ['required', Rule::in(['full_time', 'part_time'])],
            'teaching_rank' => 'required|string|max:100',
            'rank_step' => ['nullable', Rule::in(['A', 'B', 'C'])],
            'original_appointment_date' => 'nullable|date',
        ]);

        $profile->update($data);

        return redirect()
            ->route('faculty-profiles.edit', $user)
            ->with('success', 'Faculty profile updated successfully.');
    }
}
