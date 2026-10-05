<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreApplicationRequest;
use App\Http\Resources\ApplicationResource;
use App\Models\CareRequest;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

// 중첩 리소스: /api/care-requests/{care_request}/applications
// Spring: @RequestMapping("/api/care-requests/{careRequestId}/applications")
class ApplicationController extends Controller
{
    // GET /api/care-requests/{care_request}/applications
    // 부모({care_request})도 Route Model Binding으로 받는다.
    public function index(CareRequest $careRequest): AnonymousResourceCollection
    {
        // 관계 메서드로 쿼리: SELECT * FROM applications WHERE care_request_id = ? ORDER BY ...
        // JPA: applicationRepository.findByCareRequestIdOrderByCreatedAtAsc(id)
        $applications = $careRequest->applications()->oldest()->get();

        return ApplicationResource::collection($applications);
    }

    // POST /api/care-requests/{care_request}/applications
    public function store(StoreApplicationRequest $request, CareRequest $careRequest): ApplicationResource
    {
        $caregiver = User::findOrFail($request->validated('caregiver_id'));

        // make(): 객체만 만들고 저장은 안 함 (new + 부모 FK 설정)
        // associate(): belongsTo 관계 설정 (application.setCaregiver(caregiver))
        // save(): INSERT
        $application = $careRequest->applications()->make($request->safe()->only('message'));
        $application->caregiver()->associate($caregiver);
        $application->save();

        return new ApplicationResource($application);   // 새로 생성 → 201 자동
    }
}
