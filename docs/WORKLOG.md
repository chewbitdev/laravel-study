# 작업 로그 (설치·작업 기록)

Laravel 학습 중에 실행한 설치와 명령어, 파일 변경, 문제 해결 과정을 날짜순으로 기록한다.
각 항목은 **무엇을 했나 → 명령어 → 결과/확인 → 메모(Spring 비교, 주의점)** 순서로 쓴다.

---

## 2026-10-04 (일) Day 1

### 0. 학습 문서 정리
- `CLAUDE.md`에서 개인 정보 섹션("나에 대해")을 삭제했다. 목표, 일정, 미니 프로젝트, 진행 방식은 그대로 뒀다.
- 이 작업 로그 파일 `docs/WORKLOG.md`를 새로 만들었다.

### 1. 환경 확인
```bash
which php composer brew
```
- 결과: `brew`만 있음(`/opt/homebrew/bin/brew`). `php`와 `composer`는 없음.

### 2. PHP · Composer 설치
```bash
brew install php composer
```
- 상태: ✅ 완료 (exit 0)
- 확인
  ```bash
  php -v        # PHP 8.5.11 (cli) (NTS)
  composer -V   # Composer version 2.10.3
  ```
  - 설치 경로: `/opt/homebrew/bin/php`, `/opt/homebrew/bin/composer`
  - 설정 파일(php.ini): `/opt/homebrew/etc/php/8.5/`
- 설치 안내 중 Apache나 php-fpm 설정 부분은 **무시해도 된다**. 개발할 때는 `php artisan serve`로 내장 서버를 띄운다. Spring Boot의 내장 Tomcat과 비슷하다.
- 메모
  - PHP는 Java로 치면 JDK, 즉 언어 런타임이다.
  - Composer는 Maven이나 Gradle 같은 의존성 관리 도구다. `composer.json`이 `pom.xml`이나 `build.gradle`에 해당한다.

### 3. Git 저장소 분리 + GitHub 연결
- 문제: `laravel-study`의 상위 폴더가 다른 git 저장소에 포함되어 있었다. 그대로 커밋하면 엉뚱한 저장소에 들어간다.
- 해결: 이 폴더에 별도 저장소를 만들었다. 하위 폴더에 `.git`이 있으면 git은 가장 가까운 `.git`을 기준으로 동작한다.
```bash
cd ~/Documents/laravel-study
printf '.DS_Store\n.idea/\n.vscode/\n' > .gitignore
git init -b main
git add -A
git commit -m "..."
gh repo create chewbitdev/laravel-study --public --source . --push
```
- 저장소: https://github.com/chewbitdev/laravel-study (Public)
- 커밋 규칙: 의미 있는 작업 단위마다 커밋하고 push한다. 메시지는 한국어, `type: 내용` 형식이다(예: `feat: CareRequest CRUD API`, `docs: 작업 로그 갱신`).
