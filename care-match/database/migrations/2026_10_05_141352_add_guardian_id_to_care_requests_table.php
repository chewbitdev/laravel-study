<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// care_requests N : 1 users(보호자)
// JPA: @ManyToOne @JoinColumn(name = "guardian_id") private User guardian;
return new class extends Migration
{
    public function up(): void
    {
        // 운영 DB라면 NOT NULL FK를 한 번에 추가할 수 없다 (기존 행에 넣을 값이 없음).
        // 정석은 3단계: ① nullable로 추가 → ② 기존 행에 값 채우기 → ③ NOT NULL로 변경
        // 학습용이라 ②는 건너뛰고(migrate:fresh로 데이터를 비움) ①③만 보여준다.

        // ① nullable FK 추가
        Schema::table('care_requests', function (Blueprint $table) {
            // foreignId: BIGINT UNSIGNED 컬럼
            // constrained('users'): FOREIGN KEY (guardian_id) REFERENCES users(id)
            // cascadeOnDelete: 보호자가 삭제되면 그 요청도 삭제 (ON DELETE CASCADE)
            $table->foreignId('guardian_id')
                ->nullable()
                ->after('id')
                ->constrained('users')
                ->cascadeOnDelete();
        });

        // ③ NOT NULL로 변경 (->change(): 기존 컬럼 정의 수정)
        Schema::table('care_requests', function (Blueprint $table) {
            $table->foreignId('guardian_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('care_requests', function (Blueprint $table) {
            $table->dropForeign(['guardian_id']);   // FK 제약 먼저 제거
            $table->dropColumn('guardian_id');
        });
    }
};
