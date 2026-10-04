# Day 1 확인 질문 복습

결과: **7문항 모두 정답.** 아래는 답변에 덧붙일 보충 설명이다.

---

### 1. `->` vs `::`
- `->`는 인스턴스 멤버, `::`는 클래스(static) 멤버와 상수에 접근한다.
- `User::find()`는 실제 static 메서드가 아니다. `Model::__callStatic`이 `new static`으로 인스턴스를 만들고 쿼리 빌더로 넘긴다.
- **보충**: `::` 뒤의 동작은 두 종류로 구분해 두면 좋다.
  - `User::find(1)`, `User::where(...)`: **모델의 `__callStatic`** → 쿼리 빌더로 전달
  - `Route::get()`, `DB::table()`, `Cache::get()`: **Facade**. `Illuminate\Support\Facades\Route` 클래스의 `__callStatic`이 서비스 컨테이너(Spring의 ApplicationContext)에서 실제 객체(`Router` Bean)를 꺼내 호출을 넘긴다.
  - 둘 다 "정적 호출처럼 보이지만 실제로는 인스턴스 메서드 호출"이라는 점은 같다. Facade는 Day 4의 서비스 컨테이너와 DI에서 다시 다룬다.

### 2. 작은따옴표
- `Hi $name`이 출력된다. 작은따옴표 문자열은 변수를 해석하지 않는다.
- **보충**: 작은따옴표는 `\n`도 해석하지 않는다. `'a\nb'`는 줄바꿈 없이 글자 그대로 `a\nb`가 된다.

### 3. 배열 → Java
- `Map<String, Object>`, 순서까지 고려하면 `LinkedHashMap`이 맞다. PHP 배열은 넣은 순서를 유지하는 ordered map이다.
- **보충**: 그래서 `[0 => 'a', 1 => 'b']`와 `['a', 'b']`는 같은 배열이다. PHP에서는 List도 "키가 0, 1, 2…인 Map"이다.

### 4. `$fillable`
- Mass Assignment 방어용 화이트리스트다. `status`는 비즈니스 로직(매칭 수락 등)으로만 바뀌어야 하는 값이라서 뺐다.
- 정확한 답이다. Day 4의 매칭 수락에서 `$careRequest->status = ...; $careRequest->save();`처럼 **명시적으로** 바꾸는 코드를 쓰게 된다.

### 5. Route Model Binding
- 암묵적(implicit) 바인딩이 `{care_request}`와 `CareRequest $careRequest`를 연결해서 `findOrFail`을 실행한다. 없는 id면 `ModelNotFoundException`이 나고 404가 된다.
- **보충**: id가 아닌 다른 컬럼으로 찾고 싶다면 라우트에 `{careRequest:uuid}`처럼 쓰면 된다(`WHERE uuid = ?`).

### 6. Accept 헤더
- 성공 응답은 같고, 에러 응답만 `expectsJson()`에 따라 달라진다. 설정 위치는 Laravel 11 이상이면 `bootstrap/app.php`, 10 이하면 `app/Exceptions/Handler.php`다.
- **보충 (이 프로젝트 기준)**: `shouldRenderJsonWhen`에 `$request->is('api/*')`가 있어서 `/api/*`는 **헤더가 없어도** 검증 실패 422, 없는 id 404 JSON이 나온다. 표의 "헤더 없음" 열은 이 설정이 없는 경우의 동작이다. 실습 6번에서 직접 확인했다.
- 인증 실패 + 헤더 없음 → `route('login')`으로 리다이렉트를 시도한다. `login` 라우트가 없으면 `RouteNotFoundException`이 나서 500이 된다. Day 4에서 직접 재현해 보자.

### 7. Spring으로 만든다면
- Entity + enum, Repository, 요청 DTO, 응답 DTO, Service, Controller, ControllerAdvice, Flyway까지 Laravel 대응을 정확히 짚었다.
- 표에 있는 **FormRequest**(요청 DTO + @Valid), **API Resource**(응답 DTO), **Service 분리**를 Day 2에서 실제로 적용한다.
