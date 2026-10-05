<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    case Pending = 'PENDING';     // 지원함, 보호자 응답 대기
    case Accepted = 'ACCEPTED';   // 보호자가 수락 (Day 4: 트랜잭션 + 락)
    case Rejected = 'REJECTED';   // 다른 지원자가 수락되면 자동 거절

    public function label(): string
    {
        return match ($this) {
            self::Pending => '대기',
            self::Accepted => '수락',
            self::Rejected => '거절',
        };
    }
}
