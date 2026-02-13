<?php
/**
 * 결제 완료 페이지
 */

require_once __DIR__ . '/../libs/config.php';

// 세션에서 결제 정보 가져오기
session_start();
$paymentAmount = $_SESSION['payment_amount'] ?? 0;
$paymentGoodname = $_SESSION['payment_goodname'] ?? '';
$paymentMethod = $_SESSION['payment_method'] ?? '';

// 세션 정리 (보안)
unset($_SESSION['payment_link_id']);
unset($_SESSION['payment_link_token']);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>결제 완료 - <?= SITE_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@400;500;600;700&display=swap');
        body {
            font-family: 'Noto Sans KR', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        @keyframes checkmark {
            0% { transform: scale(0); opacity: 0; }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); opacity: 1; }
        }
        .checkmark {
            animation: checkmark 0.5s ease-in-out;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-green-50 to-blue-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full">
            <!-- 성공 카드 -->
            <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
                <!-- 헤더 -->
                <div class="bg-gradient-to-r from-green-500 to-emerald-600 px-8 py-12 text-center">
                    <div class="checkmark bg-white rounded-full w-24 h-24 flex items-center justify-center mx-auto mb-6 shadow-lg">
                        <i class="fas fa-check text-5xl text-green-600"></i>
                    </div>
                    <h1 class="text-3xl font-bold text-white mb-2">
                        결제 완료!
                    </h1>
                    <p class="text-green-100">
                        결제가 성공적으로 완료되었습니다
                    </p>
                </div>

                <!-- 내용 -->
                <div class="px-8 py-8">
                    <?php if ($paymentGoodname): ?>
                    <div class="bg-gray-50 rounded-lg p-6 mb-6">
                        <h2 class="text-sm text-gray-600 mb-3">결제 내역</h2>
                        <dl class="space-y-2">
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-600">상품/서비스</dt>
                                <dd class="text-sm font-medium text-gray-900 text-right">
                                    <?= e($paymentGoodname) ?>
                                </dd>
                            </div>
                            <?php if ($paymentAmount): ?>
                            <div class="flex justify-between pt-2 border-t border-gray-200">
                                <dt class="text-sm text-gray-600">결제 금액</dt>
                                <dd class="text-xl font-bold text-gray-900">
                                    <?= format_currency($paymentAmount) ?>
                                </dd>
                            </div>
                            <?php endif; ?>
                            <?php if ($paymentMethod): ?>
                            <div class="flex justify-between pt-2 border-t border-gray-200">
                                <dt class="text-sm text-gray-600">결제 수단</dt>
                                <dd class="text-sm text-gray-900">
                                    <?= get_payment_method_text($paymentMethod) ?>
                                </dd>
                            </div>
                            <?php endif; ?>
                        </dl>
                    </div>
                    <?php endif; ?>

                    <!-- 안내 메시지 -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                        <div class="flex items-start">
                            <i class="fas fa-info-circle text-blue-600 mt-1 mr-3"></i>
                            <div class="text-sm text-blue-900">
                                <p class="font-medium mb-1">안내사항</p>
                                <ul class="space-y-1 text-blue-800">
                                    <li>• 결제 영수증이 등록하신 연락처로 발송됩니다</li>
                                    <li>• 결제 관련 문의는 <?= PAYMENT_RECEIVER ?>로 연락 주세요</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- 감사 메시지 -->
                    <div class="text-center">
                        <p class="text-lg text-gray-900 font-medium mb-2">
                            감사합니다 🎉
                        </p>
                        <p class="text-sm text-gray-600">
                            <?= PAYMENT_RECEIVER ?>
                        </p>
                    </div>
                </div>

                <!-- 푸터 -->
                <div class="bg-gray-50 px-8 py-4 border-t border-gray-200 text-center">
                    <p class="text-xs text-gray-600">
                        이 창을 닫으셔도 됩니다
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
