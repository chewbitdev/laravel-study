# Day 1 - 2교시: Laravel 프로젝트 생성과 폴더 구조

> 목표: Laravel 프로젝트를 처음 열었을 때 **어디에 무엇이 있는지** 알기
> 버전: Laravel Framework 13.34 / PHP 8.5

---

## 1. 프로젝트 생성

```bash
composer create-project laravel/laravel care-match
```
Spring Initializr(start.spring.io)에서 zip을 받아 압축을 푸는 것과 같다. 이 명령 하나로 다음 작업까지 자동으로 끝난다.

| 자동으로 실행된 것 | 의미 |
|---|---|
| `composer install` | `vendor/`에 라이브러리 다운로드 (Gradle 의존성 다운로드) |
| `.env.example` → `.env` 복사 | 환경 설정 파일 생성 |
| `php artisan key:generate` | `.env`의 `APP_KEY` 생성. 암호화, 세션, 쿠키 서명에 쓰는 비밀 키 |
| `database/database.sqlite` 생성 | 기본 DB가 SQLite 파일 (H2 파일 DB와 비슷) |
| `php artisan migrate` | 기본 테이블(users, cache, jobs) 생성 |

---

## 2. artisan = Laravel의 CLI 도구

`php artisan ...`은 Laravel 개발 내내 쓰는 명령줄 도구다. Spring에는 딱 맞는 대응이 없고, `./gradlew` + Spring CLI + 코드 생성기를 합친 것이라고 보면 된다.

| 명령 | 하는 일 | Spring 대응 |
|---|---|---|
| `php artisan serve` | 개발 서버 실행 (:8000) | `./gradlew bootRun` |
| `php artisan route:list` | 등록된 라우트 전체 보기 | Actuator `/mappings` |
| `php artisan make:model Foo -mcr` | 모델, 마이그레이션, 컨트롤러 파일 생성 | (없음, 직접 생성) |
| `php artisan migrate` | DB 스키마 변경 적용 | Flyway / Liquibase |
| `php artisan migrate:status` | 마이그레이션 적용 여부 | `flyway info` |
| `php artisan tinker` | 앱이 로드된 REPL | (jshell + 컨텍스트) |
| `php artisan about` | 버전, 환경 요약 | |

> 실무 프로젝트를 처음 받으면 **`php artisan route:list`부터 실행해 보자.** 어떤 API가 있고 어느 컨트롤러가 처리하는지 한눈에 보인다.

---

## 3. 폴더 구조 ↔ Spring 대응

```
care-match/
├── app/                      ← src/main/java/com/example/ (내가 짜는 코드 대부분)
│   ├── Http/
│   │   ├── Controllers/      ← @RestController
│   │   └── (Middleware/)     ← Filter / HandlerInterceptor (필요할 때 생성)
│   ├── Models/               ← @Entity + Repository 역할을 합친 Eloquent 모델
│   └── Providers/            ← @Configuration (Bean 등록, 부팅 시 설정)
├── bootstrap/
│   └── app.php               ← 앱 조립: 라우트 파일, 미들웨어, 예외 처리 등록 (main + 설정)
├── config/                   ← application.yml을 주제별 PHP 파일로 나눈 것
├── database/
│   ├── migrations/           ← Flyway의 V1__xxx.sql (PHP 코드로 작성)
│   ├── factories/            ← 테스트용 가짜 데이터 생성기
│   └── seeders/              ← 초기 데이터 (data.sql)
├── public/
│   └── index.php             ← 모든 HTTP 요청의 단일 진입점 (DispatcherServlet 위치)
├── resources/views/          ← 템플릿 (Thymeleaf ↔ Blade). API만 만들면 거의 안 씀
├── routes/
│   ├── web.php               ← 웹(세션, 쿠키) 라우트
│   ├── api.php               ← API 라우트, 자동으로 /api 접두사 (install:api로 생성)
│   └── console.php           ← CLI 명령, 스케줄러
├── storage/                  ← 로그(storage/logs/laravel.log), 캐시, 업로드 파일
├── tests/                    ← src/test/java
├── vendor/                   ← 라이브러리 (git 제외)
├── .env                      ← 환경별 비밀값 (git 제외)
├── .env.example              ← .env 템플릿 (git 포함)
├── artisan                   ← CLI 실행 파일
└── composer.json             ← build.gradle
```

### Spring과 가장 다른 점
1. **Repository 계층이 따로 없다.** Eloquent 모델 자체가 `CareRequest::find(1)`, `$req->save()`를 할 수 있다(Active Record 패턴). JPA는 Entity와 Repository가 분리된 Data Mapper 패턴이다.
2. **어노테이션 기반 라우팅이 아니다.** `@GetMapping`을 컨트롤러에 붙이지 않고, `routes/*.php` 파일 한곳에 URL과 컨트롤러를 모아서 연결한다.
3. **Service 계층은 강제되지 않는다.** 기본 구조에 `Services/` 폴더가 없다. 회사마다 `app/Services/`, `app/Actions/` 같은 폴더를 직접 만들어 쓴다. 실무 프로젝트를 열면 이런 폴더가 있는지부터 확인한다.

---

## 4. `.env`와 `config/`

```dotenv
# .env  (git에 올리지 않음)
APP_ENV=local
APP_DEBUG=true          # true면 에러에 스택 트레이스까지 노출 → 운영에서는 반드시 false
APP_URL=http://localhost:8000
DB_CONNECTION=sqlite    # 실무에서는 mysql 등 + DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD
```
```php
// config/database.php  (git에 올림)
'default' => env('DB_CONNECTION', 'sqlite'),   // .env 값을 읽고, 없으면 기본값
```
```php
// 코드에서는 config()로 읽는다
config('database.default');   // @Value("${spring.datasource...}")
```
- **규칙: `env()`는 config 파일 안에서만 쓰고, 일반 코드에서는 `config()`를 쓴다.** 운영 환경에서 `php artisan config:cache`로 설정을 캐싱하면 `env()`가 null을 돌려주기 때문이다.
- `.env` = `application-local.yml` + 환경 변수, `config/*.php` = `application.yml`의 구조

---

## 5. 요청 하나가 처리되는 흐름

`/nope`(없는 주소)에 `Accept: application/json`으로 요청했을 때 에러 응답의 trace를 아래에서 위로 읽으면, 실제 흐름은 다음과 같다.

```
public/index.php                      ← 1. 진입점 (DispatcherServlet)
  → Application::handleRequest
  → Http\Kernel::handle               ← 2. HTTP 커널
  → Pipeline (전역 미들웨어 체인)       ← 3. Filter Chain
       ValidatePathEncoding
       TrustProxies
       HandleCors                     ← CORS
       PreventRequestsDuringMaintenance
       ValidatePostSize
       TrimStrings                    ← 입력 문자열 앞뒤 공백 제거
       ConvertEmptyStringsToNull      ← "" → null 변환
  → Router::dispatch                  ← 4. HandlerMapping (URL → 컨트롤러 찾기)
  → (컨트롤러 메서드)                  ← 5. @RestController 메서드 실행
  → Response                          ← 6. 응답
```
Spring의 `Filter → DispatcherServlet → HandlerMapping → Controller` 흐름과 구조가 거의 같다.

> 참고: `TrimStrings`와 `ConvertEmptyStringsToNull` 때문에, 클라이언트가 `"name": "  "`을 보내면 컨트롤러에서는 `null`로 받는다. Spring에는 없는 기본 동작이라 디버깅할 때 헷갈릴 수 있다.

---

## 6. `bootstrap/app.php` — 앱 조립 설정

```php
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',   // install:api가 추가
        commands: __DIR__.'/../routes/console.php',
        health: '/up',                       // 헬스체크 (Actuator /health)
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // 미들웨어 등록 (Day 4)
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // 전역 예외 처리 (@ControllerAdvice)
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
```
- 메서드 체이닝 빌더 패턴이다. Spring Security의 `http.csrf()...` 설정 스타일과 비슷하다.
- Laravel 10 이하에서는 이 설정이 `app/Http/Kernel.php`, `app/Exceptions/Handler.php`, `RouteServiceProvider.php`에 흩어져 있었다. **회사 프로젝트가 Laravel 10 이하라면 그 파일들을 찾으면 된다.**

---

## 7. web.php vs api.php

| | `routes/web.php` | `routes/api.php` |
|---|---|---|
| URL 접두사 | 없음 | 자동으로 `/api` |
| 미들웨어 그룹 | `web` (세션, 쿠키, CSRF 검사) | `api` (상태 없음) |
| 용도 | 서버 렌더링 페이지 | REST API (모바일 앱, SPA) |
| Spring 비유 | `@Controller` + 세션 | `@RestController` + Stateless |

`routes/api.php`는 Laravel 11부터 기본으로 생성되지 않는다. `php artisan install:api`를 실행해야 생기고, 이때 인증 패키지 **Sanctum**과 `personal_access_tokens` 테이블도 함께 설치된다(Day 4에서 사용).

---

## 8. `Accept: application/json` 헤더가 필요한 이유

같은 "없는 주소" 요청인데 응답이 달랐다.

```bash
curl http://127.0.0.1:8000/nope
# → HTML 404 페이지 (<title>Not Found</title>)

curl -H 'Accept: application/json' http://127.0.0.1:8000/nope
# → {"message": "The route nope could not be found.", "exception": ..., "trace": [...]}
```
- Laravel은 요청이 JSON을 원하는지(`expectsJson()`) 보고 에러 응답 형식을 정한다.
- 특히 **검증 실패** 때 차이가 크다. Accept 헤더가 없으면 422 JSON 대신 **이전 페이지로 302 리다이렉트**한다(웹 폼 동작). API 테스트에서 "왜 302가 오지?"라는 문제의 원인이 대부분 이것이다.
- 이 프로젝트의 `bootstrap/app.php`는 `api/*` 경로도 JSON으로 응답하도록 설정돼 있다. 그래도 **API를 호출할 때는 항상 `Accept: application/json`을 붙이는 습관**을 들이자.
- `APP_DEBUG=true`라서 `trace`까지 노출된다. 운영에서는 `false`로 두어 `message`만 보이게 한다.

---

## 9. 모델 맛보기: Laravel 13의 `User.php`

```php
#[Fillable(['name', 'email', 'password'])]   // 대량 할당 허용 필드
#[Hidden(['password', 'remember_token'])]     // JSON 변환 시 숨김 (@JsonIgnore)
class User extends Authenticatable
{
    use HasFactory, Notifiable;               // trait

    protected function casts(): array          // 타입 변환 (AttributeConverter)
    {
        return [
            'email_verified_at' => 'datetime',  // 문자열 → Carbon 날짜 객체
            'password' => 'hashed',             // 저장할 때 자동 해시
        ];
    }
}
```
- 필드 선언이 하나도 없다. 1교시에서 배운 `__get`/`__set` 방식이다. 컬럼은 `database/migrations/..._create_users_table.php`에서 확인한다.
- `#[Fillable]`은 PHP 8 **Attribute**(Java 어노테이션과 같은 문법) 방식이고, Laravel 13에서 새로 생긴 스타일이다. **기존 실무 코드는 대부분 프로퍼티 방식**을 쓴다. 둘 다 읽을 줄 알아야 한다.
  ```php
  protected $fillable = ['name', 'email', 'password'];   // 전통 방식
  protected $hidden = ['password', 'remember_token'];
  ```

---

## 10. 이번 교시에 실행한 명령

```bash
composer create-project laravel/laravel care-match
cd care-match
php artisan --version          # Laravel Framework 13.34.0
php artisan route:list         # GET /, GET up, storage 라우트
php artisan serve              # http://127.0.0.1:8000
curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1:8000/     # 200
curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1:8000/up   # 200
php artisan install:api --no-interaction   # api.php + Sanctum + 마이그레이션
php artisan route:list --path=api          # GET api/user
```

## 처음 보는 Laravel 프로젝트를 열었을 때 체크리스트
1. `composer.json` → Laravel 버전 확인 (`laravel/framework`)
2. `php artisan route:list` → 전체 API 지도
3. `routes/api.php` → URL과 컨트롤러 연결
4. `app/Http/Controllers/` → 요청 처리
5. `app/Models/` + `database/migrations/` → 도메인과 테이블 구조
6. `app/Services/` 같은 추가 폴더 → 회사만의 계층 구조
7. `.env.example` → 어떤 외부 연동(DB, Redis, 메일 등)이 있는지
