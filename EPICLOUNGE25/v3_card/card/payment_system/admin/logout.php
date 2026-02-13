<?php
/**
 * 관리자 로그아웃
 */

require_once __DIR__ . '/../libs/config.php';
require_once __DIR__ . '/../libs/auth.php';

$auth = new Auth();
$auth->logout();

header('Location: index.php');
exit;
