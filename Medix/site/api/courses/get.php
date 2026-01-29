<?php
/**
 * 코스 정보 조회 API
 * GET /api/courses/get.php?id=1
 * 
 * 요청 파라미터:
 * - id: 코스 ID (필수)
 * 
 * 응답:
 * - course-data.json과 동일한 형식
 * - 로그인된 경우 진행률 포함
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
    $course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($course_id <= 0) {
        throw new Exception('올바른 코스 ID를 입력해주세요.');
    }
    
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    
    // 데이터베이스 연결
    $pdo = db();
    
    // 코스 정보 조회
    $stmt = $pdo->prepare('
        SELECT id, title, description, instructor_name, total_duration
        FROM courses
        WHERE id = ?
    ');
    $stmt->execute([$course_id]);
    $course = $stmt->fetch();
    
    if (!$course) {
        throw new Exception('코스를 찾을 수 없습니다.');
    }
    
    // 섹션 및 강의 조회
    $stmt = $pdo->prepare('
        SELECT 
            les.id as lesson_id,
            les.title as lesson_title,
            les.description as lesson_description,
            les.sort_order as lesson_order,
            lec.id as lecture_id,
            lec.title as lecture_title,
            lec.vimeo_id,
            lec.vimeo_hash,
            lec.duration,
            lec.sort_order as lecture_order
        FROM lessons les
        JOIN lectures lec ON lec.lesson_id = les.id
        WHERE les.course_id = ?
        ORDER BY les.sort_order, lec.sort_order
    ');
    $stmt->execute([$course_id]);
    $rows = $stmt->fetchAll();
    
    // 사용자 진행률 조회 (로그인된 경우)
    $progress = [];
    if ($user_id) {
        $stmt = $pdo->prepare('
            SELECT lecture_id, last_position, completed
            FROM user_lecture_progress
            WHERE user_id = ? AND lecture_id IN (
                SELECT lec.id
                FROM lectures lec
                JOIN lessons les ON lec.lesson_id = les.id
                WHERE les.course_id = ?
            )
        ');
        $stmt->execute([$user_id, $course_id]);
        $progress_rows = $stmt->fetchAll();
        
        foreach ($progress_rows as $row) {
            $progress[$row['lecture_id']] = [
                'last_position' => (int)$row['last_position'],
                'completed' => (bool)$row['completed']
            ];
        }
    }
    
    // 데이터 구조화 (course-data.json 형식)
    $sections = [];
    $current_section = null;
    $total_duration_seconds = 0;
    $lecture_count = 0;
    
    foreach ($rows as $row) {
        $lesson_id = $row['lesson_id'];
        
        // 새로운 섹션
        if ($current_section === null || $current_section['id'] !== $lesson_id) {
            if ($current_section !== null) {
                $sections[] = $current_section;
            }
            
            $current_section = [
                'id' => $lesson_id,
                'title' => $row['lesson_title'],
                'description' => $row['lesson_description'],
                'lectures' => []
            ];
        }
        
        // 강의 추가
        $lecture_id = $row['lecture_id'];
        $duration_seconds = (int)$row['duration'];
        $total_duration_seconds += $duration_seconds;
        $lecture_count++;
        
        // 시간 포맷팅 (h:mm:ss 또는 mm:ss)
        $hours = floor($duration_seconds / 3600);
        $minutes = floor(($duration_seconds % 3600) / 60);
        $seconds = $duration_seconds % 60;
        
        if ($hours > 0) {
            $duration_formatted = sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
        } else {
            $duration_formatted = sprintf('%02d:%02d', $minutes, $seconds);
        }
        
        // 진행률 상태 결정
        $status = 'pending';
        if (isset($progress[$lecture_id])) {
            if ($progress[$lecture_id]['completed']) {
                $status = 'completed';
            } else if ($progress[$lecture_id]['last_position'] > 0) {
                $status = 'in_progress';
            }
        }
        
        // 첫 번째 미완료 강의를 'playing'으로 설정 (한 번만)
        static $playing_set = false;
        if (!$playing_set && $status !== 'completed') {
            $status = 'playing';
            $playing_set = true;
        }
        
        $current_section['lectures'][] = [
            'id' => $lecture_id,
            'title' => $row['lecture_title'],
            'duration' => $duration_formatted,
            'durationSeconds' => $duration_seconds,
            'vimeoId' => $row['vimeo_id'],
            'vimeoHash' => $row['vimeo_hash'],
            'status' => $status
        ];
    }
    
    // 마지막 섹션 추가
    if ($current_section !== null) {
        $sections[] = $current_section;
    }
    
    // 총 시간 포맷팅
    $total_hours = floor($total_duration_seconds / 3600);
    $total_minutes = floor(($total_duration_seconds % 3600) / 60);
    $total_duration_formatted = $total_hours > 0 
        ? sprintf('%d시간 %d분', $total_hours, $total_minutes)
        : sprintf('%d분', $total_minutes);
    
    // 응답 데이터 구성
    $response = [
        'courseInfo' => [
            'title' => $course['title'],
            'instructor' => $course['instructor_name'],
            'totalDuration' => $total_duration_formatted,
            'lectureCount' => $lecture_count
        ],
        'sections' => $sections
    ];
    
    // 성공 응답
    http_response_code(200);
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => '데이터베이스 오류가 발생했습니다.'
    ]);
    error_log('Get Course PDO Error: ' . $e->getMessage());
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
