<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Add user_type_id as nullable first (we'll set defaults later)
            if (!Schema::hasColumn('users', 'user_type_id')) {
                $table->foreignId('user_type_id')->nullable()->constrained('user_types')->onDelete('cascade');
            }

            if (!Schema::hasColumn('users', 'sso_id')) {
                $table->string('sso_id')->nullable()->unique();
            }

            if (!Schema::hasColumn('users', 'sso_provider')) {
                $table->string('sso_provider')->nullable();
            }

            if (!Schema::hasColumn('users', 'sso_attributes')) {
                $table->json('sso_attributes')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            // Safely remove columns if they exist
            $columns = ['user_type_id', 'sso_id', 'sso_provider', 'sso_attributes'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    if ($column === 'user_type_id') {
                        $table->dropForeign(['user_type_id']);
                    }
                    $table->dropColumn($column);
                }
            }
        });
    }
};