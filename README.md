# শ্রীশ্রী বাসুদেব যোগাশ্রম — ওয়েবসাইট

basudevyogashram.org-এর সোর্স।

- `index.php` — মূল পাতা (খবর, উৎসবসূচি, ভূমিদাতা, গ্যালারি, যোগাযোগ ও দানের তথ্য ডেটা থেকে আসে)
- `admin/` — বাংলা অ্যাডমিন প্যানেল (basudevyogashram.org/admin)
- `inc/` — কোড; `seed/` — প্রথমবারের ডেটা
- আপলোড করা ছবি ও সম্পাদিত ডেটা থাকে public_html-এর বাইরে `ashram-data/` ফোল্ডারে, তাই GitHub deploy এগুলো মুছে না।

`main` ব্রাঞ্চে পরিবর্তন হলে Hostinger Git auto-deployment-এর মাধ্যমে সাইট আপডেট হয়।
