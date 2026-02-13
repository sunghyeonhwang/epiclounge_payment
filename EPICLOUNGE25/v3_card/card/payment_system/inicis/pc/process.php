<?php
/**
 * INICIS PC 결제 완료 처리
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

    // 세션에서 link_id 가져오기
    $linkId = $_SESSION['payment_link_id'] ?? 0;

    if (!$linkId) {
        throw new Exception('결제 정보를 찾을 수 없습니다.');
    }

    // 결제 실패 처리
    if ($P_STATUS !== '00') {
        error_log("Payment failed: LinkID={$linkId}, Status={$P_STATUS}, Message={$P_RMESG1}");

        header('Location: ' . SITE_URL . '/payment/fail.php?resultCode=' . urlencode($P_STATUS) . '&resultMsg=' . urlencode($P_RMESG1));
        exit;
    }

    // 승인 요청
    $P_REQ_URL = "https://stdpay.inicis.com/stdjs/INIStdPayRequest.php";
    $P_CHARSET = "UTF8";
    $P_NOTI = "";

    $params = [
        'P_TID' => $P_TID,
        'P_MID' => $P_MID
    ];

    $response = $util->reqAuth($P_REQ_URL, $params, $P_CHARSET);

    // 응답 파싱
    $responseData = [];
    parse_str($response, $responseData);

    $resultCode = $responseData['P_STATUS'] ?? '';
    $resultMsg = $responseData['P_RMESG1'] ?? '';

    // 승인 실패
    if ($resultCode !== '00') {
        error_log("Payment auth failed: LinkID={$linkId}, ResultCode={$resultCode}, Message={$resultMsg}");

        header('Location: ' . SITE_URL . '/payment/fail.php?resultCode=' . urlencode($resultCode) . '&resultMsg=' . urlencode($resultMsg));
        exit;
    }

    // INICIS 응답 데이터 구조화
    $inicisResponse = [
        'tid' => $responseData['P_TID'] ?? '',
        'MOID' => $responseData['P_OID'] ?? '',
        'oid' => $responseData['P_OID'] ?? '',
        'resultCode' => $resultCode,
        'resultMsg' => $resultMsg,
        'TotPrice' => $responseData['P_AMT'] ?? '',
        'price' => $responseData['P_AMT'] ?? '',
        'payMethod' => $responseData['P_TYPE'] ?? '',
        'goodName' => $responseData['P_GOODS'] ?? '',
        'buyerName' => $responseData['P_UNAME'] ?? '',
        'buyerTel' => $responseData['P_MOBILE'] ?? '',
        'buyerEmail' => $responseData['P_EMAIL'] ?? '',
        'CARD_Code' => $responseData['P_CARD_ISSUER_CODE'] ?? '',
        'CARD_BankCode' => $responseData['P_CARD_NAME'] ?? '',
        'CARD_Quota' => $responseData['P_CARD_INSTALL_MONTH'] ?? '00',
        'CARD_Num' => $responseData['P_CARD_NUM'] ?? '',
        'applNum' => $responseData['P_AUTH_NO'] ?? '',
        'applDate' => $responseData['P_AUTH_DT'] ?? date('Ymd'),
        'applTime' => date('His'),
        'VACT_Num' => $responseData['P_VACT_NUM'] ?? '',
        'VACT_BankCode' => $responseData['P_VACT_BANK_NAME'] ?? '',
        'VACT_Date' => $responseData['P_VACT_DATE'] ?? ''
    ];

    // 결제 완료 처리
    $paymentManager->completePayment($linkId, $inicisResponse);

    // 세션에 결제 정보 저장 (success.php에서 표시용)
    $_SESSION['payment_amount'] = $responseData['P_AMT'] ?? '';
    $_SESSION['payment_goodname'] = $responseData['P_GOODS'] ?? '';
    $_SESSION['payment_method'] = $responseData['P_TYPE'] ?? '';

    // 성공 페이지로 리다이렉트
    header('Location: ' . SITE_URL . '/payment/success.php');
    exit;

} catch (Exception $e) {
    error_log("Payment process error: " . $e->getMessage());

    header('Location: ' . SITE_URL . '/payment/fail.php?resultMsg=' . urlencode($e->getMessage()));
    exit;
}
