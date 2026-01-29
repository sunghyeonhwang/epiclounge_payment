<?php
/**
 * Reset Progress API
 * 사용자의 모든 강의 진행률을 초기화합니다.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../helpers.php';

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 인증 확인
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $pdo = db();
    
    // 사용자의 모든 강의 진행률 삭제
    $stmt = $pdo->prepare("DELETE FROM user_lecture_progress WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    $deleted = $stmt->rowCount();
    
    echo json_encode([
        'success' => true,
        'message' => 'Progress reset successfully',
        'deleted_count' => $deleted
    ]);
    
} catch (PDOException $e) {
    error_log("Progress reset error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
