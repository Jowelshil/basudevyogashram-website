<?php
require __DIR__ . '/inc/lib.php';
$S = load('settings');
$events = load('events');
usort($events, fn($a, $b) => strcmp($a['date'], $b['date']));
$gallery = load('gallery');
$donors = load('donors');
$posts = array_values(array_filter(load('posts'), fn($p) => !empty($p['published'])));
usort($posts, fn($a, $b) => strcmp($b['date'], $a['date']));
$kinds = ['chandi' => ['c', 'চণ্ডীযজ্ঞ'], 'guru' => ['g', 'গুরু তিথি'], 'special' => ['s', 'বিশেষ তিথি'], 'utsav' => ['g', 'উৎসব']];
?><!doctype html>
<html lang="bn">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>শ্রীশ্রী বাসুদেব যোগাশ্রম</title>
<meta name="description" content="শ্রীশ্রী বাসুদেব যোগাশ্রম ও বাসুদেব অনাথ আশ্রম, নেহালপুর, নন্দীরহাট, হাটহাজারী, চট্টগ্রাম। স্থাপিত ১৯৬৫।">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Tiro+Bangla&family=Hind+Siliguri:wght@400;500;600&display=swap">
<link rel="stylesheet" href="assets/site.css?v=<?= @filemtime(__DIR__.'/assets/site.css') ?>">

<style>body{margin:0}</style>
</head>
<body>
<div class="chant">॥ হরি ওঁ ॥ জয় গুরু ॥ জয় সনাতন ধর্ম ॥</div>

<header>
  <div class="wrap">
    <a class="brand" href="#top">
      <span class="om" aria-hidden="true">ॐ</span>
      <span><b>শ্রীশ্রী বাসুদেব যোগাশ্রম</b><small>ও বাসুদেব অনাথ আশ্রম · স্থাপিত ১৯৬৫</small></span>
    </a>
    <button class="menu-btn" id="menuBtn" aria-expanded="false" aria-controls="mainNav">মেনু</button>
    <nav id="mainNav" data-open="false">
      <ul>
        <?php if ($posts): ?><li><a href="#khobor">খবর</a></li><?php endif; ?>
        <li><a href="#about">পরিচিতি</a></li>
        <li><a href="#itihas">ইতিহাস</a></li>
        <li><a href="#shakha">শাখা</a></li>
        <li><a href="#parampara">পরম্পরা</a></li>
        <li><a href="#seva">সেবা</a></li>
        <li><a href="#anath">অনাথ আশ্রম</a></li>
        <li><a href="#shubhodin">শুভদিন বুকিং</a></li>
        <li><a href="#utsav">উৎসব</a></li>
        <li><a href="#unnoyon">উন্নয়ন</a></li>
        <li><a href="#bhumidata">ভূমিদাতা</a></li>
        <li><a href="#sadasya">সদস্য</a></li>
        <li><a href="#donate">দান</a></li>
        <li><a href="#gallery">ছবি</a></li>
        <li><a href="<?= e($S['youtube']) ?>" target="_blank" rel="noopener">ভিডিও ↗</a></li>
        <li><a href="#contact">যোগাযোগ</a></li>
      </ul>
    </nav>
  </div>
</header>

<main id="top">
  <div class="hero">
    <img src="images/ashram-dusk.jpg" alt="সন্ধ্যায় বাসুদেব যোগাশ্রম: মন্দিরের চূড়া, আলো জ্বলা খিলান-বারান্দা ও পুকুরের প্রতিবিম্ব">
    <div class="hero-copy"><div class="wrap"><div class="hero-panel">
      <h1>শ্রীশ্রী বাসুদেব যোগাশ্রম<span>ও বাসুদেব অনাথ আশ্রম</span></h1>
      <p class="meta">নেহালপুর, নন্দীরহাট, হাটহাজারী, চট্টগ্রাম · স্থাপিত ১৯৬৫ · রেজি: নং-১২১৮২ চট্ট/হাট/২৬</p>
      <div class="cta">
        <a class="btn btn-primary" href="#donate">সেবায় দান করুন</a>
        <a class="btn btn-ghost" href="#contact">আশ্রমে আসুন</a>
      </div>
    </div></div></div>
  </div>

  <section id="karmayagya" class="column"><div class="wrap">
    <article class="col-grid">
      <aside class="col-side">
        <div class="col-frame"><img src="images/guru-biplob-chaitanya.jpg" alt="শ্রীমৎ বিপ্লব চৈতন্য ব্রহ্মচারী মহারাজ"></div>
        <p class="col-name">শ্রীমৎ বিপ্লব চৈতন্য ব্রহ্মচারী মহারাজ</p>
        <p class="col-role">অধ্যক্ষ, শ্রীশ্রী বাসুদেব যোগাশ্রম ও শ্রীশ্রী বাসুদেব অনাথ আশ্রম</p>
      </aside>
      <div class="col-body">
        <p class="eyebrow">গুরুদেবের কর্মযজ্ঞ</p>
        <h2>এক সন্ন্যাসীর ব্রত, শত শিশুর আশ্রয়</h2>
        <p>১৯৬৫ সালে নেহালপুরের এক নির্জন পুকুরপাড়ে মাত্র দুই কড়া জমিতে স্বামী বাসুদেবানন্দ গিরি মহারাজ যে “অযাচক আশ্রম” কুটির গড়েছিলেন, আজ তা হাজারো ভক্তের তীর্থধাম। এই ধামের বর্তমান রূপের পেছনে রয়েছে এক নিরলস সাধকের দুই দশকের তপস্যা ও সেবা। ২০০৪ সালে গুরুদেব স্বামী কৃষ্ণানন্দ গিরি মহারাজ অসংখ্য ভক্তের সম্মুখে যাঁর হাতে আশ্রমের দায়িত্বভার অর্পণ করেছিলেন, তিনি শ্রীমৎ বিপ্লব চৈতন্য ব্রহ্মচারী মহারাজ।</p>
        <p>দায়িত্ব গ্রহণের পর গুরুদেব প্রথমেই হাত বাড়িয়েছিলেন সমাজের সবচেয়ে অসহায় মানুষদের দিকে, পিতৃ-মাতৃহীন শিশুদের দিকে। সেই বছরই শুরু হয় বাসুদেব অনাথ আশ্রম। আজ প্রায় ৪৫০ থেকে ৫০০ বিদ্যার্থী এখানে থাকে। তাদের আহার, বাসস্থান, চিকিৎসা ও লেখাপড়াসহ সব মৌলিক চাহিদার দায়িত্ব আশ্রমের। গুরুদেব তাদের কাছে কেবল অধ্যক্ষ নন, পিতৃসুলভ আশ্রয়। প্রাত্যহিক প্রার্থনা, যোগাভ্যাস ও বিদ্যালয়ের পাঠের মধ্য দিয়ে প্রতিদিন গড়ে উঠছে একেকটি সৎ ও চরিত্রবান জীবন।</p>
        <p>শিশুদের শিক্ষার জন্য গড়ে উঠেছে সরকার অনুমোদিত বাসুদেব স্কুল এন্ড কলেজ, যেখানে আশ্রমের ছেলেরা বিনা খরচে পড়াশোনা করে। পাশাপাশি চলছে নিত্যপূজা, প্রতি অমাবস্যায় রাত্রিকালীন শ্রীশ্রী চণ্ডীযজ্ঞ, গুরুদেবদের আবির্ভাব ও তিরোধান তিথিতে বিশ্বশান্তি গীতাযজ্ঞ, সাধু সম্মেলন, বস্ত্র বিতরণ ও বিনামূল্যে চিকিৎসা সেবা। নেহালপুরের প্রধান আশ্রমের সাথে মীরসরাইয়ের গোপালপুরে আদি আশ্রমটিও একই পরম্পরায় পরিচালিত হচ্ছে।</p>
        <p>এই বিশাল কর্মযজ্ঞ চলছে ভক্ত-শিষ্যদের ভালোবাসা ও দানে। কেউ দিয়েছেন ভূমি, কেউ মাসিক প্রণামী, কেউ নিজের শুভদিনে শিশুদের মুখে তুলে দিয়েছেন এক বেলার অন্ন। এখন নির্মাণ চলছে প্রসাদ ভবন ও স্কুল এন্ড কলেজের নতুন ভবনের। আর গুরুদেবের চোখে ভবিষ্যতের স্বপ্ন আরও বড়: বাসুদেব মেডিকেল কলেজ, বাসুদেব টেকনিক্যাল কলেজ এবং সাধু-সন্ত ও ভক্তদের মহামিলন বাসুদেব কুম্ভ মেলা।</p>
        <p class="col-close">গুরুর কৃপা আর ভক্তের সেবা, এই দুইয়ের মিলনেই বাসুদেব ধাম আজ এক জীবন্ত তীর্থ। এই মহান কর্মযজ্ঞে আমরা সবাই অংশীদার হতে পারি।</p>
      </div>
    </article>

    <div class="visit">
      <div class="visit-text">
        <h3>আশ্রমে আসুন, নিজের চোখে দেখুন</h3>
        <p>গুরুধামের প্রকৃত অনুভূতি কোনো লেখা বা ছবিতে ধরা যায় না। আশ্রমের দ্বার সবসময় সবার জন্য খোলা। যখনই সময় পান, সপরিবারে চলে আসুন নেহালপুরে। গুরুদেবের দর্শন নিন, সমাধিক্ষেত্রে প্রণাম জানান, পুণ্য পুকুরঘাটে কিছুক্ষণ বসুন, আর আশ্রমের শিশুদের সাথে কাটান কিছুটা সময়। তাদের হাসিমুখই এই আশ্রমের সবচেয়ে বড় পরিচয়। অমাবস্যার রাতের চণ্ডীযজ্ঞ কিংবা বার্ষিক উৎসবের দিনগুলোতে এলে পাবেন এক অন্যরকম আধ্যাত্মিক আবহ।</p>
        <p class="visit-open">🕉 আশ্রম সবসময় সবার জন্য উন্মুক্ত</p>
        <p class="visit-addr"><b>ঠিকানা:</b> শ্রীশ্রী বাসুদেব যোগাশ্রম, নেহালপুর, নন্দীরহাট, হাটহাজারী, চট্টগ্রাম · <b>ফোন:</b> <?= e($S['phone1']) ?></p>
      </div>
      <div class="visit-actions">
        <a class="btn btn-primary" href="<?= e($S['maps']) ?>" target="_blank" rel="noopener">পথ দেখুন (গুগল ম্যাপ) ↗</a>
        <a class="btn btn-outline" href="#utsav">উৎসবসূচি দেখুন</a>
      </div>
    </div>
  </div></section>

<?php if ($posts): ?>
  <section id="khobor" class="khobor"><div class="wrap">
    <div class="sec-head">
      <p class="eyebrow">খবর ও নোটিশ</p>
      <h2>আশ্রমের সর্বশেষ খবর</h2>
    </div>
    <div class="news-grid">
<?php foreach (array_slice($posts, 0, 6) as $p): ?>
      <article class="news-card">
        <?php if (!empty($p['image'])): ?><a href="post.php?id=<?= e($p['id']) ?>"><img src="<?= e(img_url($p['image'])) ?>" alt="" loading="lazy"></a><?php endif; ?>
        <div class="nc-body">
          <p class="nc-date"><?= e(bn_date($p['date'])) ?></p>
          <h3><a href="post.php?id=<?= e($p['id']) ?>"><?= e($p['title']) ?></a></h3>
          <p><?= e(excerpt($p['body'])) ?></p>
          <a class="nc-more" href="post.php?id=<?= e($p['id']) ?>">বিস্তারিত পড়ুন →</a>
        </div>
      </article>
<?php endforeach; ?>
    </div>
    <?php if (count($posts) > 6): ?><p class="news-all"><a href="news.php">সব খবর দেখুন →</a></p><?php endif; ?>
  </div></section>
<?php endif; ?>

  <section id="about"><div class="wrap about-grid">
    <div class="about-text">
      <div class="sec-head" style="margin-bottom:6px">
        <p class="eyebrow">আশ্রম পরিচিতি</p>
        <h2>সাধনা, সেবা ও শিক্ষার এক পুণ্যধাম</h2>
      </div>
      <p>বিশ্বপরিব্রাজক অষ্টাঙ্গ যোগক্রিয়াবিদ শ্রীমৎ স্বামী বাসুদেবানন্দ গিরি মহারাজ ১৯৬৫ সালে নেহালপুর গ্রামের নির্জন পল্লীতে এক পুকুর পাড়ে মাত্র ২ কড়া জমিতে একটি কুটির নির্মাণ করে কঠোর তপস্যায় মগ্ন হন। নাম দেন <b>“অযাচক আশ্রম”</b> — না চাইতেই যা পাওয়া যায় তাতে তুষ্ট থাকা। সেই কুটিরেই আজ তাঁর সমাধিক্ষেত্র, আর তাকে ঘিরে গড়ে উঠেছে শ্রীশ্রী বাসুদেব যোগাশ্রম।</p>
      <p>শিষ্য স্বামী কৃষ্ণানন্দ গিরি মহারাজ আশ্রমকে ভক্তের মানস তীর্থে পরিণত করেন এবং প্রতি অমাবস্যার রাতে শ্রীশ্রী চণ্ডীযজ্ঞের প্রচলন করেন। ২০০৪ সাল থেকে বর্তমান অধ্যক্ষের হাতে শুরু হওয়া বাসুদেব অনাথ আশ্রমে এখন প্রায় ৪৫০–৫০০ অনাথ শিশুর আহার, বাসস্থান, শিক্ষা ও চিকিৎসার সম্পূর্ণ দায়িত্ব আশ্রম বহন করে।</p>
      <p>বর্তমানে আশ্রমের অধ্যক্ষ শ্রীশ্রী বিপ্লব চৈতন্য ব্রহ্মচারী মহারাজ।</p>
    </div>
    <div style="display:grid;gap:20px">
      <div class="facts">
        <div class="fact"><strong>১৯৬৫</strong><span>প্রতিষ্ঠাকাল</span></div>
        <div class="fact"><strong>৪৫০+</strong><span>অনাথ শিশুর আবাস</span></div>
        <div class="fact"><strong>২০০৪</strong><span>অনাথ আশ্রমের সূচনা</span></div>
        <div class="fact"><strong>বিনা খরচে</strong><span>শিক্ষা, আহার ও চিকিৎসা</span></div>
      </div>
      <blockquote>“এ অসহায় অনাথ ছেলেদের যখনই যা কিছু সেটা আমিই পাব, আমাকেই দেয়া হবে। অনাথ ছেলেদের মঙ্গল চিন্তা করলে নিজের ছেলের মঙ্গল অবিসম্ভাবী।”<cite>আশ্রমের বাণী</cite></blockquote>
    </div>
  </div></section>

  <section id="itihas"><div class="wrap">
    <div class="sec-head">
      <p class="eyebrow">আশ্রমের ইতিহাস</p>
      <h2>পর্ণকুটির থেকে সনাতনী তীর্থস্থান</h2>
    </div>
    <ol class="timeline">
      <li><span class="when">আবির্ভাব</span><div><h3>মুরাদনগর, মীরসরাই</h3><p>গৌর দোল পূর্ণিমা তিথিতে ৭নং কাটাছড়া ইউনিয়নের মুরাদনগর গ্রামে শ্রীমৎ স্বামী বাসুদেবানন্দ গিরি মহারাজের আবির্ভাব। কেদার, বদ্রী, হরিদ্বার, হৃষীকেশ, নেপাল, ভুটান ও বার্মাসহ অসংখ্য তীর্থে সাধনা করেন।</p></div></li>
      <li><span class="when">প্রথম আশ্রম</span><div><h3>গোপালপুর, মীরসরাই</h3><p>জন্মভূমির কাছে গোপালপুর গ্রামে গ্রামবাসীর সহযোগিতায় একটি পর্ণকুটির স্থাপন, যা বর্তমানে শ্রীশ্রী বাসুদেব যোগাশ্রম, গোপালপুর।</p></div></li>
      <li><span class="when">১৯৬৫</span><div><h3>নেহালপুরে অযাচক আশ্রম</h3><p>নির্জন সাধনার জন্য নেহালপুরে পুকুর পাড়ে ২ কড়া জমিতে কুটির নির্মাণ ও “অযাচক আশ্রম” নামকরণ। এবার সঙ্গে ছিলেন তরুণ শিষ্য স্বামী কৃষ্ণানন্দ গিরি।</p></div></li>
      <li><span class="when">১৯৮৫</span><div><h3>মহাসমাধি</h3><p>শ্রীকৃষ্ণ জন্মাষ্টমীর পরের দশমী তিথিতে স্বামী বাসুদেবানন্দ গিরি মহারাজের মহাসমাধি। যে কুটিরে দেহত্যাগ করেন, সেখানেই তাঁর সমাধিক্ষেত্র।</p></div></li>
      <li><span class="when">১৯৮৫–২০০৪</span><div><h3>কৃষ্ণানন্দ গিরি মহারাজের যুগ</h3><p>আশ্রমকে ভক্তের মানস তীর্থে পরিণত করা এবং প্রতি অমাবস্যার রাতে শ্রীশ্রী চণ্ডীযজ্ঞের প্রচলন।</p></div></li>
      <li><span class="when">৯ সেপ্টেম্বর ২০০৪</span><div><h3>দায়িত্ব অর্পণ</h3><p>অসংখ্য ভক্তের সম্মুখে শ্রীমৎ বিপ্লব চৈতন্য ব্রহ্মচারী মহারাজের ওপর আশ্রমের দায়িত্বভার অর্পণ। মাত্র ১০ দিন পর, ২০ সেপ্টেম্বর ২০০৪, স্বামী কৃষ্ণানন্দ দিব্যধামবাসী হন।</p></div></li>
      <li><span class="when">২০০৪–বর্তমান</span><div><h3>সেবার বিস্তার</h3><p>বাসুদেব অনাথ আশ্রম ও বাসুদেব স্কুল এন্ড কলেজ প্রতিষ্ঠা। সমাধিক্ষেত্র তথা সাধনপীঠকে ঘিরে গড়ে উঠেছে বিরাট সনাতনী তীর্থস্থান।</p></div></li>
    </ol>
    <blockquote class="hist-quote">“এই মাতৃস্তব শ্রীশ্রী চণ্ডীযজ্ঞ অশ্বমেধ, রাজসূয় যজ্ঞের চেয়েও সমধিক।”<cite>শ্রীমৎ স্বামী কৃষ্ণানন্দ গিরি মহারাজ</cite></blockquote>
    <p class="src">সূত্র: “উপাসনা পদ্ধতি”, গ্রন্থকার শ্রীমৎ বিপ্লব চৈতন্য ব্রহ্মচারী মহারাজ</p>
  </div></section>

  <section id="shakha" class="shakha"><div class="wrap">
    <div class="sec-head">
      <p class="eyebrow">আমাদের শাখা</p>
      <h2>শ্রীশ্রী বাসুদেব যোগাশ্রমের দুই ধাম</h2>
      <p class="lede">স্বামী বাসুদেবানন্দ গিরি মহারাজের হাতে গড়া দুটি আশ্রম, একই গুরু পরম্পরায় পরিচালিত।</p>
    </div>
    <div class="branches">
      <article class="branch main">
        <img src="images/ashram-pond-hall.jpg" alt="নেহালপুর আশ্রমের পুকুরঘাট ও বারান্দা" loading="lazy">
        <div class="b-body">
          <p class="b-kind">প্রধান শাখা</p>
          <h3>শ্রীশ্রী বাসুদেব যোগাশ্রম, নেহালপুর</h3>
          <p class="b-addr">নেহালপুর, নন্দীরহাট, হাটহাজারী, চট্টগ্রাম</p>
          <p>১৯৬৫ সালে প্রতিষ্ঠিত “অযাচক আশ্রম”। স্বামী বাসুদেবানন্দ গিরি মহারাজের সমাধিক্ষেত্র, মূল মন্দির, বাসুদেব অনাথ আশ্রম ও বাসুদেব স্কুল এন্ড কলেজ এখানেই।</p>
        </div>
      </article>
      <article class="branch adi">
        <div class="b-mark" aria-hidden="true"><span>আদি আশ্রম</span></div>
        <div class="b-body">
          <p class="b-kind">দ্বিতীয় শাখা · আদি আশ্রম</p>
          <h3>শ্রীশ্রী বাসুদেব যোগাশ্রম, গোপালপুর</h3>
          <p class="b-addr">গোপালপুর, মীরসরাই, চট্টগ্রাম</p>
          <p>তীর্থ পর্যটন ও সাধনা শেষে স্বামীজি জন্মভূমি মীরসরাইয়ের পাশে গোপালপুর গ্রামে গ্রামবাসীর সহযোগিতায় প্রথম পর্ণকুটির স্থাপন করেন। এটিই আশ্রমের আদি ভিত্তি।</p>
        </div>
      </article>
    </div>
  </div></section>

  <section id="parampara" class="lineage"><div class="wrap">
    <div class="sec-head">
      <p class="eyebrow">গুরু পরম্পরা</p>
      <h2>যাঁদের আশীর্বাদে এই আশ্রম</h2>
      <p class="lede">প্রতিষ্ঠাতা থেকে বর্তমান অধ্যক্ষ পর্যন্ত গুরু থেকে শিষ্যে দায়িত্বের ধারা।</p>
    </div>
    <div class="gurus">
      <article class="guru">
        <button class="frame frame-btn" type="button" data-bio="bio-basudevananda" aria-label="শ্রীমৎ স্বামী বাসুদেবানন্দ গিরি মহারাজের জীবনী পড়ুন"><img src="images/guru-basudevananda.jpg" alt="শ্রীমৎ স্বামী বাসুদেবানন্দ গিরি মহারাজ"></button>
        <p class="step">প্রতিষ্ঠাতা অধ্যক্ষ</p>
        <h3>শ্রীমৎ স্বামী বাসুদেবানন্দ গিরি মহারাজ</h3>
        <p>গৌর দোল পূর্ণিমা তিথিতে মীরসরাইয়ের মুরাদনগর গ্রামে আবির্ভাব। স্বামী সত্যানন্দ ও স্বামী ব্রহ্মানন্দের চরণে হঠযোগ ও রাজযোগ শিক্ষা। ১৯৮৫ সালে মহাসমাধি।</p>
        <button class="bio-btn" type="button" data-bio="bio-basudevananda">সংক্ষিপ্ত জীবনী পড়ুন →</button>
      </article>
      <article class="guru">
        <button class="frame frame-btn" type="button" data-bio="bio-krishnananda" aria-label="শ্রীমৎ স্বামী কৃষ্ণানন্দ গিরি মহারাজের জীবনী পড়ুন"><img src="images/guru-krishnananda.jpg" alt="শ্রীমৎ স্বামী কৃষ্ণানন্দ গিরি মহারাজ"></button>
        <p class="step">উত্তরসূরি অধ্যক্ষ</p>
        <h3>শ্রীমৎ স্বামী কৃষ্ণানন্দ গিরি মহারাজ</h3>
        <p>রাউজানের কেউটিয়া গ্রামের সন্তান, পিতা তেজেন্দ্র ভট্টাচার্য্য, মাতা সুখদা দেবী। গুরুর প্রাণপ্রিয় শিষ্য ও উত্তরাধিকারী। ২০ সেপ্টেম্বর ২০০৪ দিব্যধামবাসী।</p>
        <button class="bio-btn" type="button" data-bio="bio-krishnananda">সংক্ষিপ্ত জীবনী পড়ুন →</button>
      </article>
      <article class="guru">
        <div class="frame"><img src="images/guru-biplob-chaitanya.jpg" alt="শ্রীশ্রী বিপ্লব চৈতন্য ব্রহ্মচারী মহারাজ"></div>
        <p class="step">বর্তমান অধ্যক্ষ</p>
        <h3>শ্রীশ্রী বিপ্লব চৈতন্য ব্রহ্মচারী মহারাজ</h3>
        <p>৯ সেপ্টেম্বর ২০০৪ গুরুদেব কৃষ্ণানন্দ গিরি মহারাজ ভক্তদের সম্মুখে তাঁর ওপর আশ্রমের দায়িত্বভার অর্পণ করেন। বাসুদেব অনাথ আশ্রমের প্রবর্তক; “উপাসনা পদ্ধতি” গ্রন্থের রচয়িতা।</p>
      </article>
    </div>
  </div></section>

  <section id="seva"><div class="wrap">
    <div class="sec-head">
      <p class="eyebrow">সেবা কার্যক্রম</p>
      <h2>আশ্রমের সেবা ও প্রতিষ্ঠানসমূহ</h2>
    </div>
    <div class="seva-grid">
      <article class="card">
        <img src="images/anath-bhaban.jpg" alt="বাসুদেব অনাথ আশ্রম ভবনের সামনে আশ্রমের শিশুরা">
        <div class="body">
          <h3>বাসুদেব অনাথ আশ্রম</h3>
          <p>২০০৪ সালে গুরু মহারাজের হাতে শুরু হওয়া এই আবাসিক আশ্রমে বর্তমানে প্রায় ৪৫০–৫০০ অনাথ ছেলে থাকে।</p>
          <ul><li>আহার ও বাসস্থান</li><li>শিক্ষা ও বইপত্র</li><li>চিকিৎসা ও যত্ন</li></ul>
          <p><a href="#anath">অনাথ আশ্রম সম্পর্কে বিস্তারিত →</a></p>
        </div>
      </article>
      <article class="card">
        <img src="images/basudeb-school.jpg" alt="বাসুদেব স্কুল এন্ড কলেজের ছাত্ররা">
        <div class="body">
          <h3>বাসুদেব স্কুল এন্ড কলেজ</h3>
          <p>সরকার অনুমোদিত, জাতীয় পাঠ্যক্রম ভিত্তিক বিদ্যালয় (পূর্বনাম বাসুদেব বিদ্যাপীঠ)। আশ্রমের ছেলেরা এখানে বিনা খরচে পড়াশোনা করে।</p>
          <ul><li>দশম শ্রেণি পর্যন্ত</li><li>বিনা বেতনে শিক্ষা</li></ul>
        </div>
      </article>
      <article class="card">
        <img src="images/gita-yagna.jpg" alt="আশ্রমে বিশ্বশান্তি গীতাযজ্ঞ">
        <div class="body">
          <h3>ধর্মীয় ও মানবসেবা</h3>
          <p>উৎসব ও বিশেষ দিনে আশ্রম প্রাঙ্গণে নিয়মিত আয়োজন।</p>
          <ul><li>গুরুপূজা ও শ্রীশ্রী চণ্ডীপাঠ</li><li>বিশ্বশান্তি গীতাযজ্ঞ ও মহতী ধর্মসভা</li><li>সাধু সম্মেলন</li><li>বস্ত্র বিতরণ ও ফ্রি চিকিৎসা সেবা</li></ul>
        </div>
      </article>
    </div>
  </div></section>

  <section id="anath" class="anath"><div class="wrap">
    <div class="anath-head">
      <div class="sec-head">
        <p class="eyebrow">শ্রীশ্রী বাসুদেব অনাথ আশ্রম · Basudeb Orphanage</p>
        <h2>স্নেহ, সেবা ও দিব্য আলোয় জীবন গড়ার মহান ব্রত</h2>
        <p class="lede">শ্রীশ্রী বাসুদেব অনাথ আশ্রম শ্রদ্ধা, ভালোবাসা ও মানবিকতার এক অনন্য তীর্থভূমি। পরমারাধ্য গুরুদেব শ্রীমৎ বিপ্লব চৈতন্য ব্রহ্মচারী মহারাজের ঐশ্বরিক নির্দেশনা, অসীম কৃপা এবং স্নেহের স্পর্শে এই আশ্রমের প্রতিটি বিদ্যার্থী আজ গড়ে উঠছে সৎ, চরিত্রবান ও আদর্শ মানুষ হিসেবে।</p>
        <p class="lede">অনাথ ও বঞ্চিত শিশুদের পরম মমতায় বুকে টেনে নিয়ে তাদের সুশিক্ষায় শিক্ষিত করা এবং সুনাগরিক হিসেবে গড়ে তোলাই পরম গুরুদেবের মূল জীবনব্রত। তাঁর প্রত্যক্ষ তত্ত্বাবধানে ও সুযোগ্য পরিচালনায় আশ্রমে চলছে বিদ্যাদান, নীতিশিক্ষা ও আত্মিক পরিচর্যার এক অপূর্ব মেলবন্ধন।</p>
      </div>
      <img class="anath-photo" src="images/anath-bhaban.jpg" alt="শ্রীশ্রী বাসুদেব অনাথ আশ্রম ভবনের সামনে আশ্রমের শিশুরা" loading="lazy">
    </div>
    <h3 class="sub-h">যেসব মূল স্তম্ভের ওপর বিদ্যার্থীদের লালন-পালন করা হয়</h3>
    <div class="pillars">
      <article class="pillar"><span class="p-num">১</span><h3>সুশিক্ষার আলো</h3><p>সাধারণ শিক্ষার পাশাপাশি ধর্মীয় ও নৈতিক শিক্ষার মেলবন্ধনে শিক্ষার্থীদের মানসিকভাবে শক্তিশালী ও আলোকিত করে তোলা।</p></article>
      <article class="pillar"><span class="p-num">২</span><h3>চরিত্র গঠন ও শৃঙ্খলা</h3><p>প্রাত্যহিক প্রার্থনা, যোগাভ্যাস ও সুশৃঙ্খল জীবনযাত্রার মাধ্যমে শিক্ষার্থীদের মনকে পবিত্র ও সংকল্পবদ্ধ রাখা।</p></article>
      <article class="pillar"><span class="p-num">৩</span><h3>মমতা ও আশ্রয়ের কোল</h3><p>পরম গুরুদেবের পিতৃসুলভ স্নেহ ও সান্নিধ্যে প্রতিটি শিশু খুঁজে পায় এক নিরাপদ নীড় ও নিশ্চিত ভবিষ্যৎ।</p></article>
      <article class="pillar"><span class="p-num">৪</span><h3>সেবাব্রত ও সংস্কার</h3><p>সনাতন সংস্কৃতি, আধ্যাত্মিক চেতনা এবং মানবসেবার চেতনায় উদ্বুদ্ধ করে এক একজন আদর্শ ও স্বাবলম্বী মানুষ হিসেবে গড়ে তোলা।</p></article>
    </div>
    <article class="upasana">
      <div class="up-text">
      <p class="eyebrow">উপাসনার মুহূর্ত</p>
      <h3>প্রদীপের আলো আর ধূপের সুবাসে বিদ্যার্থীদের প্রার্থনা</h3>
      <p>শান্ত, স্নিগ্ধ পরিবেশ। প্রদীপের মৃদু আলো আর ধূপের সুবাসে চারপাশ এক পবিত্র আবহে ভেসে যাচ্ছে। ঠিক এমনই এক সুন্দর মুহূর্তে বাসুদেব অনাথ আশ্রমের কচি-কাঁচা বিদ্যার্থীরা সারিবদ্ধভাবে উপাসনায় নিমগ্ন।</p>
      <p>তাদের নিষ্পাপ চোখ, ভক্তিভরা মুখাবয়ব আর জোড়হাতে প্রার্থনার দৃশ্যটি যেন স্বর্গের এক টুকরো দৃশ্যকে মর্ত্যে নামিয়ে এনেছে। পরিবার বা আপনজনের অভাব যাদের জীবনে রয়েছে, তারা এক পরম পিতার চরণে নিজেদের সমর্পণ করে খুঁজে পায় পরম শান্তি ও আশ্রয়। সমবেত কণ্ঠে তাদের সুমধুর প্রার্থনা ও জপ কেবল আশ্রমে নয়, যেন প্রতিটি হৃদয়ে এক অলৌকিক প্রশান্তি ছড়িয়ে দেয়।</p>
      <p>তাদের এই সরল ও একনিষ্ঠ ভক্তি আমাদের শেখায়, জীবনের আলো কিংবা শক্তি বাহ্যিক জাঁকজমকে নয়, বরং অন্তরের বিশুদ্ধ বিশ্বাস ও শ্রদ্ধায় লুকিয়ে থাকে। বিদ্যা অর্জনের পাশাপাশি এমন সংস্কার ও আধ্যাত্মিক চর্চার মধ্য দিয়ে এই শিশুরা সুনাগরিক ও আলোকিত মানুষ হিসেবে গড়ে উঠুক, বাসুদেবের কাছে এই আমাদের বিনীত প্রার্থনা।</p>
      </div>
      <figure class="up-photo"><img src="images/upasana-1.jpg" alt="শ্রীশ্রী বাসুদেব অনাথ আশ্রমের বিদ্যার্থীরা মন্দিরে সারিবদ্ধভাবে জোড়হাতে উপাসনা করছে" loading="lazy"><img src="images/upasana-2.jpg" alt="গীতা ও পাঠগ্রন্থ সামনে রেখে বিদ্যার্থীদের প্রার্থনা" loading="lazy"></figure>
    </article>
    <div class="anath-strip">
      <figure><img src="images/gurudev-upadesh.jpg" alt="গুরুদেব আশ্রমের বিদ্যার্থীদের উপদেশ দিচ্ছেন" loading="lazy"><figcaption>গুরুদেবের সান্নিধ্যে উপদেশ ও নীতিশিক্ষা</figcaption></figure>
      <figure><img src="images/dhyan-yoga.jpg" alt="গুরুদেবের তত্ত্বাবধানে বিদ্যার্থীদের সমবেত ধ্যান" loading="lazy"><figcaption>প্রাত্যহিক সমবেত ধ্যান ও যোগাভ্যাস</figcaption></figure>
    </div>
    <figure class="pranam">
      <img src="images/guru-biplob-chaitanya.jpg" alt="শ্রীমৎ বিপ্লব চৈতন্য ব্রহ্মচারী মহারাজ" loading="lazy">
      <blockquote>
        <p>পবিত্র এই সাধনপীঠ ও আশ্রমের মাধ্যমে শ্রীমৎ বিপ্লব চৈতন্য ব্রহ্মচারী মহারাজ সমাজের পিছিয়ে পড়া ও সহায়-সম্বলহীন শিশুদের জীবনের দিশা দেখাচ্ছেন। তাঁর এই মহান কর্মযজ্ঞ ও অপার করুণার প্রতি জানাই কোটি কোটি প্রণাম।</p>
        <cite>গুরুদেবের চরণপ্রান্তে নিবেদিত আমাদের বিনম্র শ্রদ্ধা ও ভক্তি।<br><small>শ্রীমৎ বিপ্লব চৈতন্য ব্রহ্মচারী মহারাজ, অধ্যক্ষ, শ্রীশ্রী বাসুদেব যোগাশ্রম ও শ্রীশ্রী বাসুদেব অনাথ আশ্রম</small></cite>
      </blockquote>
    </figure>
    <div class="anath-cta">
      <p>একটি শিশুর পাশে দাঁড়ান। আপনার দান, শুভদিনে অন্নসেবা কিংবা মাসিক প্রণামী, সবই পৌঁছায় শিশুদের কাছে।</p>
      <div class="cta-row">
        <a class="btn btn-primary" href="#donate">দান করুন</a>
        <a class="btn btn-outline" href="#shubhodin">শুভদিন বুকিং</a>
        <a class="btn btn-outline" href="#sadasya">সদস্য হোন</a>
      </div>
    </div>
  </div></section>

  <section id="shubhodin" class="shubho"><div class="wrap">
    <div class="sec-head">
      <p class="eyebrow">শুভদিন বুকিং</p>
      <h2>আপনার শুভদিনটি অনাথ শিশুদের সাথে উদযাপন করুন</h2>
      <p class="lede">প্রতিদিন দূর-দূরান্ত থেকে ভক্ত ও শিষ্যরা আশ্রমে এসে তাঁদের জীবনের বিশেষ দিনগুলো পালন করেন। আপনার আনন্দের দিনে কিংবা প্রিয়জনের স্মরণের দিনে আশ্রমের শিশুদের এক বেলা অন্নসেবা দিন। গুরুর আশ্রমে, শিশুদের হাসিমুখের মাঝে দিনটি হয়ে উঠুক পুণ্যময়।</p>
    </div>
    <div class="shubho-grid">
      <div class="occasions">
        <article class="occ"><h3>অন্নপ্রাশন</h3><p>সন্তানের মুখে প্রথম অন্ন, গুরুর আশীর্বাদ ও শিশুদের সাথে।</p></article>
        <article class="occ"><h3>জন্মদিন</h3><p>নিজের বা সন্তানের জন্মদিনে শিশুদের সাথে আনন্দ ভাগ করে নিন।</p></article>
        <article class="occ"><h3>বিবাহবার্ষিকী</h3><p>দাম্পত্যের শুভদিনে গুরুপূজা ও শিশুদের অন্নসেবা।</p></article>
        <article class="occ"><h3>শুভ বিবাহ</h3><p>আশ্রম প্রাঙ্গণে শাস্ত্রমতে বিবাহের আয়োজন।</p></article>
        <article class="occ"><h3>শ্রাদ্ধ ও বাৎসরিক</h3><p>প্রয়াত পিতা-মাতা ও প্রিয়জনের আত্মার শান্তি কামনায় শ্রাদ্ধানুষ্ঠান।</p></article>
        <article class="occ"><h3>পূজা ও মানত</h3><p>যেকোনো পূজা, মানত বা পারিবারিক শুভকাজ।</p></article>
        <figure class="occ-photo"><div class="occ-pics"><img src="images/shubhodin-annaprashan.jpg" alt="গুরুদেব শিশুর মুখে প্রথম অন্ন তুলে দিচ্ছেন, অন্নপ্রাশন" loading="lazy"><img src="images/shubhodin-birthday.jpg" alt="আশ্রমে গুরুদেবের সাথে পরিবারের জন্মদিন উদযাপন" loading="lazy"></div><figcaption>আশ্রমে গুরুদেবের আশীর্বাদে অন্নপ্রাশন ও জন্মদিন উদযাপন। প্রতিটি শুভদিনে শিশুদের জন্য এক বেলা ভালো খাবার, আর আপনার পরিবারের জন্য তাদের আশীর্বাদ।</figcaption></figure>
      </div>

      <form class="book" id="bookForm" novalidate>
        <h3>অনুষ্ঠান বুক করুন</h3>
        <p class="book-sub">তথ্য দিয়ে বোতাম চাপলে হোয়াটসঅ্যাপে আশ্রমের কাছে আপনার অনুরোধ যাবে। আশ্রম যোগাযোগ করে তারিখ নিশ্চিত করবে।</p>
        <label for="bk-name">আপনার নাম</label>
        <input id="bk-name" type="text" autocomplete="name" placeholder="যেমন: সুমন দাশ">
        <label for="bk-phone">মোবাইল নম্বর</label>
        <input id="bk-phone" type="tel" autocomplete="tel" placeholder="০১XXXXXXXXX">
        <label for="bk-occ">অনুষ্ঠান</label>
        <select id="bk-occ">
          <option>অন্নপ্রাশন</option><option>জন্মদিন</option><option>বিবাহবার্ষিকী</option><option>শুভ বিবাহ</option><option>শ্রাদ্ধ / বাৎসরিক</option><option>পূজা / মানত</option><option>অন্যান্য</option>
        </select>
        <label for="bk-date">পছন্দের তারিখ</label>
        <input id="bk-date" type="date">
        <label for="bk-note">বিশেষ কিছু জানাতে চাইলে</label>
        <textarea id="bk-note" rows="3" placeholder="যেমন: অতিথি সংখ্যা, কার স্মরণে, বিশেষ পূজা"></textarea>
        <a class="btn btn-primary book-btn" id="waLink" href="https://wa.me/<?= e(preg_replace('/\D/', '', $S['whatsapp'])) ?>" target="_blank" rel="noopener">হোয়াটসঅ্যাপে বুকিং অনুরোধ পাঠান</a>
        <p class="book-alt">অন্নসেবার খরচ ও বিস্তারিত জানতে আশ্রমে যোগাযোগ করুন: <b><?= e($S['phone1']) ?></b></p>
      </form>
    </div>
  </div></section>

  <section id="utsav" class="utsav"><div class="wrap">
    <div class="sec-head">
      <p class="eyebrow">উৎসব ও অনুষ্ঠান</p>
      <h2>এক নজরে অনুষ্ঠানসমূহ ২০২৬–২০২৭</h2>
      <p class="lede">প্রতিটি অনুষ্ঠানে সকল ভক্তবৃন্দ সাদর আমন্ত্রিত। প্রতি অমাবস্যায় রাত্রীকালীন শ্রীশ্রী চণ্ডীযজ্ঞ এবং গুরুদেবগণের আবির্ভাব ও তিরোধান তিথিতে বিশ্বশান্তি গীতাযজ্ঞ অনুষ্ঠিত হয়।</p>
    </div>
    <div class="utsav-grid">
      <aside class="next" id="nextBox" aria-live="polite">
        <p class="next-label">পরবর্তী অনুষ্ঠান</p>
        <p class="next-date" id="nextDate">৮ নভেম্বর ২০২৬ · রবিবার</p>
        <p class="next-title" id="nextTitle">অমাবস্যা উপলক্ষে রাত্রীকালীন শ্রীশ্রী চণ্ডীযজ্ঞ</p>
        <p class="next-place">আশ্রম প্রাঙ্গণ, নেহালপুর</p>
      </aside>
      <ol class="cal" id="cal">
<?php foreach ($events as $ev): $k = $kinds[$ev['kind']] ?? $kinds['special']; ?>
        <li class="ev k-<?= $k[0] ?>" data-date="<?= e($ev['date']) ?>"><div class="ev-date"><b><?= e($ev['date_text'] ?: bn_date($ev['date'])) ?></b><span><?= e(trim(($ev['bangla_date'] ?? '') . ' · ' . ($ev['weekday'] ?: bn_weekday($ev['date'])), ' ·')) ?></span></div><div class="ev-what"><span class="kind"><?= e($k[1]) ?></span><span class="st"></span><p><?= e($ev['title']) ?></p></div></li>
<?php endforeach; ?>
      </ol>
    </div>
  </div></section>

  <section id="gallery"><div class="wrap">
    <div class="sec-head">
      <p class="eyebrow">ছবি</p>
      <h2>আশ্রম প্রাঙ্গণ</h2>
    </div>
    <div class="gallery">
<?php foreach ($gallery as $g): $cls = ['large' => 'g-wide g-tall', 'wide' => 'g-wide'][$g['size'] ?? ''] ?? ''; ?>
      <figure<?= $cls ? ' class="' . $cls . '"' : '' ?>><img src="<?= e(img_url($g['image'])) ?>" alt="<?= e($g['alt'] ?: $g['caption']) ?>" loading="lazy"<?= !empty($g['focus_top']) ? ' style="object-position:top"' : '' ?>><?php if ($g['caption']): ?><figcaption><?= e($g['caption']) ?></figcaption><?php endif; ?></figure>
<?php endforeach; ?>
    </div>
  </div></section>

  <section id="unnoyon" class="unnoyon"><div class="wrap">
    <div class="sec-head">
      <p class="eyebrow">উন্নয়ন ও ভবিষ্যৎ পরিকল্পনা</p>
      <h2>গড়ে উঠছে আগামীর বাসুদেব ধাম</h2>
      <p class="lede">ভক্তদের দানে আশ্রমের সেবা প্রতিদিন বড় হচ্ছে। চলমান নির্মাণকাজ ও ভবিষ্যৎ প্রকল্পে অংশ নিতে সকল ভক্ত ও শুভানুধ্যায়ীকে সাদর আমন্ত্রণ।</p>
    </div>

    <h3 class="sub-h">চলমান কাজ</h3>
    <div class="projects">
      <article class="proj"><span class="chip live">নির্মাণ চলছে</span><h4>প্রসাদ ভবন</h4><p>ভক্ত ও আশ্রমের শিশুদের প্রসাদ বিতরণ ও অন্নসেবার জন্য নতুন ভবন।</p></article>
      <article class="proj"><span class="chip live">নির্মাণ চলছে</span><h4>বাসুদেব স্কুল এন্ড কলেজ ভবন</h4><p>আশ্রমের ছেলেদের পড়াশোনার জন্য নতুন শিক্ষা ভবন।</p></article>
    </div>

    <h3 class="sub-h">ভবিষ্যৎ পরিকল্পনা</h3>
    <div class="projects three">
      <article class="proj"><span class="chip plan">পরিকল্পনা</span><h4>বাসুদেব মেডিকেল কলেজ</h4><p>চিকিৎসা শিক্ষা ও এলাকার মানুষের স্বাস্থ্যসেবার লক্ষ্যে।</p></article>
      <article class="proj"><span class="chip plan">পরিকল্পনা</span><h4>বাসুদেব টেকনিক্যাল কলেজ</h4><p>আশ্রমের ছেলেদের হাতে-কলমে কারিগরি শিক্ষা ও স্বাবলম্বী ভবিষ্যৎ।</p></article>
      <article class="proj"><span class="chip plan">পরিকল্পনা</span><h4>বাসুদেব কুম্ভ মেলা</h4><p>সাধু-সন্ত ও ভক্তদের মিলনে এক মহা ধর্মীয় সমাবেশ।</p></article>
    </div>

    <div class="invite">
      <p><b>এই কাজে অংশ নিতে চান?</b> নির্মাণসামগ্রী, অর্থ বা শ্রম, যেকোনোভাবে যুক্ত হতে আশ্রমের সাথে যোগাযোগ করুন: <b><?= e($S['phone1']) ?></b>, অথবা <a href="#donate">আশ্রমের ব্যাংক হিসাবে</a> দান পাঠান।</p>
    </div>
  </div></section>

  <section id="bhumidata" class="bhumi"><div class="wrap">
    <div class="sec-head">
      <p class="eyebrow">ভূমিদাতা কর্নার</p>
      <h2>দুই কড়া জমি থেকে আজকের তীর্থধাম</h2>
      <p class="lede">১৯৬৫ সালে স্বামী বাসুদেবানন্দ গিরি মহারাজ মাত্র ২ কড়া জমিতে যে কুটির গড়েছিলেন, আজ তা এক বিশাল বৃন্দাবনে পরিণত হয়েছে। এর প্রতিটি অংশ গড়ে উঠেছে আশ্রমের ভক্ত ও শিষ্যদের দানে। যাঁরা আশ্রমে ভূমিদান করেছেন, তাঁদের প্রতি আশ্রমের চিরকৃতজ্ঞতা।</p>
    </div>
    <div class="then-now">
      <div><strong>২ কড়া</strong><span>১৯৬৫ সালে প্রথম কুটিরের জমি</span></div>
      <div class="arrow" aria-hidden="true">→</div>
      <div><strong>বিশাল আশ্রম প্রাঙ্গণ</strong><span>মন্দির, পুকুরঘাট, অনাথ আশ্রম ও বিদ্যালয়, সবই ভক্তদের দানে</span></div>
    </div>
    <h3 class="sub-h">শ্রীশ্রী বাসুদেব যোগাশ্রম ও বাসুদেব অনাথ আশ্রমের ভূমিদাতা সদস্যবৃন্দ</h3>
    <div class="donors donors-top">
<?php foreach ($donors as $d): if (($d['group'] ?? 'main') !== 'guru') continue; ?>
      <article class="donor guru-donor"><div class="d-photo"><?php if (!empty($d['photo'])): ?><img src="<?= e(img_url($d['photo'])) ?>" alt="<?= e($d['name']) ?>" loading="lazy"><?php else: ?><span>ॐ</span><?php endif; ?></div><h3><?= e($d['name']) ?></h3><?php if ($d['address']): ?><p><?= e($d['address']) ?></p><?php endif; ?><?php if ($d['paksha']): ?><p class="paksha">পক্ষে: <?= e($d['paksha']) ?></p><?php endif; ?></article>
<?php endforeach; ?>
    </div>
    <div class="donors">
<?php foreach ($donors as $d): if (($d['group'] ?? 'main') !== 'main') continue; ?>
      <article class="donor"><div class="d-photo"><?php if (!empty($d['photo'])): ?><img src="<?= e(img_url($d['photo'])) ?>" alt="<?= e($d['name']) ?>" loading="lazy"><?php else: ?><span>ॐ</span><?php endif; ?></div><h3><?= e($d['name']) ?></h3><?php if ($d['address']): ?><p><?= e($d['address']) ?></p><?php endif; ?><?php if ($d['paksha']): ?><p class="paksha">পক্ষে: <?= e($d['paksha']) ?></p><?php endif; ?></article>
<?php endforeach; ?>
    </div>
    <h3 class="sub-h" style="margin-top:40px">বাসুদেব স্কুল এন্ড কলেজের ভূমিদাতা সদস্যবৃন্দ</h3>
    <p class="lede" style="margin-bottom:14px">বাসুদেব স্কুল এন্ড কলেজের জন্য শতাধিক ভক্ত ভূমিদান করেছেন। আশ্রমে টাঙানো সম্মাননা ফলক:</p>
    <div class="banner-scroll"><img src="images/bhumidata/school-bhumidata-banner-2.jpg" alt="বাসুদেব স্কুল এন্ড কলেজের ভূমিদাতা সদস্যবৃন্দের তালিকা (অংশ)" loading="lazy"></div>
    <details class="fullboard"><summary>সম্পূর্ণ ফলক দেখুন</summary>
    <div class="banner-scroll"><img src="images/bhumidata/school-bhumidata-banner.jpg" alt="বাসুদেব স্কুল এন্ড কলেজের ভূমিদাতা সদস্যবৃন্দের সম্পূর্ণ তালিকা" loading="lazy"></div>
    </details>
    <p class="note">ফলকটি পাশে সরিয়ে দেখুন। স্কুল এন্ড কলেজের ভূমিদাতাগণের নাম ও ছবি শীঘ্রই আলাদাভাবে যোগ করা হবে।</p>
      <div class="invite" style="margin-top:24px">
      <p><b>ভূমিদান কার্যক্রম চলমান রয়েছে।</b> আশ্রম, অনাথ আশ্রম বা স্কুল এন্ড কলেজের জন্য কেউ এই মহৎ ভূমিদানে অংশ নিতে চাইলে সরাসরি আশ্রমের সাথে যোগাযোগ করুন: <b><?= e($S['phone1']) ?></b></p>
    </div>
  </div></section>

  <section id="sadasya" class="sadasya"><div class="wrap">
    <div class="sec-head">
      <p class="eyebrow">সদস্য কর্নার</p>
      <h2>আশ্রম পরিবারের সদস্য হোন</h2>
      <p class="lede">সদস্যদের নিয়মিত প্রণামীতেই চলে আশ্রমের নিত্যপূজা, অমাবস্যার চণ্ডীযজ্ঞ এবং অনাথ শিশুদের আহার ও শিক্ষা। সামর্থ্য অনুযায়ী আপনিও যুক্ত হতে পারেন।</p>
    </div>
    <div class="tiers">
      <article class="tier">
        <p class="tier-kind">মাসিক সদস্য</p>
        <p class="tier-amt">৳৫০ <small>থেকে শুরু, প্রতি মাসে</small></p>
        <p>প্রতি মাসে সামর্থ্য অনুযায়ী প্রণামী দিয়ে আশ্রমের সেবায় নিয়মিত অংশ নিন। পরিমাণ আপনার ইচ্ছা ও সক্ষমতার ওপর।</p>
      </article>
      <article class="tier tier-life">
        <p class="tier-kind">আজীবন সদস্য</p>
        <p class="tier-amt">এককালীন প্রণামী</p>
        <p>একবার প্রণামী দিয়ে আজীবন আশ্রম পরিবারের সাথে যুক্ত থাকুন। বিস্তারিত জানতে আশ্রমে যোগাযোগ করুন।</p>
      </article>
    </div>
    <ol class="how">
      <li><b>যোগাযোগ করুন</b><span><?= e($S['phone1']) ?> বা হোয়াটসঅ্যাপে <?= e($S['phone2']) ?></span></li>
      <li><b>নাম ও ঠিকানা দিন</b><span>আশ্রমের সদস্য খাতায় আপনার নাম নিবন্ধিত হবে</span></li>
      <li><b>প্রণামী পাঠান</b><span>আশ্রমে সরাসরি, অথবা <a href="#donate">ব্যাংক হিসাবে</a></span></li>
    </ol>
  </div></section>

  <section id="donate"><div class="wrap">
    <div class="sec-head">
      <p class="eyebrow">অর্ঘ্যাদি পাঠাবার ঠিকানা</p>
      <h2>অনাথ শিশুদের সেবায় পাশে থাকুন</h2>
      <p class="lede">আপনার দান সরাসরি শিশুদের আহার, শিক্ষা ও চিকিৎসায় এবং আশ্রমের ধর্মীয় কাজে ব্যয় হয়।</p>
    </div>
    <div class="donate-grid">
<?php foreach (($S['banks'] ?? []) as $i => $bk): ?>
      <article class="acct">
        <h3><?= e($bk['title']) ?></h3>
        <dl>
          <dt>হিসাব নং</dt><dd><span class="num" id="acc<?= $i ?>"><?= e($bk['account']) ?></span></dd>
          <?php if ($bk['type']): ?><dt>ধরন</dt><dd><?= e($bk['type']) ?></dd><?php endif; ?>
          <dt>ব্যাংক</dt><dd><?= e($bk['bank']) ?></dd>
        </dl>
        <button class="copy" data-copy="acc<?= $i ?>">হিসাব নং কপি করুন</button>
      </article>
<?php endforeach; ?>
    </div>
    <p class="note">দান পাঠানোর পর অনুগ্রহ করে নিচের নম্বরে জানিয়ে দিন, যাতে আশ্রম আপনার দান গ্রহণ নিশ্চিত করতে পারে।</p>
  </div></section>

  <section id="contact" class="contact"><div class="wrap">
    <div class="sec-head">
      <p class="eyebrow">যোগাযোগ</p>
      <h2>আশ্রমে আসুন</h2>
    </div>
    <div class="contact-grid">
      <div>
        <h3>ঠিকানা</h3>
        <p>শ্রীশ্রী বাসুদেব যোগাশ্রম ও অনাথ আশ্রম<br>নেহালপুর, ডাকঘর: নন্দীরহাট<br>উপজেলা: হাটহাজারী, জেলা: চট্টগ্রাম</p>
        <p><a href="<?= e($S['maps']) ?>" target="_blank" rel="noopener">গুগল ম্যাপে দেখুন ↗</a></p>
      </div>
      <div>
        <h3>ফোন</h3>
        <p class="big"><?= e($S['phone1']) ?></p>
        <p class="big"><?= e($S['phone2']) ?> <small>(হোয়াটসঅ্যাপ)</small></p>
      </div>
      <div>
        <h3>ফেসবুক ও ইউটিউব</h3>
        <p><a href="<?= e($S['facebook']) ?>" target="_blank" rel="noopener">ফেসবুক পেজে আমাদের অনুসরণ করুন ↗</a></p>
        <p><a href="<?= e($S['youtube']) ?>" target="_blank" rel="noopener">ইউটিউবে গুরুদেবের ভিডিও দেখুন ↗</a></p>
      </div>
    </div>
  </div></section>
</main>

<footer><div class="wrap">
  <span>© শ্রীশ্রী বাসুদেব যোগাশ্রম ও বাসুদেব অনাথ আশ্রম, নেহালপুর</span>
  <span>basudevyogashram.org · ॥ জয় গুরু ॥</span>
</div></footer>

<dialog class="bio" id="bio-basudevananda" aria-labelledby="bio-b-title">
  <div class="bio-inner">
    <button class="bio-close" type="button" aria-label="বন্ধ করুন">✕</button>
    <div class="bio-head">
      <img src="images/guru-basudevananda.jpg" alt="">
      <div>
        <p class="eyebrow">সংক্ষিপ্ত জীবনী · প্রতিষ্ঠাতা অধ্যক্ষ</p>
        <h2 id="bio-b-title">পরমহংস শ্রীমৎ স্বামী বাসুদেবানন্দ গিরি মহারাজ</h2>
        <p class="bio-sub">বিশ্বপরিব্রাজক, অষ্টাঙ্গ যোগক্রিয়াবিদ, সর্বত্যাগী সন্ন্যাসী, পরিব্রাজকাচার্য</p>
      </div>
    </div>

    <h3>জন্ম ও শৈশব</h3>
    <p>চট্টগ্রামের মীরসরাই থানার ৭নং কাটাছড়া ইউনিয়নের মুরাদনগর গ্রামে এক দরিদ্র পরিবারে গৌর দোল পূর্ণিমা তিথিতে তাঁর আবির্ভাব। পিতা শ্রী রামগোবিন্দ কৃষিকাজ ও নাপিতের কাজ করতেন, মাতা শ্রীমতি কুসুম বালা শীল ছিলেন গৃহিণী। তিন ভাইয়ের মধ্যে বাসুদেব ছিলেন সবার ছোট, মা-বাবার সবচেয়ে আদরের সন্তান। শৈশব থেকেই তাঁর মধ্যে আধ্যাত্মিক ভাব, সংযম ও সততার পরিচয় পাওয়া যেত।</p>
    <p>কথিত আছে, অন্নপ্রাশনের দিন গ্রামে মৎস্য ভোজনের আয়োজন হয়েছিল। শিশু বাসুদেবের মুখে মাছ ছোঁয়াতেই তিনি মৃতপ্রায় হয়ে পড়েন, সবাই কান্নায় ভেঙে পড়েন। হাসপাতাল থেকে সুস্থ হয়ে ফিরলেও সেদিন আর অন্নপ্রাশন হয়নি। যেন জীবনের শুরুতেই তিনি বুঝিয়ে দিয়েছিলেন, তাঁর পথ ভোগের নয়, ত্যাগের।</p>

    <h3>নিরুদ্দেশ যাত্রা</h3>
    <p>আট বছর বয়সে একদিন খেলা শেষে সন্ধ্যা নামলেও বাসুদেব আর ঘরে ফেরেননি। তন্ন তন্ন করে খোঁজা হলো, পুকুরে জাল ফেলা হলো, কিন্তু কোনো হদিস মিলল না। সবাই ভাবলেন হয়তো পুকুরে ডুবে গেছেন। মায়ের মন মানল না; ছেলের জন্য কাঁদতে কাঁদতে তাঁর দুচোখ অন্ধ হয়ে গিয়েছিল।</p>

    <h3>সন্ন্যাস ও হিমালয়ে সাধনা</h3>
    <p>যৌবনেই তিনি সংসারের মায়া ত্যাগ করে সন্ন্যাস গ্রহণ করেন। আধ্যাত্মিক মহাসাধক শ্রীমৎ ব্রহ্মানন্দ মহারাজের কাছে হঠযোগ ও রাজযোগ শিক্ষা নেন, এবং গুরুর দেখানো পথে কঠোর তপস্যা, যোগাভ্যাস ও সাধনায় আত্মোন্নতির পথে অগ্রসর হন। কেদার, বদ্রী, হরিদ্বার, হৃষীকেশ, নেপাল, ভুটান ও বার্মাসহ অসংখ্য তীর্থ ও পর্বত পায়ে হেঁটে পরিভ্রমণ করেন। দীর্ঘকাল হিমালয়ের কঠিন সাধনায় মগ্ন থেকে তিনি সিদ্ধিলাভ করেন।</p>

    <h3>জন্মভূমিতে প্রত্যাবর্তন</h3>
    <p>প্রায় ত্রিশ বছর পর একদিন গেরুয়া বস্ত্রধারী, হাতে কমণ্ডলু, পায়ে খড়ম, লম্বা দাড়ির এক দীর্ঘদেহী দিব্য সন্ন্যাসী গ্রামের মেঠো পথ ধরে ধীরে হেঁটে চলেছেন। গ্রামবাসী অবাক হয়ে ভাবছে, কে এই সাধু? তৎকালীন পূর্ব পাকিস্তানের অবহেলিত, অসহায় সনাতনী মানুষের কল্যাণে তিনি ফিরে এসেছিলেন। জন্মভূমির পাশে গোপালপুর গ্রামে ভক্তদের আকুল অনুরোধে প্রথম কুটির স্থাপন করেন, যা আজ শ্রীশ্রী বাসুদেব যোগাশ্রম, গোপালপুর।</p>

    <h3>অযাচক আশ্রম, নেহালপুর</h3>
    <p>পরবর্তীতে বিভিন্ন মঠ ও মিশনের দায়িত্ব তাঁর কাছে এলেও বাসনাত্যাগী এই সন্ন্যাসী সব ছেড়ে গভীর সাধনার জন্য ১৯৬৫ সালে হাটহাজারীর প্রত্যন্ত গ্রাম নেহালপুরে এক পুকুরপাড়ে মাত্র দুই কড়া জমিতে একটি কুটির নির্মাণ করে কঠোর সাধনায় মগ্ন হন। নাম দেন <b>“অযাচক আশ্রম”</b>: কারো কাছে কিছু চাওয়া হবে না, ভক্তরা স্বেচ্ছায় যা দেবেন তাতেই আশ্রম চলবে। আধা-সেদ্ধ খাবারে দিনে এক বেলা আহার করা এই সদাচারী যোগীরাজ ছিলেন অসংখ্য যোগবিভূতির অধিকারী।</p>

    <h3>গীতার বাণী প্রচার</h3>
    <p>রাউজান, রাঙ্গুনিয়াসহ চট্টগ্রামের যেসব প্রত্যন্ত অঞ্চলে দিনের বেলাও মানুষ পথে বের হতে ভয় পেত, সেখানে তিনি পায়ে হেঁটে গিয়ে অবহেলিত সনাতনীদের মাঝে গীতার বাণী প্রচার করেছেন এবং আত্মশুদ্ধির জন্য দীক্ষা দিয়েছেন। মুক্তিযুদ্ধের সময় শরণার্থী শিবিরে আশ্রয় নেওয়া ভক্তদের কাছেও তিনি পৌঁছে যেতেন। তাঁর জীবনের বহু অলৌকিক ঘটনার সাক্ষী আজও অনেক ভক্ত।</p>

    <blockquote class="bio-quote">শরণার্থী শিবিরে দুশ্চিন্তায় ডুবে থাকা এক ভক্ত মাকে পেছন থেকে ডেকে তিনি বলেছিলেন, “তুমি বাসুদেবকে স্মরণ করো না? আমিই সেই বাসুদেব।”</blockquote>

    <h3>উত্তরসূরি ও মহাসমাধি</h3>
    <p>জীবনের শেষ পর্বে, ১৯৮১ সালে, তিনি তাঁর তেজোদীপ্ত তরুণ শিষ্য শ্রীমৎ স্বামী কৃষ্ণানন্দ গিরি মহারাজের হাতে আশ্রমের দায়িত্ব অর্পণ করেন। ১৯৮৫ সালে শ্রীকৃষ্ণ জন্মাষ্টমীর পরের দশমী তিথিতে নেহালপুরের যে কুটিরে তিনি দেহত্যাগ করেন, সেখানেই আজ তাঁর সমাধিক্ষেত্র। সেই দুই কড়া জমির অযাচক আশ্রমই আজ হাজারো ভক্তের তীর্থধাম শ্রীশ্রী বাসুদেব যোগাশ্রম।</p>

    <p class="bio-src">তথ্যসূত্র: আশ্রমে সংরক্ষিত হাতে লেখা জীবনী ও “উপাসনা পদ্ধতি” গ্রন্থ</p>
  </div>
</dialog>

<dialog class="bio" id="bio-krishnananda" aria-labelledby="bio-k-title">
  <div class="bio-inner">
    <button class="bio-close" type="button" aria-label="বন্ধ করুন">✕</button>
    <div class="bio-head">
      <img src="images/guru-krishnananda.jpg" alt="">
      <div>
        <p class="eyebrow">সংক্ষিপ্ত জীবনী · উত্তরসূরি অধ্যক্ষ</p>
        <h2 id="bio-k-title">শ্রীমৎ স্বামী কৃষ্ণানন্দ গিরি মহারাজ</h2>
        <p class="bio-sub">স্বামী বাসুদেবানন্দ গিরি মহারাজের প্রাণপ্রিয় শিষ্য ও উত্তরাধিকারী</p>
      </div>
    </div>
    <!-- KRISHNANANDA_BIO: জীবনী এখানে বসবে -->
    <p class="bio-pending">জীবনী শীঘ্রই যোগ করা হবে।</p>
    <p class="bio-src">তথ্যসূত্র: আশ্রমে সংরক্ষিত জীবনী</p>
  </div>
</dialog>


<script>
(function(){
  var btn=document.getElementById('menuBtn'),nav=document.getElementById('mainNav');
  btn.addEventListener('click',function(){var o=nav.dataset.open==='true';nav.dataset.open=String(!o);btn.setAttribute('aria-expanded',String(!o));});
  nav.addEventListener('click',function(e){if(e.target.tagName==='A'&&window.innerWidth<=820){nav.dataset.open='false';btn.setAttribute('aria-expanded','false');}});

  // mark past / next event by today's date (Bangladesh time)
  var now=new Date(Date.now()+6*3600*1000).toISOString().slice(0,10), nextSet=false;
  document.querySelectorAll('#cal .ev').forEach(function(li){
    var d=li.dataset.date, st=li.querySelector('.st');
    if(d<now){li.classList.add('past');st.textContent='সম্পন্ন';}
    else if(!nextSet){nextSet=true;li.classList.add('is-next');st.textContent='পরবর্তী';
      document.getElementById('nextDate').textContent=li.querySelector('.ev-date b').textContent+' · '+li.querySelector('.ev-date span').textContent.split(' · ')[1];
      document.getElementById('nextTitle').textContent=li.querySelector('.ev-what p').textContent;}
  });
  if(!nextSet){document.getElementById('nextDate').textContent='নতুন সূচি শীঘ্রই';document.getElementById('nextTitle').textContent='২০২৭–২০২৮ সালের অনুষ্ঠানসূচি প্রকাশিত হবে।';}

  // booking: build WhatsApp message from the form
  var wa=document.getElementById('waLink');
  function v(id){return (document.getElementById(id).value||'').trim();}
  function upd(){
    var msg='জয় গুরু। আমি আশ্রমে একটি অনুষ্ঠান বুক করতে চাই।\n'+
      'নাম: '+(v('bk-name')||'-')+'\nমোবাইল: '+(v('bk-phone')||'-')+
      '\nঅনুষ্ঠান: '+v('bk-occ')+'\nপছন্দের তারিখ: '+(v('bk-date')||'-')+
      (v('bk-note')?'\nবিস্তারিত: '+v('bk-note'):'');
    wa.href='https://wa.me/<?= e(preg_replace('/\D/', '', $S['whatsapp'])) ?>?text='+encodeURIComponent(msg);
  }
  document.getElementById('bookForm').addEventListener('input',upd);
  document.getElementById('bookForm').addEventListener('submit',function(e){e.preventDefault();});
  upd();

  // biography dialogs
  document.querySelectorAll('[data-bio]').forEach(function(b){
    b.addEventListener('click',function(){var d=document.getElementById(b.dataset.bio);if(d&&d.showModal){d.showModal();d.scrollTop=0;}});
  });
  document.querySelectorAll('dialog.bio').forEach(function(d){
    d.querySelector('.bio-close').addEventListener('click',function(){d.close();});
    d.addEventListener('click',function(e){if(e.target===d)d.close();});
  });
  document.querySelectorAll('.copy').forEach(function(b){
    b.addEventListener('click',function(){
      var t=document.getElementById(b.dataset.copy).textContent, orig=b.textContent;
      function done(){b.textContent='কপি হয়েছে';setTimeout(function(){b.textContent=orig},1800);}
      function fallback(){var r=document.createRange();r.selectNodeContents(document.getElementById(b.dataset.copy));var s=getSelection();s.removeAllRanges();s.addRange(r);b.textContent='নম্বরটি সিলেক্ট করা হয়েছে';setTimeout(function(){b.textContent=orig},1800);}
      try{navigator.clipboard.writeText(t).then(done,fallback);}catch(e){fallback();}
    });
  });
})();
</script>

</body>
</html>
