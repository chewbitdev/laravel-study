<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'care_request_id' => $this->care_request_id,
            // ⚠️ 지연 로딩: 지원자 목록에서 지원 1건마다 users 조회가 1번씩 → 3교시
            'caregiver' => [
                'id' => $this->caregiver->id,
                'name' => $this->caregiver->name,
            ],
            'message' => $this->message,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
