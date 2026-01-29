<?php
/**
 * 로그인 API
 * POST /api/auth/login.php
 * 
 * 요청 데이터:
 * - email: 이메일 (필수)
 * - password: 비밀번호 (필수)
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
require_once __DIR__ . '/../../helpers.php';

try {
    // JSON 입력 파싱
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('잘못된 요청 데이터입니다.');
    }
    
    // 입력값 추출
    $email = isset($input['email']) ? trim($input['email']) : '';
    $password = isset($input['password']) ? $input['password'] : '';
    
    // 유효성 검사
    if (empty($email)) {
        throw new Exception('이메일을 입력해주세요.');
    }
    
    if (empty($password)) {
        throw new Exception('비밀번호를 입력해주세요.');
    }
    
    // 데이터베이스 연결
    $pdo = db();
    
    // 사용자 조회
    $stmt = $pdo->prepare('
        SELECT id, email, display_name, password_hash 
        FROM users 
        WHERE email = ?
    ');
    
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        throw new Exception('이메일 또는 비밀번호가 올바르지 않습니다.');
    }
    
    // 비밀번호 검증
    if (!password_verify($password, $user['password_hash'])) {
        throw new Exception('이메일 또는 비밀번호가 올바르지 않습니다.');
    }
    
    // 세션 시작 및 사용자 정보 저장
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['display_name'] = $user['display_name'];
    
    // 성공 응답
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => '로그인되었습니다.',
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'display_name' => $user['display_name']
        ]
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => '데이터베이스 오류가 발생했습니다.'
    ]);
    
    // 에러 로그
    error_log('Login PDO Error: ' . $e->getMessage());
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
