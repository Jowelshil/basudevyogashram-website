<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
date_default_timezone_set('Asia/Dhaka');

/* ---------- storage ----------
   Data lives OUTSIDE public_html so Git deployments never overwrite it.
   Fallback: public_html/storage (blocked by .htaccess). */
function data_dir(): string {
    static $dir = null;
    if ($dir !== null) return $dir;
    $env = getenv('BYA_DATA_DIR');
    $candidates = [];
    if ($env) $candidates[] = $env;
    $candidates[] = dirname(__DIR__, 2) . '/ashram-data';
    $candidates[] = dirname(__DIR__) . '/storage';
    foreach ($candidates as $c) {
        if (is_dir($c) && is_writable($c)) { $dir = $c; break; }
        $parent = dirname($c);
        if (!is_dir($c) && is_dir($parent) && is_writable($parent) && @mkdir($c, 0750, true)) { $dir = $c; break; }
    }
    if ($dir === null) { http_response_code(500); exit('Storage folder is not writable.'); }
    foreach (['uploads', 'backups'] as $sub) if (!is_dir("$dir/$sub")) @mkdir("$dir/$sub", 0750, true);
    if (!file_exists("$dir/.htaccess")) @file_put_contents("$dir/.htaccess", "Require all denied\n");
    return $dir;
}

const COLLECTIONS = ['events', 'gallery', 'donors', 'posts', 'settings'];

function load(string $name) {
    $file = data_dir() . "/$name.json";
    if (!file_exists($file)) {
        $seed = dirname(__DIR__) . "/seed/$name.json";
        $data = file_exists($seed) ? json_decode((string)file_get_contents($seed), true) : [];
        save($name, $data ?? [], false);
        return $data ?? [];
    }
    $fh = fopen($file, 'rb');
    flock($fh, LOCK_SH);
    $raw = stream_get_contents($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
    $data = json_decode((string)$raw, true);
    return is_array($data) ? $data : [];
}

function save(string $name, $data, bool $backup = true): void {
    $dir = data_dir();
    $file = "$dir/$name.json";
    if ($backup && file_exists($file)) {
        @copy($file, "$dir/backups/$name-" . date('Ymd-His') . '.json');
        $olds = glob("$dir/backups/$name-*.json") ?: [];
        sort($olds);
        while (count($olds) > 30) @unlink(array_shift($olds));
    }
    $tmp = $file . '.tmp' . bin2hex(random_bytes(4));
    file_put_contents($tmp, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
    rename($tmp, $file);
}

function new_id(): string { return bin2hex(random_bytes(5)); }

/* ---------- output helpers ---------- */
function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function img_url(string $ref): string {
    if (str_starts_with($ref, 'u:')) return 'media.php?f=' . rawurlencode(substr($ref, 2));
    return $ref;
}

function bn_digits(string $s): string {
    return strtr($s, ['0'=>'০','1'=>'১','2'=>'২','3'=>'৩','4'=>'৪','5'=>'৫','6'=>'৬','7'=>'৭','8'=>'৮','9'=>'৯']);
}
function bn_date(string $iso): string {
    $m = ['জানুয়ারি','ফেব্রুয়ারি','মার্চ','এপ্রিল','মে','জুন','জুলাই','আগস্ট','সেপ্টেম্বর','অক্টোবর','নভেম্বর','ডিসেম্বর'];
    $t = strtotime($iso);
    if (!$t) return '';
    return bn_digits(date('j', $t)) . ' ' . $m[(int)date('n', $t) - 1] . ' ' . bn_digits(date('Y', $t));
}
function bn_weekday(string $iso): string {
    $w = ['রবিবার','সোমবার','মঙ্গলবার','বুধবার','বৃহস্পতিবার','শুক্রবার','শনিবার'];
    $t = strtotime($iso);
    return $t ? $w[(int)date('w', $t)] : '';
}
function paragraphs(string $text): string {
    $out = '';
    foreach (preg_split("/\R{2,}/u", trim($text)) as $p) {
        if (trim($p) === '') continue;
        $out .= '<p>' . nl2br(e(trim($p))) . "</p>\n";
    }
    return $out;
}
function excerpt(string $text, int $len = 160): string {
    $t = preg_replace('/\s+/u', ' ', trim($text));
    return mb_strlen($t) > $len ? mb_substr($t, 0, $len) . '…' : $t;
}
function valid_date(string $d): bool {
    return (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && checkdate((int)substr($d,5,2), (int)substr($d,8,2), (int)substr($d,0,4));
}

/* ---------- image upload ---------- */
function handle_upload(string $field, int $maxW = 1600): ?string {
    if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('ছবি আপলোড হয়নি। ফাইলটি হয়তো খুব বড় (সর্বোচ্চ ১০ MB)।');
    if ($f['size'] > 10 * 1024 * 1024) throw new RuntimeException('ছবিটি ১০ MB-এর বেশি বড়। ছোট ছবি দিন।');
    $info = @getimagesize($f['tmp_name']);
    if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF], true))
        throw new RuntimeException('শুধু JPG, PNG বা WEBP ছবি দেওয়া যাবে।');
    $name = date('Ymd') . '-' . bin2hex(random_bytes(6)) . '.jpg';
    $dest = data_dir() . '/uploads/' . $name;
    if (function_exists('imagecreatefromstring')) {
        $src = @imagecreatefromstring((string)file_get_contents($f['tmp_name']));
        if (!$src) throw new RuntimeException('ছবিটি পড়া যাচ্ছে না। অন্য ছবি দিন।');
        if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $ex = @exif_read_data($f['tmp_name']);
            $o = $ex['Orientation'] ?? 1;
            if ($o == 3) $src = imagerotate($src, 180, 0);
            elseif ($o == 6) $src = imagerotate($src, -90, 0);
            elseif ($o == 8) $src = imagerotate($src, 90, 0);
        }
        $w = imagesx($src); $h = imagesy($src);
        if ($w > $maxW) { $nh = (int)round($h * $maxW / $w); $dst = imagecreatetruecolor($maxW, $nh); }
        else { $dst = imagecreatetruecolor($w, $h); $nh = $h; }
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
        imagecopyresampled($dst, $src, 0, 0, 0, 0, imagesx($dst), $nh, $w, $h);
        imagejpeg($dst, $dest, 84);
        imagedestroy($src); imagedestroy($dst);
    } else {
        if ($info[2] !== IMAGETYPE_JPEG) throw new RuntimeException('এই সার্ভারে শুধু JPG ছবি দেওয়া যাবে।');
        move_uploaded_file($f['tmp_name'], $dest);
    }
    return 'u:' . $name;
}

function delete_upload(?string $ref): void {
    if ($ref && str_starts_with($ref, 'u:')) {
        $n = basename(substr($ref, 2));
        if (preg_match('/^[\w.-]+\.jpg$/', $n)) @unlink(data_dir() . '/uploads/' . $n);
    }
}

/* ---------- auth ---------- */
function start_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_name('bya_admin');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $secure, 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
    if (isset($_SESSION['uid'], $_SESSION['last']) && time() - $_SESSION['last'] > 4 * 3600) { session_unset(); }
    $_SESSION['last'] = time();
}
function users(): array { $u = load_users(); return $u; }
function load_users(): array {
    $file = data_dir() . '/users.json';
    return file_exists($file) ? (json_decode((string)file_get_contents($file), true) ?: []) : [];
}
function save_users(array $u): void { save('users', array_values($u)); }
function current_user(): ?array {
    if (empty($_SESSION['uid'])) return null;
    foreach (load_users() as $u) if ($u['id'] === $_SESSION['uid']) return $u;
    return null;
}
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function check_csrf(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) {
        http_response_code(400); exit('নিরাপত্তা যাচাই ব্যর্থ হয়েছে। পাতাটি রিফ্রেশ করে আবার চেষ্টা করুন।');
    }
}

function throttle_key(string $user): string { return hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . '|' . mb_strtolower($user)); }
function login_blocked(string $user): int {
    $f = data_dir() . '/attempts.json';
    $a = file_exists($f) ? (json_decode((string)file_get_contents($f), true) ?: []) : [];
    $k = throttle_key($user);
    if (!empty($a[$k]['until']) && $a[$k]['until'] > time()) return $a[$k]['until'] - time();
    return 0;
}
function login_failed(string $user): void {
    $f = data_dir() . '/attempts.json';
    $a = file_exists($f) ? (json_decode((string)file_get_contents($f), true) ?: []) : [];
    foreach ($a as $k => $v) if (($v['t'] ?? 0) < time() - 86400) unset($a[$k]);
    $k = throttle_key($user);
    $a[$k]['n'] = ($a[$k]['n'] ?? 0) + 1; $a[$k]['t'] = time();
    if ($a[$k]['n'] >= 5) { $a[$k]['until'] = time() + 15 * 60; $a[$k]['n'] = 0; }
    file_put_contents($f, json_encode($a), LOCK_EX);
}
function login_ok(string $user): void {
    $f = data_dir() . '/attempts.json';
    $a = file_exists($f) ? (json_decode((string)file_get_contents($f), true) ?: []) : [];
    unset($a[throttle_key($user)]);
    file_put_contents($f, json_encode($a), LOCK_EX);
}

function log_action(string $what): void {
    $u = current_user();
    $f = data_dir() . '/activity.json';
    $a = file_exists($f) ? (json_decode((string)file_get_contents($f), true) ?: []) : [];
    array_unshift($a, ['t' => date('Y-m-d H:i'), 'who' => $u['name'] ?? '?', 'what' => $what]);
    file_put_contents($f, json_encode(array_slice($a, 0, 300), JSON_UNESCAPED_UNICODE), LOCK_EX);
}
