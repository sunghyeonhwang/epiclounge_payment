<?php
/**
 * DB 연결 테스트 파일
 * https://mendixconnect.com/api/test-db.php 로 접속하여 확인
 */

// 에러 출력 활성화
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>DB 연결 테스트</h1>";

echo "<h2>1. PHP 버전</h2>";
echo "PHP Version: " . phpversion() . "<br>";

echo "<h2>2. config.php 로드</h2>";
try {
    require_once __DIR__ . '/../config.php';
    echo "✅ config.php 로드 성공<br>";
    echo "DB_HOST: " . (defined('DB_HOST') ? DB_HOST : $DB_HOST) . "<br>";
    echo "DB_NAME: " . (defined('DB_NAME') ? DB_NAME : $DB_NAME) . "<br>";
    echo "DB_USER: " . (defined('DB_USER') ? DB_USER : $DB_USER) . "<br>";
} catch (Exception $e) {
    echo "❌ config.php 로드 실패: " . $e->getMessage() . "<br>";
    exit;
}

echo "<h2>3. PDO 연결 테스트</h2>";
try {
    // db() 함수 호출하여 PDO 가져오기
    $pdo = db();
    echo "✅ PDO 객체 생성 성공<br>";
    
    // 간단한 쿼리 테스트
    $stmt = $pdo->query("SELECT 1 as test");
    $result = $stmt->fetch();
    echo "✅ 쿼리 실행 성공: " . $result['test'] . "<br>";
    
    // 현재 데이터베이스 확인
    $stmt = $pdo->query("SELECT DATABASE() as current_db");
    $result = $stmt->fetch();
    echo "현재 DB: " . $result['current_db'] . "<br>";
    
    // users 테이블 확인
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    $table = $stmt->fetch();
    if ($table) {
        echo "✅ users 테이블 존재<br>";
        
        // users 테이블 구조 확인
        $stmt = $pdo->query("DESCRIBE users");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "Users 테이블 컬럼: <br>";
        echo "<pre>";
        print_r($columns);
        echo "</pre>";
        
        // 레코드 수 확인
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM users");
        $result = $stmt->fetch();
        echo "Users 테이블 레코드 수: " . $result['cnt'] . "<br>";
    } else {
        echo "❌ users 테이블이 없습니다. init_mysql.sql을 실행해주세요.<br>";
        
        // 모든 테이블 목록 표시
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "현재 존재하는 테이블: <br>";
        if (empty($tables)) {
            echo "  (테이블 없음)<br>";
        } else {
            echo "<pre>";
            print_r($tables);
            echo "</pre>";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ DB 연결 실패 (PDOException): <br>";
    echo "에러 코드: " . $e->getCode() . "<br>";
    echo "에러 메시지: " . $e->getMessage() . "<br>";
    echo "<br>가능한 원인:<br>";
    echo "1. 데이터베이스 'griff25'가 존재하지 않음<br>";
    echo "2. DB 사용자 'griff25'의 권한 부족<br>";
    echo "3. DB 비밀번호가 틀림<br>";
} catch (Exception $e) {
    echo "❌ 일반 오류: " . $e->getMessage() . "<br>";
}

echo "<h2>4. 세션 테스트</h2>";
try {
    if (session_status() === PHP_SESSION_ACTIVE) {
        echo "✅ 세션 활성화됨<br>";
        echo "Session ID: " . session_id() . "<br>";
    } else {
        echo "⚠️ 세션이 시작되지 않았습니다.<br>";
    }
} catch (Exception $e) {
    echo "❌ 세션 오류: " . $e->getMessage() . "<br>";
}

echo "<h2>5. 파일 경로 확인</h2>";
echo "__DIR__: " . __DIR__ . "<br>";
echo "config.php 경로: " . __DIR__ . '/../config.php' . "<br>";
echo "helpers.php 경로: " . __DIR__ . '/../helpers.php' . "<br>";
echo "config.php 존재: " . (file_exists(__DIR__ . '/../config.php') ? '✅ 예' : '❌ 아니오') . "<br>";
echo "helpers.php 존재: " . (file_exists(__DIR__ . '/../helpers.php') ? '✅ 예' : '❌ 아니오') . "<br>";

echo "<hr>";
echo "<p>모든 항목이 ✅이면 정상입니다.</p>";
?>
