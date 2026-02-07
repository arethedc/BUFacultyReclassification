{{-- resources/views/reclassification/section4.blade.php --}}
<div class="flex flex-col gap-1 mb-4">
    <h2 class="text-2xl font-semibold text-gray-800">Reclassification - Section IV</h2>
    <p class="text-sm text-gray-500">Teaching Experience / Professional / Administrative Experience (Max 40 pts / 10%)</p>
</div>
<form method="POST" action="{{ route('reclassification.section.save', 4) }}" data-validate-evidence>
@csrf

<div x-data="sectionFour()" class="py-12 bg-bu-muted min-h-screen">
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

    {{-- =======================
    STICKY SCORE SUMMARY (Section IV)
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
                Section IV Summary
              </h3>

<template x-if="Number(finalCapped()) <= 40">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium bg-green-50 text-green-700 border border-green-200">
                  Within limit
                </span>
              </template>
<template x-if="Number(finalCapped()) <= 40">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium bg-red-50 text-red-700 border border-red-200">
                  Over limit
                </span>
              </template>

              <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium bg-gray-50 text-gray-700 border">
                Counted track:
                <span class="ml-1 font-semibold" x-text="countedTrackLabel()"></span>
              </span>
            </div>

            <p class="text-xs text-gray-600 mt-1">
              Teaching (A) counted: <span class="font-semibold text-gray-800" x-text="teachingTotalCapped()"></span>
              <span class="mx-2 text-gray-300">•</span>
              Industry/Admin (B) capped: <span class="font-semibold text-gray-800" x-text="industryCapped()"></span>
              <span class="mx-2 text-gray-300">•</span>
              Final: <span class="font-semibold text-gray-800" x-text="finalCapped()"></span>
              <span class="text-gray-400">/ 40</span>
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
            Rule: credit is given only for Teaching Experience (A) OR Industry/Professional/Admin Experience (B),
            whichever is higher in points. Part-time faculty receives 1/2 points. Subject to validation.
          </p>

          <div class="mt-3 grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div class="rounded-xl border p-4">
              <p class="text-xs text-gray-500">A1 (Before BU)</p>
              <p class="text-xl font-semibold text-gray-800">
                <span x-text="a1Capped()"></span>
                <span class="text-sm font-medium text-gray-400">/ 20</span>
              </p>
              <p class="mt-1 text-xs text-gray-500">2 pts/year (capped)</p>
            </div>

            <div class="rounded-xl border p-4">
              <p class="text-xs text-gray-500">A2 (After BU)</p>
              <p class="text-xl font-semibold text-gray-800">
                <span x-text="a2Capped()"></span>
                <span class="text-sm font-medium text-gray-400">/ 40</span>
              </p>
              <p class="mt-1 text-xs text-gray-500">3 pts/year (capped)</p>
            </div>

            <div class="rounded-xl border p-4">
              <p class="text-xs text-gray-500">Teaching Total (A)</p>
              <p class="text-xl font-semibold text-gray-800">
                <span x-text="teachingTotalCapped()"></span>
                <span class="text-sm font-medium text-gray-400">/ 40</span>
              </p>
              <p class="mt-1 text-xs text-gray-500">A1 + A2, then cap</p>
            </div>

            <div class="rounded-xl border p-4">
              <p class="text-xs text-gray-500">Industry/Admin (B)</p>
              <p class="text-xl font-semibold text-gray-800">
                <span x-text="industryCapped()"></span>
                <span class="text-sm font-medium text-gray-400">/ 20</span>
              </p>
              <p class="mt-1 text-xs text-gray-500">2 pts/year (capped)</p>
            </div>
          </div>

          <template x-if="isPartTime">
            <p class="mt-3 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
              Part-time selected: all computed points are shown at 1/2 value (display only; subject to validation).
            </p>
          </template>
        </div>
      </div>
    </div>

    {{-- ======================================================
    SECTION IV – FORM
    ====================================================== --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-200">
        <div class="px-6 py-4 border-b">
            <h3 class="text-lg font-semibold text-gray-800">
                Teaching Experience / Professional / Administrative Experience
            </h3>
            <p class="text-sm text-gray-500">
                Encode years only. System suggests points and applies caps. Subject to validation.
            </p>
        </div>

        <div class="p-6 space-y-8">

         <div class="flex items-center justify-between gap-4 p-4 rounded-xl bg-gray-50 border">
    <div>
        <p class="text-sm font-medium text-gray-800">Employment Type</p>
        <p class="text-xs text-gray-500">
            Points are automatically adjusted based on your employment status.
        </p>
    </div>

    <div class="text-sm font-semibold"
         @class([
            'text-gray-800' => auth()->user()->employment_type !== 'part_time',
            'text-amber-700' => auth()->user()->employment_type === 'part_time',
         ])>
        {{ auth()->user()->employment_type === 'part_time' ? 'Part-time (50% applied)' : 'Full-time' }}
    </div>
</div>

            {{-- A. Teaching Experience --}}
            <div class="rounded-2xl border p-5 space-y-4">
                <div>
                    <h4 class="font-semibold text-gray-800">A. Teaching Experience</h4>
                    <p class="text-sm text-gray-500">
                        A1 capped at 20 pts; A2 capped at 40 pts; Teaching total capped at 40 pts.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- A1 --}}
                    <div class="rounded-xl border p-4">
                        <p class="text-sm font-medium text-gray-800">
                            A1. Teaching before joining BU
                        </p>
                        <p class="text-xs text-gray-500">
                            2 pts per year (cap 20)
                        </p>

                        <div class="mt-3 flex items-end justify-between gap-3">
                            <div class="flex-1">
                                <label class="block text-xs text-gray-600">Years</label>
                                <input
                                    x-model.number="a1Years"
                                    name="section4[a][a1_years]"
                                    type="number" min="0" step="1"
                                    class="mt-1 w-full rounded border-gray-300"
                                    placeholder="0"
                                >
                            </div>

                            <div class="text-right">
                                <p class="text-xs text-gray-500">Points (Auto)</p>
                                <p class="text-lg font-semibold text-gray-800">
                                    <span x-text="a1Capped()"></span>
                                    <span class="text-xs font-medium text-gray-400">/20</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- A2 --}}
                    <div class="rounded-xl border p-4">
                        <p class="text-sm font-medium text-gray-800">
                            A2. Actual services after joining BU
                        </p>
                        <p class="text-xs text-gray-500">
                            3 pts per year (cap 40)
                        </p>

                        <div class="mt-3 flex items-end justify-between gap-3">
                            <div class="flex-1">
                                <label class="block text-xs text-gray-600">Years</label>
                                <input
                                    x-model.number="a2Years"
                                    name="section4[a][a2_years]"
                                    type="number" min="0" step="1"
                                    class="mt-1 w-full rounded border-gray-300"
                                    placeholder="0"
                                >
                            </div>

                            <div class="text-right">
                                <p class="text-xs text-gray-500">Points (Auto)</p>
                                <p class="text-lg font-semibold text-gray-800">
                                    <span x-text="a2Capped()"></span>
                                    <span class="text-xs font-medium text-gray-400">/40</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- A Total --}}
                <div class="flex items-center justify-between rounded-xl bg-gray-50 border px-4 py-3">
                    <div>
                        <p class="text-sm font-medium text-gray-800">Teaching Total (A)</p>
                        <p class="text-xs text-gray-500">A1 + A2, capped at 40</p>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-semibold text-gray-800">
                            <span x-text="teachingTotalCapped()"></span>
                            <span class="text-xs font-medium text-gray-400">/40</span>
                        </p>
                    </div>
                </div>
            </div>

            {{-- B. Industry/Professional/Admin Experience --}}
            <div class="rounded-2xl border p-5 space-y-4">
                <div>
                    <h4 class="font-semibold text-gray-800">B. Industry / Professional / Administrative Experience</h4>
                    <p class="text-sm text-gray-500">
                        2 pts per year (cap 20). External validation may require minimum teaching experience.
                    </p>
                </div>

                <div class="rounded-xl border p-4">
                    <div class="mt-1 flex items-end justify-between gap-3">
                        <div class="flex-1">
                            <label class="block text-xs text-gray-600">Years</label>
                            <input
                                x-model.number="bYears"
                                name="section4[b][years]"
                                type="number" min="0" step="1"
                                class="mt-1 w-full rounded border-gray-300"
                                placeholder="0"
                            >
                        </div>

                        <div class="text-right">
                            <p class="text-xs text-gray-500">Points (Auto)</p>
                            <p class="text-lg font-semibold text-gray-800">
                                <span x-text="industryCapped()"></span>
                                <span class="text-xs font-medium text-gray-400">/20</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Final counted --}}
            <div class="rounded-2xl border p-5 bg-white">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h4 class="font-semibold text-gray-800">Section IV Final (Counted)</h4>
                        <p class="text-sm text-gray-500">
                            Only the higher track is counted (A vs B). Final capped at 40.
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-gray-500">Final (Auto)</p>
                        <p class="text-2xl font-semibold text-gray-800">
                            <span x-text="finalCapped()"></span>
                            <span class="text-sm font-medium text-gray-400">/40</span>
                        </p>
                    </div>
                </div>

                <p class="mt-3 text-xs text-gray-500">
                    Counted track: <span class="font-medium text-gray-700" x-text="countedTrackLabel()"></span>
                </p>
            </div>

            {{-- ACTIONS --}}
            <div class="flex justify-end gap-4 pt-2">
            </div>

        </div>
    </div>

</div>
</div>
</form>

<script>
function sectionFour() {
  return {
    // UI toggle
    isPartTime: {{ auth()->user()->employment_type === 'part_time' ? 'true' : 'false' }},

    // years input
    a1Years: 0,
    a2Years: 0,
    bYears: 0,

    n(v) {
      const x = Number(v);
      return Number.isFinite(x) ? x : 0;
    },
    cap(v, max) {
      v = Number(v || 0);
      return v > max ? max : v;
    },
    halfIfPartTime(v) {
      return this.isPartTime ? (Number(v) / 2) : Number(v);
    },

    // A1: 2 pts/year, cap 20
    a1Raw() {
      return this.n(this.a1Years) * 2;
    },
    a1Capped() {
      const v = this.cap(this.a1Raw(), 20);
      return this.halfIfPartTime(v).toFixed(2);
    },

    // A2: 3 pts/year, cap 40
    a2Raw() {
      return this.n(this.a2Years) * 3;
    },
    a2Capped() {
      const v = this.cap(this.a2Raw(), 40);
      return this.halfIfPartTime(v).toFixed(2);
    },

    // Teaching total (A): (A1cap + A2cap) then cap 40
    teachingTotalRawCapped() {
      const a1 = this.cap(this.a1Raw(), 20);
      const a2 = this.cap(this.a2Raw(), 40);
      return this.cap(a1 + a2, 40);
    },
    teachingTotalCapped() {
      return this.halfIfPartTime(this.teachingTotalRawCapped()).toFixed(2);
    },

    // B: 2 pts/year, cap 20
    industryRawCapped() {
      return this.cap(this.n(this.bYears) * 2, 20);
    },
    industryCapped() {
      return this.halfIfPartTime(this.industryRawCapped()).toFixed(2);
    },

    // final = max(A, B) capped to 40
rawCountedNumber() {
  const a = this.halfIfPartTime(this.teachingTotalRawCapped());
  const b = this.halfIfPartTime(this.industryRawCapped());
  return Math.max(a, b);
},
finalCapped() {
  return this.cap(this.rawCountedNumber(), 40).toFixed(2);
},

    countedTrackLabel() {
      const a = this.halfIfPartTime(this.teachingTotalRawCapped());
      const b = this.halfIfPartTime(this.industryRawCapped());
      if (a === 0 && b === 0) return 'None yet';
      return (a >= b) ? 'A. Teaching Experience' : 'B. Industry/Admin Experience';
    },
  }
}
</script>


