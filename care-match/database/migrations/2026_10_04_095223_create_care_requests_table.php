<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Flyway의 V2__create_care_requests.sql 역할. SQL 대신 PHP로 스키마를 적는다.
        Schema::create('care_requests', function (Blueprint $table) {
            $table->id();                                   // BIGINT UNSIGNED PK AUTO_INCREMENT (@Id @GeneratedValue)
            $table->string('patient_name', 50);             // VARCHAR(50) NOT NULL (@Column(length = 50, nullable = false))
            $table->string('location');                     // VARCHAR(255) NOT NULL (기본 길이 255)
            $table->date('start_date');                     // DATE (LocalDate)
            $table->date('end_date');
            $table->string('status', 20)->default('PENDING'); // DB 레벨 기본값
            $table->timestamps();                           // created_at, updated_at (@CreatedDate, @LastModifiedDate)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('care_requests');
    }
};
