<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// 요청 DTO + @Valid 를 한 클래스로 합친 것.
// 컨트롤러 파라미터에 이 타입을 쓰면, 컨트롤러 메서드가 실행되기 "전에" 검증이 끝난다.
// 실패하면 컨트롤러에 들어오지도 않고 422 응답이 나간다.
class StoreCareRequestRequest extends FormRequest
{
    // 이 요청을 보낼 권한이 있는가? (Spring Security의 @PreAuthorize 자리)
    // ⚠️ make:request로 만들면 기본값이 false → 그대로 두면 모든 요청이 403.
    // 인증은 Day 4에서 붙이므로 지금은 누구나 허용.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_name' => ['required', 'string', 'max:50'],
            'location' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ];
    }

    // 에러 메시지에 쓰일 필드 이름 (기본은 "patient name")
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
