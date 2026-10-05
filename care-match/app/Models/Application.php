<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Database\Factories\ApplicationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// 간병인의 지원. care_requests와 users(간병인) 사이의 중간 엔티티.
class Application extends Model
{
    /** @use HasFactory<ApplicationFactory> */
    use HasFactory;

    // care_request_id, caregiver_id, status는 관계 메서드나 비즈니스 로직으로만 설정한다.
    protected $fillable = ['message'];

    protected $attributes = [
        'status' => ApplicationStatus::Pending->value,
    ];

    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
        ];
    }

    // 어떤 요청에 대한 지원인가 (N:1) — FK care_request_id 자동 추론
    public function careRequest(): BelongsTo
    {
        return $this->belongsTo(CareRequest::class);
    }

    // 누가 지원했나 (N:1) — 메서드명 caregiver → FK caregiver_id 추론, 대상은 User
    public function caregiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'caregiver_id');
    }
}
