<?php
/**
 * 관리자 페이지 공통 헤더
 */

if (!isset($pageTitle)) {
    $pageTitle = '관리자';
}

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - <?= SITE_NAME ?> 결제 관리</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@400;500;600;700&display=swap');

        body {
            font-family: 'Noto Sans KR', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        .nav-link {
            @apply block px-4 py-3 text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 rounded-md transition-colors;
        }

        .nav-link.active {
            @apply bg-indigo-100 text-indigo-700 font-semibold;
        }

        .btn-primary {
            @apply bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-4 py-2 rounded-md transition-colors;
        }

        .btn-secondary {
            @apply bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium px-4 py-2 rounded-md transition-colors;
        }

        .badge {
            @apply inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium;
        }

        .badge-green { @apply bg-green-100 text-green-800; }
        .badge-yellow { @apply bg-yellow-100 text-yellow-800; }
        .badge-red { @apply bg-red-100 text-red-800; }
        .badge-gray { @apply bg-gray-100 text-gray-800; }
        .badge-blue { @apply bg-blue-100 text-blue-800; }
    </style>
</head>
<body class="bg-gray-50">
    <!-- 상단 네비게이션 -->
    <nav class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="dashboard.php" class="flex items-center">
                        <span class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                            <?= SITE_NAME ?>
                        </span>
                        <span class="ml-2 text-sm text-gray-500">결제 관리</span>
                    </a>
                </div>

                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-600">
                        <i class="fas fa-user-circle mr-1"></i>
                        <strong><?= e($auth->getAdminName()) ?></strong>님
                    </span>
                    <a href="logout.php" class="text-sm text-gray-600 hover:text-gray-900">
                        <i class="fas fa-sign-out-alt mr-1"></i>
                        로그아웃
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col lg:flex-row gap-8">
