<?php
/**
 * 인증 상태 확인 API
 * GET /api/auth/check.php
 * 
 * 응답:
 * - authenticated: 로그인 여부
 * - user: 사용자 정보 (로그인된 경우)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// OPTIONS 요청 처리 (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// GET 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../../config.php';

try {
    // 세션 확인
    if (isset($_SESSION['user_id'])) {
        // 로그인 상태
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'authenticated' => true,
            'user' => [
                'id' => $_SESSION['user_id'],
                'email' => $_SESSION['email'],
                'display_name' => $_SESSION['display_name']
            ]
        ]);
    } else {
        // 비로그인 상태
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'authenticated' => false,
            'user' => null
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => '인증 확인 중 오류가 발생했습니다.'
    ]);
}
