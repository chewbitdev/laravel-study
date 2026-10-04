<?php

namespace App\Enums;

// Java의 enum CareStatus { PENDING("대기"), ... } 와 같다.
// ": string"은 backed enum. 각 case가 DB에 저장될 문자열 값을 가진다.
enum CareStatus: string
{
    case Pending = 'PENDING';
    case Matched = 'MATCHED';
    case InProgress = 'IN_PROGRESS';
    case Done = 'DONE';

    // enum에도 메서드를 둘 수 있다 (Java enum의 메서드와 같음)
    public function label(): string
    {
        return match ($this) {
            self::Pending => '매칭 대기',
            self::Matched => '매칭 완료',
            self::InProgress => '간병 중',
            self::Done => '종료',
        };
    }
}
