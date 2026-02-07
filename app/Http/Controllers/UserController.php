<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Department;
use App\Models\FacultyProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /* =====================================================
        USERS INDEX
    ===================================================== */
   public function index(Request $request)
{
    $q = trim((string) $request->get('q', ''));
    $status = $request->get('status', 'active'); // active | inactive | all

    // ✅ guard allowed values
    if (!in_array($status, ['active', 'inactive', 'all'], true)) {
        $status = 'active';
    }

    $usersQuery = User::query()->with(['department', 'facultyProfile']);

    // ✅ hide inactive by default
    if ($status !== 'all') {
        $usersQuery->where('status', $status);
    }

    if ($q !== '') {
        $usersQuery->where(function ($query) use ($q) {
            $query->where('name', 'like', "%{$q}%")
                  ->orWhere('email', 'like', "%{$q}%")
                  ->orWhere('role', 'like', "%{$q}%")
                  ->orWhereHas('facultyProfile', function ($fp) use ($q) {
                      $fp->where('employee_no', 'like', "%{$q}%");
                  });
        });
    }

    $users = $usersQuery
        ->latest()
        ->paginate(10)
        ->appends([
            'q' => $q,
            'status' => $status,
        ]);

    return view('users.index', compact('users', 'q', 'status'));
}


    /* =====================================================
        CREATE USER / CREATE FACULTY
    ===================================================== */
    public function create()
    {
        $context = request('context'); // 'faculty' or null

        return view('users.create', [
            'context' => $context,
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    /* =====================================================
        STORE USER
    ===================================================== */
    public function store(Request $request)
    {
        $data = $request->validate([
            'role' => ['required', Rule::in(['faculty', 'dean', 'hr', 'vpaa', 'president'])],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],

            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',

            // name parts
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'suffix' => 'nullable|string|max:20',

            // department rules
            'department_id' => 'nullable|exists:departments,id|required_if:role,faculty,dean',

            // faculty-only
            'employee_no' => 'nullable|string|max:50|required_if:role,faculty|unique:faculty_profiles,employee_no',
            'employment_type' => 'nullable|in:full_time,part_time',
            'teaching_rank' => 'nullable|string|max:100',
            'rank_step' => 'nullable|string|max:10',
            'original_appointment_date' => 'nullable|date',
        ]);

        $fullName = trim(
            $data['first_name'] . ' ' .
            ($data['middle_name'] ? $data['middle_name'] . ' ' : '') .
            $data['last_name'] . ' ' .
            ($data['suffix'] ?? '')
        );

        $user = User::create([
            'name' => $fullName,
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'status' => $data['status'] ?? 'active',
            'department_id' => $data['department_id'] ?? null,
        ]);

        // Create faculty profile ONLY if faculty
        if ($user->role === 'faculty') {
            FacultyProfile::create([
                'user_id' => $user->id,
                'employee_no' => $data['employee_no'],
                'employment_type' => $data['employment_type'] ?? 'full_time',
                'teaching_rank' => $data['teaching_rank'] ?? 'Instructor',
                'rank_step' => $data['rank_step'] ?? null,
                'original_appointment_date' => $data['original_appointment_date'] ?? null,
            ]);
        }

        return redirect()
            ->route('users.index')
            ->with('success', 'User created successfully.');
    }

    /* =====================================================
        EDIT USER
    ===================================================== */
    public function edit(Request $request, User $user)
    {
        $departments = Department::orderBy('name')->get();
        $user->load(['department', 'facultyProfile']);

        // remember back location
        $back = url()->previous();
        $fallback = route('users.index');

        if (str_contains($back, "/users/{$user->id}/edit")) {
            $back = $fallback;
        }

        return view('users.edit', compact('user', 'departments', 'back'));
    }

    /* =====================================================
        UPDATE USER
    ===================================================== */
    public function update(Request $request, User $user)
    {
        $needsDepartment = in_array($user->role, ['faculty', 'dean']);

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];

        if ($needsDepartment) {
            $rules['department_id'] = 'required|exists:departments,id';
        } else {
            $rules['department_id'] = 'nullable';
        }

        $data = $request->validate($rules);

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'status' => $data['status'],
            'department_id' => $needsDepartment ? $data['department_id'] : null,
        ]);

        return redirect()
            ->route('users.edit', $user)
            ->with('success', 'User updated successfully.');
    }
}
