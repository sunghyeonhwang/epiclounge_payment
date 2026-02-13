<?php
/**
 * 관리자 페이지 사이드바
 */

$currentPage = basename($_SERVER['PHP_SELF']);

$menuItems = [
    [
        'icon' => 'fas fa-chart-line',
        'title' => '대시보드',
        'page' => 'dashboard.php'
    ],
    [
        'icon' => 'fas fa-plus-circle',
        'title' => '결제 링크 생성',
        'page' => 'create_payment.php'
    ],
    [
        'icon' => 'fas fa-list',
        'title' => '결제 내역',
        'page' => 'payment_list.php'
    ]
];
?>

<!-- 사이드바 -->
<aside class="lg:w-64 flex-shrink-0">
    <div class="bg-white rounded-lg shadow-sm p-4">
        <nav class="space-y-1">
            <?php foreach ($menuItems as $item): ?>
                <a href="<?= $item['page'] ?>"
                   class="nav-link <?= ($currentPage === $item['page']) ? 'active' : '' ?>">
                    <i class="<?= $item['icon'] ?> mr-3"></i>
                    <?= $item['title'] ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>
</aside>

<!-- 메인 콘텐츠 -->
<main class="flex-1">
