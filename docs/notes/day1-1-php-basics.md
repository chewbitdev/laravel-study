# Day 1 - 1교시: Java 개발자를 위한 PHP 기초

> 목표: Laravel 코드를 읽을 때 **문법 때문에 막히지 않기**
> 실습 파일: [`practice/day1-php-basics.php`](../../practice/day1-php-basics.php) → `php practice/day1-php-basics.php`

---

## 0. PHP는 어떻게 실행되나 (Spring과 가장 큰 차이)

| | Spring Boot | PHP (Laravel) |
|---|---|---|
| 프로세스 | JVM이 계속 떠 있음 | **요청마다** 앱을 새로 부팅하고, 응답 후 전부 버림 |
| Bean / 객체 | 싱글톤이 메모리에 상주 | 요청이 끝나면 사라짐 |
| static 변수 | 요청 간 공유됨 | 요청 간 공유 **안 됨** |
| 컴파일 | `javac` → `.class` | 없음 (인터프리터, OPcache가 캐싱) |

- 그래서 요청 사이에 상태를 저장하려면 반드시 DB, 캐시(Redis), 세션을 써야 한다.
- 대신 메모리 누수나 스레드 안전성 걱정이 거의 없다(요청 하나 = 프로세스 하나의 흐름).

---

## 1. 파일과 기본 문법

```php
<?php                       // PHP 파일은 이 태그로 시작 (닫는 ?> 는 생략하는 게 관례)
declare(strict_types=1);    // 엄격한 타입 검사 켜기

// 한 줄 주석
/* 여러 줄 주석 */
/** PHPDoc (JavaDoc과 같음) */
echo "출력\n";               // System.out.print
```
- 세미콜론 `;` 필수. 중괄호 `{}` 블록 구조는 Java와 같다.

---

## 2. 변수와 타입

```php
$name = "홍길동";     // 모든 변수 앞에 $
$age  = 30;
$ok   = true;
$none = null;
```
- 변수 선언 시 타입을 쓰지 않는다(동적 타입).
- 대신 **함수 파라미터, 반환값, 클래스 프로퍼티에는 타입을 쓸 수 있다**. Laravel 코드는 대부분 타입을 쓴다.

| 타입 표기 | 의미 | Java 대응 |
|---|---|---|
| `int`, `float`, `string`, `bool` | 기본 타입 | `int`, `double`, `String`, `boolean` |
| `array` | 배열 (List/Map 겸용) | `List`, `Map` |
| `?string` | null 허용 | `@Nullable String` |
| `int\|string` | 유니언 타입 | 없음 |
| `void`, `mixed` | 반환 없음, 아무 타입 | `void`, `Object` |

---

## 3. 문자열

```php
"이름: $name"           // 큰따옴표 → 변수 치환됨
"이름: {$user->name}"   // 객체 속성이나 복잡한 식은 {} 로 감싼다
'이름: $name'           // 작은따옴표 → 그대로 출력 ("$name" 글자 그대로)
$a . $b                 // 문자열 연결은 + 가 아니라 . (점)
$s .= "추가";           // s += "추가"
```

---

## 4. 배열 — PHP의 핵심 자료구조

PHP 배열 하나가 Java의 `List`와 `Map` 역할을 **모두** 한다. Laravel은 설정, 검증 규칙, 요청 데이터를 거의 다 배열로 주고받는다.

```php
// List처럼
$list = ['서울', '부산'];
$list[] = '대구';               // list.add("대구")
$list[0];                       // list.get(0)

// Map처럼 (연관 배열)
$map = ['patient_name' => '홍길동', 'days' => 3];
$map['location'] = '전주';      // map.put("location", "전주")
$map['location'];               // map.get("location")
isset($map['memo']);            // map.containsKey("memo") (null이면 false)

// 반복
foreach ($list as $item) { }                 // for (String item : list)
foreach ($map as $key => $value) { }          // for (Entry e : map.entrySet())

// 자주 쓰는 함수
count($list);                                 // size()
in_array('서울', $list);                      // contains()
array_map(fn($x) => $x * 2, $nums);           // stream().map()
array_filter($nums, fn($x) => $x > 1);        // stream().filter()
implode(', ', $list);                         // String.join(", ", list)
```

Laravel에서 배열이 쓰이는 곳:
```php
$request->validate(['patient_name' => 'required|max:50']);  // 검증 규칙
protected $fillable = ['patient_name', 'location'];          // 대량 할당 허용 필드
return response()->json(['message' => 'ok'], 201);          // JSON 응답
```

> Laravel은 배열을 감싼 `Collection` 객체도 많이 쓴다(`$users->map()->filter()`). Java Stream과 비슷하며 Day 2에서 다룬다.

---

## 5. 비교 연산과 null 처리

```php
0 == "0"     // true  ← 타입 변환 후 비교 (느슨한 비교)
0 === "0"    // false ← 타입까지 비교 (엄격한 비교)  → 항상 === 를 쓴다
!==          // 엄격한 "같지 않음"

$memo = $data['memo'] ?? '기본값';    // null 병합: Optional.ofNullable(x).orElse("기본값")
$city = $user?->address?->city;       // null-safe: user가 null이면 전체가 null (Kotlin ?. 와 같음)
$x = $cond ? 'A' : 'B';               // 삼항 연산자 (Java와 같음)

$label = match ($status) {            // Java 14+ switch 표현식과 같음, === 로 비교
    'PENDING' => '대기',
    'MATCHED' => '매칭됨',
    default   => '기타',
};
```

---

## 6. 함수와 클로저

```php
function greet(string $name, string $suffix = '님'): string {   // 기본값 파라미터
    return $name . $suffix;
}
greet('김간병');
greet(suffix: ' 선생님', name: '이간병');   // named arguments (순서 상관없음)
```

### 익명 함수 (Laravel에서 아주 많이 씀)
```php
$rate = 15000;

// 1) 클로저: 바깥 변수를 쓰려면 use 로 명시해야 한다
$calc = function (int $h) use ($rate) { return $h * $rate; };

// 2) 화살표 함수: 한 줄짜리, 바깥 변수 자동 캡처 (Java 람다와 가장 비슷)
$calc = fn(int $h) => $h * $rate;
```
Laravel 예시:
```php
Route::get('/hello', function () { return 'hi'; });
DB::transaction(function () use ($careRequest) { ... });   // Day 4에서 사용
```

---

## 7. 클래스와 객체

```php
class CareRequest {
    public const DEFAULT_STATUS = 'PENDING';  // static final 상수
    private static int $count = 0;           // static 필드

    // 생성자 프로모션: 필드 선언 + 생성자 + this.x = x 를 한 번에
    public function __construct(
        public readonly string $patientName,  // readonly = final 필드
        private string $status = self::DEFAULT_STATUS,
    ) {
        self::$count++;
    }

    public function getStatus(): string {
        return $this->status;                 // this.status
    }
}

$req = new CareRequest('홍길동');
$req->getStatus();          // 인스턴스 멤버 → ->
CareRequest::DEFAULT_STATUS; // 정적 멤버, 상수 → ::
CareRequest::class;          // "App\Models\CareRequest" 같은 클래스 이름 문자열
```

| Java | PHP |
|---|---|
| `obj.field`, `obj.method()` | `$obj->field`, `$obj->method()` |
| `Clazz.staticMethod()` | `Clazz::staticMethod()` |
| `this` | `$this` |
| `ClassName.CONST` (자기 클래스 안) | `self::CONST` |
| `super.method()` | `parent::method()` |
| `Foo.class` | `Foo::class` |
| `public/protected/private` | 같음 (생략하면 public) |

### `::` 오해 주의
Laravel에서 `CareRequest::find(1)`, `Route::get(...)`처럼 `::`를 많이 쓴다. 겉보기는 static 호출이지만 실제로는 **Facade**나 **매직 메서드**(`__callStatic`)가 내부 인스턴스로 연결해 주는 경우가 많다. 지금은 "정적 호출처럼 쓰는 Laravel 스타일"이라고만 알아 두면 된다.

---

## 8. interface · abstract · trait · enum

```php
interface Matchable { public function match(string $c): void; }   // Java와 같음
abstract class BaseModel { abstract public function table(): string; }

class CareRequest extends BaseModel implements Matchable {
    use HasFactory, SoftDeletes;   // ← trait
}
```

### trait (Java에 없는 개념)
- **메서드 묶음을 클래스에 붙여 넣는 기능**이다. 상속(extends)은 하나만 되지만 trait는 여러 개를 쓸 수 있다.
- Java의 interface default 메서드와 비슷하지만, 필드도 가질 수 있다.
- Laravel 모델에서 `use HasFactory;`, `use Notifiable;`, `use SoftDeletes;`를 매우 자주 본다.
- 주의: 클래스 **안쪽**의 `use`는 trait 사용이고, 파일 **맨 위**의 `use`는 import다.

### enum (PHP 8.1+)
```php
enum CareStatus: string {
    case Pending = 'PENDING';
    case Matched = 'MATCHED';
}
CareStatus::Pending->value;         // 'PENDING'
CareStatus::from('MATCHED');        // 문자열 → enum (Java의 valueOf)
```

---

## 9. namespace · use · 오토로딩 · Composer

```php
namespace App\Http\Controllers;      // package com.example.controller;

use App\Models\CareRequest;          // import com.example.model.CareRequest;
use Illuminate\Http\Request;
```

- **PSR-4 오토로딩**: namespace와 폴더 경로가 대응된다. 패키지 경로와 폴더 구조가 같은 Java와 똑같은 규칙이다.
  - `App\Models\CareRequest` → `app/Models/CareRequest.php`
  - 이 대응 규칙은 `composer.json`의 `"autoload": {"psr-4": {"App\\": "app/"}}`에 정의돼 있다.
- **Composer** = Maven/Gradle

| Composer | Maven/Gradle |
|---|---|
| `composer.json` | `pom.xml` / `build.gradle` |
| `composer.lock` | (lock 파일) 정확한 버전 고정 |
| `vendor/` | `~/.m2` 또는 `build/libs` (git에 올리지 않음) |
| `composer install` | `mvn install` (lock 기준 설치) |
| `composer require 패키지` | 의존성 추가 |
| Packagist | Maven Central |

---

## 10. 매직 메서드 — Eloquent를 이해하는 열쇠

PHP는 `__`로 시작하는 특수 메서드를 지원한다.

| 매직 메서드 | 언제 호출되나 |
|---|---|
| `__construct` | 생성자 |
| `__get($key)` | **존재하지 않는** 프로퍼티를 읽을 때 |
| `__set($key, $v)` | **존재하지 않는** 프로퍼티에 쓸 때 |
| `__call` / `__callStatic` | 존재하지 않는 메서드를 호출할 때 |
| `__toString` | 문자열로 변환할 때 (Java toString) |

```php
class MiniModel {
    private array $attributes = [];
    public function __get($key) { return $this->attributes[$key] ?? null; }
    public function __set($key, $value) { $this->attributes[$key] = $value; }
}
$m = new MiniModel();
$m->status = 'PENDING';   // 필드 선언이 없는데 동작한다 → __set
echo $m->status;          // __get
```

**Eloquent 모델은 바로 이 방식으로 동작한다.**
- JPA는 `@Entity` 클래스에 `private String status;` 필드와 getter/setter를 모두 선언한다.
- Eloquent 모델 클래스는 **필드 선언이 거의 없다**. DB 컬럼 값은 내부 `$attributes` 배열에 들어 있고, `$careRequest->status`는 `__get`으로 꺼낸다.
- 그래서 Laravel 모델 파일을 열면 "필드가 어디 있지?" 하고 당황하게 된다. 컬럼 정보는 **migration 파일**에서 확인한다.

---

## 11. 예외

```php
try {
    throw new InvalidArgumentException('잘못된 상태');
} catch (InvalidArgumentException | DomainException $e) {   // multi-catch
    echo $e->getMessage();
} finally { }
```
- Java와 거의 같다. 다만 **checked exception이 없다**. `throws` 선언도 하지 않는다.
- Laravel에서는 `findOrFail()`이 `ModelNotFoundException`을 던진다. 프레임워크가 이 예외를 자동으로 404 응답으로 바꿔 준다(Spring의 `@ControllerAdvice` 역할을 기본 제공).

---

## 한눈에 보는 치트시트

| 하고 싶은 것 | Java | PHP |
|---|---|---|
| 변수 | `String s = "a";` | `$s = "a";` |
| 문자열 연결 | `a + b` | `$a . $b` |
| 리스트 | `List.of(1, 2)` | `[1, 2]` |
| 맵 | `Map.of("k", 1)` | `['k' => 1]` |
| 반복 | `for (var x : list)` | `foreach ($list as $x)` |
| 동등 비교 | `equals()` | `===` |
| null 기본값 | `Optional.orElse()` | `??` |
| 람다 | `x -> x * 2` | `fn($x) => $x * 2` |
| 멤버 접근 | `obj.method()` | `$obj->method()` |
| 정적 접근 | `Clazz.method()` | `Clazz::method()` |
| import | `import a.b.C;` | `use A\B\C;` |
| 빌드 도구 | Maven/Gradle | Composer |
