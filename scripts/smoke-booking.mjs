// Saltline booking flow. usage: node scripts/smoke-booking.mjs <base> [live|static]
//   live   (Playground): the booking is created in Events Manager and seats left drop by the party size.
//   static (export):    the Done screen shows, nothing is POSTed and no request leaves the host.
import { chromium } from 'playwright';
const [base, mode = 'static'] = process.argv.slice(2);
const host = new URL(base).host;
const b = await chromium.launch(); const p = await b.newPage({ viewport: { width: 390, height: 844 } });
const errors = [], posts = [], offHost = [];
p.on('pageerror', e => errors.push(e.message));
p.on('request', r => { if (r.method() !== 'GET') posts.push(r.method() + ' ' + new URL(r.url()).pathname); if (new URL(r.url()).host !== host) offHost.push(r.url()); });
p.on('framenavigated', f => { if (f === p.mainFrame() && /[?&]done=/.test(f.url())) out.firstUrl = f.url(); });
let target = null; // first sunrise departure with at least 4 seats left (live bookings from earlier runs reduce availability)
const left = async () => {
  const d = JSON.parse(await p.getAttribute('[data-booking]', 'data-avail'));
  if (!target) target = d.departures.find(x => x.tour === 'sunrise' && x.left >= 4);
  return d.departures.find(x => x.event === target.event).left;
};
const out = {};
await p.goto(base + '/book/?tour=sunrise-harbor-paddle', { waitUntil: 'networkidle' });
out.before = await left();
out.preselected = await p.isVisible('[data-s="date"]');
out.dateNextDisabled = await p.getAttribute('[data-s="date"] [data-next]', 'aria-disabled');
out.soldOutDays = await p.locator('.day.full').count();
await p.click(`button.day[data-date="${target.date}"]`);
await p.click('[data-s="date"] [data-next]');
out.slots = await p.locator('.slot').count();
out.soldOutSlotDisabled = (await p.locator('.slot.full').count()) ? await p.locator('.slot.full').first().isDisabled() : 'n/a';
await p.click(`.slot[data-event="${target.event}"]`);
await p.click('[data-s="time"] [data-next]');
await p.click('[data-kd="1"]');
out.kidsCapped = await p.isDisabled('[data-kd="1"]'); // 2 adults, 1 kid: kids <= adults only if adults=1; just record
out.party = (await p.textContent('[data-captext]')).trim();
await p.click('[data-s="party"] [data-next]');
await p.click('[data-s="details"] [data-next]');
out.blockedWithoutDetails = await p.isVisible('[data-s="details"]');
await p.fill('#f-name', 'Priya Raman'); await p.fill('#f-phone', '(555) 010-0142'); await p.fill('#f-email', 'priya@example.com');
await p.check('[data-s="details"] input[type=checkbox]');
await p.click('[data-s="details"] [data-next]');
out.review = (await p.textContent('[data-review]')).replace(/\s+/g, ' ').slice(0, 160);
out.total = (await p.textContent('[data-total]')).trim();
await p.click('[data-hold]');
await p.waitForSelector('[data-s="done"]:not([hidden])', { timeout: 30000 });
out.doneTitle = (await p.textContent('[data-done-title]')).trim();
out.urlHasNoPersonalData = !/priya|example\.com|555/i.test(decodeURIComponent(p.url()));
if (mode === 'live') {
  out.queryDropped = !/[?&]done=/.test(p.url());

  // a second visit (reload / shared link) must not show name or email

  await p.goto(out.firstUrl, { waitUntil: 'networkidle' });
  const again = await p.textContent('[data-s="done"]').catch(() => '');
  out.secondVisitNoPII = !!again && !/priya|example\.com/i.test(again) && /confirmed/i.test(again);
}
if (mode === 'live') { await p.goto(base + '/book/', { waitUntil: 'networkidle' }); out.after = await left(); out.seatsTaken = out.before - out.after; }
out.errors = errors; out.posts = posts; out.offHost = [...new Set(offHost)];
await p.screenshot({ path: process.env.SHOT || (process.env.TMPDIR || '/tmp') + '/booking-done.png', fullPage: true });
console.log(JSON.stringify(out, null, 1));
await b.close();
const ok = out.preselected && out.soldOutSlotDisabled !== false && out.blockedWithoutDetails && /Priya/.test(out.doneTitle) && out.urlHasNoPersonalData && !errors.length && !offHost.length
  && (mode === 'live' ? out.seatsTaken === 3 && out.queryDropped && out.secondVisitNoPII : posts.length === 0);
process.exitCode = ok ? 0 : 1;
