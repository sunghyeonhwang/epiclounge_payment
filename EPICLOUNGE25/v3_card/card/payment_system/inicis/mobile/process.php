<?php
/**
 * INICIS 모바일 결제 완료 처리
 */

require_once __DIR__ . '/../../libs/config.php';
require_once __DIR__ . '/../../libs/payment.php';
require_once __DIR__ . '/INIStdPayUtil.php';

session_start();

$paymentManager = new PaymentManager();
$util = new INIStdPayUtil();

try {
    // POST 데이터 수신
    $P_STATUS = $_POST['P_STATUS'] ?? '';
    $P_TID = $_POST['P_TID'] ?? '';
    $P_MID = $_POST['P_MID'] ?? '';
    $P_OID = $_POST['P_OID'] ?? '';
    $P_AMT = $_POST['P_AMT'] ?? '';
    $P_RMESG1 = $_POST['P_RMESG1'] ?? '';
    $P_TYPE = $_POST['P_TYPE'] ?? '';

    // 세션에서 link_id 가져오기
    $linkId = $_SESSION['payment_link_id'] ?? 0;
    $token = $_SESSION['payment_link_token'] ?? '';

    if (!$linkId) {
        throw new Exception('결제 정보를 찾을 수 없습니다.');
    }

    // 결제 실패 처리
    if ($P_STATUS !== '00') {
        error_log("Mobile payment failed: LinkID={$linkId}, Status={$P_STATUS}, Message={$P_RMESG1}");

        header('Location: ' . SITE_URL . '/payment/fail.php?token=' . urlencode($token) . '&resultCode=' . urlencode($P_STATUS) . '&resultMsg=' . urlencode($P_RMESG1));
        exit;
    }

    // INICIS 응답 데이터 구조화 (모바일은 별도 승인 요청 불필요)
    $inicisResponse = [
        'tid' => $P_TID,
        'MOID' => $P_OID,
        'oid' => $P_OID,
        'resultCode' => $P_STATUS,
        'resultMsg' => $P_RMESG1,
        'TotPrice' => $P_AMT,
        'price' => $P_AMT,
        'payMethod' => $P_TYPE,
        'goodName' => $_POST['P_GOODS'] ?? '',
        'buyerName' => $_POST['P_UNAME'] ?? '',
        'buyerTel' => $_POST['P_MOBILE'] ?? '',
        'buyerEmail' => $_POST['P_EMAIL'] ?? '',
        'CARD_Code' => $_POST['P_CARD_ISSUER_CODE'] ?? '',
        'CARD_BankCode' => $_POST['P_CARD_NAME'] ?? '',
        'CARD_Quota' => $_POST['P_CARD_INSTALL_MONTH'] ?? '00',
        'CARD_Num' => $_POST['P_CARD_NUM'] ?? '',
        'applNum' => $_POST['P_AUTH_NO'] ?? '',
        'applDate' => $_POST['P_AUTH_DT'] ?? date('Ymd'),
        'applTime' => date('His'),
        'VACT_Num' => $_POST['P_VACT_NUM'] ?? '',
        'VACT_BankCode' => $_POST['P_VACT_BANK_NAME'] ?? '',
        'VACT_Date' => $_POST['P_VACT_DATE'] ?? ''
    ];

    // 결제 완료 처리
    $paymentManager->completePayment($linkId, $inicisResponse);

    // 세션에 결제 정보 저장 (success.php에서 표시용)
    $_SESSION['payment_amount'] = $P_AMT;
    $_SESSION['payment_goodname'] = $_POST['P_GOODS'] ?? '';
    $_SESSION['payment_method'] = $P_TYPE;

    // 성공 페이지로 리다이렉트
    header('Location: ' . SITE_URL . '/payment/success.php');
    exit;

} catch (Exception $e) {
    error_log("Mobile payment process error: " . $e->getMessage());

    $token = $_SESSION['payment_link_token'] ?? '';
    header('Location: ' . SITE_URL . '/payment/fail.php?token=' . urlencode($token) . '&resultMsg=' . urlencode($e->getMessage()));
    exit;
}
