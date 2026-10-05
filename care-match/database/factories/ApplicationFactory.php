<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\CareRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'care_request_id' => CareRequest::factory(),
            'caregiver_id' => User::factory()->caregiver(),
            'message' => fake()->randomElement([
                '경력 5년 차 간병인입니다. 성실히 돌보겠습니다.',
                '요양보호사 자격증 보유, 야간 간병 가능합니다.',
                '치매 환자 간병 경험이 많습니다.',
                null,
            ]),
        ];
    }
}
