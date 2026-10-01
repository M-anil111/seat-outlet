/* =====================================================
    REAL-USER PAGE SPEED (Core Web Vitals)
===================================================== */
// Measures what visitors actually experience, because lab scores (Lighthouse) swing by several points between runs and
// say nothing about real phones and networks. Each metric is pushed to the dataLayer (GA4 via GTM, event "web_vitals")
// and a sample of page views is also sent to ajax/vitals.php, which stores no personal data (see migration 0009).
// Thresholds are the published ones (web.dev/vitals). INP is approximated by the slowest interaction seen.
(function () {
    if (!('PerformanceObserver' in window) || !window.performance) return;

    var SAMPLE_RATE = 0.25;          // share of page views whose measurements are stored; the dataLayer always gets them
    var path = location.pathname.replace(/\/+$/, '') || '/';
    var type = 'static';
    if (path === '/' || path === '/index.php') type = 'home';
    else if (/^\/(tickets|concerts|sports|theater|festival|search|checkout)$/.test(path)) type = path.slice(1);
    else if (path === '/order-confirmation') type = 'confirmation';
    else if (path.indexOf('/artist/') === 0) type = 'artist';
    else if (path.indexOf('/event/') === 0) type = 'event';
    else if (path.indexOf('/city/') === 0) type = 'city';
    else if (path.indexOf('/venue/') === 0) type = 'venue';
    else if (path.indexOf('/state/') === 0 || path.indexOf('/country/') === 0) type = 'location';
    else if (path.indexOf('/category/') === 0) type = 'category';
    else if (path === '/blog' || path.indexOf('/blog/') === 0) type = 'blog';

    var THRESHOLDS = { LCP: [2500, 4000], CLS: [0.1, 0.25], INP: [200, 500], FCP: [1800, 3000], TTFB: [800, 1800] };
    function rate(name, v) { var t = THRESHOLDS[name]; return v <= t[0] ? 'good' : (v <= t[1] ? 'needs-improvement' : 'poor'); }

    var m = {};
    function observe(entryType, cb, opts) {
        try {
            var po = new PerformanceObserver(function (list) { cb(list.getEntries()); });
            po.observe(Object.assign({ type: entryType, buffered: true }, opts || {}));
        } catch (e) { /* this browser does not support the entry type */ }
    }

    var nav = performance.getEntriesByType && performance.getEntriesByType('navigation')[0];
    if (nav && nav.responseStart > 0) m.TTFB = nav.responseStart;

    observe('paint', function (entries) {
        entries.forEach(function (e) { if (e.name === 'first-contentful-paint') m.FCP = e.startTime; });
    });
    observe('largest-contentful-paint', function (entries) {
        if (entries.length) m.LCP = entries[entries.length - 1].startTime;
    });

    var cls = 0, windowValue = 0, windowFirst = 0, windowLast = 0;
    observe('layout-shift', function (entries) {
        entries.forEach(function (e) {
            if (e.hadRecentInput) return;
            if (windowValue && e.startTime - windowLast < 1000 && e.startTime - windowFirst < 5000) {
                windowValue += e.value;
            } else {
                windowValue = e.value; windowFirst = e.startTime;
            }
            windowLast = e.startTime;
            if (windowValue > cls) cls = windowValue;
            m.CLS = cls;
        });
    });

    var slowest = 0;
    observe('event', function (entries) {
        entries.forEach(function (e) { if (e.interactionId && e.duration > slowest) { slowest = e.duration; m.INP = slowest; } });
    }, { durationThreshold: 40 });

    var sent = false;
    function report() {
        if (sent) return;
        var names = Object.keys(m);
        if (!names.length) return;
        sent = true;
        var out = {};
        window.dataLayer = window.dataLayer || [];
        names.forEach(function (name) {
            var v = name === 'CLS' ? Math.round(m[name] * 1000) / 1000 : Math.round(m[name]);
            out[name] = { v: v, r: rate(name, v) };
            window.dataLayer.push({ event: 'web_vitals', metric_name: name, metric_value: v, metric_rating: out[name].r, page_type: type });
        });
        if (Math.random() < SAMPLE_RATE && navigator.sendBeacon) {
            var conn = (navigator.connection && navigator.connection.effectiveType) || '';
            navigator.sendBeacon('/ajax/vitals.php', JSON.stringify({ p: type, d: window.innerWidth < 768 ? 'mobile' : 'desktop', c: conn, m: out }));
        }
    }
    // The page is final once it is hidden or left; that is when LCP and CLS stop changing.
    document.addEventListener('visibilitychange', function () { if (document.visibilityState === 'hidden') report(); });
    window.addEventListener('pagehide', report);
})();
