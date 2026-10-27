<?php
declare(strict_types=1);
require dirname(__DIR__) . '/inc/lib.php';
start_session();
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('Cache-Control: no-store');

/* ---------- content types ---------- */
$KINDS = ['chandi' => 'চণ্ডীযজ্ঞ (অমাবস্যা)', 'guru' => 'গুরু তিথি', 'special' => 'বিশেষ তিথি', 'utsav' => 'উৎসব'];
$TYPES = [
  'posts' => [
    'label' => 'খবর ও নোটিশ', 'item' => 'খবর', 'icon' => '📰', 'order' => 'date_desc',
    'help' => 'নতুন খবর, নোটিশ বা অনুষ্ঠানের আমন্ত্রণ লিখুন। “প্রকাশিত” টিক দিলে মূল পাতার “আশ্রমের সর্বশেষ খবর” অংশে দেখাবে।',
    'fields' => [
      ['title', 'শিরোনাম', 'text', true],
      ['date', 'তারিখ', 'date', true],
      ['body', 'বিস্তারিত লেখা', 'textarea', true, 'নতুন অনুচ্ছেদের জন্য একটি লাইন ফাঁকা রাখুন।'],
      ['image', 'ছবি (ঐচ্ছিক)', 'image', false],
      ['published', 'প্রকাশিত (ওয়েবসাইটে দেখাবে)', 'checkbox', false],
    ],
  ],
  'events' => [
    'label' => 'উৎসবসূচি', 'item' => 'অনুষ্ঠান', 'icon' => '🪔', 'order' => 'date_asc',
    'help' => 'অনুষ্ঠানগুলো তারিখ অনুযায়ী নিজে থেকেই সাজানো হয়। পেরিয়ে যাওয়া তারিখ ওয়েবসাইটে “সম্পন্ন” দেখায়।',
    'fields' => [
      ['date', 'তারিখ (ইংরেজি)', 'date', true],
      ['title', 'অনুষ্ঠানের নাম', 'text', true],
      ['kind', 'ধরন', 'select', true, '', $KINDS],
      ['bangla_date', 'বাংলা তারিখ (ঐচ্ছিক)', 'text', false, 'যেমন: ২১ কার্তিক ১৪৩৩'],
    ],
  ],
  'donors' => [
    'label' => 'ভূমিদাতা', 'item' => 'ভূমিদাতা', 'icon' => '🙏', 'order' => 'manual',
    'help' => 'ওপর-নিচ তীর দিয়ে ক্রম বদলান। “গুরুদের সারি” বড় সোনালি ফ্রেমে সবার ওপরে দেখায়।',
    'fields' => [
      ['group', 'কোন সারিতে', 'select', true, '', ['main' => 'সাধারণ তালিকা', 'guru' => 'গুরুদের সারি (ওপরে)']],
      ['name', 'নাম', 'text', true, 'যেমন: শ্রী / শ্রীমতি / স্বর্গীয় ...'],
      ['address', 'ঠিকানা', 'text', false],
      ['paksha', 'পক্ষে (ঐচ্ছিক)', 'text', false, 'যেমন: শ্রী মিঠু মহাজন (পুত্র)'],
      ['photo', 'ছবি', 'image', false],
    ],
  ],
  'gallery' => [
    'label' => 'ছবি গ্যালারি', 'item' => 'ছবি', 'icon' => '🖼️', 'order' => 'manual',
    'help' => 'ওপর-নিচ তীর দিয়ে ক্রম বদলান। “বড়” ছবি গ্যালারিতে দুই ঘর জুড়ে দেখায়।',
    'fields' => [
      ['image', 'ছবি', 'image', true],
      ['caption', 'ছবির নিচের লেখা', 'text', false],
      ['size', 'আকার', 'select', true, '', ['normal' => 'সাধারণ', 'wide' => 'চওড়া', 'large' => 'বড় (চওড়া ও লম্বা)']],
      ['focus_top', 'ছবির ওপরের অংশ দেখাও (পোস্টার/মানুষের ছবির জন্য)', 'checkbox', false],
    ],
  ],
];

/* ---------- helpers ---------- */
function url(array $q = []): string { return '?' . http_build_query($q); }
function redirect(array $q = [], string $flash = '', string $kind = 'ok'): never {
    if ($flash) $_SESSION['flash'] = [$kind, $flash];
    header('Location: ' . url($q)); exit;
}
function admin_img(string $ref): string { return '../' . img_url($ref); }
function is_super(?array $u): bool { return ($u['role'] ?? '') === 'super'; }

function layout(string $title, callable $body, ?array $user): void {
    global $TYPES;
    $flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
    $p = $_GET['p'] ?? 'home';
    ?><!doctype html>
<html lang="bn"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> · অ্যাডমিন · বাসুদেব যোগাশ্রম</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600&display=swap">
<link rel="stylesheet" href="admin.css?v=<?= @filemtime(__DIR__ . '/admin.css') ?>">
</head><body>
<?php if ($user): ?>
<header class="top"><div class="top-in">
  <a class="logo" href="<?= url() ?>">ॐ বাসুদেব যোগাশ্রম <small>অ্যাডমিন</small></a>
  <div class="who"><?= e($user['name']) ?> · <a href="<?= url(['p' => 'password']) ?>">পাসওয়ার্ড</a> · <a href="../" target="_blank" rel="noopener">সাইট দেখুন ↗</a>
    <form method="post" action="<?= url(['p' => 'logout']) ?>" class="inline"><?= csrf_field() ?><button class="link">লগআউট</button></form></div>
</div>
<nav class="tabs"><a class="<?= $p === 'home' ? 'on' : '' ?>" href="<?= url() ?>">ড্যাশবোর্ড</a>
<?php foreach ($TYPES as $k => $t): ?><a class="<?= ($_GET['t'] ?? '') === $k ? 'on' : '' ?>" href="<?= url(['p' => 'list', 't' => $k]) ?>"><?= $t['icon'] ?> <?= e($t['label']) ?></a><?php endforeach; ?>
<a class="<?= $p === 'settings' ? 'on' : '' ?>" href="<?= url(['p' => 'settings']) ?>">☎️ যোগাযোগ ও দান</a>
<?php if (is_super($user)): ?><a class="<?= $p === 'users' ? 'on' : '' ?>" href="<?= url(['p' => 'users']) ?>">👤 ব্যবহারকারী</a><?php endif; ?>
</nav></header>
<?php endif; ?>
<main class="main">
<?php if ($flash): ?><div class="flash <?= e($flash[0]) ?>"><?= e($flash[1]) ?></div><?php endif; ?>
<?php $body(); ?>
</main>
<script>
document.querySelectorAll('[data-confirm]').forEach(function(f){f.addEventListener('submit',function(e){if(!confirm(f.dataset.confirm))e.preventDefault();});});
document.querySelectorAll('input[type=file][data-preview]').forEach(function(i){i.addEventListener('change',function(){var p=document.getElementById(i.dataset.preview);if(p&&i.files[0]){p.src=URL.createObjectURL(i.files[0]);p.hidden=false;}});});
</script>
</body></html><?php
}

/* ---------- routing ---------- */
$p = (string)($_GET['p'] ?? 'home');
$user = current_user();
$hasUsers = count(load_users()) > 0;

if (!$hasUsers && $p !== 'setup') redirect(['p' => 'setup']);
if ($hasUsers && $p === 'setup') redirect(['p' => 'login']);
if (!$user && !in_array($p, ['login', 'setup', 'register'], true)) redirect(['p' => 'login']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') check_csrf();

/* ===== setup (first super admin) ===== */
if ($p === 'setup') {
    $err = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $key = trim((string)($_POST['key'] ?? ''));
        $un = trim((string)($_POST['username'] ?? ''));
        $nm = trim((string)($_POST['name'] ?? ''));
        $pw = (string)($_POST['password'] ?? '');
        if (login_blocked('setup')) $err = 'অনেকবার ভুল হয়েছে। ১৫ মিনিট পর চেষ্টা করুন।';
        elseif (!password_verify($key, SETUP_KEY_HASH)) { login_failed('setup'); $err = 'সেটআপ কী সঠিক নয়।'; }
        elseif (!preg_match('/^[a-zA-Z0-9_.]{3,30}$/', $un)) $err = 'ইউজারনেম ৩-৩০ অক্ষরের ইংরেজি অক্ষর/সংখ্যা হতে হবে।';
        elseif (mb_strlen($pw) < 8) $err = 'পাসওয়ার্ড কমপক্ষে ৮ অক্ষরের হতে হবে।';
        elseif ($nm === '') $err = 'আপনার নাম লিখুন।';
        else {
            save_users([['id' => new_id(), 'username' => $un, 'name' => $nm, 'role' => 'super', 'hash' => password_hash($pw, PASSWORD_DEFAULT), 'created' => date('Y-m-d')]]);
            foreach (COLLECTIONS as $c) load($c); // initialise data from seed
            redirect(['p' => 'login'], 'প্রধান অ্যাডমিন তৈরি হয়েছে। এবার লগইন করুন।');
        }
    }
    layout('প্রথম সেটআপ', function () use ($err) { ?>
      <form method="post" class="card narrow">
        <h1>প্রথম সেটআপ</h1>
        <p class="muted">প্রধান অ্যাডমিন অ্যাকাউন্ট তৈরি করুন। এটা একবারই করতে হবে।</p>
        <?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
        <?= csrf_field() ?>
        <label>সেটআপ কী<input name="key" required autocomplete="off"></label>
        <label>আপনার নাম<input name="name" required></label>
        <label>ইউজারনেম (ইংরেজিতে)<input name="username" required pattern="[a-zA-Z0-9_.]{3,30}" autocomplete="username"></label>
        <label>পাসওয়ার্ড (কমপক্ষে ৮ অক্ষর)<input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
        <button class="btn">অ্যাকাউন্ট তৈরি করুন</button>
      </form>
    <?php }, null);
    exit;
}

/* ===== login / logout ===== */
if ($p === 'login') {
    if ($user) redirect();
    $err = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $un = trim((string)($_POST['username'] ?? ''));
        $pw = (string)($_POST['password'] ?? '');
        if ($wait = login_blocked($un)) $err = 'অনেকবার ভুল পাসওয়ার্ড দেওয়া হয়েছে। ' . bn_digits((string)ceil($wait / 60)) . ' মিনিট পর আবার চেষ্টা করুন।';
        else {
            $found = null;
            foreach (load_users() as $u) if (strcasecmp($u['username'], $un) === 0) $found = $u;
            if ($found && password_verify($pw, $found['hash']) && ($found['status'] ?? 'active') === 'pending') {
                login_ok($un);
                $err = 'আপনার অ্যাকাউন্ট এখনো প্রধান অ্যাডমিনের অনুমোদনের অপেক্ষায় আছে। অনুমোদন হলে লগইন করতে পারবেন।';
            } elseif ($found && password_verify($pw, $found['hash'])) {
                login_ok($un);
                session_regenerate_id(true);
                $_SESSION['uid'] = $found['id'];
                $_SESSION['csrf'] = bin2hex(random_bytes(16));
                log_action('লগইন করেছেন');
                redirect();
            }
            if (!$err) { login_failed($un); $err = 'ইউজারনেম বা পাসওয়ার্ড সঠিক নয়।'; }
        }
    }
    layout('লগইন', function () use ($err) { ?>
      <form method="post" class="card narrow">
        <h1>ॐ অ্যাডমিন লগইন</h1>
        <p class="muted">শ্রীশ্রী বাসুদেব যোগাশ্রম ওয়েবসাইট</p>
        <?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
        <?= csrf_field() ?>
        <label>ইউজারনেম<input name="username" required autocomplete="username" autofocus></label>
        <label>পাসওয়ার্ড<input type="password" name="password" required autocomplete="current-password"></label>
        <button class="btn">লগইন</button>
        <p class="muted small">পাসওয়ার্ড ভুলে গেলে প্রধান অ্যাডমিনকে বলুন, তিনি নতুন পাসওয়ার্ড দিতে পারবেন।</p>
        <p class="small">নতুন? <a href="?p=register">নিজের অ্যাকাউন্ট খুলুন →</a></p>
      </form>
    <?php }, null);
    exit;
}

/* ===== self registration (needs invite code + approval) ===== */
if ($p === 'register') {
    if ($user) redirect();
    $inv = invite();
    $err = ''; $done = false;
    $prefill = preg_replace('/[^A-Z0-9-]/', '', strtoupper((string)($_GET['code'] ?? '')));
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $code = strtoupper(trim((string)($_POST['code'] ?? '')));
        $un = trim((string)($_POST['username'] ?? '')); $nm = trim((string)($_POST['name'] ?? ''));
        $ph = trim((string)($_POST['phone'] ?? '')); $pw = (string)($_POST['password'] ?? ''); $pw2 = (string)($_POST['password2'] ?? '');
        $us = load_users();
        if (login_blocked('register')) $err = 'অনেকবার ভুল কোড দেওয়া হয়েছে। ১৫ মিনিট পর চেষ্টা করুন।';
        elseif (empty($inv['on']) || !hash_equals((string)($inv['code'] ?? ''), $code)) { login_failed('register'); $err = 'আমন্ত্রণ কোড সঠিক নয়। প্রধান অ্যাডমিনের কাছ থেকে সঠিক কোড নিন।'; }
        elseif ($nm === '') $err = 'আপনার নাম লিখুন।';
        elseif (!preg_match('/^[a-zA-Z0-9_.]{3,30}$/', $un)) $err = 'ইউজারনেম ৩-৩০ অক্ষরের ইংরেজি অক্ষর বা সংখ্যা হতে হবে (ফাঁকা জায়গা ছাড়া)।';
        elseif (array_filter($us, fn($u) => strcasecmp($u['username'], $un) === 0)) $err = 'এই ইউজারনেম আগেই নেওয়া হয়েছে। অন্য একটি বেছে নিন।';
        elseif (mb_strlen($pw) < 8) $err = 'পাসওয়ার্ড কমপক্ষে ৮ অক্ষরের হতে হবে।';
        elseif ($pw !== $pw2) $err = 'দুটি পাসওয়ার্ড মেলেনি।';
        elseif (count(array_filter($us, fn($u) => ($u['status'] ?? '') === 'pending')) >= 20) $err = 'এখন অনেক আবেদন অপেক্ষায় আছে। পরে চেষ্টা করুন।';
        else {
            $us[] = ['id' => new_id(), 'username' => $un, 'name' => mb_substr($nm, 0, 80), 'phone' => mb_substr($ph, 0, 30), 'role' => 'editor', 'status' => 'pending', 'hash' => password_hash($pw, PASSWORD_DEFAULT), 'created' => date('Y-m-d H:i')];
            save_users($us); login_ok('register');
            $done = true;
        }
    }
    layout('নতুন অ্যাকাউন্ট', function () use ($err, $done, $prefill) { ?>
      <form method="post" class="card narrow" action="?p=register">
        <h1>ॐ নতুন অ্যাকাউন্ট খুলুন</h1>
        <?php if ($done): ?>
          <div class="flash ok">আবেদন জমা হয়েছে। প্রধান অ্যাডমিন অনুমোদন দিলেই আপনার ইউজারনেম ও পাসওয়ার্ড দিয়ে লগইন করতে পারবেন।</div>
          <p><a href="?p=login">← লগইন পাতায় যান</a></p>
        <?php else: ?>
        <p class="muted">আশ্রমের দায়িত্বপ্রাপ্তদের জন্য। আমন্ত্রণ কোড প্রধান অ্যাডমিনের কাছ থেকে নিন। জমা দেওয়ার পর প্রধান অ্যাডমিন অনুমোদন দিলে অ্যাকাউন্ট চালু হবে।</p>
        <?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
        <?= csrf_field() ?>
        <label>আমন্ত্রণ কোড<input name="code" required value="<?= e($_POST['code'] ?? $prefill) ?>" autocomplete="off"></label>
        <label>আপনার নাম<input name="name" required value="<?= e($_POST['name'] ?? '') ?>"></label>
        <label>মোবাইল নম্বর<input name="phone" value="<?= e($_POST['phone'] ?? '') ?>" inputmode="tel"><small>প্রধান অ্যাডমিন যাতে আপনাকে চিনতে পারেন</small></label>
        <label>ইউজারনেম (ইংরেজিতে, নিজে বেছে নিন)<input name="username" required pattern="[a-zA-Z0-9_.]{3,30}" value="<?= e($_POST['username'] ?? '') ?>" autocomplete="username"><small>যেমন: rajib, office.ctg</small></label>
        <label>পাসওয়ার্ড (কমপক্ষে ৮ অক্ষর)<input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
        <label>পাসওয়ার্ড আবার<input type="password" name="password2" required minlength="8" autocomplete="new-password"></label>
        <button class="btn">আবেদন জমা দিন</button>
        <p class="small"><a href="?p=login">← লগইন পাতায় ফিরুন</a></p>
        <?php endif; ?>
      </form>
    <?php }, null);
    exit;
}

if ($p === 'logout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    log_action('লগআউট করেছেন');
    session_unset(); session_destroy();
    session_start(); $_SESSION['flash'] = ['ok', 'লগআউট হয়েছে।'];
    header('Location: ?p=login'); exit;
}

/* ===== dashboard ===== */
if ($p === 'home') {
    layout('ড্যাশবোর্ড', function () use ($TYPES, $user) {
        $act = file_exists(data_dir() . '/activity.json') ? (json_decode((string)file_get_contents(data_dir() . '/activity.json'), true) ?: []) : []; ?>
      <h1>নমস্কার, <?= e($user['name']) ?> 🙏</h1>
      <p class="muted">কী করতে চান বেছে নিন। সব পরিবর্তন সাথে সাথে ওয়েবসাইটে দেখা যাবে।</p>
      <div class="dash">
        <?php foreach ($TYPES as $k => $t): ?>
          <a class="tile" href="<?= url(['p' => 'list', 't' => $k]) ?>"><span class="ti"><?= $t['icon'] ?></span><b><?= e($t['label']) ?></b><span><?= bn_digits((string)count(load($k))) ?>টি</span></a>
        <?php endforeach; ?>
        <a class="tile" href="<?= url(['p' => 'settings']) ?>"><span class="ti">☎️</span><b>যোগাযোগ ও দান</b><span>ফোন, ব্যাংক হিসাব</span></a>
      </div>
      <div class="quick">
        <a class="btn" href="<?= url(['p' => 'edit', 't' => 'posts']) ?>">＋ নতুন খবর লিখুন</a>
        <a class="btn ghost" href="<?= url(['p' => 'edit', 't' => 'events']) ?>">＋ নতুন অনুষ্ঠান</a>
        <a class="btn ghost" href="<?= url(['p' => 'edit', 't' => 'gallery']) ?>">＋ নতুন ছবি</a>
      </div>
      <h2>সাম্প্রতিক কাজ</h2>
      <ul class="log"><?php foreach (array_slice($act, 0, 15) as $a): ?><li><span><?= e(bn_digits($a['t'])) ?></span> <b><?= e($a['who']) ?></b> <?= e($a['what']) ?></li><?php endforeach; ?>
      <?php if (!$act): ?><li class="muted">এখনো কিছু নেই।</li><?php endif; ?></ul>
    <?php }, $user);
    exit;
}

/* ===== generic list ===== */
$t = (string)($_GET['t'] ?? '');
if (in_array($p, ['list', 'edit', 'delete', 'move', 'toggle'], true)) {
    if (!isset($TYPES[$t])) redirect([], 'পাতাটি পাওয়া যায়নি।', 'err');
    $T = $TYPES[$t];
    $items = load($t);
}

if ($p === 'list') {
    if ($T['order'] === 'date_desc') usort($items, fn($a, $b) => strcmp($b['date'], $a['date']));
    if ($T['order'] === 'date_asc') usort($items, fn($a, $b) => strcmp($a['date'], $b['date']));
    layout($T['label'], function () use ($T, $t, $items, $KINDS) {
        $today = date('Y-m-d'); ?>
      <div class="head-row"><h1><?= $T['icon'] ?> <?= e($T['label']) ?></h1><a class="btn" href="<?= url(['p' => 'edit', 't' => $t]) ?>">＋ নতুন <?= e($T['item']) ?></a></div>
      <p class="muted"><?= e($T['help']) ?></p>
      <?php if (!$items): ?><div class="card empty">এখনো কোনো <?= e($T['item']) ?> নেই। ওপরের বোতাম চেপে প্রথমটি যোগ করুন।</div><?php endif; ?>
      <ul class="items">
      <?php foreach ($items as $i => $it):
        $img = $it['image'] ?? $it['photo'] ?? '';
        if ($t === 'posts') { $main = $it['title']; $sub = bn_date($it['date']) . ' · ' . (!empty($it['published']) ? 'প্রকাশিত' : 'খসড়া (দেখাচ্ছে না)'); }
        elseif ($t === 'events') { $main = $it['title']; $sub = bn_date($it['date']) . ' · ' . ($KINDS[$it['kind']] ?? '') . ($it['date'] < $today ? ' · সম্পন্ন' : ''); }
        elseif ($t === 'donors') { $main = $it['name']; $sub = trim(($it['group'] === 'guru' ? 'গুরুদের সারি · ' : '') . $it['address'] . ($it['paksha'] ? ' · পক্ষে: ' . $it['paksha'] : ''), ' ·'); }
        else { $main = $it['caption'] ?: '(লেখা নেই)'; $sub = ['normal' => 'সাধারণ', 'wide' => 'চওড়া', 'large' => 'বড়'][$it['size']] ?? ''; }
      ?>
        <li class="item<?= ($t === 'events' && $it['date'] < $today) || ($t === 'posts' && empty($it['published'])) ? ' dim' : '' ?>">
          <?php if ($img): ?><img src="<?= e(admin_img($img)) ?>" alt="" loading="lazy"><?php elseif (in_array($t, ['donors', 'gallery', 'posts'], true)): ?><span class="noimg">ॐ</span><?php endif; ?>
          <div class="it-txt"><b><?= e($main) ?></b><span><?= e($sub) ?></span></div>
          <div class="acts">
            <?php if ($T['order'] === 'manual'): ?>
              <form method="post" action="<?= url(['p' => 'move', 't' => $t, 'id' => $it['id'], 'dir' => 'up']) ?>"><?= csrf_field() ?><button class="ic" title="ওপরে" <?= $i === 0 ? 'disabled' : '' ?>>▲</button></form>
              <form method="post" action="<?= url(['p' => 'move', 't' => $t, 'id' => $it['id'], 'dir' => 'down']) ?>"><?= csrf_field() ?><button class="ic" title="নিচে" <?= $i === count($items) - 1 ? 'disabled' : '' ?>>▼</button></form>
            <?php endif; ?>
            <a class="btn small ghost" href="<?= url(['p' => 'edit', 't' => $t, 'id' => $it['id']]) ?>">সম্পাদনা</a>
            <form method="post" action="<?= url(['p' => 'delete', 't' => $t, 'id' => $it['id']]) ?>" data-confirm="“<?= e($main) ?>” মুছে ফেলবেন? এটা ফেরত আনা যাবে না।"><?= csrf_field() ?><button class="btn small danger">মুছুন</button></form>
          </div>
        </li>
      <?php endforeach; ?>
      </ul>
    <?php }, $user);
    exit;
}

if ($p === 'edit') {
    $id = (string)($_GET['id'] ?? '');
    $idx = null;
    foreach ($items as $i => $it) if ($it['id'] === $id) $idx = $i;
    if ($id !== '' && $idx === null) redirect(['p' => 'list', 't' => $t], 'এটি পাওয়া যায়নি।', 'err');
    $item = $idx !== null ? $items[$idx] : ['id' => '', 'date' => date('Y-m-d'), 'published' => true, 'size' => 'normal', 'group' => 'main', 'kind' => 'chandi'];
    $err = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $new = $item;
        try {
            foreach ($T['fields'] as $f) {
                [$key, $label, $type] = $f; $req = $f[3] ?? false;
                if ($type === 'image') {
                    $up = handle_upload($key, $t === 'donors' ? 600 : 1600);
                    if ($up) { if (!empty($item[$key])) delete_upload($item[$key]); $new[$key] = $up; }
                    elseif (!empty($_POST[$key . '_remove'])) { delete_upload($item[$key] ?? null); $new[$key] = ''; }
                    if ($req && empty($new[$key])) throw new RuntimeException("“{$label}” দিতে হবে।");
                    continue;
                }
                if ($type === 'checkbox') { $new[$key] = !empty($_POST[$key]); continue; }
                $v = trim((string)($_POST[$key] ?? ''));
                if ($req && $v === '') throw new RuntimeException("“{$label}” ঘরটি পূরণ করুন।");
                if ($type === 'date' && $v !== '' && !valid_date($v)) throw new RuntimeException('তারিখ সঠিক নয়।');
                if ($type === 'select' && !isset($f[5][$v])) throw new RuntimeException("“{$label}” বেছে নিন।");
                if (mb_strlen($v) > ($type === 'textarea' ? 20000 : 300)) throw new RuntimeException("“{$label}” অনেক বড় হয়ে গেছে।");
                $new[$key] = $v;
            }
            if ($t === 'events') { $new['date_text'] = bn_date($new['date']); $new['weekday'] = bn_weekday($new['date']); }
            if ($t === 'gallery') $new['alt'] = $new['caption'];
            if ($idx === null) {
                $new['id'] = new_id();
                // new donors go to the end of their list; everything else goes first
                if ($t === 'donors') $items[] = $new; else array_unshift($items, $new);
            }
            else $items[$idx] = $new;
            save($t, array_values($items));
            $name = $new['title'] ?? $new['name'] ?? $new['caption'] ?? '';
            log_action(($idx === null ? 'যোগ করেছেন' : 'সম্পাদনা করেছেন') . ': ' . $T['item'] . ' “' . $name . '”');
            redirect(['p' => 'list', 't' => $t], 'সংরক্ষিত হয়েছে। ওয়েবসাইটে এখনই দেখা যাবে।');
        } catch (RuntimeException $ex) {
            $err = $ex->getMessage();
            foreach ($T['fields'] as $f) if (!in_array($f[2], ['image'], true)) $item[$f[0]] = $f[2] === 'checkbox' ? !empty($_POST[$f[0]]) : (string)($_POST[$f[0]] ?? '');
        }
    }
    layout(($idx === null ? 'নতুন ' : 'সম্পাদনা: ') . $T['item'], function () use ($T, $t, $item, $idx, $err) { ?>
      <a class="back" href="<?= url(['p' => 'list', 't' => $t]) ?>">← <?= e($T['label']) ?></a>
      <h1><?= $idx === null ? 'নতুন ' . e($T['item']) : 'সম্পাদনা' ?></h1>
      <?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
      <form method="post" enctype="multipart/form-data" class="card form">
        <?= csrf_field() ?>
        <?php foreach ($T['fields'] as $f): [$key, $label, $type] = $f; $req = $f[3] ?? false; $hint = $f[4] ?? ''; $val = $item[$key] ?? ''; ?>
          <?php if ($type === 'checkbox'): ?>
            <label class="check"><input type="checkbox" name="<?= $key ?>" value="1" <?= $val ? 'checked' : '' ?>> <?= e($label) ?></label>
          <?php elseif ($type === 'image'): ?>
            <div class="field"><span class="lbl"><?= e($label) ?><?= $req ? ' *' : '' ?></span>
              <img id="pv-<?= $key ?>" class="pv" src="<?= $val ? e(admin_img($val)) : '' ?>" alt="" <?= $val ? '' : 'hidden' ?>>
              <input type="file" name="<?= $key ?>" accept="image/jpeg,image/png,image/webp" data-preview="pv-<?= $key ?>">
              <small>JPG/PNG ছবি, ফোনের ছবি সরাসরি দেওয়া যাবে। বড় ছবি নিজে থেকেই ছোট হয়ে যাবে।</small>
              <?php if ($val && !$req): ?><label class="check"><input type="checkbox" name="<?= $key ?>_remove" value="1"> ছবিটি সরিয়ে দিন</label><?php endif; ?>
            </div>
          <?php elseif ($type === 'textarea'): ?>
            <label><?= e($label) ?><?= $req ? ' *' : '' ?><textarea name="<?= $key ?>" rows="12" <?= $req ? 'required' : '' ?>><?= e($val) ?></textarea><?php if ($hint): ?><small><?= e($hint) ?></small><?php endif; ?></label>
          <?php elseif ($type === 'select'): ?>
            <label><?= e($label) ?><select name="<?= $key ?>"><?php foreach ($f[5] as $ok => $ol): ?><option value="<?= e($ok) ?>" <?= $val === $ok ? 'selected' : '' ?>><?= e($ol) ?></option><?php endforeach; ?></select></label>
          <?php else: ?>
            <label><?= e($label) ?><?= $req ? ' *' : '' ?><input type="<?= $type === 'date' ? 'date' : 'text' ?>" name="<?= $key ?>" value="<?= e($val) ?>" <?= $req ? 'required' : '' ?> maxlength="300"><?php if ($hint): ?><small><?= e($hint) ?></small><?php endif; ?></label>
          <?php endif; ?>
        <?php endforeach; ?>
        <div class="form-actions"><button class="btn">সংরক্ষণ করুন</button><a class="btn ghost" href="<?= url(['p' => 'list', 't' => $t]) ?>">বাতিল</a></div>
      </form>
    <?php }, $user);
    exit;
}

if ($p === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (string)($_GET['id'] ?? '');
    foreach ($items as $i => $it) if ($it['id'] === $id) {
        delete_upload($it['image'] ?? $it['photo'] ?? null);
        unset($items[$i]);
        save($t, array_values($items));
        log_action('মুছে ফেলেছেন: ' . $T['item'] . ' “' . ($it['title'] ?? $it['name'] ?? $it['caption'] ?? '') . '”');
        redirect(['p' => 'list', 't' => $t], 'মুছে ফেলা হয়েছে।');
    }
    redirect(['p' => 'list', 't' => $t]);
}

if ($p === 'move' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (string)($_GET['id'] ?? ''); $dir = $_GET['dir'] ?? '';
    foreach ($items as $i => $it) if ($it['id'] === $id) {
        $j = $dir === 'up' ? $i - 1 : $i + 1;
        if (isset($items[$j])) { [$items[$i], $items[$j]] = [$items[$j], $items[$i]]; save($t, $items, false); }
        break;
    }
    redirect(['p' => 'list', 't' => $t]);
}

/* ===== settings ===== */
if ($p === 'settings') {
    $S = load('settings');
    $fields = [
        'phone1' => ['প্রধান ফোন নম্বর', 'ওয়েবসাইটের সব জায়গায় দেখাবে'],
        'phone2' => ['হোয়াটসঅ্যাপ নম্বর (দেখানোর জন্য)', 'যেমন: ০১৮৩৫-০০০৮২৪'],
        'whatsapp' => ['হোয়াটসঅ্যাপ নম্বর (বুকিং বার্তা যাবে)', 'দেশের কোডসহ ইংরেজি সংখ্যায়, যেমন: 8801835000824'],
        'facebook' => ['ফেসবুক পেজের লিংক', ''],
        'youtube' => ['ইউটিউব চ্যানেলের লিংক', ''],
        'maps' => ['গুগল ম্যাপ লিংক', ''],
    ];
    $err = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        foreach ($fields as $k => $_) $S[$k] = trim((string)($_POST[$k] ?? ''));
        foreach (['facebook', 'youtube', 'maps'] as $k) if ($S[$k] !== '' && !preg_match('#^https://#', $S[$k])) $err = 'লিংক অবশ্যই https:// দিয়ে শুরু হতে হবে।';
        if (!preg_match('/^\d{10,15}$/', $S['whatsapp'])) $err = 'বুকিংয়ের হোয়াটসঅ্যাপ নম্বর ইংরেজি সংখ্যায় দেশের কোডসহ দিন, যেমন 8801835000824।';
        $banks = [];
        foreach ((array)($_POST['bank'] ?? []) as $b) {
            $b = array_map(fn($x) => trim((string)$x), (array)$b);
            if (($b['account'] ?? '') === '') continue;
            $banks[] = ['id' => $b['id'] ?: new_id(), 'title' => $b['title'] ?? '', 'account' => $b['account'], 'type' => $b['type'] ?? '', 'bank' => $b['bank'] ?? ''];
        }
        $S['banks'] = $banks;
        if (!$err) { save('settings', $S); log_action('যোগাযোগ ও দানের তথ্য হালনাগাদ করেছেন'); redirect(['p' => 'settings'], 'সংরক্ষিত হয়েছে।'); }
    }
    layout('যোগাযোগ ও দান', function () use ($S, $fields, $err) { ?>
      <h1>☎️ যোগাযোগ ও দানের তথ্য</h1>
      <?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
      <form method="post" class="card form">
        <?= csrf_field() ?>
        <?php foreach ($fields as $k => [$l, $h]): ?>
          <label><?= e($l) ?><input name="<?= $k ?>" value="<?= e($S[$k] ?? '') ?>"><?php if ($h): ?><small><?= e($h) ?></small><?php endif; ?></label>
        <?php endforeach; ?>
        <h2>ব্যাংক হিসাব</h2>
        <p class="muted small">কোনো হিসাব সরাতে চাইলে তার “হিসাব নম্বর” ঘর খালি করে সংরক্ষণ করুন।</p>
        <?php $banks = $S['banks'] ?? []; $banks[] = ['id' => '', 'title' => '', 'account' => '', 'type' => '', 'bank' => ''];
        foreach ($banks as $i => $b): ?>
          <fieldset class="bank"><legend><?= $b['account'] ? 'হিসাব ' . bn_digits((string)($i + 1)) : '＋ নতুন হিসাব যোগ করুন' ?></legend>
            <input type="hidden" name="bank[<?= $i ?>][id]" value="<?= e($b['id']) ?>">
            <label>হিসাবের নাম<input name="bank[<?= $i ?>][title]" value="<?= e($b['title']) ?>"></label>
            <label>হিসাব নম্বর<input name="bank[<?= $i ?>][account]" value="<?= e($b['account']) ?>"></label>
            <label>ধরন<input name="bank[<?= $i ?>][type]" value="<?= e($b['type']) ?>" placeholder="যেমন: সঞ্চয়ী (S/B)"></label>
            <label>ব্যাংক ও শাখা<input name="bank[<?= $i ?>][bank]" value="<?= e($b['bank']) ?>"></label>
          </fieldset>
        <?php endforeach; ?>
        <div class="form-actions"><button class="btn">সংরক্ষণ করুন</button></div>
      </form>
    <?php }, $user);
    exit;
}

/* ===== own password ===== */
if ($p === 'password') {
    $err = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $old = (string)($_POST['old'] ?? ''); $new = (string)($_POST['new'] ?? ''); $new2 = (string)($_POST['new2'] ?? '');
        if (!password_verify($old, $user['hash'])) $err = 'বর্তমান পাসওয়ার্ড সঠিক নয়।';
        elseif (mb_strlen($new) < 8) $err = 'নতুন পাসওয়ার্ড কমপক্ষে ৮ অক্ষরের হতে হবে।';
        elseif ($new !== $new2) $err = 'দুটি নতুন পাসওয়ার্ড মেলেনি।';
        else {
            $us = load_users();
            foreach ($us as &$u) if ($u['id'] === $user['id']) $u['hash'] = password_hash($new, PASSWORD_DEFAULT);
            unset($u);
            save_users($us); log_action('নিজের পাসওয়ার্ড বদলেছেন');
            redirect([], 'পাসওয়ার্ড বদলানো হয়েছে।');
        }
    }
    layout('পাসওয়ার্ড বদলান', function () use ($err) { ?>
      <form method="post" class="card narrow form">
        <h1>পাসওয়ার্ড বদলান</h1>
        <?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>
        <?= csrf_field() ?>
        <label>বর্তমান পাসওয়ার্ড<input type="password" name="old" required autocomplete="current-password"></label>
        <label>নতুন পাসওয়ার্ড<input type="password" name="new" required minlength="8" autocomplete="new-password"></label>
        <label>নতুন পাসওয়ার্ড আবার<input type="password" name="new2" required minlength="8" autocomplete="new-password"></label>
        <button class="btn">বদলান</button>
      </form>
    <?php }, $user);
    exit;
}

/* ===== users (super admin only) ===== */
if ($p === 'users') {
    if (!is_super($user)) redirect([], 'এই পাতাটি শুধু প্রধান অ্যাডমিনের জন্য।', 'err');
    $us = load_users();
    $err = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $act = $_POST['act'] ?? '';
        if ($act === 'add') {
            $un = trim((string)($_POST['username'] ?? '')); $nm = trim((string)($_POST['name'] ?? '')); $pw = (string)($_POST['password'] ?? '');
            $role = ($_POST['role'] ?? '') === 'super' ? 'super' : 'editor';
            if (!preg_match('/^[a-zA-Z0-9_.]{3,30}$/', $un)) $err = 'ইউজারনেম ৩-৩০ অক্ষরের ইংরেজি অক্ষর/সংখ্যা হতে হবে।';
            elseif (array_filter($us, fn($u) => strcasecmp($u['username'], $un) === 0)) $err = 'এই ইউজারনেম আগে থেকেই আছে।';
            elseif ($nm === '') $err = 'নাম লিখুন।';
            elseif (mb_strlen($pw) < 8) $err = 'পাসওয়ার্ড কমপক্ষে ৮ অক্ষরের হতে হবে।';
            else {
                $us[] = ['id' => new_id(), 'username' => $un, 'name' => $nm, 'role' => $role, 'hash' => password_hash($pw, PASSWORD_DEFAULT), 'created' => date('Y-m-d')];
                save_users($us); log_action('নতুন ব্যবহারকারী যোগ করেছেন: ' . $nm);
                redirect(['p' => 'users'], "“{$nm}” যোগ হয়েছে। ইউজারনেম ও পাসওয়ার্ড তাঁকে জানিয়ে দিন।");
            }
        } elseif ($act === 'invite_new') {
            new_invite_code(); log_action('নতুন আমন্ত্রণ কোড তৈরি করেছেন');
            redirect(['p' => 'users'], 'নতুন আমন্ত্রণ কোড তৈরি হয়েছে। পুরনো কোড আর কাজ করবে না।');
        } elseif ($act === 'invite_off') {
            $inv = invite(); $inv['on'] = false; file_put_contents(data_dir() . '/invite.json', json_encode($inv), LOCK_EX);
            log_action('নতুন অ্যাকাউন্ট খোলা বন্ধ করেছেন');
            redirect(['p' => 'users'], 'নতুন অ্যাকাউন্ট খোলা বন্ধ করা হয়েছে।');
        } else {
            $id = (string)($_POST['id'] ?? '');
            $target = null; foreach ($us as $u) if ($u['id'] === $id) $target = $u;
            if (!$target) redirect(['p' => 'users']);
            if ($act === 'approve') {
                foreach ($us as &$u) if ($u['id'] === $id) $u['status'] = 'active';
                unset($u);
                save_users($us); log_action('অ্যাকাউন্ট অনুমোদন করেছেন: ' . $target['name'] . ' (' . $target['username'] . ')');
                redirect(['p' => 'users'], "“{$target['name']}”-এর অ্যাকাউন্ট চালু হয়েছে। এখন তিনি লগইন করতে পারবেন।");
            }
            if ($act === 'delete') {
                if ($id === $user['id']) redirect(['p' => 'users'], 'নিজেকে মুছে ফেলা যাবে না।', 'err');
                $us = array_filter($us, fn($u) => $u['id'] !== $id);
                save_users($us); log_action((($target['status'] ?? '') === 'pending' ? 'আবেদন বাতিল করেছেন: ' : 'ব্যবহারকারী মুছেছেন: ') . $target['name']);
                redirect(['p' => 'users'], 'মুছে ফেলা হয়েছে।');
            }
            if ($act === 'reset') {
                $pw = (string)($_POST['password'] ?? '');
                if (mb_strlen($pw) < 8) redirect(['p' => 'users'], 'নতুন পাসওয়ার্ড কমপক্ষে ৮ অক্ষরের হতে হবে।', 'err');
                foreach ($us as &$u) if ($u['id'] === $id) $u['hash'] = password_hash($pw, PASSWORD_DEFAULT);
                unset($u);
                save_users($us); log_action('পাসওয়ার্ড রিসেট করেছেন: ' . $target['name']);
                redirect(['p' => 'users'], "“{$target['name']}”-এর নতুন পাসওয়ার্ড সেট হয়েছে।");
            }
            if ($act === 'role') {
                if ($id === $user['id']) redirect(['p' => 'users'], 'নিজের ভূমিকা বদলানো যাবে না।', 'err');
                foreach ($us as &$u) if ($u['id'] === $id) $u['role'] = $u['role'] === 'super' ? 'editor' : 'super';
                unset($u);
                save_users($us); log_action('ভূমিকা বদলেছেন: ' . $target['name']);
                redirect(['p' => 'users'], 'ভূমিকা বদলানো হয়েছে।');
            }
        }
    }
    layout('ব্যবহারকারী', function () use ($us, $user, $err) {
        $inv = invite();
        $host = ($_SERVER['HTTP_HOST'] ?? 'basudevyogashram.org');
        $link = 'https://' . $host . '/admin/?p=register&code=' . rawurlencode($inv['code'] ?? '');
        $pending = array_values(array_filter($us, fn($u) => ($u['status'] ?? 'active') === 'pending'));
        $active = array_values(array_filter($us, fn($u) => ($u['status'] ?? 'active') !== 'pending')); ?>
      <h1>👤 ব্যবহারকারী</h1>
      <p class="muted"><b>প্রধান অ্যাডমিন</b> সবকিছু করতে পারেন, অ্যাকাউন্ট অনুমোদন ও পাসওয়ার্ড রিসেটসহ। <b>সম্পাদক</b> খবর, উৎসব, ভূমিদাতা, ছবি ও যোগাযোগের তথ্য বদলাতে পারেন। পাসওয়ার্ড গোপনভাবে সংরক্ষিত থাকে, কেউ দেখতে পারেন না; তবে প্রধান অ্যাডমিন নতুন পাসওয়ার্ড সেট করে দিতে পারেন।</p>
      <?php if ($err): ?><div class="flash err"><?= e($err) ?></div><?php endif; ?>

      <section class="card invite">
        <h2>আশ্রমের লোকদের নিজেদের অ্যাকাউন্ট খুলতে দিন</h2>
        <?php if (!empty($inv['on']) && !empty($inv['code'])): ?>
          <p>নিচের লিংকটি শুধু আশ্রমের দায়িত্বপ্রাপ্তদের হোয়াটসঅ্যাপে পাঠান। তাঁরা নিজেরাই ইউজারনেম ও পাসওয়ার্ড বেছে আবেদন করবেন, আর আবেদন নিচে “অনুমোদনের অপেক্ষায়” তালিকায় আসবে। আপনি অনুমোদন দিলেই অ্যাকাউন্ট চালু হবে।</p>
          <div class="inv-row"><code id="invLink"><?= e($link) ?></code><button type="button" class="btn small" id="copyInv">লিংক কপি করুন</button></div>
          <p class="small muted">আমন্ত্রণ কোড: <b><?= e($inv['code']) ?></b> · লিংক কারো হাতে ভুলভাবে চলে গেলে নতুন কোড তৈরি করুন, পুরনোটা তখন আর কাজ করবে না।</p>
          <div class="form-actions">
            <form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="invite_new"><button class="btn small ghost">নতুন কোড তৈরি করুন</button></form>
            <form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="invite_off"><button class="btn small danger">নতুন অ্যাকাউন্ট খোলা বন্ধ করুন</button></form>
          </div>
        <?php else: ?>
          <p>এখন কেউ নিজে থেকে অ্যাকাউন্ট খুলতে পারবেন না। চালু করলে একটি আমন্ত্রণ লিংক পাবেন, সেটি আশ্রমের লোকদের পাঠাবেন।</p>
          <form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="invite_new"><button class="btn">আমন্ত্রণ লিংক চালু করুন</button></form>
        <?php endif; ?>
      </section>

      <?php if ($pending): ?>
      <h2>⏳ অনুমোদনের অপেক্ষায় (<?= bn_digits((string)count($pending)) ?>)</h2>
      <ul class="items">
      <?php foreach ($pending as $u): ?>
        <li class="item user pend">
          <div class="it-txt"><b><?= e($u['name']) ?></b><span>ইউজারনেম: <?= e($u['username']) ?><?= !empty($u['phone']) ? ' · মোবাইল: ' . e($u['phone']) : '' ?> · আবেদন: <?= e(bn_digits($u['created'] ?? '')) ?></span></div>
          <div class="acts">
            <form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="approve"><input type="hidden" name="id" value="<?= e($u['id']) ?>"><button class="btn small">অনুমোদন দিন</button></form>
            <form method="post" data-confirm="“<?= e($u['name']) ?>”-এর আবেদন বাতিল করবেন?"><?= csrf_field() ?><input type="hidden" name="act" value="delete"><input type="hidden" name="id" value="<?= e($u['id']) ?>"><button class="btn small danger">বাতিল</button></form>
          </div>
        </li>
      <?php endforeach; ?>
      </ul>
      <?php endif; ?>

      <h2>সক্রিয় ব্যবহারকারী (<?= bn_digits((string)count($active)) ?>)</h2>
      <ul class="items">
      <?php foreach ($active as $u): ?>
        <li class="item user">
          <div class="it-txt"><b><?= e($u['name']) ?><?= $u['id'] === $user['id'] ? ' (আপনি)' : '' ?></b><span>ইউজারনেম: <?= e($u['username']) ?> · <?= $u['role'] === 'super' ? 'প্রধান অ্যাডমিন' : 'সম্পাদক' ?><?= !empty($u['phone']) ? ' · মোবাইল: ' . e($u['phone']) : '' ?></span></div>
          <?php if ($u['id'] !== $user['id']): ?>
          <div class="acts wrap-acts">
            <form method="post" class="reset"><?= csrf_field() ?><input type="hidden" name="act" value="reset"><input type="hidden" name="id" value="<?= e($u['id']) ?>"><input name="password" placeholder="নতুন পাসওয়ার্ড" minlength="8" required autocomplete="new-password"><button class="btn small ghost">পাসওয়ার্ড রিসেট</button></form>
            <form method="post"><?= csrf_field() ?><input type="hidden" name="act" value="role"><input type="hidden" name="id" value="<?= e($u['id']) ?>"><button class="btn small ghost"><?= $u['role'] === 'super' ? 'সম্পাদক করুন' : 'প্রধান অ্যাডমিন করুন' ?></button></form>
            <form method="post" data-confirm="“<?= e($u['name']) ?>”-কে মুছে ফেলবেন?"><?= csrf_field() ?><input type="hidden" name="act" value="delete"><input type="hidden" name="id" value="<?= e($u['id']) ?>"><button class="btn small danger">মুছুন</button></form>
          </div>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
      </ul>

      <details class="card">
        <summary><b>নিজে হাতে ব্যবহারকারী যোগ করুন</b> (আমন্ত্রণ লিংক ছাড়া)</summary>
        <form method="post" class="form" style="margin-top:14px">
          <?= csrf_field() ?><input type="hidden" name="act" value="add">
          <label>নাম<input name="name" required></label>
          <label>ইউজারনেম (ইংরেজিতে)<input name="username" required pattern="[a-zA-Z0-9_.]{3,30}" autocomplete="off"></label>
          <label>পাসওয়ার্ড (কমপক্ষে ৮ অক্ষর)<input name="password" required minlength="8" autocomplete="new-password"></label>
          <label>ভূমিকা<select name="role"><option value="editor">সম্পাদক</option><option value="super">প্রধান অ্যাডমিন</option></select></label>
          <div class="form-actions"><button class="btn">যোগ করুন</button></div>
        </form>
      </details>
      <script>
      (function(){var b=document.getElementById('copyInv');if(!b)return;b.addEventListener('click',function(){var t=document.getElementById('invLink').textContent;
        function sel(){var r=document.createRange();r.selectNodeContents(document.getElementById('invLink'));var s=getSelection();s.removeAllRanges();s.addRange(r);b.textContent='লিংক সিলেক্ট হয়েছে, কপি করুন';}
        try{navigator.clipboard.writeText(t).then(function(){b.textContent='কপি হয়েছে ✓';},sel);}catch(e){sel();}});})();
      </script>
    <?php }, $user);
    exit;
}

redirect();
