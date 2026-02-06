{{-- resources/views/reclassification/section5.blade.php --}}
<x-app-layout>
<x-slot name="header">
    <div class="flex flex-col gap-1">
        <h2 class="text-2xl font-semibold text-gray-800">
            Reclassification – Section V
        </h2>
        <p class="text-sm text-gray-500">
            Professional & Community Leadership Service (Max 30 pts / 7.5%)
        </p>
    </div>
</x-slot>

<form method="POST" enctype="multipart/form-data">
@csrf

<div x-data="sectionFive()" class="py-12 bg-bu-muted min-h-screen">
  <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

    {{-- =======================
    STICKY HEADER (Score + Caps)
    ======================== --}}
    <div
      x-data="{ open:true, stuck:false, userOverride:false }"
      x-init="
        const onScroll = () => {
          const nowStuck = window.scrollY > 140;
          if (!stuck && nowStuck) { stuck = true; if (!userOverride) open = false; return; }
          if (stuck && !nowStuck) { stuck = false; if (!userOverride) open = true; return; }
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
                Section V Summary
              </h3>

              <template x-if="Number(rawTotal()) <= 30">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium bg-green-50 text-green-700 border border-green-200">
                  Within limit
                </span>
              </template>
              <template x-if="Number(rawTotal()) > 30">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium bg-red-50 text-red-700 border border-red-200">
                  Over limit
                </span>
              </template>
            </div>

            <p class="text-xs text-gray-600 mt-1">
              Raw: <span class="font-semibold text-gray-800" x-text="rawTotal()"></span>
              <span class="text-gray-400">/ 30</span>
              <span class="mx-2 text-gray-300">•</span>
              Counted (capped): <span class="font-semibold text-gray-800" x-text="cappedTotal()"></span>
            </p>
          </div>

          <button
            type="button"
            @click="userOverride = true; open = !open"
            class="px-3 py-1.5 rounded-lg border text-xs font-medium text-gray-700 hover:bg-gray-50"
          >
            <span x-text="open ? 'Hide details' : 'Show details'"></span>
          </button>
        </div>

        <div x-show="open" x-collapse class="px-5 pb-4">
          <p class="text-xs text-gray-500">
            Select exact options based on the paper form to avoid miscalculation. Evidence is uploaded once and referenced per row.
          </p>

          <div class="mt-3 grid grid-cols-1 sm:grid-cols-5 gap-3">
            <div class="rounded-xl border p-4">
              <p class="text-xs text-gray-500">A (cap 5)</p>
              <p class="text-xl font-semibold text-gray-800"><span x-text="sumA_capped()"></span></p>
              <p class="text-xs text-gray-500 mt-1">Raw: <span x-text="sumA_raw()"></span></p>
            </div>

            <div class="rounded-xl border p-4">
              <p class="text-xs text-gray-500">B (cap 10)</p>
              <p class="text-xl font-semibold text-gray-800"><span x-text="sumB_capped()"></span></p>
              <p class="text-xs text-gray-500 mt-1">Raw: <span x-text="sumB_raw()"></span></p>
            </div>

            <div class="rounded-xl border p-4">
              <p class="text-xs text-gray-500">C (cap 15)</p>
              <p class="text-xl font-semibold text-gray-800"><span x-text="sumC_capped()"></span></p>
              <p class="text-xs text-gray-500 mt-1">Raw: <span x-text="sumC_raw()"></span></p>
            </div>

            <div class="rounded-xl border p-4">
              <p class="text-xs text-gray-500">D (cap 10)</p>
              <p class="text-xl font-semibold text-gray-800"><span x-text="sumD_capped()"></span></p>
              <p class="text-xs text-gray-500 mt-1">Raw: <span x-text="sumD_raw()"></span></p>
            </div>

            <div class="rounded-xl border p-4">
              <p class="text-xs text-gray-500">Previous (1/3)</p>
              <p class="text-xl font-semibold text-gray-800"><span x-text="prevThird()"></span></p>
              <p class="text-xs text-gray-500 mt-1">Input: <span x-text="Number(previous||0).toFixed(2)"></span></p>
            </div>
          </div>

          <template x-if="Number(rawTotal()) > 30">
            <p class="mt-3 text-xs text-red-600">
              Your raw total exceeds the 30-point limit. Excess points will not be counted.
            </p>
          </template>
        </div>
      </div>
    </div>

    {{-- ===============================
    SECTION INTRO
    =============================== --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-200">
      <div class="p-6">
        <p class="text-sm text-gray-600">
          Professional and community leadership service within the last three (3) years.
          Section total is capped at 30 pts, plus 1/3 of previous reclassification (still capped).
        </p>
      </div>
    </div>

    {{-- =====================================================
    A. AWARDS AND CITATION (cap 5)
    ===================================================== --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-200">
      <div class="px-6 py-4 border-b">
        <h3 class="text-lg font-semibold text-gray-800">
          A. Awards and Citation <span class="text-sm text-gray-500">(Cap 5 pts)</span>
        </h3>
        <p class="text-xs text-gray-500 mt-1">
          Paper options: Professional (Intl=5, Natl=4, Reg=3, Local=2, School=1) • Civic/Social same • Scholarship: Full=5, Partial=3–4, Observation/Travel=1–2
        </p>
      </div>

      <div class="p-6 space-y-3">
        <p x-show="aRows.length === 0" class="text-sm italic text-gray-500">No entry added.</p>

        <div class="overflow-x-auto">
          <table x-show="aRows.length" class="w-full text-sm border rounded-lg overflow-hidden">
            <thead class="bg-gray-50">
              <tr>
                <th class="p-2 text-left">Award / Citation</th>
                <th class="p-2 text-left">Category</th>
                <th class="p-2 text-left">Level / Type</th>
                <th class="p-2 text-left">Pts</th>
                <th class="p-2 text-left">Evidence</th>
                <th class="p-2"></th>
              </tr>
            </thead>
            <tbody>
              <template x-for="(row,i) in aRows" :key="i">
                <tr class="border-t">
                  <td class="p-2">
                    <input x-model="row.title"
                           :name="`section5[a][${i}][title]`"
                           class="w-full rounded border-gray-300"
                           placeholder="e.g., Best Paper Award (include year)">
                  </td>

                  <td class="p-2">
                    <select x-model="row.kind"
                            :name="`section5[a][${i}][kind]`"
                            class="rounded border-gray-300 w-full">
                      <option value="" disabled>Select category (required)</option>
                      <option value="professional">Professional</option>
                      <option value="civic">Civic / Social</option>
                      <option value="scholarship">Scholarship / Fellowship Grant</option>
                    </select>
                  </td>

                  <td class="p-2">
                    {{-- ✅ force blank placeholders so user must choose correctly --}}
                    <template x-if="row.kind !== 'scholarship'">
                      <select x-model="row.level"
                              :name="`section5[a][${i}][level]`"
                              class="rounded border-gray-300 w-full">
                        <option value="" disabled>Select level (required)</option>
                        <option value="international">International — 5 pts</option>
                        <option value="national">National — 4 pts</option>
                        <option value="regional">Regional — 3 pts</option>
                        <option value="local">Local — 2 pts</option>
                        <option value="school">School — 1 pt</option>
                      </select>
                    </template>

                    <template x-if="row.kind === 'scholarship'">
                      <select x-model="row.grant"
                              :name="`section5[a][${i}][grant]`"
                              class="rounded border-gray-300 w-full">
                        <option value="" disabled>Select grant type (required)</option>
                        <option value="full">Full Grant — 5 pts</option>
                        <option value="partial_4">Partial Grant — 4 pts</option>
                        <option value="partial_3">Partial Grant — 3 pts</option>
                        <option value="travel_2">Observation / Travel Grant — 2 pts</option>
                        <option value="travel_1">Observation / Travel Grant — 1 pt</option>
                      </select>
                    </template>
                  </td>

                  <td class="p-2 text-gray-700">
                    <span x-text="ptsA(row)"></span>
                    <span class="text-xs text-gray-400">(Auto)</span>
                  </td>

                  <td class="p-2">
                    <select x-model="row.evidence"
                            :name="`section5[a][${i}][evidence]`"
                            class="rounded border-gray-300 w-full">
                      <option value="" disabled>Select evidence (required)</option>
                      <template x-for="(e,idx) in evidenceFiles" :key="idx">
                        <option :value="idx" x-text="e.name"></option>
                      </template>
                    </select>
                  </td>

                  <td class="p-2 text-right">
                    <button type="button" @click="aRows.splice(i,1)" class="text-red-500 text-xs hover:underline">Remove</button>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>

        <button type="button"
                @click="aRows.push({ title:'', kind:'', level:'', grant:'', evidence:'' })"
                class="text-sm text-bu hover:underline">
          + Add award / citation
        </button>

        <p class="text-xs text-gray-500">Counted points for A are capped at 5.</p>
      </div>
    </div>

    {{-- =====================================================
    B. MEMBERSHIP & LEADERSHIP (cap 10)
    ===================================================== --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-200">
      <div class="px-6 py-4 border-b">
        <h3 class="text-lg font-semibold text-gray-800">
          B. Membership & Leadership in Professional Organizations <span class="text-sm text-gray-500">(Cap 10 pts)</span>
        </h3>
        <p class="text-xs text-gray-500 mt-1">
          Paper options: Officer/Board (10/8/6/4/2) • Committee Chairman (5/4/3/2/1) • Committee Member (4/3/2/1.5/1) • Member (3/2.5/2/1/0.5)
        </p>
      </div>

      <div class="p-6 space-y-3">
        <p x-show="bRows.length === 0" class="text-sm italic text-gray-500">No entry added.</p>

        <div class="overflow-x-auto">
          <table x-show="bRows.length" class="w-full text-sm border rounded-lg overflow-hidden">
            <thead class="bg-gray-50">
              <tr>
                <th class="p-2 text-left">Organization</th>
                <th class="p-2 text-left">Role</th>
                <th class="p-2 text-left">Level</th>
                <th class="p-2 text-left">Pts</th>
                <th class="p-2 text-left">Evidence</th>
                <th class="p-2"></th>
              </tr>
            </thead>
            <tbody>
              <template x-for="(row,i) in bRows" :key="i">
                <tr class="border-t">
                  <td class="p-2">
                    <input x-model="row.org"
                           :name="`section5[b][${i}][org]`"
                           class="w-full rounded border-gray-300"
                           placeholder="e.g., IEEE (include year/s)">
                  </td>

                  <td class="p-2">
                    <select x-model="row.role"
                            :name="`section5[b][${i}][role]`"
                            class="rounded border-gray-300 w-full">
                      <option value="" disabled>Select role (required)</option>
                      <option value="officer">Officer / Board of Directors</option>
                      <option value="chairman">Committee Chairman</option>
                      <option value="member_committee">Committee Member</option>
                      <option value="member">Member</option>
                    </select>
                  </td>

                  <td class="p-2">
                    <select x-model="row.level"
                            :name="`section5[b][${i}][level]`"
                            class="rounded border-gray-300 w-full">
                      <option value="" disabled>Select level (required)</option>
                      <option value="international">International</option>
                      <option value="national">National</option>
                      <option value="regional">Regional</option>
                      <option value="local">Local</option>
                      <option value="school">School</option>
                    </select>
                  </td>

                  <td class="p-2 text-gray-700">
                    <span x-text="ptsB(row)"></span>
                    <span class="text-xs text-gray-400">(Auto)</span>
                  </td>

                  <td class="p-2">
                    <select x-model="row.evidence"
                            :name="`section5[b][${i}][evidence]`"
                            class="rounded border-gray-300 w-full">
                      <option value="" disabled>Select evidence (required)</option>
                      <template x-for="(e,idx) in evidenceFiles" :key="idx">
                        <option :value="idx" x-text="e.name"></option>
                      </template>
                    </select>
                  </td>

                  <td class="p-2 text-right">
                    <button type="button" @click="bRows.splice(i,1)" class="text-red-500 text-xs hover:underline">Remove</button>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>

        <button type="button"
                @click="bRows.push({ org:'', role:'', level:'', evidence:'' })"
                class="text-sm text-bu hover:underline">
          + Add organization role
        </button>

        <p class="text-xs text-gray-500">Counted points for B are capped at 10.</p>
      </div>
    </div>

    {{-- =====================================================
    C. SERVICE TO THE UNIVERSITY (cap 15)
    ===================================================== --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-200">
      <div class="px-6 py-4 border-b">
        <h3 class="text-lg font-semibold text-gray-800">
          C. Service to the University <span class="text-sm text-gray-500">(Cap 15 pts)</span>
        </h3>
        <p class="text-xs text-gray-500 mt-1">
          Paper caps: C1 cap 10 • C2 cap 5 • C3 cap 10 • Overall C cap 15
        </p>
      </div>

      <div class="p-6 space-y-6">

        {{-- C1 Academic committee work (cap 10) --}}
        <div class="rounded-xl border p-4">
          <div class="flex items-start justify-between gap-4">
            <div>
              <p class="font-semibold text-gray-800">C1. Academic committee work <span class="text-xs text-gray-500">(cap 10 pts)</span></p>
              <p class="text-xs text-gray-500 mt-1">Subject area work, accreditation prep, syllabi, curriculum revision, etc.</p>
            </div>
            <div class="text-right">
              <p class="text-xs text-gray-500">Counted</p>
              <p class="text-lg font-semibold text-gray-800" x-text="c1_capped()"></p>
            </div>
          </div>

          <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
              <label class="text-xs text-gray-600">Role</label>
              <select x-model="c1.role" name="section5[c][c1][role]" class="mt-1 w-full rounded border-gray-300">
                <option value="" disabled>Select role (required)</option>
                <option value="overall">Over-all Chairman — 7 pts/activity</option>
                <option value="chairman">Chairman — 5 pts/activity</option>
                <option value="member">Member — 2 pts/activity</option>
              </select>
            </div>
            <div>
              <label class="text-xs text-gray-600">No. of activities</label>
              <input x-model.number="c1.qty" name="section5[c][c1][qty]" type="number" min="0"
                     class="mt-1 w-full rounded border-gray-300" placeholder="Enter number of activities">
            </div>
            <div>
              <label class="text-xs text-gray-600">Evidence</label>
              <select x-model="c1.evidence" name="section5[c][c1][evidence]" class="mt-1 w-full rounded border-gray-300">
                <option value="" disabled>Select evidence (required)</option>
                <template x-for="(e,idx) in evidenceFiles" :key="idx">
                  <option :value="idx" x-text="e.name"></option>
                </template>
              </select>
            </div>
          </div>

          <p class="text-xs text-gray-500 mt-3">Raw points: <span class="font-medium text-gray-700" x-text="c1_raw()"></span></p>
        </div>

        {{-- C2 Co-curricular activities (cap 5) --}}
        <div class="rounded-xl border p-4">
          <div class="flex items-start justify-between gap-4">
            <div>
              <p class="font-semibold text-gray-800">C2. Co-curricular activities <span class="text-xs text-gray-500">(cap 5 pts)</span></p>
            </div>
            <div class="text-right">
              <p class="text-xs text-gray-500">Counted</p>
              <p class="text-lg font-semibold text-gray-800" x-text="c2_capped()"></p>
            </div>
          </div>

          <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
              <label class="text-xs text-gray-600">Type</label>
              <select x-model="c2.type" name="section5[c][c2][type]" class="mt-1 w-full rounded border-gray-300">
                <option value="" disabled>Select type (required)</option>
                <option value="campus">Campus — 5 pts/activity</option>
                <option value="department">Department — 3 pts/activity</option>
                <option value="class">Class — 1 pt/activity</option>
              </select>
            </div>
            <div>
              <label class="text-xs text-gray-600">No. of activities</label>
              <input x-model.number="c2.qty" name="section5[c][c2][qty]" type="number" min="0"
                     class="mt-1 w-full rounded border-gray-300" placeholder="Enter number of activities">
            </div>
            <div>
              <label class="text-xs text-gray-600">Evidence</label>
              <select x-model="c2.evidence" name="section5[c][c2][evidence]" class="mt-1 w-full rounded border-gray-300">
                <option value="" disabled>Select evidence (required)</option>
                <template x-for="(e,idx) in evidenceFiles" :key="idx">
                  <option :value="idx" x-text="e.name"></option>
                </template>
              </select>
            </div>
          </div>

          <p class="text-xs text-gray-500 mt-3">Raw points: <span class="font-medium text-gray-700" x-text="c2_raw()"></span></p>
        </div>

        {{-- C3 University activities (cap 10) --}}
        <div class="rounded-xl border p-4">
          <div class="flex items-start justify-between gap-4">
            <div>
              <p class="font-semibold text-gray-800">C3. University Activities <span class="text-xs text-gray-500">(cap 10 pts)</span></p>
              <p class="text-xs text-gray-500 mt-1">Faculty dev seminars, school programs, graduation, intramurals, etc.</p>
            </div>
            <div class="text-right">
              <p class="text-xs text-gray-500">Counted</p>
              <p class="text-lg font-semibold text-gray-800" x-text="c3_capped()"></p>
            </div>
          </div>

          <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
              <label class="text-xs text-gray-600">Role</label>
              <select x-model="c3.role" name="section5[c][c3][role]" class="mt-1 w-full rounded border-gray-300">
                <option value="" disabled>Select role (required)</option>
                <option value="overall">Over-all Chairman — 5 pts/activity</option>
                <option value="chairman">Committee Chairman — 3 pts/activity</option>
                <option value="member">Member — 1 pt/activity</option>
              </select>
            </div>
            <div>
              <label class="text-xs text-gray-600">No. of activities</label>
              <input x-model.number="c3.qty" name="section5[c][c3][qty]" type="number" min="0"
                     class="mt-1 w-full rounded border-gray-300" placeholder="Enter number of activities">
            </div>
            <div>
              <label class="text-xs text-gray-600">Evidence</label>
              <select x-model="c3.evidence" name="section5[c][c3][evidence]" class="mt-1 w-full rounded border-gray-300">
                <option value="" disabled>Select evidence (required)</option>
                <template x-for="(e,idx) in evidenceFiles" :key="idx">
                  <option :value="idx" x-text="e.name"></option>
                </template>
              </select>
            </div>
          </div>

          <p class="text-xs text-gray-500 mt-3">Raw points: <span class="font-medium text-gray-700" x-text="c3_raw()"></span></p>
          <p class="text-xs text-gray-500 mt-2">Note: Overall C is capped at 15.</p>
        </div>

      </div>
    </div>

    {{-- =====================================================
    D. COMMUNITY PROJECTS (cap 10)
    ===================================================== --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-200">
      <div class="px-6 py-4 border-b">
        <h3 class="text-lg font-semibold text-gray-800">
          D. Active Participation in Community Projects / Programs <span class="text-sm text-gray-500">(Cap 10 pts)</span>
        </h3>
        <p class="text-xs text-gray-500 mt-1">
          Paper: Chairman=5 pts/activity • Coordinator/Trainor=3 • Participant=1
        </p>
      </div>

      <div class="p-6 space-y-3">
        <p x-show="dRows.length === 0" class="text-sm italic text-gray-500">No entry added.</p>

        <div class="overflow-x-auto">
          <table x-show="dRows.length" class="w-full text-sm border rounded-lg overflow-hidden">
            <thead class="bg-gray-50">
              <tr>
                <th class="p-2 text-left">Project / Program</th>
                <th class="p-2 text-left">Role</th>
                <th class="p-2 text-left">No. of activities</th>
                <th class="p-2 text-left">Pts</th>
                <th class="p-2 text-left">Evidence</th>
                <th class="p-2"></th>
              </tr>
            </thead>
            <tbody>
              <template x-for="(row,i) in dRows" :key="i">
                <tr class="border-t">
                  <td class="p-2">
                    <input x-model="row.title"
                           :name="`section5[d][${i}][title]`"
                           class="w-full rounded border-gray-300"
                           placeholder="e.g., Community Outreach Program (include year)">
                  </td>

                  <td class="p-2">
                    <select x-model="row.role"
                            :name="`section5[d][${i}][role]`"
                            class="rounded border-gray-300 w-full">
                      <option value="" disabled>Select role (required)</option>
                      <option value="chairman">Chairman — 5 pts/activity</option>
                      <option value="coordinator">Coordinator / Trainor — 3 pts/activity</option>
                      <option value="participant">Participant — 1 pt/activity</option>
                    </select>
                  </td>

                  <td class="p-2">
                    <input x-model.number="row.qty"
                           :name="`section5[d][${i}][qty]`"
                           type="number" min="0"
                           class="w-40 rounded border-gray-300"
                           placeholder="Enter number of activities">
                  </td>

                  <td class="p-2 text-gray-700">
                    <span x-text="ptsD(row)"></span>
                    <span class="text-xs text-gray-400">(Auto)</span>
                  </td>

                  <td class="p-2">
                    <select x-model="row.evidence"
                            :name="`section5[d][${i}][evidence]`"
                            class="rounded border-gray-300 w-full">
                      <option value="" disabled>Select evidence (required)</option>
                      <template x-for="(e,idx) in evidenceFiles" :key="idx">
                        <option :value="idx" x-text="e.name"></option>
                      </template>
                    </select>
                  </td>

                  <td class="p-2 text-right">
                    <button type="button" @click="dRows.splice(i,1)" class="text-red-500 text-xs hover:underline">Remove</button>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>

        <button type="button"
                @click="dRows.push({ title:'', role:'', qty:'', evidence:'' })"
                class="text-sm text-bu hover:underline">
          + Add community activity
        </button>

        <p class="text-xs text-gray-500">Counted points for D are capped at 10.</p>
      </div>
    </div>

    {{-- ===============================
    PREVIOUS RECLASSIFICATION
    =============================== --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-200">
      <div class="p-6">
        <label class="block text-sm font-medium text-gray-700">
          Points from Previous Reclassification (if applicable)
        </label>
        <input
          x-model.number="previous"
          name="section5[previous_points]"
          type="number" step="0.01"
          class="mt-1 w-56 rounded border-gray-300"
          placeholder="Type previous total points (e.g., 12.00)"
        >
        <p class="text-xs text-gray-500 mt-1">
          System applies 1/3 of this value. Subject to validation.
        </p>
      </div>
    </div>

    {{-- ===============================
    EVIDENCE UPLOAD
    =============================== --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-200">
      <div class="px-6 py-4 border-b">
        <h3 class="text-lg font-semibold text-gray-800">Section V Evidence Upload</h3>
        <p class="text-sm text-gray-500 mt-1">Upload once. Reference a file per row.</p>
      </div>
      <div class="p-6">
        <input type="file"
               name="section5[evidence_files][]"
               multiple
               @change="handleEvidence($event)"
               class="w-full text-sm">
      </div>
    </div>

    {{-- ACTIONS --}}
    <div class="flex justify-end gap-4">
      <button type="submit" name="action" value="draft"
              class="px-6 py-2.5 rounded-xl border border-gray-300">
        Save Draft
      </button>
      <button type="submit" name="action" value="submit"
              class="px-6 py-2.5 rounded-xl bg-bu text-white">
        Submit Section V
      </button>
    </div>

  </div>
</div>

<script>
function sectionFive() {
  return {
    evidenceFiles: [],
    handleEvidence(e) { this.evidenceFiles = Array.from(e.target.files || []); },

    // A/B/D rows start EMPTY with required placeholders
    aRows: [],
    bRows: [],
    dRows: [],

    // C aggregates (start blank for required selections)
    c1: { role:'', qty:'', evidence:'' }, // cap 10
    c2: { type:'', qty:'', evidence:'' }, // cap 5
    c3: { role:'', qty:'', evidence:'' }, // cap 10

    previous: 0,

    // ---------- POINTS ----------
    ptsA(row) {
      if (!row || !row.kind) return Number(0).toFixed(2);

      if (row.kind !== 'scholarship') {
        if (!row.level) return Number(0).toFixed(2);
        const lvl = { international:5, national:4, regional:3, local:2, school:1 };
        return Number(lvl[row.level] || 0).toFixed(2);
      }

      if (!row.grant) return Number(0).toFixed(2);
      const grant = { full:5, partial_4:4, partial_3:3, travel_2:2, travel_1:1 };
      return Number(grant[row.grant] || 0).toFixed(2);
    },

    ptsB(row) {
      if (!row || !row.role || !row.level) return Number(0).toFixed(2);

      const officer = { international:10, national:8, regional:6, local:4, school:2 };
      const chairman = { international:5, national:4, regional:3, local:2, school:1 };
      const committee = { international:4, national:3, regional:2, local:1.5, school:1 };
      const member = { international:3, national:2.5, regional:2, local:1, school:0.5 };

      const mapByRole = {
        officer,
        chairman,
        member_committee: committee,
        member,
      };

      return Number(mapByRole[row.role]?.[row.level] || 0).toFixed(2);
    },

    ptsC1() {
      if (!this.c1.role || this.c1.qty === '' || this.c1.qty === null) return Number(0).toFixed(2);
      const per = { overall:7, chairman:5, member:2 };
      return Number((Number(this.c1.qty || 0) * (per[this.c1.role] || 0))).toFixed(2);
    },
    ptsC2() {
      if (!this.c2.type || this.c2.qty === '' || this.c2.qty === null) return Number(0).toFixed(2);
      const per = { campus:5, department:3, class:1 };
      return Number((Number(this.c2.qty || 0) * (per[this.c2.type] || 0))).toFixed(2);
    },
    ptsC3() {
      if (!this.c3.role || this.c3.qty === '' || this.c3.qty === null) return Number(0).toFixed(2);
      const per = { overall:5, chairman:3, member:1 };
      return Number((Number(this.c3.qty || 0) * (per[this.c3.role] || 0))).toFixed(2);
    },

    ptsD(row) {
      if (!row || !row.role || row.qty === '' || row.qty === null) return Number(0).toFixed(2);
      const per = { chairman:5, coordinator:3, participant:1 };
      return Number(Number(row.qty || 0) * (per[row.role] || 0)).toFixed(2);
    },

    cap(v, max) { v = Number(v || 0); return v > max ? max : v; },

    // ---------- SUMS ----------
    sumA_raw() {
      const t = this.aRows.reduce((s,r)=> s + Number(this.ptsA(r)), 0);
      return t.toFixed(2);
    },
    sumA_capped() { return Number(this.cap(this.sumA_raw(), 5)).toFixed(2); },

    sumB_raw() {
      const t = this.bRows.reduce((s,r)=> s + Number(this.ptsB(r)), 0);
      return t.toFixed(2);
    },
    sumB_capped() { return Number(this.cap(this.sumB_raw(), 10)).toFixed(2); },

    c1_raw() { return this.ptsC1(); },
    c1_capped() { return Number(this.cap(this.c1_raw(), 10)).toFixed(2); },

    c2_raw() { return this.ptsC2(); },
    c2_capped() { return Number(this.cap(this.c2_raw(), 5)).toFixed(2); },

    c3_raw() { return this.ptsC3(); },
    c3_capped() { return Number(this.cap(this.c3_raw(), 10)).toFixed(2); },

    sumC_raw() {
      const t = Number(this.c1_raw()) + Number(this.c2_raw()) + Number(this.c3_raw());
      return t.toFixed(2);
    },
    sumC_capped() {
      const t = Number(this.c1_capped()) + Number(this.c2_capped()) + Number(this.c3_capped());
      return Number(this.cap(t, 15)).toFixed(2);
    },

    sumD_raw() {
      const t = this.dRows.reduce((s,r)=> s + Number(this.ptsD(r)), 0);
      return t.toFixed(2);
    },
    sumD_capped() { return Number(this.cap(this.sumD_raw(), 10)).toFixed(2); },

    subtotal() {
      return (
        Number(this.sumA_capped()) +
        Number(this.sumB_capped()) +
        Number(this.sumC_capped()) +
        Number(this.sumD_capped())
      ).toFixed(2);
    },

    prevThird() { return (Number(this.previous || 0) / 3).toFixed(2); },

    rawTotal() { return (Number(this.subtotal()) + Number(this.prevThird())).toFixed(2); },

    cappedTotal() {
      const raw = Number(this.rawTotal());
      return (raw > 30 ? 30 : raw).toFixed(2);
    },
  }
}
</script>

<script defer src="https://unpkg.com/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

</form>
</x-app-layout>
