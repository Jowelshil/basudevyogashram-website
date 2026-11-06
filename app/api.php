<?php
/* বাসুদেব যোগাশ্রম হিসাব অ্যাপ: JSON API
   Data: <ashram-data>/hisab/*.json  (outside public_html, safe from Git deploys)
   Auth: bearer token (hashed on server). Roles: admin (Gurudev) / user. */
declare(strict_types=1);
require dirname(__DIR__) . '/inc/lib.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

const MAX_USERS = 10;
const TOKEN_DAYS = 30;
const IDLE_SECONDS = 300; // server-side idle limit; the app itself logs out after 3 minutes
const AREAS_DEFAULT = ['চট্টগ্রাম শহর', 'রাঙ্গুনিয়া', 'রাউজান', 'ফটিকছড়ি', 'হাটহাজারী'];
const LISTS = ['monthly' => 'মাসিক প্রণামী', 'land' => 'ভূমিদাতা', 'life' => 'আজীবন সদস্য'];
const KINDS = ['income' => 'অনুদান/আয়', 'daily' => 'দৈনিক খরচ', 'construction' => 'নির্মাণ খরচ'];

/* ---------- storage with locking ---------- */
function hdir(): string {
    $d = data_dir() . '/hisab';
    if (!is_dir($d)) { @mkdir($d, 0750, true); @file_put_contents("$d/.htaccess", "Require all denied\n"); }
    return $d;
}
function hread(string $name, $default = []) {
    $f = hdir() . "/$name.json";
    if (!file_exists($f)) return $default;
    $d = json_decode((string)file_get_contents($f), true);
    return is_array($d) ? $d : $default;
}
function hwrite(string $name, $data): void {
    $f = hdir() . "/$name.json";
    $tmp = $f . '.tmp' . bin2hex(random_bytes(4));
    file_put_contents($tmp, json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX);
    rename($tmp, $f);
}
/* read-modify-write under one exclusive lock so two phones never overwrite each other */
function hmutate(string $name, callable $fn, $default = []) {
    $lock = fopen(hdir() . "/$name.lock", 'c');
    flock($lock, LOCK_EX);
    try {
        $data = hread($name, $default);
        $result = $fn($data);
        hwrite($name, $data);
        daily_backup($name);
        return $result;
    } finally { flock($lock, LOCK_UN); fclose($lock); }
}
function daily_backup(string $name): void {
    $dir = hdir() . '/backups';
    if (!is_dir($dir)) @mkdir($dir, 0750, true);
    $b = "$dir/$name-" . date('Ymd') . '.json';
    if (!file_exists($b)) {
        @copy(hdir() . "/$name.json", $b);
        $olds = glob("$dir/$name-*.json") ?: []; sort($olds);
        while (count($olds) > 90) @unlink(array_shift($olds));
    }
}
function audit(array $u, string $what, array $extra = []): void {
    $line = json_encode(['t' => date('Y-m-d H:i:s'), 'uid' => $u['id'] ?? '', 'who' => $u['name'] ?? '', 'what' => $what] + $extra, JSON_UNESCAPED_UNICODE);
    file_put_contents(hdir() . '/audit.log', $line . "\n", FILE_APPEND | LOCK_EX);
}

/* ---------- helpers ---------- */
function out($data, int $code = 200): never { http_response_code($code); echo json_encode($data, JSON_UNESCAPED_UNICODE); exit; }
function fail(string $msg, int $code = 400): never { out(['ok' => false, 'error' => $msg], $code); }
function str_in(array $in, string $k, int $max = 200): string { return mb_substr(trim((string)($in[$k] ?? '')), 0, $max); }
function norm_mobile(string $v): string {
    $d = preg_replace('/\D/', '', strtr($v, ['০'=>'0','১'=>'1','২'=>'2','৩'=>'3','৪'=>'4','৫'=>'5','৬'=>'6','৭'=>'7','৮'=>'8','৯'=>'9']));
    if (str_starts_with($d, '880')) $d = substr($d, 2); elseif (str_starts_with($d, '88')) $d = substr($d, 2);
    return preg_match('/^01[3-9]\d{8}$/', $d) ? $d : '';
}
function amount_in(array $in, string $k = 'amount', bool $required = true): float {
    $raw = strtr((string)($in[$k] ?? ''), ['০'=>'0','১'=>'1','২'=>'2','৩'=>'3','৪'=>'4','৫'=>'5','৬'=>'6','৭'=>'7','৮'=>'8','৯'=>'9', ','=>'']);
    $a = is_numeric($raw) ? round((float)$raw, 2) : 0.0;
    if ($required && $a <= 0) fail('টাকার পরিমাণ শূন্যের বেশি হতে হবে।');
    if ($a > 100000000) fail('টাকার পরিমাণ ঠিক নেই।');
    return max(0.0, $a);
}
/* area list for মাসিক প্রণামী; "অন্যান্য" is always there and always last */
function areas_list(): array {
    $a = array_values(array_filter(hread('areas', AREAS_DEFAULT), fn($x) => $x !== 'অন্যান্য'));
    $a[] = 'অন্যান্য';
    return $a;
}
/* Banglish land words and digits to Bangla (same rules as the app) */
function land_bn(string $t): string {
    $rules = [
        '/\b(sho|sha|so|sa)t(o|a)?(k|ng?sho|ngsho|nsho|angsh?o)\b/i' => 'শতক', '/\bdecimals?\b|\bdec\b/i' => 'শতক',
        '/\bk(a|o)a?th?a\b|\bkata\b/i' => 'কাঠা', '/\bbig?h?a\b/i' => 'বিঘা', '/\bka+ni\b/i' => 'কানি', '/\bg(o|a)nd(a|o)\b/i' => 'গণ্ডা',
        '/\bk(o|a)ra\b/i' => 'কড়া', '/\b(acre|akor|ekor|okor)s?\b/i' => 'একর', '/\b(o|a)jut(a|o)ng?sho\b/i' => 'অযুতাংশ',
        '/\bm(o|a)u?u?za\b|\bmouja\b/i' => 'মৌজা', '/\bdag\b/i' => 'দাগ', '/\bkh?(a|o)tt?i?y?an\b/i' => 'খতিয়ান', '/\bjomi\b/i' => 'জমি', '/\bch?(o|a)t(o|a)k\b/i' => 'ছটাক',
        '/\bno\.?(?=\s|$)/i' => 'নং', '/\band\b/i' => 'ও'];
    foreach ($rules as $re => $bn) $t = preg_replace($re, $bn, $t);
    return bn_digits($t);
}
function public_user(array $u): array { return ['id' => $u['id'], 'username' => $u['username'], 'name' => $u['name'], 'role' => $u['role'], 'active' => $u['active'] ?? true, 'created' => $u['created'] ?? '']; }

/* ---------- auth ---------- */
function auth(bool $adminOnly = false): array {
    $h = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    $tok = str_starts_with($h, 'Bearer ') ? substr($h, 7) : (string)($_POST['token'] ?? $GLOBALS['IN']['token'] ?? '');
    if ($tok === '') fail('লগইন করুন।', 401);
    $tokens = hread('tokens');
    $t = $tokens[hash('sha256', $tok)] ?? null;
    if (!$t || $t['exp'] < time()) fail('লগইনের মেয়াদ শেষ। আবার লগইন করুন।', 401);
    $key = hash('sha256', $tok);
    if (($t['seen'] ?? 0) < time() - IDLE_SECONDS) {
        hmutate('tokens', function (&$all) use ($key) { unset($all[$key]); });
        fail('কিছুক্ষণ কোনো কাজ না হওয়ায় নিরাপত্তার জন্য লগআউট হয়েছে। আবার লগইন করুন।', 401);
    }
    if ($t['seen'] < time() - 20) hmutate('tokens', function (&$all) use ($key) { if (isset($all[$key])) $all[$key]['seen'] = time(); });
    foreach (hread('users') as $u) {
        if ($u['id'] === $t['uid'] && ($u['active'] ?? true)) {
            if ($adminOnly && $u['role'] !== 'admin') fail('এই কাজটি শুধু অ্যাডমিন (গুরুদেব) করতে পারবেন।', 403);
            return $u;
        }
    }
    fail('এই অ্যাকাউন্ট বন্ধ আছে।', 401);
}
function issue_token(array $u): string {
    $tok = bin2hex(random_bytes(24));
    hmutate('tokens', function (&$t) use ($tok, $u) {
        foreach ($t as $k => $v) if ($v['exp'] < time() || ($v['seen'] ?? 0) < time() - IDLE_SECONDS) unset($t[$k]);
        $t[hash('sha256', $tok)] = ['uid' => $u['id'], 'exp' => time() + TOKEN_DAYS * 86400, 'seen' => time()];
    });
    return $tok;
}
function drop_tokens_of(string $uid): void {
    hmutate('tokens', function (&$t) use ($uid) { foreach ($t as $k => $v) if ($v['uid'] === $uid) unset($t[$k]); });
}

/* ---------- SMS (Robi A2P or any HTTP gateway, configured by admin) ---------- */
function sms_cfg(): array {
    return hread('sms', []) + [
        'enabled' => false, 'method' => 'GET', 'url' => '', 'params' => '', 'success' => '',
        'number_format' => '880', 'auto' => false,
        'template' => 'জয় গুরু। {নাম}, আপনার প্রদত্ত {টাকা} টাকা বাসুদেব যোগাশ্রমে জমা হয়েছে। ক্রমিক {ক্রমিক}, {তারিখ}। ধন্যবাদ।',
    ];
}
function bn_num($n): string { $s = number_format((float)$n, (floor((float)$n) == (float)$n) ? 0 : 2); return bn_digits($s); }
function sms_text(array $r, array $c): string {
    return strtr($c['template'], ['{নাম}' => $r['name'] ?: 'ভক্ত', '{টাকা}' => bn_num($r['amount']), '{তারিখ}' => bn_date($r['date']),
        '{রসিদ}' => $r['ref'] ?: bn_digits((string)$r['serial']), '{ক্রমিক}' => bn_digits((string)$r['serial']),
        '{মাস}' => !empty($r['month']) ? (['জানুয়ারি','ফেব্রুয়ারি','মার্চ','এপ্রিল','মে','জুন','জুলাই','আগস্ট','সেপ্টেম্বর','অক্টোবর','নভেম্বর','ডিসেম্বর'][(int)substr($r['month'], 5, 2) - 1] . ' ' . bn_digits(substr($r['month'], 0, 4))) : bn_date($r['date'])]);
}
function sms_send(string $mobile, string $text, array $c): array {
    if (!$c['enabled'] || $c['url'] === '') return [false, 'SMS সেটিংস চালু করা হয়নি'];
    if (!function_exists('curl_init')) return [false, 'সার্ভারে curl নেই'];
    $to = $c['number_format'] === '880' ? '88' . $mobile : $mobile;
    $fill = fn(string $s, bool $enc) => strtr($s, ['{to}' => $enc ? rawurlencode($to) : $to, '{msg}' => $enc ? rawurlencode($text) : $text]);
    $ch = curl_init();
    $url = $c['url'];
    if (strtoupper($c['method']) === 'GET') {
        $q = $fill($c['params'], true);
        $url .= $q === '' ? '' : ((str_contains($url, '?') ? '&' : '?') . $q);
        $url = $fill($url, true);
    } else {
        $body = trim($c['params']);
        $isJson = str_starts_with($body, '{');
        $body = $isJson ? strtr($body, ['{to}' => $to, '{msg}' => trim(json_encode($text, JSON_UNESCAPED_UNICODE), '"')]) : $fill($body, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [$isJson ? 'Content-Type: application/json' : 'Content-Type: application/x-www-form-urlencoded']);
    }
    curl_setopt_array($ch, [CURLOPT_URL => $url, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20, CURLOPT_CONNECTTIMEOUT => 10]);
    $resp = (string)curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($err) return [false, 'সংযোগ ব্যর্থ: ' . $err];
    $ok = $code >= 200 && $code < 300 && ($c['success'] === '' || stripos($resp, $c['success']) !== false);
    return [$ok, mb_substr("HTTP $code: " . trim(strip_tags($resp)), 0, 200)];
}
/* send all pending receipts for a date; returns [sent, failed] */
function sms_flush(array $u, string $date, ?string $onlyId = null): array {
    $c = sms_cfg();
    if (!$c['enabled']) fail('SMS এখনো চালু হয়নি। অ্যাডমিন সেটিংসে Robi-র তথ্য দিয়ে চালু করুন।');
    $year = substr($date, 0, 4);
    $todo = [];
    foreach (hread("ledger-$year") as $r)
        if ($r['date'] === $date && $r['kind'] === 'income' && $r['mobile'] && empty($r['deleted']) && ($r['sms']['status'] ?? 'pending') !== 'sent' && (!$onlyId || $r['id'] === $onlyId))
            $todo[] = $r;
    $res = [];
    foreach ($todo as $r) { [$ok, $msg] = sms_send($r['mobile'], sms_text($r, $c), $c); $res[$r['id']] = ['status' => $ok ? 'sent' : 'failed', 'at' => date('Y-m-d H:i'), 'info' => $msg]; }
    if ($res) hmutate("ledger-$year", function (&$l) use ($res) { foreach ($l as &$r) if (isset($res[$r['id']])) $r['sms'] = $res[$r['id']]; });
    $sent = count(array_filter($res, fn($x) => $x['status'] === 'sent'));
    audit($u, "SMS পাঠানো $date", ['sent' => $sent, 'failed' => count($res) - $sent]);
    return [$sent, count($res) - $sent];
}

/* ---------- request ---------- */
$IN = json_decode((string)file_get_contents('php://input'), true) ?: $_POST;
$act = (string)($_GET['a'] ?? $IN['a'] ?? '');

switch ($act) {

case 'status':
    out(['ok' => true, 'needsSetup' => count(hread('users')) === 0, 'now' => date('Y-m-d')]);

case 'setup': {
    if (count(hread('users')) > 0) fail('সেটআপ আগেই সম্পন্ন হয়েছে।');
    if ($s = login_blocked('hisab-setup')) fail('বারবার ভুল হয়েছে। ' . bn_digits((string)ceil($s / 60)) . ' মিনিট পর চেষ্টা করুন।', 429);
    if (!password_verify(str_in($IN, 'key'), SETUP_KEY_HASH)) { login_failed('hisab-setup'); fail('সেটআপ কী সঠিক নয়। ওয়েবসাইট সেটআপের সময় যে কী দেওয়া হয়েছিল সেটা দিন।'); }
    $un = mb_strtolower(str_in($IN, 'username', 40)); $nm = str_in($IN, 'name', 80); $pw = (string)($IN['password'] ?? '');
    if (!preg_match('/^[a-z0-9_.]{3,40}$/', $un)) fail('ইউজার আইডি ইংরেজি ছোট হাতের অক্ষর বা সংখ্যায় দিন (৩ অক্ষরের বেশি)।');
    if (mb_strlen($pw) < 8) fail('পাসওয়ার্ড অন্তত ৮ অক্ষর হতে হবে।');
    $u = ['id' => new_id(), 'username' => $un, 'name' => $nm ?: 'গুরুদেব', 'role' => 'admin', 'hash' => password_hash($pw, PASSWORD_DEFAULT), 'active' => true, 'created' => date('Y-m-d')];
    hmutate('users', function (&$us) use ($u) { $us[] = $u; });
    hwrite('areas', AREAS_DEFAULT);
    audit($u, 'অ্যাডমিন অ্যাকাউন্ট তৈরি');
    out(['ok' => true, 'token' => issue_token($u), 'user' => public_user($u)]);
}

case 'login': {
    $un = mb_strtolower(str_in($IN, 'username', 40)); $pw = (string)($IN['password'] ?? '');
    if ($s = login_blocked("hisab:$un")) fail('বারবার ভুল পাসওয়ার্ড দেওয়া হয়েছে। ' . bn_digits((string)ceil($s / 60)) . ' মিনিট পর চেষ্টা করুন।', 429);
    foreach (hread('users') as $u) {
        if ($u['username'] === $un && password_verify($pw, $u['hash'])) {
            if (!($u['active'] ?? true)) fail('এই অ্যাকাউন্ট বন্ধ করা হয়েছে। অ্যাডমিনের সাথে যোগাযোগ করুন।', 403);
            login_ok("hisab:$un");
            audit($u, 'লগইন');
            out(['ok' => true, 'token' => issue_token($u), 'user' => public_user($u)]);
        }
    }
    login_failed("hisab:$un");
    fail('ইউজার আইডি বা পাসওয়ার্ড সঠিক নয়।', 401);
}

case 'logout': {
    $h = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    $tok = str_starts_with($h, 'Bearer ') ? substr($h, 7) : '';
    if ($tok) hmutate('tokens', function (&$t) use ($tok) { unset($t[hash('sha256', $tok)]); });
    out(['ok' => true]);
}

case 'load': {
    $u = auth();
    $isAdmin = $u['role'] === 'admin';
    $y = (int)date('Y');
    $ledger = [];
    if ($isAdmin) {
        foreach (glob(hdir() . '/ledger-*.json') ?: [] as $f) $ledger = array_merge($ledger, hread(basename($f, '.json')));
    } else {
        $from = date('Y-m-d', strtotime('-45 days'));
        foreach (array_unique([$y - 1, $y]) as $yy) foreach (hread("ledger-$yy") as $r) if ($r['date'] >= $from && empty($r['deleted']) && $r['by'] === $u['id']) { unset($r['history']); $ledger[] = $r; }
    }
    $people = hread('people');
    if (!$isAdmin) $people = array_values(array_map(function ($p) { unset($p['history']); return $p; }, array_filter($people, fn($p) => empty($p['deleted']))));
    $names = [];
    foreach (hread('users') as $x) $names[$x['id']] = $x['name'];
    $c = sms_cfg();
    out(['ok' => true, 'user' => public_user($u), 'today' => date('Y-m-d'), 'areas' => areas_list(),
         'ledger' => $ledger, 'people' => $people, 'names' => $names,
         'sms' => ['enabled' => (bool)$c['enabled'], 'auto' => (bool)$c['auto'], 'template' => $c['template']]]);
}

/* ----- ledger (আয়-ব্যয়) ----- */
case 'ledger_add': {
    $u = auth();
    $kind = str_in($IN, 'kind');
    if (!isset(KINDS[$kind])) fail('এন্ট্রির ধরন ঠিক নেই।');
    $date = str_in($IN, 'date', 10);
    if (!valid_date($date)) fail('তারিখ ঠিক নেই।');
    if ($u['role'] !== 'admin' && ($date > date('Y-m-d') || $date < date('Y-m-d', strtotime('-7 days')))) fail('আজ থেকে ৭ দিনের পুরনো বা ভবিষ্যতের তারিখে এন্ট্রি দিতে অ্যাডমিন লাগবে।');
    $mobRaw = str_in($IN, 'mobile', 20);
    $mobile = $kind === 'income' ? norm_mobile($mobRaw) : '';
    if ($kind === 'income' && $mobRaw !== '' && $mobile === '') fail('মোবাইল নম্বর ঠিক নেই। ১১ অঙ্কের নম্বর দিন (01XXXXXXXXX)।');
    $project = $kind === 'construction' ? str_in($IN, 'project', 100) : '';
    if ($kind === 'construction' && $project === '') fail('নির্মাণ খরচের জন্য প্রকল্পের নাম লিখুন।');
    $rec = ['id' => new_id(), 'date' => $date, 'kind' => $kind, 'category' => str_in($IN, 'category', 80), 'amount' => amount_in($IN),
        'name' => str_in($IN, 'name', 100), 'mobile' => $mobile, 'address' => $kind === 'income' ? str_in($IN, 'address', 200) : '',
        'project' => $project, 'ref' => str_in($IN, 'ref', 40), 'mode' => str_in($IN, 'mode', 30), 'note' => str_in($IN, 'note', 300),
        'personId' => str_in($IN, 'personId', 20), 'month' => str_in($IN, 'month', 7),
        'by' => $u['id'], 'createdAt' => date('Y-m-d H:i:s'), 'sms' => ['status' => 'pending']];
    $addLedger = function (array $rec) {
        return hmutate('ledger-' . substr($rec['date'], 0, 4), function (&$l) use ($rec) {
            $max = 0;
            foreach ($l as $r) if ($r['date'] === $rec['date'] && $r['kind'] === $rec['kind']) $max = max($max, (int)$r['serial']);
            $rec['serial'] = $max + 1;
            $l[] = $rec;
            return $rec;
        });
    };
    if ($rec['personId'] !== '') {
        /* প্রণামী for one month of a listed devotee: mark the month paid and add the ledger entry together */
        if ($kind !== 'income' || !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $rec['month'])) fail('মাস ঠিক নেই।');
        $rec = hmutate('people', function (&$p) use (&$rec, $addLedger, $u) {
            foreach ($p as &$x) if ($x['id'] === $rec['personId'] && empty($x['deleted'])) {
                if ($x['list'] !== 'monthly') fail('এই তালিকায় মাসিক জমা নেওয়া হয় না।');
                if (!empty($x['paid'][$rec['month']])) fail('এই মাসের প্রণামী আগেই জমা হয়েছে।');
                $rec['name'] = $rec['name'] ?: $x['name']; $rec['mobile'] = $rec['mobile'] ?: $x['mobile']; $rec['address'] = $rec['address'] ?: $x['address'];
                $saved = $addLedger($rec);
                $x['paid'][$rec['month']] = ['amount' => $saved['amount'], 'date' => $saved['date'], 'entry' => $saved['id'], 'by' => $u['id']];
                return $saved;
            }
            fail('তালিকায় এই নাম পাওয়া যায়নি।', 404);
        });
    } else {
        $rec = $addLedger($rec);
    }
    audit($u, 'এন্ট্রি যোগ', ['id' => $rec['id'], 'amount' => $rec['amount'], 'kind' => $kind]);
    $smsInfo = null;
    if ($kind === 'income' && $mobile && sms_cfg()['auto'] && sms_cfg()['enabled']) { [$s] = sms_flush($u, $date, $rec['id']); $smsInfo = $s ? 'sent' : 'failed'; }
    out(['ok' => true, 'entry' => $rec, 'sms' => $smsInfo]);
}

case 'ledger_update': {
    $u = auth(true);
    $id = str_in($IN, 'id', 20); $year = str_in($IN, 'year', 4);
    if (!preg_match('/^\d{4}$/', $year)) fail('বছর ঠিক নেই।');
    $patch = ['category' => str_in($IN, 'category', 80), 'amount' => amount_in($IN), 'name' => str_in($IN, 'name', 100),
        'address' => str_in($IN, 'address', 200), 'project' => str_in($IN, 'project', 100), 'ref' => str_in($IN, 'ref', 40),
        'mode' => str_in($IN, 'mode', 30), 'note' => str_in($IN, 'note', 300)];
    $mobRaw = str_in($IN, 'mobile', 20);
    $patch['mobile'] = norm_mobile($mobRaw);
    if ($mobRaw !== '' && $patch['mobile'] === '') fail('মোবাইল নম্বর ঠিক নেই।');
    $done = hmutate("ledger-$year", function (&$l) use ($id, $patch, $u) {
        foreach ($l as &$r) if ($r['id'] === $id) {
            $old = $r; unset($old['history']);
            $r['history'][] = $old + ['changedAt' => date('Y-m-d H:i:s'), 'changedBy' => $u['id']];
            $r = array_merge($r, $patch);
            return $r + ['personId' => '', 'month' => ''];
        }
        return false;
    });
    if (!$done) fail('এন্ট্রি পাওয়া যায়নি।', 404);
    if (is_array($done) && $done['personId'] !== '' && $done['month'] !== '')
        hmutate('people', function (&$p) use ($done) { foreach ($p as &$x) if ($x['id'] === $done['personId'] && ($x['paid'][$done['month']]['entry'] ?? '') === $done['id']) $x['paid'][$done['month']]['amount'] = $done['amount']; });
    audit($u, 'এন্ট্রি সম্পাদনা', ['id' => $id]);
    out(['ok' => true]);
}

case 'ledger_delete':
case 'ledger_restore': {
    $u = auth(true);
    $id = str_in($IN, 'id', 20); $year = str_in($IN, 'year', 4); $reason = str_in($IN, 'reason', 200);
    if (!preg_match('/^\d{4}$/', $year)) fail('বছর ঠিক নেই।');
    if ($act === 'ledger_delete' && $reason === '') fail('মুছে ফেলার কারণ লিখুন।');
    $done = hmutate("ledger-$year", function (&$l) use ($id, $act, $reason, $u) {
        foreach ($l as &$r) if ($r['id'] === $id) {
            if ($act === 'ledger_delete') { $r['deleted'] = true; $r['deletedAt'] = date('Y-m-d H:i:s'); $r['deletedBy'] = $u['id']; $r['deleteReason'] = $reason; }
            else unset($r['deleted'], $r['deletedAt'], $r['deletedBy'], $r['deleteReason']);
            return $r + ['personId' => '', 'month' => ''];
        }
        return false;
    });
    if (!$done) fail('এন্ট্রি পাওয়া যায়নি।', 404);
    if (is_array($done) && $done['personId'] !== '' && $done['month'] !== '') {
        hmutate('people', function (&$p) use ($done, $act, $u) {
            foreach ($p as &$x) if ($x['id'] === $done['personId']) {
                if ($act === 'ledger_delete') { if (($x['paid'][$done['month']]['entry'] ?? '') === $done['id']) unset($x['paid'][$done['month']]); }
                elseif (empty($x['paid'][$done['month']])) $x['paid'][$done['month']] = ['amount' => $done['amount'], 'date' => $done['date'], 'entry' => $done['id'], 'by' => $done['by']];
            }
        });
    }
    audit($u, $act === 'ledger_delete' ? 'এন্ট্রি মুছে ফেলা' : 'এন্ট্রি ফিরিয়ে আনা', ['id' => $id, 'reason' => $reason]);
    out(['ok' => true]);
}

/* ----- lists: monthly pranami / land donors / life members ----- */
case 'people_add':
case 'people_update': {
    $u = auth($act === 'people_update');
    $list = str_in($IN, 'list');
    if (!isset(LISTS[$list])) fail('তালিকা ঠিক নেই।');
    $name = str_in($IN, 'name', 100);
    if ($name === '') fail('নাম লিখুন।');
    $mobRaw = str_in($IN, 'mobile', 20); $mobile = norm_mobile($mobRaw);
    if ($mobRaw !== '' && $mobile === '') fail('মোবাইল নম্বর ঠিক নেই। ১১ অঙ্কের নম্বর দিন (01XXXXXXXXX)।');
    $area = str_in($IN, 'area', 60);
    if ($list === 'monthly' && $area === '') fail('এলাকা (থানা) বেছে নিন।');
    $fields = ['list' => $list, 'area' => $area, 'name' => $name, 'address' => str_in($IN, 'address', 200), 'mobile' => $mobile,
        'amount' => amount_in($IN, 'amount', $list !== 'land'), 'land' => land_bn(str_in($IN, 'land', 60)), 'details' => land_bn(str_in($IN, 'details', 300)),
        'date' => valid_date(str_in($IN, 'date', 10)) ? str_in($IN, 'date', 10) : '', 'memberNo' => str_in($IN, 'memberNo', 30), 'note' => str_in($IN, 'note', 300)];
    if ($act === 'people_add') {
        $rec = ['id' => new_id()] + $fields + ['by' => $u['id'], 'createdAt' => date('Y-m-d H:i:s')];
        hmutate('people', function (&$p) use ($rec) { $p[] = $rec; });
        audit($u, LISTS[$list] . ' যোগ', ['id' => $rec['id'], 'name' => $name]);
        out(['ok' => true, 'person' => $rec]);
    }
    $id = str_in($IN, 'id', 20);
    $done = hmutate('people', function (&$p) use ($id, $fields, $u) {
        foreach ($p as &$r) if ($r['id'] === $id) { $old = $r; unset($old['history']); $r['history'][] = $old + ['changedAt' => date('Y-m-d H:i:s'), 'changedBy' => $u['id']]; $r = array_merge($r, $fields); return true; }
        return false;
    });
    if (!$done) fail('তথ্য পাওয়া যায়নি।', 404);
    audit($u, LISTS[$list] . ' সম্পাদনা', ['id' => $id]);
    out(['ok' => true]);
}

case 'people_delete':
case 'people_restore': {
    $u = auth(true);
    $id = str_in($IN, 'id', 20); $reason = str_in($IN, 'reason', 200);
    if ($act === 'people_delete' && $reason === '') fail('মুছে ফেলার কারণ লিখুন।');
    $done = hmutate('people', function (&$p) use ($id, $act, $reason, $u) {
        foreach ($p as &$r) if ($r['id'] === $id) {
            if ($act === 'people_delete') { $r['deleted'] = true; $r['deletedAt'] = date('Y-m-d H:i:s'); $r['deletedBy'] = $u['id']; $r['deleteReason'] = $reason; }
            else unset($r['deleted'], $r['deletedAt'], $r['deletedBy'], $r['deleteReason']);
            return true;
        }
        return false;
    });
    if (!$done) fail('তথ্য পাওয়া যায়নি।', 404);
    audit($u, $act === 'people_delete' ? 'তালিকা থেকে মুছে ফেলা' : 'তালিকায় ফিরিয়ে আনা', ['id' => $id, 'reason' => $reason]);
    out(['ok' => true]);
}

case 'area_add': {
    $u = auth(true);
    $n = str_in($IN, 'name', 60);
    if ($n === '') fail('এলাকার নাম লিখুন।');
    hmutate('areas', function (&$a) use ($n) { if (!in_array($n, $a, true)) $a[] = $n; }, AREAS_DEFAULT);
    audit($u, 'এলাকা যোগ', ['name' => $n]);
    out(['ok' => true, 'areas' => areas_list()]);
}

/* ----- SMS ----- */
case 'sms_day': {
    $u = auth();
    $date = str_in($IN, 'date', 10);
    if (!valid_date($date)) fail('তারিখ ঠিক নেই।');
    [$s, $f] = sms_flush($u, $date);
    out(['ok' => true, 'sent' => $s, 'failed' => $f]);
}
case 'sms_get': { auth(true); out(['ok' => true, 'cfg' => sms_cfg()]); }
case 'sms_set': {
    $u = auth(true);
    $c = ['enabled' => !empty($IN['enabled']), 'auto' => !empty($IN['auto']), 'method' => strtoupper(str_in($IN, 'method', 4)) === 'POST' ? 'POST' : 'GET',
        'url' => str_in($IN, 'url', 300), 'params' => str_in($IN, 'params', 1000), 'success' => str_in($IN, 'success', 60),
        'number_format' => str_in($IN, 'number_format', 3) === '0' ? '0' : '880', 'template' => str_in($IN, 'template', 400)];
    if ($c['url'] !== '' && !preg_match('#^https?://#', $c['url'])) fail('API লিংক http:// বা https:// দিয়ে শুরু হতে হবে।');
    if ($c['template'] === '') fail('বার্তার নমুনা খালি রাখা যাবে না।');
    hwrite('sms', $c);
    audit($u, 'SMS সেটিংস বদল');
    out(['ok' => true]);
}
case 'sms_test': {
    $u = auth(true);
    $m = norm_mobile(str_in($IN, 'mobile', 20));
    if (!$m) fail('মোবাইল নম্বর ঠিক নেই।');
    $c = sms_cfg(); $c['enabled'] = true;
    [$ok, $info] = sms_send($m, 'বাসুদেব যোগাশ্রম হিসাব অ্যাপ থেকে পরীক্ষামূলক SMS। জয় গুরু।', $c);
    audit($u, 'পরীক্ষামূলক SMS', ['ok' => $ok]);
    out(['ok' => true, 'sent' => $ok, 'info' => $info]);
}

/* ----- users (admin) ----- */
case 'users': { auth(true); out(['ok' => true, 'users' => array_map('public_user', hread('users')), 'max' => MAX_USERS]); }
case 'user_add': {
    $u = auth(true);
    $un = mb_strtolower(str_in($IN, 'username', 40)); $nm = str_in($IN, 'name', 80); $pw = (string)($IN['password'] ?? '');
    if (!preg_match('/^[a-z0-9_.]{3,40}$/', $un)) fail('ইউজার আইডি ইংরেজি ছোট হাতের অক্ষর বা সংখ্যায় দিন (৩ অক্ষরের বেশি)।');
    if ($nm === '') fail('নাম লিখুন।');
    if (mb_strlen($pw) < 6) fail('পাসওয়ার্ড অন্তত ৬ অক্ষর হতে হবে।');
    hmutate('users', function (&$us) use ($un, $nm, $pw) {
        $n = 0;
        foreach ($us as $x) { if ($x['username'] === $un) fail('এই ইউজার আইডি আগেই আছে।'); if ($x['role'] === 'user' && ($x['active'] ?? true)) $n++; }
        if ($n >= MAX_USERS) fail('সর্বোচ্চ ' . bn_digits((string)MAX_USERS) . ' জন ইউজার রাখা যায়। কাউকে বন্ধ করে তারপর নতুন যোগ করুন।');
        $us[] = ['id' => new_id(), 'username' => $un, 'name' => $nm, 'role' => 'user', 'hash' => password_hash($pw, PASSWORD_DEFAULT), 'active' => true, 'created' => date('Y-m-d')];
    });
    audit($u, 'ইউজার যোগ', ['username' => $un]);
    out(['ok' => true]);
}
case 'user_reset':
case 'user_toggle': {
    $u = auth(true);
    $id = str_in($IN, 'id', 20); $pw = (string)($IN['password'] ?? '');
    if ($act === 'user_reset' && mb_strlen($pw) < 6) fail('নতুন পাসওয়ার্ড অন্তত ৬ অক্ষর দিন।');
    if ($act === 'user_toggle' && $id === $u['id']) fail('নিজের অ্যাকাউন্ট বন্ধ করা যাবে না।');
    hmutate('users', function (&$us) use ($id, $act, $pw) {
        $active = 0;
        foreach ($us as $x) if ($x['role'] === 'user' && ($x['active'] ?? true)) $active++;
        foreach ($us as &$x) if ($x['id'] === $id) {
            if ($act === 'user_reset') $x['hash'] = password_hash($pw, PASSWORD_DEFAULT);
            else { if (!($x['active'] ?? true) && $x['role'] === 'user' && $active >= MAX_USERS) fail('সর্বোচ্চ ইউজার সংখ্যা পূর্ণ।'); $x['active'] = !($x['active'] ?? true); }
            return;
        }
        fail('ইউজার পাওয়া যায়নি।', 404);
    });
    drop_tokens_of($id);
    audit($u, $act === 'user_reset' ? 'পাসওয়ার্ড রিসেট' : 'ইউজার চালু/বন্ধ', ['id' => $id]);
    out(['ok' => true]);
}
case 'password': {
    $u = auth();
    if (!password_verify((string)($IN['old'] ?? ''), $u['hash'])) fail('বর্তমান পাসওয়ার্ড সঠিক নয়।');
    $new = (string)($IN['new'] ?? '');
    if (mb_strlen($new) < ($u['role'] === 'admin' ? 8 : 6)) fail('নতুন পাসওয়ার্ড আরও লম্বা দিন।');
    hmutate('users', function (&$us) use ($u, $new) { foreach ($us as &$x) if ($x['id'] === $u['id']) $x['hash'] = password_hash($new, PASSWORD_DEFAULT); });
    audit($u, 'নিজের পাসওয়ার্ড বদল');
    out(['ok' => true]);
}

case 'ping': { auth(); out(['ok' => true]); }

case 'audit': {
    auth(true);
    $f = hdir() . '/audit.log';
    $lines = file_exists($f) ? array_slice(file($f, FILE_IGNORE_NEW_LINES) ?: [], -400) : [];
    out(['ok' => true, 'log' => array_reverse(array_map(fn($l) => json_decode($l, true), $lines))]);
}

/* ----- CSV download (one-time link, works with Android DownloadManager) ----- */
case 'export_link': {
    $u = auth(true);
    if (str_in($IN, 'what', 10) === 'backup') audit($u, 'পূর্ণ ব্যাকআপ ডাউনলোড');
    $code = bin2hex(random_bytes(16));
    hmutate('dl', function (&$d) use ($code, $IN) {
        foreach ($d as $k => $v) if ($v['exp'] < time()) unset($d[$k]);
        $d[$code] = ['exp' => time() + 120, 'what' => str_in($IN, 'what', 10), 'from' => str_in($IN, 'from', 10), 'to' => str_in($IN, 'to', 10)];
    });
    out(['ok' => true, 'url' => 'api.php?a=export&c=' . $code]);
}
case 'export': {
    $code = (string)($_GET['c'] ?? '');
    $job = hmutate('dl', function (&$d) use ($code) { $j = $d[$code] ?? null; unset($d[$code]); return $j; });
    if (!$job || $job['exp'] < time()) { header('Content-Type: text/plain; charset=utf-8'); http_response_code(410); exit('লিংকের মেয়াদ শেষ। অ্যাপ থেকে আবার ডাউনলোড চাপুন।'); }
    if ($job['what'] === 'backup') {
        /* everything needed to rebuild the ledger; password hashes and login sessions are left out */
        $b = ['app' => 'basudev-hisab', 'version' => 1, 'created' => date('Y-m-d H:i:s'),
              'users' => array_map('public_user', hread('users')), 'areas' => areas_list(), 'people' => hread('people'), 'sms' => sms_cfg(), 'ledger' => []];
        foreach (glob(hdir() . '/ledger-*.json') ?: [] as $f) $b['ledger'][basename($f, '.json')] = hread(basename($f, '.json'));
        $f = hdir() . '/audit.log';
        $b['audit'] = file_exists($f) ? array_map(fn($l) => json_decode($l, true), file($f, FILE_IGNORE_NEW_LINES) ?: []) : [];
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="basudev-hisab-backup_' . date('Y-m-d_Hi') . '.json"');
        echo json_encode($b, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    $rows = [];
    if ($job['what'] === 'people') {
        $rows[] = ['তালিকা', 'এলাকা', 'নাম', 'ঠিকানা', 'মোবাইল', 'টাকা', 'জমির পরিমাণ', 'বিবরণ', 'তারিখ', 'সদস্য নং', 'মন্তব্য'];
        foreach (hread('people') as $p) if (empty($p['deleted'])) $rows[] = [LISTS[$p['list']] ?? '', $p['area'], $p['name'], $p['address'], $p['mobile'], $p['amount'], $p['land'], $p['details'], $p['date'], $p['memberNo'], $p['note']];
        $fname = 'ashram-talika.csv';
    } else {
        $from = valid_date($job['from']) ? $job['from'] : '2000-01-01'; $to = valid_date($job['to']) ? $job['to'] : '2100-12-31';
        $rows[] = ['তারিখ', 'ক্রমিক', 'ধরন', 'খাত', 'প্রকল্প', 'নাম', 'মোবাইল', 'ঠিকানা', 'রসিদ', 'মাধ্যম', 'টাকা', 'মন্তব্য', 'SMS'];
        $all = [];
        for ($y = (int)substr($from, 0, 4); $y <= min((int)substr($to, 0, 4), (int)date('Y') + 1); $y++) $all = array_merge($all, hread("ledger-$y"));
        usort($all, fn($a, $b) => [$a['date'], $a['kind'], $a['serial']] <=> [$b['date'], $b['kind'], $b['serial']]);
        foreach ($all as $r) if (empty($r['deleted']) && $r['date'] >= $from && $r['date'] <= $to)
            $rows[] = [$r['date'], $r['serial'], KINDS[$r['kind']], $r['category'], $r['project'], $r['name'], $r['mobile'], $r['address'], $r['ref'], $r['mode'], $r['amount'], $r['note'], $r['sms']['status'] ?? ''];
        $fname = "ashram-hisab_{$from}_{$to}.csv";
    }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $fname . '"');
    $o = fopen('php://output', 'w'); fwrite($o, "\xEF\xBB\xBF");
    foreach ($rows as $r) fputcsv($o, $r);
    exit;
}

default:
    fail('অজানা অনুরোধ।', 404);
}
