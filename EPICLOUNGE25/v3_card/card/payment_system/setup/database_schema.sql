-- ============================================
-- 그리프 카드 결제 관리 시스템 - 데이터베이스 스키마
-- ============================================

-- 1. 관리자 테이블
CREATE TABLE IF NOT EXISTS `griff_payment_admins` (
  `admin_id` INT(11) NOT NULL AUTO_INCREMENT,
  `admin_username` VARCHAR(50) NOT NULL UNIQUE COMMENT '로그인 아이디',
  `admin_password` VARCHAR(255) NOT NULL COMMENT 'bcrypt 해시',
  `admin_name` VARCHAR(100) NOT NULL COMMENT '관리자 이름',
  `admin_email` VARCHAR(200) NOT NULL,
  `admin_phone` VARCHAR(20) DEFAULT NULL,
  `admin_status` ENUM('active', 'inactive') DEFAULT 'active',
  `admin_last_login` DATETIME DEFAULT NULL,
  `admin_created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `admin_updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`admin_id`),
  KEY `idx_username` (`admin_username`),
  KEY `idx_status` (`admin_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='결제 관리 관리자';

-- 2. 결제 링크 테이블
CREATE TABLE IF NOT EXISTS `griff_payment_links` (
  `link_id` INT(11) NOT NULL AUTO_INCREMENT,
  `link_uuid` VARCHAR(64) NOT NULL UNIQUE COMMENT 'SHA256 해시',
  `link_token` VARCHAR(64) NOT NULL UNIQUE COMMENT 'URL 파라미터 토큰',
  `admin_id` INT(11) NOT NULL COMMENT '생성한 관리자',

  -- 결제 정보
  `payment_amount` DECIMAL(10,2) NOT NULL COMMENT '결제 금액',
  `payment_goodname` VARCHAR(200) NOT NULL COMMENT '상품/서비스명',
  `payment_description` TEXT DEFAULT NULL COMMENT '결제 설명',
  `payment_category` VARCHAR(100) DEFAULT NULL COMMENT '결제 분류 (서비스제공비, 용역비, 상품판매 등)',

  -- 결제자 정보 (선택적)
  `buyer_name` VARCHAR(100) DEFAULT NULL,
  `buyer_phone` VARCHAR(20) DEFAULT NULL,
  `buyer_email` VARCHAR(200) DEFAULT NULL,

  -- 링크 상태
  `link_status` ENUM('active', 'used', 'expired', 'cancelled') DEFAULT 'active',
  `link_expire_date` DATETIME DEFAULT NULL COMMENT '만료일 (NULL이면 무제한)',

  -- 결제 완료 정보
  `payment_status` ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
  `payment_completed_at` DATETIME DEFAULT NULL,

  -- 메타 정보
  `link_created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `link_updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `link_memo` TEXT DEFAULT NULL COMMENT '관리자 메모',

  PRIMARY KEY (`link_id`),
  KEY `idx_uuid` (`link_uuid`),
  KEY `idx_token` (`link_token`),
  KEY `idx_admin` (`admin_id`),
  KEY `idx_status` (`link_status`, `payment_status`),
  KEY `idx_created` (`link_created_at`),
  KEY `idx_category` (`payment_category`),
  FOREIGN KEY (`admin_id`) REFERENCES `griff_payment_admins` (`admin_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='결제 링크';

-- 3. 결제 거래 내역 테이블
CREATE TABLE IF NOT EXISTS `griff_payment_transactions` (
  `transaction_id` INT(11) NOT NULL AUTO_INCREMENT,
  `link_id` INT(11) NOT NULL,

  -- INICIS 결제 정보
  `inicis_tid` VARCHAR(100) DEFAULT NULL COMMENT 'INICIS 거래 고유번호',
  `inicis_oid` VARCHAR(100) NOT NULL UNIQUE COMMENT '주문번호',
  `inicis_result_code` VARCHAR(10) DEFAULT NULL,
  `inicis_result_msg` VARCHAR(500) DEFAULT NULL,

  -- 결제 상세
  `payment_amount` DECIMAL(10,2) NOT NULL,
  `payment_method` VARCHAR(50) DEFAULT NULL COMMENT 'Card, VBank, DirectBank, KakaoPay 등',
  `payment_goodname` VARCHAR(200) DEFAULT NULL,

  -- 결제자 정보
  `buyer_name` VARCHAR(100) DEFAULT NULL,
  `buyer_phone` VARCHAR(20) DEFAULT NULL,
  `buyer_email` VARCHAR(200) DEFAULT NULL,

  -- 카드 결제 정보
  `card_code` VARCHAR(10) DEFAULT NULL COMMENT '카드사 코드',
  `card_name` VARCHAR(50) DEFAULT NULL COMMENT '카드사명',
  `card_quota` VARCHAR(10) DEFAULT NULL COMMENT '할부 개월',
  `card_num` VARCHAR(20) DEFAULT NULL COMMENT '카드번호 (마스킹)',
  `card_applnum` VARCHAR(50) DEFAULT NULL COMMENT '승인번호',

  -- 가상계좌 정보
  `vbank_num` VARCHAR(50) DEFAULT NULL COMMENT '가상계좌 번호',
  `vbank_name` VARCHAR(50) DEFAULT NULL COMMENT '은행명',
  `vbank_date` VARCHAR(20) DEFAULT NULL COMMENT '입금 기한',

  -- 타임스탬프
  `payment_date` DATETIME DEFAULT NULL COMMENT 'INICIS 승인 일시',
  `transaction_created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,

  -- 전문 데이터 (디버깅/감사용)
  `inicis_raw_response` TEXT DEFAULT NULL COMMENT 'INICIS 응답 원본 (JSON)',

  -- 환불 정보
  `refund_status` ENUM('none', 'requested', 'completed', 'failed') DEFAULT 'none',
  `refund_amount` DECIMAL(10,2) DEFAULT 0.00,
  `refund_date` DATETIME DEFAULT NULL,
  `refund_reason` TEXT DEFAULT NULL,
  `refund_tid` VARCHAR(100) DEFAULT NULL COMMENT '환불 거래번호',

  PRIMARY KEY (`transaction_id`),
  KEY `idx_link` (`link_id`),
  KEY `idx_tid` (`inicis_tid`),
  KEY `idx_oid` (`inicis_oid`),
  KEY `idx_payment_date` (`payment_date`),
  KEY `idx_refund_status` (`refund_status`),
  FOREIGN KEY (`link_id`) REFERENCES `griff_payment_links` (`link_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='결제 거래 내역';

-- 4. 알림 발송 로그 테이블
CREATE TABLE IF NOT EXISTS `griff_payment_notifications` (
  `notification_id` INT(11) NOT NULL AUTO_INCREMENT,
  `link_id` INT(11) NOT NULL,
  `notification_type` ENUM('sms', 'email') NOT NULL,
  `recipient_name` VARCHAR(100) DEFAULT NULL,
  `recipient_phone` VARCHAR(20) DEFAULT NULL,
  `recipient_email` VARCHAR(200) DEFAULT NULL,
  `notification_status` ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
  `notification_title` VARCHAR(200) DEFAULT NULL,
  `notification_message` TEXT DEFAULT NULL,
  `notification_response` TEXT DEFAULT NULL COMMENT 'API 응답',
  `sent_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`notification_id`),
  KEY `idx_link` (`link_id`),
  KEY `idx_type` (`notification_type`),
  KEY `idx_status` (`notification_status`),
  KEY `idx_sent_at` (`sent_at`),
  FOREIGN KEY (`link_id`) REFERENCES `griff_payment_links` (`link_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='알림 발송 로그';
