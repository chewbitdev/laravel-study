<?php

namespace App\Models;

use App\Enums\CareStatus;
use Database\Factories\CareRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// JPA라면 @Entity @Table(name = "care_requests") + CareRequestRepository 두 개가 필요하다.
// Eloquent는 이 클래스 하나가 둘 다 맡는다(Active Record).
// 테이블 이름은 클래스명 CareRequest → snake_case 복수형 care_requests 로 자동 추론된다.
class CareRequest extends Model
{
    /** @use HasFactory<CareRequestFactory> */
    use HasFactory;   // CareRequest::factory() 를 쓸 수 있게 해 주는 trait

    // 대량 할당(mass assignment) 허용 필드 = 화이트리스트.
    // CareRequest::create($request->all()) 처럼 배열을 통째로 넣을 때 여기 있는 키만 들어간다.
    // status는 일부러 뺐다 → 클라이언트가 {"status": "DONE"}을 보내도 무시된다.
    // guardian_id도 뺐다 → 관계 메서드($guardian->careRequests()->create())로만 설정한다.
    // Spring에서 Entity 대신 요청 DTO에 필드를 골라 두는 것과 같은 목적이다.
    // (Laravel 13에서는 클래스 위에 #[Fillable([...])] Attribute로도 쓸 수 있다)
    protected $fillable = [
        'patient_name',
        'location',
        'start_date',
        'end_date',
    ];

    // new CareRequest() 했을 때 PHP 객체의 기본값 (필드 초기값 private String status = "PENDING";)
    // DB default만 있으면 저장 직후 $careRequest->status가 null로 보이므로 모델에도 둔다.
    // $attributes에는 DB에 들어갈 "원시 값"을 넣어야 해서 ->value 를 쓴다.
    protected $attributes = [
        'status' => CareStatus::Pending->value,
    ];

    // 컬럼 값을 꺼낼 때 타입 변환 (JPA의 LocalDate 매핑 / AttributeConverter)
    protected function casts(): array
    {
        return [
            'start_date' => 'date:Y-m-d',   // Carbon 날짜 객체로, JSON에는 2026-10-12 형식으로
            'end_date' => 'date:Y-m-d',
            'status' => CareStatus::class,  // 'PENDING' ↔ CareStatus::Pending (@Enumerated(EnumType.STRING))
        ];
    }

    // ── 관계 ─────────────────────────────────────────────

    // 이 요청을 올린 보호자 (N:1)
    // JPA: @ManyToOne(fetch = LAZY) @JoinColumn(name = "guardian_id") User guardian;
    // 메서드 이름이 guardian → 기본 FK는 guardian_id 로 추론된다.
    // 다만 연결 대상 클래스(User)는 이름으로 추론할 수 없어서 직접 지정한다.
    public function guardian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guardian_id');
    }

    // 이 요청에 들어온 지원들 (1:N)
    // FK는 규칙대로 care_request_id → 인자 생략 가능
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }
}
