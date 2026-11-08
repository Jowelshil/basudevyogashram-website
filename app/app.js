/* বাসুদেব যোগাশ্রম হিসাব অ্যাপ (ওয়েব + Android) */
"use strict";
const $ = id => document.getElementById(id);
const BN_MONTHS = ["জানুয়ারি","ফেব্রুয়ারি","মার্চ","এপ্রিল","মে","জুন","জুলাই","আগস্ট","সেপ্টেম্বর","অক্টোবর","নভেম্বর","ডিসেম্বর"];
const BN_MS = ["জানু","ফেব্রু","মার্চ","এপ্রিল","মে","জুন","জুলাই","আগস্ট","সেপ্টে","অক্টো","নভে","ডিসে"];
const INC_CATS = ["এককালীন অনুদান","মাসিক প্রণামী","ভূমিদাতা প্রণামী","আজীবন সদস্য ফি","প্রণামী / দানবাক্স","পূজা-উৎসব অনুদান","অনাথ আশ্রম অনুদান","বিদ্যালয় অনুদান","নির্মাণ অনুদান","অন্যান্য আয়"];
const DAILY_CATS = ["বাজার ও খাদ্য সামগ্রী","চাল-ডাল (মজুদ)","পূজা ও ভোগ সামগ্রী","বিদ্যুৎ বিল","গ্যাস / জ্বালানি","কর্মচারী বেতন","শিক্ষক বেতন","চিকিৎসা","শিক্ষা উপকরণ","যাতায়াত","মেরামত ও রক্ষণাবেক্ষণ","অন্যান্য খরচ"];
const CON_CATS = ["রড","সিমেন্ট","ইট","বালু / পাথর","শ্রমিক মজুরি","ঠিকাদার বিল","কাঠ / দরজা-জানালা","বিদ্যুৎ ও স্যানিটারি","রং","পরিবহন","অন্যান্য নির্মাণ"];
const CATS_FALLBACK = {income: INC_CATS, daily: DAILY_CATS, construction: CON_CATS};
const cats = k => (D.cats && D.cats[k] && D.cats[k].length ? D.cats[k] : CATS_FALLBACK[k]);
const KLABEL = {income: "জমা/আয়", daily: "দৈনিক খরচ", construction: "নির্মাণ খরচ"};
const LIST_LABEL = {monthly: "মাসিক প্রণামী", land: "ভূমিদাতা", life: "আজীবন সদস্য"};
const LIST_CAT = {monthly: "মাসিক প্রণামী"};

const bn = n => String(Number(n || 0).toLocaleString("en-IN", {maximumFractionDigits: 2})).replace(/\d/g, d => "০১২৩৪৫৬৭৮৯"[d]);
const bnd = s => String(s).replace(/\d/g, d => "০১২৩৪৫৬৭৮৯"[d]);
const tk = n => "৳ " + bn(n);
const esc = s => String(s ?? "").replace(/[&<>"']/g, c => ({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"}[c]));
const fmtDate = s => { if (!s) return ""; const [y, m, d] = s.split("-").map(Number); return bnd(d) + " " + BN_MONTHS[m - 1] + " " + bnd(y); };
const monthName = ym => BN_MONTHS[+ym.slice(5, 7) - 1] + " " + bnd(ym.slice(0, 4));
const addDays = (s, n) => { const d = new Date(s + "T00:00:00Z"); d.setUTCDate(d.getUTCDate() + n); return d.toISOString().slice(0, 10); };
/* Banglish land words and digits to Bangla: "2 shotok" -> "২ শতক", "dag 1234" -> "দাগ ১২৩৪" */
const LAND_WORDS = [
  [/\b(sho|sha|so|sa)t(o|a)?(k|ng?sho|ngsho|nsho|angsh?o)\b/gi, "শতক"], [/\bdecimals?\b|\bdec\b/gi, "শতক"],
  [/\bk(a|o)a?th?a\b|\bkata\b/gi, "কাঠা"], [/\bbig?h?a\b/gi, "বিঘা"], [/\bka+ni\b/gi, "কানি"], [/\bg(o|a)nd(a|o)\b/gi, "গণ্ডা"],
  [/\bk(o|a)ra\b/gi, "কড়া"], [/\b(acre|akor|ekor|okor)s?\b/gi, "একর"], [/\b(o|a)jut(a|o)ng?sho\b/gi, "অযুতাংশ"],
  [/\bm(o|a)u?u?za\b|\bmouja\b/gi, "মৌজা"], [/\bdag\b/gi, "দাগ"], [/\bkh?(a|o)tt?i?y?an\b/gi, "খতিয়ান"], [/\bjomi\b/gi, "জমি"], [/\bch?(o|a)t(o|a)k\b/gi, "ছটাক"],
  [/\bno\.?(?=\s|$)/gi, "নং"], [/\band\b/gi, "ও"]];
const landBn = v => { let t = String(v || ""); LAND_WORDS.forEach(([re, bnw]) => t = t.replace(re, bnw)); return t.replace(/\d/g, d => "০১২৩৪৫৬৭৮৯"[d]); };
const toEn = s => String(s || "").replace(/[০-৯]/g, c => "০১২৩৪৫৬৭৮৯".indexOf(c));

let token = null, me = null, D = {ledger: [], people: [], areas: [], names: {}, sms: {}, today: ""};
let kind = "income", view = "ledger", curArea = null, prefill = null;
const isAdmin = () => me && me.role === "admin";
const store = {get: k => { try { return localStorage.getItem(k); } catch (e) { return null; } },
               set: (k, v) => { try { v == null ? localStorage.removeItem(k) : localStorage.setItem(k, v); } catch (e) {} }};

/* ---------- API ---------- */
async function api(a, body = {}) {
  let r;
  try {
    r = await fetch("api.php?a=" + a, {method: "POST", headers: {"Content-Type": "application/json", ...(token ? {"Authorization": "Bearer " + token} : {})},
      body: JSON.stringify({...body, token})});
  } catch (e) { $("offline").hidden = false; throw new Error("ইন্টারনেট সংযোগ নেই।"); }
  $("offline").hidden = true;
  let j; try { j = await r.json(); } catch (e) { throw new Error("সার্ভার থেকে উত্তর পাওয়া যায়নি (" + r.status + ")।"); }
  if (r.status === 401 && a !== "login") { logoutLocal(j.error); throw new Error(j.error); }
  if (!j.ok) throw new Error(j.error || "কিছু একটা সমস্যা হয়েছে।");
  return j;
}
function toast(t) { const el = $("toast"); el.textContent = t; el.hidden = false; clearTimeout(toast.t); toast.t = setTimeout(() => el.hidden = true, 3200); }
async function guard(btn, fn) { if (btn) btn.disabled = true; try { await fn(); } catch (e) { toast(e.message); } finally { if (btn) btn.disabled = false; } }

/* ---------- auth ---------- */
/* ---------- auto logout after 3 minutes without any touch ---------- */
const IDLE_MS = 3 * 60 * 1000;
let lastAct = Date.now(), lastPing = Date.now(), lastSaved = 0;
function activity() {
  if (!token) return;
  lastAct = Date.now();
  if (lastAct - lastSaved > 5000) { lastSaved = lastAct; store.set("bya_last", String(lastAct)); }
  if (lastAct - lastPing > 60000) { lastPing = lastAct; api("ping").catch(() => {}); } // keeps the server session alive while someone is working
}
["pointerdown", "touchstart", "keydown", "input", "wheel"].forEach(ev => document.addEventListener(ev, activity, {passive: true, capture: true}));
window.addEventListener("scroll", activity, {passive: true});
async function idleCheck() {
  if (!token || $("appScreen").hidden) return;
  if (Date.now() - lastAct > IDLE_MS) {
    closeSheet(); closeMenu();
    try { await api("logout"); } catch (e) {}
    logoutLocal("৩ মিনিট কোনো কাজ না হওয়ায় নিরাপত্তার জন্য লগআউট হয়েছে।");
  }
}
setInterval(idleCheck, 10000);
document.addEventListener("visibilitychange", () => { if (!document.hidden) idleCheck(); });
window.addEventListener("pageshow", idleCheck);

async function boot() {
  token = store.get("bya_token");
  if (token && Date.now() - Number(store.get("bya_last") || 0) > IDLE_MS) { store.set("bya_token", null); token = null; showAuth("৩ মিনিট কোনো কাজ না হওয়ায় লগআউট হয়েছে। আবার লগইন করুন।"); return; }
  if (token) { try { await reload(); enterApp(); return; } catch (e) { if (!token) {} } }
  showAuth();
}
async function showAuth(msg) {
  $("appScreen").hidden = true; $("authScreen").hidden = false;
  $("authMsg").textContent = msg || "";
  let st = {needsSetup: false};
  try { st = await api("status"); } catch (e) { $("authMsg").textContent = e.message; }
  $("setupForm").hidden = !st.needsSetup; $("loginForm").hidden = st.needsSetup;
}
$("loginForm").addEventListener("submit", e => { e.preventDefault(); guard(e.submitter, async () => {
  $("authMsg").textContent = "";
  try { const j = await api("login", {username: $("lUser").value.trim(), password: $("lPass").value}); token = j.token; store.set("bya_token", token); $("lPass").value = ""; await reload(); enterApp(); }
  catch (err) { $("authMsg").textContent = err.message; }
}); });
$("setupForm").addEventListener("submit", e => { e.preventDefault(); guard(e.submitter, async () => {
  try { const j = await api("setup", {key: $("sKey").value.trim(), name: $("sName").value.trim(), username: $("sUser").value.trim(), password: $("sPass").value});
    token = j.token; store.set("bya_token", token); await reload(); enterApp(); toast("অ্যাডমিন অ্যাকাউন্ট তৈরি হয়েছে। এখন ইউজার যোগ করুন।"); go("users"); }
  catch (err) { $("authMsg").textContent = err.message; }
}); });
function logoutLocal(msg) { token = null; me = null; enterApp.done = false; view = "ledger"; store.set("bya_token", null); showAuth(msg); }
$("logoutBtn").onclick = async () => { closeMenu(); try { await api("logout"); } catch (e) {} logoutLocal("লগআউট হয়েছে।"); };

async function reload() {
  const j = await api("load");
  me = j.user; D = j;
  if (typeof kind !== "undefined" && $("fCat")) { const cur = $("fCat").value; $("fCat").innerHTML = cats(kind).map(c => `<option>${esc(c)}</option>`).join(""); if (cats(kind).includes(cur)) $("fCat").value = cur; }
  D.ledger.sort((a, b) => b.date.localeCompare(a.date) || String(b.createdAt).localeCompare(String(a.createdAt)));
}
function enterApp() {
  lastAct = lastPing = Date.now(); store.set("bya_last", String(lastAct));
  $("authScreen").hidden = true; $("appScreen").hidden = false;
  $("whoami").textContent = me.name + (isAdmin() ? " · অ্যাডমিন" : "");
  document.querySelectorAll(".admin-only").forEach(el => el.hidden = !isAdmin());
  $("lockNote").hidden = isAdmin();
  if (!$("fDate").value) { $("fDate").value = D.today; $("dayPick").value = D.today; $("dailyPick").value = D.today; initReport(); }
  setKind(kind);
  if (!enterApp.done) { enterApp.done = true; if (isAdmin()) view = "dash"; }
  go(view);
}

/* ---------- navigation ---------- */
const TABS = ["ledger", "monthly", "land", "life", "other"];
document.querySelectorAll("#tabs button").forEach(b => b.onclick = () => { if (b.dataset.tab === "monthly") curArea = null; go(b.dataset.tab); });
function go(v) {
  if (!isAdmin() && !TABS.includes(v) && v !== "password") v = "ledger";
  view = v;
  document.querySelectorAll(".view").forEach(s => s.hidden = s.id !== "v-" + v);
  document.querySelectorAll("#tabs button").forEach(b => b.toggleAttribute("aria-current", b.dataset.tab === v));
  if (document.querySelector("#tabs [aria-current]")) document.querySelector("#tabs [aria-current]").setAttribute("aria-current", "page");
  $("fab").hidden = !["monthly", "land", "life"].includes(v);
  window.scrollTo(0, 0);
  render();
}
function render() {
  ({ledger: renderLedger, monthly: renderMonthly, land: renderLand, life: renderLife, dash: renderDash, daily: renderDaily,
    report: renderReport, invite: renderInvite, users: renderUsers, sms: loadSms, records: renderRecords}[view] || (() => {}))();
}
document.querySelector(".brand").onclick = () => go(isAdmin() ? "dash" : "ledger");
$("menuBtn").onclick = () => { $("menu").hidden = false; $("menuScrim").hidden = false; };
function closeMenu() { $("menu").hidden = true; $("menuScrim").hidden = true; }
$("menuScrim").onclick = closeMenu;
document.querySelectorAll("#menu [data-go]").forEach(b => b.onclick = () => { closeMenu(); go(b.dataset.go); });
$("refreshBtn").onclick = () => { closeMenu(); guard(null, async () => { await reload(); render(); toast("তথ্য হালনাগাদ হয়েছে।"); }); };

/* ---------- sheet ---------- */
function openSheet(title, html, onReady) {
  $("sheetTitle").textContent = title; $("sheetBody").innerHTML = html;
  $("sheet").hidden = false; $("sheetScrim").hidden = false;
  if (onReady) onReady($("sheetBody"));
  const f = $("sheetBody").querySelector("input:not([type=hidden]),select,textarea"); if (f && window.innerWidth > 700) f.focus();
}
function closeSheet() { $("sheet").hidden = true; $("sheetScrim").hidden = true; $("sheetBody").innerHTML = ""; }
$("sheetClose").onclick = closeSheet; $("sheetScrim").onclick = closeSheet;
window.addEventListener("popstate", () => { if (!$("sheet").hidden) closeSheet(); });

/* ================= Tab 1: আয়/ব্যয় ================= */
function setKind(k) {
  kind = k;
  document.querySelectorAll("#kindSeg button").forEach(b => b.setAttribute("aria-pressed", b.dataset.k === k));
  $("fCat").innerHTML = cats(k).map(c => `<option>${esc(c)}</option>`).join("");
  $("projWrap").hidden = k !== "construction";
  $("donorExtra").hidden = k !== "income";
  $("nameLbl").textContent = k === "income" ? "ভক্ত / দাতার নাম" : "কাকে দেওয়া হলো (দোকান / ব্যক্তি)";
}
document.querySelectorAll("#kindSeg button").forEach(b => b.onclick = () => { clearPrefill(); setKind(b.dataset.k); });
function clearPrefill() { prefill = null; $("prefillNote").hidden = true; }
$("fName").addEventListener("change", () => {
  if (kind !== "income") return;
  const n = $("fName").value.trim();
  const p = D.people.find(x => x.name === n) || D.ledger.find(x => x.kind === "income" && x.name === n);
  if (p) { if (!$("fMobile").value) $("fMobile").value = p.mobile || ""; if (!$("fAddr").value) $("fAddr").value = p.address || ""; }
});
$("ledgerForm").addEventListener("submit", e => { e.preventDefault(); guard($("saveBtn"), async () => {
  const body = {kind, date: $("fDate").value, amount: toEn($("fAmount").value), category: $("fCat").value, project: $("fProj").value,
    name: $("fName").value, mobile: $("fMobile").value, address: $("fAddr").value, ref: $("fRef").value, mode: $("fMode").value, note: $("fNote").value};
  const j = await api("ledger_add", body);
  const k = kind; $("ledgerForm").reset(); $("fDate").value = D.today; setKind(k); clearPrefill();
  toast(`সংরক্ষিত · ক্রমিক ${bnd(j.entry.serial)}` + (j.sms === "sent" ? " · SMS গেছে" : j.sms === "failed" ? " · SMS যায়নি" : ""));
  $("dayPick").value = j.entry.date;
  await reload(); render();
}); });
$("dayPick").addEventListener("input", renderLedger);

function entryRow(r, {who = false, ops = false} = {}) {
  const sms = r.kind === "income" && r.mobile ? (r.sms?.status === "sent" ? '<span class="chip ok">SMS গেছে</span>' : r.sms?.status === "failed" ? '<span class="chip bad">SMS যায়নি</span>' : '<span class="chip wait">SMS বাকি</span>') : "";
  return `<div class="item">
    <div class="sn">${bnd(r.serial || "")}</div>
    <div><div class="t">${esc(r.name || r.category)}</div>
      <div class="s"><span class="chip ${r.kind}">${KLABEL[r.kind]}</span> ${esc(r.category)}${r.project ? " · " + esc(r.project) : ""}${r.month ? " · " + monthName(r.month) : ""}</div>
      <div class="s">${[r.mobile, r.address, r.ref ? "রসিদ " + r.ref : "", r.mode, r.note].filter(Boolean).map(esc).join(" · ")}</div>
      ${who ? `<div class="s">এন্ট্রি: ${esc(D.names[r.by] || "?")} · ${esc((r.createdAt || "").slice(11, 16))}</div>` : ""}
      ${r.history?.length ? '<span class="chip wait">সম্পাদিত</span>' : ""} ${sms}</div>
    <div class="amt ${r.kind === "income" ? "inc" : "exp"}">${tk(r.amount)}</div>
    ${ops ? `<div class="ops"><button class="btn ghost small" data-edit="${r.id}">সম্পাদনা</button><button class="btn ghost small" data-del="${r.id}">মুছুন</button></div>` : ""}
  </div>`;
}
function renderLedger() {
  const d = $("dayPick").value || D.today;
  const list = D.ledger.filter(r => r.date === d && !r.deleted).sort((a, b) => a.kind.localeCompare(b.kind) || a.serial - b.serial);
  $("projOptions").innerHTML = [...new Set(D.ledger.map(r => r.project).filter(Boolean))].map(n => `<option value="${esc(n)}">`).join("");
  $("nameOptions").innerHTML = [...new Set([...D.people.map(p => p.name), ...D.ledger.filter(r => r.kind === "income").map(r => r.name)].filter(Boolean))].slice(0, 400).map(n => `<option value="${esc(n)}">`).join("");
  {
    /* top boxes: the day's income, cost and balance (users see only their own entries) */
    const s = sums(list), isToday = d === D.today;
    $("dayBox").innerHTML = `<div class="dh"><span>${isToday ? "আজকের" : fmtDate(d) + " তারিখের"} ${isAdmin() ? "মোট হিসাব" : "হিসাব (আপনার দেওয়া এন্ট্রি)"}</span><span>${bnd(list.length)}টি এন্ট্রি</span></div>
      <div class="row3"><div class="bx inc"><span>মোট আয়</span><b class="inc">${tk(s.income)}</b></div>
      <div class="bx exp"><span>মোট খরচ</span><b class="exp">${tk(s.cost)}</b></div>
      <div class="bx"><span>ব্যালেন্স</span><b class="${s.net < 0 ? "exp" : ""}">${signed(s.net)}</b></div></div>
      ${s.construction ? `<div class="dh"><span>খরচের মধ্যে দৈনিক ${tk(s.daily)} · নির্মাণ ${tk(s.construction)}</span></div>` : ""}`;
  }
  if (isAdmin()) {
    const s = sums(list);
    $("daySum").innerHTML = `<span>জমা <b class="inc">${tk(s.income)}</b></span><span>দৈনিক খরচ <b class="exp">${tk(s.daily)}</b></span><span>নির্মাণ <b class="con">${tk(s.construction)}</b></span>`;
  } else {
    $("daySum").innerHTML = `<span class="muted">${fmtDate(d)} তারিখে আপনার দেওয়া এন্ট্রি</span>`;
  }
  const pend = list.filter(r => r.kind === "income" && r.mobile && r.sms?.status !== "sent").length;
  $("smsBox").innerHTML = D.sms.enabled && pend ? `<div class="smsbar"><span>${bnd(pend)} জন দাতার SMS বাকি</span><button class="btn small" id="smsDayBtn">দিন শেষ: SMS পাঠান</button></div>` : "";
  if ($("smsDayBtn")) $("smsDayBtn").onclick = e => guard(e.target, async () => { const j = await api("sms_day", {date: d}); toast(`${bnd(j.sent)}টি SMS গেছে` + (j.failed ? `, ${bnd(j.failed)}টি যায়নি` : "")); await reload(); render(); });
  $("dayList").innerHTML = list.length ? list.map(r => entryRow(r, {who: isAdmin(), ops: isAdmin()})).join("") : `<div class="empty">এই তারিখে কোনো এন্ট্রি নেই।</div>`;
}
$("dayList").addEventListener("click", e => {
  const t = e.target.closest("button"); if (!t) return;
  if (t.dataset.edit) editEntry(t.dataset.edit); else if (t.dataset.del) deleteEntry(t.dataset.del);
});

/* admin: edit / delete ledger entry */
function editEntry(id) {
  const r = D.ledger.find(x => x.id === id); if (!r) return;
  const catList = cats(r.kind).includes(r.category) ? cats(r.kind) : [r.category, ...cats(r.kind)];
  openSheet("এন্ট্রি সম্পাদনা", `<form class="form" id="eForm">
    <p class="note">${fmtDate(r.date)} · ক্রমিক ${bnd(r.serial)} · ${KLABEL[r.kind]}</p>
    <div class="row2"><label>টাকা<input id="eAmt" inputmode="decimal" value="${esc(r.amount)}" required></label><label>খাত<select id="eCat">${catList.map(c => `<option${c === r.category ? " selected" : ""}>${esc(c)}</option>`).join("")}</select></label></div>
    ${r.kind === "construction" ? `<label>প্রকল্প<input id="eProj" value="${esc(r.project)}"></label>` : ""}
    <label>নাম<input id="eName" value="${esc(r.name)}"></label>
    ${r.kind === "income" ? `<div class="row2"><label>মোবাইল<input id="eMob" value="${esc(r.mobile)}"></label><label>ঠিকানা<input id="eAddr" value="${esc(r.address)}"></label></div>` : ""}
    <div class="row2"><label>রসিদ<input id="eRef" value="${esc(r.ref)}"></label><label>মাধ্যম<input id="eMode" value="${esc(r.mode)}"></label></div>
    <label>মন্তব্য<input id="eNote" value="${esc(r.note)}"></label>
    <button class="btn wide">হালনাগাদ করুন</button><p class="note">আগের তথ্য সম্পাদনার রেকর্ডে থেকে যাবে।</p></form>`, b => {
    b.querySelector("#eForm").onsubmit = ev => { ev.preventDefault(); guard(ev.submitter, async () => {
      const v = id => (b.querySelector("#" + id) || {}).value ?? "";
      await api("ledger_update", {id, year: r.date.slice(0, 4), amount: toEn(v("eAmt")), category: v("eCat"), project: v("eProj") || r.project, name: v("eName"),
        mobile: r.kind === "income" ? v("eMob") : "", address: r.kind === "income" ? v("eAddr") : "", ref: v("eRef"), mode: v("eMode"), note: v("eNote")});
      closeSheet(); toast("হালনাগাদ হয়েছে।"); await reload(); render(); }); };
  });
}
function deleteEntry(id) {
  const r = D.ledger.find(x => x.id === id); if (!r) return;
  openSheet("এন্ট্রি মুছবেন?", `<form class="form" id="dForm"><p>${fmtDate(r.date)} · ক্রমিক ${bnd(r.serial)} · ${esc(r.name || r.category)} · <b>${tk(r.amount)}</b></p>
    ${r.month ? `<p class="note">এটা ${monthName(r.month)} মাসের প্রণামী। মুছলে ওই মাস আবার বকেয়া দেখাবে।</p>` : ""}
    <label>মুছে ফেলার কারণ<input id="dReason" required></label><button class="btn danger wide">মুছে ফেলুন</button>
    <p class="note">মুছে ফেলা এন্ট্রি রেকর্ডে থেকে যায়, দরকার হলে ফিরিয়ে আনা যাবে।</p></form>`, b => {
    b.querySelector("#dForm").onsubmit = ev => { ev.preventDefault(); guard(ev.submitter, async () => {
      await api("ledger_delete", {id, year: r.date.slice(0, 4), reason: b.querySelector("#dReason").value}); closeSheet(); toast("মুছে ফেলা হয়েছে।"); await reload(); render(); }); };
  });
}

/* ================= Tab 2/3: মাসিক প্রণামী + ভূমিদাতা (১২ মাসের ঘর) ================= */
const gridYear = {monthly: null};
function renderMonthly() {
  const ppl = D.people.filter(p => p.list === "monthly" && !p.deleted);
  const curM = D.today.slice(0, 7);
  if (curArea === null) {
    $("areaGrid").hidden = false; $("areaDetail").hidden = true;
    const q = $("mAllSearch").value.trim();
    $("areaCards").hidden = !!q; $("mAllList").hidden = !q;
    $("areaHint").textContent = q ? "সব এলাকার খোঁজের ফল" : "এলাকা বেছে নিন";
    if (q) { $("mAllList").innerHTML = gridHTML("monthly", ppl, q, true); scrollGrid($("mAllList"), "monthly"); return; }
    $("areaCards").innerHTML = `<div class="areas">` +
      D.areas.map(a => { const l = ppl.filter(p => p.area === a); const paid = l.filter(p => p.paid?.[curM]).length;
        return `<button class="area" data-area="${esc(a)}"><b>${esc(a)}</b><span>${bnd(l.length)} জন ভক্ত</span><span>${BN_MONTHS[+curM.slice(5) - 1]}: ${bnd(paid)} জন জমা দিয়েছেন</span>${isAdmin() ? `<span>মাসিক মোট ${tk(l.reduce((s, p) => s + Number(p.amount || 0), 0))}</span>` : ""}</button>`; }).join("") + `</div>`;
    return;
  }
  $("areaGrid").hidden = true; $("areaDetail").hidden = false;
  $("areaTitle").textContent = curArea;
  $("mList").innerHTML = gridHTML("monthly", ppl.filter(p => p.area === curArea), $("mSearch").value);
  scrollGrid($("mList"), "monthly");
}
$("areaGrid").addEventListener("click", e => { const b = e.target.closest("[data-area]"); if (b) { curArea = b.dataset.area; $("mSearch").value = ""; renderMonthly(); window.scrollTo(0, 0); } });
$("areaBack").onclick = () => { curArea = null; renderMonthly(); };
$("mSearch").addEventListener("input", renderMonthly);
$("mAllSearch").addEventListener("input", renderMonthly);
$("landSearch").addEventListener("input", renderLand);

function matches(p, q) { q = toEn(q).trim().toLowerCase(); return !q || [p.name, p.address, p.mobile, p.area, p.details, p.memberNo, p.land].join(" ").toLowerCase().includes(q); }
function landNextSn(list = "land") {
  const max = D.people.filter(p => p.list === list && !p.deleted && /^\d+$/.test(p.memberNo || "")).reduce((m, p) => Math.max(m, +p.memberNo), 0);
  return String(max + 1).padStart(3, "0");
}
function renderLand() {
  const all = D.people.filter(p => p.list === "land" && !p.deleted);
  const ppl = all.filter(p => matches(p, $("landSearch").value)).sort((a, b) => (!a.memberNo - !b.memberNo) || (a.memberNo || "").localeCompare(b.memberNo || "", "en", {numeric: true}) || a.name.localeCompare(b.name, "bn"));
  $("landCount").innerHTML = bnd(all.length) + " জন" + (isAdmin() && all.some(p => !/^\d{3,}$/.test(p.memberNo || "")) ? ` <button class="btn ghost small" id="landRenum" type="button">সদস্য নং ০০১ থেকে সাজান</button>` : "");
  if ($("landRenum")) $("landRenum").onclick = () => openSheet("সদস্য নং নতুন করে সাজাবেন?", `<p>সব ভূমিদাতা এখনকার ক্রম অনুযায়ী ০০১, ০০২, ০০৩… নম্বর পাবেন। পুরনো LD-আইডি থাকলে সেটা মন্তব্যে লেখা থাকবে।</p><button class="btn wide" id="renOk">সাজান</button>`, bb => {
    bb.querySelector("#renOk").onclick = e => guard(e.target, async () => { const j = await api("land_renumber"); closeSheet(); await reload(); render(); toast(`${bnd(j.count)} জনের সদস্য নং ০০১ থেকে সাজানো হয়েছে।`); });
  });
  $("landList").innerHTML = `<div class="card">` + (ppl.length ? ppl.map((p, i) => `<div class="item"><div class="sn" style="font-size:.8rem">${p.memberNo ? esc(p.memberNo) : bnd(i + 1)}</div>
    <div><button class="link-btn" data-person="${p.id}" style="padding:0;color:inherit;text-align:left"><span class="t">${esc(p.name)}</span></button>
    <div class="s">${esc([p.address, p.mobile].filter(Boolean).join(" · "))}</div>
    <div class="s">${esc([p.land ? "জমি " + landBn(p.land) : "", landBn(p.details), p.area].filter(Boolean).join(" · "))}${p.date ? " · দান " + fmtDate(p.date) : ""}</div></div>
    <div></div></div>`).join("") : `<div class="empty">${$("landSearch").value ? "কিছু পাওয়া যায়নি।" : "এখনো কেউ যোগ হয়নি। নিচের + বাটন চেপে যোগ করুন।"}</div>`) + `</div>`;
}
function scrollGrid(box, list) {
  const w = box.querySelector(".mgrid-wrap"); if (!w) return;
  const y = gridYear[list] || +D.today.slice(0, 4), t = D.today;
  const col = y < +t.slice(0, 4) ? 12 : y > +t.slice(0, 4) ? 1 : +t.slice(5, 7);
  const th = w.querySelectorAll("thead th")[col], pin = w.querySelector("th.pin");
  if (th && pin) w.scrollLeft = Math.max(0, th.offsetLeft - pin.offsetWidth - th.offsetWidth * 2.5);
}
function gridHTML(list, ppl, q, showArea) {
  const y = gridYear[list] || +D.today.slice(0, 4);
  const curM = D.today.slice(0, 7);
  const rows = ppl.filter(p => matches(p, q)).sort((a, b) => (!a.memberNo - !b.memberNo) || (a.memberNo || "").localeCompare(b.memberNo || "", "en", {numeric: true}) || a.name.localeCompare(b.name, "bn"));
  const years = []; for (let i = +D.today.slice(0, 4) + 1; i >= 2024; i--) years.push(i);
  let paidCount = 0, dueCount = 0, paidSum = 0;
  const body = rows.map(p => {
    const start = (p.date || p.createdAt || "").slice(0, 7);
    const cells = BN_MS.map((_, i) => {
      const m = `${y}-${String(i + 1).padStart(2, "0")}`;
      const pd = p.paid?.[m];
      if (pd) { paidCount++; paidSum += Number(pd.amount || 0); return `<td class="m"><button class="mc paid" data-paid="${p.id}" data-m="${m}" title="${fmtDate(pd.date)}">✓ জমা</button></td>`; }
      if (start && m < start) return `<td class="m"><span class="mc na">·</span></td>`;
      if (m <= curM) { dueCount++; return `<td class="m"><button class="mc due" data-pay="${p.id}" data-m="${m}">বকেয়া</button></td>`; }
      return `<td class="m"><button class="mc future" data-pay="${p.id}" data-m="${m}">—</button></td>`;
    }).join("");
    return `<tr><td class="pin"><button class="link-btn" data-person="${p.id}" style="text-align:left;padding:0;color:inherit"><span class="t">${p.memberNo ? `<span class="muted" style="font-weight:600">${esc(bnd(p.memberNo))}.</span> ` : ""}${esc(p.name)}</span></button>
      <div class="s">${esc(p.address || "")}</div><div class="s">${esc(p.mobile || "")}</div>
      <div class="s">${p.amount ? "মাসিক " + tk(p.amount) : ""}${showArea && p.area ? ` · <b>${esc(p.area)}</b>` : ""}</div></td>${cells}</tr>`;
  }).join("");
  const sum = isAdmin() ? `<span class="muted">${bnd(y)}: ${bnd(paidCount)}টি মাস জমা (${tk(paidSum)}), ${bnd(dueCount)}টি বকেয়া</span>` : `<span class="muted">${bnd(rows.length)} জন</span>`;
  return `<div class="gridbar"><label style="display:flex;gap:6px;align-items:center">বছর <select data-year="${list}">${years.map(v => `<option value="${v}"${v === y ? " selected" : ""}>${bnd(v)}</option>`).join("")}</select></label>${sum}</div>
    <div class="legend" style="margin-bottom:8px"><span><i class="sw inc-bg"></i>জমা হয়েছে</span><span><i class="sw exp-bg"></i>বকেয়া (চাপ দিয়ে জমা নিন)</span><span>— সামনের মাস</span></div>` +
    (rows.length ? `<div class="mgrid-wrap"><table class="mgrid"><thead><tr><th class="pin">নাম / ঠিকানা</th>${BN_MS.map(m => `<th style="text-align:center">${m}</th>`).join("")}</tr></thead><tbody>${body}</tbody></table></div>`
      : `<div class="empty">${q ? "কিছু পাওয়া যায়নি।" : "এখনো কেউ যোগ হয়নি। নিচের + বাটন চেপে যোগ করুন।"}</div>`);
}
document.addEventListener("change", e => { const s = e.target.closest("[data-year]"); if (s) { gridYear[s.dataset.year] = +s.value; render(); } });
["mList", "mAllList", "landList", "lifeList"].forEach(id => $(id).addEventListener("click", e => {
  const b = e.target.closest("button"); if (!b) return;
  if (b.dataset.pay) payMonth(b.dataset.pay, b.dataset.m);
  else if (b.dataset.paid) showPaid(b.dataset.paid, b.dataset.m);
  else if (b.dataset.person) showPerson(b.dataset.person);
}));

function payMonth(pid, m) {
  const p = D.people.find(x => x.id === pid); if (!p) return;
  openSheet(`${monthName(m)}: প্রণামী জমা`, `<form class="form" id="pForm">
    <p><b>${esc(p.name)}</b><br><span class="muted">${esc([p.address, p.mobile].filter(Boolean).join(" · "))}</span></p>
    <div class="row2"><label>টাকা<input id="pAmt" inputmode="decimal" value="${esc(p.amount || "")}" required></label><label>জমার তারিখ<input type="date" id="pDate" value="${D.today}" required></label></div>
    <div class="row2"><label>মাধ্যম<select id="pMode"><option>নগদ</option><option>বিকাশ</option><option>নগদ (মোবাইল)</option><option>রকেট</option><option>ব্যাংক</option></select></label><label>রসিদ নং<input id="pRef"></label></div>
    ${m > D.today.slice(0, 7) ? `<p class="banner">এটা সামনের মাসের অগ্রিম জমা।</p>` : ""}
    <button class="btn wide">জমা নিন</button>
    <p class="note">${isAdmin() ? "" : "জমা নেওয়ার পর আর বদলানো যাবে না। "}${p.mobile && D.sms.enabled ? "দাতার মোবাইলে SMS যাবে।" : ""}</p></form>`, b => {
    b.querySelector("#pForm").onsubmit = ev => { ev.preventDefault(); guard(ev.submitter, async () => {
      const j = await api("ledger_add", {kind: "income", date: b.querySelector("#pDate").value, amount: toEn(b.querySelector("#pAmt").value), category: LIST_CAT[p.list],
        name: p.name, mobile: p.mobile, address: p.address, mode: b.querySelector("#pMode").value, ref: b.querySelector("#pRef").value, personId: p.id, month: m});
      closeSheet(); toast(`${monthName(m)} জমা হয়েছে · ক্রমিক ${bnd(j.entry.serial)}` + (j.sms === "sent" ? " · SMS গেছে" : ""));
      await reload(); render(); }); };
  });
}
function showPaid(pid, m) {
  const p = D.people.find(x => x.id === pid); const pd = p?.paid?.[m]; if (!pd) return;
  const e = D.ledger.find(x => x.id === pd.entry);
  openSheet(`${monthName(m)}: জমা হয়েছে`, `<p><b>${esc(p.name)}</b></p><p>${tk(pd.amount)} · ${fmtDate(pd.date)}${pd.by ? " · এন্ট্রি: " + esc(D.names[pd.by] || "") : ""}</p>
    ${isAdmin() && e ? `<div class="actions"><button class="btn ghost" id="pdEdit">এন্ট্রি সম্পাদনা</button><button class="btn danger" id="pdDel">এন্ট্রি মুছুন</button></div>` : ""}`, b => {
    if (b.querySelector("#pdEdit")) { b.querySelector("#pdEdit").onclick = () => editEntry(e.id); b.querySelector("#pdDel").onclick = () => deleteEntry(e.id); }
  });
}

/* ================= Tab 4: আজীবন সদস্য ================= */
$("lifeSearch").addEventListener("input", renderLife);
function renderLife() {
  const ppl = D.people.filter(p => p.list === "life" && !p.deleted && matches(p, $("lifeSearch").value)).sort((a, b) => (a.memberNo || "").localeCompare(b.memberNo || "", "bn", {numeric: true}) || a.name.localeCompare(b.name, "bn"));
  $("lifeCount").textContent = bnd(D.people.filter(p => p.list === "life" && !p.deleted).length) + " জন" + (isAdmin() ? " · মোট " + tk(D.people.filter(p => p.list === "life" && !p.deleted).reduce((s, p) => s + Number(p.amount || 0), 0)) : "");
  $("lifeList").innerHTML = `<div class="card">` + (ppl.length ? ppl.map(p => `<div class="item"><div class="sn">${esc(bnd(p.memberNo || ""))}</div>
    <div><button class="link-btn" data-person="${p.id}" style="padding:0;color:inherit;text-align:left"><span class="t">${esc(p.name)}</span></button>
    <div class="s">${esc([p.address, p.mobile].filter(Boolean).join(" · "))}</div><div class="s">${p.date ? "সদস্য হয়েছেন " + fmtDate(p.date) : ""}${p.area ? " · " + esc(p.area) : ""}</div>
    <div class="s">${p.feePaid ? `<span class="chip ok">ফি জমা ${fmtDate(p.feePaid.date)}</span>` : p.feeStatus === "now" ? `<span class="chip bad">ফির এন্ট্রি মুছে ফেলা হয়েছে</span>` : `<span class="chip wait">পুরনো সদস্য</span>`}</div></div>
    <div class="amt inc">${p.amount ? tk(p.amount) : ""}</div></div>`).join("") : `<div class="empty">এখনো কেউ যোগ হয়নি। নিচের + বাটন চেপে যোগ করুন।</div>`) + `</div>`;
}

/* ================= add / view / edit person ================= */
$("fab").onclick = () => personForm(view === "monthly" ? "monthly" : view, null);
function personForm(list, p) {
  const v = k => esc(p ? p[k] ?? "" : "");
  const areaOpts = (sel, optional) => (optional ? `<option value="">—</option>` : "") + D.areas.map(a => `<option${a === sel ? " selected" : ""}>${esc(a)}</option>`).join("");
  const areaSel = p ? p.area : (list === "monthly" ? curArea : "");
  openSheet((p ? "সম্পাদনা: " : "নতুন ") + LIST_LABEL[list], `<form class="form" id="peForm">
    <label>নাম<input id="peName" value="${v("name")}" required></label>
    <label>ঠিকানা<input id="peAddr" value="${v("address")}" placeholder="গ্রাম, ডাকঘর, থানা"></label>
    <div class="row2"><label>মোবাইল নম্বর<input id="peMob" type="tel" inputmode="numeric" value="${v("mobile")}" placeholder="01XXXXXXXXX"></label>
      <label>এলাকা (থানা)<select id="peArea"${list === "monthly" ? " required" : ""}>${areaOpts(areaSel, list !== "monthly")}</select></label></div>
    <div class="row2">${list === "land" ? "" : `<label>${list === "life" ? "সদস্য ফি (টাকা)" : "মাসিক প্রণামী (টাকা)"}<input id="peAmt" inputmode="decimal" value="${v("amount")}" required></label>`}
      <label>${list === "life" ? "সদস্য হওয়ার তারিখ" : list === "land" ? "জমি দানের তারিখ" : "কোন মাস থেকে"}<input type="date" id="peDate" value="${v("date") || (list === "land" ? "" : D.today)}"></label></div>
    ${list === "land" ? `<div class="row2"><label>জমির পরিমাণ<input id="peLand" value="${v("land")}" placeholder="যেমন: ১০ শতক"></label><label>মৌজা / দাগ নং<input id="peDet" value="${v("details")}"></label></div>` : ""}
    ${list === "life" || list === "land" || list === "monthly" ? `<label>সদস্য নং<input id="peNo" value="${p ? v("memberNo") : list !== "life" ? landNextSn(list) : ""}"></label>` : ""}
    ${list === "life" && !p ? `<div class="banner" style="display:grid;gap:8px"><b>সদস্য ফি</b>
      <label class="chk"><input type="radio" name="peFee" value="now"> আজ জমা নিচ্ছি (আজকের আয়ে যোগ হবে)</label>
      <label class="chk"><input type="radio" name="peFee" value="old"> আগেই পরিশোধিত, পুরনো সদস্য (আয়ে যোগ হবে না)</label>
      <div class="row2" id="peFeeNow" hidden><label>মাধ্যম<select id="peMode"><option>নগদ</option><option>বিকাশ</option><option>নগদ (মোবাইল)</option><option>রকেট</option><option>ব্যাংক</option><option>চেক</option></select></label><label>রসিদ নং<input id="peRef"></label></div></div>` : ""}
    <label>মন্তব্য<input id="peNote" value="${v("note")}"></label>
    <button class="btn wide">${p ? "হালনাগাদ করুন" : "যোগ করুন"}</button>
    ${p || isAdmin() ? "" : `<p class="note">যোগ করার পর বদলাতে বা মুছতে অ্যাডমিন লাগবে।</p>`}</form>`, b => {
    b.querySelectorAll("[name=peFee]").forEach(r => r.addEventListener("change", () => { b.querySelector("#peFeeNow").hidden = r.value !== "now" || !r.checked; }));
    ["peLand", "peDet"].forEach(id => { const el = b.querySelector("#" + id); if (el) el.addEventListener("blur", () => el.value = landBn(el.value)); });
    b.querySelector("#peForm").onsubmit = ev => { ev.preventDefault(); guard(ev.submitter, async () => {
      const g = id => (b.querySelector("#" + id) || {}).value ?? "";
      const body = {list, name: g("peName"), address: g("peAddr"), mobile: g("peMob"), area: g("peArea"), amount: toEn(g("peAmt")), date: g("peDate"),
        land: landBn(g("peLand")), details: landBn(g("peDet")), memberNo: toEn(g("peNo")), note: g("peNote"),
        fee: (b.querySelector("[name=peFee]:checked") || {}).value || "", mode: g("peMode"), ref: g("peRef")};
      if (list === "life" && !p && !body.fee) throw new Error("সদস্য ফি আজ জমা নিচ্ছেন, নাকি আগেই পরিশোধিত, বেছে নিন।");
      let res = null;
      if (p) await api("people_update", {...body, id: p.id}); else res = await api("people_add", body);
      closeSheet(); toast(p ? "হালনাগাদ হয়েছে।" : res && res.entry ? `যোগ হয়েছে। ${tk(res.entry.amount)} আজকের আয়ে জমা হয়েছে (ক্রমিক ${bnd(res.entry.serial)})` + (res.sms === "sent" ? ", SMS গেছে।" : "।") : "যোগ হয়েছে।"); await reload(); render(); }); };
  });
}
function showPerson(id) {
  const p = D.people.find(x => x.id === id); if (!p) return;
  const paid = Object.keys(p.paid || {}).sort().reverse();
  const rows = [["ঠিকানা", p.address], ["মোবাইল", p.mobile], ["এলাকা", p.area], [p.list === "life" ? "সদস্য ফি" : "মাসিক প্রণামী", p.amount && p.list !== "land" ? tk(p.amount) : ""],
    ["জমির পরিমাণ", landBn(p.land)], ["মৌজা / দাগ", landBn(p.details)], ["সদস্য নং", p.memberNo], [p.list === "life" ? "সদস্য হয়েছেন" : p.list === "land" ? "জমি দানের তারিখ" : "শুরু", p.date ? fmtDate(p.date) : ""],
    ["মন্তব্য", p.note], ["যোগ করেছেন", (D.names[p.by] || "") + " · " + fmtDate((p.createdAt || "").slice(0, 10))]].filter(r => r[1]);
  openSheet(p.name, `<table><tbody>${rows.map(([k, v]) => `<tr><th>${k}</th><td>${esc(v)}</td></tr>`).join("")}</tbody></table>
    ${paid.length ? `<h3 class="sub" style="margin-top:12px">জমার ইতিহাস</h3><table><tbody>${paid.slice(0, 24).map(m => `<tr><td>${monthName(m)}</td><td>${fmtDate(p.paid[m].date)}</td><td class="n">${tk(p.paid[m].amount)}</td></tr>`).join("")}</tbody></table>` : ""}
    ${isAdmin() ? `<div class="actions"><button class="btn ghost" id="psEdit">সম্পাদনা</button><button class="btn danger" id="psDel">তালিকা থেকে মুছুন</button></div>` : ""}`, b => {
    if (!isAdmin()) return;
    b.querySelector("#psEdit").onclick = () => personForm(p.list, p);
    b.querySelector("#psDel").onclick = () => openSheet("তালিকা থেকে মুছবেন?", `<form class="form" id="pdForm"><p><b>${esc(p.name)}</b> (${LIST_LABEL[p.list]})</p>
      <label>কারণ<input id="pdR" required></label><button class="btn danger wide">মুছে ফেলুন</button><p class="note">আগের জমার হিসাব থেকে যাবে। রেকর্ড থেকে ফিরিয়ে আনা যাবে।</p></form>`, bb => {
      bb.querySelector("#pdForm").onsubmit = ev => { ev.preventDefault(); guard(ev.submitter, async () => { await api("people_delete", {id: p.id, reason: bb.querySelector("#pdR").value}); closeSheet(); toast("মুছে ফেলা হয়েছে।"); await reload(); render(); }); };
    });
  });
}

/* ================= admin: dashboard ================= */
function live() { return D.ledger.filter(r => !r.deleted); }
function sums(list) { const s = {income: 0, daily: 0, construction: 0}; list.forEach(r => s[r.kind] += Number(r.amount) || 0); s.cost = s.daily + s.construction; s.net = s.income - s.cost; return s; }
function inRange(a, b) { return live().filter(r => r.date >= a && r.date <= b); }
function groupBy(list, key) { const m = new Map(); list.forEach(r => { const k = r[key] || "—"; m.set(k, (m.get(k) || 0) + (Number(r.amount) || 0)); }); return [...m].sort((a, b) => b[1] - a[1]); }
const signed = n => (n < 0 ? "− " : "") + tk(Math.abs(n));
function kpiHTML(s) {
  return `<div class="kpi"><div class="lbl">জমা / আয়</div><div class="val inc">${tk(s.income)}</div></div>
  <div class="kpi"><div class="lbl">দৈনিক খরচ</div><div class="val exp">${tk(s.daily)}</div></div>
  <div class="kpi"><div class="lbl">নির্মাণ খরচ</div><div class="val con">${tk(s.construction)}</div></div>
  <div class="kpi"><div class="lbl">উদ্বৃত্ত / ঘাটতি</div><div class="val ${s.net < 0 ? "exp" : "inc"}">${signed(s.net)}</div><div class="s">মোট খরচ ${tk(s.cost)}</div></div>`;
}
function barChart(buckets) {
  const W = Math.max(520, buckets.length * 38), H = 220, pl = 64, pb = 28, pt = 10, cw = (W - pl - 8) / Math.max(1, buckets.length);
  const max = Math.max(1000, ...buckets.map(b => Math.max(b.income, b.daily + b.construction)));
  const p10 = Math.pow(10, Math.floor(Math.log10(max))), f = max / p10, nice = (f <= 2 ? 2 : f <= 5 ? 5 : 10) * p10;
  const y = v => pt + (H - pt - pb) * (1 - v / nice); let g = "";
  for (let i = 0; i <= 4; i++) { const v = nice * i / 4; g += `<line x1="${pl}" x2="${W - 4}" y1="${y(v)}" y2="${y(v)}" stroke="var(--line)"/><text x="${pl - 6}" y="${y(v) + 4}" text-anchor="end" font-size="10" fill="var(--muted)">${bn(v)}</text>`; }
  buckets.forEach((b, i) => { const x = pl + i * cw, bw = Math.min(14, cw / 2 - 3);
    g += `<rect x="${x + cw / 2 - bw - 1}" y="${y(b.income)}" width="${bw}" height="${y(0) - y(b.income)}" fill="var(--inc)" rx="2"><title>${esc(b.label)} জমা ${tk(b.income)}</title></rect>`;
    g += `<rect x="${x + cw / 2 + 1}" y="${y(b.daily)}" width="${bw}" height="${y(0) - y(b.daily)}" fill="var(--exp)"><title>${esc(b.label)} দৈনিক খরচ ${tk(b.daily)}</title></rect>`;
    g += `<rect x="${x + cw / 2 + 1}" y="${y(b.daily + b.construction)}" width="${bw}" height="${y(b.daily) - y(b.daily + b.construction)}" fill="var(--con)"><title>${esc(b.label)} নির্মাণ ${tk(b.construction)}</title></rect>`;
    g += `<text x="${x + cw / 2}" y="${H - 10}" text-anchor="middle" font-size="10" fill="var(--muted)">${esc(b.label)}</text>`; });
  return `<svg viewBox="0 0 ${W} ${H}" role="img" aria-label="আয়-ব্যয় চার্ট">${g}</svg>`;
}
function renderDash() {
  const t = D.today, Y = t.slice(0, 4), M = t.slice(5, 7);
  $("todayTitle").textContent = "আজ · " + fmtDate(t); $("todayKpis").innerHTML = kpiHTML(sums(inRange(t, t)));
  $("monthTitle").textContent = "এই মাস · " + BN_MONTHS[+M - 1] + " " + bnd(Y); $("monthKpis").innerHTML = kpiHTML(sums(inRange(`${Y}-${M}-01`, `${Y}-${M}-31`)));
  const b = []; for (let i = 13; i >= 0; i--) { const d = addDays(t, -i); b.push({label: bnd(+d.slice(8)), ...sums(inRange(d, d))}); }
  $("dashChart").innerHTML = barChart(b);
  const proj = groupBy(live().filter(r => r.kind === "construction"), "project");
  $("projList").innerHTML = proj.length ? `<table><tbody>${proj.map(([p, v]) => `<tr><td>${esc(p)}</td><td class="n con">${tk(v)}</td></tr>`).join("")}</tbody></table>` : `<div class="empty">এখনো নির্মাণ খরচের এন্ট্রি নেই।</div>`;
  renderPranamiStatus();
}

/* summary tab: monthly pranami status for the current month and the 2 before it */
let dashTab = "money";
document.querySelectorAll("#dashSeg button").forEach(b => b.onclick = () => {
  dashTab = b.dataset.d;
  document.querySelectorAll("#dashSeg button").forEach(x => x.setAttribute("aria-pressed", x === b));
  $("dashMoney").hidden = dashTab !== "money"; $("dashPranami").hidden = dashTab !== "pranami";
});
function lastMonths(n) {
  const out = []; let [y, m] = D.today.slice(0, 7).split("-").map(Number);
  for (let i = 0; i < n; i++) { out.unshift(`${y}-${String(m).padStart(2, "0")}`); if (--m < 1) { m = 12; y--; } }
  return out;
}
/* who is expected to pay in a month: started on or before it (members brought in from old records count from the start) */
const pmStart = p => (p.date || (p.imported ? "" : (p.createdAt || ""))).slice(0, 7);
function pmMonth(ppl, ym) {
  const due = ppl.filter(p => !pmStart(p) || pmStart(p) <= ym);
  const paid = due.filter(p => p.paid?.[ym]);
  return {total: due.length, paid: paid.length, unpaid: due.length - paid.length,
    got: paid.reduce((s, p) => s + Number(p.paid[ym].amount || 0), 0),
    owed: due.filter(p => !p.paid?.[ym]).reduce((s, p) => s + Number(p.amount || 0), 0)};
}
function renderPranamiStatus() {
  const ppl = D.people.filter(p => p.list === "monthly" && !p.deleted);
  const months = lastMonths(3), cur = D.today.slice(0, 7);
  $("pm3").innerHTML = months.map(ym => { const r = pmMonth(ppl, ym), pct = r.total ? Math.round(r.paid / r.total * 100) : 0;
    return `<div class="pmc${ym === cur ? " cur" : ""}"><h3><span>${monthName(ym)}</span>${ym === cur ? '<span class="chip wait">চলতি মাস</span>' : ""}</h3>
      <div class="big"><button class="pmbtn" data-pm="${ym}|paid|"><b class="inc">${bnd(r.paid)}</b><span>পেইড ›</span></button><button class="pmbtn" data-pm="${ym}|due|"><b class="exp">${bnd(r.unpaid)}</b><span>বকেয়া ›</span></button><div><b>${bnd(r.total)}</b><span>মোট সদস্য</span></div></div>
      <div class="pmbar" role="img" aria-label="${bnd(pct)}% পেইড"><i style="width:${pct}%"></i></div>
      <div class="s">${bnd(pct)}% আদায় · জমা ${tk(r.got)} · বকেয়া প্রায় ${tk(r.owed)}</div></div>`; }).join("");
  const areas = D.areas.filter(a => ppl.some(p => p.area === a));
  $("pmArea").innerHTML = areas.length ? `<table><thead><tr><th>এলাকা</th>${months.map(ym => `<th class="n">${BN_MS[+ym.slice(5) - 1]}</th>`).join("")}</tr></thead><tbody>${areas.map(a => { const pa = ppl.filter(p => p.area === a);
      return `<tr><td>${esc(a)}</td>${months.map(ym => { const r = pmMonth(pa, ym); return `<td class="n"><button class="pmlink" data-pm="${ym}|paid|${esc(a)}"><b class="inc">${bnd(r.paid)}</b></button> / ${bnd(r.total)}${r.unpaid ? `<br><button class="pmlink note exp" data-pm="${ym}|due|${esc(a)}">${bnd(r.unpaid)} বকেয়া</button>` : ""}</td>`; }).join("")}</tr>`; }).join("")}</tbody></table>`
    : `<div class="empty">মাসিক প্রণামীর তালিকায় এখনো কেউ নেই।</div>`;
}

/* tap a paid / due figure: the list of those devotees */
function pmList(ym, kind, area) {
  let ppl = D.people.filter(p => p.list === "monthly" && !p.deleted && (!pmStart(p) || pmStart(p) <= ym));
  if (area) ppl = ppl.filter(p => p.area === area);
  const draw = (k, q) => {
    const l = ppl.filter(p => (k === "paid") === !!p.paid?.[ym]).filter(p => matches(p, q))
      .sort((a, b) => a.area.localeCompare(b.area, "bn") || a.name.localeCompare(b.name, "bn"));
    const sum = l.reduce((s, p) => s + Number(k === "paid" ? p.paid[ym].amount : p.amount || 0), 0);
    return `<p class="note">${bnd(l.length)} জন · ${k === "paid" ? "জমা" : "বকেয়া প্রায়"} ${tk(sum)}</p>` + (l.length ? l.map((p, i) => `<div class="item"><div class="sn">${bnd(i + 1)}</div>
      <div><div class="t">${esc(p.name)}</div><div class="s">${esc([area ? "" : p.area, p.address, p.mobile].filter(Boolean).join(" · "))}</div>
      ${k === "paid" ? `<div class="s">জমা ${fmtDate(p.paid[ym].date)}${p.paid[ym].by ? " · " + esc(D.names[p.paid[ym].by] || "") : ""}</div>` : ""}</div>
      <div class="amt ${k === "paid" ? "inc" : "exp"}">${tk(k === "paid" ? p.paid[ym].amount : p.amount)}</div></div>`).join("") : `<div class="empty">কেউ নেই।</div>`);
  };
  openSheet(`${monthName(ym)}${area ? " · " + area : ""}`, `<div class="seg" style="grid-template-columns:1fr 1fr" id="pmSeg">
      <button type="button" data-k="income" data-pk="paid" aria-pressed="${kind === "paid"}">পেইড</button><button type="button" data-k="daily" data-pk="due" aria-pressed="${kind === "due"}">বকেয়া</button></div>
    <input class="search" id="pmQ" placeholder="নাম, ঠিকানা বা মোবাইল খুঁজুন" style="margin-bottom:6px"><div id="pmBody">${draw(kind, "")}</div>`, b => {
    const upd = () => b.querySelector("#pmBody").innerHTML = draw(kind, b.querySelector("#pmQ").value);
    b.querySelectorAll("#pmSeg button").forEach(x => x.onclick = () => { kind = x.dataset.pk; b.querySelectorAll("#pmSeg button").forEach(y => y.setAttribute("aria-pressed", y === x)); upd(); });
    b.querySelector("#pmQ").addEventListener("input", upd);
  });
}
$("dashPranami").addEventListener("click", e => { const b = e.target.closest("[data-pm]"); if (!b) return; const [ym, k, a] = b.dataset.pm.split("|"); pmList(ym, k, a); });

/* ================= admin: daily entries by user ================= */
$("dailyPick").addEventListener("input", renderDaily);
function renderDaily() {
  const d = $("dailyPick").value || D.today;
  const led = live().filter(r => r.date === d || (r.createdAt || "").slice(0, 10) === d);
  const ppl = D.people.filter(p => (p.createdAt || "").slice(0, 10) === d);
  const uids = [...new Set([...led.map(r => r.by), ...ppl.map(p => p.by)])];
  if (!uids.length) { $("dailyBody").innerHTML = `<div class="card"><div class="empty">${fmtDate(d)} তারিখে কেউ কোনো এন্ট্রি দেননি।</div></div>`; return; }
  $("dailyBody").innerHTML = uids.map(uid => {
    const l = led.filter(r => r.by === uid).sort((a, b) => String(a.createdAt).localeCompare(String(b.createdAt)));
    const pp = ppl.filter(p => p.by === uid); const s = sums(l);
    return `<div class="card"><div class="card-head"><h2>${esc(D.names[uid] || "অজানা ইউজার")}</h2><span class="muted">${bnd(l.length)}টি হিসাব${pp.length ? ", " + bnd(pp.length) + " জন নতুন নাম" : ""}</span></div>
      <div class="daysum"><span>জমা <b class="inc">${tk(s.income)}</b></span><span>দৈনিক খরচ <b class="exp">${tk(s.daily)}</b></span><span>নির্মাণ <b class="con">${tk(s.construction)}</b></span></div>
      ${l.map(r => entryRow(r, {ops: true}) .replace('<div class="s">', `<div class="s">${r.date !== d ? "হিসাবের তারিখ " + fmtDate(r.date) + " · " : ""}এন্ট্রির সময় ${esc((r.createdAt || "").slice(11, 16))} · `)).join("")}
      ${pp.length ? `<h3 class="sub" style="margin-top:10px">তালিকায় নতুন যোগ</h3>` + pp.map(p => `<div class="item"><div class="sn"></div><div><div class="t">${esc(p.name)}</div><div class="s">${LIST_LABEL[p.list]} · ${esc([p.area, p.address, p.mobile].filter(Boolean).join(" · "))}</div></div><div class="amt">${p.amount ? tk(p.amount) : ""}</div></div>`).join("") : ""}</div>`;
  }).join("");
}
$("dailyBody").addEventListener("click", e => { const t = e.target.closest("button"); if (!t) return; if (t.dataset.edit) editEntry(t.dataset.edit); else if (t.dataset.del) deleteEntry(t.dataset.del); });

/* ================= admin: reports ================= */
function initReport() { const t = D.today; $("rMonth").value = t.slice(0, 7); $("rYear").value = t.slice(0, 4); $("rFrom").value = t.slice(0, 7) + "-01"; $("rTo").value = t; }
["rType", "rMonth", "rYear", "rHalf", "rFrom", "rTo"].forEach(id => $(id).addEventListener("input", renderReport));
function period() {
  const t = $("rType").value; ["rMonthW", "rYearW", "rHalfW", "rFromW", "rToW"].forEach(id => $(id).hidden = true);
  if (t === "month") { $("rMonthW").hidden = false; const [y, m] = $("rMonth").value.split("-"); return {a: `${y}-${m}-01`, b: `${y}-${m}-31`, title: `মাসিক রিপোর্ট · ${BN_MONTHS[+m - 1]} ${bnd(y)}`, by: "day"}; }
  const y = +$("rYear").value;
  if (t === "half") { $("rYearW").hidden = false; $("rHalfW").hidden = false; return $("rHalf").value === "1" ? {a: `${y}-01-01`, b: `${y}-06-30`, title: `ষাণ্মাসিক রিপোর্ট · জানুয়ারি–জুন ${bnd(y)}`, by: "month"} : {a: `${y}-07-01`, b: `${y}-12-31`, title: `ষাণ্মাসিক রিপোর্ট · জুলাই–ডিসেম্বর ${bnd(y)}`, by: "month"}; }
  if (t === "year") { $("rYearW").hidden = false; return {a: `${y}-01-01`, b: `${y}-12-31`, title: `বার্ষিক রিপোর্ট · ${bnd(y)}`, by: "month"}; }
  if (t === "fy") { $("rYearW").hidden = false; return {a: `${y}-07-01`, b: `${y + 1}-06-30`, title: `অর্থবছর ${bnd(y)}–${bnd(y + 1)} রিপোর্ট`, by: "month"}; }
  $("rFromW").hidden = false; $("rToW").hidden = false; return {a: $("rFrom").value, b: $("rTo").value, title: `রিপোর্ট · ${fmtDate($("rFrom").value)} থেকে ${fmtDate($("rTo").value)}`, by: "month"};
}
function catTable(list, cls) { const g = groupBy(list, "category"); if (!g.length) return `<div class="empty">—</div>`; const tot = g.reduce((s, x) => s + x[1], 0);
  return `<table><tbody>${g.map(([c, v]) => `<tr><td>${esc(c)}</td><td class="n">${bn((v / tot * 100).toFixed(1))}%</td><td class="n ${cls}">${tk(v)}</td></tr>`).join("")}<tr class="tot"><td>মোট</td><td></td><td class="n ${cls}">${tk(tot)}</td></tr></tbody></table>`; }
let curPeriod = null;
function renderReport() {
  if (!isAdmin()) return;
  const p = period(); curPeriod = p;
  if (!p.a || !p.b || p.a.includes("undefined")) { $("reportBody").innerHTML = `<div class="empty">সময়সীমা বেছে নিন।</div>`; return; }
  const list = inRange(p.a, p.b), s = sums(list);
  const opening = sums(live().filter(r => r.date < p.a)).net, closing = opening + s.net;
  const buckets = [];
  if (p.by === "day") { for (let d = p.a; d <= p.b && d.slice(0, 7) === p.a.slice(0, 7); d = addDays(d, 1)) buckets.push({key: d, label: bnd(+d.slice(8)), ...sums(list.filter(r => r.date === d))}); }
  else { let [y, m] = p.a.split("-").map(Number); const end = p.b.slice(0, 7); for (;;) { const k = `${y}-${String(m).padStart(2, "0")}`; if (k > end) break; buckets.push({key: k, label: BN_MS[m - 1], ...sums(list.filter(r => r.date.startsWith(k)))}); if (++m > 12) { m = 1; y++; } } }
  const donors = groupBy(list.filter(r => r.kind === "income" && r.name), "name");
  const proj = groupBy(list.filter(r => r.kind === "construction"), "project");
  const active = buckets.filter(b => b.income || b.cost);
  $("reportBody").innerHTML = `<div class="rhead"><h2>${p.title}</h2><span class="note">শ্রীশ্রী বাসুদেব যোগাশ্রম, নেহালপুর · প্রস্তুত ${fmtDate(D.today)}</span></div>
    <div class="kpis">${kpiHTML(s)}</div>
    <p class="bal">প্রারম্ভিক জের ${signed(opening)} + এই সময়ের উদ্বৃত্ত ${signed(s.net)} = সমাপনী জের <span class="${closing < 0 ? "exp" : "inc"}">${signed(closing)}</span></p>
    <div class="chart" style="margin-block:14px">${barChart(buckets)}</div>
    <div class="two"><div><h3 class="sub inc">আয়ের খাত</h3>${catTable(list.filter(r => r.kind === "income"), "inc")}</div><div><h3 class="sub exp">দৈনিক খরচের খাত</h3>${catTable(list.filter(r => r.kind === "daily"), "exp")}</div></div>
    <div class="two" style="margin-top:14px"><div><h3 class="sub con">নির্মাণ: প্রকল্প অনুযায়ী</h3>${proj.length ? `<table><tbody>${proj.map(([c, v]) => `<tr><td>${esc(c)}</td><td class="n con">${tk(v)}</td></tr>`).join("")}</tbody></table>` : `<div class="empty">—</div>`}
      <h3 class="sub con" style="margin-top:12px">নির্মাণ: উপকরণ অনুযায়ী</h3>${catTable(list.filter(r => r.kind === "construction"), "con")}</div>
      <div><h3 class="sub">দাতা অনুযায়ী জমা</h3>${donors.length ? `<div class="tbl"><table><tbody>${donors.slice(0, 50).map(([c, v]) => `<tr><td>${esc(c)}</td><td class="n inc">${tk(v)}</td></tr>`).join("")}</tbody></table></div>${donors.length > 50 ? `<p class="note">আরও ${bnd(donors.length - 50)} জন CSV-তে আছেন।</p>` : ""}` : `<div class="empty">—</div>`}</div></div>
    <h3 class="sub" style="margin-top:16px">${p.by === "day" ? "দিনভিত্তিক" : "মাসভিত্তিক"} হিসাব</h3>
    <div class="tbl"><table><thead><tr><th>${p.by === "day" ? "তারিখ" : "মাস"}</th><th class="n">জমা</th><th class="n">দৈনিক খরচ</th><th class="n">নির্মাণ</th><th class="n">উদ্বৃত্ত</th></tr></thead><tbody>
    ${active.map(b => `<tr><td>${p.by === "day" ? fmtDate(b.key) : monthName(b.key)}</td><td class="n inc">${tk(b.income)}</td><td class="n exp">${tk(b.daily)}</td><td class="n con">${tk(b.construction)}</td><td class="n">${signed(b.net)}</td></tr>`).join("") || `<tr><td colspan="5" class="empty">এই সময়ে কোনো এন্ট্রি নেই।</td></tr>`}
    <tr class="tot"><td>মোট</td><td class="n inc">${tk(s.income)}</td><td class="n exp">${tk(s.daily)}</td><td class="n con">${tk(s.construction)}</td><td class="n">${signed(s.net)}</td></tr></tbody></table></div>`;
}
async function download(what, from, to) { const j = await api("export_link", {what, from, to}); window.location.href = j.url; }
$("csvBtn").onclick = e => guard(e.target, () => download("ledger", curPeriod?.a, curPeriod?.b));
$("csvPeople").onclick = e => guard(e.target, () => download("people"));
$("backupBtn").onclick = () => { closeMenu(); guard(null, async () => { await download("backup"); toast("ব্যাকআপ ফাইল ডাউনলোড হচ্ছে। এটা Google Drive বা পেনড্রাইভে রেখে দিন।"); }); };

/* ================= admin: users & areas ================= */
async function renderUsers() {
  renderAreasCats();
  let j; try { j = await api("users"); } catch (e) { $("userList").innerHTML = `<div class="msg">${esc(e.message)}</div>`; return; }
  const n = j.users.filter(u => u.role === "user" && u.active).length;
  $("userList").innerHTML = `<div class="card-head"><h3 class="sub">ইউজার (${bnd(n)} / ${bnd(j.max)}) ও অ্যাডমিন</h3></div>` + j.users.map(u => `<div class="item"><div class="sn">${u.role === "admin" ? "★" : ""}</div>
    <div><div class="t">${esc(u.name)}${u.id === me.id ? " (আপনি)" : ""}</div><div class="s">আইডি: ${esc(u.username)} · ${u.role === "admin" ? "অ্যাডমিন" : "ইউজার"} ${u.active ? "" : '<span class="chip bad">বন্ধ</span>'}</div>
    <div class="ops" style="margin-top:6px">
      <button class="btn ghost small" data-rename="${u.id}" data-n="${esc(u.name)}" data-u="${esc(u.username)}">নাম / আইডি বদল</button>
      ${u.id !== me.id ? `<button class="btn ghost small" data-reset="${u.id}" data-n="${esc(u.name)}">পাসওয়ার্ড রিসেট</button>
      <button class="btn ghost small" data-role="${u.id}" data-to="${u.role === "admin" ? "user" : "admin"}" data-n="${esc(u.name)}">${u.role === "admin" ? "অ্যাডমিন থেকে সরান" : "অ্যাডমিন বানান"}</button>
      <button class="btn ghost small" data-toggle="${u.id}">${u.active ? "বন্ধ করুন" : "চালু করুন"}</button>` : ""}
    </div></div><div></div></div>`).join("") + (j.users.filter(u => u.role === "admin" && u.active).length < 2 ? `<p class="banner" style="margin-top:8px">এখন অ্যাডমিন মাত্র একজন। পাসওয়ার্ড ভুলে গেলে আর ঢোকার উপায় থাকবে না, তাই বিশ্বস্ত আরেকজনকে অ্যাডমিন বানিয়ে রাখুন।</p>` : "");
}
$("userList").addEventListener("click", e => {
  const b = e.target.closest("button"); if (!b) return;
  if (b.dataset.toggle) guard(b, async () => { await api("user_toggle", {id: b.dataset.toggle}); toast("হালনাগাদ হয়েছে।"); renderUsers(); });
  if (b.dataset.reset) openSheet("পাসওয়ার্ড রিসেট: " + b.dataset.n, `<form class="form" id="rsF"><label>নতুন পাসওয়ার্ড<input id="rsP" required minlength="6"></label><button class="btn wide">রিসেট করুন</button></form>`, bb => {
    bb.querySelector("#rsF").onsubmit = ev => { ev.preventDefault(); guard(ev.submitter, async () => { await api("user_reset", {id: b.dataset.reset, password: bb.querySelector("#rsP").value}); closeSheet(); toast("পাসওয়ার্ড বদলানো হয়েছে। নতুন পাসওয়ার্ড ইউজারকে জানিয়ে দিন।"); }); };
  });
  if (b.dataset.rename) openSheet("নাম / আইডি বদল", `<form class="form" id="rnF"><label>নাম<input id="rnN" value="${esc(b.dataset.n)}" required></label>
    <label>ইউজার আইডি (ইংরেজিতে)<input id="rnU" value="${esc(b.dataset.u)}" autocapitalize="none" spellcheck="false" required></label>
    <p class="note">আইডি বদলালে পরের লগইন থেকে নতুন আইডি দিতে হবে। পাসওয়ার্ড আগেরটাই থাকবে।</p><button class="btn wide">সংরক্ষণ করুন</button></form>`, bb => {
    bb.querySelector("#rnF").onsubmit = ev => { ev.preventDefault(); guard(ev.submitter, async () => {
      await api("user_update", {id: b.dataset.rename, name: bb.querySelector("#rnN").value, username: bb.querySelector("#rnU").value});
      closeSheet(); toast("হালনাগাদ হয়েছে।"); await reload(); if (b.dataset.rename === me.id) $("whoami").textContent = me.name + " · অ্যাডমিন"; renderUsers(); }); };
  });
  if (b.dataset.role) {
    const toAdmin = b.dataset.to === "admin";
    openSheet(toAdmin ? "অ্যাডমিন বানাবেন?" : "অ্যাডমিন থেকে সরাবেন?", `<p><b>${esc(b.dataset.n)}</b> ${toAdmin ? "অ্যাডমিন হলে সব হিসাব, মোট টাকা, রিপোর্ট দেখতে পারবেন এবং যেকোনো এন্ট্রি সম্পাদনা ও মুছতে পারবেন। শুধু খুব বিশ্বস্ত কাউকে অ্যাডমিন বানান।" : "সাধারণ ইউজার হয়ে যাবেন। মোট হিসাব আর দেখতে পারবেন না।"}</p>
      <p class="note">এরপর তাঁকে আবার লগইন করতে হবে।</p><button class="btn wide" id="roleOk">${toAdmin ? "হ্যাঁ, অ্যাডমিন বানান" : "হ্যাঁ, সরান"}</button>`, bb => {
      bb.querySelector("#roleOk").onclick = ev => guard(ev.target, async () => { await api("user_role", {id: b.dataset.role, role: b.dataset.to}); closeSheet(); toast("হালনাগাদ হয়েছে।"); await reload(); renderUsers(); });
    });
  }
});

/* areas and categories */
function renderAreasCats() {
  $("areaList").innerHTML = D.areas.map(a => {
    const n = D.people.filter(p => p.list === "monthly" && !p.deleted && p.area === a).length;
    return `<div class="item"><div class="sn"></div><div><div class="t">${esc(a)}</div><div class="s">${bnd(n)} জন</div>
      ${a === "অন্যান্য" ? "" : `<div class="ops"><button class="btn ghost small" data-arename="${esc(a)}">নাম বদল</button><button class="btn ghost small" data-adel="${esc(a)}">মুছুন</button></div>`}</div><div></div></div>`;
  }).join("");
  const kindSel = $("catKind").value || "income";
  $("catList").innerHTML = cats(kindSel).map((c, i) => `<div class="item" style="padding:8px 0"><div class="sn">${bnd(i + 1)}</div><div class="t" style="font-weight:500">${esc(c)}</div>
    <div class="ops" style="grid-column:auto">${i ? `<button class="btn ghost small" data-cup="${esc(c)}" aria-label="উপরে তুলুন">↑</button>` : ""}${c === "মাসিক প্রণামী" ? "" : `<button class="btn ghost small" data-crem="${esc(c)}" aria-label="সরান">✕</button>`}</div></div>`).join("");
}
$("catKind").addEventListener("change", renderAreasCats);
$("catList").addEventListener("click", e => { const b = e.target.closest("button"); if (!b) return;
  const kindSel = $("catKind").value;
  if (b.dataset.cup) guard(b, async () => { const j = await api("cat_move", {kind: kindSel, name: b.dataset.cup}); D.cats = j.cats; renderAreasCats(); setKind(kind); });
  if (b.dataset.crem) openSheet("খাত সরাবেন?", `<p><b>${esc(b.dataset.crem)}</b> খাতটি এন্ট্রির তালিকা থেকে সরে যাবে। আগের এন্ট্রিগুলোতে এই খাতের নাম যেমন আছে তেমনই থাকবে, রিপোর্টেও দেখাবে।</p><button class="btn danger wide" id="crOk">সরান</button>`, bb => {
    bb.querySelector("#crOk").onclick = ev => guard(ev.target, async () => { const j = await api("cat_remove", {kind: kindSel, name: b.dataset.crem}); D.cats = j.cats; closeSheet(); renderAreasCats(); setKind(kind); toast("খাত সরানো হয়েছে।"); });
  });
});
$("catForm").addEventListener("submit", e => { e.preventDefault(); guard(e.submitter, async () => {
  const j = await api("cat_add", {kind: $("catKind").value, name: $("catName").value}); D.cats = j.cats; $("catName").value = ""; renderAreasCats(); setKind(kind); toast("খাত যোগ হয়েছে।"); }); });
$("areaList").addEventListener("click", e => { const b = e.target.closest("button"); if (!b) return;
  if (b.dataset.arename) openSheet("এলাকার নাম বদল", `<form class="form" id="arF"><label>নতুন নাম<input id="arN" value="${esc(b.dataset.arename)}" required></label><p class="note">এই এলাকার সব ভক্ত নতুন নামের এলাকায় চলে যাবেন।</p><button class="btn wide">সংরক্ষণ করুন</button></form>`, bb => {
    bb.querySelector("#arF").onsubmit = ev => { ev.preventDefault(); guard(ev.submitter, async () => { await api("area_rename", {old: b.dataset.arename, name: bb.querySelector("#arN").value}); closeSheet(); await reload(); renderAreasCats(); toast("নাম বদলানো হয়েছে।"); }); };
  });
  if (b.dataset.adel) guard(b, async () => { await api("area_delete", {old: b.dataset.adel}); await reload(); renderAreasCats(); toast("এলাকা মুছে ফেলা হয়েছে।"); });
});

/* import a list from a CSV file (Excel "Save as CSV UTF-8") */
function parseCSV(text) {
  const rows = []; let row = [], cell = "", q = false;
  text = text.replace(/^﻿/, "");
  for (let i = 0; i < text.length; i++) {
    const ch = text[i];
    if (q) { if (ch === '"') { if (text[i + 1] === '"') { cell += '"'; i++; } else q = false; } else cell += ch; }
    else if (ch === '"') q = true;
    else if (ch === ",") { row.push(cell); cell = ""; }
    else if (ch === "\n" || ch === "\r") { if (ch === "\r" && text[i + 1] === "\n") i++; row.push(cell); rows.push(row); row = []; cell = ""; }
    else cell += ch;
  }
  if (cell !== "" || row.length) { row.push(cell); rows.push(row); }
  return rows.filter(r => r.some(c => c.trim() !== ""));
}
const IMPORT_COLS = [["memberNo", /নং|id|no\b|ক্রম|s\s*\/\s*n|serial/i], ["name", /নাম|name/i], ["mobile", /মোবাইল|ফোন|mobile|phone/i], ["address", /ঠিকানা|address/i],
  ["land", /জমি|land/i], ["details", /মৌজা|দাগ|mouza|dag/i], ["area", /এলাকা|থানা|area/i], ["amount", /টাকা|পরিমাণ|ফি|amount/i], ["date", /তারিখ|date/i], ["note", /মন্তব্য|note/i]];
$("importFile").addEventListener("change", async e => {
  const f = e.target.files[0]; e.target.value = ""; if (!f) return;
  const list = $("importList").value;
  const rows = parseCSV(await f.text());
  if (rows.length < 2) { toast("ফাইলে কোনো তথ্য পাওয়া যায়নি।"); return; }
  const head = rows[0].map(h => h.trim()), map = {};
  head.forEach((h, i) => { const hit = IMPORT_COLS.find(([k, re]) => !(k in map) && re.test(h) && !(k === "amount" && /জমি/.test(h))); if (hit) map[hit[0]] = i; });
  if (!("name" in map)) { toast("ফাইলের প্রথম সারিতে \"নাম\" কলাম পাওয়া যায়নি।"); return; }
  const data = rows.slice(1).map(r => Object.fromEntries(Object.entries(map).map(([k, i]) => [k, (r[i] || "").trim()]))).filter(r => r.name);
  const withMob = data.filter(r => r.mobile).length;
  openSheet("তালিকা ইমপোর্ট: " + LIST_LABEL[list], `<form class="form" id="impF">
    <p>${bnd(data.length)} জনের নাম পাওয়া গেছে, তার মধ্যে ${bnd(withMob)} জনের মোবাইল নম্বর আছে।</p>
    <p class="note">যে কলামগুলো চেনা গেছে: ${Object.keys(map).map(k => ({memberNo: "নং", name: "নাম", mobile: "মোবাইল", address: "ঠিকানা", land: "জমি", details: "মৌজা/দাগ", area: "এলাকা", amount: "টাকা", date: "তারিখ", note: "মন্তব্য"})[k]).join(", ")}</p>
    <div class="tbl"><table><tbody>${data.slice(0, 5).map(r => `<tr><td>${esc(r.memberNo || "")}</td><td>${esc(r.name)}</td><td>${esc(r.mobile || "")}</td></tr>`).join("")}</tbody></table></div>
    ${data.length > 5 ? `<p class="note">… আরও ${bnd(data.length - 5)} জন</p>` : ""}
    <p class="banner">একই নং আগে থেকে তালিকায় থাকলে নতুন করে যোগ হবে না, শুধু তাঁর ফাঁকা ঘর (ঠিকানা, মোবাইল ইত্যাদি) ফাইল থেকে পূরণ হবে। আগে থেকে লেখা কিছু বদলাবে না।${list === "life" ? " আজীবন সদস্যরা \"পুরনো সদস্য\" হিসেবে উঠবেন, আয়ে কিছু যোগ হবে না।" : ""}</p>
    <button class="btn wide">ইমপোর্ট করুন</button></form>`, b => {
    b.querySelector("#impF").onsubmit = ev => { ev.preventDefault(); guard(ev.submitter, async () => {
      const j = await api("people_import", {list, rows: data});
      closeSheet(); await reload(); render();
      toast(`${bnd(j.added)} জন যোগ হয়েছে` + (j.updated ? `, ${bnd(j.updated)} জনের ফাঁকা তথ্য (ঠিকানা ইত্যাদি) পূরণ হয়েছে` : "") + (j.skipped ? `, ${bnd(j.skipped)} জন আগে থেকেই ছিলেন` : "") + (j.badMobile.length ? `, ${bnd(j.badMobile.length)} জনের মোবাইল নম্বর ঠিক না থাকায় নম্বর ছাড়া উঠেছে` : "") + "।");
    }); };
  });
});

/* restore from a backup file */
$("restoreFile").addEventListener("change", async e => {
  const f = e.target.files[0]; e.target.value = ""; if (!f) return;
  let b; try { b = JSON.parse(await f.text()); } catch (err) { toast("ফাইলটি পড়া যায়নি। সঠিক ব্যাকআপ ফাইল বেছে নিন।"); return; }
  if (!b || b.app !== "basudev-hisab" || !b.ledger || !b.people) { toast("এটা বাসুদেব যোগাশ্রম হিসাবের ব্যাকআপ ফাইল নয়।"); return; }
  const n = Object.values(b.ledger).reduce((s, r) => s + (Array.isArray(r) ? r.length : 0), 0);
  openSheet("ব্যাকআপ থেকে পুনরুদ্ধার", `<form class="form" id="rstF"><p>ব্যাকআপের সময়: <b>${esc(b.created || "?")}</b><br>${bnd(n)}টি আয়-ব্যয় এন্ট্রি, ${bnd(b.people.length)} জনের তালিকা।</p>
    <p class="banner">এখনকার সব হিসাব আর তালিকা এই ব্যাকআপ দিয়ে বদলে যাবে। ব্যাকআপের পরে যা এন্ট্রি হয়েছে তা চলে যাবে। ইউজার ও পাসওয়ার্ড বদলাবে না। এখনকার ডেটার একটা কপি সার্ভারে আলাদা করে রাখা হবে।</p>
    <label class="chk"><input type="checkbox" id="rstC" required> আমি বুঝেছি, এখনকার হিসাব ব্যাকআপ দিয়ে বদলে যাবে</label><button class="btn danger wide">পুনরুদ্ধার করুন</button></form>`, bb => {
    bb.querySelector("#rstF").onsubmit = ev => { ev.preventDefault(); guard(ev.submitter, async () => {
      const j = await api("restore", {backup: b, confirm: bb.querySelector("#rstC").checked ? "yes" : ""});
      closeSheet(); await reload(); render(); toast(`পুনরুদ্ধার হয়েছে: ${bnd(j.entries)}টি এন্ট্রি, ${bnd(j.people)} জনের তালিকা।`); }); };
  });
});
$("restoreBtn").onclick = () => { closeMenu(); go("users"); setTimeout(() => $("restoreCard").scrollIntoView({behavior: "smooth"}), 100); };

$("userForm").addEventListener("submit", e => { e.preventDefault(); guard(e.submitter, async () => {
  await api("user_add", {name: $("uName").value, username: $("uUser").value, password: $("uPass").value});
  toast(`ইউজার যোগ হয়েছে। আইডি: ${$("uUser").value.trim().toLowerCase()}`); $("userForm").reset(); await reload(); renderUsers(); }); });
$("areaForm").addEventListener("submit", e => { e.preventDefault(); guard(e.submitter, async () => { const j = await api("area_add", {name: $("areaName").value}); D.areas = j.areas; $("areaName").value = ""; renderAreasCats(); toast("এলাকা যোগ হয়েছে।"); }); });

/* ================= admin: invitation SMS for yearly events ================= */
const INV_DEFAULT = "জয় গুরু। {নাম}, {তারিখ} শ্রীশ্রী বাসুদেব যোগাশ্রমে {অনুষ্ঠান} উপলক্ষে আপনাকে সপরিবারে আমন্ত্রণ জানাই। - বাসুদেব যোগাশ্রম, নেহালপুর";
let EV = {events: [], campaigns: []};
const smsParts = t => { const n = [...t].length; return n <= 70 ? 1 : Math.ceil(n / 67); };
function invRecipients(groups) {
  const seen = new Set();
  D.people.forEach(p => { if (!p.deleted && groups.includes(p.list) && p.mobile) seen.add(p.mobile); });
  return seen.size;
}
async function renderInvite() {
  $("smsOffNote").innerHTML = D.sms.enabled ? "" : `<p class="banner">SMS এখনো চালু হয়নি। অনুষ্ঠান লিখে রাখা যাবে, কিন্তু পাঠাতে আগে ☰ → "SMS সেটিংস"-এ Robi-র তথ্য দিয়ে চালু করতে হবে।</p>`;
  try { EV = await api("events"); } catch (e) { $("evList").textContent = e.message; return; }
  $("evList").innerHTML = EV.events.length ? EV.events.map(ev => `<div class="item"><div class="sn"></div><div>
      <div class="t">${esc(ev.name)}</div><div class="s">${esc(ev.when || "")}</div><div class="s" style="margin-top:4px">${esc(ev.template)}</div>
      <div class="ops" style="margin-top:8px"><button class="btn small" data-evsend="${ev.id}">আমন্ত্রণ পাঠান</button><button class="btn ghost small" data-evedit="${ev.id}">সম্পাদনা</button><button class="btn ghost small" data-evdel="${ev.id}">মুছুন</button></div>
    </div><div></div></div>`).join("") : `<div class="empty">এখনো কোনো অনুষ্ঠান লেখা হয়নি। উপরের "+ নতুন অনুষ্ঠান" চাপুন।</div>`;
  $("campList").innerHTML = EV.campaigns.length ? EV.campaigns.map(c => `<div class="item"><div class="sn"></div><div>
      <div class="t">${esc(c.event || "আমন্ত্রণ")}</div><div class="s">${esc(c.at)} · ${c.groups.map(g => LIST_LABEL[g]).join(", ")}</div>
      <div class="s"><span class="chip ok">${bnd(c.sent)} জন পেয়েছেন</span> ${c.failed ? `<span class="chip bad">${bnd(c.failed)} জন পাননি</span>` : ""} ${c.pending ? `<span class="chip wait">${bnd(c.pending)} বাকি</span>` : ""}</div>
      ${c.failed || c.pending ? `<div class="ops" style="margin-top:6px"><button class="btn ghost small" data-cresume="${c.id}" data-retry="${c.failed ? 1 : 0}">${c.pending ? "বাকিগুলো পাঠান" : "যাঁরা পাননি তাঁদের আবার পাঠান"}</button></div>` : ""}
    </div><div></div></div>`).join("") : `<div class="empty">এখনো কোনো আমন্ত্রণ পাঠানো হয়নি।</div>`;
}
function eventForm(ev) {
  openSheet(ev ? "অনুষ্ঠান সম্পাদনা" : "নতুন বার্ষিক অনুষ্ঠান", `<form class="form" id="evF">
    <label>অনুষ্ঠানের নাম<input id="evN" value="${esc(ev?.name || "")}" placeholder="যেমন: শ্রীশ্রী বাসুদেবানন্দ গিরি মহারাজের আবির্ভাব উৎসব" required></label>
    <label>তারিখ ও সময় (প্রতি বছর পাঠানোর আগে মিলিয়ে নেবেন)<input id="evW" value="${esc(ev?.when || "")}" placeholder="যেমন: ১৫ ফাল্গুন, শুক্রবার সকাল ১০টা"></label>
    <label>আমন্ত্রণের বার্তা ({নাম} {অনুষ্ঠান} {তারিখ} নিজে থেকে বসবে)<textarea id="evT" rows="4" required>${esc(ev?.template || INV_DEFAULT)}</textarea></label>
    <p class="note" id="evLen"></p><button class="btn wide">সংরক্ষণ করুন</button></form>`, b => {
    const len = () => { const t = b.querySelector("#evT").value; b.querySelector("#evLen").textContent = `প্রায় ${bnd([...t].length)} অক্ষর (নাম বসার আগে), প্রতি জনে ${bnd(smsParts(t))}টি SMS।`; };
    b.querySelector("#evT").addEventListener("input", len); len();
    b.querySelector("#evF").onsubmit = ev2 => { ev2.preventDefault(); guard(ev2.submitter, async () => {
      await api("event_save", {id: ev?.id || "", name: b.querySelector("#evN").value, when: b.querySelector("#evW").value, template: b.querySelector("#evT").value});
      closeSheet(); toast("অনুষ্ঠান সংরক্ষিত।"); renderInvite(); }); };
  });
}
$("evNew").onclick = () => eventForm(null);
function inviteSheet(ev) {
  const counts = {monthly: invRecipients(["monthly"]), land: invRecipients(["land"]), life: invRecipients(["life"])};
  openSheet("আমন্ত্রণ পাঠান: " + ev.name, `<form class="form" id="ivF">
    <label>তারিখ ও সময় (এ বছরের জন্য ঠিক করে নিন)<input id="ivW" value="${esc(ev.when || "")}"></label>
    <div><div class="note" style="margin-bottom:4px">কাদের পাঠাবেন</div>
      ${["monthly", "land", "life"].map(g => `<label class="chk"><input type="checkbox" name="ivG" value="${g}" checked> ${LIST_LABEL[g]}${g === "monthly" ? " দাতা" : ""} (${bnd(counts[g])} জন)</label>`).join("")}</div>
    <label>বার্তা<textarea id="ivT" rows="4">${esc(ev.template)}</textarea></label>
    <p class="banner" id="ivSum"></p>
    <label class="chk"><input type="checkbox" id="ivOk" required> বার্তা আর তারিখ মিলিয়ে দেখেছি, এখন পাঠান</label>
    <button class="btn wide" id="ivBtn">আমন্ত্রণ পাঠান</button>
    <p class="msg ok" id="ivProg"></p></form>`, b => {
    const groups = () => [...b.querySelectorAll("[name=ivG]:checked")].map(x => x.value);
    const upd = () => { const n = invRecipients(groups()), t = b.querySelector("#ivT").value.replace(/\{অনুষ্ঠান\}/g, ev.name).replace(/\{তারিখ\}/g, b.querySelector("#ivW").value).replace(/\{নাম\}/g, "শ্রী রতন দাশ"), parts = smsParts(t);
      b.querySelector("#ivSum").innerHTML = `${bnd(n)} জন পাবেন (একই নম্বর দুই তালিকায় থাকলে একবারই যাবে)। প্রতি জনে ${bnd(parts)}টি, মোট প্রায় ${bnd(n * parts)}টি SMS খরচ হবে।<br><span class="note">নমুনা: ${esc(t)}</span>`; };
    b.querySelectorAll("input,textarea").forEach(x => x.addEventListener("input", upd)); upd();
    b.querySelector("#ivF").onsubmit = e => { e.preventDefault(); guard(b.querySelector("#ivBtn"), async () => {
      const j = await api("invite_start", {name: ev.name, when: b.querySelector("#ivW").value, text: b.querySelector("#ivT").value, groups: groups()});
      await runInvite(j.id, false, b.querySelector("#ivProg"), j.total);
    }); };
  });
}
async function runInvite(id, retry, progEl, total) {
  let r, first = true;
  do {
    lastAct = Date.now(); // a long send is activity, so auto logout waits
    r = await api("invite_send", {id, retry: retry && first}); first = false;
    const all = r.sent + r.failed + r.pending;
    if (progEl) progEl.textContent = `পাঠানো হচ্ছে… ${bnd(r.sent + r.failed)} / ${bnd(total || all)}`;
  } while (!r.done);
  toast(`আমন্ত্রণ গেছে ${bnd(r.sent)} জনের কাছে` + (r.failed ? `, ${bnd(r.failed)} জনের কাছে যায়নি` : "") + "।");
  closeSheet(); if (view === "invite") renderInvite();
}
$("v-invite").addEventListener("click", e => { const b = e.target.closest("button"); if (!b) return;
  const ev = EV.events.find(x => x.id === (b.dataset.evsend || b.dataset.evedit || b.dataset.evdel));
  if (b.dataset.evsend) { if (!D.sms.enabled) { toast("আগে SMS সেটিংসে SMS চালু করুন।"); return; } inviteSheet(ev); }
  if (b.dataset.evedit) eventForm(ev);
  if (b.dataset.evdel) openSheet("অনুষ্ঠান মুছবেন?", `<p><b>${esc(ev.name)}</b> তালিকা থেকে মুছে যাবে। আগে পাঠানো আমন্ত্রণের হিসাব থেকে যাবে।</p><button class="btn danger wide" id="evDelOk">মুছুন</button>`, bb => {
    bb.querySelector("#evDelOk").onclick = x => guard(x.target, async () => { await api("event_delete", {id: ev.id}); closeSheet(); renderInvite(); });
  });
  if (b.dataset.cresume) { const c = EV.campaigns.find(x => x.id === b.dataset.cresume);
    openSheet("আবার পাঠান", `<p>${esc(c.event)}: ${bnd(c.failed + c.pending)} জনের কাছে পাঠানো হবে।</p><button class="btn wide" id="crOk">পাঠান</button><p class="msg ok" id="crProg"></p>`, bb => {
      bb.querySelector("#crOk").onclick = x => guard(x.target, () => runInvite(c.id, b.dataset.retry === "1", bb.querySelector("#crProg"), c.sent + c.failed + c.pending));
    }); }
});

/* ================= admin: SMS ================= */
async function loadSms() {
  try { const {cfg} = await api("sms_get");
    $("smsEnabled").checked = cfg.enabled; $("smsAuto").checked = cfg.auto; $("smsMethod").value = cfg.method; $("smsNum").value = cfg.number_format;
    $("smsUrl").value = cfg.url; $("smsParams").value = cfg.params; $("smsSuccess").value = cfg.success; $("smsTpl").value = cfg.template; smsLen();
  } catch (e) { toast(e.message); }
}
function smsLen() { const t = $("smsTpl").value.replace("{নাম}", "শ্রী রতন দাশ").replace("{টাকা}", "৫০০").replace("{তারিখ}", "৬ নভেম্বর ২০২৬").replace("{মাস}", "নভেম্বর ২০২৬").replace(/\{(রসিদ|ক্রমিক)\}/g, "১২");
  const n = [...t].length, parts = n <= 70 ? 1 : Math.ceil(n / 67); $("smsLen").textContent = `নমুনা বার্তা প্রায় ${bnd(n)} অক্ষর, অর্থাৎ প্রতি দাতায় ${bnd(parts)}টি SMS। ৭০ অক্ষরের মধ্যে রাখলে খরচ সবচেয়ে কম। {মাস} দিলে মাসিক প্রণামীর মাসের নাম বসবে।`; }
$("smsTpl").addEventListener("input", smsLen);
$("smsForm").addEventListener("submit", e => { e.preventDefault(); guard(e.submitter, async () => {
  await api("sms_set", {enabled: $("smsEnabled").checked, auto: $("smsAuto").checked, method: $("smsMethod").value, number_format: $("smsNum").value,
    url: $("smsUrl").value, params: $("smsParams").value, success: $("smsSuccess").value, template: $("smsTpl").value});
  await reload(); toast("SMS সেটিংস সংরক্ষিত।"); }); });
$("smsTestForm").addEventListener("submit", e => { e.preventDefault(); guard(e.submitter, async () => {
  const j = await api("sms_test", {mobile: $("smsTestNo").value}); $("smsTestMsg").className = "msg" + (j.sent ? " ok" : "");
  $("smsTestMsg").textContent = (j.sent ? "পাঠানো হয়েছে। " : "যায়নি। ") + j.info; }); });

/* ================= admin: records ================= */
async function renderRecords() {
  const del = D.ledger.filter(r => r.deleted);
  $("delLog").innerHTML = (del.length ? del.map(r => `<div class="item"><div class="sn">${bnd(r.serial)}</div><div><div class="t">${esc(r.name || r.category)} · ${fmtDate(r.date)}</div>
    <div class="s">${KLABEL[r.kind]} · ${esc(r.category)} · এন্ট্রি: ${esc(D.names[r.by] || "")}</div><div class="s">মুছেছেন ${esc(D.names[r.deletedBy] || "")}, ${esc(r.deletedAt)} · কারণ: ${esc(r.deleteReason)}</div>
    <div class="ops"><button class="btn ghost small" data-undel="${r.id}" data-y="${r.date.slice(0, 4)}">ফিরিয়ে আনুন</button></div></div><div class="amt">${tk(r.amount)}</div></div>`).join("") : `<div class="empty">কোনো এন্ট্রি মুছে ফেলা হয়নি।</div>`)
    + (D.people.some(p => p.deleted) ? `<h3 class="sub" style="margin-top:10px">তালিকা থেকে মুছে ফেলা নাম</h3>` + D.people.filter(p => p.deleted).map(p => `<div class="item"><div class="sn"></div><div><div class="t">${esc(p.name)}</div><div class="s">${LIST_LABEL[p.list]} · কারণ: ${esc(p.deleteReason)} · ${esc(p.deletedAt)}</div><div class="ops"><button class="btn ghost small" data-unp="${p.id}">ফিরিয়ে আনুন</button></div></div><div></div></div>`).join("") : "");
  const ed = D.ledger.filter(r => r.history?.length);
  $("editLog").innerHTML = ed.length ? `<div class="tbl"><table><thead><tr><th>এন্ট্রি</th><th>আগে</th><th>পরে</th><th>কবে</th></tr></thead><tbody>${ed.flatMap(r => r.history.map((h, i) => { const nx = r.history[i + 1] || r;
    return `<tr><td>${fmtDate(r.date)} · ক্রমিক ${bnd(r.serial)}</td><td>${tk(h.amount)} · ${esc(h.category)}${h.name ? " · " + esc(h.name) : ""}</td><td>${tk(nx.amount)} · ${esc(nx.category)}${nx.name ? " · " + esc(nx.name) : ""}</td><td>${esc(h.changedAt)}</td></tr>`; })).join("")}</tbody></table></div>` : `<div class="empty">কোনো এন্ট্রি সম্পাদনা হয়নি।</div>`;
  try { const {log} = await api("audit");
    $("auditLog").innerHTML = `<div class="tbl"><table><tbody>${log.slice(0, 200).map(l => `<tr><td style="white-space:nowrap">${esc(l.t)}</td><td>${esc(l.who)}</td><td>${esc(l.what)}${l.amount ? " · " + tk(l.amount) : ""}${l.reason ? " · " + esc(l.reason) : ""}</td></tr>`).join("")}</tbody></table></div>`;
  } catch (e) { $("auditLog").textContent = e.message; }
}
$("v-records").addEventListener("click", e => { const b = e.target.closest("button"); if (!b) return;
  if (b.dataset.undel) guard(b, async () => { await api("ledger_restore", {id: b.dataset.undel, year: b.dataset.y}); await reload(); renderRecords(); toast("ফিরিয়ে আনা হয়েছে।"); });
  if (b.dataset.unp) guard(b, async () => { await api("people_restore", {id: b.dataset.unp}); await reload(); renderRecords(); toast("ফিরিয়ে আনা হয়েছে।"); }); });

/* ================= password ================= */
$("pwForm").addEventListener("submit", e => { e.preventDefault(); guard(e.submitter, async () => { await api("password", {old: $("pwOld").value, new: $("pwNew").value}); $("pwForm").reset(); toast("পাসওয়ার্ড বদলানো হয়েছে।"); go("ledger"); }); });

/* Android back button support (called by the app shell) */
window.byaBack = function () {
  if (!$("sheet").hidden) { closeSheet(); return true; }
  if (!$("menu").hidden) { closeMenu(); return true; }
  if (view === "monthly" && curArea !== null) { curArea = null; renderMonthly(); return true; }
  const home = isAdmin() ? "dash" : "ledger";
  if (view !== home && !$("appScreen").hidden) { go(home); return true; }
  return false;
};
/* swipe between the 5 bottom tabs: finger to the left goes to the tab on the left, to the right goes right */
(function () {
  let x0 = null, y0 = 0, t0 = 0;
  const main = $("main");
  main.addEventListener("touchstart", e => {
    x0 = null;
    if (e.touches.length !== 1 || !TABS.includes(view) || !$("sheet").hidden || !$("menu").hidden) return;
    const t = e.touches[0];
    if (t.clientX < 24 || t.clientX > window.innerWidth - 24) return; // leave the phone's own edge-swipe alone
    if (e.target.closest("input,select,textarea,.mgrid-wrap,.chart,.tbl")) return; // these scroll sideways themselves
    x0 = t.clientX; y0 = t.clientY; t0 = Date.now();
  }, {passive: true});
  main.addEventListener("touchend", e => {
    if (x0 === null) return;
    const t = e.changedTouches[0], dx = t.clientX - x0, dy = t.clientY - y0;
    x0 = null;
    if (Math.abs(dx) < 70 || Math.abs(dy) > Math.abs(dx) * 0.6 || Date.now() - t0 > 800) return;
    const i = TABS.indexOf(view), next = TABS[i + (dx < 0 ? -1 : 1)];
    if (!next) return;
    if (next === "monthly") curArea = null;
    main.classList.remove("slide-l", "slide-r"); void main.offsetWidth;
    main.classList.add(dx < 0 ? "slide-r" : "slide-l");
    go(next);
  }, {passive: true});
})();
window.addEventListener("online", () => $("offline").hidden = true);
window.addEventListener("offline", () => $("offline").hidden = false);
boot();
