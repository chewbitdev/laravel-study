<?php

use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\CareRequestController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// 한 줄로 REST 라우트 5개 등록 (index, store, show, update, destroy)
// 이 파일의 라우트에는 자동으로 /api 접두사가 붙는다 → /api/care-requests
Route::apiResource('care-requests', CareRequestController::class);

// 중첩 리소스: 점(.)으로 부모.자식 → /care-requests/{care_request}/applications
// only(): 지금은 목록과 지원(생성)만 필요
Route::apiResource('care-requests.applications', ApplicationController::class)
    ->only(['index', 'store']);
