<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reclassification_applications', function (Blueprint $table) {
            if (!Schema::hasColumn('reclassification_applications', 'faculty_return_request_reason')) {
                $table->text('faculty_return_request_reason')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('reclassification_applications', function (Blueprint $table) {
            if (Schema::hasColumn('reclassification_applications', 'faculty_return_request_reason')) {
                $table->dropColumn('faculty_return_request_reason');
            }
        });
    }
};

