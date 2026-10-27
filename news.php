<?php
require __DIR__ . '/inc/lib.php';
require __DIR__ . '/inc/page.php';
$posts = array_values(array_filter(load('posts'), fn($p) => !empty($p['published'])));
usort($posts, fn($a, $b) => strcmp($b['date'], $a['date']));
page_head('খবর ও নোটিশ · শ্রীশ্রী বাসুদেব যোগাশ্রম');
?>
<section class="khobor"><div class="wrap">
  <div class="sec-head"><p class="eyebrow">খবর ও নোটিশ</p><h2>আশ্রমের সব খবর</h2></div>
  <?php if (!$posts): ?><p>এখনো কোনো খবর প্রকাশিত হয়নি।</p><?php endif; ?>
  <div class="news-grid">
  <?php foreach ($posts as $p): ?>
    <article class="news-card">
      <?php if (!empty($p['image'])): ?><a href="post.php?id=<?= e($p['id']) ?>"><img src="<?= e(img_url($p['image'])) ?>" alt="" loading="lazy"></a><?php endif; ?>
      <div class="nc-body"><p class="nc-date"><?= e(bn_date($p['date'])) ?></p><h3><a href="post.php?id=<?= e($p['id']) ?>"><?= e($p['title']) ?></a></h3><p><?= e(excerpt($p['body'])) ?></p><a class="nc-more" href="post.php?id=<?= e($p['id']) ?>">বিস্তারিত পড়ুন →</a></div>
    </article>
  <?php endforeach; ?>
  </div>
</div></section>
<?php page_foot();
