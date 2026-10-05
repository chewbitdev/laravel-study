<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    // ── 관계 ─────────────────────────────────────────────
    // 관계는 "메서드"로 정의한다. JPA처럼 필드에 @OneToMany를 붙이지 않는다.

    // 보호자가 올린 간병 요청들
    // JPA: @OneToMany(mappedBy = "guardian") List<CareRequest> careRequests;
    // FK 이름이 규칙(user_id)과 달라서 두 번째 인자로 'guardian_id'를 알려준다.
    public function careRequests(): HasMany
    {
        return $this->hasMany(CareRequest::class, 'guardian_id');
    }

    // 간병인이 한 지원들
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'caregiver_id');
    }

    public function isGuardian(): bool
    {
        return $this->role === UserRole::Guardian;
    }

    public function isCaregiver(): bool
    {
        return $this->role === UserRole::Caregiver;
    }
}
