<?php
/**
 * 관리자 대시보드
 */

require_once __DIR__ . '/../libs/config.php';
require_once __DIR__ . '/../libs/auth.php';
require_once __DIR__ . '/../libs/payment.php';

$auth = new Auth();
$auth->requireAuth();

$paymentManager = new PaymentManager();

// 통계 데이터
$stats = $paymentManager->getDashboardStats($auth->getAdminId());

// 최근 결제 내역
$recentPayments = $paymentManager->getPaymentLinks($auth->getAdminId(), [
    'order_by' => 'l.link_created_at',
    'order_dir' => 'DESC'
]);
$recentPayments = array_slice($recentPayments, 0, 10); // 최근 10개

$pageTitle = '대시보드';
?>

<?php include __DIR__ . '/components/header.php'; ?>
<?php include __DIR__ . '/components/sidebar.php'; ?>

<!-- 대시보드 콘텐츠 -->
<div class="space-y-6">
    <!-- 페이지 헤더 -->
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">
            <i class="fas fa-chart-line mr-2"></i>
            대시보드
        </h1>
        <a href="create_payment.php" class="btn-primary">
            <i class="fas fa-plus-circle mr-2"></i>
            결제 링크 생성
        </a>
    </div>

    <!-- 통계 카드 -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- 오늘 결제 -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="text-sm font-medium text-gray-600">오늘 결제</div>
                <div class="bg-blue-100 rounded-full p-3">
                    <i class="fas fa-calendar-day text-blue-600"></i>
                </div>
            </div>
            <div class="text-3xl font-bold text-gray-900"><?= number_format($stats['today_payments']) ?>건</div>
            <div class="text-sm text-gray-500 mt-1"><?= format_currency($stats['today_amount']) ?></div>
        </div>

        <!-- 이번 달 총액 -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="text-sm font-medium text-gray-600">이번 달</div>
                <div class="bg-green-100 rounded-full p-3">
                    <i class="fas fa-calendar-alt text-green-600"></i>
                </div>
            </div>
            <div class="text-3xl font-bold text-gray-900"><?= format_currency($stats['month_amount']) ?></div>
            <div class="text-sm text-gray-500 mt-1">월 누적 금액</div>
        </div>

        <!-- 총 결제 완료 -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="text-sm font-medium text-gray-600">총 결제</div>
                <div class="bg-purple-100 rounded-full p-3">
                    <i class="fas fa-check-circle text-purple-600"></i>
                </div>
            </div>
            <div class="text-3xl font-bold text-gray-900"><?= number_format($stats['completed_payments']) ?>건</div>
            <div class="text-sm text-gray-500 mt-1"><?= format_currency($stats['total_amount']) ?></div>
        </div>

        <!-- 활성 링크 -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="text-sm font-medium text-gray-600">활성 링크</div>
                <div class="bg-yellow-100 rounded-full p-3">
                    <i class="fas fa-link text-yellow-600"></i>
                </div>
            </div>
            <div class="text-3xl font-bold text-gray-900"><?= number_format($stats['active_links']) ?>개</div>
            <div class="text-sm text-gray-500 mt-1">결제 대기 중</div>
        </div>
    </div>

    <!-- 최근 결제 내역 -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-history mr-2"></i>
                최근 결제 내역
            </h2>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">생성일시</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">상품/서비스</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">금액</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">결제자</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">상태</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">액션</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($recentPayments)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-inbox text-4xl mb-3 text-gray-300"></i>
                                <p>결제 내역이 없습니다.</p>
                                <a href="create_payment.php" class="text-indigo-600 hover:text-indigo-800 mt-2 inline-block">
                                    첫 결제 링크 생성하기 →
                                </a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentPayments as $payment): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= time_ago($payment['link_created_at']) ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <div class="font-medium"><?= e($payment['payment_goodname']) ?></div>
                                    <?php if ($payment['payment_category']): ?>
                                        <div class="text-xs text-gray-500"><?= e($payment['payment_category']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                    <?= format_currency($payment['payment_amount']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= e($payment['buyer_name'] ?: '-') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $statusColor = get_status_color($payment['payment_status']);
                                    $statusText = get_payment_status_text($payment['payment_status']);
                                    ?>
                                    <span class="badge badge-<?= $statusColor ?>">
                                        <?= $statusText ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <a href="payment_detail.php?id=<?= $payment['link_id'] ?>"
                                       class="text-indigo-600 hover:text-indigo-900">
                                        <i class="fas fa-eye mr-1"></i>
                                        상세보기
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (!empty($recentPayments)): ?>
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 text-center">
            <a href="payment_list.php" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                전체 결제 내역 보기 →
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/components/footer.php'; ?>
