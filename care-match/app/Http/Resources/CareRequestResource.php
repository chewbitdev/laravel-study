<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// 응답 DTO (CareRequestResponseDto.from(entity)).
// 모델을 그대로 반환하면 DB 컬럼이 전부 노출되고, 컬럼 이름을 바꾸면 API 응답도 같이 바뀐다.
// Resource로 "API 응답 모양"을 DB 구조와 분리한다.
class CareRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // $this->id 는 감싸고 있는 모델의 속성으로 위임된다 ($this->resource->id)
        return [
            'id' => $this->id,
            // 관계를 속성처럼 접근하면 그 순간 SELECT가 실행된다 (지연 로딩, JPA의 LAZY)
            // ⚠️ 목록에서 이 Resource를 15번 만들면 쿼리도 15번 → 3교시 N+1에서 다룬다
            'guardian' => [
                'id' => $this->guardian->id,
                'name' => $this->guardian->name,
            ],
            'patient_name' => $this->patient_name,
            'location' => $this->location,
            'start_date' => $this->start_date->toDateString(),
            'end_date' => $this->end_date->toDateString(),
            'days' => (int) $this->start_date->diffInDays($this->end_date) + 1,  // DB에 없는 계산 필드
            'status' => $this->status->value,          // enum → 'PENDING'
            'status_label' => $this->status->label(),  // enum 메서드 → '매칭 대기'
            'created_at' => $this->created_at->toIso8601String(),
            // updated_at은 일부러 노출하지 않음
        ];
    }
}
