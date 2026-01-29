# 사용자 인증 및 진행률 저장 시스템 구현 현황

> **목표**: localStorage 기반 진행률 추적을 MySQL/MariaDB + PHP 세션 기반 서버 시스템으로 전환

**업데이트 날짜**: 2026-01-29  
**호스팅 환경**: Cafe24 (MySQL/MariaDB 지원)  
**기술 스택**: PHP + MySQL + Vanilla JS

---

## 📋 Phase 1: 데이터베이스 설정 (Database Setup) ✅ 완료

### 1.1 MySQL 스키마 작성
- [x] `init_mysql.sql` 파일 생성
- [x] `users` 테이블 생성 (이메일, 비밀번호, 이름)
- [x] `courses` 테이블 생성 (코스 정보)
- [x] `lessons` 테이블 생성 (섹션 정보)
- [x] `lectures` 테이블 생성 (강의 정보 + Vimeo ID/hash)
- [x] `user_lecture_progress` 테이블 생성 (진행률)
- [x] `user_course_progress` 테이블 생성 (전체 진행률 캐싱)
- [x] 인덱스 설정 (user_id, lecture_id 등)
- [x] 외래 키(Foreign Key) 제약 조건 설정

### 1.2 데이터베이스 연결 확인
- [x] `config.php` DB 접속 정보 확인
  - [x] `$DB_HOST` 설정
  - [x] `$DB_NAME` 설정
  - [x] `$DB_USER` 설정
  - [x] `$DB_PASS` 설정
- [x] PDO 연결 테스트

### 1.3 스키마 적용 및 초기 데이터 입력
- [x] phpMyAdmin으로 `init_mysql.sql` 실행
- [x] 코스 데이터 입력 (바이브 코딩)
- [x] 섹션 데이터 입력 (바이브 코딩 기초)
- [x] 강의 데이터 입력 (6개 강의)
  - [x] 0. 오리엔테이션 (5분)
  - [x] 1. 바이브 코딩 도구 둘러보기 (40:57)
  - [x] 2. 바이브 코딩 Skill Up 1 (33:40)
  - [x] 3. 바이브 코딩 Skill Up 2 (32:48)
  - [x] 4. 사이트 실전 개발 1 (1:05:30)
  - [x] 5. 사이트 실전 개발 2 (1:08:10)
- [x] Vimeo ID, hash, duration 메타데이터 입력

---

## 📋 Phase 2: 백엔드 API 구현 (Backend API) ✅ 완료

### 2.1 인증 API
- [x] `api/auth/signup.php` 구현
  - [x] 이메일 형식 검증
  - [x] 비밀번호 강도 체크 (최소 8자)
  - [x] 이메일 중복 확인
  - [x] `password_hash()` 사용
  - [x] JSON 응답 형식
- [x] `api/auth/login.php` 구현
  - [x] 이메일/비밀번호 검증
  - [x] `password_verify()` 사용
  - [x] 세션 생성 (`$_SESSION['user_id']`)
  - [x] `session_regenerate_id()` 호출
  - [x] JSON 응답 (user_id, email, display_name)
- [x] `api/auth/logout.php` 구현
  - [x] 세션 파괴 (`session_destroy()`)
  - [x] JSON 응답
- [x] `api/auth/check.php` 구현
  - [x] 세션 확인
  - [x] 로그인 여부 + 사용자 정보 반환

### 2.2 진행률 API
- [x] `api/progress/get.php` 구현
  - [x] 인증 체크 (세션 확인)
  - [x] `course_id` 파라미터 처리
  - [x] SQL: 사용자의 모든 강의 진행률 조회
  - [x] JSON 응답 (lecture_id, last_position, completed)
- [x] `api/progress/save.php` 구현
  - [x] 인증 체크
  - [x] `lecture_id`, `last_position` 파라미터 처리
  - [x] SQL: UPSERT (`ON DUPLICATE KEY UPDATE`)
  - [x] JSON 응답
- [x] `api/progress/complete.php` 구현
  - [x] 인증 체크
  - [x] `lecture_id` 파라미터 처리
  - [x] SQL: `completed = 1`, `completed_at = NOW()`
  - [x] 전체 진행률 재계산
  - [x] JSON 응답

### 2.3 코스 API
- [x] `api/courses/get.php` 구현
  - [x] `course_id` 파라미터 처리
  - [x] SQL: 코스, 섹션, 강의 정보 JOIN
  - [x] JSON 응답 (기존 `course-data.json` 형식과 동일)
  - [x] 인증된 사용자의 경우 진행률 포함

### 2.4 보안 강화
- [x] SQL Injection 방지 (PDO prepared statements)
- [x] XSS 방지 (에러 메시지 출력 제한)
- [x] 세션 보안 설정 (`config.php`에서 세션 시작)
- [ ] CSRF 토큰 적용 (향후 추가 가능)

---

## 📋 Phase 3: 프론트엔드 수정 (Frontend) ✅ 완료

### 3.1 로그인/회원가입 페이지
- [x] `lesson/auth.html` 생성
  - [x] 로그인 폼 (이메일, 비밀번호)
  - [x] 회원가입 폼 (이메일, 비밀번호, 이름)
  - [x] 탭 전환 UI (로그인 ↔ 회원가입)
  - [x] 클라이언트 측 폼 검증
  - [x] 에러 메시지 표시
  - [x] 성공 시 `index.html`로 리다이렉트
  - [x] 다크 테마 스타일링

### 3.2 `client.js` 리팩토링
- [x] **초기화 플로우 변경**
  - [x] `fetch('/api/auth/check.php')` 추가
  - [x] 미인증 시 `auth.html`로 리다이렉트
  - [x] 인증 완료 시 코스 데이터 로드
- [x] **코스 데이터 로드**
  - [x] 서버 API로 변경 `fetch('/api/courses/get.php?id=1')`
  - [x] 응답 JSON을 기존 `courseData` 변수에 매핑
- [x] **진행률 자동 저장** (기존 로직 유지, 서버 API 사용)
- [x] **강의 완료 처리** (기존 로직 유지, 서버 API 사용)

### 3.3 `index.html` 수정
- [x] 로그아웃 버튼 추가 (헤더 우측)
  - [x] 클릭 시 `/api/auth/logout.php` 호출
  - [x] 성공 시 `auth.html`로 리다이렉트
  - [x] 확인 다이얼로그 추가

### 3.4 마이그레이션 로직
- [ ] localStorage → 서버 마이그레이션 (향후 추가 가능)
  - [ ] 로그인 후 localStorage 확인
  - [ ] 진행률 데이터가 있으면 서버로 업로드
  - [ ] 업로드 성공 시 localStorage 클리어
  - [ ] 사용자에게 알림 ("진행률이 계정에 저장되었습니다")

---

## 📋 Phase 4: 테스트 및 검증 (Testing)

### 4.1 기능 테스트
- [ ] **회원가입 테스트**
  - [ ] 정상 가입 (이메일 + 비밀번호)
  - [ ] 중복 이메일 에러
  - [ ] 비밀번호 해싱 확인 (DB 조회)
- [ ] **로그인 테스트**
  - [ ] 정상 로그인
  - [ ] 잘못된 비밀번호 에러
  - [ ] 존재하지 않는 이메일 에러
  - [ ] 세션 유지 확인 (새로고침)
- [ ] **진행률 저장 테스트**
  - [ ] 영상 시청 중 5초마다 자동 저장
  - [ ] DB 확인 (`last_position` 업데이트)
- [ ] **진행률 불러오기 테스트**
  - [ ] 새로고침 후 이어보기
  - [ ] 다른 브라우저에서 같은 계정 로그인
- [ ] **강의 완료 테스트**
  - [ ] 영상 끝까지 시청 시 `completed = 1`
  - [ ] 진행률 바 업데이트 (0/6 → 1/6)
- [ ] **로그아웃 테스트**
  - [ ] 로그아웃 후 세션 삭제 확인
  - [ ] `index.html` 접근 시 `auth.html`로 리다이렉트

### 4.2 보안 테스트
- [ ] **CSRF 공격 방지**
  - [ ] 토큰 없이 API 호출 시 거부
- [ ] **SQL Injection 방지**
  - [ ] `' OR '1'='1` 같은 입력 테스트
  - [ ] Prepared statements 사용 확인
- [ ] **XSS 공격 방지**
  - [ ] `<script>alert('XSS')</script>` 입력 테스트
  - [ ] 출력 시 `htmlspecialchars()` 사용 확인
- [ ] **세션 보안**
  - [ ] 로그인 후 `session_regenerate_id()` 호출 확인
  - [ ] 쿠키 설정 확인 (httponly, secure, samesite)

### 4.3 성능 테스트
- [ ] 진행률 저장 API 응답 시간 (< 200ms)
- [ ] 코스 데이터 로드 시간 (< 500ms)
- [ ] 동시 사용자 테스트 (10명 이상)

---

## 📋 Phase 5: 배포 및 모니터링 (Deployment)

### 5.1 배포 준비
- [ ] `config.php` 프로덕션 설정
  - [ ] 실제 DB 접속 정보 입력
  - [ ] 에러 표시 끄기 (`error_reporting(0)`)
- [ ] HTTPS 설정 확인 (Cafe24)
- [ ] `.gitignore` 업데이트
  - [ ] `config.php` 추가 (민감 정보 보호)
  - [ ] `.env` 파일 사용 권장

### 5.2 FTP 업로드
- [ ] `api/` 폴더 업로드
- [ ] `init_mysql.sql` 업로드 (임시, 실행 후 삭제)
- [ ] `lesson/auth.html` 업로드
- [ ] `lesson/client.js` 업데이트
- [ ] `lesson/index.html` 업데이트

### 5.3 데이터베이스 설정
- [ ] Cafe24 phpMyAdmin 접속
- [ ] 데이터베이스 생성 (`mendix_lessons`)
- [ ] `init_mysql.sql` 실행
- [ ] 테이블 생성 확인
- [ ] 초기 데이터 입력 확인

### 5.4 배포 후 검증
- [ ] 프로덕션 환경에서 회원가입 테스트
- [ ] 프로덕션 환경에서 로그인 테스트
- [ ] 프로덕션 환경에서 진행률 저장/불러오기 테스트
- [ ] 에러 로그 확인 (PHP error log)

### 5.5 모니터링
- [ ] 데이터베이스 용량 모니터링
- [ ] API 응답 시간 모니터링
- [ ] 사용자 피드백 수집

---

## 📋 Phase 6: 향후 확장 계획 (Future Enhancements)

### 6.1 소셜 로그인
- [ ] Google OAuth 연동
- [ ] Kakao OAuth 연동

### 6.2 비밀번호 관리
- [ ] 비밀번호 찾기/재설정 기능
- [ ] 이메일 인증 시스템

### 6.3 학습 통계
- [ ] 학습 시간 추적
- [ ] 대시보드 (진행률, 통계)
- [ ] 수료증 발급

### 6.4 커뮤니티 기능
- [ ] 코멘트/Q&A 시스템
- [ ] 강의 평점 시스템

---

## 🔗 관련 파일

- **계획 문서**: `/Users/griff_m5/.cursor/plans/사용자_인증_및_진행률_저장_시스템_e81ef933.plan.md`
- **기존 config.php**: `Medix/site/config.php`
- **기존 helpers.php**: `Medix/site/helpers.php`
- **강의 데이터**: `Medix/site/lesson/course-data.json`
- **클라이언트 JS**: `Medix/site/lesson/client.js`
- **메인 HTML**: `Medix/site/lesson/index.html`

---

## 📝 노트

### 현재 진행 상황
- ✅ 강의 플랫폼 기본 UI 완성 (다크 테마, 6개 강의)
- ✅ MySQL/MariaDB 데이터베이스 스키마 생성 및 적용
- ✅ 백엔드 API 구현 완료 (인증, 진행률, 코스)
- ✅ 로그인/회원가입 페이지 구현
- ✅ client.js 서버 API 연동 완료
- ⏳ FTP 업로드 및 프로덕션 테스트 예정

### 주요 결정 사항
1. **데이터베이스**: MySQL/MariaDB 선택 (Cafe24 호스팅 환경)
2. **인증 방식**: PHP 세션 기반 (JWT 대신)
3. **회원가입**: 이메일/비밀번호만 (소셜 로그인은 향후 추가)
4. **데이터 구조**: 서버 API에서 동적 생성 (JSON 파일 유지 옵션도 가능)

### 참고 사항
- 진행률은 사용자별로 localStorage가 아닌 DB에 저장되므로 **디바이스 간 동기화** 가능
- HTTPS 환경에서만 세션 쿠키 보안 설정 활성화
- `course-data.json`은 API로 대체하거나 static 백업으로 유지 가능

---

**작성일**: 2026-01-29  
**작성자**: AI Assistant  
**버전**: 1.0
