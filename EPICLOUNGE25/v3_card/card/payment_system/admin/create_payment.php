<?php
/**
 * 결제 링크 생성
 */

require_once __DIR__ . '/../libs/config.php';
require_once __DIR__ . '/../libs/auth.php';
require_once __DIR__ . '/../libs/payment.php';
require_once __DIR__ . '/../libs/sms.php';
require_once __DIR__ . '/../libs/email.php';

$auth = new Auth();
$auth->requireAuth();

$paymentManager = new PaymentManager();
$smsManager = new SMSManager();
$emailManager = new EmailManager();

$success = '';
$error = '';
$createdLink = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 입력 데이터
        $data = [
            'amount' => $_POST['amount'] ?? 0,
            'goodname' => $_POST['goodname'] ?? '',
            'category' => $_POST['category'] ?? null,
            'description' => $_POST['description'] ?? null,
            'buyer_name' => $_POST['buyer_name'] ?? null,
            'buyer_phone' => $_POST['buyer_phone'] ?? null,
            'buyer_email' => $_POST['buyer_email'] ?? null,
            'expire_days' => $_POST['expire_days'] ?? PAYMENT_LINK_EXPIRE_DAYS,
            'memo' => $_POST['memo'] ?? null
        ];

        // 결제 링크 생성
        $result = $paymentManager->createPaymentLink($auth->getAdminId(), $data);
        $createdLink = $result;

        // SMS 발송
        if (!empty($_POST['send_sms']) && !empty($data['buyer_name']) && !empty($data['buyer_phone'])) {
            $smsManager->sendPaymentLink(
                $result['link_id'],
                $data['buyer_name'],
                $data['buyer_phone'],
                $result['payment_url'],
                $data['amount'],
                $data['goodname']
            );
        }

        // 이메일 발송
        if (!empty($_POST['send_email']) && !empty($data['buyer_name']) && !empty($data['buyer_email'])) {
            $emailManager->sendPaymentLink(
                $result['link_id'],
                $data['buyer_name'],
                $data['buyer_email'],
                $result['payment_url'],
                $data['amount'],
                $data['goodname'],
                $data['description']
            );
        }

        $success = '결제 링크가 생성되었습니다.';

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$pageTitle = '결제 링크 생성';
?>

<?php include __DIR__ . '/components/header.php'; ?>
<?php include __DIR__ . '/components/sidebar.php'; ?>

<!-- 결제 링크 생성 폼 -->
<div class="space-y-6">
    <!-- 페이지 헤더 -->
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">
            <i class="fas fa-plus-circle mr-2"></i>
            결제 링크 생성
        </h1>
        <a href="payment_list.php" class="btn-secondary">
            <i class="fas fa-list mr-2"></i>
            결제 내역
        </a>
    </div>

    <?php if ($success && $createdLink): ?>
    <!-- 생성 성공 -->
    <div class="bg-green-50 border border-green-200 rounded-lg p-6">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <i class="fas fa-check-circle text-green-600 text-2xl"></i>
            </div>
            <div class="ml-4 flex-1">
                <h3 class="text-lg font-semibold text-green-900 mb-3">
                    ✅ <?= e($success) ?>
                </h3>

                <div class="bg-white rounded-lg p-4 border border-green-200">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-link mr-1"></i>
                        결제 URL
                    </label>
                    <div class="flex gap-2">
                        <input type="text" readonly
                               value="<?= e($createdLink['payment_url']) ?>"
                               class="flex-1 px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-sm"
                               id="paymentUrl">
                        <button type="button"
                                onclick="copyToClipboard(document.getElementById('paymentUrl').value, '결제 URL이 복사되었습니다!')"
                                class="btn-primary whitespace-nowrap">
                            <i class="fas fa-copy mr-2"></i>
                            복사
                        </button>
                    </div>
                </div>

                <div class="mt-4 flex gap-3">
                    <a href="payment_detail.php?id=<?= $createdLink['link_id'] ?>" class="btn-primary">
                        <i class="fas fa-eye mr-2"></i>
                        상세 보기
                    </a>
                    <a href="create_payment.php" class="btn-secondary">
                        <i class="fas fa-plus mr-2"></i>
                        새 링크 생성
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <!-- 오류 메시지 -->
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-center">
        <i class="fas fa-exclamation-circle mr-2"></i>
        <?= e($error) ?>
    </div>
    <?php endif; ?>

    <!-- 생성 폼 -->
    <form method="POST" action="" class="bg-white rounded-lg shadow p-6 space-y-6">
        <!-- 결제 정보 -->
        <div class="border-b border-gray-200 pb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-shopping-cart mr-2"></i>
                결제 정보
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- 상품/서비스명 -->
                <div class="md:col-span-2">
                    <label for="goodname" class="block text-sm font-medium text-gray-700 mb-2">
                        상품/서비스명 <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="goodname" name="goodname" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                           placeholder="예: 웹사이트 제작비"
                           value="<?= e($_POST['goodname'] ?? '') ?>">
                </div>

                <!-- 결제 분류 -->
                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700 mb-2">
                        결제 분류
                    </label>
                    <select id="category" name="category"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <option value="">선택 안 함</option>
                        <option value="서비스제공비">서비스제공비</option>
                        <option value="용역비">용역비</option>
                        <option value="컨설팅비">컨설팅비</option>
                        <option value="상품판매">상품판매</option>
                        <option value="기타">기타</option>
                    </select>
                </div>

                <!-- 결제 금액 -->
                <div>
                    <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">
                        결제 금액 (원) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" id="amount" name="amount" required min="1000"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                           placeholder="10000"
                           value="<?= e($_POST['amount'] ?? '') ?>">
                </div>

                <!-- 설명 -->
                <div class="md:col-span-2">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                        설명 (선택)
                    </label>
                    <textarea id="description" name="description" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                              placeholder="결제에 대한 추가 설명을 입력하세요"><?= e($_POST['description'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- 결제자 정보 -->
        <div class="border-b border-gray-200 pb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-user mr-2"></i>
                결제자 정보 (선택)
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="buyer_name" class="block text-sm font-medium text-gray-700 mb-2">
                        이름
                    </label>
                    <input type="text" id="buyer_name" name="buyer_name"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                           placeholder="홍길동"
                           value="<?= e($_POST['buyer_name'] ?? '') ?>">
                </div>

                <div>
                    <label for="buyer_phone" class="block text-sm font-medium text-gray-700 mb-2">
                        전화번호
                    </label>
                    <input type="tel" id="buyer_phone" name="buyer_phone"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                           placeholder="010-1234-5678"
                           value="<?= e($_POST['buyer_phone'] ?? '') ?>">
                </div>

                <div>
                    <label for="buyer_email" class="block text-sm font-medium text-gray-700 mb-2">
                        이메일
                    </label>
                    <input type="email" id="buyer_email" name="buyer_email"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                           placeholder="example@email.com"
                           value="<?= e($_POST['buyer_email'] ?? '') ?>">
                </div>
            </div>
        </div>

        <!-- 알림 설정 -->
        <div class="border-b border-gray-200 pb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-bell mr-2"></i>
                알림 발송
            </h2>

            <div class="space-y-3">
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" name="send_sms" value="1" class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                    <span class="ml-3 text-sm text-gray-700">
                        <i class="fas fa-sms mr-1"></i>
                        SMS 발송 (결제자 전화번호 필요)
                    </span>
                </label>

                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" name="send_email" value="1" class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                    <span class="ml-3 text-sm text-gray-700">
                        <i class="fas fa-envelope mr-1"></i>
                        이메일 발송 (결제자 이메일 필요)
                    </span>
                </label>
            </div>
        </div>

        <!-- 추가 설정 -->
        <div class="pb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
                <i class="fas fa-cog mr-2"></i>
                추가 설정
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="expire_days" class="block text-sm font-medium text-gray-700 mb-2">
                        링크 유효기간 (일)
                    </label>
                    <input type="number" id="expire_days" name="expire_days" min="1" max="365"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                           value="<?= PAYMENT_LINK_EXPIRE_DAYS ?>">
                    <p class="text-xs text-gray-500 mt-1">0 입력 시 무제한</p>
                </div>

                <div class="md:col-span-2">
                    <label for="memo" class="block text-sm font-medium text-gray-700 mb-2">
                        관리자 메모 (내부용)
                    </label>
                    <textarea id="memo" name="memo" rows="2"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                              placeholder="관리자만 볼 수 있는 메모"><?= e($_POST['memo'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- 버튼 -->
        <div class="flex justify-end gap-3">
            <a href="dashboard.php" class="btn-secondary">
                <i class="fas fa-times mr-2"></i>
                취소
            </a>
            <button type="submit" class="btn-primary">
                <i class="fas fa-check mr-2"></i>
                결제 링크 생성
            </button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/components/footer.php'; ?>
