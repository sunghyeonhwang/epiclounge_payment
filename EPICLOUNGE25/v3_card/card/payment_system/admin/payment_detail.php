<?php
/**
 * 결제 상세 정보
 */

require_once __DIR__ . '/../libs/config.php';
require_once __DIR__ . '/../libs/auth.php';
require_once __DIR__ . '/../libs/payment.php';
require_once __DIR__ . '/../libs/sms.php';

$auth = new Auth();
$auth->requireAuth();

$paymentManager = new PaymentManager();
$smsManager = new SMSManager();

$linkId = $_GET['id'] ?? 0;

// 결제 링크 조회
try {
    $link = $paymentManager->getPaymentLinkById($linkId);

    if (!$link || $link['admin_id'] != $auth->getAdminId()) {
        throw new Exception('결제 링크를 찾을 수 없습니다.');
    }

    // 거래 내역 조회
    $transaction = $paymentManager->getTransaction($linkId);

    // 알림 내역 조회
    $notifications = $smsManager->getNotifications($linkId);

} catch (Exception $e) {
    header('Location: payment_list.php');
    exit;
}

$pageTitle = '결제 상세';
$paymentUrl = SITE_URL . '/payment/index.php?token=' . $link['link_token'];
?>

<?php include __DIR__ . '/components/header.php'; ?>
<?php include __DIR__ . '/components/sidebar.php'; ?>

<!-- 결제 상세 -->
<div class="space-y-6">
    <!-- 페이지 헤더 -->
    <div class="flex items-center justify-between">
        <div>
            <a href="payment_list.php" class="text-sm text-indigo-600 hover:text-indigo-800 mb-2 inline-block">
                <i class="fas fa-arrow-left mr-1"></i> 목록으로
            </a>
            <h1 class="text-2xl font-bold text-gray-900">
                결제 상세 정보 #<?= $linkId ?>
            </h1>
        </div>

        <?php if ($link['link_status'] === 'active'): ?>
        <button onclick="copyToClipboard('<?= e($paymentUrl) ?>', '결제 URL이 복사되었습니다!')"
                class="btn-primary">
            <i class="fas fa-copy mr-2"></i>
            URL 복사
        </button>
        <?php endif; ?>
    </div>

    <!-- 상태 요약 -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div>
                <div class="text-sm text-gray-600 mb-1">링크 상태</div>
                <?php
                $linkStatusColor = get_status_color($link['link_status']);
                $linkStatusText = get_link_status_text($link['link_status']);
                ?>
                <span class="badge badge-<?= $linkStatusColor ?> text-lg">
                    <?= $linkStatusText ?>
                </span>
            </div>

            <div>
                <div class="text-sm text-gray-600 mb-1">결제 상태</div>
                <?php
                $paymentStatusColor = get_status_color($link['payment_status']);
                $paymentStatusText = get_payment_status_text($link['payment_status']);
                ?>
                <span class="badge badge-<?= $paymentStatusColor ?> text-lg">
                    <?= $paymentStatusText ?>
                </span>
            </div>

            <div>
                <div class="text-sm text-gray-600 mb-1">결제 금액</div>
                <div class="text-2xl font-bold text-gray-900">
                    <?= format_currency($link['payment_amount']) ?>
                </div>
            </div>

            <div>
                <div class="text-sm text-gray-600 mb-1">생성일시</div>
                <div class="text-sm text-gray-900">
                    <?= format_datetime($link['link_created_at']) ?>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- 결제 정보 -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4 border-b pb-3">
                <i class="fas fa-shopping-cart mr-2"></i>
                결제 정보
            </h2>

            <dl class="space-y-3">
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <dt class="text-sm text-gray-600">상품/서비스</dt>
                    <dd class="text-sm font-medium text-gray-900 text-right"><?= e($link['payment_goodname']) ?></dd>
                </div>

                <?php if ($link['payment_category']): ?>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <dt class="text-sm text-gray-600">분류</dt>
                    <dd class="text-sm text-gray-900"><?= e($link['payment_category']) ?></dd>
                </div>
                <?php endif; ?>

                <div class="flex justify-between py-2 border-b border-gray-100">
                    <dt class="text-sm text-gray-600">금액</dt>
                    <dd class="text-lg font-bold text-gray-900"><?= format_currency($link['payment_amount']) ?></dd>
                </div>

                <?php if ($link['payment_description']): ?>
                <div class="py-2 border-b border-gray-100">
                    <dt class="text-sm text-gray-600 mb-1">설명</dt>
                    <dd class="text-sm text-gray-900"><?= nl2br(e($link['payment_description'])) ?></dd>
                </div>
                <?php endif; ?>

                <div class="flex justify-between py-2 border-b border-gray-100">
                    <dt class="text-sm text-gray-600">유효기간</dt>
                    <dd class="text-sm text-gray-900">
                        <?= $link['link_expire_date'] ? format_datetime($link['link_expire_date']) : '무제한' ?>
                    </dd>
                </div>

                <?php if ($link['link_memo']): ?>
                <div class="py-2 border-b border-gray-100">
                    <dt class="text-sm text-gray-600 mb-1">관리자 메모</dt>
                    <dd class="text-sm text-gray-700 bg-yellow-50 p-3 rounded">
                        <?= nl2br(e($link['link_memo'])) ?>
                    </dd>
                </div>
                <?php endif; ?>
            </dl>
        </div>

        <!-- 결제자 정보 -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4 border-b pb-3">
                <i class="fas fa-user mr-2"></i>
                결제자 정보
            </h2>

            <dl class="space-y-3">
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <dt class="text-sm text-gray-600">이름</dt>
                    <dd class="text-sm text-gray-900"><?= e($link['buyer_name'] ?: '-') ?></dd>
                </div>

                <div class="flex justify-between py-2 border-b border-gray-100">
                    <dt class="text-sm text-gray-600">전화번호</dt>
                    <dd class="text-sm text-gray-900"><?= format_phone($link['buyer_phone']) ?></dd>
                </div>

                <div class="flex justify-between py-2 border-b border-gray-100">
                    <dt class="text-sm text-gray-600">이메일</dt>
                    <dd class="text-sm text-gray-900"><?= e($link['buyer_email'] ?: '-') ?></dd>
                </div>

                <?php if ($link['payment_completed_at']): ?>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <dt class="text-sm text-gray-600">결제 완료일</dt>
                    <dd class="text-sm text-gray-900"><?= format_datetime($link['payment_completed_at']) ?></dd>
                </div>
                <?php endif; ?>
            </dl>
        </div>
    </div>

    <!-- 결제 URL -->
    <?php if ($link['link_status'] === 'active'): ?>
    <div class="bg-indigo-50 rounded-lg shadow p-6 border-l-4 border-indigo-600">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
            <i class="fas fa-link mr-2"></i>
            결제 URL
        </h2>
        <div class="flex gap-2">
            <input type="text" readonly value="<?= e($paymentUrl) ?>"
                   class="flex-1 px-4 py-3 border border-gray-300 rounded-lg bg-white font-mono text-sm"
                   id="paymentUrlInput">
            <button onclick="copyToClipboard(document.getElementById('paymentUrlInput').value, '결제 URL이 복사되었습니다!')"
                    class="btn-primary whitespace-nowrap">
                <i class="fas fa-copy mr-2"></i>
                복사
            </button>
        </div>
    </div>
    <?php endif; ?>

    <!-- 거래 내역 -->
    <?php if ($transaction): ?>
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4 border-b pb-3">
            <i class="fas fa-receipt mr-2"></i>
            거래 내역
        </h2>

        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="py-2 border-b border-gray-100">
                <dt class="text-sm text-gray-600 mb-1">거래번호</dt>
                <dd class="text-sm font-mono text-gray-900"><?= e($transaction['inicis_tid']) ?></dd>
            </div>

            <div class="py-2 border-b border-gray-100">
                <dt class="text-sm text-gray-600 mb-1">주문번호</dt>
                <dd class="text-sm font-mono text-gray-900"><?= e($transaction['inicis_oid']) ?></dd>
            </div>

            <div class="py-2 border-b border-gray-100">
                <dt class="text-sm text-gray-600 mb-1">결제 수단</dt>
                <dd class="text-sm text-gray-900"><?= get_payment_method_text($transaction['payment_method']) ?></dd>
            </div>

            <div class="py-2 border-b border-gray-100">
                <dt class="text-sm text-gray-600 mb-1">결제 금액</dt>
                <dd class="text-lg font-bold text-gray-900"><?= format_currency($transaction['payment_amount']) ?></dd>
            </div>

            <?php if ($transaction['card_name']): ?>
            <div class="py-2 border-b border-gray-100">
                <dt class="text-sm text-gray-600 mb-1">카드사</dt>
                <dd class="text-sm text-gray-900"><?= e($transaction['card_name']) ?></dd>
            </div>

            <div class="py-2 border-b border-gray-100">
                <dt class="text-sm text-gray-600 mb-1">카드번호</dt>
                <dd class="text-sm font-mono text-gray-900"><?= mask_card_number($transaction['card_num']) ?></dd>
            </div>

            <div class="py-2 border-b border-gray-100">
                <dt class="text-sm text-gray-600 mb-1">할부</dt>
                <dd class="text-sm text-gray-900"><?= $transaction['card_quota'] == '00' ? '일시불' : $transaction['card_quota'] . '개월' ?></dd>
            </div>

            <div class="py-2 border-b border-gray-100">
                <dt class="text-sm text-gray-600 mb-1">승인번호</dt>
                <dd class="text-sm font-mono text-gray-900"><?= e($transaction['card_applnum']) ?></dd>
            </div>
            <?php endif; ?>

            <div class="py-2 border-b border-gray-100">
                <dt class="text-sm text-gray-600 mb-1">승인일시</dt>
                <dd class="text-sm text-gray-900"><?= format_datetime($transaction['payment_date']) ?></dd>
            </div>
        </dl>
    </div>
    <?php endif; ?>

    <!-- 알림 내역 -->
    <?php if (!empty($notifications)): ?>
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4 border-b pb-3">
            <i class="fas fa-bell mr-2"></i>
            알림 발송 내역
        </h2>

        <div class="space-y-3">
            <?php foreach ($notifications as $noti): ?>
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <?php if ($noti['notification_type'] === 'sms'): ?>
                                <i class="fas fa-sms text-indigo-600"></i>
                                <span class="font-medium">SMS</span>
                            <?php else: ?>
                                <i class="fas fa-envelope text-indigo-600"></i>
                                <span class="font-medium">이메일</span>
                            <?php endif; ?>
                        </div>
                        <?php
                        $notiStatusColor = $noti['notification_status'] === 'sent' ? 'green' : 'red';
                        $notiStatusText = $noti['notification_status'] === 'sent' ? '발송완료' : '발송실패';
                        ?>
                        <span class="badge badge-<?= $notiStatusColor ?>">
                            <?= $notiStatusText ?>
                        </span>
                    </div>

                    <div class="text-sm text-gray-600 space-y-1">
                        <div>받는 사람: <?= e($noti['recipient_name']) ?></div>
                        <div>
                            <?= $noti['notification_type'] === 'sms'
                                ? format_phone($noti['recipient_phone'])
                                : e($noti['recipient_email'])
                            ?>
                        </div>
                        <div>발송일시: <?= format_datetime($noti['sent_at']) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/components/footer.php'; ?>
