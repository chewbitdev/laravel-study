<?php

namespace App\Http\Controllers;

use App\Enums\CareStatus;
use App\Http\Requests\StoreCareRequestRequest;
use App\Http\Requests\UpdateCareRequestRequest;
use App\Http\Resources\CareRequestResource;
use App\Models\CareRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

// @RestController + @RequestMapping("/api/care-requests")
// URL 매핑은 어노테이션이 아니라 routes/api.php의 Route::apiResource()가 한다.
class CareRequestController extends Controller
{
    // GET /api/care-requests?status=PENDING&per_page=10&page=2
    // (@GetMapping + @RequestParam + Pageable)
    public function index(Request $request): AnonymousResourceCollection
    {
        // 쿼리스트링도 validate로 검증할 수 있다.
        // Rule::enum: CareStatus에 있는 값만 허용 (PENDING, MATCHED, ...)
        $filters = $request->validate([
            'status' => ['sometimes', Rule::enum(CareStatus::class)],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
        ]);

        $careRequests = CareRequest::query()
            // when(조건, 콜백): 조건이 참일 때만 where를 붙인다 (동적 쿼리, QueryDSL의 BooleanBuilder 느낌)
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest()          // ORDER BY created_at DESC
            ->latest('id')      // 같은 초에 생성된 행의 순서를 보장 (Day 1에서 발견한 문제)
            ->paginate($filters['per_page'] ?? 15);   // LIMIT/OFFSET + COUNT 쿼리, ?page=N 은 자동으로 읽음

        // 컬렉션 → Resource 배열. 페이지 정보(meta, links)가 자동으로 붙는다.
        return CareRequestResource::collection($careRequests);
    }

    // POST /api/care-requests
    // 파라미터 타입이 FormRequest → 메서드 실행 전에 authorize() + rules() 검증이 끝나 있다.
    public function store(StoreCareRequestRequest $request): CareRequestResource
    {
        // validated(): 검증을 통과한 필드만 (Day 1의 $validated와 같음)
        $careRequest = CareRequest::create($request->validated());

        // 방금 생성된 모델($careRequest->wasRecentlyCreated === true)을 Resource로 반환하면
        // Laravel이 상태 코드를 자동으로 201로 정한다.
        return new CareRequestResource($careRequest);
    }

    // GET /api/care-requests/{care_request}  (Route Model Binding)
    public function show(CareRequest $careRequest): CareRequestResource
    {
        return new CareRequestResource($careRequest);
    }

    // PUT/PATCH /api/care-requests/{care_request}
    public function update(UpdateCareRequestRequest $request, CareRequest $careRequest): CareRequestResource
    {
        // JPA 더티 체킹과 달리 save/update를 명시적으로 호출해야 저장된다.
        $careRequest->update($request->validated());

        return new CareRequestResource($careRequest);
    }

    // DELETE /api/care-requests/{care_request}
    public function destroy(CareRequest $careRequest): Response
    {
        $careRequest->delete();

        return response()->noContent();   // 204
    }
}
