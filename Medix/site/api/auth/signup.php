<?php
/**
 * 회원가입 API
 * POST /api/auth/signup.php
 * 
 * 요청 데이터:
 * - email: 이메일 (필수)
 * - password: 비밀번호 (필수, 최소 8자)
 * - display_name: 사용자 이름 (필수)
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
    $display_name = isset($input['display_name']) ? trim($input['display_name']) : '';
    
    // 유효성 검사
    if (empty($email)) {
        throw new Exception('이메일을 입력해주세요.');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('올바른 이메일 형식이 아닙니다.');
    }
    
    if (empty($password)) {
        throw new Exception('비밀번호를 입력해주세요.');
    }
    
    if (strlen($password) < 8) {
        throw new Exception('비밀번호는 최소 8자 이상이어야 합니다.');
    }
    
    if (empty($display_name)) {
        throw new Exception('이름을 입력해주세요.');
    }
    
    if (strlen($display_name) > 100) {
        throw new Exception('이름은 100자를 초과할 수 없습니다.');
    }
    
    // 데이터베이스 연결
    $pdo = db();
    
    // 이메일 중복 확인
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        throw new Exception('이미 사용 중인 이메일입니다.');
    }
    
    // 비밀번호 해싱
    $password_hash = password_hash($password, PASSWORD_BCRYPT);
    
    // 사용자 등록
    $stmt = $pdo->prepare('
        INSERT INTO users (email, display_name, password_hash, created_at, updated_at)
        VALUES (?, ?, ?, NOW(), NOW())
    ');
    
    $stmt->execute([$email, $display_name, $password_hash]);
    
    $user_id = $pdo->lastInsertId();
    
    // 세션 시작 및 사용자 정보 저장
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user_id;
    $_SESSION['email'] = $email;
    $_SESSION['display_name'] = $display_name;
    
    // 성공 응답
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => '회원가입이 완료되었습니다.',
        'user' => [
            'id' => $user_id,
            'email' => $email,
            'display_name' => $display_name
        ]
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => '데이터베이스 오류가 발생했습니다.'
    ]);
    
    // 에러 로그 (프로덕션에서는 파일로 기록)
    error_log('Signup PDO Error: ' . $e->getMessage());
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
