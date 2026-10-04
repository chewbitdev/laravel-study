<?php
// 실행: php practice/day1-php-basics.php
declare(strict_types=1); // 타입을 엄격하게 검사한다 (Java처럼 "1"을 int 자리에 넣으면 TypeError)

// ─────────────────────────────────────────────
// 1. 변수와 문자열
// ─────────────────────────────────────────────
$name = "홍길동";
$age  = 30;
echo "1) 이름: $name, 나이: {$age}세\n";   // 큰따옴표: 변수 치환
echo '1) 이름: $name' . "\n";              // 작은따옴표: 치환 없음, 연결은 .

// ─────────────────────────────────────────────
// 2. 배열 (List + Map)
// ─────────────────────────────────────────────
$locations = ['서울', '부산', '전주'];                 // List<String>
$request   = ['patient_name' => '홍길동', 'days' => 3]; // Map<String, Object>
$request['location'] = '전주';                         // put
$locations[] = '대구';                                  // add (끝에 추가)

foreach ($request as $key => $value) {                 // for (Map.Entry e : map.entrySet())
    echo "2) $key = $value\n";
}
$upper = array_map(fn($l) => "[$l]", $locations);      // stream().map()
echo "2) " . implode(', ', $upper) . "\n";             // String.join
echo "2) 개수: " . count($locations) . "\n";

// ─────────────────────────────────────────────
// 3. 비교와 null 처리
// ─────────────────────────────────────────────
var_dump(0 == "0");     // true  (값만 비교, 형변환)
var_dump(0 === "0");    // false (타입까지 비교) ← 항상 이걸 쓴다
$memo = $request['memo'] ?? '메모 없음';   // Optional.ofNullable(...).orElse(...)
echo "3) $memo\n";

$status = 'MATCHED';
$label = match ($status) {                 // Java 14+ switch 표현식
    'PENDING' => '대기',
    'MATCHED' => '매칭됨',
    default   => '기타',
};
echo "3) 상태: $label\n";

// ─────────────────────────────────────────────
// 4. 함수
// ─────────────────────────────────────────────
function greet(string $name, string $suffix = '님'): string {
    return $name . $suffix;
}
echo "4) " . greet('김간병') . "\n";
echo "4) " . greet(suffix: ' 선생님', name: '이간병') . "\n"; // named arguments

$rate = 15000;
$calc = function (int $hours) use ($rate) { return $hours * $rate; }; // 클로저: 바깥 변수는 use로
$calc2 = fn(int $hours) => $hours * $rate;                             // 화살표 함수: 자동 캡처
echo "4) " . $calc(8) . " / " . $calc2(8) . "\n";

// ─────────────────────────────────────────────
// 5. 클래스 · enum · trait · interface
// ─────────────────────────────────────────────
enum CareStatus: string {          // Java enum + 값
    case Pending = 'PENDING';
    case Matched = 'MATCHED';
}

trait HasTimestamps {              // 클래스에 "복붙"되는 메서드 묶음 (Laravel의 HasFactory 등)
    public function touch(): string { return "updated_at 갱신"; }
}

interface Matchable {
    public function match(string $caregiver): void;
}

class CareRequest implements Matchable {
    use HasTimestamps;

    public const DEFAULT_STATUS = CareStatus::Pending;   // static final 상수
    private static int $count = 0;

    // 생성자 프로모션: 필드 선언 + 생성자 + this.x = x 를 한 줄에 (Lombok @AllArgsConstructor 느낌)
    public function __construct(
        public readonly string $patientName,
        private CareStatus $status = self::DEFAULT_STATUS,
        private ?string $caregiver = null,               // ?string = nullable
    ) {
        self::$count++;
    }

    public function match(string $caregiver): void {
        $this->caregiver = $caregiver;
        $this->status = CareStatus::Matched;
    }

    public function summary(): string {
        return "{$this->patientName} / {$this->status->value} / " . ($this->caregiver ?? '미정');
    }

    public static function count(): int { return self::$count; }
}

$req = new CareRequest('홍길동');
echo "5) " . $req->summary() . "\n";
$req->match('김간병');
echo "5) " . $req->summary() . "\n";
echo "5) " . $req->touch() . "\n";
echo "5) 생성된 개수: " . CareRequest::count() . "\n";
echo "5) 클래스 이름: " . CareRequest::class . "\n";   // Java의 CareRequest.class.getName()

// ─────────────────────────────────────────────
// 6. 매직 메서드 __get — Eloquent의 $model->status 가 동작하는 원리
// ─────────────────────────────────────────────
class MiniModel {
    private array $attributes = [];          // DB 컬럼 값이 여기 배열로 들어있다
    public function __get(string $key) { return $this->attributes[$key] ?? null; }
    public function __set(string $key, $value): void { $this->attributes[$key] = $value; }
}
$m = new MiniModel();
$m->status = 'PENDING';                       // 필드 선언 없이도 동작 → __set 호출
echo "6) " . $m->status . "\n";               // __get 호출

// ─────────────────────────────────────────────
// 7. 예외
// ─────────────────────────────────────────────
try {
    throw new InvalidArgumentException('잘못된 상태');
} catch (InvalidArgumentException $e) {
    echo "7) 예외: " . $e->getMessage() . "\n";
} finally {
    echo "7) finally 실행\n";
}
