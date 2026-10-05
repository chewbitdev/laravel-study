<?php

namespace App\Enums;

enum UserRole: string
{
    case Guardian = 'GUARDIAN';     // 보호자: 간병 요청을 올린다
    case Caregiver = 'CAREGIVER';   // 간병인: 요청에 지원한다

    public function label(): string
    {
        return match ($this) {
            self::Guardian => '보호자',
            self::Caregiver => '간병인',
        };
    }
}
