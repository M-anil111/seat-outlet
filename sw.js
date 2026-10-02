/* Seat Outlet service worker: offline fallback only.
 *
 * It does nothing while the visitor is online. When a page navigation fails because there is no connection, it shows
 * /offline.html instead of the browser's dinosaur. It deliberately caches no tickets, prices, pages, scripts or styles, so it
 * can never show stale prices or an old version of the site. */
var CACHE = 'so-offline-v1';
var OFFLINE = '/offline.html';

self.addEventListener('install', function (e) {
  e.waitUntil(caches.open(CACHE).then(function (c) { return c.add(new Request(OFFLINE, { cache: 'reload' })); }).then(function () { return self.skipWaiting(); }));
});

self.addEventListener('activate', function (e) {
  e.waitUntil(caches.keys().then(function (keys) {
    return Promise.all(keys.filter(function (k) { return k !== CACHE; }).map(function (k) { return caches.delete(k); }));
  }).then(function () { return self.clients.claim(); }));
});

self.addEventListener('fetch', function (e) {
  var r = e.request;
  if (r.mode !== 'navigate') return;   // only page loads; everything else goes straight to the network
  e.respondWith(fetch(r).catch(function () { return caches.match(OFFLINE); }));
});
