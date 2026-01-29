<?php
// helpers.php

// 간단한 CSRF 토큰
function csrf_token() {
  if (empty($_SESSION['csrf_token'])) {
	$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf_token'];
}

function verify_csrf($token) {
  return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// 입력값 트림/정리
function inpost($key, $default = '') {
  return isset($_POST[$key]) ? trim((string)$_POST[$key]) : $default;
}

// 허용값 화이트리스트 체크
function one_of($value, array $allowed, $fallback) {
  return in_array($value, $allowed, true) ? $value : $fallback;
}

// 기본 XSS 이스케이프
function e($str) {
  return htmlspecialchars((string)$str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}