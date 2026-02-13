<?php
/**
 * 초기 관리자 계정 생성 스크립트
 *
 * 사용법:
 * 1. libs/config.php에서 데이터베이스 설정 확인
 * 2. 브라우저에서 이 파일 실행: http://yourdomain.com/payment_system/setup/create_admin.php
 * 3. 또는 CLI에서 실행: php create_admin.php
 */

require_once __DIR__ . '/../libs/config.php';
require_once __DIR__ . '/../libs/db.php';

// 보안: 이미 관리자가 있으면 실행 방지
try {
    $db = Database::getInstance();

    $existingAdmins = $db->fetch("SELECT COUNT(*) as count FROM griff_payment_admins");

    if ($existingAdmins && $existingAdmins['count'] > 0) {
        die("이미 관리자 계정이 존재합니다. 보안을 위해 이 스크립트는 더 이상 실행할 수 없습니다.");
    }

    // 초기 관리자 정보 (실제 운영 시 변경 필요)
    $adminData = [
        'username' => 'admin',
        'password' => 'griff2025!',  // 초기 비밀번호 (로그인 후 반드시 변경)
        'name' => '그리프 관리자',
        'email' => 'admin@griff.co.kr'
    ];

    // bcrypt 해싱
    $hashedPassword = password_hash($adminData['password'], PASSWORD_BCRYPT, ['cost' => 12]);

    // 관리자 계정 생성
    $db->query(
        "INSERT INTO griff_payment_admins (admin_username, admin_password, admin_name, admin_email, admin_status)
         VALUES (?, ?, ?, ?, 'active')",
        [
            $adminData['username'],
            $hashedPassword,
            $adminData['name'],
            $adminData['email']
        ]
    );

    $adminId = $db->lastInsertId();

    echo "✅ 초기 관리자 계정이 생성되었습니다!\n\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "관리자 ID: {$adminId}\n";
    echo "아이디: {$adminData['username']}\n";
    echo "비밀번호: {$adminData['password']}\n";
    echo "이름: {$adminData['name']}\n";
    echo "이메일: {$adminData['email']}\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    echo "⚠️  보안 경고:\n";
    echo "1. 로그인 후 반드시 비밀번호를 변경하세요.\n";
    echo "2. 이 스크립트 파일을 삭제하거나 외부 접근을 차단하세요.\n";
    echo "3. 관리자 페이지: " . SITE_URL . "/admin/\n\n";

} catch (Exception $e) {
    echo "❌ 오류 발생: " . $e->getMessage() . "\n";
    exit(1);
}
