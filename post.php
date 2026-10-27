<?php
require __DIR__ . '/inc/lib.php';
require __DIR__ . '/inc/page.php';
$id = (string)($_GET['id'] ?? '');
$post = null;
foreach (load('posts') as $p) if ($p['id'] === $id && !empty($p['published'])) $post = $p;
if (!$post) { http_response_code(404); page_head('খবর পাওয়া যায়নি'); echo '<div class="wrap article"><h1>খবরটি পাওয়া যায়নি</h1><p><a class="back" href="./">← মূল পাতায় ফিরুন</a></p></div>'; page_foot(); exit; }
page_head($post['title'] . ' · শ্রীশ্রী বাসুদেব যোগাশ্রম', excerpt($post['body']));
?>
<article class="wrap article">
  <a class="back" href="./#khobor">← সব খবর</a>
  <p class="nc-date"><?= e(bn_date($post['date'])) ?></p>
  <h1><?= e($post['title']) ?></h1>
  <?php if (!empty($post['image'])): ?><img class="lead" src="<?= e(img_url($post['image'])) ?>" alt=""><?php endif; ?>
  <?= paragraphs($post['body']) ?>
</article>
<?php page_foot();
