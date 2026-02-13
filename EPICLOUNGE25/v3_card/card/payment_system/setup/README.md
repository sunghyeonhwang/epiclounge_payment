# 그리프 카드 결제 시스템 - 설치 가이드

## 1단계: 데이터베이스 설정

### MySQL/MariaDB에 테이블 생성

```bash
# MySQL 로그인
mysql -u root -p

# 데이터베이스 선택 (기존 DB 사용)
USE your_database_name;

# 스키마 실행
SOURCE /path/to/payment_system/setup/database_schema.sql;

# 테이블 확인
SHOW TABLES LIKE 'griff_payment_%';
```

**생성되는 테이블:**
- `griff_payment_admins` - 관리자 계정
- `griff_payment_links` - 결제 링크
- `griff_payment_transactions` - 거래 내역
- `griff_payment_notifications` - 알림 로그

## 2단계: 설정 파일 수정

`libs/config.php` 파일을 열어 다음 정보를 수정하세요:

```php
// 데이터베이스
define('DB_HOST', 'localhost');           // MySQL 호스트
define('DB_NAME', 'database_name');       // 데이터베이스명
define('DB_USER', 'username');            // 사용자명
define('DB_PASS', 'password');            // 비밀번호

// INICIS (운영/테스트 환경 선택)
define('INICIS_MID', 'MOIepiclou');                          // 상점 ID
define('INICIS_SIGNKEY', 'Wno0S3hIQVhUZ1BKSHFYMXRIVUJpQT09'); // SignKey

// DirectSend SMS
define('SMS_USERNAME', 'griff16');
define('SMS_KEY', 'BaIpwA1FNBOYszC');
define('SMS_SENDER', '023263701');

// 시스템 URL
define('SITE_URL', 'https://epiclounge.co.kr/card/payment_system');
```

## 3단계: 초기 관리자 생성

### 방법 1: 브라우저에서 실행
```
https://yourdomain.com/card/payment_system/setup/create_admin.php
```

### 방법 2: CLI에서 실행
```bash
cd /path/to/payment_system/setup
php create_admin.php
```

**초기 관리자 정보:**
- 아이디: `admin`
- 비밀번호: `griff2025!`

⚠️ **보안 주의사항:**
1. 로그인 후 반드시 비밀번호를 변경하세요
2. `setup/` 디렉토리를 외부 접근 불가하도록 설정하세요
3. 또는 초기 설정 완료 후 `create_admin.php` 파일을 삭제하세요

## 4단계: 파일 권한 설정

```bash
# 디렉토리 권한
chmod 755 payment_system/
chmod 755 payment_system/admin/
chmod 755 payment_system/payment/
chmod 755 payment_system/libs/

# PHP 파일 권한
chmod 644 payment_system/**/*.php
```

## 5단계: 관리자 로그인

```
https://yourdomain.com/card/payment_system/admin/
```

로그인 후:
1. 비밀번호 변경
2. 테스트 결제 링크 생성
3. 결제 테스트 진행

## 문제 해결

### 데이터베이스 연결 오류
- `libs/config.php`에서 DB 정보 확인
- MySQL 서비스 실행 상태 확인
- 방화벽 설정 확인

### INICIS 결제 오류
- MID와 SignKey 확인
- 테스트/운영 환경 확인
- HTTPS 설정 (운영 환경 필수)

### SMS 발송 오류
- DirectSend API 인증 정보 확인
- 발신번호 등록 여부 확인

## 다음 단계

✅ 관리자 로그인 성공
✅ 비밀번호 변경 완료
✅ 테스트 결제 링크 생성
✅ SMS/이메일 발송 테스트
✅ 결제 테스트 (INICIS 테스트 환경)
✅ 운영 환경 전환 (INICIS MID/SignKey 변경)
