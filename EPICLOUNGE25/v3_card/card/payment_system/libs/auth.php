<?php
/**
 * 관리자 인증 클래스
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';

class Auth {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();

        // 세션 시작
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * 로그인
     *
     * @param string $username 아이디
     * @param string $password 비밀번호
     * @return bool 성공 여부
     */
    public function login($username, $password) {
        // 입력 검증
        if (empty($username) || empty($password)) {
            return false;
        }

        // 관리자 조회
        $admin = $this->db->fetch(
            "SELECT * FROM griff_payment_admins
             WHERE admin_username = ? AND admin_status = 'active'",
            [$username]
        );

        if (!$admin) {
            error_log("Login failed: User not found - {$username}");
            return false;
        }

        // 비밀번호 검증
        if (!password_verify($password, $admin['admin_password'])) {
            error_log("Login failed: Invalid password - {$username}");
            return false;
        }

        // 세션 설정
        $_SESSION['admin_id'] = $admin['admin_id'];
        $_SESSION['admin_username'] = $admin['admin_username'];
        $_SESSION['admin_name'] = $admin['admin_name'];
        $_SESSION['admin_email'] = $admin['admin_email'];
        $_SESSION['last_activity'] = time();

        // 세션 고정 공격 방지
        session_regenerate_id(true);

        // 마지막 로그인 시간 업데이트
        $this->db->query(
            "UPDATE griff_payment_admins SET admin_last_login = NOW() WHERE admin_id = ?",
            [$admin['admin_id']]
        );

        error_log("Login success: {$username} (ID: {$admin['admin_id']})");

        return true;
    }

    /**
     * 로그아웃
     */
    public function logout() {
        $adminId = $_SESSION['admin_id'] ?? 'unknown';

        // 세션 변수 제거
        $_SESSION = [];

        // 세션 쿠키 제거
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // 세션 파괴
        session_destroy();

        error_log("Logout: Admin ID {$adminId}");

        return true;
    }

    /**
     * 인증 확인
     *
     * @return bool 인증 여부
     */
    public function isAuthenticated() {
        // 세션에 관리자 ID가 없으면 미인증
        if (!isset($_SESSION['admin_id'])) {
            return false;
        }

        // 세션 타임아웃 체크
        if (isset($_SESSION['last_activity'])) {
            $elapsed = time() - $_SESSION['last_activity'];

            if ($elapsed > SESSION_TIMEOUT) {
                error_log("Session timeout: Admin ID {$_SESSION['admin_id']} ({$elapsed}s)");
                $this->logout();
                return false;
            }
        }

        // 세션 활동 시간 갱신
        $_SESSION['last_activity'] = time();

        return true;
    }

    /**
     * 인증 필수 (리다이렉트)
     *
     * 미인증 시 로그인 페이지로 이동
     */
    public function requireAuth() {
        if (!$this->isAuthenticated()) {
            $currentUrl = $_SERVER['REQUEST_URI'] ?? '';

            // 로그인 페이지로 리다이렉트
            $loginUrl = SITE_URL . '/admin/index.php';

            // 로그인 후 돌아올 URL 저장
            if (!empty($currentUrl) && strpos($currentUrl, 'index.php') === false) {
                $loginUrl .= '?redirect=' . urlencode($currentUrl);
            }

            header('Location: ' . $loginUrl);
            exit;
        }
    }

    /**
     * 관리자 ID 반환
     */
    public function getAdminId() {
        return $_SESSION['admin_id'] ?? null;
    }

    /**
     * 관리자 이름 반환
     */
    public function getAdminName() {
        return $_SESSION['admin_name'] ?? null;
    }

    /**
     * 관리자 아이디 반환
     */
    public function getAdminUsername() {
        return $_SESSION['admin_username'] ?? null;
    }

    /**
     * 관리자 이메일 반환
     */
    public function getAdminEmail() {
        return $_SESSION['admin_email'] ?? null;
    }

    /**
     * 관리자 정보 반환
     */
    public function getAdmin() {
        $adminId = $this->getAdminId();

        if (!$adminId) {
            return null;
        }

        return $this->db->fetch(
            "SELECT admin_id, admin_username, admin_name, admin_email, admin_phone,
                    admin_status, admin_last_login, admin_created_at
             FROM griff_payment_admins
             WHERE admin_id = ?",
            [$adminId]
        );
    }

    /**
     * 비밀번호 변경
     *
     * @param int $adminId 관리자 ID
     * @param string $currentPassword 현재 비밀번호
     * @param string $newPassword 새 비밀번호
     * @return bool 성공 여부
     */
    public function changePassword($adminId, $currentPassword, $newPassword) {
        // 현재 관리자 조회
        $admin = $this->db->fetch(
            "SELECT admin_password FROM griff_payment_admins WHERE admin_id = ?",
            [$adminId]
        );

        if (!$admin) {
            throw new Exception('관리자를 찾을 수 없습니다.');
        }

        // 현재 비밀번호 검증
        if (!password_verify($currentPassword, $admin['admin_password'])) {
            throw new Exception('현재 비밀번호가 올바르지 않습니다.');
        }

        // 새 비밀번호 유효성 검증
        if (strlen($newPassword) < 8) {
            throw new Exception('새 비밀번호는 8자 이상이어야 합니다.');
        }

        // 비밀번호 해싱
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => PASSWORD_COST]);

        // 비밀번호 업데이트
        $this->db->query(
            "UPDATE griff_payment_admins SET admin_password = ? WHERE admin_id = ?",
            [$hashedPassword, $adminId]
        );

        error_log("Password changed: Admin ID {$adminId}");

        return true;
    }

    /**
     * 세션 남은 시간 (초)
     */
    public function getSessionTimeRemaining() {
        if (!isset($_SESSION['last_activity'])) {
            return 0;
        }

        $elapsed = time() - $_SESSION['last_activity'];
        $remaining = SESSION_TIMEOUT - $elapsed;

        return max(0, $remaining);
    }

    /**
     * 세션 만료 임박 확인 (5분 이내)
     */
    public function isSessionExpiringSoon() {
        $remaining = $this->getSessionTimeRemaining();
        return $remaining > 0 && $remaining < 300; // 5분
    }
}
