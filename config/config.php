<?php
declare(strict_types=1);

// Keep sessions inside the project so the app does not depend on the
// permissions of XAMPP's global temporary directory.
$sessionPath = getenv('SESSION_SAVE_PATH');
if ($sessionPath === false || $sessionPath === '') {
    $sessionPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.runtime' . DIRECTORY_SEPARATOR . 'sessions';
}
if (!is_dir($sessionPath)) {
    if (!mkdir($sessionPath, 0775, true) && !is_dir($sessionPath)) {
        throw new RuntimeException('Unable to create the PHP session directory.');
    }
}
if (!is_writable($sessionPath) || session_save_path($sessionPath) === false) {
    throw new RuntimeException('The PHP session directory is not writable.');
}
session_start();

$dbHost = getenv('DB_HOST');
$dbName = getenv('DB_NAME');
$dbUser = getenv('DB_USER');
$dbPass = getenv('DB_PASS');
$dbPort = getenv('DB_PORT');
define('DB_HOST', $dbHost === false || $dbHost === '' ? '127.0.0.1' : $dbHost);
define('DB_NAME', $dbName === false || $dbName === '' ? 'ifab_auction' : $dbName);
define('DB_USER', $dbUser === false || $dbUser === '' ? 'root' : $dbUser);
define('DB_PASS', $dbPass === false ? '' : $dbPass);
define('DB_PORT', $dbPort === false || $dbPort === '' ? 3306 : (int)$dbPort);
define('APP_NAME', 'IFAB Flower Exchange');
$baseUrl = getenv('APP_BASE_URL');
if ($baseUrl === false) {
    $host = strtolower((string)parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST));
    if ($host !== '' && !in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
        $baseUrl = '';
    } else {
        $baseUrl = ($_SERVER['SERVER_PORT'] ?? '') === '8000' ? '' : '/IFAB-Flower-Auction';
    }
}
define('BASE_URL', rtrim($baseUrl, '/'));

function db(): PDO {
    static $pdo = null;
    static $schemaReady = false;
    if ($pdo instanceof PDO) return $pdo;
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);

    // Existing installations predate seller-owned listings. Add the nullable
    // owner column once so older databases can use the order workflow too.
    if (!$schemaReady) {
        $column = $pdo->query("SHOW COLUMNS FROM flowers LIKE 'grower_id'")->fetch();
        if (!$column) {
            $pdo->exec("ALTER TABLE flowers ADD COLUMN grower_id INT NULL AFTER id");
            $pdo->exec("CREATE INDEX idx_flowers_grower_id ON flowers (grower_id)");
        }
        $addressColumn = $pdo->query("SHOW COLUMNS FROM orders LIKE 'shipping_address'")->fetch();
        if (!$addressColumn) {
            $pdo->exec("ALTER TABLE orders ADD COLUMN shipping_address TEXT NULL AFTER total_amount");
        }
        $videoColumn = $pdo->query("SHOW COLUMNS FROM flowers LIKE 'youtube_url'")->fetch();
        if (!$videoColumn) {
            $pdo->exec("ALTER TABLE flowers ADD COLUMN youtube_url VARCHAR(500) NULL AFTER image_url");
        }
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS site_videos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(180) NOT NULL,
                youtube_url VARCHAR(500) NOT NULL,
                description TEXT NULL,
                status ENUM('active','inactive') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )"
        );
        $pdo->exec(
            "INSERT INTO site_videos (title, youtube_url, status)
             SELECT f.name, f.youtube_url, 'active'
             FROM flowers f
             WHERE f.youtube_url IS NOT NULL AND f.youtube_url <> ''
               AND NOT EXISTS (
                   SELECT 1 FROM site_videos sv WHERE sv.youtube_url = f.youtube_url
               )"
        );
        $pdo->exec("ALTER TABLE orders MODIFY status ENUM('pending','confirmed','processing','shipped','out_for_delivery','delivered','cancelled') DEFAULT 'pending'");
        $schemaReady = true;
    }
    return $pdo;
}
function e(?string $value): string { return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); }
function url(string $path = ''): string { return BASE_URL . '/' . ltrim($path, '/'); }
function flower_video_url(?string $videoUrl, string $flowerName): string {
    $videoUrl = trim($videoUrl ?? '');
    if (preg_match('#^https?://(www\.)?(youtube\.com|youtu\.be)/#i', $videoUrl)) {
        return $videoUrl;
    }
    return 'https://www.youtube.com/watch?v=fHKSrHJsvzo';
}
function openai_api_key(): string {
    return trim((string)(getenv('OPENAI_API_KEY') ?: ($_ENV['OPENAI_API_KEY'] ?? '')));
}
function openai_vision_model(): string {
    return trim((string)(getenv('OPENAI_VISION_MODEL') ?: ($_ENV['OPENAI_VISION_MODEL'] ?? 'gpt-4.1-mini')));
}
function youtube_embed_url(string $videoUrl): string {
    $parts = parse_url($videoUrl);
    $host = strtolower($parts['host'] ?? '');
    $path = trim($parts['path'] ?? '', '/');
    $videoId = '';
    if ($host === 'youtu.be') {
        $videoId = explode('/', $path)[0] ?? '';
    } elseif (str_contains($host, 'youtube.com')) {
        parse_str($parts['query'] ?? '', $query);
        $videoId = $query['v'] ?? '';
        if (!$videoId && preg_match('#^(embed|shorts)/([^/]+)#', $path, $matches)) {
            $videoId = $matches[2];
        }
    }
    return preg_match('/^[A-Za-z0-9_-]{6,}$/', (string)$videoId)
        ? 'https://www.youtube.com/embed/' . $videoId . '?rel=0'
        : '';
}
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function verify_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf'] ?? '';
        if (!hash_equals($_SESSION['csrf'] ?? '', $token)) {
            http_response_code(419); exit('Invalid request token. Refresh and try again.');
        }
    }
}
function require_admin(): void {
    if (empty($_SESSION['admin_id'])) { header('Location: ' . url('admin/login.php')); exit; }
}
function flash(string $message, string $type = 'success'): void { $_SESSION['flash'] = [$message, $type]; }
function get_flash(): ?array { $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f; }
