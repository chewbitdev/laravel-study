<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 이미 실행된 create_users_table을 고치지 않고, "변경분"만 담은 새 마이그레이션을 추가한다.
// Flyway에서 V1을 고치지 않고 V2__add_role.sql을 추가하는 것과 같다.
return new class extends Migration
{
    public function up(): void
    {
        // Schema::create가 아니라 Schema::table → ALTER TABLE users
        Schema::table('users', function (Blueprint $table) {
            // 기존 행이 있을 수 있으므로 NOT NULL 컬럼을 추가할 때는 기본값이 필요하다.
            $table->string('role', 20)->default('GUARDIAN')->after('email');
        });
    }

    // 롤백(php artisan migrate:rollback) 시 up()을 정확히 되돌린다.
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
