<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Department;
use App\Models\FacultyProfile;
use App\Models\FacultyHighestDegree;
use App\Models\RankLevel;
use App\Notifications\SetPasswordNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
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
        $viewer = request()->user();
        $isDean = $viewer && $viewer->role === 'dean';
        $defaultDepartmentId = $isDean ? $viewer->department_id : null;
        $forceRole = $isDean ? 'faculty' : null;
        $lockDepartment = $isDean;
        $actionRoute = $isDean ? route('dean.users.store') : route('users.store');
        $departments = $isDean && $defaultDepartmentId
            ? Department::where('id', $defaultDepartmentId)->get()
            : Department::orderBy('name')->get();

        return view('users.create', [
            'context' => $context,
            'departments' => $departments,
            'rankLevels' => Schema::hasTable('rank_levels')
                ? RankLevel::orderBy('order_no')->get()
                : collect(),
            'forceRole' => $forceRole,
            'lockDepartment' => $lockDepartment,
            'defaultDepartmentId' => $defaultDepartmentId,
            'actionRoute' => $actionRoute,
        ]);
    }

    /* =====================================================
        STORE USER
    ===================================================== */
    public function store(Request $request)
    {
        $isDean = $request->user()->role === 'dean';
        if ($isDean) {
            $departmentId = $request->user()->department_id;
            abort_unless($departmentId, 422);
            $request->merge([
                'role' => 'faculty',
                'department_id' => $departmentId,
            ]);
        }
        $isManualPassword = $request->boolean('manual_password');

        $data = $request->validate([
            'role' => ['required', Rule::in(['faculty', 'dean', 'hr', 'vpaa', 'president'])],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'manual_password' => ['nullable', 'boolean'],

            'email' => 'required|email|unique:users,email',
            'password' => ['nullable', 'min:8', 'confirmed', Rule::requiredIf($isManualPassword)],

            // name parts
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'suffix' => 'nullable|string|max:20',

            // department rules
            'department_id' => 'nullable|exists:departments,id|required_if:role,faculty,dean',

            // faculty-only
            'employee_no' => ['nullable', 'string', 'max:50', 'required_if:role,faculty', 'unique:faculty_profiles,employee_no', 'regex:/^\d{4}-\d{3}$/'],
            'employment_type' => 'nullable|in:full_time,part_time',
            'rank_level_id' => 'nullable|exists:rank_levels,id|required_if:role,faculty',
            'teaching_rank' => 'nullable|string|max:100',
            'original_appointment_date' => 'nullable|date',
            'highest_degree' => ['nullable', Rule::in(['bachelors', 'masters', 'doctorate'])],
        ]);

        $fullName = trim(
            $data['first_name'] . ' ' .
            ($data['middle_name'] ? $data['middle_name'] . ' ' : '') .
            $data['last_name'] . ' ' .
            ($data['suffix'] ?? '')
        );
        $rawPassword = $isManualPassword
            ? (string) ($data['password'] ?? '')
            : Str::password(16);

        $user = User::create([
            'name' => $fullName,
            'email' => $data['email'],
            'password' => Hash::make($rawPassword),
            'role' => $data['role'],
            'status' => $data['status'] ?? 'active',
            'department_id' => $data['department_id'] ?? null,
        ]);

        $message = 'User created successfully.';
        if (!$isManualPassword) {
            $token = Password::broker()->createToken($user);
            try {
                $user->notify(new SetPasswordNotification($token));
                $message .= ' Invitation email sent with password setup link.';
            } catch (\Throwable $e) {
                $message .= ' Password setup email could not be sent. You may resend using Forgot Password.';
            }
        } elseif (method_exists($user, 'sendEmailVerificationNotification') && !$user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
            $message .= ' Verification email sent.';
        }

        // Create faculty profile ONLY if faculty
        if ($user->role === 'faculty') {
            $rankTitle = null;
            if (!empty($data['rank_level_id'])) {
                $rankTitle = RankLevel::where('id', $data['rank_level_id'])->value('title');
            }
            FacultyProfile::create([
                'user_id' => $user->id,
                'employee_no' => $data['employee_no'],
                'employment_type' => $data['employment_type'] ?? 'full_time',
                'rank_level_id' => $data['rank_level_id'] ?? null,
                'teaching_rank' => $rankTitle ?? ($data['teaching_rank'] ?? 'Instructor'),
                'original_appointment_date' => $data['original_appointment_date'] ?? null,
            ]);

            if (!empty($data['highest_degree'])) {
                FacultyHighestDegree::create([
                    'user_id' => $user->id,
                    'highest_degree' => $data['highest_degree'],
                ]);
            }
        }

        return redirect()
            ->route($isDean ? 'dean.faculty.index' : 'users.index')
            ->with('success', $message);
    }

    /* =====================================================
        EDIT USER
    ===================================================== */
    public function edit(Request $request, User $user)
    {
        $departments = Department::orderBy('name')->get();
        $user->load(['department', 'facultyProfile']);
        $nameParts = $this->splitName($user->name ?? '');

        // remember back location
        $back = url()->previous();
        $fallback = route('users.index');

        if (str_contains($back, "/users/{$user->id}/edit")) {
            $back = $fallback;
        }

        return view('users.edit', compact('user', 'departments', 'back', 'nameParts'));
    }

    /* =====================================================
        UPDATE USER
    ===================================================== */
    public function update(Request $request, User $user)
    {
        $needsDepartment = in_array($user->role, ['faculty', 'dean']);

        $rules = [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'suffix' => 'nullable|string|max:20',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];

        if ($needsDepartment) {
            $rules['department_id'] = 'required|exists:departments,id';
        } else {
            $rules['department_id'] = 'nullable';
        }

        if ($user->role === 'faculty') {
            $rules['employee_no'] = ['required', 'string', 'max:50', 'regex:/^\d{4}-\d{3}$/'];
        }

        $data = $request->validate($rules);

        $fullName = trim(
            $data['first_name'] . ' ' .
            ($data['middle_name'] ? $data['middle_name'] . ' ' : '') .
            $data['last_name'] . ' ' .
            ($data['suffix'] ?? '')
        );

        $emailChanged = (string) $data['email'] !== (string) $user->email;

        $user->update([
            'name' => $fullName,
            'email' => $data['email'],
            'status' => $data['status'],
            'department_id' => $needsDepartment ? $data['department_id'] : null,
            'email_verified_at' => $emailChanged ? null : $user->email_verified_at,
        ]);

        if ($user->role === 'faculty' && $user->facultyProfile) {
            $user->facultyProfile->update([
                'employee_no' => $data['employee_no'],
            ]);
        }

        $message = 'User updated successfully.';
        if ($emailChanged && method_exists($user, 'sendEmailVerificationNotification')) {
            $user->sendEmailVerificationNotification();
            $message .= ' A verification email has been sent to the new email address.';
        }

        return redirect()
            ->route('users.edit', $user)
            ->with('success', $message);
    }

    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name));
        if (!$parts || count($parts) === 1) {
            return [
                'first_name' => $name,
                'middle_name' => '',
                'last_name' => '',
                'suffix' => '',
            ];
        }

        $suffixes = ['jr', 'sr', 'ii', 'iii', 'iv', 'v'];
        $suffix = '';
        $last = strtolower(end($parts));
        if (in_array($last, $suffixes, true)) {
            $suffix = array_pop($parts);
        }

        $first = array_shift($parts) ?? '';
        $last = array_pop($parts) ?? '';
        $middle = trim(implode(' ', $parts));

        return [
            'first_name' => $first,
            'middle_name' => $middle,
            'last_name' => $last,
            'suffix' => $suffix,
        ];
    }

    public function destroy(Request $request, User $user)
    {
        abort_unless($request->user()->role === 'hr', 403);

        if ((int) $request->user()->id === (int) $user->id) {
            return redirect()
                ->route('users.index')
                ->withErrors([
                    'user' => 'You cannot delete your own account.',
                ]);
        }

        $deletedName = (string) ($user->name ?? $user->email ?? 'User');
        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', "User deleted: {$deletedName}.");
    }
}
