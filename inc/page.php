<?php
function page_head(string $title, string $desc = ''): void { ?>
<!doctype html>
<html lang="bn">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($title) ?></title>
<?php if ($desc): ?><meta name="description" content="<?= e($desc) ?>"><?php endif; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Tiro+Bangla&family=Hind+Siliguri:wght@400;500;600&display=swap">
<link rel="stylesheet" href="assets/site.css?v=<?= @filemtime(__DIR__ . '/../assets/site.css') ?>">
<style>body{margin:0}</style>
</head>
<body>
<div class="chant">॥ হরি ওঁ ॥ জয় গুরু ॥ জয় সনাতন ধর্ম ॥</div>
<header><div class="wrap">
  <a class="brand" href="./"><span class="om" aria-hidden="true">ॐ</span><span><b>শ্রীশ্রী বাসুদেব যোগাশ্রম</b><small>ও বাসুদেব অনাথ আশ্রম · স্থাপিত ১৯৬৫</small></span></a>
  <nav data-open="true"><ul><li><a href="./">মূল পাতা</a></li><li><a href="news.php">সব খবর</a></li><li><a href="./#contact">যোগাযোগ</a></li></ul></nav>
</div></header>
<main>
<?php }
function page_foot(): void { ?>
</main>
<footer><div class="wrap"><span>© শ্রীশ্রী বাসুদেব যোগাশ্রম ও বাসুদেব অনাথ আশ্রম, নেহালপুর</span><span>basudevyogashram.org · ॥ জয় গুরু ॥</span></div></footer>
</body>
</html>
<?php }
