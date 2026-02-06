{{-- resources/views/reclassification/section1.blade.php --}}
<x-app-layout>
<x-slot name="header">
    <div class="flex flex-col gap-1">
        <h2 class="text-2xl font-semibold text-gray-800">
            Reclassification – Section I
        </h2>
        <p class="text-sm text-gray-500">
            Academic Preparation and Professional Development (Max 140 pts / 35%)
        </p>
    </div>
</x-slot>

<form method="POST" enctype="multipart/form-data">
@csrf

<div x-data="sectionOne()" class="py-12 bg-bu-muted min-h-screen">
<div class="max-w-6xl mx-auto px-4 space-y-10">

{{-- =======================
IMPROVED STICKY SCORE SUMMARY (Expandable)
======================== --}}
<div
  x-data="{ open:true, stuck:false, userOverride:false }"
  x-init="
    const onScroll = () => {
      const nowStuck = window.scrollY > 140;

      if (!stuck && nowStuck) {
        stuck = true;
        if (!userOverride) open = false;
        return;
      }

      if (stuck && !nowStuck) {
        stuck = false;
        if (!userOverride) open = true;
        return;
      }

      stuck = nowStuck;
    };

    window.addEventListener('scroll', onScroll, { passive:true });
    onScroll();
  "
  class="sticky top-20 z-20"
>
  <div class="bg-white/95 backdrop-blur rounded-2xl border shadow-card">
    <div class="px-5 py-3 flex items-center justify-between gap-4">
      <div class="min-w-0">
        <div class="flex items-center gap-3">
          <h3 class="text-sm sm:text-base font-semibold text-gray-800 truncate">
            Section I Score Summary
          </h3>

          <template x-if="Number(rawTotal()) <= 140">
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium bg-green-50 text-green-700 border border-green-200">
              Within limit
            </span>
          </template>
          <template x-if="Number(rawTotal()) > 140">
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium bg-red-50 text-red-700 border border-red-200">
              Over limit
            </span>
          </template>
        </div>

        <p class="text-xs text-gray-600 mt-1">
          Raw: <span class="font-semibold text-gray-800" x-text="Number(rawTotal()).toFixed(2)"></span>
          <span class="text-gray-400">/ 140</span>
          <span class="mx-2 text-gray-300">•</span>
          Counted: <span class="font-semibold text-gray-800" x-text="Number(cappedTotal()).toFixed(2)"></span>
        </p>
      </div>

      <button type="button"
              @click="userOverride = true; open = !open"
              class="px-3 py-1.5 rounded-lg border text-xs font-medium text-gray-700 hover:bg-gray-50">
        <span x-text="open ? 'Hide details' : 'Show details'"></span>
      </button>
    </div>

    <div x-show="open" x-collapse class="px-5 pb-4">
      <p class="text-xs text-gray-500">
        System-suggested points (subject to validation). Limits are applied for guidance only.
      </p>

      <div class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div class="rounded-xl border p-4">
          <p class="text-xs text-gray-500">Total (Raw)</p>
          <p class="text-xl font-semibold text-gray-800">
            <span x-text="Number(rawTotal()).toFixed(2)"></span>
            <span class="text-sm font-medium text-gray-400">/ 140</span>
          </p>
          <p class="mt-1 text-xs text-gray-500">
            Counted (capped): <span class="font-medium text-gray-700" x-text="Number(cappedTotal()).toFixed(2)"></span>
          </p>
        </div>

        <div class="rounded-xl border p-4">
          <div class="flex items-center justify-between">
            <p class="text-xs text-gray-500">A. Academic Degree Earned</p>
            <span class="text-[11px] px-2 py-0.5 rounded-full border bg-gray-50 text-gray-600">Max 140</span>
          </div>
          <p class="text-lg font-semibold text-gray-800 mt-1">
            <span x-text="Number(rawA()).toFixed(2)"></span>
            <span class="text-sm font-medium text-gray-400">/ 140</span>
          </p>
          <p class="text-xs text-gray-500">
            Counted: <span class="font-medium text-gray-700" x-text="Number(cap(rawA(),140)).toFixed(2)"></span>
          </p>

          <div class="mt-2 text-xs text-gray-500 space-y-1">
            <div class="flex items-center justify-between">
              <span>A8 Exams cap</span>
              <span>
                <span class="font-medium text-gray-700" x-text="Number(rawA8()).toFixed(2)"></span>
                <span class="text-gray-400">/ 15</span>
              </span>
            </div>
            <div class="flex items-center justify-between">
              <span>A9 Certifications cap</span>
              <span>
                <span class="font-medium text-gray-700" x-text="Number(rawA9()).toFixed(2)"></span>
                <span class="text-gray-400">/ 10</span>
              </span>
            </div>
          </div>
        </div>

        <div class="rounded-xl border p-4 space-y-3">
          <div>
            <div class="flex items-center justify-between">
              <p class="text-xs text-gray-500">B. Specialized Training</p>
              <span class="text-[11px] px-2 py-0.5 rounded-full border bg-gray-50 text-gray-600">Max 20</span>
            </div>
            <p class="text-lg font-semibold text-gray-800 mt-1">
              <span x-text="Number(rawB()).toFixed(2)"></span>
              <span class="text-sm font-medium text-gray-400">/ 20</span>
            </p>
            <p class="text-xs text-gray-500">
              Counted: <span class="font-medium text-gray-700" x-text="Number(cap(rawB(),20)).toFixed(2)"></span>
            </p>
          </div>

          <div class="border-t pt-3">
            <div class="flex items-center justify-between">
              <p class="text-xs text-gray-500">C. Seminars/Workshops</p>
              <span class="text-[11px] px-2 py-0.5 rounded-full border bg-gray-50 text-gray-600">Max 20</span>
            </div>
            <p class="text-lg font-semibold text-gray-800 mt-1">
              <span x-text="Number(rawC()).toFixed(2)"></span>
              <span class="text-sm font-medium text-gray-400">/ 20</span>
            </p>
            <p class="text-xs text-gray-500">
              Counted: <span class="font-medium text-gray-700" x-text="Number(cap(rawC(),20)).toFixed(2)"></span>
            </p>
          </div>
        </div>
      </div>

      <template x-if="Number(rawTotal()) > 140">
        <p class="mt-3 text-xs text-red-600">
          Your raw total exceeds the 140-point limit. Excess points will not be counted.
        </p>
      </template>
    </div>
  </div>
</div>

{{-- ======================================================
A. ACADEMIC DEGREE EARNED
====================================================== --}}
<div class="bg-white rounded-2xl shadow-card border">

<div class="px-6 py-4 border-b">
<h3 class="text-lg font-semibold text-gray-800">A. Academic Degree Earned</h3>
<p class="text-sm text-gray-500">
Instruction: Kindly check the corresponding points in the blanks and write the Final Rating for each Academic Qualifications.
</p>
</div>

<div class="p-6 space-y-10">

{{-- A1 BACHELOR --}}
<div>
  <h4 class="font-medium text-gray-800 mb-2">A1. Bachelor’s Degree</h4>
  <div class="space-y-2 text-sm">
    <label class="flex gap-2">
      <input @change="refreshA1()" type="radio" name="section1[a1][honors]" value="summa">
      Summa Cum Laude (3 pts)
    </label>
    <label class="flex gap-2">
      <input @change="refreshA1()" type="radio" name="section1[a1][honors]" value="magna">
      Magna Cum Laude (2 pts)
    </label>
    <label class="flex gap-2">
      <input @change="refreshA1()" type="radio" name="section1[a1][honors]" value="cum">
      Cum Laude (1 pt)
    </label>
    <label class="flex gap-2">
      <input @change="refreshA1()" type="radio" name="section1[a1][honors]" value="none">
      None
    </label>
  </div>
</div>

@php
$tables = [
'A2. For every additional bachelor’s degree' => [
  'key' => 'a2',
  'cols' => ['Degree','Category','Pts','Evidence'],
  'placeholder' => ['Degree' => 'e.g., BSIT',]
],
'A3. Master’s degree (including LLB-bar passer)' => [
  'key' => 'a3',
  'cols' => ['Degree','Category','Thesis','Pts','Evidence'],
  'placeholder' => ['Degree' => 'e.g., MAEd / LLB',]
],
'A4. Master’s degree units (9-unit blocks)' => [
  'key' => 'a4',
  'cols' => ['Category','No. of Blocks','Pts','Evidence'],
],
'A5. For every additional Master’s degree' => [
  'key' => 'a5',
  'cols' => ['Degree','Category','Pts','Evidence'],
  'placeholder' => ['Degree' => 'e.g., MBA',]
],
'A6. Doctoral degree units (9-unit blocks)' => [
  'key' => 'a6',
  'cols' => ['Category','No. of Blocks','Pts','Evidence'],
],
'A7. Doctor’s degree' => [
  'key' => 'a7',
  'cols' => ['Degree','Category','Pts','Evidence'],
  'placeholder' => ['Degree' => 'e.g., PhD / EdD',]
],
'A8. Qualifying Government Examinations (cap 15; max 5 per exam)' => [
  'key' => 'a8',
  'cols' => ['Exam','Relation','Pts','Evidence'],
  'placeholder' => ['Exam' => 'e.g., Civil Service / LET',]
],
'A9. International / National Certifications (cap 10)' => [
  'key' => 'a9',
  'cols' => ['Certification','Level','Pts','Evidence'],
  'placeholder' => ['Certification' => 'e.g., Cisco / Microsoft',]
],
];
@endphp

@foreach($tables as $title => $cfg)
<div class="space-y-2">
  <h4 class="font-medium text-gray-800">{{ $title }}</h4>

  <p x-show="{{ $cfg['key'] }}.length === 0" class="text-sm italic text-gray-500">No entry added.</p>

  <table x-show="{{ $cfg['key'] }}.length > 0" class="w-full text-sm border rounded-lg overflow-hidden">
    <thead class="bg-gray-50">
      <tr>
        @foreach($cfg['cols'] as $col)
          <th class="px-3 py-2 text-left">{{ $col }}</th>
        @endforeach
        <th class="px-3 py-2"></th>
      </tr>
    </thead>
    <tbody class="divide-y">
      <template x-for="(row,i) in {{ $cfg['key'] }}" :key="i">
        <tr>
          @foreach($cfg['cols'] as $col)
            <td class="px-3 py-2 align-top">
              @if($col === 'Pts')
                <span class="text-gray-800 font-semibold" x-text="Number(ptsA('{{ $cfg['key'] }}', row)).toFixed(2)"></span>
                <span class="text-gray-400 text-xs"> (Auto)</span>

              @elseif($col === 'Evidence')
                <select x-model="row.evidence"
                        :name="`section1[{{ $cfg['key'] }}][${i}][evidence]`"
                        class="rounded border-gray-300 w-full">
                  <option value="" disabled selected>Select evidence (required)</option>
                  <template x-for="(e,index) in evidenceFiles" :key="index">
                    <option :value="index" x-text="e.name"></option>
                  </template>
                </select>

              @elseif($col === 'Category')
                {{-- ✅ paper-based categories per item --}}
                <template x-if="['a4','a6'].includes('{{ $cfg['key'] }}')">
                  <select x-model="row.category"
                          :name="`section1[{{ $cfg['key'] }}][${i}][category]`"
                          class="rounded border-gray-300 w-full">
                    <option value="" disabled selected>Select category</option>
                    <option value="specialization">Field of specialization / allied field</option>
                    <option value="other">Other fields</option>
                  </select>
                </template>

                <template x-if="!['a4','a6'].includes('{{ $cfg['key'] }}')">
                  <select x-model="row.category"
                          :name="`section1[{{ $cfg['key'] }}][${i}][category]`"
                          class="rounded border-gray-300 w-full">
                    <option value="" disabled selected>Select category</option>
                    <option value="teaching">Teaching field</option>
                    <option value="not_teaching">Not in the teaching field</option>
                  </select>
                </template>

              @elseif($col === 'Thesis')
                <select x-model="row.thesis"
                        :name="`section1[{{ $cfg['key'] }}][${i}][thesis]`"
                        class="rounded border-gray-300 w-full">
                  <option value="" disabled selected>Select thesis option</option>
                  <option value="with">With thesis</option>
                  <option value="without">Without thesis</option>
                </select>

              @elseif($col === 'Relation')
                <select x-model="row.relation"
                        :name="`section1[{{ $cfg['key'] }}][${i}][relation]`"
                        class="rounded border-gray-300 w-full">
                  <option value="" disabled selected>Select relation</option>
                  <option value="direct">Directly related / required by teaching area</option>
                  <option value="not_direct">Not directly related / required</option>
                </select>

              @elseif($col === 'Level')
                <select x-model="row.level"
                        :name="`section1[{{ $cfg['key'] }}][${i}][level]`"
                        class="rounded border-gray-300 w-full">
                  <option value="" disabled selected>Select level</option>
                  <option value="international">International</option>
                  <option value="national">National</option>
                </select>

              @elseif($col === 'No. of Blocks')
                <input type="number" min="1" step="1"
                       x-model.number="row.blocks"
                       :name="`section1[{{ $cfg['key'] }}][${i}][blocks]`"
                       class="w-full rounded border-gray-300"
                       placeholder="Enter blocks">

              @else
                <input x-model="row.text"
                       :name="`section1[{{ $cfg['key'] }}][${i}][text]`"
                       class="w-full rounded border-gray-300"
                       :placeholder="placeholders['{{ $cfg['key'] }}']['{{ $col }}'] || 'Enter value'">
              @endif
            </td>
          @endforeach

          <td class="px-3 py-2 text-right">
            <button type="button"
                    @click="{{ $cfg['key'] }}.splice(i,1)"
                    class="text-red-500 text-xs hover:underline">
              Remove
            </button>
          </td>
        </tr>
      </template>
    </tbody>
  </table>

  <button type="button"
          @click="addRow('{{ $cfg['key'] }}')"
          class="text-sm text-bu hover:underline">
    + Add entry
  </button>
</div>
@endforeach

</div>
</div>

{{-- ======================================================
B. ADVANCED / SPECIALIZED TRAINING (paper: fixed options)
====================================================== --}}
<div class="bg-white rounded-2xl shadow-card border">
  <div class="px-6 py-4 border-b">
    <h3 class="text-lg font-semibold text-gray-800">B. Advanced or Specialized Training (non-degree)</h3>
    <p class="text-sm text-gray-500">
      Within the last three years only. Supported by evidences. Max 20 pts.
    </p>
  </div>

  <div class="p-6 space-y-2">
    <p x-show="b.length === 0" class="text-sm italic text-gray-500">No training added.</p>

    <table x-show="b.length > 0" class="w-full text-sm border rounded-lg overflow-hidden">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-3 py-2 text-left">Training Title</th>
          <th class="px-3 py-2 text-left">Hours Category</th>
          <th class="px-3 py-2 text-left">Pts</th>
          <th class="px-3 py-2 text-left">Evidence</th>
          <th class="px-3 py-2"></th>
        </tr>
      </thead>
      <tbody class="divide-y">
        <template x-for="(row,i) in b" :key="i">
          <tr>
            <td class="px-3 py-2">
              <input x-model="row.title"
                     :name="`section1[b][${i}][title]`"
                     class="w-full rounded border-gray-300"
                     placeholder="e.g., Advanced Training Title">
            </td>

            <td class="px-3 py-2">
              <select x-model="row.hours"
                      :name="`section1[b][${i}][hours]`"
                      class="rounded border-gray-300 w-full">
                <option value="" disabled selected>Select hours (required)</option>
                <option value="120">At least 120 hours (15 pts)</option>
                <option value="80">At least 80 hours (10 pts)</option>
                <option value="50">At least 50 hours (6 pts)</option>
                <option value="20">At least 20 hours (4 pts)</option>
              </select>
            </td>

            <td class="px-3 py-2">
              <span class="text-gray-800 font-semibold" x-text="Number(ptsB(row)).toFixed(2)"></span>
              <span class="text-gray-400 text-xs"> (Auto)</span>
            </td>

            <td class="px-3 py-2">
              <select x-model="row.evidence"
                      :name="`section1[b][${i}][evidence]`"
                      class="rounded border-gray-300 w-full">
                <option value="" disabled selected>Select evidence (required)</option>
                <template x-for="(e,index) in evidenceFiles" :key="index">
                  <option :value="index" x-text="e.name"></option>
                </template>
              </select>
            </td>

            <td class="px-3 py-2 text-right">
              <button type="button" @click="b.splice(i,1)" class="text-red-500 text-xs hover:underline">Remove</button>
            </td>
          </tr>
        </template>
      </tbody>
    </table>

    <button type="button"
            @click="b.push({ title:'', hours:'', evidence:'' })"
            class="text-sm text-bu hover:underline">
      + Add training
    </button>
  </div>
</div>

{{-- ======================================================
C. SEMINARS / WORKSHOPS / CONFERENCES
✅ Auto-calculated using PAPER ranges (uses MIN of range)
====================================================== --}}
<div class="bg-white rounded-2xl shadow-card border">
  <div class="px-6 py-4 border-b">
    <h3 class="text-lg font-semibold text-gray-800">C. Attendance at Workshops / Seminars / Conferences</h3>
    <p class="text-sm text-gray-500">
      Within the last three years only. Supported by evidences. Max 20 pts.
    </p>
  </div>

  <div class="p-6 space-y-2">
    <p x-show="c.length === 0" class="text-sm italic text-gray-500">No activity added.</p>

    <table x-show="c.length > 0" class="w-full text-sm border rounded-lg overflow-hidden">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-3 py-2 text-left">Title</th>
          <th class="px-3 py-2 text-left">Role</th>
          <th class="px-3 py-2 text-left">Level</th>
          <th class="px-3 py-2 text-left">Points (Auto)</th>
          <th class="px-3 py-2 text-left">Evidence</th>
          <th class="px-3 py-2"></th>
        </tr>
      </thead>

      <tbody class="divide-y">
        <template x-for="(row,i) in c" :key="i">
          <tr>
            <td class="px-3 py-2">
              <input x-model="row.title"
                     :name="`section1[c][${i}][title]`"
                     class="w-full rounded border-gray-300"
                     placeholder="e.g., Seminar Title">
            </td>

            <td class="px-3 py-2">
              <select x-model="row.role"
                      :name="`section1[c][${i}][role]`"
                      class="rounded border-gray-300 w-full">
                <option value="" disabled selected>Select role (required)</option>
                <option value="speaker">Speaker</option>
                <option value="resource">Resource Person / Consultant</option>
                <option value="participant">Participant / Delegate</option>
              </select>
            </td>

            <td class="px-3 py-2">
              <select x-model="row.level"
                      :name="`section1[c][${i}][level]`"
                      class="rounded border-gray-300 w-full">
                <option value="" disabled selected>Select level (required)</option>
                <option value="international">International</option>
                <option value="national">National</option>
                <option value="regional">Regional</option>
                <option value="provincial">Provincial</option>
                <option value="municipal">Municipal</option>
                <option value="school">School</option>
              </select>
            </td>

            <td class="px-3 py-2">
              <div class="font-semibold text-gray-800">
                <span x-text="ptsC(row).toFixed(2)"></span>
                <span class="text-xs text-gray-400">(Auto)</span>
              </div>

              {{-- show range hint (paper-based) --}}
              <div class="text-xs text-gray-500 mt-1" x-text="rangeHintC(row)"></div>

              {{-- hidden field to submit computed points --}}
              <input type="hidden"
                     :name="`section1[c][${i}][points]`"
                     :value="ptsC(row)">
            </td>

            <td class="px-3 py-2">
              <select x-model="row.evidence"
                      :name="`section1[c][${i}][evidence]`"
                      class="rounded border-gray-300 w-full">
                <option value="" disabled selected>Select evidence (required)</option>
                <template x-for="(e,index) in evidenceFiles" :key="index">
                  <option :value="index" x-text="e.name"></option>
                </template>
              </select>
            </td>

            <td class="px-3 py-2 text-right">
              <button type="button" @click="c.splice(i,1)" class="text-red-500 text-xs hover:underline">Remove</button>
            </td>
          </tr>
        </template>
      </tbody>
    </table>

    <button type="button"
            @click="c.push({ title:'', role:'', level:'', evidence:'' })"
            class="text-sm text-bu hover:underline">
      + Add activity
    </button>
  </div>
</div>

{{-- ======================================================
SECTION I EVIDENCE
====================================================== --}}
<div class="bg-white rounded-2xl shadow-card border">
  <div class="px-6 py-4 border-b">
    <h3 class="text-lg font-semibold text-gray-800">Section I Evidence Upload</h3>
    <p class="text-sm text-gray-500">Upload once. Evidence will be validated externally.</p>
  </div>
  <div class="p-6">
    <input type="file" name="section1[evidence_files][]" multiple
           @change="handleEvidence($event)"
           class="w-full text-sm">
  </div>
</div>

{{-- ACTIONS --}}
<div class="flex justify-end gap-4">
  <button type="submit" name="action" value="draft"
          class="px-6 py-2.5 rounded-xl border">
    Save Draft
  </button>
  <button type="submit" name="action" value="submit"
          class="px-6 py-2.5 rounded-xl bg-bu text-white">
    Submit Section I
  </button>
</div>

</div>
</div>
</form>

<script>
function sectionOne() {
  return {
    evidenceFiles: [],

    // A arrays
    a2: [], a3: [], a4: [], a5: [], a6: [], a7: [], a8: [], a9: [],
    // B/C arrays
    b: [], c: [],

    // placeholders for text inputs
    placeholders: {
      a2: { Degree: 'e.g., BSIT' },
      a3: { Degree: 'e.g., MAEd / LLB' },
      a5: { Degree: 'e.g., MBA' },
      a7: { Degree: 'e.g., PhD / EdD' },
      a8: { Exam: 'e.g., LET / Civil Service' },
      a9: { Certification: 'e.g., Cisco / Microsoft' },
    },

    handleEvidence(e) {
      this.evidenceFiles = Array.from(e.target.files || []);
    },

    refreshA1() {},

    addRow(key) {
      const base = { text:'', category:'', thesis:'', relation:'', level:'', blocks:'', evidence:'' };
      if (key === 'a4' || key === 'a6') base.blocks = 1;
      this[key].push({ ...base });
    },

    cap(v, max) {
      v = Number(v || 0);
      return v > max ? max : v;
    },

    // ===== PAPER-BASED A POINTS (fixed) =====
    ptsA(key, row) {
      const cat = (row?.category || '');
      const thesis = (row?.thesis || '');
      const rel = (row?.relation || '');
      const lvl = (row?.level || '');
      const blocks = Number(row?.blocks || 0);

      // A2 additional bachelor
      if (key === 'a2') {
        if (cat === 'teaching') return 10;
        if (cat === 'not_teaching') return 5;
        return 0;
      }

      // A3 masters
      if (key === 'a3') {
        if (cat === 'teaching' && thesis === 'with') return 100;
        if (cat === 'teaching' && thesis === 'without') return 90;
        if (cat === 'not_teaching' && thesis === 'with') return 80;
        if (cat === 'not_teaching' && thesis === 'without') return 70;
        return 0;
      }

      // A4 masters units (9-unit blocks)
      if (key === 'a4') {
        if (cat === 'specialization') return blocks * 4;
        if (cat === 'other') return blocks * 3;
        return 0;
      }

      // A5 additional masters
      if (key === 'a5') {
        if (cat === 'teaching') return 15;
        if (cat === 'not_teaching') return 10;
        return 0;
      }

      // A6 doctoral units (9-unit blocks)
      if (key === 'a6') {
        if (cat === 'specialization') return blocks * 5;
        if (cat === 'other') return blocks * 4;
        return 0;
      }

      // A7 doctorate degree
      if (key === 'a7') {
        if (cat === 'teaching') return 140;
        if (cat === 'not_teaching') return 120;
        return 0;
      }

      // A8 gov exams (cap 15 total; cap 5 per exam rule is handled below)
      if (key === 'a8') {
        if (rel === 'direct') return 10;
        if (rel === 'not_direct') return 5;
        return 0;
      }

      // A9 certifications (cap 10 total)
      if (key === 'a9') {
        if (lvl === 'international') return 5;
        if (lvl === 'national') return 3;
        return 0;
      }

      return 0;
    },

    a1Pts() {
      const el = document.querySelector('input[name="section1[a1][honors]"]:checked');
      const v = el ? el.value : '';
      if (v === 'summa') return 3;
      if (v === 'magna') return 2;
      if (v === 'cum') return 1;
      return 0;
    },

    rawA8() {
      // paper: "not to exceed 5 pts per examination"
      // our options are 10 or 5, so only 5 is within that cap;
      // BUT you pasted official table includes 10 and 5.
      // We'll enforce: if user chose "direct"(10), still allow, but per-exam cap rule isn't consistent with 10.
      // To avoid wrong adding: we cap each exam contribution at 5.
      return (this.a8 || []).reduce((t, r) => t + Math.min(this.ptsA('a8', r), 5), 0);
    },

    rawA9() {
      return (this.a9 || []).reduce((t, r) => t + this.ptsA('a9', r), 0);
    },

    rawA() {
      const a1 = this.a1Pts();
      const a2 = (this.a2 || []).reduce((t, r) => t + this.ptsA('a2', r), 0);
      const a3 = (this.a3 || []).reduce((t, r) => t + this.ptsA('a3', r), 0);
      const a4 = (this.a4 || []).reduce((t, r) => t + this.ptsA('a4', r), 0);
      const a5 = (this.a5 || []).reduce((t, r) => t + this.ptsA('a5', r), 0);
      const a6 = (this.a6 || []).reduce((t, r) => t + this.ptsA('a6', r), 0);
      const a7 = (this.a7 || []).reduce((t, r) => t + this.ptsA('a7', r), 0);

      // caps
      const a8 = this.cap(this.rawA8(), 15);
      const a9 = this.cap(this.rawA9(), 10);

      return a1 + a2 + a3 + a4 + a5 + a6 + a7 + a8 + a9;
    },

    // ===== PAPER-BASED B POINTS (fixed) =====
    ptsB(row) {
      const h = String(row?.hours || '');
      if (h === '120') return 15;
      if (h === '80') return 10;
      if (h === '50') return 6;
      if (h === '20') return 4;
      return 0;
    },

    rawB() {
      return (this.b || []).reduce((t, r) => t + this.ptsB(r), 0);
    },

    // ===== PAPER-BASED C POINTS (ranges; user must pick exact) =====
    pointOptionsForC(row) {
      const role = row?.role;
      const level = row?.level;

      if (!role || !level) return [];

      const ranges = {
        speaker: {
          international: [13,15],
          national: [11,12],
          regional: [9,10],
          provincial: [7,8],
          municipal: [4,6],
          school: [1,3],
        },
        resource: {
          international: [11,12],
          national: [9,10],
          regional: [7,8],
          provincial: [5,6],
          municipal: [3,4],
          school: [1,2],
        },
        participant: {
          international: [9,10],
          national: [7,8],
          regional: [5,6],
          provincial: [3,4],
          municipal: [2,2],
          school: [1,1],
        },
      };

      const r = ranges?.[role]?.[level];
      if (!r) return [];

      const [min,max] = r;
      const opts = [];
      for (let p = min; p <= max; p++) opts.push({ value: p, label: `${p} pt${p>1?'s':''}` });
      return opts;
    },

// ✅ REPLACE your current ptsC(row) with this AUTO (paper-min-range) version
ptsC(row) {
  const role = (row?.role || '').trim();
  const level = (row?.level || '').trim();
  if (!role || !level) return 0;

  // Paper-based MIN values (conservative, avoids over-scoring)
  const minMap = {
    speaker:     { international: 13, national: 11, regional: 9, provincial: 7, municipal: 4, school: 1 },
    resource:    { international: 11, national: 9,  regional: 7, provincial: 5, municipal: 3, school: 1 },
    participant: { international: 9,  national: 7,  regional: 5, provincial: 3, municipal: 2, school: 1 },
  };

  return Number(minMap?.[role]?.[level] || 0);
},

// ✅ KEEP rawC() but now it will sum the AUTO points above
rawC() {
  return (this.c || []).reduce((t, r) => t + this.ptsC(r), 0);
},

// ✅ KEEP these (no change needed)
rawTotal() {
  return this.rawA() + this.rawB() + this.rawC();
},

cappedTotal() {
  return this.cap(
    this.cap(this.rawA(), 140) +
    this.cap(this.rawB(), 20) +
    this.cap(this.rawC(), 20),
    140
  );
},
  }
}
</script>

<script defer src="https://unpkg.com/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</x-app-layout>
