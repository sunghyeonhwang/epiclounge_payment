<?php
/**
 * 그리프 카드 결제 관리 시스템 - 설정 파일
 *
 * 주의: 이 파일에는 민감한 정보가 포함되어 있습니다.
 * Git에 커밋하지 마세요. (.gitignore에 추가)
 */

// ============================================
// 데이터베이스 설정
// ============================================
define('DB_HOST', 'localhost');
define('DB_NAME', '###');          // 실제 DB명으로 변경
define('DB_USER', '###');               // 실제 사용자명으로 변경
define('DB_PASS', '###!@');               // 실제 비밀번호로 변경
define('DB_CHARSET', 'utf8mb4');

// ============================================
// INICIS 결제 설정
// ============================================
// 운영 환경 (실제 결제)
define('INICIS_MID', 'MOIepiclou');                          // 운영 MID
define('INICIS_SIGNKEY', 'Wno0S3hIQVhUZ1BKSHFYMXRIVUJpQT09'); // 운영 SignKey
define('INICIS_APIKEY', 'nf2Vszdaxij1qXsm');                    // INICIS 취소/환불 API Key
define('INICIS_PC_SCRIPT', 'https://stdpay.inicis.com/stdjs/INIStdPay.js');
define('INICIS_MOBILE_URL', 'https://mobile.inicis.com/smart/payment/');

// 테스트 환경 (아래 주석 해제하고 위의 운영 환경 주석 처리)
/*
define('INICIS_MID', 'INIpayTest');                          // 테스트 MID
define('INICIS_SIGNKEY', 'SU5JTElURV9UUklQTEVERVNfS0VZU1RS'); // 테스트 SignKey
define('INICIS_PC_SCRIPT', 'https://stgstdpay.inicis.com/stdjs/INIStdPay.js');
define('INICIS_MOBILE_URL', 'https://stgmobile.inicis.com/smart/payment/');
*/

// ============================================
// DirectSend SMS API 설정
// ============================================
define('SMS_USERNAME', 'griff16');
define('SMS_KEY', 'BaIpwA1FNBOYszC');
define('SMS_SENDER', '023263701');           // 발신번호
define('SMS_API_URL', 'https://directsend.co.kr/index.php/api_v2/sms_change_word');

// ============================================
// 시스템 설정
// ============================================
define('SITE_NAME', '그리프');
define('SITE_URL', 'https://epiclounge.co.kr/card/payment_system');  // 실제 URL로 변경
define('PAYMENT_RECEIVER', '(주)그리프');                              // 결제 받는 자
define('PAYMENT_LINK_EXPIRE_DAYS', 30);                              // 링크 유효기간 (일)
define('SESSION_TIMEOUT', 3600);                                     // 세션 타임아웃 (초, 1시간)

// ============================================
// 보안 설정
// ============================================
define('HASH_ALGORITHM', 'sha256');
define('PASSWORD_COST', 12);                 // bcrypt cost (10-12 권장)

// ============================================
// 이메일 설정
// ============================================
define('EMAIL_FROM', 'noreply@griff.co.kr');
define('EMAIL_FROM_NAME', '그리프');

// ============================================
// 시스템 경로
// ============================================
define('BASE_PATH', __DIR__ . '/..');
define('LIBS_PATH', __DIR__);
define('INICIS_PC_PATH', BASE_PATH . '/inicis/pc');
define('INICIS_MOBILE_PATH', BASE_PATH . '/inicis/mobile');

// ============================================
// 환경 설정
// ============================================
date_default_timezone_set('Asia/Seoul');

// 에러 리포팅 (운영 환경에서는 주석 처리)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 세션 보안 설정
ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);  // 세션 GC 수명 = 타임아웃과 동일
ini_set('session.cookie_lifetime', 0);               // 브라우저 종료 시까지 유효
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);                 // HTTPS 전용
ini_set('session.save_path', sys_get_temp_dir());    // Cafe24 세션 경로 명시

// ============================================
// 헬퍼 함수
// ============================================

/**
 * 금액 포맷 (₩1,000)
 */
function format_currency($amount) {
    return '₩' . number_format($amount);
}

/**
 * 날짜 포맷 (2025-02-13 14:30)
 */
function format_datetime($datetime) {
    if (empty($datetime)) return '-';
    return date('Y-m-d H:i', strtotime($datetime));
}

/**
 * 날짜만 포맷 (2025-02-13)
 */
function format_date($datetime) {
    if (empty($datetime)) return '-';
    return date('Y-m-d', strtotime($datetime));
}

/**
 * 상대 시간 (3시간 전, 2일 전)
 */
function time_ago($datetime) {
    if (empty($datetime)) return '-';

    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;

    if ($diff < 60) return '방금 전';
    if ($diff < 3600) return floor($diff / 60) . '분 전';
    if ($diff < 86400) return floor($diff / 3600) . '시간 전';
    if ($diff < 604800) return floor($diff / 86400) . '일 전';

    return format_datetime($datetime);
}

/**
 * 결제 상태 한글 변환
 */
function get_payment_status_text($status) {
    $statuses = [
        'pending' => '대기',
        'completed' => '완료',
        'failed' => '실패',
        'refunded' => '환불'
    ];
    return $statuses[$status] ?? $status;
}

/**
 * 링크 상태 한글 변환
 */
function get_link_status_text($status) {
    $statuses = [
        'active' => '활성',
        'used' => '사용완료',
        'expired' => '만료',
        'cancelled' => '취소'
    ];
    return $statuses[$status] ?? $status;
}

/**
 * 상태별 색상 클래스 (Tailwind)
 */
function get_status_color($status) {
    $colors = [
        'pending' => 'yellow',
        'completed' => 'green',
        'failed' => 'red',
        'refunded' => 'gray',
        'active' => 'blue',
        'used' => 'green',
        'expired' => 'gray',
        'cancelled' => 'red'
    ];
    return $colors[$status] ?? 'gray';
}

/**
 * XSS 방지
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * 전화번호 포맷 (010-1234-5678)
 */
function format_phone($phone) {
    if (empty($phone)) return '-';

    $phone = preg_replace('/[^0-9]/', '', $phone);

    if (strlen($phone) == 11) {
        return preg_replace('/(\d{3})(\d{4})(\d{4})/', '$1-$2-$3', $phone);
    } elseif (strlen($phone) == 10) {
        return preg_replace('/(\d{3})(\d{3})(\d{4})/', '$1-$2-$3', $phone);
    }

    return $phone;
}

/**
 * 카드번호 마스킹 (1234-****-****-5678)
 */
function mask_card_number($cardNum) {
    if (empty($cardNum)) return '-';

    $cardNum = preg_replace('/[^0-9]/', '', $cardNum);

    if (strlen($cardNum) >= 16) {
        return substr($cardNum, 0, 4) . '-****-****-' . substr($cardNum, -4);
    }

    return '****-****-****-' . substr($cardNum, -4);
}

/**
 * 결제 수단 한글 변환
 */
function get_payment_method_text($method) {
    $methods = [
        'Card' => '신용카드',
        'VBank' => '가상계좌',
        'DirectBank' => '계좌이체',
        'HPP' => '간편결제',
        'KakaoPay' => '카카오페이',
        'NaverPay' => '네이버페이',
        'PayPal' => '페이팔'
    ];
    return $methods[$method] ?? $method;
}
