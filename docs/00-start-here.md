# 📘 복습 가이드 — 여기서부터 읽기

> 지금까지(Day 1 ~ Day 2 2교시) 배운 내용을 **아주 쉽게** 다시 정리한 문서다.
> "서버가 뭐지? DB는 어디 있지? 설정은 어디서 하지? 나중에 진짜 DB는 어떻게 연결하지?"에 답한다.

---

## 0. 문서 읽는 순서

복습은 이 순서대로 하면 된다. 각 단계에서 **문서를 읽고 → 코드 파일을 직접 열어 보는 것**까지가 한 세트다.

| 순서 | 읽을 문서 | 같이 열어 볼 코드 | 핵심 한 줄 |
|---|---|---|---|
| ① | **이 문서** (`docs/00-start-here.md`) | — | 전체 그림부터 잡기 |
| ①-2 | **`docs/01-file-map.md`** (파일 지도) | `care-match/` 폴더를 에디터 왼쪽 탐색기에 펼쳐 놓고 | 어떤 파일이 무슨 일을 하는지 |
| ② | `docs/notes/day1-1-php-basics.md` | `practice/day1-php-basics.php` | `$변수`, `->`, `::`, 배열 = Map |
| ③ | `docs/notes/day1-2-laravel-project-structure.md` | `care-match/` 폴더 구조, `bootstrap/app.php` | 폴더마다 하는 일 |
| ④ | `docs/notes/day1-3-care-request-crud.md` | migration → Model → Controller → `routes/api.php` | API 하나가 만들어지는 과정 |
| ⑤ | `docs/notes/day1-4-review.md` | — | Day 1 퀴즈 복습 |
| ⑥ | `docs/notes/day2-1-request-response-layer.md` | `app/Http/Requests/`, `app/Http/Resources/`, `app/Enums/` | 요청 검증과 응답 모양 분리 |
| ⑦ | `docs/notes/day2-2-eloquent-relationships.md` | `app/Models/` 3개, `database/migrations/` | 테이블끼리 연결 |
| 📒 | `docs/WORKLOG.md` | — | 실제로 친 명령어와 에러 기록 (필요할 때 찾아보기) |

> 💡 처음 읽을 때는 노트의 "Spring 비교" 표는 건너뛰어도 된다. 두 번째 읽을 때 보면 훨씬 잘 들어온다.

---

## 1. 큰 그림 — 식당으로 비유하기

```
 [손님]                 [식당 = 서버]                              [창고 = DB]
 curl / 앱 / 브라우저 ──주문──▶  php artisan serve  ──▶ Laravel 코드 ──꺼내기/넣기──▶ database.sqlite
                      ◀─음식──   (주소: 127.0.0.1:8000)  (요리사)     ◀────────────
       요청(Request)               응답(Response, JSON)
```

| 비유 | 실제 | 우리 프로젝트에서 |
|---|---|---|
| 손님 | **클라이언트**: 요청을 보내는 쪽 | 터미널의 `curl`, 나중에는 케어네이션 앱 |
| 주문서 | **HTTP 요청** | `POST /api/care-requests` + JSON 본문 |
| 식당 건물 | **서버**: 요청을 받아 주는 프로그램 | `php artisan serve` |
| 요리사 | **애플리케이션 코드** | `care-match/app/` 안의 Controller, Model |
| 메뉴판 | **라우트**: 어떤 주문을 받는지 | `routes/api.php` |
| 창고 | **데이터베이스**: 데이터를 오래 보관 | `care-match/database/database.sqlite` 파일 |
| 음식 | **HTTP 응답** | `{"data": {...}}` JSON + 상태 코드(200, 201, 422 …) |

**핵심 세 가지**
1. 서버를 켜야 손님(요청)을 받을 수 있다. 끄면 `curl`이 "연결 거부"로 실패한다.
2. 서버를 꺼도 창고(DB)에 넣은 데이터는 남아 있다. DB는 파일이라서 그렇다.
3. 요리사(코드)를 고치면, 이 서버는 다음 요청부터 바로 반영된다(PHP는 요청마다 코드를 새로 읽는다).

---

## 2. 서버 — "요청을 받아 주는 프로그램"

### 2-1. 켜기 / 끄기
```bash
cd ~/Documents/laravel-study/care-match
php artisan serve                 # 켜기 → "Server running on [http://127.0.0.1:8000]"
# 끄기: 그 터미널에서 Ctrl + C
```
- 서버를 켠 터미널은 **계속 열어 둬야 한다.** 다른 명령은 **새 터미널 탭**을 열어서 친다.
- `127.0.0.1`(= `localhost`)은 "내 컴퓨터 자신"이라는 뜻이다. 그래서 지금 이 서버는 **내 컴퓨터에서만** 접속된다.
- `8000`은 포트 번호다. 한 컴퓨터 안의 "문 번호"라고 생각하면 된다. 이미 쓰고 있으면 `php artisan serve --port=8001`로 바꾼다.

### 2-2. 서버가 살아 있는지 확인
```bash
curl http://127.0.0.1:8000/up          # 200이 오면 정상 (헬스체크)
php artisan route:list --path=api      # 서버가 받아 주는 API 목록 (메뉴판)
```

### 2-3. 요청 하나가 서버 안에서 지나가는 길
```
public/index.php          ← 모든 요청이 들어오는 정문
  → 미들웨어               ← 보안 검색대 (CORS, 공백 제거, 나중에 로그인 확인)
  → routes/api.php        ← "이 주소는 어느 컨트롤러로?"
  → FormRequest           ← 주문서 검사 (빠진 값, 잘못된 값이면 422로 돌려보냄)
  → Controller            ← 실제 일 처리
  → Model                 ← DB에서 꺼내거나 저장
  → Resource              ← 응답 JSON 모양 만들기
  → 응답
```

### 2-4. ⚠️ `php artisan serve`는 연습용 서버다
실제 서비스(운영)에서는 이 명령을 쓰지 않는다. **Nginx(웹 서버) + PHP-FPM(PHP 실행기)** 조합을 쓴다. 회사 서버도 대부분 이 구조다. Spring의 내장 Tomcat 대신 외부 Tomcat에 올리는 것과 비슷한 차이다. → 7장

---

## 3. DB — "데이터를 오래 보관하는 창고"

### 3-1. 지금 쓰는 DB: SQLite
- **SQLite는 DB 전체가 파일 하나**다: `care-match/database/database.sqlite`
- MySQL처럼 따로 켜 둘 DB 서버가 필요 없다. 파일을 지우면 DB도 사라진다.
- 학습용으로 아주 편하다. Spring에서 H2 파일 DB를 쓰던 것과 비슷하다.
- 회사에서는 거의 확실히 **MySQL**(또는 MariaDB, PostgreSQL)을 쓴다. → 6장

### 3-2. 지금 DB에 있는 테이블
| 테이블 | 내용 | 누가 만들었나 |
|---|---|---|
| `users` | 사용자 (role: GUARDIAN 보호자 / CAREGIVER 간병인) | Laravel 기본 + Day 2에서 role 추가 |
| `care_requests` | 간병 요청 (환자 이름, 장소, 기간, 상태, 보호자 id) | Day 1 + Day 2에서 guardian_id 추가 |
| `applications` | 간병인의 지원 (어느 요청에, 누가, 메시지, 상태) | Day 2 |
| `migrations` | "어떤 마이그레이션을 실행했나" 기록장 | Laravel 자동 |
| `personal_access_tokens` | 로그인 토큰 (Day 4에서 사용) | install:api |
| `cache`, `jobs`, `sessions` … | Laravel 기본 기능용 | Laravel 기본 |

### 3-3. DB 안을 들여다보는 3가지 방법
```bash
cd care-match

# ① Laravel 명령 (가장 쉬움)
php artisan db:show                      # DB 종류, 테이블 목록
php artisan db:table care_requests       # 컬럼, FK, 인덱스

# ② sqlite3 (맥에 기본으로 설치되어 있음) — SQL을 직접 실행
sqlite3 -header -column database/database.sqlite "SELECT id, name, email, role FROM users LIMIT 5;"

# ③ tinker — PHP 코드로 모델을 직접 다뤄 보기 (Day 2에서 사용)
php artisan tinker
>>> App\Models\CareRequest::count()
>>> App\Models\CareRequest::first()->guardian->name
>>> exit
```
> GUI가 편하면 **TablePlus**나 **DBeaver**로 `database.sqlite` 파일을 열면 된다(무료 버전으로 충분하다). 나중에 회사 MySQL도 같은 프로그램으로 접속한다.

### 3-4. 테이블은 어떻게 만들어지나 — 마이그레이션
- 테이블 구조는 SQL로 직접 만들지 않고 `database/migrations/` 안의 PHP 파일로 적는다.
- `php artisan migrate`를 실행하면 **아직 실행 안 한 파일만** 순서대로 실행된다.
- 실행한 기록은 `migrations` 테이블에 남는다.

```bash
php artisan migrate:status        # 어떤 파일이 실행됐는지 (Ran / Pending)
php artisan migrate               # 아직 안 된 것 실행
php artisan migrate:fresh --seed  # ⚠️ 전부 지우고 처음부터 + 가짜 데이터 넣기 (로컬에서만!)
```

### 3-5. 가짜 데이터 — Factory와 Seeder
- `database/factories/`: "가짜 사용자나 요청을 이렇게 만들어라"라는 틀
- `database/seeders/DatabaseSeeder.php`: "틀로 몇 개를 만들어라"라는 실행 스크립트
- 지금 들어 있는 테스트 계정 (비밀번호는 둘 다 `password`, Day 4 로그인에 사용)

| id | 이메일 | 역할 |
|---|---|---|
| 1 | `guardian@example.com` | 보호자 |
| 2 | `caregiver@example.com` | 간병인 |

데이터가 꼬이면 `php artisan migrate:fresh --seed` 한 번으로 깨끗하게 되돌릴 수 있다.

---

## 4. 로컬 설정 — `.env` 파일

### 4-1. `.env`는 "이 컴퓨터에서만 쓰는 설정 + 비밀번호 보관함"이다
`care-match/.env`를 열어 보면 이런 줄들이 있다.

```dotenv
APP_NAME=Laravel
APP_ENV=local              # 지금 환경: local(내 컴퓨터) / production(운영)
APP_KEY=base64:....        # 암호화 열쇠. 절대 공유하지 않는다
APP_DEBUG=true             # 에러가 나면 자세한 내용을 보여 줄지 (운영에서는 반드시 false)
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite       # 어떤 DB를 쓸지 → 지금은 SQLite 파일
# DB_HOST=127.0.0.1        # MySQL로 바꿀 때 주석(#)을 풀고 채운다 (6장)
# DB_PORT=3306
# DB_DATABASE=laravel
# DB_USERNAME=root
# DB_PASSWORD=
```

### 4-2. `.env`와 `.env.example`의 차이
| | `.env` | `.env.example` |
|---|---|---|
| 내용 | 진짜 값 (비밀번호, 키) | 빈칸이나 예시 값만 있는 **양식** |
| GitHub에 올라가나? | ❌ 안 올라감 (`.gitignore`) | ✅ 올라감 |
| 용도 | 내 컴퓨터에서 실제로 사용 | 새로 받은 사람이 복사해서 `.env`를 만드는 용도 |

→ 그래서 GitHub에서 프로젝트를 받으면 **`.env`가 없다.** 직접 만들어야 한다(5장).

### 4-3. 설정 값은 어디서 읽나
```
.env  ──(env())──▶  config/database.php, config/app.php ...  ──(config())──▶  내 코드
```
- `config/` 폴더의 파일들이 `.env` 값을 읽어서 정리해 둔다.
- 내 코드에서는 `config('app.timezone')`처럼 **`config()`로 읽는다.** `env()`는 `config/` 파일 안에서만 쓴다.
- `.env`를 고쳤는데 반영이 안 되면 서버를 껐다 켜고, 그래도 안 되면 `php artisan config:clear`를 실행한다.

### 4-4. 아직 결정하지 않은 설정: 시간대
`config/app.php`의 `'timezone' => 'UTC'` 때문에 서버 시간이 한국보다 9시간 느리다. 회사 프로젝트에서 이 값이 무엇인지 확인해 보자(국내 서비스는 `Asia/Seoul`이 많다).

---

## 5. 처음부터 세팅하기 — 다른 컴퓨터나 새 폴더에서

GitHub에서 받은 직후에는 `.env`, `vendor/`(라이브러리), DB 파일이 **없다.** 아래 순서대로 하면 된다. *(새 폴더에 clone해서 이 순서대로 직접 실행해 보고 검증했다.)*

```bash
# 0) 준비물: PHP, Composer (맥: brew install php composer)
php -v && composer -V

# 1) 코드 받기
git clone https://github.com/chewbitdev/laravel-study.git
cd laravel-study/care-match

# 2) 라이브러리 설치 (vendor/ 폴더가 생김) — Gradle 의존성 다운로드와 같음
composer install

# 3) 설정 파일 만들기
cp .env.example .env            # 양식을 복사해서
php artisan key:generate        # 암호화 열쇠(APP_KEY) 채우기

# 4) DB 만들기
touch database/database.sqlite  # 빈 DB 파일 만들기
php artisan migrate --seed      # 테이블 만들기 + 가짜 데이터 넣기

# 5) 서버 켜기
php artisan serve

# 6) (새 터미널에서) 확인
curl -H 'Accept: application/json' http://127.0.0.1:8000/api/care-requests
```

**회사 프로젝트를 처음 받았을 때도 순서는 거의 같다.** 보통 다음이 다르다.
- 3단계: `.env`에 넣을 값(DB 주소, 외부 API 키)을 선배나 사내 문서에서 받아야 한다.
- 4단계: SQLite 대신 회사 개발 DB나 Docker MySQL에 연결한다.
- 추가: 프론트 자원이 있으면 `npm install && npm run dev`도 실행한다.

---

## 6. 나중에 연결하기 ① — SQLite → MySQL로 바꾸기

> ⏳ 아직 실습하지 않은 부분이다. 방법만 미리 알아 두고 실제로 할 때 이 문서를 업데이트한다.

### 6-1. 바꾸는 이유
- 회사는 MySQL을 쓴다. SQLite와 MySQL은 미묘하게 다르다(날짜 처리, 대소문자 비교, 락 동작 등).
- 특히 Day 4의 `lockForUpdate`(비관적 락)는 **SQLite에서 제대로 확인할 수 없다.** MySQL에서 실습해야 한다.

### 6-2. MySQL 서버 띄우기 (방법 둘 중 하나)
```bash
# A. Docker로 (추천: 지우기 쉽고, 버전을 고르기 쉬움)
docker run -d --name care-mysql \
  -e MYSQL_ROOT_PASSWORD=secret \
  -e MYSQL_DATABASE=care_match \
  -p 3306:3306 mysql:8.4

# B. Homebrew로
brew install mysql && brew services start mysql
mysql -u root -e "CREATE DATABASE care_match;"
```

### 6-3. Laravel에 "이제 MySQL을 써"라고 알려 주기 — `.env`만 바꾸면 된다
```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1        # DB 서버 주소 (내 컴퓨터)
DB_PORT=3306             # MySQL 기본 포트
DB_DATABASE=care_match   # DB 이름
DB_USERNAME=root
DB_PASSWORD=secret
```
```bash
php artisan config:clear
php artisan db:show              # Connection이 mysql로 나오면 연결 성공
php artisan migrate:fresh --seed # MySQL에 테이블 + 데이터 만들기
```
> PHP가 MySQL과 대화하는 드라이버(`pdo_mysql`)는 brew로 설치한 PHP에 이미 들어 있다(`php -m | grep pdo`로 확인).

**코드는 한 줄도 안 바꾼다.** 마이그레이션과 Eloquent가 DB 차이를 감춰 주기 때문이다. Spring에서 `application.yml`의 datasource URL만 바꾸는 것과 같다.

### 6-4. 회사 개발 DB에 연결할 때 주의할 점
- 접속 정보(호스트, 계정)는 사내 문서나 선배에게 받는다. **절대 GitHub이나 메신저에 평문으로 올리지 않는다.**
- 사내 DB는 보통 외부에서 바로 접속되지 않는다. **VPN**이나 **SSH 터널**(점프 서버를 거쳐 접속)이 필요할 수 있다.
- ⚠️ **공용 개발 DB에서는 `migrate:fresh`, `db:seed`, `migrate:rollback`을 절대 실행하지 않는다.** 다른 사람 데이터가 전부 사라진다. 먼저 `php artisan db:show`로 **지금 어느 DB에 붙어 있는지 확인하는 습관**을 들이자.

---

## 7. 나중에 연결하기 ② — 다른 기기에서 내 서버에 접속하기

| 상황 | 방법 |
|---|---|
| 같은 와이파이의 휴대폰에서 테스트 | `php artisan serve --host=0.0.0.0` → 휴대폰에서 `http://내맥IP:8000` |
| Flutter 앱(에뮬레이터)에서 호출 | Android 에뮬레이터는 `http://10.0.2.2:8000`, iOS 시뮬레이터는 `http://127.0.0.1:8000` |
| 외부에서 잠깐 접속 | Cloudflare Tunnel이나 ngrok으로 임시 공개 주소 받기 |
| 실제 운영 | 클라우드 서버 + Nginx + PHP-FPM + MySQL (+ HTTPS) |

운영 서버 구조 (회사 서버도 대부분 이렇다):
```
앱/브라우저 ──HTTPS──▶ Nginx (정문, 443 포트) ──▶ PHP-FPM (Laravel 실행) ──▶ MySQL
                                                        └──▶ Redis (캐시, 큐) — Day 5
```

---

## 8. 지금까지 만든 API (메뉴판)

Accept 헤더를 항상 붙인다: `-H 'Accept: application/json'`

| 메서드 | 주소 | 하는 일 | 요청 예시 |
|---|---|---|---|
| GET | `/api/care-requests` | 요청 목록 (페이징) | `?status=PENDING&per_page=10&page=2` |
| POST | `/api/care-requests` | 요청 만들기 | `{"guardian_id":1,"patient_name":"홍길동","location":"전주","start_date":"2026-12-01","end_date":"2026-12-03"}` |
| GET | `/api/care-requests/{id}` | 요청 하나 보기 | |
| PATCH | `/api/care-requests/{id}` | 일부 수정 | `{"location":"전주 덕진구"}` |
| DELETE | `/api/care-requests/{id}` | 삭제 (지원도 같이 삭제) | |
| GET | `/api/care-requests/{id}/applications` | 지원자 목록 | |
| POST | `/api/care-requests/{id}/applications` | 간병인이 지원 | `{"caregiver_id":2,"message":"주말 가능합니다."}` |

```bash
# 복사해서 바로 쓰는 예시
curl -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -X POST http://127.0.0.1:8000/api/care-requests \
  -d '{"guardian_id":1,"patient_name":"홍길동","location":"전주","start_date":"2026-12-01","end_date":"2026-12-03"}'
```
> `start_date`는 **오늘 이후**여야 한다(검증 규칙 `after_or_equal:today`). 예시 날짜가 지났다면 바꿔서 쓴다.

### 응답 상태 코드 읽는 법
| 코드 | 뜻 | 우리 프로젝트에서 언제? |
|---|---|---|
| 200 | 성공 | 조회, 수정 |
| 201 | 만들어짐 | 요청 생성, 지원 |
| 204 | 성공, 돌려줄 내용 없음 | 삭제 |
| 404 | 없음 | 없는 id |
| 422 | 입력값이 잘못됨 | 검증 실패 (`errors`에 이유가 담김) |
| 500 | 서버 코드 에러 | → `storage/logs/laravel.log` 확인 |

---

## 9. 막혔을 때 보는 곳

| 증상 | 원인과 해결 |
|---|---|
| `curl: Failed to connect` | 서버가 꺼져 있음 → `php artisan serve` |
| `Address already in use` | 8000번 포트를 이미 사용 중 → `--port=8001` 또는 기존 서버 종료 |
| 500 에러 | `storage/logs/laravel.log`의 **맨 아래**를 본다. `tail -50 storage/logs/laravel.log` |
| 422인데 이유를 모르겠음 | 응답 JSON의 `errors` 항목에 필드별 이유가 있다 |
| 302가 옴 | `Accept: application/json` 헤더가 빠졌을 가능성 |
| 새로 만든 FormRequest에서 403 | `authorize()`가 `false`로 남아 있음 → `true`로 |
| 마이그레이션 실패 후 꼬임 | (로컬이면) `php artisan migrate:fresh --seed` |
| `.env`를 고쳤는데 반영이 안 됨 | 서버 재시작, `php artisan config:clear` |
| `Class "..." not found` | `use` 문(import) 확인, 또는 `composer dump-autoload` |

---

## 10. 자주 쓰는 명령어 모음

```bash
# 서버
php artisan serve                       # 켜기 (끄기: Ctrl+C)
php artisan route:list --path=api       # API 목록

# DB
php artisan db:show                     # 어느 DB에 연결됐는지 + 테이블 목록
php artisan db:table users              # 테이블 구조
php artisan migrate:status              # 마이그레이션 실행 여부
php artisan migrate                     # 새 마이그레이션 실행
php artisan migrate:fresh --seed        # ⚠️ 로컬 전용: 전부 초기화 + 가짜 데이터
php artisan tinker                      # 모델을 직접 만져 보는 콘솔

# 파일 만들기
php artisan make:model 이름 -mf          # 모델 + 마이그레이션 + 팩토리
php artisan make:controller 이름
php artisan make:request 이름            # ⚠️ authorize() true로 바꾸기
php artisan make:resource 이름
php artisan make:migration add_컬럼_to_테이블_table --table=테이블

# 문제 해결
tail -50 storage/logs/laravel.log       # 에러 로그
php artisan config:clear                # 설정 캐시 지우기
```

---

## 11. 복습 체크리스트

아래 질문에 **소리 내서 설명할 수 있으면** 복습 완료다. 막히면 괄호 안의 문서를 다시 본다.

- [ ] 서버를 켜고, `/up`과 API 목록을 확인할 수 있다 (2장)
- [ ] 우리 DB가 어디에 있고, 어떤 테이블이 있는지 말할 수 있다 (3장)
- [ ] `.env`와 `.env.example`의 차이, `.env`를 GitHub에 올리면 안 되는 이유를 안다 (4장)
- [ ] GitHub에서 새로 받아서 서버를 켜기까지의 순서를 안다 (5장)
- [ ] SQLite에서 MySQL로 바꿀 때 무엇을 고치는지 안다 (6장)
- [ ] 요청 하나가 `index.php` → 라우트 → FormRequest → Controller → Model → Resource로 흐르는 과정을 설명할 수 있다 (2-3, `day1-2`)
- [ ] `$fillable`이 왜 필요한지 안다 (`day1-3`)
- [ ] FormRequest와 Resource가 Spring의 무엇에 해당하는지 안다 (`day2-1`)
- [ ] `belongsTo`와 `hasMany` 중 어느 쪽에 FK가 있는지 안다 (`day2-2`)
- [ ] `$user->careRequests()`와 `$user->careRequests`의 차이를 안다 (`day2-2`)
- [ ] 운영 DB에 NOT NULL 컬럼을 추가할 때 왜 단계를 나누는지 안다 (`day2-2`)

---

## 12. 다음에 배울 것

| 단계 | 내용 |
|---|---|
| Day 2 - 3교시 | **N+1 문제**: 목록 조회 시 쿼리가 1 + N번 실행되는 문제를 `with()`로 해결 |
| Day 3 | 마이그레이션 심화, 시간대 결정, (권장) MySQL로 전환 |
| Day 4 | 로그인(Sanctum), 미들웨어, 매칭 수락(트랜잭션 + 비관적 락) |
| Day 5 | Queue/Job, 스케줄러, 테스트, README와 ERD 문서화 |
