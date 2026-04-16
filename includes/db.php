<?php
/**
 * Kamyon Garajı - Veritabanı Bağlantısı (PDO)
 */

if (!defined('DB_HOST')) {
    require_once __DIR__ . '/../config.php';
}

class Database {
    private static ?PDO $instance = null;

    public static function getInstance(): PDO {
        if (self::$instance === null) {
            try {
                $dsn = sprintf(
                    'mysql:host=%s;dbname=%s;charset=%s',
                    DB_HOST,
                    DB_NAME,
                    DB_CHARSET
                );
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci, time_zone = '+03:00'"
                ];
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                if (DEBUG_MODE) {
                    die('DB Bağlantı Hatası: ' . $e->getMessage());
                }
                error_log('DB Error: ' . $e->getMessage());
                die('Veritabanı bağlantısı kurulamadı.');
            }
        }
        return self::$instance;
    }

    private function __construct() {}
    private function __clone() {}
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

/**
 * Kısayol fonksiyonu
 */
function db(): PDO {
    return Database::getInstance();
}

/**
 * Güvenli query çalıştırma
 */
function db_query(string $sql, array $params = []): PDOStatement {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/**
 * Tek satır getir
 */
function db_fetch(string $sql, array $params = []): array|false {
    return db_query($sql, $params)->fetch();
}

/**
 * Tüm satırları getir
 */
function db_fetch_all(string $sql, array $params = []): array {
    return db_query($sql, $params)->fetchAll();
}

/**
 * INSERT ve son ID'yi döndür
 */
function db_insert(string $table, array $data): int {
    $columns = array_keys($data);
    $placeholders = array_map(fn($c) => ':' . $c, $columns);
    $sql = sprintf(
        'INSERT INTO `%s` (`%s`) VALUES (%s)',
        $table,
        implode('`, `', $columns),
        implode(', ', $placeholders)
    );
    db_query($sql, $data);
    return (int)db()->lastInsertId();
}

/**
 * UPDATE
 */
function db_update(string $table, array $data, string $where, array $whereParams = []): int {
    $sets = array_map(fn($c) => "`$c` = :$c", array_keys($data));
    $sql = sprintf(
        'UPDATE `%s` SET %s WHERE %s',
        $table,
        implode(', ', $sets),
        $where
    );
    $stmt = db()->prepare($sql);
    $stmt->execute(array_merge($data, $whereParams));
    return $stmt->rowCount();
}

/**
 * DELETE
 */
function db_delete(string $table, string $where, array $params = []): int {
    $sql = sprintf('DELETE FROM `%s` WHERE %s', $table, $where);
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}

/**
 * Kayıt sayısı
 */
function db_count(string $table, string $where = '1', array $params = []): int {
    $sql = sprintf('SELECT COUNT(*) FROM `%s` WHERE %s', $table, $where);
    return (int)db_query($sql, $params)->fetchColumn();
}

/**
 * Ayar getir
 */
function ayar(string $anahtar, $default = null) {
    static $ayarlar = null;
    if ($ayarlar === null) {
        $ayarlar = [];
        try {
            $rows = db_fetch_all("SELECT anahtar, deger FROM kg_ayarlar");
            foreach ($rows as $r) {
                $ayarlar[$r['anahtar']] = $r['deger'];
            }
        } catch (Exception $e) {
            // Ayarlar tablosu yok, sessizce devam et
        }
    }
    return $ayarlar[$anahtar] ?? $default;
}

/**
 * Ayar kaydet
 */
function ayar_kaydet(string $anahtar, string $deger): bool {
    $sql = "INSERT INTO kg_ayarlar (anahtar, deger) VALUES (:a, :d)
            ON DUPLICATE KEY UPDATE deger = :d2";
    try {
        db_query($sql, ['a' => $anahtar, 'd' => $deger, 'd2' => $deger]);
        return true;
    } catch (Exception $e) {
        return false;
    }
}
