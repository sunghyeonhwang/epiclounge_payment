<?php
/**
 * 강의 완료 처리 API
 * POST /api/progress/complete.php
 * 
 * 요청 데이터:
 * - lecture_id: 강의 ID (필수)
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
    
    if ($lecture_id <= 0) {
        throw new Exception('올바른 강의 ID를 입력해주세요.');
    }
    
    // 데이터베이스 연결
    $pdo = db();
    
    // 강의 완료 처리 (UPSERT)
    $stmt = $pdo->prepare('
        INSERT INTO user_lecture_progress 
            (user_id, lecture_id, last_position, completed, completed_at, updated_at)
        VALUES (?, ?, 0, 1, NOW(), NOW())
        ON DUPLICATE KEY UPDATE 
            completed = 1,
            completed_at = NOW(),
            updated_at = NOW()
    ');
    
    $stmt->execute([$user_id, $lecture_id]);
    
    // 해당 강의가 속한 코스 ID 조회
    $stmt = $pdo->prepare('
        SELECT les.course_id
        FROM lectures lec
        JOIN lessons les ON lec.lesson_id = les.id
        WHERE lec.id = ?
    ');
    $stmt->execute([$lecture_id]);
    $course = $stmt->fetch();
    
    if (!$course) {
        throw new Exception('강의를 찾을 수 없습니다.');
    }
    
    $course_id = $course['course_id'];
    
    // 전체 진행률 재계산
    $stmt = $pdo->prepare('
        SELECT 
            COUNT(*) as total_lectures,
            SUM(CASE WHEN ulp.completed = 1 THEN 1 ELSE 0 END) as completed_lectures
        FROM lectures lec
        JOIN lessons les ON lec.lesson_id = les.id
        LEFT JOIN user_lecture_progress ulp 
            ON ulp.lecture_id = lec.id AND ulp.user_id = ?
        WHERE les.course_id = ?
    ');
    $stmt->execute([$user_id, $course_id]);
    $stats = $stmt->fetch();
    
    $total_lectures = (int)$stats['total_lectures'];
    $completed_lectures = (int)$stats['completed_lectures'];
    $progress_pct = $total_lectures > 0 
        ? round(($completed_lectures / $total_lectures) * 100, 2) 
        : 0;
    
    // user_course_progress 테이블 업데이트
    $stmt = $pdo->prepare('
        INSERT INTO user_course_progress 
            (user_id, course_id, completed_lectures, total_lectures, progress_pct, last_accessed_at, updated_at)
        VALUES (?, ?, ?, ?, ?, NOW(), NOW())
        ON DUPLICATE KEY UPDATE 
            completed_lectures = VALUES(completed_lectures),
            total_lectures = VALUES(total_lectures),
            progress_pct = VALUES(progress_pct),
            last_accessed_at = NOW(),
            updated_at = NOW()
    ');
    $stmt->execute([$user_id, $course_id, $completed_lectures, $total_lectures, $progress_pct]);
    
    // 성공 응답
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => '강의가 완료되었습니다.',
        'lecture_id' => $lecture_id,
        'progress' => [
            'total_lectures' => $total_lectures,
            'completed_lectures' => $completed_lectures,
            'progress_pct' => $progress_pct
        ]
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => '데이터베이스 오류가 발생했습니다.'
    ]);
    error_log('Complete Progress PDO Error: ' . $e->getMessage());
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
