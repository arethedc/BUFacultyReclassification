<x-app-layout>
<x-slot name="header">
    <h2 class="text-2xl font-semibold text-gray-800">
        Faculty Reclassification
    </h2>
    <p class="text-sm text-gray-500">
        Review and complete your reclassification requirements.
    </p>
</x-slot>

<div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"
     x-data="{ tab: 'section1' }"x-data="{ tab: 'section2' }"x-data="{ tab: 'section3' }"x-data="{ tab: 'section4' }"x-data="{ tab: 'section5' }">
    {{-- TABS --}}
    <div class="flex gap-4 border-b mb-6">
        <button @click="tab='section1'"
                :class="tab==='section1' ? 'border-bu text-bu' : 'text-gray-500'"
                class="pb-2 border-b-2 font-medium">
            Section I
        </button>

        <button @click="tab='section2'"
                class="pb-2 border-b-2 font-medium text-gray-400 cursor-not-allowed">
            Section II
        </button>
           <button @click="tab='section3'"
                :class="tab==='section3' ? 'border-bu text-bu' : 'text-gray-500'"
                class="pb-2 border-b-2 font-medium">
            Section III
        </button>
           <button @click="tab='section4'"
                :class="tab==='section4' ? 'border-bu text-bu' : 'text-gray-500'"
                class="pb-2 border-b-2 font-medium">
            Section IV
        </button>
           <button @click="tab='section5'"
                :class="tab==='section5' ? 'border-bu text-bu' : 'text-gray-500'"
                class="pb-2 border-b-2 font-medium">
            Section V
        </button>
    </div>
    </div>
    {{-- SECTION I --}}
    <div x-show="tab==='section1'">
        @include('reclassification.section1')
    </div>

    {{-- SECTION II (VIEW ONLY FOR FACULTY) --}}
    <div x-show="tab==='section2'" class="opacity-60">
        <div class="mb-4 text-sm text-gray-500 italic">
            Section II is completed by the Dean and is view-only.
        </div>
         @include('reclassification.section2')
    </div>

 <div x-show="tab==='section3'">
        @include('reclassification.section3')
    </div>
 <div x-show="tab==='section4'">
        @include('reclassification.section4')
    </div>
 <div x-show="tab==='section5'">
        @include('reclassification.section5')
    </div>
     <div x-show="tab==='review'">
        @include('reclassification.review')
    </div>



</div>
</x-app-layout>
