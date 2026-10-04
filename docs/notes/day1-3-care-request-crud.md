# Day 1 - 3교시: CareRequest CRUD API

> 목표: Migration → Model → Controller → Route 순서로 REST API 하나를 끝까지 만들어 보기
> 코드 위치: `care-match/` 아래 (각 파일에 Spring 대응 주석이 달려 있음)

---

## 0. 전체 그림 — Spring과 파일 대응

| 역할 | Spring Boot | Laravel | 파일 |
|---|---|---|---|
| 테이블 정의 | Flyway SQL / `ddl-auto` | Migration | `database/migrations/..._create_care_requests_table.php` |
| 엔티티 | `@Entity` | Eloquent Model | `app/Models/CareRequest.php` |
| DB 접근 | `JpaRepository` | **모델 자체** (`CareRequest::find`) | 〃 |
| 요청 DTO + 검증 | `@Valid` + DTO | `$request->validate([...])` | 컨트롤러 안 |
| 컨트롤러 | `@RestController` | Controller | `app/Http/Controllers/CareRequestController.php` |
| URL 매핑 | `@GetMapping` 등 | `Route::apiResource` | `routes/api.php` |
| JSON 직렬화 | Jackson | 모델 → 배열 → JSON 자동 | |

---

## 1. 파일 생성기

```bash
php artisan make:model CareRequest -mcr --api
```
| 옵션 | 생성 파일 |
|---|---|
| `-m` | migration |
| `-c` | controller |
| `-r` | resource controller (CRUD 메서드 뼈대) |
| `--api` | 화면용 `create`/`edit` 메서드를 뺀 API용 5개 메서드만 |

**이름 규칙(Convention over Configuration)** 덕분에 설정이 필요 없다.
- 모델 `CareRequest` (단수, PascalCase)
- 테이블 `care_requests` (복수, snake_case) ← 자동 추론, `@Table` 불필요
- 컨트롤러 `CareRequestController`

---

## 2. Migration — 테이블 정의

```php
Schema::create('care_requests', function (Blueprint $table) {
    $table->id();                                     // BIGINT PK AUTO_INCREMENT
    $table->string('patient_name', 50);               // VARCHAR(50) NOT NULL
    $table->string('location');                       // VARCHAR(255) NOT NULL
    $table->date('start_date');                       // DATE
    $table->date('end_date');
    $table->string('status', 20)->default('PENDING'); // DEFAULT 'PENDING'
    $table->timestamps();                             // created_at, updated_at
});
```
- **기본은 NOT NULL**이다. null을 허용하려면 `->nullable()`을 붙인다. JPA `@Column`은 기본이 nullable이라 반대다.
- `up()` = 적용, `down()` = 되돌리기(`php artisan migrate:rollback`). Flyway는 기본적으로 롤백이 없다.
- 실행한 마이그레이션은 `migrations` 테이블에 기록된다(Flyway의 `flyway_schema_history`). **이미 실행한 마이그레이션 파일은 고치지 말고, 새 마이그레이션을 만든다.** (Day 3)

```bash
php artisan migrate          # 아직 실행 안 된 것만 실행
php artisan migrate:status   # 상태 확인
```

---

## 3. Model — `$fillable`, `$attributes`, `casts`

```php
class CareRequest extends Model
{
    protected $fillable = ['patient_name', 'location', 'start_date', 'end_date'];
    protected $attributes = ['status' => 'PENDING'];
    protected function casts(): array {
        return ['start_date' => 'date:Y-m-d', 'end_date' => 'date:Y-m-d'];
    }
}
```

### `$fillable` — 대량 할당(Mass Assignment) 화이트리스트
`CareRequest::create($배열)`이나 `$model->update($배열)`처럼 **배열을 통째로** 넣을 때, `$fillable`에 있는 키만 반영한다.

```bash
# 클라이언트가 status를 몰래 보내도
POST {"patient_name":"홍길동", ..., "status":"DONE"}
# → 응답 status: "PENDING"  (무시됨)
```
- **왜 필요한가?** `$fillable`이 없으면 `CareRequest::create($request->all())` 한 줄로 공격자가 `status`, `is_admin`, `user_id` 같은 민감한 컬럼을 마음대로 바꿀 수 있다.
- Spring에서는 Entity를 직접 `@RequestBody`로 받지 않고 **요청 DTO에 받을 필드만 정의**해서 막는다. `$fillable`은 그 역할을 모델 쪽에서 한다.
- 반대 개념인 블랙리스트 `$guarded = ['id']`도 있다. 회사 코드에서 `$guarded = [];`(전부 허용)를 보면 주의해서 보자.
- 여기서는 `validate()`의 반환값(검증된 필드만)을 넣기 때문에 **이중 방어**가 된다.

### `$attributes` — 모델 객체의 기본값
- 마이그레이션의 `->default('PENDING')`은 **DB**의 기본값이다.
- `$attributes`는 **PHP 객체**의 기본값이다. 이게 없으면 `create()` 직후 응답에 `status`가 빠진다. DB가 채운 값을 다시 읽어 오지 않기 때문이다.
- JPA의 `private String status = "PENDING";` 필드 초기화와 같다.

### `casts` — 타입 변환
- DB의 `'2026-10-12'` 문자열을 **Carbon**(Java의 `LocalDate`/`LocalDateTime`에 해당하는 날짜 라이브러리) 객체로 바꾼다.
- `date:Y-m-d`는 JSON으로 내보낼 때의 형식이다.

---

## 4. Controller — 5개 메서드

### index — 목록
```php
public function index() {
    return CareRequest::latest()->get();   // ORDER BY created_at DESC
}
```
- 모델이나 컬렉션을 `return`하면 Laravel이 자동으로 JSON 응답(200)으로 바꾼다.
- `latest()`는 **`created_at`만** 기준으로 정렬한다. 같은 초에 생성된 행은 순서가 보장되지 않는다(실습에서 id 1이 2보다 먼저 나옴). 확실히 하려면 `->latest()->latest('id')`처럼 정렬 기준을 하나 더 둔다.
- 실무에서는 `get()` 대신 `paginate(20)`을 쓴다(Spring `Pageable`). (Day 2)

### store — 생성
```php
$validated = $request->validate([
    'patient_name' => ['required', 'string', 'max:50'],
    'end_date' => ['required', 'date', 'after_or_equal:start_date'],
    ...
]);
$careRequest = CareRequest::create($validated);
return response()->json($careRequest, 201);
```
| Laravel 규칙 | Bean Validation |
|---|---|
| `required` | `@NotNull` / `@NotBlank` |
| `string`, `max:50` | `@Size(max = 50)` |
| `date` | `LocalDate` 타입 변환 |
| `after_or_equal:start_date` | 커스텀 `@AssertTrue` (다른 필드와 비교) |
| `email`, `in:A,B`, `exists:users,id` | `@Email`, 커스텀, 커스텀 |

- 검증에 실패하면 **그 줄에서 바로 예외**가 나고, 422 응답이 나간다. `if (bindingResult.hasErrors())` 같은 코드가 필요 없다.
  ```json
  {"message": "The patient name field is required. (and 1 more error)",
   "errors": {"patient_name": ["The patient name field is required."],
              "end_date": ["The end date field must be a date after or equal to start date."]}}
  ```
- 규칙은 `'required|string|max:50'`처럼 파이프 문자열로도 쓸 수 있다. 회사 코드에서 둘 다 보게 된다.
- 검증 규칙이 길어지면 **FormRequest** 클래스로 분리한다(DTO + `@Valid`에 더 가까움). (Day 2)

### show — 단건 조회와 Route Model Binding
```php
public function show(CareRequest $careRequest) {
    return $careRequest;
}
```
- 라우트 `api/care-requests/{care_request}`의 `{care_request}` 자리 값(1)을 보고, Laravel이 **`CareRequest::findOrFail(1)`을 미리 실행해서** 파라미터로 넣어 준다.
- 동작 조건: 파라미터의 **타입 힌트가 모델**이고, **변수명이 라우트 파라미터 이름과 대응**해야 한다(`{care_request}` ↔ `$careRequest`).
- 없는 id면 컨트롤러에 들어오기 전에 `ModelNotFoundException`이 나고, **404**가 자동으로 응답된다.
- Spring으로 치면 `@PathVariable Long id` + `repository.findById(id).orElseThrow(NotFoundException::new)` + `@ControllerAdvice`의 404 처리까지를 한 번에 해 주는 셈이다. (Spring Data의 `@PathVariable("id") CareRequest req` 도메인 클래스 컨버터와 비슷하다.)

### update — 부분 수정
```php
'location' => ['sometimes', 'required', 'string', 'max:255'],
...
$careRequest->update($validated);
```
- `sometimes`는 **요청에 그 키가 있을 때만** 검증한다. 그래서 `{"location": "..."}`만 보내는 PATCH가 가능하다.
- `sometimes` + `required`는 "보내지 않는 건 괜찮지만, 보냈다면 빈 값이면 안 된다"는 뜻이다.
- `update()`는 `fill()` + `save()`다. 바뀐 컬럼만 UPDATE하고 `updated_at`도 갱신한다.
- **JPA와의 차이**: JPA는 트랜잭션 안에서 엔티티 값만 바꾸면 더티 체킹으로 자동 UPDATE된다. Eloquent는 **`save()`나 `update()`를 꼭 호출해야** 저장된다.
- 남은 한계: `end_date`만 보내면 `start_date`와의 앞뒤 관계를 검증하지 못한다 → Day 2에서 FormRequest로 개선한다.

### destroy — 삭제
```php
$careRequest->delete();
return response()->noContent();   // 204
```

---

## 5. Route — `apiResource`

```php
Route::apiResource('care-requests', CareRequestController::class);
```
`php artisan route:list --path=api/care` 결과:

| 메서드 | URI | 컨트롤러 메서드 |
|---|---|---|
| GET | `/api/care-requests` | index |
| POST | `/api/care-requests` | store |
| GET | `/api/care-requests/{care_request}` | show |
| PUT/PATCH | `/api/care-requests/{care_request}` | update |
| DELETE | `/api/care-requests/{care_request}` | destroy |

- 한 줄로 REST 라우트 5개가 생긴다. 메서드 이름 규칙(index/store/show/update/destroy)을 지키는 대신 매핑 코드를 쓰지 않는 방식이다.
- 개별로 쓰면 `Route::get('care-requests/{care_request}', [CareRequestController::class, 'show']);`처럼 된다.
- 라우트에 붙은 `care-requests.show` 같은 이름은 URL을 생성할 때 쓴다(`route('care-requests.show', 1)`).

---

## 6. 테스트 결과 (curl)

```bash
B=http://127.0.0.1:8000/api/care-requests
H=(-H 'Accept: application/json' -H 'Content-Type: application/json')
curl "${H[@]}" -X POST $B -d '{"patient_name":"홍길동","location":"전주","start_date":"2026-10-12","end_date":"2026-10-20","status":"DONE"}'
```

| # | 시나리오 | 결과 | 확인한 개념 |
|---|---|---|---|
| 1 | 생성 + `status: DONE` 끼워넣기 | 201, status = PENDING | `$fillable`, `$attributes` |
| 2 | 두 번째 생성 | 201 | |
| 3 | 목록 | 200, 배열 | `latest()` (같은 초 → 순서 미보장) |
| 4 | 단건 조회 | 200 | Route Model Binding |
| 5 | 빈 이름 + 종료일이 시작일보다 앞 (Accept 있음) | **422** + `errors` | validate |
| 6 | 검증 실패 (Accept 없음) | **422** | 아래 참고 |
| 7 | PATCH `location`만 | 200, location만 변경 | `sometimes` |
| 8 | 삭제 | **204** | `noContent()` |
| 9 | 없는 id(999) 조회 | **404** | `findOrFail` 자동 |

### 6번: Accept 없이도 422가 나온 이유
- 2교시에서 "Accept 헤더가 없으면 검증 실패 시 302 리다이렉트"라고 정리했다. 그런데 이 프로젝트에서는 422가 나왔다.
- 이유는 `bootstrap/app.php`의 `shouldRenderJsonWhen(fn ($r) => $r->is('api/*') || $r->expectsJson())` 설정이다. **`/api/*` 경로는 Accept와 상관없이 JSON 에러로 응답한다.** 최신 Laravel 기본 설정이 함정을 막아 둔 것이다.
- 하지만 **이 설정이 없는 이전 버전 프로젝트**나 `/api`가 아닌 경로에서는 여전히 302가 날 수 있다. 그러니 Accept 헤더를 붙이는 습관은 유지한다.

### 한글이 `홍길동`으로 보이는 이유
- JSON 표준의 유니코드 이스케이프다. 클라이언트가 파싱하면 정상 한글이 된다. 버그가 아니다.
- 사람이 보기 좋게 하려면 `response()->json($data, 200, [], JSON_UNESCAPED_UNICODE)`를 쓴다. 터미널에서 보기만 할 때는 `| jq`를 쓰면 된다.

---

## 7. Spring이라면 어떻게 만들었을까

```java
@Entity @Table(name = "care_requests")
public class CareRequest {
    @Id @GeneratedValue Long id;
    @Column(length = 50, nullable = false) String patientName;
    @Column(nullable = false) String location;
    LocalDate startDate, endDate;
    String status = "PENDING";
    @CreatedDate LocalDateTime createdAt; @LastModifiedDate LocalDateTime updatedAt;
}
public interface CareRequestRepository extends JpaRepository<CareRequest, Long> {}
public record CreateCareRequestDto(@NotBlank @Size(max = 50) String patientName, ...) {}

@RestController @RequestMapping("/api/care-requests") @RequiredArgsConstructor
public class CareRequestController {
    private final CareRequestRepository repo;
    @GetMapping List<CareRequest> index() { return repo.findAll(Sort.by(DESC, "createdAt")); }
    @PostMapping ResponseEntity<CareRequest> store(@Valid @RequestBody CreateCareRequestDto dto) {
        return ResponseEntity.status(201).body(repo.save(dto.toEntity()));
    }
    @GetMapping("/{id}") CareRequest show(@PathVariable Long id) {
        return repo.findById(id).orElseThrow(() -> new ResponseStatusException(NOT_FOUND));
    }
    ...
}
```
파일 수로 보면 Spring은 Entity, Repository, DTO, Controller, 그리고 예외 처리까지 4~5개다. Laravel은 Migration, Model, Controller, Route 한 줄로 끝난다. 대신 Laravel은 **이름 규칙과 "마법"(자동 추론, 매직 메서드)에 많이 기대므로, 규칙을 모르면 코드가 어디서 동작하는지 찾기 어렵다.**
