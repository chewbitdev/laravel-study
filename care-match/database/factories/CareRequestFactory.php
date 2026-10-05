<?php

namespace Database\Factories;

use App\Models\CareRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

// 테스트/개발용 가짜 데이터 생성기 (Java의 Fixture Monkey, Instancio 같은 역할)
/**
 * @extends Factory<CareRequest>
 */
class CareRequestFactory extends Factory
{
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('+1 day', '+30 days');

        return [
            // 관계 FK에 "팩토리"를 넣으면, 따로 지정하지 않았을 때 보호자를 자동으로 만들어 연결한다.
            'guardian_id' => User::factory()->guardian(),
            'patient_name' => fake('ko_KR')->name(),
            'location' => fake('ko_KR')->city(),
            'start_date' => $start,
            'end_date' => fake()->dateTimeBetween($start, (clone $start)->modify('+14 days')),
        ];
    }
}
