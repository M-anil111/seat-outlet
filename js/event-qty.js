/* Event page: quantity choices 1, 2, 3, 4, 5 and 6+.
 *
 * The seat-map widget offers Any, 1, 2, 3 and "4+" and has no setting for more. This adds 5 and 6+ next to them, in both the
 * "How many tickets?" sheet and the Filters panel. Choosing one of them applies the widget's own "4 or more" filter, then hides
 * the listings that cannot sell that many (each row says how many tickets it can sell, for example "1-6 Tickets"). Choosing any
 * of the widget's own options clears it. A line above the list says what is being shown, with a way to clear it.
 *
 * The sheet reads 1, 2, 3, 4, 5, 6+ (the widget's own "Any" stays available as "Show all tickets"). "4", "5" and "6+" mean listings
 * that can sell that many or more. A listing that shows "1-8" can sell at most 8 but may not sell every number in between: the
 * exact choices are confirmed in the widget before checkout. Nothing here changes prices or checkout. */
(function () {
  'use strict';
  var root = document.getElementById('tn-maps');
  if (!root) return;

  var EXTRA = [{ key: '5', min: 5, label: '5', aria: '5 tickets or more' }, { key: '6p', min: 6, label: '6+', aria: '6 tickets or more' }];
  var minQty = 0;          // 0 = the widget's own filter only
  var queued = false;

  function maxOf(row) {
    var txt = '';
    var sr = row.querySelector('.sea-sr-only + .sea-sr-only, .venue-ticket-list-section-qty-col .sea-sr-only:nth-of-type(2)');
    var q = row.querySelector('.venue-ticket-list-quantity-js') || row.querySelector('.venue-ticket-list-quantity');
    txt = (q ? q.textContent : '') + ' ' + (sr ? sr.textContent : '');
    var all = row.querySelectorAll('.venue-ticket-list-section-qty-col .sea-sr-only');
    for (var i = 0; i < all.length; i++) { if (/tickets? available/i.test(all[i].textContent)) txt += ' ' + all[i].textContent; }
    var m = txt.match(/(\d+)\s*(?:-|to)\s*(\d+)/i);
    if (m) return parseInt(m[2], 10);
    m = txt.match(/(\d+)\s*tickets?/i);
    return m ? parseInt(m[1], 10) : 0;
  }

  function applyRows() {
    queued = false;
    var rows = root.querySelectorAll('tr.Sea-TicketRow');
    var shown = 0, total = rows.length;
    for (var i = 0; i < rows.length; i++) {
      var hide = minQty > 0 && maxOf(rows[i]) < minQty;
      if (hide) { if (rows[i].getAttribute('data-so-qty-hidden') !== '1') { rows[i].setAttribute('data-so-qty-hidden', '1'); rows[i].style.display = 'none'; } }
      else if (rows[i].getAttribute('data-so-qty-hidden') === '1') { rows[i].removeAttribute('data-so-qty-hidden'); rows[i].style.display = ''; }
      if (!hide) shown++;
    }
    note(shown, total);
  }

  function note(shown, total) {
    var el = document.getElementById('so-qty-note');
    if (minQty === 0) { if (el) el.remove(); return; }
    var list = document.getElementById('list-ctn') || root.querySelector('#bannersFiltersDiv');
    if (!list) return;
    if (!el) {
      el = document.createElement('div');
      el.id = 'so-qty-note';
      el.className = 'so-qty-note';
      el.setAttribute('role', 'status');
      var host = document.getElementById('bannersFiltersDiv');
      (host || list).insertAdjacentElement(host ? 'afterend' : 'afterbegin', el);
    }
    var what = minQty + ' or more tickets';
    el.innerHTML = '';
    var span = document.createElement('span');
    span.textContent = (shown === 0 ? 'No listings sell ' : 'Showing listings that sell ') + what + '.';
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.textContent = 'Clear';
    btn.addEventListener('click', clear);
    el.appendChild(span); el.appendChild(btn);
  }

  function clear() {
    var any = root.querySelector('.filters-qty-filter input[value="any"]');
    minQty = 0;
    syncActive();
    if (any) any.click(); else applyRows();
    applyRows();
  }

  function syncActive() {
    root.querySelectorAll('.filters-qty-filter').forEach(function (box) {
      box.querySelectorAll('.so-qty-extra').forEach(function (b) {
        var on = minQty > 0 && Number(b.getAttribute('data-min')) === minQty;
        b.classList.toggle('sea-active', on); b.classList.toggle('active', on);
        b.setAttribute('aria-pressed', on ? 'true' : 'false');
      });
    });
  }

  function enhance(box) {
    if (box.getAttribute('data-so-qty') === '1') return;
    var four = box.querySelector('input[value="4+"]');
    if (!four) return;
    box.setAttribute('data-so-qty', '1');
    // The widget's "4+" label reads "4": it already means four or more, and 5, 6 and 6+ follow it.
    var fourLabel = four.closest('label');
    if (fourLabel) {
      for (var n = fourLabel.firstChild; n; n = n.nextSibling) { if (n.nodeType === 3 && /4\s*\+/.test(n.nodeValue)) n.nodeValue = n.nodeValue.replace('4+', '4'); }
    }
    var tpl = fourLabel;
    EXTRA.forEach(function (x) {
      var b = document.createElement('button');
      b.type = 'button';
      b.className = (tpl ? tpl.className.replace(/\bsea-active\b|\bactive\b/g, '') : 'sea-btn') + ' so-qty-extra';
      b.setAttribute('data-min', String(x.min));
      b.setAttribute('aria-label', x.aria);
      b.setAttribute('aria-pressed', 'false');
      b.textContent = x.label;
      b.addEventListener('click', function (e) {
        e.preventDefault(); e.stopPropagation();
        minQty = x.min;
        syncActive();
        // Apply the widget's own "4 or more" (this also closes the sheet), then trim the list.
        if (four) four.click();
        minQty = x.min;       // the widget's own handler runs first and our own state is set again afterwards
        syncActive();
        schedule();
      });
      box.appendChild(b);
    });
    // Any of the widget's own choices clears ours.
    box.addEventListener('click', function (e) {
      if (e.target.closest && e.target.closest('.so-qty-extra')) return;
      if (e.target.closest && e.target.closest('label')) { minQty = 0; syncActive(); schedule(); }
    }, true);
  }

  function scan() {
    root.querySelectorAll('.filters-qty-filter').forEach(enhance);
    var skip = document.getElementById('sea-quantity-modal-skip');
    if (skip && skip.textContent.trim() === 'Skip') skip.textContent = 'Show all tickets';
    if (minQty > 0) syncActive();
  }

  function schedule() {
    if (queued) return;
    queued = true;
    (window.requestAnimationFrame || setTimeout)(function () { scan(); applyRows(); });
  }

  new MutationObserver(function () { schedule(); }).observe(root, { childList: true, subtree: true });
  schedule();
})();
