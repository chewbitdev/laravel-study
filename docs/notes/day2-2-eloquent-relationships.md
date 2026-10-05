# Day 2 - 2교시: Eloquent 관계 (hasMany / belongsTo)

> 목표: users · care_requests · applications 세 테이블을 관계로 연결하고, JPA 연관관계와 비교하기
> 이번 교시에 마이그레이션 실패를 직접 겪고 원인을 확인했다.

---

## 0. ERD

```
users (보호자/간병인)
 ├─ id
 ├─ role  GUARDIAN | CAREGIVER          ← 이번에 추가
 │
 ├──< care_requests.guardian_id         (보호자 1 : N 간병 요청)
 └──< applications.caregiver_id         (간병인 1 : N 지원)

care_requests
 ├─ id, guardian_id(FK), patient_name, location, start_date, end_date, status
 └──< applications.care_request_id      (요청 1 : N 지원)

applications                            ← 이번에 생성
 ├─ id, care_request_id(FK), caregiver_id(FK), message, status(PENDING|ACCEPTED|REJECTED)
 └─ UNIQUE(care_request_id, caregiver_id)   같은 요청에 중복 지원 불가
```
`applications`는 care_requests와 간병인 사이 **N:M 관계를 푸는 중간 엔티티**다. status와 message 같은 자체 데이터가 있어서 `@ManyToMany` 조인 테이블이 아니라 별도 엔티티로 둔다. JPA에서 중간 엔티티를 만드는 판단과 같다.

---

## 1. 마이그레이션 — 기존 테이블 변경

### 컬럼 추가: `Schema::table`
```php
// 2026_10_05_141351_add_role_to_users_table.php
Schema::table('users', function (Blueprint $table) {      // create가 아니라 table → ALTER TABLE
    $table->string('role', 20)->default('GUARDIAN')->after('email');
});
// down(): $table->dropColumn('role');
```
- **이미 실행한 `create_users_table`은 고치지 않는다.** 변경분만 담은 새 마이그레이션을 추가한다(Flyway에서 V1을 고치지 않고 V2를 추가하는 것과 같다).
- 기존 행이 있을 수 있으므로 NOT NULL 컬럼에는 기본값이 필요하다.

### FK 추가
```php
$table->foreignId('guardian_id')        // BIGINT UNSIGNED
      ->constrained('users')            // FOREIGN KEY REFERENCES users(id)
      ->cascadeOnDelete();              // ON DELETE CASCADE

$table->foreignId('care_request_id')->constrained();   // 이름 규칙으로 care_requests 테이블 자동 추론
```
| Laravel | SQL / JPA |
|---|---|
| `foreignId('x_id')` | `BIGINT UNSIGNED x_id` |
| `->constrained('users')` | `FOREIGN KEY (x_id) REFERENCES users(id)` |
| `->cascadeOnDelete()` | `ON DELETE CASCADE` (JPA `cascade = REMOVE`와 달리 **DB가** 삭제) |
| `->nullOnDelete()` | `ON DELETE SET NULL` |
| `$table->unique([a, b])` | 복합 UNIQUE (`@Table(uniqueConstraints = ...)`) |
| `->change()` | 기존 컬럼 정의 변경 (`ALTER COLUMN`) |

---

## 2. 🔥 실제로 겪은 마이그레이션 실패

`guardian_id`를 NOT NULL FK로 추가하려 했다. 그때 DB에는 이미 간병 요청 5건이 있었다.
```
2026_10_05_141351_add_role_to_users_table ............ DONE
2026_10_05_141352_add_guardian_id_to_care_requests ... FAIL
  SQLSTATE[23000]: NOT NULL constraint failed: __temp__care_requests.guardian_id
```
### 원인
기존 5건에는 넣을 보호자 id가 없다. 그래서 NOT NULL로 바꾸는 순간 제약 위반이 난다.

### 더 중요한 발견: 반쯤 적용된 상태로 남는다
```
migrate:status → add_guardian_id ... Pending   (실행 안 된 것으로 기록)
Schema::hasColumn('care_requests', 'guardian_id') → yes   (그런데 컬럼은 이미 생김)
```
- 마이그레이션 하나 안에 단계가 두 개(① nullable 추가 → ③ NOT NULL 변경)였는데, ①만 반영되고 ③에서 실패했다.
- 이 상태로 다시 `migrate`하면 ①에서 "컬럼이 이미 있다"는 에러가 난다. 손으로 정리해야 한다.
- **MySQL은 DDL이 자동 커밋되므로**(트랜잭션 롤백 불가) 운영에서도 똑같은 일이 생긴다.

### 운영 DB에서의 정석
NOT NULL FK를 기존 테이블에 추가할 때는 단계를 나눈다. 가능하면 **마이그레이션 파일도 단계마다 분리**한다.
1. nullable 컬럼 추가
2. 기존 행에 값 채우기(데이터 마이그레이션 또는 배치)
3. NOT NULL로 변경

### 이번 해결 (개발 DB이므로)
```bash
php artisan migrate:fresh --seed   # 모든 테이블 DROP → 마이그레이션 처음부터 → 시더 실행
```
⚠️ `migrate:fresh`는 **모든 데이터를 지운다.** 로컬 개발 DB에서만 쓴다. 운영 DB 접속 정보가 담긴 `.env`에서는 절대 실행하지 않는다.

---

## 3. 관계 정의 — 필드가 아니라 메서드

```php
class User extends Authenticatable {
    public function careRequests(): HasMany {               // 보호자 1 : N 요청
        return $this->hasMany(CareRequest::class, 'guardian_id');
    }
    public function applications(): HasMany {               // 간병인 1 : N 지원
        return $this->hasMany(Application::class, 'caregiver_id');
    }
}

class CareRequest extends Model {
    public function guardian(): BelongsTo {                 // N : 1 보호자
        return $this->belongsTo(User::class, 'guardian_id');
    }
    public function applications(): HasMany {               // 1 : N 지원
        return $this->hasMany(Application::class);          // FK care_request_id 자동 추론
    }
}

class Application extends Model {
    public function careRequest(): BelongsTo { return $this->belongsTo(CareRequest::class); }
    public function caregiver(): BelongsTo   { return $this->belongsTo(User::class, 'caregiver_id'); }
}
```

| Eloquent | JPA | FK 위치 |
|---|---|---|
| `belongsTo` | `@ManyToOne` | **내 테이블**에 FK가 있다 (`care_requests.guardian_id`) |
| `hasMany` | `@OneToMany(mappedBy)` | **상대 테이블**에 FK가 있다 |
| `hasOne` | `@OneToOne(mappedBy)` | 상대 테이블 |
| `belongsToMany` | `@ManyToMany` | 피벗(조인) 테이블 |

**FK가 어느 쪽에 있느냐로 `belongsTo`와 `hasMany`를 고른다.** 헷갈리면 "FK를 가진 쪽이 belongsTo"라고 기억한다.

### 이름 규칙과 직접 지정
- `belongsTo(CareRequest::class)` → FK `care_request_id`(메서드명 + `_id`) 자동 추론
- `hasMany(Application::class)` → FK `care_request_id`(내 모델명 snake + `_id`) 자동 추론
- 규칙과 다른 이름(`guardian_id`, `caregiver_id`)은 **두 번째 인자로 직접 지정**한다. 역할 이름으로 FK를 짓는 실무 코드에서는 거의 항상 지정하게 된다.

### JPA와의 차이
- 관계가 필드가 아니라 **메서드**다. 양방향 매핑을 맞추거나 연관관계 편의 메서드를 만들 필요가 없다.
- 기본이 **지연 로딩(LAZY)**이다. 프록시 객체가 아니라 처음 접근할 때 쿼리를 실행하고 결과를 모델에 캐싱한다.
- 영속성 컨텍스트가 없다. `$a->careRequest`와 `$b->careRequest`가 같은 행이어도 **서로 다른 PHP 객체**다(1차 캐시 없음).

---

## 4. 괄호 있음 vs 없음 — 가장 중요한 차이

```php
$guardian->careRequests()    // Relations\HasMany 객체 → 쿼리 빌더. 아직 실행 안 함
$guardian->careRequests      // Eloquent\Collection     → 실행된 결과 (동적 속성, __get)
```
| 쓰는 법 | 실행되는 SQL | 용도 |
|---|---|---|
| `$g->careRequests()->count()` | `SELECT COUNT(*) ... WHERE guardian_id = ?` | DB에서 바로 집계 |
| `$g->careRequests->count()` | `SELECT * ... WHERE guardian_id = ?` 후 PHP에서 셈 | 이미 불러온 목록 재사용 |
| `$g->careRequests()->where('status', 'PENDING')->get()` | 조건 추가 | 관계 + 추가 조건 |
| `$g->careRequests()->create([...])` | `INSERT ... guardian_id = $g->id` | 관계로 생성 |

tinker 확인 결과:
```
careRequests() 타입: Illuminate\Database\Eloquent\Relations\HasMany
careRequests 타입:   Illuminate\Database\Eloquent\Collection
```

---

## 5. 관계로 저장하기

```php
// hasMany 쪽에서 생성: FK(guardian_id)가 자동으로 채워진다
$careRequest = $guardian->careRequests()->create($request->safe()->except('guardian_id'));

// make() + associate() + save(): 객체를 만들고 → belongsTo 관계를 설정하고 → 저장
$application = $careRequest->applications()->make(['message' => '...']);  // care_request_id 설정
$application->caregiver()->associate($caregiver);                         // caregiver_id 설정
$application->save();
```
- 이렇게 하면 FK 컬럼(`guardian_id`, `care_request_id`, `caregiver_id`)을 **`$fillable`에 넣지 않아도 된다.** 클라이언트가 FK를 조작할 여지를 줄인다.
- `associate()`는 JPA의 `application.setCaregiver(caregiver)`와 같다.
- `$request->safe()->except('a')`나 `->only('a')`로 검증된 값의 일부만 꺼낸다.

---

## 6. Factory · Seeder — 개발용 데이터

```php
// UserFactory: state로 역할 변형
public function caregiver(): static {
    return $this->state(fn () => ['role' => UserRole::Caregiver]);
}

// CareRequestFactory: FK에 팩토리를 넣으면 부모를 자동 생성
'guardian_id' => User::factory()->guardian(),

// Seeder
CareRequest::factory()->count(3)->for($guardian, 'guardian')->create();   // 특정 부모에 연결
Application::factory()->for($careRequest)->for($caregiver, 'caregiver')->create();
```
- Factory는 테스트 픽스처 생성기다(Instancio, Fixture Monkey와 비슷). `fake('ko_KR')`로 한국어 이름과 도시를 만든다.
- 모델에 `use HasFactory;` trait가 있어야 `Model::factory()`를 쓸 수 있다.
- Seeder 결과: 사용자 17명(보호자 6, 간병인 11), 요청 12건, 지원 35건
- 고정 테스트 계정: `guardian@example.com`(id 1), `caregiver@example.com`(id 2), 비밀번호 `password` → Day 4 로그인에 사용

---

## 7. 중첩 라우트와 지원 API

```php
Route::apiResource('care-requests.applications', ApplicationController::class)->only(['index', 'store']);
```
```
GET  api/care-requests/{care_request}/applications   → index(CareRequest $careRequest)
POST api/care-requests/{care_request}/applications   → store(StoreApplicationRequest $request, CareRequest $careRequest)
```

### StoreApplicationRequest의 검증
```php
'caregiver_id' => [
    'required', 'integer',
    Rule::exists('users', 'id')->where('role', 'CAREGIVER'),                          // 간병인만
    Rule::unique('applications', 'caregiver_id')->where('care_request_id', $careRequest->id),  // 중복 지원 금지
],
// after(): 요청 상태가 PENDING일 때만 지원 가능
```
- `Rule::exists(...)->where(...)`: "존재하고 + 조건도 맞는가"를 검사한다. 존재 여부와 역할을 한 번에 본다.
- `Rule::unique(...)->where(...)`: 복합 unique를 검증 단계에서 미리 확인한다. 친절한 에러 메시지를 주기 위해서다.
- `messages()`: 특정 규칙의 메시지를 바꾼다(`'caregiver_id.unique' => '이미 지원한 요청입니다.'`).

### 검증 unique와 DB unique, 둘 다 필요한 이유
- 검증 단계의 unique 검사와 INSERT는 **원자적이지 않다.** 같은 간병인이 요청을 동시에 두 번 보내면 둘 다 검증을 통과할 수 있다.
- 그래서 **최종 방어선은 DB의 UNIQUE 제약**이다. 실습 9번에서 검증을 우회해 중복 INSERT를 시도했더니 `UniqueConstraintViolationException`이 발생했다.
- 이 예외를 잡지 않으면 500이 된다. 동시성 처리는 Day 4(트랜잭션 + 락)에서 다룬다.

---

## 8. 테스트 결과

| # | 시나리오 | 결과 |
|---|---|---|
| 1 | 보호자(id 1)로 요청 생성 | 201, `guardian: {id: 1, name: 테스트보호자}` |
| 2 | 간병인 id를 guardian_id로 | 422 `The selected 보호자 is invalid.` |
| 3 | 요청#1 지원자 목록 | 200, 지원자 2명 |
| 4 | 테스트간병인 지원 | 201 |
| 5 | 같은 요청에 재지원 | 422 `이미 지원한 요청입니다.` |
| 6 | 보호자가 지원 | 422 `The selected 간병인 is invalid.` |
| 7 | MATCHED 요청에 지원 | 422 `매칭 대기 중인 요청에만 지원할 수 있습니다.` |
| 8 | 없는 요청(999)에 지원 | 404 (부모 Route Model Binding) |
| 9 | 검증 우회 중복 INSERT / 요청 삭제 | `UniqueConstraintViolationException` / 지원 3건 CASCADE 삭제 |

---

## 9. ⚠️ 숨겨 둔 문제 → 3교시

`CareRequestResource`와 `ApplicationResource`는 `$this->guardian->name`, `$this->caregiver->name`처럼 **관계를 지연 로딩**한다.
- 목록 15건을 반환하면: 요청 목록 쿼리 1번 + 보호자 조회 **15번**
- 이것이 **N+1 문제**다. 3교시에서 쿼리 로그로 직접 세어 보고 `with()`로 고친다.
