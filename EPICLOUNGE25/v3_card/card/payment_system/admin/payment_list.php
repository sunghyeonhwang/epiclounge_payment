<?php
/**
 * 결제 내역 조회
 */

require_once __DIR__ . '/../libs/config.php';
require_once __DIR__ . '/../libs/auth.php';
require_once __DIR__ . '/../libs/payment.php';

$auth = new Auth();
$auth->requireAuth();

$paymentManager = new PaymentManager();

// 필터
$filters = [
    'search' => $_GET['search'] ?? '',
    'link_status' => $_GET['link_status'] ?? '',
    'payment_status' => $_GET['payment_status'] ?? '',
    'category' => $_GET['category'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? ''
];

// 결제 내역 조회
$payments = $paymentManager->getPaymentLinks($auth->getAdminId(), $filters);

$pageTitle = '결제 내역';
?>

<?php include __DIR__ . '/components/header.php'; ?>
<?php include __DIR__ . '/components/sidebar.php'; ?>

<!-- 결제 내역 -->
<div class="space-y-6">
    <!-- 페이지 헤더 -->
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">
            <i class="fas fa-list mr-2"></i>
            결제 내역
        </h1>
        <a href="create_payment.php" class="btn-primary">
            <i class="fas fa-plus-circle mr-2"></i>
            결제 링크 생성
        </a>
    </div>

    <!-- 필터 -->
    <div class="bg-white rounded-lg shadow p-6">
        <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <!-- 검색 -->
            <div class="md:col-span-2">
                <input type="text" name="search"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                       placeholder="상품명, 결제자명, 전화번호 검색"
                       value="<?= e($filters['search']) ?>">
            </div>

            <!-- 링크 상태 -->
            <div>
                <select name="link_status"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <option value="">전체 상태</option>
                    <option value="active" <?= $filters['link_status'] === 'active' ? 'selected' : '' ?>>활성</option>
                    <option value="used" <?= $filters['link_status'] === 'used' ? 'selected' : '' ?>>사용완료</option>
                    <option value="expired" <?= $filters['link_status'] === 'expired' ? 'selected' : '' ?>>만료</option>
                    <option value="cancelled" <?= $filters['link_status'] === 'cancelled' ? 'selected' : '' ?>>취소</option>
                </select>
            </div>

            <!-- 결제 상태 -->
            <div>
                <select name="payment_status"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <option value="">전체 결제</option>
                    <option value="pending" <?= $filters['payment_status'] === 'pending' ? 'selected' : '' ?>>대기</option>
                    <option value="completed" <?= $filters['payment_status'] === 'completed' ? 'selected' : '' ?>>완료</option>
                    <option value="failed" <?= $filters['payment_status'] === 'failed' ? 'selected' : '' ?>>실패</option>
                    <option value="refunded" <?= $filters['payment_status'] === 'refunded' ? 'selected' : '' ?>>환불</option>
                </select>
            </div>

            <!-- 버튼 -->
            <div class="flex gap-2">
                <button type="submit" class="flex-1 btn-primary">
                    <i class="fas fa-search"></i>
                </button>
                <a href="payment_list.php" class="flex-1 btn-secondary text-center">
                    <i class="fas fa-redo"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- 테이블 -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">생성일시</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">상품/서비스</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">금액</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">결제자</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">링크상태</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">결제상태</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">액션</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($payments)): ?>
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-inbox text-4xl mb-3 text-gray-300"></i>
                                <p>검색 결과가 없습니다.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($payments as $payment): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    #<?= $payment['link_id'] ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <div><?= format_date($payment['link_created_at']) ?></div>
                                    <div class="text-xs text-gray-400"><?= date('H:i', strtotime($payment['link_created_at'])) ?></div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <div class="font-medium max-w-xs truncate"><?= e($payment['payment_goodname']) ?></div>
                                    <?php if ($payment['payment_category']): ?>
                                        <div class="text-xs text-gray-500"><?= e($payment['payment_category']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                    <?= format_currency($payment['payment_amount']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php if ($payment['buyer_name']): ?>
                                        <div><?= e($payment['buyer_name']) ?></div>
                                        <?php if ($payment['buyer_phone']): ?>
                                            <div class="text-xs text-gray-400"><?= format_phone($payment['buyer_phone']) ?></div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-gray-400">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $linkStatusColor = get_status_color($payment['link_status']);
                                    $linkStatusText = get_link_status_text($payment['link_status']);
                                    ?>
                                    <span class="badge badge-<?= $linkStatusColor ?>">
                                        <?= $linkStatusText ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $paymentStatusColor = get_status_color($payment['payment_status']);
                                    $paymentStatusText = get_payment_status_text($payment['payment_status']);
                                    ?>
                                    <span class="badge badge-<?= $paymentStatusColor ?>">
                                        <?= $paymentStatusText ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                                    <a href="payment_detail.php?id=<?= $payment['link_id'] ?>"
                                       class="text-indigo-600 hover:text-indigo-900"
                                       title="상세보기">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (!empty($payments)): ?>
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
            <p class="text-sm text-gray-600">
                총 <strong><?= number_format(count($payments)) ?></strong>건
            </p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/components/footer.php'; ?>
