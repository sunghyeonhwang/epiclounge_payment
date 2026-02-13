# 그리프 카드 결제 관리 시스템

관리자가 결제 링크를 생성하고, 고객이 해당 링크로 결제할 수 있는 시스템입니다.

## 주요 기능

### 관리자
- ✅ 결제 링크 생성 (금액, 상품명, 설명 자유 설정)
- ✅ SMS/이메일 자동 발송
- ✅ 결제 내역 조회 및 관리
- ✅ 대시보드 통계

### 결제자
- ✅ 카드결제, 계좌이체, 간편결제 지원
- ✅ PC/모바일 반응형
- ✅ 결제 완료 알림

## 디렉토리 구조

```
payment_system/
├── admin/              # 관리자 페이지
│   ├── components/     # 공통 컴포넌트
│   ├── index.php       # 로그인
│   ├── dashboard.php   # 대시보드
│   ├── create_payment.php   # 결제 링크 생성
│   ├── payment_list.php     # 결제 내역
│   └── payment_detail.php   # 결제 상세
├── payment/            # 결제자 페이지
│   ├── index.php       # 결제 페이지
│   ├── success.php     # 결제 완료
│   └── fail.php        # 결제 실패
├── libs/               # 공통 라이브러리
│   ├── config.php      # 설정 (⚠️ 수정 필요)
│   ├── db.php          # 데이터베이스
│   ├── auth.php        # 인증
│   ├── payment.php     # 결제 관리
│   ├── sms.php         # SMS 발송
│   └── email.php       # 이메일 발송
├── inicis/             # INICIS 라이브러리
│   ├── pc/             # PC 결제
│   └── mobile/         # 모바일 결제
└── setup/              # 설치 스크립트
    ├── database_schema.sql   # DB 테이블
    ├── create_admin.php      # 초기 관리자 생성
    └── README.md             # 설치 가이드
```

## 설치 방법

### 1. 파일 업로드
`payment_system/` 폴더 전체를 서버에 업로드합니다.

```
서버 경로 예시:
/card/payment_system/
```

### 2. 데이터베이스 설정

MySQL에 접속하여 테이블을 생성합니다:

```sql
SOURCE /path/to/payment_system/setup/database_schema.sql;
```

4개 테이블이 생성됩니다:
- `griff_payment_admins` - 관리자
- `griff_payment_links` - 결제 링크
- `griff_payment_transactions` - 거래 내역
- `griff_payment_notifications` - 알림 로그

### 3. 설정 파일 수정

`libs/config.php` 파일을 열어 다음 정보를 수정하세요:

```php
// 데이터베이스
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');  // ⚠️ 수정
define('DB_USER', 'your_username');       // ⚠️ 수정
define('DB_PASS', 'your_password');       // ⚠️ 수정

// INICIS (운영/테스트 환경 선택)
define('INICIS_MID', 'MOIepiclou');       // ⚠️ 확인
define('INICIS_SIGNKEY', '...');          // ⚠️ 확인

// DirectSend SMS
define('SMS_USERNAME', 'griff16');        // ⚠️ 확인
define('SMS_KEY', 'BaIpwA1FNBOYszC');    // ⚠️ 확인
define('SMS_SENDER', '023263701');        // ⚠️ 확인

// 시스템 URL
define('SITE_URL', 'https://epiclounge.co.kr/card/payment_system');  // ⚠️ 수정
```

### 4. 초기 관리자 생성

브라우저에서 다음 URL에 접속:

```
https://yourdomain.com/card/payment_system/setup/create_admin.php
```

**초기 관리자 정보:**
- 아이디: `admin`
- 비밀번호: `griff2025!`

⚠️ **보안 주의**: 로그인 후 반드시 비밀번호를 변경하세요!

### 5. 관리자 로그인

```
https://yourdomain.com/card/payment_system/admin/
```

## 사용 방법

### 결제 링크 생성
1. 관리자 페이지 로그인
2. "결제 링크 생성" 클릭
3. 상품명, 금액, 결제자 정보 입력
4. SMS/이메일 발송 체크 (선택)
5. 생성된 URL을 고객에게 전송

### 결제 진행
고객이 결제 URL 클릭 → 결제 정보 확인 → 결제하기 → 완료

## 보안 설정

### 필수 작업
1. ✅ `libs/config.php` 파일 권한 설정
   ```bash
   chmod 600 payment_system/libs/config.php
   ```

2. ✅ 초기 관리자 비밀번호 변경

3. ✅ `setup/create_admin.php` 파일 삭제 또는 접근 차단
   ```bash
   rm payment_system/setup/create_admin.php
   ```

4. ✅ HTTPS 사용 (운영 환경 필수)

### .htaccess 설정 (Apache)
```apache
# libs 디렉토리 접근 차단
<Directory /path/to/payment_system/libs>
    Require all denied
</Directory>

# setup 디렉토리 접근 차단
<Directory /path/to/payment_system/setup>
    Require all denied
</Directory>
```

## 테스트

### 1. 관리자 로그인 테스트
- URL: `/admin/index.php`
- 계정: admin / griff2025!

### 2. 결제 링크 생성 테스트
- 금액: 10,000원
- 상품명: 테스트 상품
- SMS 발송 체크

### 3. 결제 테스트
- INICIS 테스트 환경 사용 권장
- 테스트 카드로 결제 진행

## 문제 해결

### 데이터베이스 연결 오류
- `libs/config.php`에서 DB 정보 확인
- MySQL 서비스 실행 상태 확인

### INICIS 결제 오류
- MID와 SignKey 확인
- 테스트/운영 환경 확인
- HTTPS 설정 (운영 환경 필수)

### SMS 발송 오류
- DirectSend API 인증 정보 확인
- 발신번호 등록 여부 확인

## 기술 스택

- **PHP** 7.4+
- **MySQL** 5.7+
- **Tailwind CSS** (CDN)
- **INICIS** 결제 모듈
- **DirectSend** SMS API

## 라이선스

© 2025 그리프. All rights reserved.

## 지원

문의: (주)그리프
