// CI: the "Today / Tomorrow / This weekend" pill on listing rows (js/events-listing.js, whenLabel) agrees with the server's
// "This weekend" chip (listingDateRange('weekend') in functions.php: Friday to Sunday, from today when today is Fri, Sat or Sun).
// Runs the function on its own for every weekday and the 10 days after it. Run: node tools/test-date-pill.mjs
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';

const src = readFileSync(fileURLToPath(new URL('../js/events-listing.js', import.meta.url)), 'utf8');
const m = src.match(/\/\* when-label:start[\s\S]*?\*\/([\s\S]*?)\/\* when-label:end \*\//);
if (!m) { console.log('FAIL whenLabel block not found in js/events-listing.js'); process.exit(1); }
const whenLabel = new Function(m[1] + '\nreturn whenLabel;')();

// The server's rule, written out on its own: Fri/Sat/Sun start today, other days the coming Friday; the window ends on that Sunday.
function serverWeekend(now) {
  const dow = now.getDay();
  const from = new Date(now); if (!(dow === 5 || dow === 6 || dow === 0)) from.setDate(now.getDate() + (5 - dow));
  const to = new Date(from); to.setDate(from.getDate() + ((7 - from.getDay()) % 7));
  return [from, to];
}

let fail = 0;
const check = (name, got, want) => { if (got !== want) { console.log(`FAIL ${name}: got ${JSON.stringify(got)}, wanted ${JSON.stringify(want)}`); fail++; } };
const names = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
// 16 to 22 Nov 2026 is Monday to Sunday; also run across the March DST change (8 Mar 2026 in the US).
for (const base of [new Date(2026, 10, 16), new Date(2026, 2, 2)]) {
  for (let d = 0; d < 7; d++) {
    const now = new Date(base); now.setDate(base.getDate() + d);
    const [from, to] = serverWeekend(now);
    for (let k = 0; k <= 10; k++) {
      const dt = new Date(now); dt.setDate(now.getDate() + k);
      const want = k === 0 ? 'Today' : k === 1 ? 'Tomorrow' : (dt >= from && dt <= to ? 'This weekend' : '');
      check(`${names[now.getDay()]} ${now.toDateString()} +${k}d`, whenLabel(dt, now), want);
    }
  }
}
// The cases that were wrong before, by name.
const sun = new Date(2026, 10, 22), sat = new Date(2026, 10, 28);
check('Sunday: next Saturday is not "this weekend"', whenLabel(sat, sun), '');
const mon = new Date(2026, 10, 16), fri = new Date(2026, 10, 20);
check('Monday: Friday is "this weekend" (the chip lists it)', whenLabel(fri, mon), 'This weekend');
check('Monday: Sunday is "this weekend"', whenLabel(new Date(2026, 10, 22), mon), 'This weekend');
check('Monday: the Saturday after is not', whenLabel(new Date(2026, 10, 28), mon), '');

if (fail) { console.log(`${fail} failure(s)`); process.exit(1); }
console.log('ok: listing date pill matches the server weekend window');
