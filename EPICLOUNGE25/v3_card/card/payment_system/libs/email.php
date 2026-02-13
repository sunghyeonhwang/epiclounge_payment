<?php
/**
 * 이메일 발송 클래스
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';

class EmailManager {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * 결제 링크 이메일 발송
     *
     * @param int $linkId 결제 링크 ID
     * @param string $recipientName 받는 사람 이름
     * @param string $recipientEmail 받는 사람 이메일
     * @param string $paymentUrl 결제 URL
     * @param float $amount 결제 금액
     * @param string $goodname 상품/서비스명
     * @param string $description 상품 설명
     * @return bool 발송 성공 여부
     */
    public function sendPaymentLink($linkId, $recipientName, $recipientEmail, $paymentUrl, $amount, $goodname, $description = '') {
        $subject = '[' . SITE_NAME . '] 결제 요청';

        $htmlMessage = $this->getPaymentEmailTemplate([
            'recipient_name' => $recipientName,
            'goodname' => $goodname,
            'description' => $description,
            'amount' => number_format($amount),
            'payment_url' => $paymentUrl,
            'expire_days' => PAYMENT_LINK_EXPIRE_DAYS
        ]);

        return $this->send($linkId, $recipientName, $recipientEmail, $subject, $htmlMessage);
    }

    /**
     * 결제 완료 이메일 발송
     *
     * @param int $linkId 결제 링크 ID
     * @param string $recipientName 받는 사람 이름
     * @param string $recipientEmail 받는 사람 이메일
     * @param array $paymentData 결제 데이터
     * @return bool 발송 성공 여부
     */
    public function sendPaymentComplete($linkId, $recipientName, $recipientEmail, $paymentData) {
        $subject = '[' . SITE_NAME . '] 결제 완료 안내';

        $htmlMessage = $this->getPaymentCompleteTemplate([
            'recipient_name' => $recipientName,
            'goodname' => $paymentData['goodname'],
            'amount' => number_format($paymentData['amount']),
            'payment_method' => get_payment_method_text($paymentData['payment_method']),
            'payment_date' => format_datetime($paymentData['payment_date']),
            'transaction_id' => $paymentData['transaction_id'] ?? '',
            'card_info' => $paymentData['card_info'] ?? ''
        ]);

        return $this->send($linkId, $recipientName, $recipientEmail, $subject, $htmlMessage);
    }

    /**
     * 이메일 발송
     *
     * @param int $linkId 결제 링크 ID (로그용)
     * @param string $recipientName 받는 사람 이름
     * @param string $recipientEmail 받는 사람 이메일
     * @param string $subject 제목
     * @param string $htmlMessage HTML 내용
     * @return bool 발송 성공 여부
     */
    private function send($linkId, $recipientName, $recipientEmail, $subject, $htmlMessage) {
        try {
            // 이메일 주소 검증
            if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('올바른 이메일 주소가 아닙니다.');
            }

            // 헤더 설정
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=utf-8\r\n";
            $headers .= "From: " . EMAIL_FROM_NAME . " <" . EMAIL_FROM . ">\r\n";
            $headers .= "Reply-To: " . EMAIL_FROM . "\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

            // 이메일 발송
            $result = mail($recipientEmail, $subject, $htmlMessage, $headers);

            // 발송 로그 저장
            $this->db->insert('griff_payment_notifications', [
                'link_id' => $linkId,
                'notification_type' => 'email',
                'recipient_name' => $recipientName,
                'recipient_email' => $recipientEmail,
                'notification_status' => $result ? 'sent' : 'failed',
                'notification_title' => $subject,
                'notification_message' => $htmlMessage,
                'sent_at' => date('Y-m-d H:i:s')
            ]);

            if ($result) {
                error_log("Email sent: LinkID={$linkId}, Email={$recipientEmail}");
            } else {
                error_log("Email failed: LinkID={$linkId}, Email={$recipientEmail}");
            }

            return $result;

        } catch (Exception $e) {
            error_log("Email send error: " . $e->getMessage());

            // 실패 로그 저장
            try {
                $this->db->insert('griff_payment_notifications', [
                    'link_id' => $linkId,
                    'notification_type' => 'email',
                    'recipient_name' => $recipientName,
                    'recipient_email' => $recipientEmail,
                    'notification_status' => 'failed',
                    'notification_title' => $subject,
                    'notification_message' => $htmlMessage,
                    'notification_response' => $e->getMessage(),
                    'sent_at' => date('Y-m-d H:i:s')
                ]);
            } catch (Exception $dbError) {
                error_log("Failed to log email error: " . $dbError->getMessage());
            }

            return false;
        }
    }

    /**
     * 결제 요청 이메일 템플릿
     */
    private function getPaymentEmailTemplate($data) {
        $siteName = SITE_NAME;
        $recipientName = e($data['recipient_name']);
        $goodname = e($data['goodname']);
        $description = !empty($data['description']) ? e($data['description']) : '';
        $amount = $data['amount'];
        $paymentUrl = e($data['payment_url']);
        $expireDays = $data['expire_days'];
        $receiver = PAYMENT_RECEIVER;

        $descriptionHtml = '';
        if ($description) {
            $descriptionHtml = <<<HTML
                <div class="info-row">
                    <span class="label">설명</span>
                    <span class="value">{$description}</span>
                </div>
HTML;
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$siteName} - 결제 요청</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Malgun Gothic', sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f3f4f6; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px 20px; text-align: center; border-radius: 10px 10px 0 0; }
        .header h1 { margin: 0; font-size: 24px; font-weight: 600; }
        .content { background: white; padding: 40px 30px; border-radius: 0 0 10px 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .greeting { font-size: 16px; margin-bottom: 20px; }
        .info-box { background: #f9fafb; padding: 25px; border-radius: 8px; margin: 25px 0; border-left: 4px solid #667eea; }
        .info-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #e5e7eb; }
        .info-row:last-child { border-bottom: none; }
        .label { font-weight: 600; color: #6b7280; font-size: 14px; }
        .value { color: #111827; font-size: 14px; text-align: right; }
        .amount { font-size: 28px; font-weight: bold; color: #667eea; }
        .button-container { text-align: center; margin: 30px 0; }
        .button {
            display: inline-block;
            padding: 16px 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white !important;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            box-shadow: 0 4px 6px rgba(102, 126, 234, 0.4);
            transition: transform 0.2s;
        }
        .button:hover { transform: translateY(-2px); }
        .notice { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; border-radius: 4px; margin: 20px 0; font-size: 14px; color: #92400e; }
        .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 13px; margin-top: 20px; }
        .footer-info { margin: 5px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💳 결제 요청</h1>
        </div>
        <div class="content">
            <p class="greeting">안녕하세요, <strong>{$recipientName}</strong>님</p>
            <p>결제 요청이 도착했습니다. 아래 내용을 확인하시고 결제를 진행해주세요.</p>

            <div class="info-box">
                <div class="info-row">
                    <span class="label">상품/서비스</span>
                    <span class="value">{$goodname}</span>
                </div>
                {$descriptionHtml}
                <div class="info-row">
                    <span class="label">결제 금액</span>
                    <span class="amount">₩{$amount}</span>
                </div>
            </div>

            <div class="button-container">
                <a href="{$paymentUrl}" class="button">결제하기</a>
            </div>

            <div class="notice">
                <strong>📌 안내사항</strong><br>
                • 링크는 {$expireDays}일간 유효합니다<br>
                • 카드결제, 계좌이체, 간편결제를 지원합니다<br>
                • 결제 후 영수증이 이메일로 발송됩니다
            </div>
        </div>
        <div class="footer">
            <p class="footer-info">본 메일은 발신 전용입니다.</p>
            <p class="footer-info"><strong>결제 받는 자:</strong> {$receiver}</p>
            <p class="footer-info" style="margin-top: 15px; color: #9ca3af;">ⓒ {$siteName}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * 결제 완료 이메일 템플릿
     */
    private function getPaymentCompleteTemplate($data) {
        $siteName = SITE_NAME;
        $recipientName = e($data['recipient_name']);
        $goodname = e($data['goodname']);
        $amount = $data['amount'];
        $paymentMethod = e($data['payment_method']);
        $paymentDate = e($data['payment_date']);
        $transactionId = e($data['transaction_id']);
        $cardInfo = e($data['card_info']);
        $receiver = PAYMENT_RECEIVER;

        $cardInfoHtml = '';
        if ($cardInfo) {
            $cardInfoHtml = <<<HTML
                <div class="info-row">
                    <span class="label">카드 정보</span>
                    <span class="value">{$cardInfo}</span>
                </div>
HTML;
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$siteName} - 결제 완료</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Malgun Gothic', sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f3f4f6; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 30px 20px; text-align: center; border-radius: 10px 10px 0 0; }
        .header h1 { margin: 0; font-size: 24px; font-weight: 600; }
        .success-icon { font-size: 48px; margin-bottom: 10px; }
        .content { background: white; padding: 40px 30px; border-radius: 0 0 10px 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .info-box { background: #f9fafb; padding: 25px; border-radius: 8px; margin: 25px 0; border-left: 4px solid #10b981; }
        .info-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #e5e7eb; }
        .info-row:last-child { border-bottom: none; }
        .label { font-weight: 600; color: #6b7280; font-size: 14px; }
        .value { color: #111827; font-size: 14px; text-align: right; }
        .amount { font-size: 28px; font-weight: bold; color: #10b981; }
        .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 13px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="success-icon">✅</div>
            <h1>결제 완료</h1>
        </div>
        <div class="content">
            <p><strong>{$recipientName}</strong>님의 결제가 성공적으로 완료되었습니다.</p>
            <p>결제 내역은 아래와 같습니다.</p>

            <div class="info-box">
                <div class="info-row">
                    <span class="label">상품/서비스</span>
                    <span class="value">{$goodname}</span>
                </div>
                <div class="info-row">
                    <span class="label">결제 금액</span>
                    <span class="amount">₩{$amount}</span>
                </div>
                <div class="info-row">
                    <span class="label">결제 수단</span>
                    <span class="value">{$paymentMethod}</span>
                </div>
                {$cardInfoHtml}
                <div class="info-row">
                    <span class="label">결제 일시</span>
                    <span class="value">{$paymentDate}</span>
                </div>
                <div class="info-row">
                    <span class="label">거래 번호</span>
                    <span class="value">{$transactionId}</span>
                </div>
            </div>

            <p style="text-align: center; color: #6b7280; margin-top: 30px;">
                감사합니다.
            </p>
        </div>
        <div class="footer">
            <p>결제 받는 자: <strong>{$receiver}</strong></p>
            <p style="margin-top: 15px; color: #9ca3af;">ⓒ {$siteName}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
