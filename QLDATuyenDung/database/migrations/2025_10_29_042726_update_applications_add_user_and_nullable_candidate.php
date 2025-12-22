<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateApplicationsAddUserAndNullableCandidate extends Migration
{
    public function up()
    {

        // NOTE: change() requires doctrine/dbal
        Schema::table('applications', function (Blueprint $table) {
            if (Schema::hasColumn('applications', 'candidate_id')) {
                $table->unsignedBigInteger('candidate_id')->nullable()->change();
            }

            if (! Schema::hasColumn('applications', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('candidate_id');
            }
        });

        // add foreign keys (safe drop if exists)
        Schema::table('applications', function (Blueprint $table) {
            // drop existing fks if present (suppress exceptions)
            try {
                $table->dropForeign(['candidate_id']);
            } catch (\Throwable $e) {
            }
            try {
                $table->dropForeign(['user_id']);
            } catch (\Throwable $e) {
            }

            if (Schema::hasColumn('applications', 'candidate_id')) {
                $table->foreign('candidate_id')->references('id')->on('candidates')->onDelete('set null');
            }
            if (Schema::hasColumn('applications', 'user_id')) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            }
        });
    }

    public function down()
    {
        Schema::table('applications', function (Blueprint $table) {
            try {
                $table->dropForeign(['candidate_id']);
            } catch (\Throwable $e) {
            }
            try {
                $table->dropForeign(['user_id']);
            } catch (\Throwable $e) {
            }
        });

        Schema::table('applications', function (Blueprint $table) {
            if (Schema::hasColumn('applications', 'user_id')) {
                $table->dropColumn('user_id');
            }
            if (Schema::hasColumn('applications', 'candidate_id')) {
                $table->unsignedBigInteger('candidate_id')->nullable(false)->change();
            }
        });
    }
}
