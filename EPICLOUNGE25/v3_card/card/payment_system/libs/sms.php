<?php
/**
 * SMS 발송 클래스 (DirectSend API)
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';

class SMSManager {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * 결제 링크 SMS 발송
     *
     * @param int $linkId 결제 링크 ID
     * @param string $recipientName 받는 사람 이름
     * @param string $recipientPhone 받는 사람 전화번호
     * @param string $paymentUrl 결제 URL
     * @param float $amount 결제 금액
     * @param string $goodname 상품/서비스명
     * @return bool 발송 성공 여부
     */
    public function sendPaymentLink($linkId, $recipientName, $recipientPhone, $paymentUrl, $amount, $goodname) {
        $title = '[' . SITE_NAME . '] 결제 요청';

        $message = sprintf(
            "[%s] 결제 요청\n\n안녕하세요, %s님\n\n상품: %s\n금액: %s원\n\n아래 링크를 클릭하여 결제해주세요.\n%s\n\n※ 링크는 %d일간 유효합니다.\n\n%s",
            SITE_NAME,
            $recipientName,
            $goodname,
            number_format($amount),
            $paymentUrl,
            PAYMENT_LINK_EXPIRE_DAYS,
            PAYMENT_RECEIVER
        );

        return $this->send($linkId, $recipientName, $recipientPhone, $title, $message);
    }

    /**
     * 결제 완료 SMS 발송
     *
     * @param int $linkId 결제 링크 ID
     * @param string $recipientName 받는 사람 이름
     * @param string $recipientPhone 받는 사람 전화번호
     * @param float $amount 결제 금액
     * @param string $goodname 상품/서비스명
     * @param string $paymentMethod 결제 수단
     * @return bool 발송 성공 여부
     */
    public function sendPaymentComplete($linkId, $recipientName, $recipientPhone, $amount, $goodname, $paymentMethod) {
        $title = '[' . SITE_NAME . '] 결제 완료';

        $message = sprintf(
            "[%s] 결제 완료\n\n%s님의 결제가 완료되었습니다.\n\n상품: %s\n금액: %s원\n결제수단: %s\n결제일시: %s\n\n감사합니다.\n\n%s",
            SITE_NAME,
            $recipientName,
            $goodname,
            number_format($amount),
            get_payment_method_text($paymentMethod),
            date('Y-m-d H:i'),
            PAYMENT_RECEIVER
        );

        return $this->send($linkId, $recipientName, $recipientPhone, $title, $message);
    }

    /**
     * SMS 발송 (DirectSend API)
     *
     * @param int $linkId 결제 링크 ID (로그용)
     * @param string $recipientName 받는 사람 이름
     * @param string $recipientPhone 받는 사람 전화번호
     * @param string $title SMS 제목
     * @param string $message SMS 내용
     * @return bool 발송 성공 여부
     */
    private function send($linkId, $recipientName, $recipientPhone, $title, $message) {
        try {
            // 전화번호 형식 검증
            $recipientPhone = preg_replace('/[^0-9]/', '', $recipientPhone);

            if (strlen($recipientPhone) < 10 || strlen($recipientPhone) > 11) {
                throw new Exception('올바른 전화번호 형식이 아닙니다.');
            }

            // DirectSend API 요청
            $receiver = [
                ['name' => $recipientName, 'mobile' => $recipientPhone]
            ];

            $postData = [
                'title' => $title,
                'message' => $message,
                'sender' => SMS_SENDER,
                'username' => SMS_USERNAME,
                'key' => SMS_KEY,
                'receiver' => $receiver
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, SMS_API_URL);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Cache-Control: no-cache',
                'Content-Type: application/json; charset=utf-8'
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);

            curl_close($ch);

            // 에러 체크
            if ($curlError) {
                throw new Exception('SMS 발송 실패: ' . $curlError);
            }

            // 발송 성공 여부
            $success = ($httpCode === 200);

            // 발송 로그 저장
            $this->db->insert('griff_payment_notifications', [
                'link_id' => $linkId,
                'notification_type' => 'sms',
                'recipient_name' => $recipientName,
                'recipient_phone' => $recipientPhone,
                'notification_status' => $success ? 'sent' : 'failed',
                'notification_title' => $title,
                'notification_message' => $message,
                'notification_response' => $response,
                'sent_at' => date('Y-m-d H:i:s')
            ]);

            if ($success) {
                error_log("SMS sent: LinkID={$linkId}, Phone={$recipientPhone}");
            } else {
                error_log("SMS failed: LinkID={$linkId}, Phone={$recipientPhone}, Response={$response}");
            }

            return $success;

        } catch (Exception $e) {
            error_log("SMS send error: " . $e->getMessage());

            // 실패 로그 저장
            try {
                $this->db->insert('griff_payment_notifications', [
                    'link_id' => $linkId,
                    'notification_type' => 'sms',
                    'recipient_name' => $recipientName,
                    'recipient_phone' => $recipientPhone,
                    'notification_status' => 'failed',
                    'notification_title' => $title,
                    'notification_message' => $message,
                    'notification_response' => $e->getMessage(),
                    'sent_at' => date('Y-m-d H:i:s')
                ]);
            } catch (Exception $dbError) {
                error_log("Failed to log SMS error: " . $dbError->getMessage());
            }

            return false;
        }
    }

    /**
     * SMS 발송 내역 조회
     *
     * @param int $linkId 결제 링크 ID
     * @param string $type 알림 타입 (sms, email, null=전체)
     * @return array 발송 내역
     */
    public function getNotifications($linkId, $type = null) {
        $where = "link_id = ?";
        $params = [$linkId];

        if ($type) {
            $where .= " AND notification_type = ?";
            $params[] = $type;
        }

        return $this->db->fetchAll(
            "SELECT * FROM griff_payment_notifications WHERE {$where} ORDER BY created_at DESC",
            $params
        );
    }

    /**
     * 알림 재발송
     *
     * @param int $notificationId 알림 ID
     * @return bool 발송 성공 여부
     */
    public function resendNotification($notificationId) {
        $notification = $this->db->fetch(
            "SELECT * FROM griff_payment_notifications WHERE notification_id = ?",
            [$notificationId]
        );

        if (!$notification) {
            throw new Exception('알림 내역을 찾을 수 없습니다.');
        }

        if ($notification['notification_type'] !== 'sms') {
            throw new Exception('SMS 알림만 재발송할 수 있습니다.');
        }

        return $this->send(
            $notification['link_id'],
            $notification['recipient_name'],
            $notification['recipient_phone'],
            $notification['notification_title'],
            $notification['notification_message']
        );
    }
}
