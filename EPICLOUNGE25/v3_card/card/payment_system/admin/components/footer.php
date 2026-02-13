<?php
/**
 * 관리자 페이지 공통 푸터
 */
?>
        </main>
    </div>
</div>

<footer class="mt-12 py-6 text-center text-sm text-gray-500">
    <p>&copy; <?= date('Y') ?> <?= SITE_NAME ?>. All rights reserved.</p>
</footer>

<script>
// 전역 헬퍼 함수
function copyToClipboard(text, message = '복사되었습니다') {
    navigator.clipboard.writeText(text).then(() => {
        alert(message);
    }).catch(err => {
        console.error('복사 실패:', err);
        alert('복사에 실패했습니다.');
    });
}

// 확인 대화상자
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// 금액 포맷
function formatCurrency(amount) {
    return '₩' + Number(amount).toLocaleString('ko-KR');
}
</script>

</body>
</html>
