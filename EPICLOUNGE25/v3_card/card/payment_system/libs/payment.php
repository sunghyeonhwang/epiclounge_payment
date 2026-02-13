<?php
/**
 * 결제 관리 클래스
 *
 * 결제 링크 생성, 조회, INICIS 연동 등
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';

class PaymentManager {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * 결제 링크 생성
     *
     * @param int $adminId 관리자 ID
     * @param array $data 결제 데이터
     *   - amount: 결제 금액 (필수)
     *   - goodname: 상품/서비스명 (필수)
     *   - description: 설명 (선택)
     *   - category: 분류 (선택)
     *   - buyer_name: 결제자 이름 (선택)
     *   - buyer_phone: 전화번호 (선택)
     *   - buyer_email: 이메일 (선택)
     *   - expire_days: 만료일 (선택, 기본 30일)
     *   - memo: 관리자 메모 (선택)
     * @return array ['link_id', 'link_token', 'payment_url']
     */
    public function createPaymentLink($adminId, $data) {
        // 입력 검증
        if (empty($data['amount']) || $data['amount'] <= 0) {
            throw new Exception('결제 금액을 입력해주세요.');
        }

        if (empty($data['goodname'])) {
            throw new Exception('상품/서비스명을 입력해주세요.');
        }

        // 고유 토큰 생성 (64자리 hex)
        $linkToken = $this->generateLinkToken();
        $linkUuid = hash(HASH_ALGORITHM, $linkToken . microtime(true) . $adminId);

        // 만료일 계산
        $expireDays = $data['expire_days'] ?? PAYMENT_LINK_EXPIRE_DAYS;
        $expireDate = null;
        if ($expireDays > 0) {
            $expireDate = date('Y-m-d H:i:s', strtotime("+{$expireDays} days"));
        }

        // DB 저장
        $linkId = $this->db->insert('griff_payment_links', [
            'link_uuid' => $linkUuid,
            'link_token' => $linkToken,
            'admin_id' => $adminId,
            'payment_amount' => $data['amount'],
            'payment_goodname' => $data['goodname'],
            'payment_description' => $data['description'] ?? null,
            'payment_category' => $data['category'] ?? null,
            'buyer_name' => $data['buyer_name'] ?? null,
            'buyer_phone' => $data['buyer_phone'] ?? null,
            'buyer_email' => $data['buyer_email'] ?? null,
            'link_expire_date' => $expireDate,
            'link_memo' => $data['memo'] ?? null
        ]);

        $paymentUrl = SITE_URL . '/payment/index.php?token=' . $linkToken;

        error_log("Payment link created: ID={$linkId}, Token={$linkToken}, Admin={$adminId}");

        return [
            'link_id' => $linkId,
            'link_token' => $linkToken,
            'link_uuid' => $linkUuid,
            'payment_url' => $paymentUrl,
            'expire_date' => $expireDate
        ];
    }

    /**
     * 토큰으로 결제 링크 조회
     *
     * @param string $token 링크 토큰
     * @return array 결제 링크 정보
     * @throws Exception 링크가 없거나 유효하지 않을 때
     */
    public function getPaymentLinkByToken($token) {
        $link = $this->db->fetch(
            "SELECT * FROM griff_payment_links WHERE link_token = ?",
            [$token]
        );

        if (!$link) {
            throw new Exception('결제 링크를 찾을 수 없습니다. URL을 다시 확인해주세요.');
        }

        // 상태 검증
        if ($link['link_status'] === 'used') {
            throw new Exception('이미 사용된 결제 링크입니다.');
        }

        if ($link['link_status'] === 'cancelled') {
            throw new Exception('취소된 결제 링크입니다.');
        }

        if ($link['link_status'] === 'expired') {
            throw new Exception('만료된 결제 링크입니다.');
        }

        // 만료일 체크
        if ($link['link_expire_date'] && strtotime($link['link_expire_date']) < time()) {
            // 상태 업데이트
            $this->db->update(
                'griff_payment_links',
                ['link_status' => 'expired'],
                'link_id = ?',
                [$link['link_id']]
            );

            throw new Exception('만료된 결제 링크입니다. (유효기간: ' .
                date('Y-m-d H:i', strtotime($link['link_expire_date'])) . ')');
        }

        return $link;
    }

    /**
     * ID로 결제 링크 조회
     */
    public function getPaymentLinkById($linkId) {
        return $this->db->fetch(
            "SELECT * FROM griff_payment_links WHERE link_id = ?",
            [$linkId]
        );
    }

    /**
     * INICIS 결제 파라미터 생성
     *
     * @param int $linkId 결제 링크 ID
     * @param bool $isMobile 모바일 여부
     * @return array INICIS 파라미터
     */
    public function prepareInicisPayment($linkId, $isMobile = false) {
        $link = $this->getPaymentLinkById($linkId);

        if (!$link) {
            throw new Exception('결제 링크를 찾을 수 없습니다.');
        }

        // INICIS 유틸 로드 (기존 파일 활용)
        if (!class_exists('INIStdPayUtil')) {
            $utilPath = INICIS_PC_PATH . '/INIStdPayUtil.php';
            if (file_exists($utilPath)) {
                require_once $utilPath;
            } else {
                throw new Exception('INICIS 라이브러리를 찾을 수 없습니다.');
            }
        }

        $util = new INIStdPayUtil();
        $timestamp = $util->getTimestamp();
        $orderNumber = INICIS_MID . "_" . $linkId . "_" . $timestamp;

        // Signature 생성
        $signatureParams = [
            'oid' => $orderNumber,
            'price' => $link['payment_amount'],
            'timestamp' => $timestamp
        ];
        $signature = $util->makeSignature($signatureParams);

        // 해시 키 생성
        $mKey = $util->makeHash(INICIS_SIGNKEY, 'sha256');

        // PC/모바일 공통 파라미터
        $result = [
            'mid' => INICIS_MID,
            'oid' => $orderNumber,
            'price' => $link['payment_amount'],
            'goodname' => $link['payment_goodname'],
            'buyername' => !empty($link['buyer_name']) ? $link['buyer_name'] : '결제자',
            'buyertel' => !empty($link['buyer_phone']) ? $link['buyer_phone'] : '01000000000',
            'buyeremail' => !empty($link['buyer_email']) ? $link['buyer_email'] : 'noreply@griff.co.kr',
            'timestamp' => $timestamp,
            'signature' => $signature,
            'mKey' => $mKey,
            'link_id' => $linkId
        ];

        // PC 결제
        if (!$isMobile) {
            $result['returnUrl'] = SITE_URL . '/inicis/pc/process.php';
            $result['closeUrl'] = SITE_URL . '/payment/fail.php?token=' . $link['link_token'];
            $result['gopaymethod'] = 'Card:Directbank:HPP';  // 카드, 계좌이체, 간편결제
            $result['acceptmethod'] = 'HPP(1):below1000:centerCd(Y)';
            $result['payViewType'] = 'popup';  // 팝업 방식
        }
        // 모바일 결제
        else {
            $result['P_INI_PAYMENT'] = 'CARD';
            $result['P_MID'] = INICIS_MID;
            $result['P_OID'] = $orderNumber;
            $result['P_AMT'] = $link['payment_amount'];
            $result['P_GOODS'] = $link['payment_goodname'];
            $result['P_UNAME'] = !empty($link['buyer_name']) ? $link['buyer_name'] : '결제자';
            $result['P_MOBILE'] = !empty($link['buyer_phone']) ? $link['buyer_phone'] : '01000000000';
            $result['P_EMAIL'] = !empty($link['buyer_email']) ? $link['buyer_email'] : 'noreply@griff.co.kr';
            $result['P_NEXT_URL'] = SITE_URL . '/inicis/mobile/process.php';
            $result['P_NOTI_URL'] = SITE_URL . '/inicis/mobile/vbank_noti.php';
            $result['P_CHARSET'] = 'utf8';
            $result['P_RESERVED'] = 'below1000=Y&vbank_receipt=Y&centerCd=Y';
        }

        return $result;
    }

    /**
     * 결제 완료 처리
     *
     * @param int $linkId 결제 링크 ID
     * @param array $inicisResponse INICIS 응답 데이터
     * @return bool 성공 여부
     */
    public function completePayment($linkId, $inicisResponse) {
        try {
            $this->db->beginTransaction();

            // 결제 내역 저장
            $transactionId = $this->db->insert('griff_payment_transactions', [
                'link_id' => $linkId,
                'inicis_tid' => $inicisResponse['tid'] ?? null,
                'inicis_oid' => $inicisResponse['MOID'] ?? $inicisResponse['oid'] ?? null,
                'inicis_result_code' => $inicisResponse['resultCode'] ?? '0000',
                'inicis_result_msg' => $inicisResponse['resultMsg'] ?? 'success',
                'payment_amount' => $inicisResponse['TotPrice'] ?? $inicisResponse['price'] ?? null,
                'payment_method' => $inicisResponse['payMethod'] ?? null,
                'payment_goodname' => $inicisResponse['goodName'] ?? null,
                'buyer_name' => $inicisResponse['buyerName'] ?? null,
                'buyer_phone' => $inicisResponse['buyerTel'] ?? null,
                'buyer_email' => $inicisResponse['buyerEmail'] ?? null,
                'card_code' => $inicisResponse['CARD_Code'] ?? null,
                'card_name' => $inicisResponse['CARD_BankCode'] ?? null,
                'card_quota' => $inicisResponse['CARD_Quota'] ?? null,
                'card_num' => $inicisResponse['CARD_Num'] ?? null,
                'card_applnum' => $inicisResponse['applNum'] ?? null,
                'vbank_num' => $inicisResponse['VACT_Num'] ?? null,
                'vbank_name' => $inicisResponse['VACT_BankCode'] ?? null,
                'vbank_date' => $inicisResponse['VACT_Date'] ?? null,
                'payment_date' => isset($inicisResponse['applDate']) && isset($inicisResponse['applTime'])
                    ? $inicisResponse['applDate'] . ' ' . $inicisResponse['applTime']
                    : date('Y-m-d H:i:s'),
                'inicis_raw_response' => json_encode($inicisResponse, JSON_UNESCAPED_UNICODE)
            ]);

            // 링크 상태 업데이트
            // 가상계좌는 입금 대기, 나머지는 완료
            $paymentStatus = (isset($inicisResponse['payMethod']) && $inicisResponse['payMethod'] === 'VBank')
                ? 'pending'
                : 'completed';

            $this->db->update(
                'griff_payment_links',
                [
                    'link_status' => 'used',
                    'payment_status' => $paymentStatus,
                    'payment_completed_at' => date('Y-m-d H:i:s')
                ],
                'link_id = ?',
                [$linkId]
            );

            $this->db->commit();

            error_log("Payment completed: LinkID={$linkId}, TID={$inicisResponse['tid']}, TransactionID={$transactionId}");

            return true;

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Payment completion failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 결제 링크 목록 조회
     *
     * @param int $adminId 관리자 ID (선택, null이면 전체)
     * @param array $filters 필터 옵션
     * @return array 결제 링크 목록
     */
    public function getPaymentLinks($adminId = null, $filters = []) {
        $where = [];
        $params = [];

        // 관리자 필터
        if ($adminId !== null) {
            $where[] = "l.admin_id = ?";
            $params[] = $adminId;
        }

        // 상태 필터
        if (!empty($filters['link_status'])) {
            $where[] = "l.link_status = ?";
            $params[] = $filters['link_status'];
        }

        if (!empty($filters['payment_status'])) {
            $where[] = "l.payment_status = ?";
            $params[] = $filters['payment_status'];
        }

        // 분류 필터
        if (!empty($filters['category'])) {
            $where[] = "l.payment_category = ?";
            $params[] = $filters['category'];
        }

        // 검색
        if (!empty($filters['search'])) {
            $where[] = "(l.payment_goodname LIKE ? OR l.buyer_name LIKE ? OR l.buyer_phone LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        // 날짜 범위
        if (!empty($filters['date_from'])) {
            $where[] = "l.link_created_at >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $where[] = "l.link_created_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // 정렬
        $orderBy = $filters['order_by'] ?? 'l.link_created_at';
        $orderDir = $filters['order_dir'] ?? 'DESC';

        $sql = "
            SELECT
                l.*,
                a.admin_name,
                t.transaction_id,
                t.payment_date,
                t.payment_method,
                t.inicis_tid,
                t.card_name,
                t.card_num
            FROM griff_payment_links l
            LEFT JOIN griff_payment_admins a ON l.admin_id = a.admin_id
            LEFT JOIN griff_payment_transactions t ON l.link_id = t.link_id
            {$whereClause}
            ORDER BY {$orderBy} {$orderDir}
        ";

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * 결제 거래 내역 조회
     *
     * @param int $linkId 결제 링크 ID
     * @return array|null 거래 내역
     */
    public function getTransaction($linkId) {
        return $this->db->fetch(
            "SELECT * FROM griff_payment_transactions WHERE link_id = ? ORDER BY transaction_created_at DESC LIMIT 1",
            [$linkId]
        );
    }

    /**
     * 결제 링크 취소
     *
     * @param int $linkId 결제 링크 ID
     * @return bool 성공 여부
     */
    public function cancelPaymentLink($linkId) {
        $link = $this->getPaymentLinkById($linkId);

        if (!$link) {
            throw new Exception('결제 링크를 찾을 수 없습니다.');
        }

        if ($link['link_status'] === 'used' && $link['payment_status'] === 'completed') {
            throw new Exception('이미 결제가 완료된 링크는 취소할 수 없습니다.');
        }

        $this->db->update(
            'griff_payment_links',
            ['link_status' => 'cancelled'],
            'link_id = ?',
            [$linkId]
        );

        error_log("Payment link cancelled: LinkID={$linkId}");

        return true;
    }

    /**
     * 대시보드 통계
     *
     * @param int $adminId 관리자 ID (선택)
     * @return array 통계 데이터
     */
    public function getDashboardStats($adminId = null) {
        // WHERE 조건 및 파라미터 설정
        $where = "";
        $params = [];

        if ($adminId !== null) {
            $where = "WHERE admin_id = ?";
            $params = [$adminId];
        }

        // 전체 링크 수
        $totalLinks = $this->db->fetch(
            "SELECT COUNT(*) as count FROM griff_payment_links {$where}",
            $params
        )['count'];

        // 활성 링크 수
        $whereActive = $where ? "{$where} AND link_status = 'active'" : "WHERE link_status = 'active'";
        $activeLinks = $this->db->fetch(
            "SELECT COUNT(*) as count FROM griff_payment_links {$whereActive}",
            $params
        )['count'];

        // 완료된 결제 수
        $whereCompleted = $where ? "{$where} AND payment_status = 'completed'" : "WHERE payment_status = 'completed'";
        $completedPayments = $this->db->fetch(
            "SELECT COUNT(*) as count FROM griff_payment_links {$whereCompleted}",
            $params
        )['count'];

        // 총 결제 금액
        $totalAmount = $this->db->fetch(
            "SELECT COALESCE(SUM(payment_amount), 0) as total
             FROM griff_payment_links {$whereCompleted}",
            $params
        )['total'];

        // 오늘 결제 수
        $whereToday = $whereCompleted . " AND DATE(payment_completed_at) = CURDATE()";
        $todayPayments = $this->db->fetch(
            "SELECT COUNT(*) as count FROM griff_payment_links {$whereToday}",
            $params
        )['count'];

        // 오늘 결제 금액
        $todayAmount = $this->db->fetch(
            "SELECT COALESCE(SUM(payment_amount), 0) as total
             FROM griff_payment_links {$whereToday}",
            $params
        )['total'];

        // 이번 달 결제 금액
        $whereMonth = $whereCompleted . " AND YEAR(payment_completed_at) = YEAR(CURDATE()) AND MONTH(payment_completed_at) = MONTH(CURDATE())";
        $monthAmount = $this->db->fetch(
            "SELECT COALESCE(SUM(payment_amount), 0) as total
             FROM griff_payment_links {$whereMonth}",
            $params
        )['total'];

        return [
            'total_links' => $totalLinks,
            'active_links' => $activeLinks,
            'completed_payments' => $completedPayments,
            'total_amount' => $totalAmount,
            'today_payments' => $todayPayments,
            'today_amount' => $todayAmount,
            'month_amount' => $monthAmount
        ];
    }

    /**
     * 토큰 생성 (64자리 hex)
     */
    private function generateLinkToken() {
        return bin2hex(random_bytes(32));
    }

    /**
     * 모바일 감지
     */
    public static function isMobile() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        return preg_match('/(android|webos|iphone|ipad|ipod|blackberry|windows phone)/i', $userAgent);
    }
}
