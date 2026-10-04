<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateCareRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // sometimes: 요청에 그 키가 있을 때만 검증 → 부분 수정(PATCH)
    public function rules(): array
    {
        return [
            'patient_name' => ['sometimes', 'required', 'string', 'max:50'],
            'location' => ['sometimes', 'required', 'string', 'max:255'],
            'start_date' => ['sometimes', 'required', 'date'],
            'end_date' => ['sometimes', 'required', 'date'],
        ];
    }

    // 규칙 검사가 끝난 뒤 실행되는 추가 검증 (클래스 레벨 커스텀 Validator와 비슷)
    // Day 1의 한계: end_date만 보내면 기존 start_date와 비교할 수 없었다.
    // → 요청에 없는 값은 DB에 저장된 기존 값으로 채워서 비교한다.
    public function after(): array
    {
        return [
            function (Validator $validator) {
                // 날짜 형식부터 틀렸다면 비교할 필요 없음 (이미 에러가 담겨 있음)
                if ($validator->errors()->hasAny(['start_date', 'end_date'])) {
                    return;
                }

                // 라우트 모델 바인딩으로 이미 조회된 모델을 꺼낸다 ({care_request})
                $careRequest = $this->route('care_request');

                // $this->date(): 요청 값을 Carbon 날짜로. 키가 없으면 null → ?? 로 기존 값 사용
                $start = $this->date('start_date') ?? $careRequest->start_date;
                $end = $this->date('end_date') ?? $careRequest->end_date;

                if ($end->lt($start)) {   // lt = less than (isBefore)
                    $validator->errors()->add('end_date', '종료일은 시작일과 같거나 이후여야 합니다.');
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'patient_name' => '환자 이름',
            'location' => '장소',
            'start_date' => '시작일',
            'end_date' => '종료일',
        ];
    }
}
