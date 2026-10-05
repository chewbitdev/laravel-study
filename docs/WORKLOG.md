# 작업 로그 (설치·작업 기록)

Laravel 학습 중에 실행한 설치와 명령어, 파일 변경, 문제 해결 과정을 날짜순으로 기록한다.
각 항목은 **무엇을 했나 → 명령어 → 결과/확인 → 메모(Spring 비교, 주의점)** 순서로 쓴다.

---

## 2026-10-04 (일) Day 1

### 0. 학습 문서 정리
- `CLAUDE.md`에서 개인 정보 섹션("나에 대해")을 삭제했다. 목표, 일정, 미니 프로젝트, 진행 방식은 그대로 뒀다.
- 이 작업 로그 파일 `docs/WORKLOG.md`를 새로 만들었다.

### 1. 환경 확인
```bash
which php composer brew
```
- 결과: `brew`만 있음(`/opt/homebrew/bin/brew`). `php`와 `composer`는 없음.

### 2. PHP · Composer 설치
```bash
brew install php composer
```
- 상태: ✅ 완료 (exit 0)
- 확인
  ```bash
  php -v        # PHP 8.5.11 (cli) (NTS)
  composer -V   # Composer version 2.10.3
  ```
  - 설치 경로: `/opt/homebrew/bin/php`, `/opt/homebrew/bin/composer`
  - 설정 파일(php.ini): `/opt/homebrew/etc/php/8.5/`
- 설치 안내 중 Apache나 php-fpm 설정 부분은 **무시해도 된다**. 개발할 때는 `php artisan serve`로 내장 서버를 띄운다. Spring Boot의 내장 Tomcat과 비슷하다.
- 메모
  - PHP는 Java로 치면 JDK, 즉 언어 런타임이다.
  - Composer는 Maven이나 Gradle 같은 의존성 관리 도구다. `composer.json`이 `pom.xml`이나 `build.gradle`에 해당한다.

### 3. Git 저장소 분리 + GitHub 연결
- 문제: `laravel-study`의 상위 폴더가 다른 git 저장소에 포함되어 있었다. 그대로 커밋하면 엉뚱한 저장소에 들어간다.
- 해결: 이 폴더에 별도 저장소를 만들었다. 하위 폴더에 `.git`이 있으면 git은 가장 가까운 `.git`을 기준으로 동작한다.
```bash
cd ~/Documents/laravel-study
printf '.DS_Store\n.idea/\n.vscode/\n' > .gitignore
git init -b main
git add -A
git commit -m "..."
gh repo create chewbitdev/laravel-study --public --source . --push
```
- 저장소: https://github.com/chewbitdev/laravel-study (Public)
- 커밋 규칙: 의미 있는 작업 단위마다 커밋하고 push한다. 메시지는 한국어, `type: 내용` 형식이다(예: `feat: CareRequest CRUD API`, `docs: 작업 로그 갱신`).

### 4. 1교시: PHP 기초 강의 노트와 실습
- 진행 방식 변경: 개념을 먼저 다 설명하고, 퀴즈는 Day 끝에 모아서 낸다(CLAUDE.md에 반영).
- 생성한 파일
  - `docs/notes/day1-1-php-basics.md`: 실행 모델, 변수와 타입, 문자열, 배열, 비교와 null, 함수와 클로저, 클래스, trait와 enum, namespace와 Composer, 매직 메서드(Eloquent 원리), 예외
  - `practice/day1-php-basics.php`: 위 개념을 실제로 돌려 보는 예제
- 실행 확인
  ```bash
  php practice/day1-php-basics.php   # 7개 섹션 모두 정상 출력
  ```

### 5. 2교시: Laravel 프로젝트 생성
```bash
composer create-project laravel/laravel care-match
```
- 결과: Laravel Framework **13.34.0**
- 자동으로 실행된 것: `composer install`, `.env` 생성, `key:generate`, `database/database.sqlite` 생성, 기본 마이그레이션 3개(users, cache, jobs)
- 기본 DB는 SQLite(`DB_CONNECTION=sqlite`)다. 학습용으로는 별도 DB 서버가 필요 없다.
- `care-match/CLAUDE.md`와 `AGENTS.md`는 Laravel이 자동 생성한 AI 도구용 가이드 파일이다(laravel-boost). 이 학습용 CLAUDE.md와는 별개다.

### 6. 개발 서버 실행과 확인
```bash
php artisan route:list
php artisan serve                     # http://127.0.0.1:8000
curl http://127.0.0.1:8000/           # 200 (welcome 페이지)
curl http://127.0.0.1:8000/up         # 200 (헬스체크)
curl http://127.0.0.1:8000/nope                                # HTML 404 페이지
curl -H 'Accept: application/json' http://127.0.0.1:8000/nope  # JSON 404 + trace
```
- Accept 헤더에 따라 에러 응답 형식이 바뀌는 것을 확인했다.

### 7. API 스캐폴딩 설치
```bash
php artisan install:api --no-interaction
```
- `routes/api.php`를 만들고 `bootstrap/app.php`에 `api:` 라우트를 등록했다.
- Sanctum 패키지를 설치하고 `personal_access_tokens` 테이블 마이그레이션을 실행했다(batch 2).
- 안내 메시지: User 모델에 `HasApiTokens` trait를 추가하라고 함 → **Day 4(Sanctum 인증)에서 진행**
- 확인: `php artisan route:list --path=api` → `GET api/user`

### 8. git 커밋 대상 확인
- `.env`, `vendor/`, `database/database.sqlite`는 Laravel 기본 `.gitignore`가 제외해 준다. 커밋 전에 `git add -n`으로 빠지는 것을 확인했다.

- 강의 노트: `docs/notes/day1-2-laravel-project-structure.md`

### 9. 3교시: CareRequest CRUD API
```bash
cd care-match
php artisan make:model CareRequest -mcr --api
```
- 생성된 파일
  - `app/Models/CareRequest.php`
  - `database/migrations/2026_10_04_095223_create_care_requests_table.php`
  - `app/Http/Controllers/CareRequestController.php`
- 작성하거나 수정한 코드
  - migration: `patient_name(50)`, `location`, `start_date`, `end_date`, `status` default PENDING, timestamps
  - model: `$fillable`(status 제외), `$attributes`(status = PENDING), `casts`(날짜 → `date:Y-m-d`)
  - controller: index(`latest()`), store(validate + 201), show(Route Model Binding), update(`sometimes` 검증), destroy(204)
  - `routes/api.php`: `Route::apiResource('care-requests', CareRequestController::class);`
- 실행
  ```bash
  php artisan migrate                         # care_requests 테이블 생성
  php artisan route:list --path=api/care      # 라우트 5개 확인
  php artisan serve
  ```
- curl 테스트 9개 시나리오 모두 통과: 201 / 201 / 200 / 200 / 422 / 422 / 200 / 204 / 404
- 발견한 점
  - Accept 헤더가 없어도 `/api/*` 검증 실패는 422가 나온다. `bootstrap/app.php`의 `shouldRenderJsonWhen` 설정 때문이다.
  - `latest()`는 created_at만 기준이라, 같은 초에 생성된 행은 정렬 순서가 보장되지 않는다.
  - JSON 응답의 한글은 `\uXXXX`로 이스케이프된다(표준 동작).
- `care-match/CLAUDE.md`가 Laravel Boost 설치를 권하지만 학습에는 필요 없어서 설치하지 않았다.
- 강의 노트: `docs/notes/day1-3-care-request-crud.md`

### 10. Day 1 확인 질문
- 7문항 모두 정답. 보충 설명(Facade와 모델 `__callStatic`의 차이, 이 프로젝트의 Accept 헤더 동작 등)은 `docs/notes/day1-4-review.md`에 정리했다.

---

## 2026-10-05 (월) Day 2

### 1. 1교시: FormRequest · API Resource · Enum · 페이징
```bash
cd care-match
php artisan make:enum CareStatus --string     # app/CareStatus.php에 생성됨 → 삭제하고 app/Enums/CareStatus.php로 다시 작성
php artisan make:request StoreCareRequestRequest
php artisan make:request UpdateCareRequestRequest
php artisan make:resource CareRequestResource
```
- 작성하거나 수정한 파일
  - `app/Enums/CareStatus.php`: PENDING, MATCHED, IN_PROGRESS, DONE + `label()`
  - `app/Http/Requests/StoreCareRequestRequest.php`: `authorize()` true(기본 false 함정), rules, 한글 `attributes()`, `start_date`는 `after_or_equal:today`
  - `app/Http/Requests/UpdateCareRequestRequest.php`: `sometimes` 규칙 + `after()` 훅으로 기존 값과 날짜 비교(Day 1 숙제 해결)
  - `app/Http/Resources/CareRequestResource.php`: 응답 필드 선택, `days` 계산, `status_label`, `updated_at` 숨김
  - `app/Models/CareRequest.php`: status를 enum으로 cast
  - `app/Http/Controllers/CareRequestController.php`: FormRequest와 Resource 적용, index에 `?status`, `?per_page` 필터와 `paginate`, `latest('id')` 보조 정렬
- 테스트 데이터: tinker로 MATCHED 상태 1건 생성
- curl 테스트 9개 시나리오 모두 통과: 201 자동 / 422(한글 필드명) / 페이징 meta / 상태 필터 / 422(enum) / 422(after 훅) / 422(형식 오류, 500 아님) / 200 / status 무시
- 발견한 점: `config/app.php`의 timezone이 UTC라서 `today()`가 한국 날짜보다 하루 늦을 수 있다(한국 시간 00~09시). → Day 3에서 정책을 결정한다.
- 강의 노트: `docs/notes/day2-1-request-response-layer.md`

### 2. 2교시: Eloquent 관계 (users role, applications)
```bash
php artisan make:migration add_role_to_users_table --table=users
php artisan make:migration add_guardian_id_to_care_requests_table --table=care_requests
php artisan make:model Application -mf             # 모델 + 마이그레이션 + 팩토리
php artisan make:factory CareRequestFactory --model=CareRequest
php artisan make:controller ApplicationController
php artisan make:request StoreApplicationRequest
php artisan make:resource ApplicationResource
```
- 새로 만든 파일
  - enum: `app/Enums/UserRole.php`(GUARDIAN, CAREGIVER), `app/Enums/ApplicationStatus.php`(PENDING, ACCEPTED, REJECTED)
  - migration: users.role, care_requests.guardian_id(FK, NOT NULL, CASCADE), applications 테이블(FK 2개, UNIQUE(care_request_id, caregiver_id))
  - model: `Application`(careRequest, caregiver belongsTo)
  - factory: `CareRequestFactory`, `ApplicationFactory`
  - API: `ApplicationController`(index, store), `StoreApplicationRequest`, `ApplicationResource`
- 수정한 파일
  - model: `User`(role cast, careRequests/applications hasMany), `CareRequest`(HasFactory, guardian belongsTo, applications hasMany)
  - factory: `UserFactory`에 role, ko_KR 이름, guardian()/caregiver() state 추가
  - seeder: `DatabaseSeeder`에 고정 계정 2개, 보호자 5, 간병인 10, 요청, 지원 생성
  - API: `StoreCareRequestRequest`에 guardian_id 검증 추가, `CareRequestController@store`를 관계로 생성하도록 변경, `CareRequestResource`에 guardian 추가
  - routes: `Route::apiResource('care-requests.applications', ...)->only(['index', 'store'])`

#### 🔥 마이그레이션 실패와 해결
- 기존 데이터 5건이 있는 상태에서 `php artisan migrate` 실행
  - `add_role_to_users_table` 성공
  - `add_guardian_id_to_care_requests_table` **실패**: `NOT NULL constraint failed: __temp__care_requests.guardian_id`
- 실패 후 상태: 마이그레이션은 Pending인데 `guardian_id` 컬럼은 이미 생성돼 있었다(반쯤 적용됨). 다시 migrate하면 "컬럼이 이미 있다" 에러가 난다.
- 해결(개발 DB):
  ```bash
  php artisan migrate:fresh --seed   # 전체 DROP 후 재실행 + 시더
  ```
- 교훈: 운영 DB에는 ① nullable 추가 → ② 데이터 채우기 → ③ NOT NULL 변경으로, 가능하면 파일도 나눠서 적용한다. MySQL은 DDL이 자동 커밋되어 롤백되지 않는다.

#### 결과 확인
- 시더 결과: users 17(보호자 6, 간병인 11), care_requests 12, applications 35
- tinker로 관계 탐색: `$r->guardian->name`, `$r->applications`, `careRequests()`(HasMany)와 `careRequests`(Collection)의 차이 확인
- curl 9개 시나리오 통과: 201 / 422(역할) / 200 / 201 / 422(중복) / 422(역할) / 422(상태) / 404 / DB UNIQUE 예외 + CASCADE 삭제
- 남은 문제: Resource에서 관계를 지연 로딩 → N+1 (3교시)
- 강의 노트: `docs/notes/day2-2-eloquent-relationships.md`
