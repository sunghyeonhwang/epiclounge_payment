<?php
/**
 * 데이터베이스 클래스 (PDO 기반)
 *
 * 싱글톤 패턴으로 구현
 */

require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    private $pdo = null;

    /**
     * 생성자 (private)
     */
    private function __construct() {
        try {
            $dsn = sprintf(
                "mysql:host=%s;dbname=%s;charset=%s",
                DB_HOST,
                DB_NAME,
                DB_CHARSET
            );

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];

            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("데이터베이스 연결에 실패했습니다. 관리자에게 문의하세요.");
        }
    }

    /**
     * 싱글톤 인스턴스 반환
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * PDO 연결 객체 반환
     */
    public function getConnection() {
        return $this->pdo;
    }

    /**
     * 쿼리 실행
     *
     * @param string $sql SQL 쿼리
     * @param array $params 바인딩 파라미터
     * @return PDOStatement
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query failed: " . $e->getMessage() . " | SQL: " . $sql);
            throw new Exception("데이터베이스 쿼리 실행 중 오류가 발생했습니다.");
        }
    }

    /**
     * 단일 행 조회
     *
     * @param string $sql SQL 쿼리
     * @param array $params 바인딩 파라미터
     * @return array|false
     */
    public function fetch($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }

    /**
     * 여러 행 조회
     *
     * @param string $sql SQL 쿼리
     * @param array $params 바인딩 파라미터
     * @return array
     */
    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * 마지막 삽입 ID 반환
     */
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }

    /**
     * 트랜잭션 시작
     */
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }

    /**
     * 트랜잭션 커밋
     */
    public function commit() {
        return $this->pdo->commit();
    }

    /**
     * 트랜잭션 롤백
     */
    public function rollback() {
        return $this->pdo->rollBack();
    }

    /**
     * 트랜잭션 상태 확인
     */
    public function inTransaction() {
        return $this->pdo->inTransaction();
    }

    /**
     * INSERT 헬퍼
     *
     * @param string $table 테이블명
     * @param array $data ['column' => 'value', ...]
     * @return int 삽입된 ID
     */
    public function insert($table, $data) {
        $columns = array_keys($data);
        $values = array_values($data);

        $columnList = implode(', ', array_map(function($col) {
            return "`{$col}`";
        }, $columns));

        $placeholders = implode(', ', array_fill(0, count($values), '?'));

        $sql = "INSERT INTO `{$table}` ({$columnList}) VALUES ({$placeholders})";

        $this->query($sql, $values);

        return $this->lastInsertId();
    }

    /**
     * UPDATE 헬퍼
     *
     * @param string $table 테이블명
     * @param array $data ['column' => 'value', ...]
     * @param string $where WHERE 조건 (예: "id = ?")
     * @param array $whereParams WHERE 파라미터
     * @return int 영향받은 행 수
     */
    public function update($table, $data, $where, $whereParams = []) {
        $columns = array_keys($data);
        $values = array_values($data);

        $setClause = implode(', ', array_map(function($col) {
            return "`{$col}` = ?";
        }, $columns));

        $sql = "UPDATE `{$table}` SET {$setClause} WHERE {$where}";

        $params = array_merge($values, $whereParams);

        $stmt = $this->query($sql, $params);

        return $stmt->rowCount();
    }

    /**
     * DELETE 헬퍼
     *
     * @param string $table 테이블명
     * @param string $where WHERE 조건 (예: "id = ?")
     * @param array $whereParams WHERE 파라미터
     * @return int 삭제된 행 수
     */
    public function delete($table, $where, $whereParams = []) {
        $sql = "DELETE FROM `{$table}` WHERE {$where}";

        $stmt = $this->query($sql, $whereParams);

        return $stmt->rowCount();
    }

    /**
     * COUNT 헬퍼
     *
     * @param string $table 테이블명
     * @param string $where WHERE 조건 (선택)
     * @param array $whereParams WHERE 파라미터
     * @return int 행 수
     */
    public function count($table, $where = '1=1', $whereParams = []) {
        $sql = "SELECT COUNT(*) as count FROM `{$table}` WHERE {$where}";

        $result = $this->fetch($sql, $whereParams);

        return (int) $result['count'];
    }

    /**
     * 존재 여부 확인
     *
     * @param string $table 테이블명
     * @param string $where WHERE 조건
     * @param array $whereParams WHERE 파라미터
     * @return bool
     */
    public function exists($table, $where, $whereParams = []) {
        return $this->count($table, $where, $whereParams) > 0;
    }

    /**
     * 클론 방지
     */
    private function __clone() {}

    /**
     * 직렬화 방지
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
