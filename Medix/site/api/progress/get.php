<?php
/**
 * 진행률 조회 API
 * GET /api/progress/get.php?course_id=1
 * 
 * 요청 파라미터:
 * - course_id: 코스 ID (필수)
 * 
 * 응답:
 * - progress: 강의별 진행률 배열
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
    // 로그인 확인
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('로그인이 필요합니다.');
    }
    
    $user_id = $_SESSION['user_id'];
    $course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
    
    if ($course_id <= 0) {
        throw new Exception('올바른 코스 ID를 입력해주세요.');
    }
    
    // 데이터베이스 연결
    $pdo = db();
    
    // 해당 코스의 모든 강의 진행률 조회
    $stmt = $pdo->prepare('
        SELECT 
            lec.id as lecture_id,
            COALESCE(ulp.last_position, 0) as last_position,
            COALESCE(ulp.completed, 0) as completed,
            ulp.completed_at
        FROM lectures lec
        JOIN lessons les ON lec.lesson_id = les.id
        LEFT JOIN user_lecture_progress ulp 
            ON ulp.lecture_id = lec.id AND ulp.user_id = ?
        WHERE les.course_id = ?
        ORDER BY lec.sort_order
    ');
    
    $stmt->execute([$user_id, $course_id]);
    $progress = $stmt->fetchAll();
    
    // 전체 진행률 계산
    $total_lectures = count($progress);
    $completed_lectures = 0;
    
    foreach ($progress as $item) {
        if ($item['completed']) {
            $completed_lectures++;
        }
    }
    
    $progress_pct = $total_lectures > 0 
        ? round(($completed_lectures / $total_lectures) * 100, 2) 
        : 0;
    
    // 성공 응답
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'course_id' => $course_id,
        'progress' => $progress,
        'summary' => [
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
    error_log('Get Progress PDO Error: ' . $e->getMessage());
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
