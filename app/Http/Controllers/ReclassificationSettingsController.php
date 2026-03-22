<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\RankLevel;
use Database\Seeders\DepartmentsSeeder;
use Database\Seeders\RankLevelsSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ReclassificationSettingsController extends Controller
{
    public function index()
    {
        $departments = Department::query()
            ->withCount('users')
            ->orderBy('name')
            ->get();

        $rankLevels = RankLevel::query()
            ->withCount('facultyProfiles')
            ->orderBy('order_no')
            ->get();

        return view('settings.reclassification', compact('departments', 'rankLevels'));
    }

    public function seedDefaults(): RedirectResponse
    {
        DB::transaction(function (): void {
            (new DepartmentsSeeder())->run();
            (new RankLevelsSeeder())->run();
        });

        return redirect()
            ->route('settings.reclassification')
            ->with('success', 'Default departments and academic rank levels were seeded.');
    }

    public function storeDepartment(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:departments,name'],
        ]);

        Department::query()->create([
            'name' => trim((string) $data['name']),
        ]);

        return redirect()
            ->route('settings.reclassification')
            ->with('success', 'Department created.');
    }

    public function updateDepartment(Request $request, Department $department): RedirectResponse
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('departments', 'name')->ignore($department->id),
            ],
        ]);

        $department->update([
            'name' => trim((string) $data['name']),
        ]);

        return redirect()
            ->route('settings.reclassification')
            ->with('success', 'Department updated.');
    }

    public function destroyDepartment(Department $department): RedirectResponse
    {
        $assignedUsers = $department->users()->count();
        if ($assignedUsers > 0) {
            return redirect()
                ->route('settings.reclassification')
                ->withErrors([
                    'department' => 'Cannot delete department while assigned to users.',
                ]);
        }

        $department->delete();

        return redirect()
            ->route('settings.reclassification')
            ->with('success', 'Department deleted.');
    }

    public function storeRankLevel(Request $request): RedirectResponse
    {
        $request->merge([
            'code' => $this->normalizeRankCode($request->input('code')),
        ]);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:100', 'regex:/^[A-Z0-9_]+$/', 'unique:rank_levels,code'],
            'title' => ['required', 'string', 'max:255'],
            'order_no' => ['required', 'integer', 'min:1', 'max:9999', 'unique:rank_levels,order_no'],
        ]);

        RankLevel::query()->create([
            'code' => $data['code'],
            'title' => trim((string) $data['title']),
            'order_no' => (int) $data['order_no'],
        ]);

        return redirect()
            ->route('settings.reclassification')
            ->with('success', 'Academic rank level created.');
    }

    public function updateRankLevel(Request $request, RankLevel $rankLevel): RedirectResponse
    {
        $request->merge([
            'code' => $this->normalizeRankCode($request->input('code')),
        ]);

        $data = $request->validate([
            'code' => [
                'required',
                'string',
                'max:100',
                'regex:/^[A-Z0-9_]+$/',
                Rule::unique('rank_levels', 'code')->ignore($rankLevel->id),
            ],
            'title' => ['required', 'string', 'max:255'],
            'order_no' => [
                'required',
                'integer',
                'min:1',
                'max:9999',
                Rule::unique('rank_levels', 'order_no')->ignore($rankLevel->id),
            ],
        ]);

        $rankLevel->update([
            'code' => $data['code'],
            'title' => trim((string) $data['title']),
            'order_no' => (int) $data['order_no'],
        ]);

        return redirect()
            ->route('settings.reclassification')
            ->with('success', 'Academic rank level updated.');
    }

    public function destroyRankLevel(RankLevel $rankLevel): RedirectResponse
    {
        $profileCount = $rankLevel->facultyProfiles()->count();
        if ($profileCount > 0) {
            return redirect()
                ->route('settings.reclassification')
                ->withErrors([
                    'rank_level' => 'Cannot delete rank level while assigned to faculty profiles.',
                ]);
        }

        $rankLevel->delete();

        return redirect()
            ->route('settings.reclassification')
            ->with('success', 'Academic rank level deleted.');
    }

    private function normalizeRankCode(mixed $code): string
    {
        return Str::of((string) $code)
            ->trim()
            ->upper()
            ->replaceMatches('/\s+/', '_')
            ->value();
    }
}
