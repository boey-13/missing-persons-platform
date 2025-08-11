<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        if (!Schema::hasTable('sighting_reports')) {
            return;
        }
        Schema::table('sighting_reports', function (Blueprint $table) {
            if (!Schema::hasColumn('sighting_reports', 'reporter_email')) {
                $table->string('reporter_email')->nullable()->after('reporter_phone');
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('sighting_reports')) {
            return;
        }
        Schema::table('sighting_reports', function (Blueprint $table) {
            if (Schema::hasColumn('sighting_reports', 'reporter_email')) {
                $table->dropColumn('reporter_email');
            }
        });
    }
};


