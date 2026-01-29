<?php
/**
 * 진행률 저장 API
 * POST /api/progress/save.php
 * 
 * 요청 데이터:
 * - lecture_id: 강의 ID (필수)
 * - last_position: 마지막 시청 위치 (초 단위, 필수)
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
    // 로그인 확인
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('로그인이 필요합니다.');
    }
    
    $user_id = $_SESSION['user_id'];
    
    // JSON 입력 파싱
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('잘못된 요청 데이터입니다.');
    }
    
    $lecture_id = isset($input['lecture_id']) ? (int)$input['lecture_id'] : 0;
    $last_position = isset($input['last_position']) ? (int)$input['last_position'] : 0;
    
    if ($lecture_id <= 0) {
        throw new Exception('올바른 강의 ID를 입력해주세요.');
    }
    
    if ($last_position < 0) {
        $last_position = 0;
    }
    
    // 데이터베이스 연결
    $pdo = db();
    
    // 진행률 저장 또는 업데이트 (UPSERT)
    $stmt = $pdo->prepare('
        INSERT INTO user_lecture_progress 
            (user_id, lecture_id, last_position, updated_at)
        VALUES (?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE 
            last_position = VALUES(last_position),
            updated_at = NOW()
    ');
    
    $stmt->execute([$user_id, $lecture_id, $last_position]);
    
    // 성공 응답
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => '진행률이 저장되었습니다.',
        'lecture_id' => $lecture_id,
        'last_position' => $last_position
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => '데이터베이스 오류가 발생했습니다.'
    ]);
    error_log('Save Progress PDO Error: ' . $e->getMessage());
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
