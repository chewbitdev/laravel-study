<?php

namespace App\Http\Requests;

use App\Enums\CareStatus;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

// POST /api/care-requests/{care_request}/applications
class StoreApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;   // Day 4: 로그인한 간병인만
    }

    public function rules(): array
    {
        // 중첩 라우트의 부모 모델 (이미 바인딩되어 있음)
        $careRequest = $this->route('care_request');

        return [
            // 아직 로그인이 없으므로 간병인 id를 본문으로 받는다. (Day 4에서 $request->user()로 교체)
            'caregiver_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('role', UserRole::Caregiver->value),
                // 같은 요청에 이미 지원했는지: applications 테이블에서
                // care_request_id = 이 요청 AND caregiver_id = 입력값 인 행이 없어야 한다
                Rule::unique('applications', 'caregiver_id')->where('care_request_id', $careRequest->id),
            ],
            'message' => ['nullable', 'string', 'max:500'],
        ];
    }

    // 매칭 대기 중인 요청에만 지원할 수 있다
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->route('care_request')->status !== CareStatus::Pending) {
                    $validator->errors()->add('care_request', '매칭 대기 중인 요청에만 지원할 수 있습니다.');
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'caregiver_id' => '간병인',
            'message' => '지원 메시지',
        ];
    }

    public function messages(): array
    {
        return [
            'caregiver_id.unique' => '이미 지원한 요청입니다.',
        ];
    }
}
