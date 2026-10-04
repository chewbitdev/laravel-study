# Laravel 학습 프로젝트 – 이어서 진행하기 위한 맥락

이 파일은 Claude(Cowork)와 나눈 대화를 VS Code의 Claude Code에서 이어가기 위해 만든 인수인계 문서다.
항상 한국어로 답하고, 개념을 설명할 때는 Spring Boot와 비교해서 설명한다.

## 목표
- 케어네이션(돌봄 매칭 플랫폼) 백엔드 인턴 합격 (미래내일 일경험, 8주)
  - 사전직무교육: 2026-10-07 ~ 10-08 (한국표준협회 가산)
  - 근무: 2026-10-12 ~ 12-04, 평일 10:00~16:00
  - 업무: Laravel 프로젝트 구조 분석, 간단한 기능 개발·수정, 개발 문서 및 작업 내역 정리
- 첫 출근 전까지 "Laravel 프로젝트를 열었을 때 길을 잃지 않는 것"이 목표

## 학습 일정
| 날짜 | 내용 |
|---|---|
| 10/4 (일) Day 1 | PHP 기초, 설치, CareRequest CRUD API |
| 10/5 (월) Day 2 | 라우트·컨트롤러·검증 심화, Eloquent 관계, N+1 |
| 10/6 (화) Day 3 | Migration, applications 테이블 추가, 미니 프로젝트 설계 |
| 10/7~8 | 사전직무교육 (가볍게 복습만) |
| 10/9 (금) Day 4 | Sanctum 인증, 미들웨어, DB::transaction + lockForUpdate로 매칭 수락 |
| 10/10 (토) Day 5 | Queue/Job, 스케줄러(오래된 PENDING 만료), 테스트 맛보기, README·ERD 문서화 |
| 10/11 (일) | 휴식, 케어네이션 앱 써보기, 첫 주 체크리스트 |

## 미니 프로젝트: care-match (간병 매칭 API)
- 위치: 이 폴더 아래 `care-match/` (Laravel 13.34, PHP 8.5, SQLite, install:api 완료)
- 테이블: users(보호자/간병인 role), care_requests(간병 요청), applications(간병인 지원)
- 상태 전이: PENDING → MATCHED → IN_PROGRESS → DONE
- 핵심: 보호자가 지원자를 수락할 때 트랜잭션 + 비관적 락으로 중복 매칭 방지

## 현재 진행 상황: Day 2 - 1교시 완료, 2교시(Eloquent 관계) 차례
Day 1 ✅ (노트 `docs/notes/day1-*.md`, 확인 질문 7/7 정답 → `day1-4-review.md`)

Day 2
- ✅ 1교시: 라우트 심화(개념), FormRequest, API Resource, Enum cast, 페이징과 필터 (`docs/notes/day2-1-request-response-layer.md`)
- ⏭ 2교시: Eloquent 관계. users에 role 추가, applications 테이블, hasMany/belongsTo, 중첩 라우트
- ⏭ 3교시: N+1 문제와 `with()` eager loading, 쿼리 로그로 확인
- Day 2가 끝나면 확인 질문 3~4개

미뤄 둔 결정
- timezone: `config/app.php`가 UTC → `today()`가 한국 날짜보다 하루 늦을 수 있음. Day 3에서 Asia/Seoul로 바꿀지 결정
- User 모델에 HasApiTokens 추가 → Day 4

## 진행 방식
- 코드를 대신 다 짜주기보다, 단계별로 같이 만들고 주석으로 Spring 대응 개념을 달아준다.
- **개념 설명을 먼저 빠짐없이 한다.** 퀴즈나 확인 질문을 수업 중간에 끼워 넣지 않고, Day가 끝날 때 3~4개만 낸다.
- 각 교시의 강의 내용은 `docs/notes/dayN-M-주제.md`에 저장하고, 실행할 수 있는 예제는 `practice/`에 둔다.
- 진행할 때마다 이 파일의 "현재 진행 상황"을 업데이트한다.
- **모든 설치 과정과 작업(실행한 명령어, 생성·수정한 파일, 에러와 해결)을 `docs/WORKLOG.md`에 날짜순으로 기록한다.** 작업할 때마다 바로 추가하고, 나중에 몰아서 쓰지 않는다.
- **의미 있는 작업 단위마다 커밋하고 GitHub(https://github.com/chewbitdev/laravel-study)에 push한다.** 이 프로젝트에서는 사용자가 자동 커밋을 허락했다. 메시지는 한국어 `type: 내용` 형식으로 쓰고, Co-Authored-By 태그는 붙이지 않는다.
- git 저장소 루트는 이 폴더(`laravel-study/`)다. 상위 폴더의 다른 저장소에 커밋하지 않는다.
- **이 저장소는 Public이다. 이 학습과 관계없는 개인 프로젝트, 계정, 경로 같은 사적인 내용은 문서와 커밋 메시지에 절대 쓰지 않는다.**
