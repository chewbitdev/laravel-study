<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// JPA라면 @Entity @Table(name = "care_requests") + CareRequestRepository 두 개가 필요하다.
// Eloquent는 이 클래스 하나가 둘 다 맡는다(Active Record).
// 테이블 이름은 클래스명 CareRequest → snake_case 복수형 care_requests 로 자동 추론된다.
class CareRequest extends Model
{
    // 대량 할당(mass assignment) 허용 필드 = 화이트리스트.
    // CareRequest::create($request->all()) 처럼 배열을 통째로 넣을 때 여기 있는 키만 들어간다.
    // status는 일부러 뺐다 → 클라이언트가 {"status": "DONE"}을 보내도 무시된다.
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
    protected $attributes = [
        'status' => 'PENDING',
    ];

    // 컬럼 값을 꺼낼 때 타입 변환 (JPA의 LocalDate 매핑 / AttributeConverter)
    protected function casts(): array
    {
        return [
            'start_date' => 'date:Y-m-d',   // Carbon 날짜 객체로, JSON에는 2026-10-12 형식으로
            'end_date' => 'date:Y-m-d',
        ];
    }
}
