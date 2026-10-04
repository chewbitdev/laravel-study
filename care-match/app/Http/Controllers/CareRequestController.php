<?php

namespace App\Http\Controllers;

use App\Models\CareRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

// @RestController + @RequestMapping("/api/care-requests")
// URL 매핑은 어노테이션이 아니라 routes/api.php의 Route::apiResource()가 한다.
class CareRequestController extends Controller
{
    // GET /api/care-requests  (@GetMapping)
    public function index()
    {
        // SELECT * FROM care_requests ORDER BY created_at DESC
        // repository.findAll(Sort.by(DESC, "createdAt")) 와 같다.
        // 모델/컬렉션을 return하면 Laravel이 알아서 JSON으로 직렬화한다 (Jackson 역할).
        return CareRequest::latest()->get();
    }

    // POST /api/care-requests  (@PostMapping + @Valid @RequestBody)
    public function store(Request $request): JsonResponse
    {
        // Bean Validation(@NotBlank, @Size...)을 배열 규칙으로 쓴다.
        // 실패하면 여기서 바로 예외 → 422 + 에러 JSON 응답 (MethodArgumentNotValidException 자동 처리)
        // 반환값은 "검증을 통과한 필드만" 담긴 배열이다.
        $validated = $request->validate([
            'patient_name' => ['required', 'string', 'max:50'],
            'location' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        // INSERT. $fillable에 있는 키만 들어간다. (repository.save(new CareRequest(...)))
        $careRequest = CareRequest::create($validated);

        // ResponseEntity.status(201).body(careRequest)
        return response()->json($careRequest, 201);
    }

    // GET /api/care-requests/{care_request}  (@GetMapping("/{id}"))
    // Route Model Binding: URL의 {care_request} 값(id)으로 Laravel이 미리 조회해서 넣어준다.
    // = repository.findById(id).orElseThrow(() -> new NotFoundException()) 를 자동으로.
    // 없는 id면 컨트롤러에 들어오기도 전에 404.
    public function show(CareRequest $careRequest)
    {
        return $careRequest;
    }

    // PUT/PATCH /api/care-requests/{care_request}
    public function update(Request $request, CareRequest $careRequest)
    {
        // sometimes: "요청에 그 필드가 있을 때만" 검증한다 → 부분 수정(PATCH) 가능.
        // 날짜 앞뒤 관계(after_or_equal)는 한쪽만 올 수도 있어서 여기서는 생략 (Day 2에서 개선)
        $validated = $request->validate([
            'patient_name' => ['sometimes', 'required', 'string', 'max:50'],
            'location' => ['sometimes', 'required', 'string', 'max:255'],
            'start_date' => ['sometimes', 'required', 'date'],
            'end_date' => ['sometimes', 'required', 'date'],
        ]);

        // UPDATE ... SET (바뀐 컬럼만) WHERE id = ?
        // JPA 더티 체킹과 달리 save/update를 명시적으로 호출해야 저장된다.
        $careRequest->update($validated);

        return $careRequest;
    }

    // DELETE /api/care-requests/{care_request}
    public function destroy(CareRequest $careRequest): Response
    {
        $careRequest->delete();

        // ResponseEntity.noContent().build()
        return response()->noContent();   // 204
    }
}
