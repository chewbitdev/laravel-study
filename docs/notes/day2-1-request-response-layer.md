# Day 2 - 1교시: 라우트 · 검증(FormRequest) · 응답(API Resource) · 페이징 · Enum

> 목표: Day 1의 "모든 걸 컨트롤러에" 구조를 Spring처럼 **요청 DTO / 응답 DTO / enum**으로 나누기
> Day 1 숙제였던 ① update 날짜 검증, ② 페이징, ③ 정렬 순서 문제를 해결한다.

---

## 0. 바뀐 구조 한눈에 보기

| 역할 | Spring | Laravel | 파일 |
|---|---|---|---|
| 요청 DTO + `@Valid` | `CreateDto` + Bean Validation | **FormRequest** | `app/Http/Requests/StoreCareRequestRequest.php`, `UpdateCareRequestRequest.php` |
| 응답 DTO | `ResponseDto.from(entity)` | **API Resource** | `app/Http/Resources/CareRequestResource.php` |
| 상태 enum | `enum` + `@Enumerated(STRING)` | **backed enum** + `casts` | `app/Enums/CareStatus.php` |
| 페이징 | `Pageable`, `Page<T>` | `paginate()` | 컨트롤러 `index` |

```bash
php artisan make:enum CareStatus --string          # ⚠️ app/CareStatus.php 에 생김 → app/Enums/ 로 옮김
php artisan make:request StoreCareRequestRequest
php artisan make:request UpdateCareRequestRequest
php artisan make:resource CareRequestResource
```

---

## 1. 라우트 심화 (개념)

Day 1에서는 `Route::apiResource` 한 줄만 썼다. 실무 `routes/api.php`에서 자주 보는 문법은 다음과 같다.

```php
// 개별 라우트: [컨트롤러::class, '메서드명'] 배열로 연결
Route::get('care-requests/{careRequest}', [CareRequestController::class, 'show']);

// 그룹: 공통 접두사, 미들웨어, 이름을 묶는다 (@RequestMapping을 클래스에 붙이는 느낌)
Route::prefix('v1')->middleware('auth:sanctum')->name('v1.')->group(function () {
    Route::apiResource('care-requests', CareRequestController::class);
    Route::post('care-requests/{careRequest}/accept', [MatchController::class, 'accept']);
});

// 일부 메서드만
Route::apiResource('care-requests', CareRequestController::class)->only(['index', 'show']);
Route::apiResource('care-requests', CareRequestController::class)->except(['destroy']);

// 파라미터 제약 (@GetMapping("/{id:\\d+}"))
Route::get('users/{id}', ...)->whereNumber('id');

// id 대신 다른 컬럼으로 바인딩
Route::get('care-requests/{careRequest:uuid}', ...);   // WHERE uuid = ?

// 중첩 리소스 (Day 3: 요청에 대한 지원 목록)
Route::apiResource('care-requests.applications', ApplicationController::class);
// → /care-requests/{care_request}/applications/{application}
```

> **회사 코드를 읽는 순서**: `php artisan route:list`로 URL과 컨트롤러 메서드를 확인하고 → `routes/api.php`에서 어떤 그룹이나 미들웨어에 묶여 있는지 확인한다.

---

## 2. FormRequest — 요청 DTO + 검증

```php
class StoreCareRequestRequest extends FormRequest
{
    public function authorize(): bool { return true; }       // 권한 검사
    public function rules(): array { return [...]; }          // 검증 규칙
    public function attributes(): array { return ['start_date' => '시작일', ...]; }  // 에러 메시지용 이름
}

// 컨트롤러
public function store(StoreCareRequestRequest $request) {
    CareRequest::create($request->validated());   // 검증 통과한 필드만
}
```
### 동작 순서
1. 라우팅 → 2. Laravel이 컨테이너에서 `StoreCareRequestRequest`를 만든다 → 3. `authorize()`가 false면 **403** → 4. `rules()` 실패면 **422** → 5. 모두 통과해야 컨트롤러 메서드가 실행된다.

Spring의 `@Valid @RequestBody CreateDto dto`와 같은 흐름이다. 컨트롤러 코드에는 검증 코드가 한 줄도 없다.

### ⚠️ 함정: `authorize()` 기본값은 `false`
`make:request`로 만든 파일은 `return false;`로 시작한다. 그대로 두면 **모든 요청이 403 Forbidden**이 된다. 처음 쓸 때 "검증 규칙은 맞는데 왜 403이지?"의 원인이 대부분 이것이다.

### `after()` — 여러 필드를 엮는 추가 검증
Day 1의 문제는 PATCH로 `end_date`만 보내면 기존 `start_date`와 비교할 수 없다는 것이었다.
```php
public function after(): array
{
    return [function (Validator $validator) {
        if ($validator->errors()->hasAny(['start_date', 'end_date'])) return;  // 형식 오류면 건너뜀

        $careRequest = $this->route('care_request');              // 바인딩된 모델
        $start = $this->date('start_date') ?? $careRequest->start_date;   // 요청 값이 없으면 DB 값
        $end   = $this->date('end_date')   ?? $careRequest->end_date;

        if ($end->lt($start)) {
            $validator->errors()->add('end_date', '종료일은 시작일과 같거나 이후여야 합니다.');
        }
    }];
}
```
- Spring의 클래스 레벨 커스텀 제약(`@ValidDateRange` + `ConstraintValidator`)에 해당한다.
- **형식 오류를 먼저 걸러내는 줄이 중요하다.** 이 줄이 없으면 `"abc"` 같은 값이 들어왔을 때 `$this->date()`가 파싱 예외를 던져서 **500**이 된다. after 훅은 규칙 검사가 실패해도 실행되기 때문이다.
- `$this->route('care_request')`: FormRequest 안에서도 라우트 파라미터(이미 바인딩된 모델)를 꺼낼 수 있다.

### 규칙 추가
| 규칙 | 의미 |
|---|---|
| `after_or_equal:today` | 오늘 이후 (과거 날짜로 간병 요청 불가) |
| `Rule::enum(CareStatus::class)` | enum에 정의된 값만 허용 |
| `between:1,100` | 숫자 범위 (`@Min(1) @Max(100)`) |

---

## 3. API Resource — 응답 DTO

```php
class CareRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'start_date' => $this->start_date->toDateString(),
            'days' => (int) $this->start_date->diffInDays($this->end_date) + 1,  // 계산 필드
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'created_at' => $this->created_at->toIso8601String(),
            // updated_at은 노출하지 않음
        ];
    }
}
```
### 왜 모델을 그대로 반환하지 않나?
- 모델을 그대로 반환하면 **모든 컬럼이 노출**된다. 나중에 `users` 테이블의 내부 컬럼 같은 것이 그대로 새어 나갈 수 있다.
- DB 컬럼 이름을 바꾸면 API 응답도 같이 바뀌어서 앱 클라이언트가 깨진다.
- Spring에서 Entity를 직접 반환하지 않고 ResponseDto로 변환하는 것과 같은 이유다.

### 응답 모양의 변화
```jsonc
// 단건: {"data": {...}} 로 감싸진다
{"data": {"id": 4, "patient_name": "이민수", "days": 3, "status": "PENDING", "status_label": "매칭 대기", ...}}

// 목록 + paginate: data + links + meta
{"data": [...],
 "links": {"first": "...?page=1", "last": "...?page=2", "prev": null, "next": "...?page=2"},
 "meta":  {"current_page": 1, "last_page": 2, "per_page": 2, "total": 4, ...}}
```
- `data`로 감싸는 동작은 `JsonResource::withoutWrapping()`으로 끌 수 있다. 회사 API 규격을 먼저 확인하자.
- `$this->id`는 감싼 모델의 속성으로 위임된다(`__get` 매직 메서드, 1교시에서 배운 것).

### 생성 시 201이 자동으로
```php
return new CareRequestResource($careRequest);   // store에서
```
`create()`로 방금 만든 모델은 `$model->wasRecentlyCreated === true`다. 이런 모델을 Resource로 반환하면 Laravel이 상태 코드를 **자동으로 201**로 정한다. Day 1처럼 `response()->json($x, 201)`을 쓰지 않아도 된다.

---

## 4. Enum + cast

```php
enum CareStatus: string {
    case Pending = 'PENDING'; case Matched = 'MATCHED';
    case InProgress = 'IN_PROGRESS'; case Done = 'DONE';
    public function label(): string { return match ($this) { self::Pending => '매칭 대기', ... }; }
}

// 모델
protected $attributes = ['status' => CareStatus::Pending->value];   // 원시 값 'PENDING'
protected function casts(): array { return ['status' => CareStatus::class]; }
```
- DB에는 `'PENDING'` 문자열로 저장되고, `$careRequest->status`로 꺼내면 `CareStatus::Pending` **객체**가 나온다. JPA의 `@Enumerated(EnumType.STRING)`과 같다.
- 그래서 `$careRequest->status === CareStatus::Pending`처럼 비교한다. 문자열 `'PENDING'`과 비교하면 false가 된다(1교시 `===`).
- 오타 방지, IDE 자동완성, `label()` 같은 동작을 enum에 모을 수 있다. Day 4의 상태 전이(`canTransitionTo()`)도 여기에 둔다.

---

## 5. 페이징과 동적 필터

```php
$filters = $request->validate([
    'status' => ['sometimes', Rule::enum(CareStatus::class)],
    'per_page' => ['sometimes', 'integer', 'between:1,100'],
]);

CareRequest::query()
    ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
    ->latest()          // ORDER BY created_at DESC
    ->latest('id')      //        , id DESC  ← 같은 초 생성 시 순서 보장
    ->paginate($filters['per_page'] ?? 15);
```
- `paginate(n)`은 COUNT 쿼리와 `LIMIT n OFFSET ...` 쿼리를 함께 실행한다. `?page=2`는 **자동으로** 읽는다(Spring `Pageable` 자동 바인딩과 같음).
- `when(값, 콜백)`: 값이 있을 때만 조건을 붙인다. `if`문으로 쿼리를 조립하는 코드를 체이닝으로 바꾼 것이다(QueryDSL `BooleanBuilder`, JPA Specification과 비슷).
- `per_page` 상한(100)을 두지 않으면 `?per_page=1000000` 한 번으로 DB가 힘들어진다.
- 대용량 무한 스크롤에는 `cursorPaginate()`(OFFSET 없이 마지막 id 기준)를 쓴다.

---

## 6. 테스트 결과

| # | 시나리오 | 결과 |
|---|---|---|
| 1 | 생성 → Resource 반환 | **201 자동**, `{"data": {...}}`, `days`, `status_label` 포함 |
| 2 | 과거 시작일 | 422, `The 시작일 field must be ...` (`attributes()`의 한글 이름 적용) |
| 3 | `?per_page=2` | data 2개 + `links` + `meta`(total 4, last_page 2), 같은 초 생성 → id 역순 정렬 |
| 4 | `?status=MATCHED` | MATCHED 1건만 |
| 5 | `?status=WRONG` | 422 (Rule::enum) |
| 6 | PATCH `end_date`만, 기존 시작일보다 앞 | 422, after() 커스텀 메시지 ✅ Day 1 숙제 해결 |
| 7 | PATCH `start_date: "abc"` | **422** (500이 아님, 형식 오류 가드) |
| 8 | PATCH 정상 | 200, days 재계산 |
| 9 | PATCH `status: DONE` | 무시됨 (`$fillable` + `validated()`) |

테스트용 MATCHED 데이터는 tinker로 만들었다.
```bash
php artisan tinker --execute '$c = App\Models\CareRequest::create([...]); $c->status = App\Enums\CareStatus::Matched; $c->save();'
```

---

## 7. 발견한 점: 시간대(timezone)가 UTC다

```
php artisan tinker → now() = 2026-10-04 15:25 (UTC)   ← 한국 시간으로는 2026-10-05 00:25
```
- `config/app.php`의 `'timezone' => 'UTC'`가 기본값이다.
- 그래서 `after_or_equal:today`의 "오늘"은 **UTC 기준**이다. 한국 시간 00~09시 사이에는 한국 기준 "어제" 날짜도 통과한다.
- 선택지
  1. `config/app.php`의 timezone을 `Asia/Seoul`로 바꾼다(국내 서비스에서 흔함). DB에 저장되는 시각도 KST가 된다.
  2. 저장은 UTC로 두고, 비교할 때만 `today('Asia/Seoul')`을 쓴다(글로벌 서비스 방식).
- **실무 체크 포인트**: 회사 프로젝트의 `config/app.php` timezone과 DB 서버의 시간대를 확인하자. 둘이 다르면 날짜 버그가 생긴다. → Day 3에서 결정한다.

---

## 8. 한글 에러 메시지는?
지금은 필드 이름만 한글이고 문장은 영어다(`The 시작일 field must be...`). 문장까지 한국어로 하려면 `.env`에 `APP_LOCALE=ko`를 설정하고 `lang/ko/validation.php` 번역 파일을 둔다(`php artisan lang:publish` 후 번역, 또는 커뮤니티 패키지 사용). 회사 프로젝트에 `lang/ko/`가 있는지 확인해 보자.
