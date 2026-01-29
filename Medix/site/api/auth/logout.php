<?php
/**
 * 로그아웃 API
 * POST /api/auth/logout.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// OPTIONS 요청 처리 (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../../config.php';

try {
    // 세션 파괴
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = [];
        
        // 세션 쿠키 삭제
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        session_destroy();
    }
    
    // 성공 응답
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => '로그아웃되었습니다.'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => '로그아웃 중 오류가 발생했습니다.'
    ]);
}
