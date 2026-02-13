<?php
/**
 * 결제 페이지
 */

require_once __DIR__ . '/../libs/config.php';
require_once __DIR__ . '/../libs/payment.php';

$paymentManager = new PaymentManager();

$error = '';
$link = null;
$inicisParams = null;

// 토큰 확인
$token = $_GET['token'] ?? '';

if (empty($token)) {
    $error = '올바른 결제 링크가 아닙니다.';
} else {
    try {
        // 결제 링크 조회
        $link = $paymentManager->getPaymentLinkByToken($token);

        // 모바일 감지
        $isMobile = PaymentManager::isMobile();

        // INICIS 파라미터 생성
        $inicisParams = $paymentManager->prepareInicisPayment($link['link_id'], $isMobile);

        // 세션에 link_id 저장 (process.php에서 사용)
        session_start();
        $_SESSION['payment_link_id'] = $link['link_id'];
        $_SESSION['payment_link_token'] = $token;

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>결제 - <?= SITE_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@400;500;600;700&display=swap');
        body {
            font-family: 'Noto Sans KR', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
    </style>
    <?php if ($link && !$isMobile): ?>
    <!-- INICIS PC 결제 스크립트 -->
    <script src="<?= INICIS_PC_SCRIPT ?>"></script>
    <?php endif; ?>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full">
            <!-- 로고 -->
            <div class="text-center mb-8">
                <div class="bg-white rounded-full w-20 h-20 flex items-center justify-center mx-auto mb-4 shadow-lg">
                    <i class="fas fa-credit-card text-3xl text-indigo-600"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">
                    <?= SITE_NAME ?> 결제
                </h1>
                <p class="text-sm text-gray-600 mt-1">
                    안전한 결제 시스템
                </p>
            </div>

            <?php if ($error): ?>
                <!-- 오류 메시지 -->
                <div class="bg-white rounded-lg shadow-lg p-8 text-center">
                    <div class="bg-red-100 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-exclamation-circle text-3xl text-red-600"></i>
                    </div>
                    <h2 class="text-xl font-semibold text-gray-900 mb-2">
                        결제 링크 오류
                    </h2>
                    <p class="text-gray-600 mb-6">
                        <?= e($error) ?>
                    </p>
                    <p class="text-sm text-gray-500">
                        결제 링크를 다시 확인하거나<br>
                        문의: <?= PAYMENT_RECEIVER ?>
                    </p>
                </div>

            <?php else: ?>
                <!-- 결제 정보 -->
                <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                    <!-- 결제 상세 -->
                    <div class="p-8">
                        <h2 class="text-lg font-semibold text-gray-900 mb-6 pb-4 border-b">
                            <i class="fas fa-shopping-cart mr-2"></i>
                            결제 정보
                        </h2>

                        <dl class="space-y-4">
                            <div class="flex justify-between py-3 border-b border-gray-100">
                                <dt class="text-sm text-gray-600">상품/서비스</dt>
                                <dd class="text-sm font-medium text-gray-900 text-right max-w-xs">
                                    <?= e($link['payment_goodname']) ?>
                                </dd>
                            </div>

                            <?php if ($link['payment_description']): ?>
                            <div class="py-3 border-b border-gray-100">
                                <dt class="text-sm text-gray-600 mb-2">설명</dt>
                                <dd class="text-sm text-gray-700 bg-gray-50 p-3 rounded">
                                    <?= nl2br(e($link['payment_description'])) ?>
                                </dd>
                            </div>
                            <?php endif; ?>

                            <div class="flex justify-between py-3 border-b border-gray-100">
                                <dt class="text-sm text-gray-600">결제 금액</dt>
                                <dd class="text-2xl font-bold text-indigo-600">
                                    <?= format_currency($link['payment_amount']) ?>
                                </dd>
                            </div>

                            <div class="flex justify-between py-3 border-b border-gray-100">
                                <dt class="text-sm text-gray-600">결제 받는 자</dt>
                                <dd class="text-sm text-gray-900"><?= PAYMENT_RECEIVER ?></dd>
                            </div>

                            <?php if ($link['buyer_name']): ?>
                            <div class="flex justify-between py-3 border-b border-gray-100">
                                <dt class="text-sm text-gray-600">결제자</dt>
                                <dd class="text-sm text-gray-900"><?= e($link['buyer_name']) ?></dd>
                            </div>
                            <?php endif; ?>
                        </dl>

                        <!-- 결제 수단 안내 -->
                        <div class="mt-6 bg-indigo-50 rounded-lg p-4">
                            <p class="text-sm text-indigo-900 font-medium mb-2">
                                <i class="fas fa-info-circle mr-1"></i>
                                지원 결제 수단
                            </p>
                            <div class="flex flex-wrap gap-2 text-xs text-indigo-700">
                                <span class="bg-white px-3 py-1 rounded-full">💳 신용카드</span>
                                <span class="bg-white px-3 py-1 rounded-full">🏦 계좌이체</span>
                                <span class="bg-white px-3 py-1 rounded-full">📱 간편결제</span>
                            </div>
                        </div>

                        <!-- 결제 버튼 -->
                        <div class="mt-8">
                            <?php if ($isMobile): ?>
                                <!-- 모바일 결제 폼 -->
                                <form name="ini" id="mobilepay" method="post" action="<?= INICIS_MOBILE_URL ?>">
                                    <?php foreach ($inicisParams as $key => $value): ?>
                                        <input type="hidden" name="<?= $key ?>" value="<?= e($value) ?>">
                                    <?php endforeach; ?>

                                    <button type="submit"
                                            class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-semibold py-4 px-6 rounded-lg shadow-lg transition-all transform hover:scale-105">
                                        <i class="fas fa-lock mr-2"></i>
                                        안전하게 결제하기
                                    </button>
                                </form>

                            <?php else: ?>
                                <!-- PC 결제 버튼 -->
                                <button onclick="startPayment()"
                                        class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-semibold py-4 px-6 rounded-lg shadow-lg transition-all transform hover:scale-105">
                                    <i class="fas fa-lock mr-2"></i>
                                    안전하게 결제하기
                                </button>

                                <!-- INICIS PC 결제 폼 -->
                                <form id="SendPayForm_id" name="SendPayForm" method="post" acceptCharset="utf-8">
                                    <?php foreach ($inicisParams as $key => $value): ?>
                                        <?php if ($value !== null && $value !== ''): ?>
                                        <input type="hidden" name="<?= $key ?>" value="<?= e($value) ?>">
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </form>
                            <?php endif; ?>
                        </div>

                        <!-- 보안 안내 -->
                        <div class="mt-6 text-center text-xs text-gray-500">
                            <i class="fas fa-shield-alt mr-1"></i>
                            결제 정보는 암호화되어 안전하게 보호됩니다
                        </div>
                    </div>

                    <!-- 푸터 -->
                    <div class="bg-gray-50 px-8 py-4 border-t border-gray-200 text-center">
                        <p class="text-xs text-gray-600">
                            결제 문의: <?= PAYMENT_RECEIVER ?>
                        </p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- 카피라이트 -->
            <div class="mt-6 text-center text-xs text-gray-500">
                &copy; <?= date('Y') ?> <?= SITE_NAME ?>. All rights reserved.
            </div>
        </div>
    </div>

    <?php if ($link && !$isMobile): ?>
    <script>
    // INICIS PC 결제 시작
    function startPayment() {
        INIStdPay.pay('SendPayForm_id');
    }
    </script>
    <?php endif; ?>
</body>
</html>
