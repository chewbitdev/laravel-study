# 🗺️ 파일 지도 — 어떤 파일이 무슨 일을 하나

> "파일이 너무 많아서 뭐가 뭔지 모르겠다"를 해결하는 문서다.
> 모든 파일을 다 알 필요는 없다. **⭐ 표시된 파일만 확실히 알면** 지금까지 만든 기능을 전부 설명할 수 있다.

## 표시 읽는 법

| 표시 | 뜻 | 얼마나 자주 보나 |
|---|---|---|
| ⭐ | **우리가 직접 만들었거나 고친 파일** | 매일. 복습의 중심 |
| 📄 | Laravel이 만들어 준 파일. 가끔 열어서 설정을 확인함 | 가끔 |
| 💤 | 거의 건드리지 않는 파일. 존재만 알면 됨 | 거의 안 봄 |
| 🔒 | **내 컴퓨터에만 있는 파일** (GitHub에 안 올라감) | 필요할 때 |

---

## 1. 가장 바깥: `laravel-study/` (공부 저장소 전체)

```
laravel-study/                 ← GitHub 저장소 (chewbitdev/laravel-study)
├── CLAUDE.md                  ⭐ 학습 진행 상황, 일정, 규칙 (Claude가 읽는 인수인계 문서)
├── .gitignore                 💤 GitHub에 안 올릴 파일 목록 (.DS_Store 등)
├── docs/                      ⭐ 공부 문서 모음
│   ├── 00-start-here.md       ⭐ 복습 가이드 (읽는 순서, 서버/DB/설정)
│   ├── 01-file-map.md         ⭐ 지금 이 문서
│   ├── WORKLOG.md             ⭐ 실제로 친 명령어, 에러, 해결 기록 (날짜순)
│   └── notes/                 ⭐ 교시별 강의 노트
│       ├── day1-1-php-basics.md
│       ├── day1-2-laravel-project-structure.md
│       ├── day1-3-care-request-crud.md
│       ├── day1-4-review.md
│       ├── day2-1-request-response-layer.md
│       ├── day2-2-eloquent-relationships.md
│       └── spring-00-setup-and-roadmap.md   ⭐ Spring 버전 골격 설명 + 직접 만들기 로드맵
├── practice/
│   └── day1-php-basics.php    ⭐ PHP 문법 연습 파일 (php practice/day1-php-basics.php)
├── care-match/                ⭐ 진짜 Laravel 프로젝트 (아래 2장)
└── care-match-spring/         ⭐ 같은 API를 Spring Boot로 직접 만들어 보는 프로젝트 (아래 14장, 지금은 빈 골격)
```

**구분할 것**: `laravel-study/`는 "공부 노트 + 프로젝트"를 담은 상자이고, **실제 Laravel 앱은 `care-match/` 폴더 하나**다. `php artisan` 명령은 항상 `care-match/` 안에서 실행한다.

---

## 2. Laravel 프로젝트: `care-match/` 전체 모습

먼저 큰 덩어리만 보자. 폴더는 13개지만, **우리가 손대는 곳은 4군데**(`app/`, `database/`, `routes/`, `.env`)뿐이다.

```
care-match/
├── app/            ⭐ 우리 코드 대부분 (Controller, Model, 검증, 응답 모양)
├── database/       ⭐ 테이블 설계도(migrations), 가짜 데이터(factories, seeders), DB 파일
├── routes/         ⭐ 주소 → 컨트롤러 연결 (메뉴판)
├── .env            🔒⭐ 이 컴퓨터의 설정 (DB 종류, 비밀 키)
│
├── config/         📄 설정 파일들 (.env 값을 읽어서 정리)
├── bootstrap/      📄 앱 조립 (라우트 파일 등록, 에러 처리 방식)
├── public/         📄 웹 서버의 입구 (index.php)
├── storage/        📄 로그, 캐시, 업로드 파일이 쌓이는 곳
├── tests/          📄 테스트 코드 (Day 5)
│
├── resources/      💤 화면(HTML) 템플릿 — API만 만드는 우리는 거의 안 씀
├── vendor/         🔒💤 설치된 라이브러리 (Laravel 자체도 여기 있음). 절대 직접 수정 금지
└── (파일들)         composer.json, artisan, .env.example …
```

---

## 3. ⭐ `app/` — 우리가 짠 코드

```
app/
├── Enums/                               ⭐ "정해진 값 목록" (Java enum)
│   ├── CareStatus.php                   ⭐ 간병 요청 상태: PENDING / MATCHED / IN_PROGRESS / DONE + 한글 label()
│   ├── ApplicationStatus.php            ⭐ 지원 상태: PENDING / ACCEPTED / REJECTED
│   └── UserRole.php                     ⭐ 사용자 역할: GUARDIAN(보호자) / CAREGIVER(간병인)
│
├── Http/                                ← "HTTP 요청을 처리하는 코드" 모음
│   ├── Controllers/                     ⭐ 요청을 받아서 일을 시키고 응답을 돌려줌 (@RestController)
│   │   ├── Controller.php               💤 모든 컨트롤러의 부모 클래스 (비어 있음)
│   │   ├── CareRequestController.php    ⭐ 간병 요청 목록/생성/조회/수정/삭제 (5개 메서드)
│   │   └── ApplicationController.php    ⭐ 지원자 목록 보기, 지원하기 (2개 메서드)
│   │
│   ├── Requests/                        ⭐ 들어온 데이터 검사 (요청 DTO + @Valid)
│   │   ├── StoreCareRequestRequest.php  ⭐ 요청 "생성" 검사: 필수값, 날짜, 보호자인지
│   │   ├── UpdateCareRequestRequest.php ⭐ 요청 "수정" 검사: 보낸 값만 검사 + 날짜 앞뒤 확인
│   │   └── StoreApplicationRequest.php  ⭐ "지원" 검사: 간병인인지, 중복 지원인지, 대기 중인 요청인지
│   │
│   └── Resources/                       ⭐ 응답 JSON 모양 만들기 (응답 DTO)
│       ├── CareRequestResource.php      ⭐ 간병 요청 → JSON (days 계산, status_label, 보호자 이름)
│       └── ApplicationResource.php      ⭐ 지원 → JSON (간병인 이름, 상태)
│
├── Models/                              ⭐ 테이블 1개 = 모델 1개 (@Entity + Repository)
│   ├── User.php                         ⭐ users 테이블. 관계: careRequests(), applications()
│   ├── CareRequest.php                  ⭐ care_requests 테이블. 관계: guardian(), applications()
│   └── Application.php                  ⭐ applications 테이블. 관계: careRequest(), caregiver()
│
└── Providers/
    └── AppServiceProvider.php           💤 앱이 켜질 때 실행할 설정 (@Configuration). 지금은 비어 있음
                                            → Day 2 3교시에 N+1 방지 설정을 여기 넣는다
```

### 이 폴더의 파일들은 어떻게 협력하나

```
           routes/api.php 가 "이 주소는 이 컨트롤러로" 연결
                                │
                                ▼
 Requests/  ──검사 통과──▶  Controllers/  ──데이터 요청──▶  Models/  ◀──▶  DB
 (입력 검사)                (일 처리)      ◀──결과────────   (테이블)
                                │
                                ▼
                          Resources/  ──▶  JSON 응답
                          (응답 모양)
 Enums/ 는 모두가 같이 쓰는 "상태 값 사전"
```

---

## 4. ⭐ `database/` — 테이블 설계도와 데이터

```
database/
├── database.sqlite                 🔒⭐ 진짜 DB 파일. 데이터가 전부 여기 들어 있음 (지우면 데이터 전부 삭제)
├── .gitignore                      💤 database.sqlite를 GitHub에 안 올리게 하는 설정
│
├── migrations/                     ⭐ 테이블 설계도. 파일 이름 앞의 날짜 순서대로 실행됨
│   ├── 0001_01_01_000000_create_users_table.php            📄 users, password_reset_tokens, sessions 테이블 (Laravel 기본)
│   ├── 0001_01_01_000001_create_cache_table.php            💤 캐시용 테이블 (Laravel 기본)
│   ├── 0001_01_01_000002_create_jobs_table.php             💤 큐 작업용 테이블 (Laravel 기본, Day 5)
│   ├── 2026_10_04_013028_create_personal_access_tokens_table.php  📄 로그인 토큰 테이블 (install:api가 만듦, Day 4)
│   ├── 2026_10_04_095223_create_care_requests_table.php    ⭐ Day 1: 간병 요청 테이블
│   ├── 2026_10_05_141351_add_role_to_users_table.php       ⭐ Day 2: users에 role 컬럼 추가
│   ├── 2026_10_05_141352_add_guardian_id_to_care_requests_table.php  ⭐ Day 2: 요청에 보호자 FK 추가 (실패했던 그 파일)
│   └── 2026_10_05_141353_create_applications_table.php     ⭐ Day 2: 지원 테이블
│
├── factories/                      ⭐ 가짜 데이터 "틀"
│   ├── UserFactory.php             ⭐ 가짜 사용자 (한국 이름, guardian()/caregiver() 역할 선택)
│   ├── CareRequestFactory.php      ⭐ 가짜 간병 요청 (한국 도시, 30일 안의 날짜)
│   └── ApplicationFactory.php      ⭐ 가짜 지원 (랜덤 메시지)
│
└── seeders/
    └── DatabaseSeeder.php          ⭐ 틀로 실제 데이터를 몇 개 만들지 정하는 스크립트
                                       (테스트 계정 2개 + 보호자 5 + 간병인 10 + 요청 + 지원)
```

### 마이그레이션 파일 이름 읽는 법
```
2026_10_05_141353_create_applications_table.php
└──── 날짜·시각 ────┘└──── 무슨 일을 하는지 ───┘
```
- 앞의 날짜는 **실행 순서**를 정한다. 그래서 "테이블 만들기"가 "컬럼 추가"보다 먼저 와야 한다.
- 이름은 `make:migration` 명령이 만들어 준다. 직접 지을 필요 없다.
- `0001_01_01_...`은 Laravel 기본 파일이라 일부러 가장 앞에 오도록 지은 이름이다.

---

## 5. ⭐ `routes/` — 주소와 코드를 연결하는 메뉴판

```
routes/
├── api.php       ⭐ API 주소 목록. 여기 적은 주소 앞에는 자동으로 /api 가 붙음
│                    - /api/user (로그인 사용자 정보, Day 4)
│                    - /api/care-requests ... (5개)
│                    - /api/care-requests/{id}/applications (2개)
├── web.php       💤 웹페이지 주소 (/ → 환영 페이지). API만 만드는 우리는 안 씀
└── console.php   💤 터미널 명령과 스케줄러 등록 (Day 5)
```
전체 목록은 파일을 열지 않아도 `php artisan route:list`로 볼 수 있다.

---

## 6. 📄 설정 관련: `.env`, `config/`, `bootstrap/`

```
care-match/
├── .env                 🔒⭐ 이 컴퓨터 전용 설정 (DB 종류, APP_KEY, 디버그 모드)
├── .env.example         📄 .env의 양식. GitHub에 올라가는 건 이것
│
├── config/              📄 주제별 설정 파일. 대부분 .env 값을 읽어서 씀
│   ├── app.php          📄 앱 이름, 환경, timezone(지금 UTC ← 결정 필요), 언어
│   ├── database.php     📄 DB 연결 방법 (sqlite, mysql …)
│   ├── auth.php         📄 로그인 방식 (Day 4)
│   ├── sanctum.php      📄 API 토큰 로그인 설정 (Day 4)
│   ├── logging.php      📄 로그를 어디에 남길지 (storage/logs)
│   ├── cache.php        💤 캐시 저장 위치
│   ├── queue.php        💤 큐 작업 설정 (Day 5)
│   ├── session.php      💤 세션 설정 (API에서는 거의 안 씀)
│   ├── mail.php         💤 메일 발송 설정
│   ├── filesystems.php  💤 파일 업로드 저장 위치
│   └── services.php     💤 외부 서비스 키 (카카오, 슬랙 등을 연결할 때)
│
└── bootstrap/
    ├── app.php          📄 앱 조립 설명서: 어떤 라우트 파일을 쓸지, 미들웨어, 에러를 JSON으로 줄지
    ├── providers.php    💤 AppServiceProvider 등록 목록
    └── cache/           💤 Laravel이 속도를 위해 자동으로 만드는 캐시 파일
```

---

## 7. 📄 실행·결과물 관련: `public/`, `storage/`

```
public/                  ← 인터넷에 공개되는 유일한 폴더
├── index.php            📄 모든 요청이 들어오는 정문. 여기서 Laravel이 시작됨 (수정할 일 없음)
├── .htaccess            💤 Apache 웹 서버용 설정
├── favicon.ico          💤 브라우저 탭 아이콘
└── robots.txt           💤 검색 엔진 안내 파일

storage/                 ← Laravel이 실행 중에 파일을 쓰는 곳
├── logs/
│   └── laravel.log      🔒⭐ 에러 로그! 500 에러가 나면 제일 먼저 여기 맨 아래를 본다
├── app/                 💤 업로드된 파일 저장 (private/, public/)
└── framework/           💤 캐시, 세션, 컴파일된 화면 (Laravel이 알아서 관리)
```
> 💡 `storage/` 아래에 `.gitignore`가 많은 이유: 폴더 구조는 GitHub에 올리되, 안에 쌓이는 로그나 캐시 파일은 올리지 않기 위해서다.

---

## 8. 📄 프로젝트 정보 파일 (맨 바깥에 흩어져 있는 것들)

| 파일 | 표시 | 하는 일 | Spring 대응 |
|---|---|---|---|
| `composer.json` | 📄 | 필요한 라이브러리 목록, PHP 버전, 폴더 ↔ namespace 규칙 | `build.gradle` / `pom.xml` |
| `composer.lock` | 💤 | 실제 설치된 라이브러리의 **정확한 버전** 고정 (직접 수정 금지) | lock 파일 |
| `vendor/` | 🔒💤 | 라이브러리 실제 코드. `composer install`로 생김 | `~/.gradle` 캐시 |
| `artisan` | 📄 | `php artisan ...` 명령의 본체 | `gradlew` |
| `phpunit.xml` | 💤 | 테스트 실행 설정 (Day 5) | `test` 설정 |
| `tests/` | 📄 | `Feature/`(API 테스트), `Unit/`(단위 테스트), `TestCase.php`(부모) | `src/test/java` |
| `package.json`, `vite.config.js`, `.npmrc` | 💤 | 프론트(CSS/JS) 빌드 설정. API만 만드는 우리는 안 씀 | — |
| `resources/` | 💤 | `views/welcome.blade.php`(첫 화면 HTML), `css/`, `js/` | `templates/` |
| `.editorconfig`, `.gitattributes` | 💤 | 에디터 들여쓰기, git 줄바꿈 규칙 | — |
| `README.md` | 💤 | Laravel 기본 설명서 (Day 5에서 우리 내용으로 교체 예정) | — |
| `CLAUDE.md`, `AGENTS.md` | 💤 | Laravel이 자동으로 만든 AI 도구용 안내문. **바깥의 `laravel-study/CLAUDE.md`와는 다른 파일** | — |

---

## 9. 🔒 내 컴퓨터에만 있는 파일 (GitHub에 없음)

| 파일/폴더 | 왜 안 올리나 | 없으면 어떻게 만드나 |
|---|---|---|
| `.env` | 비밀번호와 키가 들어 있음 | `cp .env.example .env` → `php artisan key:generate` |
| `vendor/` | 용량이 크고 다시 받을 수 있음 | `composer install` |
| `database/database.sqlite` | 내 데이터 (사람마다 다름) | `touch database/database.sqlite` → `php artisan migrate --seed` |
| `storage/logs/laravel.log` | 실행할 때마다 쌓이는 기록 | 에러가 나면 자동으로 생김 |

→ 그래서 GitHub에서 새로 받으면 이 4가지를 직접 만들어야 한다. (`00-start-here.md` 5장)

---

## 10. 🔍 API 하나를 파일로 따라가기

"간병인이 간병 요청에 지원한다"(`POST /api/care-requests/1/applications`)가 실행될 때 거치는 파일을 순서대로 보자. **복습할 때 이 순서대로 파일을 열어 보면** 구조가 한 번에 이해된다.

| 순서 | 파일 | 여기서 하는 일 |
|---|---|---|
| 1 | `public/index.php` | 요청이 들어온다. Laravel을 깨운다 |
| 2 | `bootstrap/app.php` | "API 주소는 `routes/api.php`를 봐라" |
| 3 | `routes/api.php` | `care-requests.applications` → `ApplicationController@store`로 보낸다 |
| 4 | `app/Models/CareRequest.php` | 주소의 `1`로 간병 요청 1번을 DB에서 미리 찾아 둔다 (Route Model Binding). 없으면 404 |
| 5 | `app/Http/Requests/StoreApplicationRequest.php` | 간병인이 맞는지, 이미 지원했는지, 대기 중인 요청인지 검사한다. 실패하면 422 |
| 6 | `app/Http/Controllers/ApplicationController.php` | `store()`가 지원 데이터를 만든다 |
| 7 | `app/Models/Application.php` | `save()`로 applications 테이블에 INSERT |
| 8 | `database/database.sqlite` | 데이터가 실제로 저장된다 |
| 9 | `app/Http/Resources/ApplicationResource.php` | 저장된 지원을 JSON 모양으로 바꾼다 |
| 10 | (응답) | `201` + `{"data": {...}}` |

같은 방법으로 `GET /api/care-requests`(목록)도 따라가 보자. 3 → `CareRequestController@index` → `CareRequest` 모델 → `CareRequestResource` 순서다.

---

## 11. 이름 규칙 — 이름만 보고 파일 찾기

Laravel은 **이름 규칙**으로 파일을 연결한다. 규칙을 알면 파일을 찾기 쉽다.

| 개념 | 이름 | 파일 위치 |
|---|---|---|
| 모델 | `CareRequest` (단수, 대문자로 시작) | `app/Models/CareRequest.php` |
| 테이블 | `care_requests` (복수, 소문자_밑줄) | `database/migrations/..._create_care_requests_table.php` |
| 컨트롤러 | `CareRequestController` | `app/Http/Controllers/CareRequestController.php` |
| 생성 검증 | `StoreCareRequestRequest` | `app/Http/Requests/StoreCareRequestRequest.php` |
| 수정 검증 | `UpdateCareRequestRequest` | `app/Http/Requests/UpdateCareRequestRequest.php` |
| 응답 모양 | `CareRequestResource` | `app/Http/Resources/CareRequestResource.php` |
| 가짜 데이터 | `CareRequestFactory` | `database/factories/CareRequestFactory.php` |
| 주소 | `/api/care-requests` (복수, 소문자-하이픈) | `routes/api.php` |
| FK 컬럼 | `care_request_id` (단수_id) | 마이그레이션 안 |

**클래스 이름 = 파일 이름**이고, **`namespace` = 폴더 경로**다. 예를 들어 `App\Http\Controllers\CareRequestController`는 `app/Http/Controllers/CareRequestController.php`에 있다. Java의 패키지와 폴더 관계와 똑같다.

---

## 12. 새 기능을 만들 때 파일을 만드는 순서

예: "리뷰(Review) 기능 추가"

```bash
php artisan make:model Review -mf            # ① 모델 + 마이그레이션 + 팩토리
#   → database/migrations/..._create_reviews_table.php  : 컬럼 적기
#   → app/Models/Review.php                              : $fillable, casts, 관계
php artisan migrate                          # ② 테이블 생성
php artisan make:request StoreReviewRequest  # ③ 입력 검사 (⚠️ authorize() → true)
php artisan make:resource ReviewResource     # ④ 응답 모양
php artisan make:controller ReviewController # ⑤ 컨트롤러
#   → routes/api.php 에 Route 한 줄 추가       # ⑥ 주소 연결
php artisan route:list --path=api/review     # ⑦ 등록 확인 → curl로 테스트
```
설계도(마이그레이션) → 모델 → 검사 → 응답 모양 → 컨트롤러 → 주소 순서다. 지금까지 `CareRequest`와 `Application`도 이 순서로 만들었다.

---

## 13. 회사 프로젝트를 열었을 때 이 지도 활용법

1. **`app/` 아래 폴더 목록부터 본다.** 우리 프로젝트에 없는 폴더(`Services/`, `Repositories/`, `Actions/`, `Jobs/`, `Events/`, `Policies/` …)가 있으면, 그게 회사만의 구조다. 선배에게 그 폴더의 역할을 물어보자.
2. **`routes/api.php` + `php artisan route:list`**: 어떤 API가 있는지 본다.
3. **`database/migrations/`**: 테이블 구조를 본다(모델 파일에는 컬럼이 안 적혀 있다).
4. **`app/Models/`**: 테이블끼리의 관계를 본다.
5. **`.env.example`**: 어떤 외부 서비스(DB, Redis, 메일, 결제)에 연결하는지 본다.
6. **`composer.json`**: Laravel 버전과 추가 라이브러리를 본다.

---

## 14. Spring 버전: `care-match-spring/` (빈 골격)

> 자세한 설명과 직접 만들 순서는 `docs/notes/spring-00-setup-and-roadmap.md`

```
care-match-spring/
├── build.gradle                          📄 의존성, Java 버전 (= composer.json)
├── settings.gradle                       💤 프로젝트 이름
├── gradlew, gradlew.bat, gradle/wrapper/ 💤 Gradle 실행기 (= artisan 같은 도구)
├── HELP.md                               💤 Initializr 안내문 (git 제외)
├── .gitignore, .gitattributes            💤
├── src/main/java/com/study/carematch/
│   ├── CareMatchSpringApplication.java   📄 main(). 앱 시작점 (= public/index.php + bootstrap/app.php)
│   ├── domain/package-info.java          ⭐ 직접 만들 곳: 엔티티, enum        (= app/Models, app/Enums)
│   ├── repository/package-info.java      ⭐ 직접 만들 곳: JpaRepository       (Laravel에 없음)
│   ├── service/package-info.java         ⭐ 직접 만들 곳: 비즈니스 로직        (Laravel에선 Controller/after())
│   ├── controller/package-info.java      ⭐ 직접 만들 곳: @RestController     (= Controllers + routes/api.php)
│   ├── dto/package-info.java             ⭐ 직접 만들 곳: 요청/응답 record     (= Requests + Resources)
│   ├── exception/package-info.java       ⭐ 직접 만들 곳: @RestControllerAdvice (= withExceptions)
│   └── config/package-info.java          ⭐ 직접 만들 곳: 초기 데이터 등       (= Seeder, Provider)
├── src/main/resources/
│   ├── application.properties            ⭐ 설정 (= .env + config/*.php). Laravel과 응답 모양을 맞추는 설정이 들어 있음
│   ├── db/migration/.gitkeep             ⭐ 직접 만들 곳: V1__...sql ~       (= database/migrations)
│   ├── static/, templates/               💤 화면용 (API만 만드므로 안 씀)
├── src/test/java/.../CareMatchSpringApplicationTests.java  📄 앱이 뜨는지 확인하는 기본 테스트 (= tests/)
├── data/care-match.mv.db                 🔒 H2 DB 파일 (= database/database.sqlite)
├── build/                                🔒 컴파일 결과, jar
└── .gradle/                              🔒 Gradle 캐시
```
`package-info.java`에는 "이 패키지가 무슨 역할인지, Laravel의 어디에 해당하는지"만 주석으로 적혀 있다. 이 패키지들 안에 클래스를 직접 만들어 나간다.
