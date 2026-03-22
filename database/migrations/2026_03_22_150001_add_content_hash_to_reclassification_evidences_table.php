<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reclassification_evidences', function (Blueprint $table) {
            if (!Schema::hasColumn('reclassification_evidences', 'content_hash')) {
                $table->string('content_hash', 64)->nullable()->after('size_bytes');
                $table->index(
                    ['reclassification_application_id', 'content_hash'],
                    'rc_ev_app_hash_ix'
                );
            }
        });
    }

    public function down(): void
    {
        Schema::table('reclassification_evidences', function (Blueprint $table) {
            if (Schema::hasColumn('reclassification_evidences', 'content_hash')) {
                $table->dropIndex('rc_ev_app_hash_ix');
                $table->dropColumn('content_hash');
            }
        });
    }
};

