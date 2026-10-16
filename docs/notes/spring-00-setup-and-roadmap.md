# Spring Boot 버전 care-match — 골격과 직접 만들기 로드맵

> Laravel `care-match`와 **똑같은 API를 Spring Boot로 직접 만들어 보면서** 두 프레임워크를 비교한다.
> 이 프로젝트는 **실행되는 빈 골격까지만** 준비되어 있다. 테이블, 엔티티, API는 아래 로드맵을 보고 **직접** 작성한다.

---

## 1. 무엇이 준비되어 있나

| 항목 | 값 |
|---|---|
| 위치 | `laravel-study/care-match-spring/` |
| Spring Boot | **4.1.1** (Spring Framework 7.0.9) |
| Java | 21 |
| 빌드 | Gradle (래퍼 `./gradlew` 포함 → Gradle 설치 불필요) |
| DB | H2 **파일** 모드 → `data/care-match.mv.db` (Laravel의 `database.sqlite`와 같은 역할) |
| 마이그레이션 | Flyway 12.4 → `src/main/resources/db/migration/` (비어 있음) |
| ORM | Hibernate 7.4 (JPA) |
| JSON | Jackson 3.1 (패키지 이름이 `com.fasterxml.jackson` → **`tools.jackson`**으로 바뀜. 어노테이션은 그대로 `com.fasterxml.jackson.annotation`) |
| 의존성 | webmvc, data-jpa, validation, flyway, h2, h2console, lombok |
| 포트 | **8080** (Laravel 8000과 동시에 켜서 비교 가능) |

### 생성 방법 (Spring Initializr)
```bash
curl -s "https://start.spring.io/starter.zip?type=gradle-project&language=java&bootVersion=4.1.1&javaVersion=21\
&groupId=com.study&artifactId=care-match-spring&name=care-match-spring&packageName=com.study.carematch\
&baseDir=care-match-spring&dependencies=web,data-jpa,validation,flyway,h2,lombok" -o cms.zip && unzip cms.zip
```
> ⚠️ **겪은 실수**: 처음에 `bootVersion=4.1.1.RELEASE`로 요청했다. Initializr 메타데이터의 id 표기를 그대로 쓴 것이다. 그러자 Gradle 생성은 서버 500 에러가 났고, Maven 생성은 됐지만 `pom.xml`에 `4.1.1.RELEASE`가 들어가서 의존성을 찾지 못했다. Maven Central에 올라간 버전 이름은 **`4.1.1`**이다. Spring Boot는 2.x 중반부터 `.RELEASE` 접미사를 쓰지 않는다.

### Spring Boot 4에서 바뀐 이름 (Boot 3까지 배운 것과 다른 점)
| Boot 3 | Boot 4 |
|---|---|
| `spring-boot-starter-web` | `spring-boot-starter-webmvc` |
| (h2 의존성만으로 콘솔 사용) | `spring-boot-h2console` 모듈을 따로 추가 |
| `flyway-core` | `spring-boot-starter-flyway` |
| `com.fasterxml.jackson.databind.*` | `tools.jackson.databind.*` (Jackson 3) |
| 테스트: `spring-boot-starter-test` 하나 | 기능별 `...-test` 스타터 (`webmvc-test`, `data-jpa-test` …) |

---

## 2. 실행 방법

```bash
cd care-match-spring
./gradlew bootRun          # 서버 켜기 (php artisan serve) → http://localhost:8080
./gradlew test             # 테스트 (php artisan test)
./gradlew build            # jar 만들기 → build/libs/care-match-spring-0.0.1-SNAPSHOT.jar
```
- Java 버전이 여러 개 설치돼 있다면 `export JAVA_HOME=$(/usr/libexec/java_home -v 21)`로 21을 고른다.
- **DB 보기**: 서버를 켠 상태에서 브라우저로 `http://localhost:8080/h2-console` 접속 → JDBC URL `jdbc:h2:file:./data/care-match`, User `sa`, 비밀번호 비움
- **DB 초기화** (Laravel `migrate:fresh`): 서버를 끄고 `rm -rf data/` → 다시 `bootRun`하면 Flyway가 V1부터 다시 실행한다.

### 지금 상태에서 실행하면 (확인 완료)
```
Flyway  : No migrations found. Are your locations set up correctly?   ← 아직 V1 파일이 없어서 (정상)
H2 console available at '/h2-console'
Tomcat started on port 8080
Started CareMatchSpringApplication in 1.779 seconds
GET /            → 404   (API가 아직 없으니 정상)
GET /h2-console  → 200
```

---

## 3. 골격 파일 구조

```
care-match-spring/
├── build.gradle                     ← composer.json (의존성, Java 버전)
├── settings.gradle                  ← 프로젝트 이름
├── gradlew, gradle/wrapper/         ← php artisan 같은 실행 도구 (Gradle 자동 다운로드)
├── src/main/java/com/study/carematch/
│   ├── CareMatchSpringApplication.java   ← main(). public/index.php + bootstrap/app.php 역할
│   ├── domain/       (비어 있음)  ← app/Models, app/Enums
│   ├── repository/   (비어 있음)  ← (Laravel에 없음. 모델이 직접 DB 접근)
│   ├── service/      (비어 있음)  ← (Laravel에서는 Controller + FormRequest after()에 있던 로직)
│   ├── controller/   (비어 있음)  ← app/Http/Controllers + routes/api.php
│   ├── dto/          (비어 있음)  ← app/Http/Requests(FormRequest) + app/Http/Resources
│   ├── exception/    (비어 있음)  ← bootstrap/app.php의 withExceptions()
│   └── config/       (비어 있음)  ← AppServiceProvider, DatabaseSeeder
├── src/main/resources/
│   ├── application.properties       ← .env + config/*.php
│   └── db/migration/ (비어 있음)  ← database/migrations
├── src/test/java/.../CareMatchSpringApplicationTests.java  ← 앱이 뜨는지만 확인하는 기본 테스트
└── data/            (git 제외)    ← H2 DB 파일 (database/database.sqlite)
```
각 패키지의 `package-info.java`에는 역할과 Laravel 대응만 한 줄씩 적혀 있다. 코드는 없다.

### `application.properties`에 미리 넣어 둔 설정 (Laravel과 API 모양을 맞추기 위한 것)
| 설정 | 이유 |
|---|---|
| `spring.datasource.url=jdbc:h2:file:./data/care-match` | SQLite처럼 파일 하나짜리 DB |
| `spring.jpa.hibernate.ddl-auto=validate` | 테이블은 Flyway가 만들고, Hibernate는 엔티티와 테이블이 맞는지 **검사만** 한다 |
| `spring.jpa.open-in-view=false` | 지연 로딩은 서비스(트랜잭션) 안에서만. N+1 실습 때 차이가 드러난다 |
| `spring.jackson.property-naming-strategy=SNAKE_CASE` | JSON 키를 Laravel처럼 `patient_name`으로 |
| `spring.data.web.pageable.one-indexed-parameters=true` | `?page=1`부터 시작 (Spring 기본은 0) |
| `spring.data.web.pageable.size-parameter=per_page` | Laravel과 같은 `?per_page=` |
| `default-page-size=15`, `max-page-size=100` | Laravel `paginate(15)`, `between:1,100`과 맞춤 |

이 설정들은 언제든 바꿔도 된다. 바꾸면 Laravel과 응답 모양이 어떻게 달라지는지 비교해 보는 것도 공부가 된다.

---

## 4. 🛠️ 직접 만들기 로드맵

> **원칙**: 각 단계에서 ① 왼쪽의 Laravel 파일을 다시 읽고 → ② 오른쪽 Spring 파일을 직접 작성하고 → ③ Laravel 때와 **같은 curl**로 확인한다.
> 정답 코드는 적지 않았다. 막히면 "찾아볼 키워드"를 검색하거나 질문하자(힌트 위주로 답한다).

### 1단계: 테이블 (Flyway)
| Laravel에서 볼 파일 | Spring에서 만들 파일 |
|---|---|
| `0001_01_01_000000_create_users_table.php` | `db/migration/V1__create_users_table.sql` |
| `..._create_care_requests_table.php` | `V2__create_care_requests_table.sql` |
| `..._add_role_to_users_table.php` | `V3__add_role_to_users_table.sql` |
| `..._add_guardian_id_to_care_requests_table.php` | `V4__add_guardian_id_to_care_requests_table.sql` |
| `..._create_applications_table.php` | `V5__create_applications_table.sql` |

- 찾아볼 키워드: `Flyway naming V1__`, `H2 ALTER TABLE ADD COLUMN`, `FOREIGN KEY ON DELETE CASCADE`, `UNIQUE constraint`
- 확인: `bootRun` 로그에 `Successfully applied 5 migrations`가 나오는지, h2-console에서 테이블 4개(users, care_requests, applications, flyway_schema_history)가 보이는지
- 💡 V4는 Day 2에서 실패를 겪은 그 "nullable → 채우기 → NOT NULL" 3단계로 써 보자.
- 💡 한 번 실행된 V 파일을 고치면 Flyway가 **체크섬 불일치**로 앱 시작을 거부한다. 실습 중에 고치고 싶으면 `data/`를 지우고 처음부터 다시 실행한다.

### 2단계: enum과 엔티티 (`domain/`)
| Laravel | Spring |
|---|---|
| `app/Enums/CareStatus.php`, `UserRole.php`, `ApplicationStatus.php` | `domain/CareStatus.java` … (label 메서드 포함) |
| `app/Models/User.php` | `domain/User.java` |
| `app/Models/CareRequest.php` | `domain/CareRequest.java` |
| `app/Models/Application.php` | `domain/Application.java` |

- 찾아볼 키워드: `@Entity @Table`, `@GeneratedValue IDENTITY`, `@Enumerated(EnumType.STRING)`, `@ManyToOne(fetch = LAZY) @JoinColumn`, `@OneToMany(mappedBy)`, `@CreationTimestamp @UpdateTimestamp`, Lombok `@Getter @NoArgsConstructor(access = PROTECTED)`
- 확인: `bootRun`이 에러 없이 뜨면 성공이다. `ddl-auto=validate`가 엔티티와 테이블을 대조해 준다.
- 💡 Laravel의 `$fillable`(status는 못 바꾸게)을 Spring에서는 어떻게 지킬까? → **setter를 열지 않고**, 생성 메서드와 변경 메서드만 둔다.
- 💡 validate 에러 메시지에 컬럼 **타입이 다르다**는 내용(예: enum 컬럼)이 나오면, 엔티티의 타입 매핑과 SQL 컬럼 타입을 비교해 보자. 키워드: `Hibernate 6 enum H2 validate`, `@JdbcTypeCode`

### 3단계: Repository (`repository/`)
| Laravel | Spring |
|---|---|
| `CareRequest::find()`, `->where('status', ...)`, `->paginate()` | `CareRequestRepository extends JpaRepository<CareRequest, Long>` |
| `Rule::exists('users','id')->where('role', ...)` | `UserRepository`의 id + role 조회 메서드 |
| `Rule::unique('applications', ...)` | `ApplicationRepository`의 `existsBy...` 메서드 |

- 찾아볼 키워드: `Spring Data JPA query methods`, `findByStatus(..., Pageable)`, `existsBy`
- 💡 Laravel에는 이 계층이 없다. "모델이 곧 Repository"(Active Record)였던 것과 비교해 보자.

### 4단계: DTO (`dto/`)
| Laravel | Spring |
|---|---|
| `StoreCareRequestRequest.php` (rules) | `CreateCareRequestRequest` record + Bean Validation |
| `UpdateCareRequestRequest.php` (`sometimes`) | `UpdateCareRequestRequest` record |
| `StoreApplicationRequest.php` | `CreateApplicationRequest` record |
| `CareRequestResource.php` | `CareRequestResponse` record (`days`, `status_label`, `guardian`) |
| `ApplicationResource.php` | `ApplicationResponse` record |
| `{"data": ...}`, `links`, `meta` | 감싸는 응답 record (선택) |

- 찾아볼 키워드: `Java record DTO`, `@NotBlank @Size @NotNull @FutureOrPresent`, `Bean Validation null vs absent`
- 💡 Laravel `sometimes`("보냈을 때만 검사")는 Spring에서 어떻게 표현할까? null이면 "안 보냄"으로 보는 방법과 그 한계를 생각해 보자.
- 💡 `end_date >= start_date`처럼 **여러 필드를 엮는 검사**(Laravel `after()`)는 어디서 할까? 클래스 레벨 커스텀 제약과 서비스 계층 검사 중에서 골라 보자.

### 5단계: Service (`service/`)
- Laravel에서 컨트롤러와 `after()`에 흩어져 있던 로직을 모은다: 보호자 확인, 날짜 앞뒤 검사, 중복 지원 검사, PENDING 상태 검사
- 찾아볼 키워드: `@Service`, `@Transactional(readOnly = true)`, `dirty checking`
- 💡 **수정(update)에서 `save()`를 호출하지 않아도 저장되는지** 확인해 보자. Laravel은 `$model->update()`나 `save()`가 반드시 필요했다.

### 6단계: Controller (`controller/`)
| Laravel `route:list` | Spring |
|---|---|
| `GET/POST api/care-requests` | `@GetMapping`, `@PostMapping` |
| `GET/PUT/PATCH/DELETE api/care-requests/{care_request}` | `@GetMapping("/{id}")`, `@PatchMapping`, `@DeleteMapping` |
| `GET/POST api/care-requests/{care_request}/applications` | `@RequestMapping("/api/care-requests/{careRequestId}/applications")` |

- 찾아볼 키워드: `@RestController @RequestMapping`, `@PathVariable`, `@RequestParam(required = false)`, `Pageable 파라미터`, `ResponseEntity.status(201)`, `ResponseEntity.noContent()`
- 💡 Laravel의 Route Model Binding(id → 모델 자동 조회, 없으면 404)을 Spring에서는 누가 대신하나?

### 7단계: 예외 처리 (`exception/`)
- Laravel이 **자동으로** 해 주던 것을 직접 만든다.
  - 검증 실패 → 422 + `{"message", "errors": {"필드": ["메시지"]}}`
  - 없는 id → 404
  - 잘못된 enum 쿼리 파라미터(`?status=WRONG`) → 422
- 찾아볼 키워드: `@RestControllerAdvice`, `MethodArgumentNotValidException`, `MethodArgumentTypeMismatchException`, `HttpMessageNotReadableException`
- 💡 Spring의 검증 실패 기본 상태 코드는 **400**이다. Laravel(422)과 맞출지, Spring 관례(400)를 따를지 직접 정해 보자.
- 💡 에러 응답의 필드 이름이 `guardianId`(Java)로 나오는지 `guardian_id`(JSON)로 나오는지 확인하자.

### 8단계: 개발용 데이터 (`config/`)
- Laravel `DatabaseSeeder`처럼 테스트 계정 2개(id 1 보호자, id 2 간병인)와 보호자 5, 간병인 10, 요청, 지원을 넣는다.
- 찾아볼 키워드: `CommandLineRunner`, `@Profile("local")`, `repository.count() == 0 이면 실행`
- 💡 비밀번호는 Day 4(Spring Security, BCrypt)에서 제대로 다룬다.

### 9단계: Laravel과 나란히 테스트
두 서버를 동시에 켜고 **같은 요청**을 보내서 응답을 비교한다.
```bash
# 터미널 1: cd care-match && php artisan serve          → :8000
# 터미널 2: cd care-match-spring && ./gradlew bootRun   → :8080
for port in 8000 8080; do
  curl -s -H 'Accept: application/json' "http://127.0.0.1:$port/api/care-requests?per_page=2" | jq -c '.meta'
done
```
Laravel 노트의 테스트 표(`day1-3` 6장, `day2-1` 6장, `day2-2` 8장)에 있는 시나리오를 그대로 체크리스트로 쓰면 된다.

---

## 5. 만들면서 비교할 질문 (완성 후 스스로 답해 보기)

1. 같은 기능을 만드는 데 Laravel은 파일 몇 개, Spring은 파일 몇 개가 필요했나? 어느 쪽 코드가 "어디서 무슨 일이 일어나는지" 더 잘 보이나?
2. Laravel의 `$fillable`과 Spring의 "setter 없는 엔티티 + DTO"는 각각 무엇을 막나?
3. Laravel은 `update()`를 꼭 불러야 했다. Spring은 왜 안 불러도 저장되나? 그 대신 조심할 점은?
4. Route Model Binding과 `findById().orElseThrow()` 중 어느 쪽이 더 편하고, 어느 쪽이 더 명확한가?
5. 같은 목록 API에서 두 프레임워크가 실행하는 SQL 수는 같은가? (→ N+1, Day 2 3교시)
