<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// applications: 간병인이 간병 요청에 "지원"한 기록
//   care_requests 1 : N applications
//   users(간병인) 1 : N applications
// → care_requests와 users(간병인)의 N:M 관계를 풀어 주는 중간 테이블이다.
//   단순 조인 테이블이 아니라 status, message 같은 자체 데이터가 있어서 별도 엔티티로 둔다.
//   (JPA에서 @ManyToMany 대신 중간 엔티티를 만드는 것과 같은 판단)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            // 컬럼명이 care_request_id 이면 constrained()가 테이블명(care_requests)을 자동 추론한다.
            $table->foreignId('care_request_id')->constrained()->cascadeOnDelete();
            // caregiver_id → 이름으로 추론 불가(caregivers 테이블 없음) → 테이블을 직접 지정
            $table->foreignId('caregiver_id')->constrained('users')->cascadeOnDelete();
            $table->text('message')->nullable();               // 지원 메시지 (선택)
            $table->string('status', 20)->default('PENDING');
            $table->timestamps();

            // 같은 간병인이 같은 요청에 두 번 지원하지 못하게 (@Table(uniqueConstraints = ...))
            $table->unique(['care_request_id', 'caregiver_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
