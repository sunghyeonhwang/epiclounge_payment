-- ============================================================
-- Vimeo 실제 duration으로 업데이트
-- phpMyAdmin에서 실행하세요
-- ============================================================

-- 1. 개별 강의 duration 업데이트
UPDATE lectures SET duration = 303 WHERE id = 1;   -- 오리엔테이션 (302 → 303)
UPDATE lectures SET duration = 2456 WHERE id = 2;  -- 바이브 코딩 도구 둘러보기 (2457 → 2456)
UPDATE lectures SET duration = 3942 WHERE id = 5;  -- 사이트 실전 개발 1 (3930 → 3942)
UPDATE lectures SET duration = 4099 WHERE id = 6;  -- 사이트 실전 개발 2 (4090 → 4099)

-- 2. 코스 total_duration 재계산 및 업데이트
UPDATE courses c
SET total_duration = (
    SELECT COALESCE(SUM(lec.duration), 0)
    FROM lectures lec
    JOIN lessons les ON lec.lesson_id = les.id
    WHERE les.course_id = c.id
),
updated_at = CURRENT_TIMESTAMP
WHERE id = 1;

-- 3. 확인용 쿼리 (실행 후 결과 확인)
SELECT 
    id,
    title,
    duration,
    SEC_TO_TIME(duration) as formatted_duration
FROM lectures
ORDER BY id;

-- 4. 전체 duration 확인
SELECT 
    id,
    title,
    total_duration,
    SEC_TO_TIME(total_duration) as formatted_total
FROM courses
WHERE id = 1;

-- ============================================================
-- 예상 결과:
-- 총 강의 시간: 14,763초 = 4시간 6분 3초
-- (303 + 2456 + 2020 + 1968 + 3942 + 4099 = 14,788초)
-- ============================================================
