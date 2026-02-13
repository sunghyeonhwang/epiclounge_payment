<?php
/**
 * 결제 실패/취소 페이지
 */

require_once __DIR__ . '/../libs/config.php';

// 에러 메시지
$resultCode = $_GET['resultCode'] ?? '';
$resultMsg = $_GET['resultMsg'] ?? '결제가 취소되었습니다.';
$token = $_GET['token'] ?? '';

// 세션 정리
session_start();
unset($_SESSION['payment_link_id']);
unset($_SESSION['payment_link_token']);

$paymentUrl = '';
if ($token) {
    $paymentUrl = SITE_URL . '/payment/index.php?token=' . $token;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>결제 실패 - <?= SITE_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@400;500;600;700&display=swap');
        body {
            font-family: 'Noto Sans KR', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full">
            <!-- 실패 카드 -->
            <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
                <!-- 헤더 -->
                <div class="bg-gradient-to-r from-red-500 to-pink-600 px-8 py-12 text-center">
                    <div class="bg-white rounded-full w-24 h-24 flex items-center justify-center mx-auto mb-6 shadow-lg">
                        <i class="fas fa-times text-5xl text-red-600"></i>
                    </div>
                    <h1 class="text-3xl font-bold text-white mb-2">
                        결제 실패
                    </h1>
                    <p class="text-red-100">
                        결제 처리 중 문제가 발생했습니다
                    </p>
                </div>

                <!-- 내용 -->
                <div class="px-8 py-8">
                    <!-- 에러 메시지 -->
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                        <div class="flex items-start">
                            <i class="fas fa-exclamation-circle text-red-600 mt-1 mr-3"></i>
                            <div class="text-sm text-red-900">
                                <p class="font-medium mb-1">오류 내용</p>
                                <p class="text-red-800"><?= e($resultMsg) ?></p>
                                <?php if ($resultCode): ?>
                                <p class="text-xs text-red-600 mt-1">오류 코드: <?= e($resultCode) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- 안내 메시지 -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                        <div class="flex items-start">
                            <i class="fas fa-info-circle text-blue-600 mt-1 mr-3"></i>
                            <div class="text-sm text-blue-900">
                                <p class="font-medium mb-1">다음 사항을 확인해주세요</p>
                                <ul class="space-y-1 text-blue-800">
                                    <li>• 카드 한도 및 잔액을 확인해주세요</li>
                                    <li>• 카드 정보를 정확하게 입력했는지 확인해주세요</li>
                                    <li>• 결제 승인이 가능한 카드인지 확인해주세요</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- 버튼 -->
                    <div class="space-y-3">
                        <?php if ($paymentUrl): ?>
                        <a href="<?= e($paymentUrl) ?>"
                           class="block w-full bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-semibold py-3 px-6 rounded-lg shadow-lg text-center transition-all">
                            <i class="fas fa-redo mr-2"></i>
                            다시 시도하기
                        </a>
                        <?php endif; ?>

                        <p class="text-center text-sm text-gray-600">
                            문의: <?= PAYMENT_RECEIVER ?>
                        </p>
                    </div>
                </div>

                <!-- 푸터 -->
                <div class="bg-gray-50 px-8 py-4 border-t border-gray-200 text-center">
                    <p class="text-xs text-gray-600">
                        결제가 완료되지 않았습니다
                    </p>
                </div>
            </div>

            <!-- 카피라이트 -->
            <div class="mt-6 text-center text-xs text-gray-500">
                &copy; <?= date('Y') ?> <?= SITE_NAME ?>. All rights reserved.
            </div>
        </div>
    </div>
</body>
</html>
