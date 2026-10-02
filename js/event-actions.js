/* Event page: remember the event for "Pick up where you left off", add it to a calendar, share it.
   Everything stays in the visitor's browser; the facts come from the JSON block event.php prints. */
(function () {
  'use strict';
  var node = document.getElementById('so-event-data');
  if (!node) return;
  var ev;
  try { ev = JSON.parse(node.textContent); } catch (e) { return; }
  if (!ev || !ev.id) return;

  function track(name, extra) {
    var o = { event: name, event_id: String(ev.id) };
    for (var k in extra) o[k] = extra[k];
    (window.dataLayer = window.dataLayer || []).push(o);
  }
  function say(msg) {
    var s = document.getElementById('so-action-status');
    if (s) { s.textContent = ''; setTimeout(function () { s.textContent = msg; }, 30); }
  }
  function flash(btn, text) {
    var span = btn.querySelector('span');
    if (!span) return;
    var old = btn.getAttribute('data-label') || span.textContent;
    btn.setAttribute('data-label', old);
    span.textContent = text;
    setTimeout(function () { span.textContent = old; }, 2200);
  }

  // Remember this event on this device (home page "Pick up where you left off").
  if (window.soLocal && window.soLocal.addEvent) window.soLocal.addEvent(ev);

  /* ---- Calendar file (RFC 5545) ---- */
  function esc(t) { return String(t || '').replace(/\\/g, '\\\\').replace(/\r?\n/g, '\\n').replace(/([,;])/g, '\\$1'); }
  function fold(line) {
    var out = [], enc = new TextEncoder(), cur = '', bytes = 0;
    Array.from(line).forEach(function (ch) {
      var n = enc.encode(ch).length;
      if (bytes + n > 74) { out.push(cur); cur = ' '; bytes = 1; }
      cur += ch; bytes += n;
    });
    out.push(cur);
    return out.join('\r\n');
  }
  function utc(d) { return d.toISOString().replace(/[-:]/g, '').replace(/\.\d{3}/, ''); }
  function buildIcs() {
    var lines = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Seat Outlet//Event//EN', 'CALSCALE:GREGORIAN', 'METHOD:PUBLISH', 'BEGIN:VEVENT',
      'UID:event-' + ev.id + '@seatoutlet.com', 'DTSTAMP:' + utc(new Date())];
    if (!ev.allDay && ev.start && !isNaN(Date.parse(ev.start))) {
      lines.push('DTSTART:' + utc(new Date(ev.start)));
    } else if (/^\d{4}-\d{2}-\d{2}$/.test(ev.date || '')) {
      var d = ev.date.replace(/-/g, '');
      var next = new Date(ev.date + 'T00:00:00Z'); next.setUTCDate(next.getUTCDate() + 1);
      lines.push('DTSTART;VALUE=DATE:' + d, 'DTEND;VALUE=DATE:' + next.toISOString().slice(0, 10).replace(/-/g, ''));
    } else {
      return '';
    }
    lines.push('SUMMARY:' + esc(ev.name));
    var where = [ev.venue, ev.city].filter(Boolean).join(', ');
    if (where) lines.push('LOCATION:' + esc(where));
    lines.push('DESCRIPTION:' + esc('Tickets: ' + ev.url + (ev.allDay ? '\nStart time to be announced.' : '')));
    lines.push('URL:' + ev.url, 'END:VEVENT', 'END:VCALENDAR');
    return lines.map(fold).join('\r\n') + '\r\n';
  }
  function downloadIcs(btn) {
    var ics = buildIcs();
    if (!ics) { say('Calendar file is not available for this event.'); return; }
    var blob = new Blob([ics], { type: 'text/calendar;charset=utf-8' });
    var a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = (ev.slug || 'event') + '.ics';
    document.body.appendChild(a); a.click(); a.remove();
    setTimeout(function () { URL.revokeObjectURL(a.href); }, 4000);
    track('add_to_calendar', { method: 'ics' });
    say('Calendar file downloaded.');
  }

  /* ---- "Add to calendar" menu: Google, Outlook, Microsoft 365, Yahoo, or a file for Apple and everything else ---- */
  (function () {
    var wrap = document.querySelector('[data-so-cal]');
    if (!wrap) return;
    var toggle = wrap.querySelector('[data-so-cal-toggle]');
    var menu = wrap.querySelector('.so-cal__menu');
    var title = ev.name || 'Event';
    var where = [ev.venue, ev.city].filter(Boolean).join(', ');
    var notes = 'Tickets: ' + ev.url;
    var timed = !ev.allDay && ev.start && !isNaN(Date.parse(ev.start));
    var startD = timed ? new Date(ev.start) : null;
    var endD = timed ? new Date(startD.getTime() + 3 * 3600 * 1000) : null;   // length is not published: 3 hours
    var day = /^\d{4}-\d{2}-\d{2}$/.test(ev.date || '') ? ev.date : '';
    function compact(d) { return d.toISOString().replace(/[-:]/g, '').replace(/\.\d{3}/, ''); }
    function nextDay(s) { var d = new Date(s + 'T00:00:00Z'); d.setUTCDate(d.getUTCDate() + 1); return d.toISOString().slice(0, 10); }
    function q(o) { return Object.keys(o).map(function (k) { return k + '=' + encodeURIComponent(o[k]); }).join('&'); }
    function link(kind) {
      if (!timed && !day) return '';
      if (kind === 'google') {
        return 'https://calendar.google.com/calendar/render?' + q({ action: 'TEMPLATE', text: title, details: notes, location: where,
          dates: timed ? compact(startD) + '/' + compact(endD) : day.replace(/-/g, '') + '/' + nextDay(day).replace(/-/g, '') });
      }
      if (kind === 'outlook' || kind === 'office') {
        var base = kind === 'outlook' ? 'https://outlook.live.com' : 'https://outlook.office.com';
        var o = { path: '/calendar/action/compose', rru: 'addevent', subject: title, body: notes, location: where };
        if (timed) { o.startdt = startD.toISOString(); o.enddt = endD.toISOString(); }
        else { o.startdt = day; o.enddt = nextDay(day); o.allday = 'true'; }
        return base + '/calendar/0/deeplink/compose?' + q(o);
      }
      if (kind === 'yahoo') {
        return 'https://calendar.yahoo.com/?' + q({ v: '60', title: title, desc: notes, in_loc: where,
          st: timed ? compact(startD) : day.replace(/-/g, ''), dur: timed ? '0300' : 'allday' });
      }
      return '';
    }
    ['google', 'outlook', 'office', 'yahoo'].forEach(function (k) {
      var a = menu.querySelector('[data-cal="' + k + '"]');
      var href = link(k);
      if (a) { if (href) a.setAttribute('href', href); else a.hidden = true; }
    });
    function close() { menu.hidden = true; toggle.setAttribute('aria-expanded', 'false'); }
    toggle.addEventListener('click', function (e) {
      e.stopPropagation();
      var open = menu.hidden;
      menu.hidden = !open;
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    document.addEventListener('click', function (e) { if (!wrap.contains(e.target)) close(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
    menu.addEventListener('click', function (e) {
      var item = e.target.closest('[data-cal]');
      if (!item) return;
      var kind = item.getAttribute('data-cal');
      if (kind === 'ics') downloadIcs(); else track('add_to_calendar', { method: kind });
      close();
    });
  })();

  /* ---- Share ---- */
  var shareBtn = document.querySelector('[data-so-share]');
  if (shareBtn) shareBtn.addEventListener('click', function () {
    var text = ev.name + (ev.city ? ' in ' + ev.city : '') + ' tickets';
    if (navigator.share) {
      navigator.share({ title: ev.name, text: text, url: ev.url }).then(function () { track('share', { method: 'web_share' }); }).catch(function () { /* cancelled */ });
      return;
    }
    function done() { track('share', { method: 'copy_link' }); flash(shareBtn, 'Link copied'); say('Link copied.'); }
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(ev.url).then(done, function () { window.prompt('Copy this link', ev.url); });
    } else {
      window.prompt('Copy this link', ev.url);
    }
  });
})();
