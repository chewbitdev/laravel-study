<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\CareRequest;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

// 개발용 초기 데이터 (Spring의 data.sql / CommandLineRunner로 넣는 더미 데이터)
// 실행: php artisan db:seed  또는  php artisan migrate:fresh --seed
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // 로그인 테스트용 고정 계정 (Day 4에서 사용, 비밀번호는 factory 기본값 'password')
        User::factory()->guardian()->create(['name' => '테스트보호자', 'email' => 'guardian@example.com']);
        User::factory()->caregiver()->create(['name' => '테스트간병인', 'email' => 'caregiver@example.com']);

        $guardians = User::factory()->guardian()->count(5)->create();
        $caregivers = User::factory()->caregiver()->count(10)->create();

        // 보호자마다 간병 요청 2~3건
        foreach ($guardians as $guardian) {
            CareRequest::factory()
                ->count(fake()->numberBetween(2, 3))
                ->for($guardian, 'guardian')   // guardian 관계로 연결 (guardian_id 자동 설정)
                ->create();
        }

        // 요청마다 서로 다른 간병인 2~4명이 지원 (unique 제약 때문에 중복 없이 뽑는다)
        foreach (CareRequest::all() as $careRequest) {
            $caregivers->random(fake()->numberBetween(2, 4))->each(
                fn (User $caregiver) => Application::factory()
                    ->for($careRequest)
                    ->for($caregiver, 'caregiver')
                    ->create()
            );
        }
    }
}
