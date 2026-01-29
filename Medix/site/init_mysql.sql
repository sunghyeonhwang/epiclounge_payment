-- ============================================================
-- JB Lessons - 학습 진도 데이터 저장을 위한 DB 스키마
-- MySQL/MariaDB v5.7+
-- ============================================================
-- 
-- 사용 방법:
-- 1. phpMyAdmin 왼쪽에서 사용할 데이터베이스를 클릭하여 선택
-- 2. SQL 탭을 클릭
-- 3. 이 파일의 내용을 붙여넣고 실행
-- ============================================================

START TRANSACTION;

-- ============================================================
-- 1. users: 사용자 정보
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    email           VARCHAR(255) NOT NULL,
    display_name    VARCHAR(100) NOT NULL,
    password_hash   VARCHAR(255) NOT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_email (email),
    KEY idx_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. courses: 코스(레슨) 목록
--    예: 레슨 1 - 바이브 코딩 (6개 강의), 레슨 2 - Maia (준비중)
-- ============================================================
CREATE TABLE IF NOT EXISTS courses (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    title           VARCHAR(255) NOT NULL,
    description     TEXT,
    instructor_name VARCHAR(100),
    thumbnail_url   VARCHAR(512),
    total_duration  INT NOT NULL DEFAULT 0,
    status          VARCHAR(20) NOT NULL DEFAULT 'published',
    sort_order      INT NOT NULL DEFAULT 0,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. lessons: 코스 내 섹션(레슨/섹션) 목록
--    예: 섹션 1 - 바이브 코딩 기초
-- ============================================================
CREATE TABLE IF NOT EXISTS lessons (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    course_id       INT NOT NULL,
    title           VARCHAR(255) NOT NULL,
    description     TEXT,
    sort_order      INT NOT NULL DEFAULT 0,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_lessons_course_id (course_id),
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. lectures: 개별 강의(영상) 목록
--    예: 오리엔테이션 (5:00), 바이브 코딩 도구 둘러보기 (40:57) 등
-- ============================================================
CREATE TABLE IF NOT EXISTS lectures (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    lesson_id       INT NOT NULL,
    title           VARCHAR(255) NOT NULL,
    description     TEXT,
    vimeo_id        VARCHAR(50),
    vimeo_hash      VARCHAR(50),
    duration        INT NOT NULL DEFAULT 0,
    sort_order      INT NOT NULL DEFAULT 0,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_lectures_lesson_id (lesson_id),
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. user_lecture_progress: 사용자별 강의 시청 진도
--    - last_position: 마지막 시청 위치 (초 단위 타임스탬프)
--    - completed: 시청 완료 여부
-- ============================================================
CREATE TABLE IF NOT EXISTS user_lecture_progress (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    lecture_id      INT NOT NULL,
    last_position   INT NOT NULL DEFAULT 0,
    completed       TINYINT(1) NOT NULL DEFAULT 0,
    completed_at    TIMESTAMP NULL DEFAULT NULL,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_lecture (user_id, lecture_id),
    KEY idx_ulp_user_id (user_id),
    KEY idx_ulp_lecture_id (lecture_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (lecture_id) REFERENCES lectures(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. user_course_progress: 사용자별 코스 전체 진행률
--    - 코스별 전체 진행률을 캐싱하여 빠른 조회 지원
-- ============================================================
CREATE TABLE IF NOT EXISTS user_course_progress (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    user_id             INT NOT NULL,
    course_id           INT NOT NULL,
    completed_lectures  INT NOT NULL DEFAULT 0,
    total_lectures      INT NOT NULL DEFAULT 0,
    progress_pct        DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    last_accessed_at    TIMESTAMP NULL DEFAULT NULL,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_course (user_id, course_id),
    KEY idx_ucp_user_id (user_id),
    KEY idx_ucp_course_id (course_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 7. 초기 시드 데이터: 바이브 코딩 코스
-- ============================================================

-- 코스 등록
INSERT IGNORE INTO courses (id, title, description, instructor_name, total_duration, status, sort_order)
VALUES
    (1, '바이브 코딩 완벽 가이드: 기초부터 실전 프로젝트까지', '바이브 코딩으로 배우는 실전 개발 가이드', '김재범', 14675, 'published', 1);

-- 섹션 등록
INSERT IGNORE INTO lessons (id, course_id, title, description, sort_order)
VALUES
    (1, 1, '바이브 코딩 기초', '6개 강의 · 4시간 6분', 1);

-- 강의 등록 (6개 강의 - course-data.json 기반)
INSERT IGNORE INTO lectures (id, lesson_id, title, vimeo_id, vimeo_hash, duration, sort_order)
VALUES
    (1, 1, '오리엔테이션', '1159124594', '56268871e0', 302, 1),
    (2, 1, '바이브 코딩 도구 둘러보기', '1159124902', 'b5177d58c3', 2457, 2),
    (3, 1, '바이브 코딩 Skill Up 1', '1159132372', 'bed85ad5da', 2020, 3),
    (4, 1, '바이브 코딩 Skill Up 2', '1159132630', '5dd83ee636', 1968, 4),
    (5, 1, '사이트 실전 개발 1', '1159140854', '05d6e69779', 3930, 5),
    (6, 1, '사이트 실전 개발 2', '1159147063', '789524a566', 4090, 6);

-- 코스 total_duration 업데이트 (모든 강의 시간 합계)
UPDATE courses c
SET total_duration = (
    SELECT COALESCE(SUM(lec.duration), 0)
    FROM lectures lec
    JOIN lessons les ON lec.lesson_id = les.id
    WHERE les.course_id = c.id
),
updated_at = CURRENT_TIMESTAMP
WHERE id = 1;

-- AUTO_INCREMENT 값 조정 (시드 데이터 이후 충돌 방지)
ALTER TABLE courses AUTO_INCREMENT = 10;
ALTER TABLE lessons AUTO_INCREMENT = 10;
ALTER TABLE lectures AUTO_INCREMENT = 10;

COMMIT;

-- ============================================================
-- 스키마 생성 완료
-- ============================================================
-- 다음 단계:
-- 1. config.php에 DB 접속 정보 입력
-- 2. 이 파일을 MySQL/MariaDB에서 실행
-- 3. 테이블 생성 및 초기 데이터 확인
-- ============================================================
