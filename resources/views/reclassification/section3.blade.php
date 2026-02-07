{{-- resources/views/reclassification/section3.blade.php --}}
<div class="flex flex-col gap-1 mb-4">
    <h2 class="text-2xl font-semibold text-gray-800">Reclassification - Section III</h2>
    <p class="text-sm text-gray-500">Research Competence & Productivity (Max 70 pts / 17.5%)</p>
</div>
<form method="POST" action="{{ route('reclassification.section.save', 3) }}" enctype="multipart/form-data" data-validate-evidence>
@csrf

<div x-data="sectionThree()" class="py-12 bg-bu-muted min-h-screen">
  <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

    {{-- =======================
    STICKY HEADER (Score + Criteria Met + Caps)
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
                Section III Summary
              </h3>

              <template x-if="criteriaMet() >= 2">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium bg-green-50 text-green-700 border border-green-200">
                  Criteria met
                </span>
              </template>
              <template x-if="criteriaMet() < 2">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium bg-amber-50 text-amber-700 border border-amber-200">
                  Need 2 criteria
                </span>
              </template>

              <template x-if="Number(rawTotal()) <= 70">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium bg-green-50 text-green-700 border border-green-200">
                  Within limit
                </span>
              </template>
              <template x-if="Number(rawTotal()) > 70">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium bg-red-50 text-red-700 border border-red-200">
                  Over limit
                </span>
              </template>
            </div>

            <p class="text-xs text-gray-600 mt-1">
              Criteria met: <span class="font-semibold text-gray-800" x-text="criteriaMet()"></span>
              <span class="text-gray-400">/ 9</span>
              <span class="mx-2 text-gray-300">•</span>
              Raw: <span class="font-semibold text-gray-800" x-text="rawTotal()"></span>
              <span class="text-gray-400">/ 70</span>
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
            Points are system-suggested from your selections. Evidence is uploaded once for the section and referenced per row.
            Subject to validation.
          </p>

          <div class="mt-3 grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div class="rounded-xl border p-4">
              <p class="text-xs text-gray-500">Total (No Previous)</p>
              <p class="text-xl font-semibold text-gray-800">
                <span x-text="subtotal()"></span>
              </p>
              <p class="mt-1 text-xs text-gray-500">
                Book: <span class="font-medium text-gray-700" x-text="sum1()"></span>
                <span class="mx-1 text-gray-300">•</span>
                Workbooks: <span class="font-medium text-gray-700" x-text="sum2()"></span>
                <span class="mx-1 text-gray-300">•</span>
                Articles: <span class="font-medium text-gray-700" x-text="sum4()"></span>
              </p>
            </div>

            <div class="rounded-xl border p-4">
              <p class="text-xs text-gray-500">Previous Reclass (1/3)</p>
              <p class="text-xl font-semibold text-gray-800">
                <span x-text="prevThird()"></span>
              </p>
              <p class="mt-1 text-xs text-gray-500">
                Input: <span class="font-medium text-gray-700" x-text="Number(previous || 0).toFixed(2)"></span>
              </p>
            </div>

            <div class="rounded-xl border p-4">
              <p class="text-xs text-gray-500">Final (Raw)</p>
              <p class="text-xl font-semibold text-gray-800">
                <span x-text="rawTotal()"></span>
                <span class="text-sm font-medium text-gray-400">/ 70</span>
              </p>
              <p class="mt-1 text-xs text-gray-500">
                Counted: <span class="font-medium text-gray-700" x-text="cappedTotal()"></span>
              </p>
            </div>

            <div class="rounded-xl border p-4">
              <p class="text-xs text-gray-500">Evidence</p>
              <p class="text-xl font-semibold text-gray-800">
                <span x-text="evidenceFiles.length"></span>
              </p>
              <p class="mt-1 text-xs text-gray-500">files uploaded</p>
            </div>
          </div>

          <template x-if="Number(rawTotal()) > 70">
            <p class="mt-3 text-xs text-red-600">
              Your raw total exceeds the 70-point limit. Excess points will not be counted.
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
          Publications and Creative Works within the last three years (supported by evidences) + 1/3 of the points earned in the last reclassification.
          Any two (2) criteria below must be met.
        </p>
      </div>
    </div>

    {{-- =====================================================
    1. BOOK AUTHORSHIP
    ===================================================== --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-200">
      <div class="px-6 py-4 border-b">
        <h3 class="text-lg font-semibold text-gray-800">1. Authorship / Co-authorship of Book</h3>
      </div>

      <div class="p-6 space-y-3">
        <p x-show="c1.length === 0" class="text-sm italic text-gray-500">No entry added.</p>

        <div class="overflow-x-auto">
          <table x-show="c1.length" class="w-full text-sm border rounded-lg overflow-hidden">
            <thead class="bg-gray-50">
            <tr>
              <th class="p-2 text-left">Title</th>
              <th class="p-2 text-left">Authorship</th>
              <th class="p-2 text-left">Publication</th>
              <th class="p-2 text-left">Publisher Type</th>
              <th class="p-2 text-left">Points</th>
              <th class="p-2 text-left">Evidence</th>
              <th class="p-2"></th>
            </tr>
            </thead>
            <tbody>
            <template x-for="(row,i) in c1" :key="i">
              <tr class="border-t">
                <td class="p-2">
                  <input x-model="row.title"
                         :name="`section3[c1][${i}][title]`"
                         class="w-full rounded border-gray-300"
                         placeholder="Enter book title">
                </td>

                <td class="p-2">
                  <select x-model="row.authorship"
                          :name="`section3[c1][${i}][authorship]`"
                          class="rounded border-gray-300 w-full">
                    <option value="" disabled selected>Select authorship</option>
                    <option value="sole">Sole authorship</option>
                    <option value="co">Co-authorship</option>
                  </select>
                </td>

                <td class="p-2">
                  <select x-model="row.edition"
                          :name="`section3[c1][${i}][edition]`"
                          class="rounded border-gray-300 w-full">
                    <option value="" disabled selected>Select edition</option>
                    <option value="new">New book</option>
                    <option value="revised">Revised edition</option>
                  </select>
                </td>

                <td class="p-2">
                  <select x-model="row.publisher"
                          :name="`section3[c1][${i}][publisher]`"
                          class="rounded border-gray-300 w-full">
                    <option value="" disabled selected>Select publisher type</option>
                    <option value="registered">Registered publisher</option>
                    <option value="printed_approved">Printed by author + approved by textbook board</option>
                  </select>
                </td>

                <td class="p-2 text-gray-700">
                  <span x-text="ptsBook(row)"></span>
                  <span class="text-xs text-gray-400">(Auto)</span>
                </td>

                <td class="p-2">
                  <select x-model="row.evidence"
                          :name="`section3[c1][${i}][evidence]`"
                          class="rounded border-gray-300 w-full">
                    <option value="" disabled selected>Select evidence</option>
                    <template x-for="(e,idx) in evidenceFiles" :key="idx">
                      <option :value="idx" x-text="e.name"></option>
                    </template>
                  </select>
                </td>

                <td class="p-2 text-right">
                  <button type="button" @click="c1.splice(i,1)" class="text-red-500 text-xs hover:underline">Remove</button>
                </td>
              </tr>
            </template>
            </tbody>
          </table>
        </div>

        <button type="button"
                @click="c1.push({ title:'', authorship:'', edition:'', publisher:'', evidence:'' })"
                class="text-sm text-bu hover:underline">
          + Add book
        </button>
      </div>
    </div>

    {{-- =====================================================
    2. WORKBOOKS / MANUALS
    ===================================================== --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-200">
      <div class="px-6 py-4 border-b">
        <h3 class="text-lg font-semibold text-gray-800">2. Workbooks / Manuals / Instructional Materials</h3>
      </div>

      <div class="p-6 space-y-3">
        <p x-show="c2.length === 0" class="text-sm italic text-gray-500">No entry added.</p>

        <div class="overflow-x-auto">
          <table x-show="c2.length" class="w-full text-sm border rounded-lg overflow-hidden">
            <thead class="bg-gray-50">
            <tr>
              <th class="p-2 text-left">Title</th>
              <th class="p-2 text-left">Authorship</th>
              <th class="p-2 text-left">Edition</th>
              <th class="p-2 text-left">Publisher Type</th>
              <th class="p-2 text-left">Points</th>
              <th class="p-2 text-left">Evidence</th>
              <th class="p-2"></th>
            </tr>
            </thead>
            <tbody>
            <template x-for="(row,i) in c2" :key="i">
              <tr class="border-t">
                <td class="p-2">
                  <input x-model="row.title"
                         :name="`section3[c2][${i}][title]`"
                         class="w-full rounded border-gray-300"
                         placeholder="Enter material title">
                </td>

                <td class="p-2">
                  <select x-model="row.authorship"
                          :name="`section3[c2][${i}][authorship]`"
                          class="rounded border-gray-300 w-full">
                    <option value="" disabled selected>Select authorship</option>
                    <option value="sole">Sole authorship</option>
                    <option value="co">Co-authorship</option>
                  </select>
                </td>

                <td class="p-2">
                  <select x-model="row.edition"
                          :name="`section3[c2][${i}][edition]`"
                          class="rounded border-gray-300 w-full">
                    <option value="" disabled selected>Select edition</option>
                    <option value="new">New edition</option>
                    <option value="revised">Revised edition</option>
                  </select>
                </td>

                <td class="p-2">
                  <select x-model="row.publisher"
                          :name="`section3[c2][${i}][publisher]`"
                          class="rounded border-gray-300 w-full">
                    <option value="" disabled selected>Select publisher type</option>
                    <option value="registered">Registered publisher</option>
                    <option value="printed_approved">Printed by author + approved by textbook board</option>
                  </select>
                </td>

                <td class="p-2 text-gray-700">
                  <span x-text="ptsWorkbook(row)"></span>
                  <span class="text-xs text-gray-400">(Auto)</span>
                </td>

                <td class="p-2">
                  <select x-model="row.evidence"
                          :name="`section3[c2][${i}][evidence]`"
                          class="rounded border-gray-300 w-full">
                    <option value="" disabled selected>Select evidence</option>
                    <template x-for="(e,idx) in evidenceFiles" :key="idx">
                      <option :value="idx" x-text="e.name"></option>
                    </template>
                  </select>
                </td>

                <td class="p-2 text-right">
                  <button type="button" @click="c2.splice(i,1)" class="text-red-500 text-xs hover:underline">Remove</button>
                </td>
              </tr>
            </template>
            </tbody>
          </table>
        </div>

        <button type="button"
                @click="c2.push({ title:'', authorship:'', edition:'', publisher:'', evidence:'' })"
                class="text-sm text-bu hover:underline">
          + Add material
        </button>
      </div>
    </div>

    {{-- =====================================================
    3. COMPILATIONS / ANTHOLOGIES
    ===================================================== --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-200">
      <div class="px-6 py-4 border-b">
        <h3 class="text-lg font-semibold text-gray-800">3. Compilations / Anthologies</h3>
      </div>

      <div class="p-6 space-y-3">
        <p x-show="c3.length === 0" class="text-sm italic text-gray-500">No entry added.</p>

        <div class="overflow-x-auto">
          <table x-show="c3.length" class="w-full text-sm border rounded-lg overflow-hidden">
            <thead class="bg-gray-50">
            <tr>
              <th class="p-2 text-left">Title</th>
              <th class="p-2 text-left">Authorship</th>
              <th class="p-2 text-left">Edition</th>
              <th class="p-2 text-left">Publisher Type</th>
              <th class="p-2 text-left">Points</th>
              <th class="p-2 text-left">Evidence</th>
              <th class="p-2"></th>
            </tr>
            </thead>
            <tbody>
            <template x-for="(row,i) in c3" :key="i">
              <tr class="border-t">
                <td class="p-2">
                  <input x-model="row.title"
                         :name="`section3[c3][${i}][title]`"
                         class="w-full rounded border-gray-300"
                         placeholder="Enter compilation title">
                </td>

                <td class="p-2">
                  <select x-model="row.authorship"
                          :name="`section3[c3][${i}][authorship]`"
                          class="rounded border-gray-300 w-full">
                    <option value="" disabled selected>Select authorship</option>
                    <option value="sole">Sole authorship</option>
                    <option value="co">Co-authorship</option>
                  </select>
                </td>

                <td class="p-2">
                  <select x-model="row.edition"
                          :name="`section3[c3][${i}][edition]`"
                          class="rounded border-gray-300 w-full">
                    <option value="" disabled selected>Select edition</option>
                    <option value="new">New edition</option>
                    <option value="revised">Revised edition</option>
                  </select>
                </td>

                <td class="p-2">
                  <select x-model="row.publisher"
                          :name="`section3[c3][${i}][publisher]`"
                          class="rounded border-gray-300 w-full">
                    <option value="" disabled selected>Select publisher type</option>
                    <option value="registered">Registered publisher</option>
                    <option value="printed_approved">Printed by author + approved by textbook board</option>
                  </select>
                </td>

                <td class="p-2 text-gray-700">
                  <span x-text="ptsCompilation(row)"></span>
                  <span class="text-xs text-gray-400">(Auto)</span>
                </td>

                <td class="p-2">
                  <select x-model="row.evidence"
                          :name="`section3[c3][${i}][evidence]`"
                          class="rounded border-gray-300 w-full">
                    <option value="" disabled selected>Select evidence</option>
                    <template x-for="(e,idx) in evidenceFiles" :key="idx">
                      <option :value="idx" x-text="e.name"></option>
                    </template>
                  </select>
                </td>

                <td class="p-2 text-right">
                  <button type="button" @click="c3.splice(i,1)" class="text-red-500 text-xs hover:underline">Remove</button>
                </td>
              </tr>
            </template>
            </tbody>
          </table>
        </div>

        <button type="button"
                @click="c3.push({ title:'', authorship:'', edition:'', publisher:'', evidence:'' })"
                class="text-sm text-bu hover:underline">
          + Add compilation
        </button>
      </div>
    </div>

    {{-- =====================================================
    4. ARTICLES (incl. Other publications)
    ===================================================== --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-200">
      <div class="px-6 py-4 border-b">
        <h3 class="text-lg font-semibold text-gray-800">4. Articles Published</h3>
      </div>

      <div class="p-6 space-y-3">
        <p x-show="c4.length === 0" class="text-sm italic text-gray-500">No entry added.</p>

        <div class="overflow-x-auto">
          <table x-show="c4.length" class="w-full text-sm border rounded-lg overflow-hidden">
            <thead class="bg-gray-50">
            <tr>
              <th class="p-2 text-left">Title</th>
              <th class="p-2 text-left">Type</th>
              <th class="p-2 text-left">Authorship</th>
              <th class="p-2 text-left">Scope</th>
              <th class="p-2 text-left">Points</th>
              <th class="p-2 text-left">Evidence</th>
              <th class="p-2"></th>
            </tr>
            </thead>
            <tbody>
            <template x-for="(row,i) in c4" :key="i">
              <tr class="border-t">
                <td class="p-2">
                  <input x-model="row.title"
                         :name="`section3[c4][${i}][title]`"
                         class="w-full rounded border-gray-300"
                         placeholder="Enter article title">
                </td>
                <td class="p-2">
                  <select x-model="row.kind"
                          :name="`section3[c4][${i}][kind]`"
                          class="rounded border-gray-300 w-full">
                    <option value="" disabled selected>Select type</option>
                    <option value="refereed">Refereed</option>
                    <option value="nonrefereed">Non-refereed</option>
                    <option value="otherpub">Other publications (columns / contributions)</option>
                  </select>
                </td>
                <td class="p-2">
                  <select x-model="row.authorship"
                          :name="`section3[c4][${i}][authorship]`"
                          class="rounded border-gray-300 w-full"
                          :disabled="row.kind === 'otherpub'">
                    <option value="" disabled selected>Select authorship</option>
                    <option value="sole">Sole Author</option>
                    <option value="co">Co-Author</option>
                  </select>
                </td>
                <td class="p-2">
                  <select x-model="row.scope"
                          :name="`section3[c4][${i}][scope]`"
                          class="rounded border-gray-300 w-full">
                    <template x-if="row.kind !== 'otherpub'">
                      <optgroup label="Journal / Magazine Scope">
                        <option value="" disabled selected>Select scope</option>
                        <option value="international">International</option>
                        <option value="national">National</option>
                        <option value="university">University</option>
                      </optgroup>
                    </template>
                    <template x-if="row.kind === 'otherpub'">
                      <optgroup label="Other publications">
                        <option value="" disabled selected>Select scope</option>
                        <option value="national_periodicals">National periodicals</option>
                        <option value="local_periodicals">Local periodicals</option>
                        <option value="university_newsletters">University/department newsletters</option>
                      </optgroup>
                    </template>
                  </select>
                </td>
                <td class="p-2 text-gray-700">
                  <span x-text="ptsArticle(row)"></span>
                  <span class="text-xs text-gray-400">(Auto)</span>
                </td>
                <td class="p-2">
                  <select x-model="row.evidence"
                          :name="`section3[c4][${i}][evidence]`"
                          class="rounded border-gray-300 w-full">
                    <option value="" disabled selected>Select evidence</option>
                    <template x-for="(e,idx) in evidenceFiles" :key="idx">
                      <option :value="idx" x-text="e.name"></option>
                    </template>
                  </select>
                </td>
                <td class="p-2 text-right">
                  <button type="button" @click="c4.splice(i,1)" class="text-red-500 text-xs hover:underline">Remove</button>
                </td>
              </tr>
            </template>
            </tbody>
          </table>
        </div>

        <button type="button"
                @click="c4.push({ title:'', kind:'', authorship:'', scope:'', evidence:'' })"
                class="text-sm text-bu hover:underline">
          + Add article / publication
        </button>
      </div>
    </div>

    {{-- =====================================================
    5. CONFERENCE PAPERS
    ===================================================== --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-200">
      <div class="px-6 py-4 border-b">
        <h3 class="text-lg font-semibold text-gray-800">5. Conference Paper Presentations</h3>
      </div>

      <div class="p-6 space-y-3">
        <p x-show="c5.length === 0" class="text-sm italic text-gray-500">No entry added.</p>

        <div class="overflow-x-auto">
          <table x-show="c5.length" class="w-full text-sm border rounded-lg overflow-hidden">
            <thead class="bg-gray-50">
            <tr>
              <th class="p-2 text-left">Title</th>
              <th class="p-2 text-left">Level</th>
              <th class="p-2 text-left">Points</th>
              <th class="p-2 text-left">Evidence</th>
              <th class="p-2"></th>
            </tr>
            </thead>
            <tbody>
            <template x-for="(row,i) in c5" :key="i">
              <tr class="border-t">
                <td class="p-2">
                  <input x-model="row.title"
                         :name="`section3[c5][${i}][title]`"
                         class="w-full rounded border-gray-300"
                         placeholder="Enter paper title">
                </td>
                <td class="p-2">
                  <select x-model="row.level"
                          :name="`section3[c5][${i}][level]`"
                          class="rounded border-gray-300 w-full">
                    <option value="" disabled selected>Select level</option>
                    <option value="international">International</option>
                    <option value="national">National</option>
                    <option value="regional">Regional / Provincial</option>
                    <option value="institutional">Institutional / Local</option>
                  </select>
                </td>
                <td class="p-2 text-gray-700">
                  <span x-text="ptsConference(row)"></span>
                  <span class="text-xs text-gray-400">(Auto)</span>
                </td>
                <td class="p-2">
                  <select x-model="row.evidence"
                          :name="`section3[c5][${i}][evidence]`"
                          class="rounded border-gray-300 w-full">
                    <option value="" disabled selected>Select evidence</option>
                    <template x-for="(e,idx) in evidenceFiles" :key="idx">
                      <option :value="idx" x-text="e.name"></option>
                    </template>
                  </select>
                </td>
                <td class="p-2 text-right">
                  <button type="button" @click="c5.splice(i,1)" class="text-red-500 text-xs hover:underline">Remove</button>
                </td>
              </tr>
            </template>
            </tbody>
          </table>
        </div>

        <button type="button"
                @click="c5.push({ title:'', level:'', evidence:'' })"
                class="text-sm text-bu hover:underline">
          + Add conference paper
        </button>
      </div>
    </div>

    {{-- =====================================================
    6. COMPLETED RESEARCH
    ===================================================== --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-200">
      <div class="px-6 py-4 border-b">
        <h3 class="text-lg font-semibold text-gray-800">
          6. Completed Research (Not part of graduate degree requirement)
        </h3>
      </div>

      <div class="p-6 space-y-3">
        <p x-show="c6.length === 0" class="text-sm italic text-gray-500">No entry added.</p>

        <div class="overflow-x-auto">
          <table x-show="c6.length" class="w-full text-sm border rounded-lg overflow-hidden">
            <thead class="bg-gray-50">
            <tr>
              <th class="p-2 text-left">Title</th>
              <th class="p-2 text-left">Role</th>
              <th class="p-2 text-left">Level</th>
              <th class="p-2 text-left">Points</th>
              <th class="p-2 text-left">Evidence</th>
              <th class="p-2"></th>
            </tr>
            </thead>
            <tbody>
            <template x-for="(row,i) in c6" :key="i">
              <tr class="border-t">
                <td class="p-2">
                  <input x-model="row.title"
                         :name="`section3[c6][${i}][title]`"
                         class="w-full rounded border-gray-300"
                         placeholder="Enter research title">
                </td>
                <td class="p-2">
                  <select x-model="row.role"
                          :name="`section3[c6][${i}][role]`"
                          class="rounded border-gray-300 w-full">
                    <option value="" disabled selected>Select role</option>
                    <option value="principal">Principal proponent</option>
                    <option value="team">Team member</option>
                  </select>
                </td>
                <td class="p-2">
                  <select x-model="row.level"
                          :name="`section3[c6][${i}][level]`"
                          class="rounded border-gray-300 w-full">
                    <option value="" disabled selected>Select level</option>
                    <option value="international">International</option>
                    <option value="national">National</option>
                    <option value="regional">Regional / Provincial</option>
                    <option value="institutional">Institutional / Local</option>
                  </select>
                </td>
                <td class="p-2 text-gray-700">
                  <span x-text="ptsCompleted(row)"></span>
                  <span class="text-xs text-gray-400">(Auto)</span>
                </td>
                <td class="p-2">
                  <select x-model="row.evidence"
                          :name="`section3[c6][${i}][evidence]`"
                          class="rounded border-gray-300 w-full">
                    <option value="" disabled selected>Select evidence</option>
                    <template x-for="(e,idx) in evidenceFiles" :key="idx">
                      <option :value="idx" x-text="e.name"></option>
                    </template>
                  </select>
                </td>
                <td class="p-2 text-right">
                  <button type="button" @click="c6.splice(i,1)" class="text-red-500 text-xs hover:underline">Remove</button>
                </td>
              </tr>
            </template>
            </tbody>
          </table>
        </div>

        <button type="button"
                @click="c6.push({ title:'', role:'', level:'', evidence:'' })"
                class="text-sm text-bu hover:underline">
          + Add research
        </button>
      </div>
    </div>

    {{-- =====================================================
    7. RESEARCH / PROJECT PROPOSALS APPROVED
    ===================================================== --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-200">
      <div class="px-6 py-4 border-b">
        <h3 class="text-lg font-semibold text-gray-800">
          7. Research / Project Proposals Approved (Reviewed & approved by Research Center)
        </h3>
      </div>

      <div class="p-6 space-y-3">
        <p x-show="c7.length === 0" class="text-sm italic text-gray-500">No entry added.</p>

        <div class="overflow-x-auto">
          <table x-show="c7.length" class="w-full text-sm border rounded-lg overflow-hidden">
            <thead class="bg-gray-50">
            <tr>
              <th class="p-2 text-left">Title</th>
              <th class="p-2 text-left">Role</th>
              <th class="p-2 text-left">Level</th>
              <th class="p-2 text-left">Points</th>
              <th class="p-2 text-left">Evidence</th>
              <th class="p-2"></th>
            </tr>
            </thead>
            <tbody>
            <template x-for="(row,i) in c7" :key="i">
              <tr class="border-t">
                <td class="p-2">
                  <input x-model="row.title"
                         :name="`section3[c7][${i}][title]`"
                         class="w-full rounded border-gray-300"
                         placeholder="Enter proposal title">
                </td>
                <td class="p-2">
                  <select x-model="row.role"
                          :name="`section3[c7][${i}][role]`"
                          class="rounded border-gray-300 w-full">
                    <option value="" disabled selected>Select role</option>
                    <option value="principal">Principal proponent</option>
                    <option value="team">Team member</option>
                  </select>
                </td>
                <td class="p-2">
                  <select x-model="row.level"
                          :name="`section3[c7][${i}][level]`"
                          class="rounded border-gray-300 w-full">
                    <option value="" disabled selected>Select level</option>
                    <option value="international">International</option>
                    <option value="national">National</option>
                    <option value="regional">Regional / Provincial</option>
                    <option value="institutional">Institutional / Local</option>
                  </select>
                </td>
                <td class="p-2 text-gray-700">
                  <span x-text="ptsProposal(row)"></span>
                  <span class="text-xs text-gray-400">(Auto)</span>
                </td>
                <td class="p-2">
                  <select x-model="row.evidence"
                          :name="`section3[c7][${i}][evidence]`"
                          class="rounded border-gray-300 w-full">
                    <option value="" disabled selected>Select evidence</option>
                    <template x-for="(e,idx) in evidenceFiles" :key="idx">
                      <option :value="idx" x-text="e.name"></option>
                    </template>
                  </select>
                </td>
                <td class="p-2 text-right">
                  <button type="button" @click="c7.splice(i,1)" class="text-red-500 text-xs hover:underline">Remove</button>
                </td>
              </tr>
            </template>
            </tbody>
          </table>
        </div>

        <button type="button"
                @click="c7.push({ title:'', role:'', level:'', evidence:'' })"
                class="text-sm text-bu hover:underline">
          + Add proposal
        </button>
      </div>
    </div>

    {{-- =====================================================
    8. CASE STUDIES / ACTION RESEARCH (fixed 5)
    ===================================================== --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-200">
      <div class="px-6 py-4 border-b">
        <h3 class="text-lg font-semibold text-gray-800">
          8. Authorship of Case Studies / Classroom-based Action Research
        </h3>
      </div>

      <div class="p-6 space-y-3">
        <p class="text-sm text-gray-600">Fixed: 5 points per output</p>

        <p x-show="c8.length === 0" class="text-sm italic text-gray-500">No entry added.</p>

        <div class="overflow-x-auto">
          <table x-show="c8.length" class="w-full text-sm border rounded-lg overflow-hidden">
            <thead class="bg-gray-50">
            <tr>
              <th class="p-2 text-left">Title</th>
              <th class="p-2 text-left">Points</th>
              <th class="p-2 text-left">Evidence</th>
              <th class="p-2"></th>
            </tr>
            </thead>
            <tbody>
            <template x-for="(row,i) in c8" :key="i">
              <tr class="border-t">
                <td class="p-2">
                  <input x-model="row.title"
                         :name="`section3[c8][${i}][title]`"
                         class="w-full rounded border-gray-300"
                         placeholder="Enter case study / action research title">
                </td>
                <td class="p-2 text-gray-700">
                  <span>5</span> <span class="text-xs text-gray-400">(Fixed)</span>
                </td>
                <td class="p-2">
                  <select x-model="row.evidence"
                          :name="`section3[c8][${i}][evidence]`"
                          class="rounded border-gray-300 w-full">
                    <option value="" disabled selected>Select evidence</option>
                    <template x-for="(e,idx) in evidenceFiles" :key="idx">
                      <option :value="idx" x-text="e.name"></option>
                    </template>
                  </select>
                </td>
                <td class="p-2 text-right">
                  <button type="button" @click="c8.splice(i,1)" class="text-red-500 text-xs hover:underline">Remove</button>
                </td>
              </tr>
            </template>
            </tbody>
          </table>
        </div>

        <button type="button"
                @click="c8.push({ title:'', evidence:'' })"
                class="text-sm text-bu hover:underline">
          + Add case study / action research
        </button>
      </div>
    </div>

    {{-- =====================================================
    9. EDITORIAL SERVICES
    ===================================================== --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-200">
      <div class="px-6 py-4 border-b">
        <h3 class="text-lg font-semibold text-gray-800">9. Editorial Services</h3>
      </div>

      <div class="p-6 space-y-3">
        <p x-show="c9.length === 0" class="text-sm italic text-gray-500">No entry added.</p>

        <div class="overflow-x-auto">
          <table x-show="c9.length" class="w-full text-sm border rounded-lg overflow-hidden">
            <thead class="bg-gray-50">
            <tr>
              <th class="p-2 text-left">Service Type</th>
              <th class="p-2 text-left">Points</th>
              <th class="p-2 text-left">Evidence</th>
              <th class="p-2"></th>
            </tr>
            </thead>
            <tbody>
            <template x-for="(row,i) in c9" :key="i">
              <tr class="border-t">
                <td class="p-2">
                  <select x-model="row.service"
                          :name="`section3[c9][${i}][service]`"
                          class="rounded border-gray-300 w-full">
                    <option value="" disabled selected>Select service type</option>
                    <option value="chief">Editor-in-chief / executive / associate / managing editor (Intl/Natl)</option>
                    <option value="editor">Editor of org/university-based publications</option>
                    <option value="consultant">Editorial consultant / technical adviser</option>
                  </select>
                </td>
                <td class="p-2 text-gray-700">
                  <span x-text="ptsEditorial(row)"></span>
                  <span class="text-xs text-gray-400">(Auto)</span>
                </td>
                <td class="p-2">
                  <select x-model="row.evidence"
                          :name="`section3[c9][${i}][evidence]`"
                          class="rounded border-gray-300 w-full">
                    <option value="" disabled selected>Select evidence</option>
                    <template x-for="(e,idx) in evidenceFiles" :key="idx">
                      <option :value="idx" x-text="e.name"></option>
                    </template>
                  </select>
                </td>
                <td class="p-2 text-right">
                  <button type="button" @click="c9.splice(i,1)" class="text-red-500 text-xs hover:underline">Remove</button>
                </td>
              </tr>
            </template>
            </tbody>
          </table>
        </div>

        <button type="button"
                @click="c9.push({ service:'', evidence:'' })"
                class="text-sm text-bu hover:underline">
          + Add editorial service
        </button>
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
          name="section3[previous_points]"
          type="number" step="0.01"
          class="mt-1 w-56 rounded border-gray-300"
          placeholder="0.00"
        >
        <p class="text-xs text-gray-500 mt-1">
          System applies 1/3 of this value. Subject to validation.
        </p>
      </div>
    </div>

    {{-- ===============================
    EVIDENCE UPLOAD (ONE UPLOAD AREA)
    =============================== --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-200">
      <div class="px-6 py-4 border-b">
        <h3 class="text-lg font-semibold text-gray-800">Section III Evidence Upload</h3>
        <p class="text-sm text-gray-500 mt-1">
          Upload once. Reference a file per row.
        </p>
      </div>

      <div class="p-6">
        <input type="file"
               name="section3[evidence_files][]"
               multiple
               @change="handleEvidence($event)"
               class="w-full text-sm">
      </div>
    </div>

    {{-- ACTIONS --}}
    <div class="flex justify-end gap-4">
    </div>

  </div>
</div>

<script>
function sectionThree() {
  return {
    evidenceFiles: [],
    handleEvidence(e) {
      this.evidenceFiles = Array.from(e.target.files || []);
    },

    c1: [], c2: [], c3: [], c4: [], c5: [], c6: [], c7: [], c8: [], c9: [],
    previous: 0,

    // ✅ Paper-based points
    ptsBook(row) {
      // Requires authorship + edition + publisher
      const a = row.authorship;
      const ed = row.edition;
      const pub = row.publisher;
      if (!a || !ed || !pub) return Number(0).toFixed(2);

      const map = {
        sole: {
          new: { registered: 20, printed_approved: 18 },
          revised: { registered: 16, printed_approved: 14 },
        },
        co: {
          new: { registered: 14, printed_approved: 12 },
          revised: { registered: 10, printed_approved: 8 },
        }
      };

      return Number(map?.[a]?.[ed]?.[pub] || 0).toFixed(2);
    },

    ptsWorkbook(row) {
      const a = row.authorship;
      const ed = row.edition;      // new / revised
      const pub = row.publisher;   // registered / printed_approved
      if (!a || !ed || !pub) return Number(0).toFixed(2);

      const map = {
        sole: {
          new: { registered: 15, printed_approved: 13 },
          revised: { registered: 11, printed_approved: 9 },
        },
        co: {
          new: { registered: 9, printed_approved: 8 },
          revised: { registered: 7, printed_approved: 6 },
        }
      };

      return Number(map?.[a]?.[ed]?.[pub] || 0).toFixed(2);
    },

    ptsCompilation(row) {
      const a = row.authorship;
      const ed = row.edition;
      const pub = row.publisher;
      if (!a || !ed || !pub) return Number(0).toFixed(2);

      const map = {
        sole: {
          new: { registered: 12, printed_approved: 11 },
          revised: { registered: 10, printed_approved: 9 },
        },
        co: {
          new: { registered: 8, printed_approved: 7 },
          revised: { registered: 6, printed_approved: 5 },
        }
      };

      return Number(map?.[a]?.[ed]?.[pub] || 0).toFixed(2);
    },

    ptsArticle(row) {
      if (!row.kind || !row.scope) return Number(0).toFixed(2);

      // other publications fixed
      if (row.kind === 'otherpub') {
        const other = {
          national_periodicals: 5,
          local_periodicals: 4,
          university_newsletters: 3,
        };
        return Number(other[row.scope] || 0).toFixed(2);
      }

      // refereed / nonrefereed requires authorship
      if (!row.authorship) return Number(0).toFixed(2);

      const key = `${row.kind}_${row.authorship}_${row.scope}`;
      const map = {
        // Refereed
        refereed_sole_international: 40,
        refereed_co_international: 36,
        refereed_sole_national: 38,
        refereed_co_national: 34,
        refereed_sole_university: 36,
        refereed_co_university: 32,

        // Non-refereed
        nonrefereed_sole_international: 30,
        nonrefereed_co_international: 24,
        nonrefereed_sole_national: 28,
        nonrefereed_co_national: 22,
        nonrefereed_sole_university: 20,
        nonrefereed_co_university: 20,
      };
      return Number(map[key] || 0).toFixed(2);
    },

    ptsConference(row) {
      const map = { international: 15, national: 13, regional: 11, institutional: 9 };
      return Number(map[row.level] || 0).toFixed(2);
    },

    ptsCompleted(row) {
      const principal = { international: 20, national: 18, regional: 16, institutional: 14 };
      const team      = { international: 15, national: 13, regional: 11, institutional: 9 };
      const a = row.role === 'team' ? team : principal;
      return Number(a[row.level] || 0).toFixed(2);
    },

    ptsProposal(row) {
      const principal = { international: 15, national: 13, regional: 11, institutional: 9 };
      const team      = { international: 11, national: 9, regional: 7, institutional: 5 };
      const a = row.role === 'team' ? team : principal;
      return Number(a[row.level] || 0).toFixed(2);
    },

    ptsEditorial(row) {
      const map = { chief: 15, editor: 10, consultant: 5 };
      return Number(map[row.service] || 0).toFixed(2);
    },

    // --- sums per criterion ---
    sum1() { return this.c1.reduce((t,r)=> t + Number(this.ptsBook(r)), 0).toFixed(2); },
    sum2() { return this.c2.reduce((t,r)=> t + Number(this.ptsWorkbook(r)), 0).toFixed(2); },
    sum3() { return this.c3.reduce((t,r)=> t + Number(this.ptsCompilation(r)), 0).toFixed(2); },
    sum4() { return this.c4.reduce((t,r)=> t + Number(this.ptsArticle(r)), 0).toFixed(2); },
    sum5() { return this.c5.reduce((t,r)=> t + Number(this.ptsConference(r)), 0).toFixed(2); },
    sum6() { return this.c6.reduce((t,r)=> t + Number(this.ptsCompleted(r)), 0).toFixed(2); },
    sum7() { return this.c7.reduce((t,r)=> t + Number(this.ptsProposal(r)), 0).toFixed(2); },
    sum8() { return this.c8.reduce((t,_r)=> t + 5, 0).toFixed(2); },
    sum9() { return this.c9.reduce((t,r)=> t + Number(this.ptsEditorial(r)), 0).toFixed(2); },

    subtotal() {
      const total =
        Number(this.sum1()) + Number(this.sum2()) + Number(this.sum3()) +
        Number(this.sum4()) + Number(this.sum5()) + Number(this.sum6()) +
        Number(this.sum7()) + Number(this.sum8()) + Number(this.sum9());
      return total.toFixed(2);
    },

    prevThird() {
      const p = Number(this.previous || 0);
      return (p / 3).toFixed(2);
    },

    rawTotal() {
      return (Number(this.subtotal()) + Number(this.prevThird())).toFixed(2);
    },

    cappedTotal() {
      const raw = Number(this.rawTotal());
      return (raw > 70 ? 70 : raw).toFixed(2);
    },

    criteriaMet() {
      const arr = [this.c1,this.c2,this.c3,this.c4,this.c5,this.c6,this.c7,this.c8,this.c9];
      return arr.reduce((c,a)=> c + ((a && a.length) ? 1 : 0), 0);
    },
  }
}
</script>

</form>

