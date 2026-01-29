<?php
/**
 * Vimeo 영상의 실제 duration을 확인하는 디버그 페이지
 * https://mendixconnect.com/api/debug-durations.php 로 접속
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Vimeo 영상 Duration 확인</h1>";
echo "<p>각 강의의 Vimeo ID로 iframe을 로드하여 실제 duration을 확인합니다.</p>";
echo "<hr>";

$videos = [
    ['id' => 1, 'title' => '오리엔테이션', 'vimeo_id' => '1159124594', 'hash' => '56268871e0', 'db_duration' => 302],
    ['id' => 2, 'title' => '바이브 코딩 도구 둘러보기', 'vimeo_id' => '1159124902', 'hash' => 'b5177d58c3', 'db_duration' => 2457],
    ['id' => 3, 'title' => '바이브 코딩 Skill Up 1', 'vimeo_id' => '1159132372', 'hash' => 'bed85ad5da', 'db_duration' => 2020],
    ['id' => 4, 'title' => '바이브 코딩 Skill Up 2', 'vimeo_id' => '1159132630', 'hash' => '5dd83ee636', 'db_duration' => 1968],
    ['id' => 5, 'title' => '사이트 실전 개발 1', 'vimeo_id' => '1159140854', 'hash' => '05d6e69779', 'db_duration' => 3930],
    ['id' => 6, 'title' => '사이트 실전 개발 2', 'vimeo_id' => '1159147063', 'hash' => '789524a566', 'db_duration' => 4090],
];

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Duration Debug</title>
    <script src="https://player.vimeo.com/api/player.js"></script>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .video-item { margin-bottom: 30px; padding: 15px; border: 1px solid #ddd; border-radius: 8px; }
        .video-item h3 { margin-top: 0; }
        .duration-info { background: #f0f0f0; padding: 10px; border-radius: 4px; margin-top: 10px; }
        .duration-info span { display: block; margin: 5px 0; }
        .error { color: red; }
        .success { color: green; }
        .mismatch { color: orange; font-weight: bold; }
        iframe { width: 640px; height: 360px; margin: 10px 0; }
    </style>
</head>
<body>
    <?php foreach ($videos as $video): ?>
        <div class="video-item">
            <h3><?php echo $video['id']; ?>. <?php echo htmlspecialchars($video['title']); ?></h3>
            
            <iframe 
                id="player-<?php echo $video['id']; ?>"
                src="https://player.vimeo.com/video/<?php echo $video['vimeo_id']; ?>?h=<?php echo $video['hash']; ?>&title=0&byline=0&portrait=0"
                frameborder="0"
                allow="autoplay; fullscreen"
            ></iframe>
            
            <div class="duration-info">
                <span><strong>Vimeo ID:</strong> <?php echo $video['vimeo_id']; ?></span>
                <span><strong>DB Duration:</strong> <span id="db-<?php echo $video['id']; ?>"><?php echo $video['db_duration']; ?>초 (<?php echo gmdate('H:i:s', $video['db_duration']); ?>)</span></span>
                <span><strong>실제 Duration:</strong> <span id="real-<?php echo $video['id']; ?>">로딩 중...</span></span>
                <span id="compare-<?php echo $video['id']; ?>"></span>
            </div>
        </div>
    <?php endforeach; ?>
    
    <script>
        const videos = <?php echo json_encode($videos); ?>;
        
        videos.forEach(video => {
            const iframe = document.getElementById(`player-${video.id}`);
            const player = new Vimeo.Player(iframe);
            
            player.ready().then(() => {
                return player.getDuration();
            }).then(duration => {
                const realDuration = Math.round(duration);
                const dbDuration = video.db_duration;
                const diff = Math.abs(realDuration - dbDuration);
                
                const realEl = document.getElementById(`real-${video.id}`);
                const compareEl = document.getElementById(`compare-${video.id}`);
                
                const hours = Math.floor(realDuration / 3600);
                const minutes = Math.floor((realDuration % 3600) / 60);
                const seconds = realDuration % 60;
                const formatted = hours > 0 
                    ? `${hours}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`
                    : `${minutes}:${String(seconds).padStart(2, '0')}`;
                
                realEl.innerHTML = `${realDuration}초 (${formatted})`;
                
                if (diff === 0) {
                    compareEl.innerHTML = '<span class="success">✅ 일치</span>';
                } else {
                    compareEl.innerHTML = `<span class="mismatch">⚠️ ${diff}초 차이 - UPDATE 필요!</span>`;
                    console.log(`UPDATE lectures SET duration = ${realDuration} WHERE id = ${video.id};`);
                }
            }).catch(error => {
                document.getElementById(`real-${video.id}`).innerHTML = 
                    `<span class="error">❌ 로드 실패: ${error.message}</span>`;
            });
        });
    </script>
    
    <hr>
    <h2>SQL UPDATE 명령어</h2>
    <p>브라우저 콘솔(F12)을 열어서 필요한 UPDATE 명령어를 확인하세요.</p>
</body>
</html>
