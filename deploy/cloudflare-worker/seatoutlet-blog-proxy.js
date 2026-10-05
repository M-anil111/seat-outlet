// Cloudflare Worker "seatoutlet-blog-proxy": sits in front of https://seatoutlet.com and forwards to https://beta.seatoutlet.com.
//
// What changed from the version that was deployed on 4 Oct 2026 (written with ChatGPT Codex):
//   - It no longer builds any sitemap. It used to answer /sitemap with a hand-written HTML page (a fixed list of pages and the 7 blog
//     posts), /sitemaps/sitemap.xml with a fixed list of 17 child files (so new files such as holiday-events-1.xml were never listed
//     and files that do not exist were), and /sitemaps/sitemap.xsl with its own stylesheet. All of these now come from the site
//     (sitemap.php, the files in /sitemaps, sitemap.xsl), which read the catalog, the database and the page files, so they stay current.
//   - Retired sitemap addresses 301 to the current ones instead of answering 410.
//   - /sitemap.xsl is sent as text/xsl so browsers apply it.
//   - robots.txt keeps its own answer here (the site's robots.php on the beta host says "Disallow: /"), now with the site's Disallow list.
// Everything else is as deployed. Source of truth for the Worker: this file. Deploy with `npx wrangler deploy` or paste into the
// dashboard (Workers, seatoutlet-blog-proxy, Edit code). docs/production-cutover.md retires the Worker at cutover.

var BETA_ORIGIN = "https://beta.seatoutlet.com";
var DEFAULT_LOCATION = {
  city: "Austin",
  state: "TX",
  lat: "30.29710",
  lng: "-97.81810"
};
var LIVE_REPO_COMMIT = "53ae0576fa93139deee8385fbc49b50b69d6fdf8";
var REPO_JS_ASSETS = new Set([
  "/js/main.min.js",
  "/js/home.min.js"
]);
var CANONICAL_ORIGIN = "https://seatoutlet.com";
// The same list robots.php prints (SO_ROBOTS_DISALLOW in inc/sitemap-build.php).
var ROBOTS_DISALLOW = ["/admin/", "/ajax/", "/cache/", "/vendor/", "/db/", "/tools/", "/cron/", "/deploy/", "/docs/", "/inc/", "/search", "/checkout", "/newsletter", "/unsubscribe", "/thank-you", "/order-confirmation"];
// Old sitemap addresses and where they go now.
var SITEMAP_REDIRECTS = {
  "/sitemap.xml": "/sitemaps/sitemap.xml",
  "/sitemaps/sitemap-index.xml": "/sitemaps/sitemap.xml",
  "/sitemaps/sitemap.php": "/sitemaps/sitemap.xml",
  "/sitemap.php": "/sitemap",
  "/sitemap-page": "/sitemap"
};
var BLOG_POSTS = {
  "/blog/how-to-avoid-ticket-scams": {
    category: "Ticket Safety",
    title: "Ticket Scams: 15 Warning Signs and How to Buy Tickets Safely",
    description: "Learn how ticket scams work, 15 warning signs, a 12-point pre-purchase checklist, safe vs risky payment methods, what the BOTS Act and FTC fee rule cover, and what to do if you were scammed.",
    image: "/images/event-ticket-buying.webp",
    alt: "Concert tickets on a laptop and a phone, with a green check mark on the phone ticket"
  },
  "/blog/upcoming-concert-tours": {
    category: "Concerts & Tours",
    title: "Upcoming Concert Tours: 12 Announced Tours and How to Get Tickets",
    description: "12 verified upcoming concert tours for late 2026 and 2027 in North America, with dates as published, plus how announcements, presales and on-sales work and how to compare resale seats. As of 2 October 2026.",
    image: "/images/stage.webp",
    alt: "Musicians performing on a stage lit by beams of light, with the crowd in the foreground"
  },
  "/blog/taylor-swift-tour-dates": {
    category: "Concerts & Tours",
    title: "Taylor Swift Tour Dates: Latest Updates and How to Get Tickets in 2026",
    description: "As of 2 October 2026 no Taylor Swift tour has been announced. Here is what is verified, how to prepare, how resale works and how to avoid fake tour-date posts.",
    image: "/images/indie-rock-night.webp",
    alt: "Packed crowd at an indoor concert under red and blue stage lights"
  },
  "/blog/kids-events-in-austin": {
    category: "City Guides",
    title: "Kids Events in Austin: 12 Family Ideas, Shows and Tickets",
    description: "A practical guide to kids events in Austin: family shows, sports, holiday lights and attractions, with age tips and venue rules on lap seats, bags and strollers. Verified against official sites as of 2 October 2026.",
    image: "/images/austin.webp",
    alt: "Large crowd at an outdoor festival stage with a city skyline behind it"
  },
  "/blog/best-concerts-in-nyc": {
    category: "City Guides",
    title: "Best Concerts in NYC: Venues, Shows and Smart Ticket Tips",
    description: "A practical guide to the best concerts in NYC by experience: arenas, historic theaters, indie clubs, jazz halls, outdoor summer shows and New Jersey stadiums, with a venue table, seat advice, transit and ticket tips.",
    image: "/images/venue.webp",
    alt: "Empty arena with rows of seats facing a lit stage"
  },
  "/blog/things-to-do-in-nyc-in-december": {
    category: "City Guides",
    title: "Things to Do in NYC in December: Top Ticketed Events for 2026",
    description: "A guide to ticketed and free things to do in NYC in December 2026: Radio City, The Nutcracker, Jingle Ball, sports, the Rockefeller tree and New Year's Eve, with dates as published by organizers as of 2 October 2026.",
    image: "/images/loews-theatre.webp",
    alt: "Front of a historic theater with a clock tower and a lit marquee"
  },
  "/blog/best-concert-venues-in-the-us": {
    category: "Venues",
    title: "Best Concert Venues in the US: 20 Iconic Places to See Live Music",
    description: "An editorial guide to the best concert venues in the US: 20 amphitheaters, historic theaters, arenas, a stadium and clubs, with capacities, history, seating tips and trip planning.",
    image: "/images/festival-1.webp",
    alt: "Crowd at a large arena concert with stage lights and rigging overhead"
  }
};
var index_default = {
  async fetch(request) {
    const url = new URL(request.url);
    if (url.pathname === "/robots.txt") {
      return robotsResponse(url);
    }
    if (SITEMAP_REDIRECTS[url.pathname]) {
      return Response.redirect(CANONICAL_ORIGIN + SITEMAP_REDIRECTS[url.pathname], 301);
    }
    if (REPO_JS_ASSETS.has(url.pathname)) {
      return repoAssetResponse(url.pathname);
    }
    const upstreamUrl = new URL(`${url.pathname}${url.search}`, BETA_ORIGIN);
    const upstreamHeaders = new Headers(request.headers);
    upstreamHeaders.set("Accept-Encoding", "identity");
    upstreamHeaders.set("Host", "beta.seatoutlet.com");
    upstreamHeaders.set("X-Forwarded-Host", url.hostname);
    applyVisitorHeaders(upstreamHeaders, request);
    const upstreamRequest = new Request(upstreamUrl, {
      body: request.body,
      headers: upstreamHeaders,
      method: request.method,
      redirect: "manual"
    });
    const response = await fetch(upstreamRequest, { redirect: "manual" });
    if (url.pathname === "/ajax/get_ip_details.php") {
      return locationResponse(response);
    }
    if (response.status >= 400 && BLOG_POSTS[url.pathname]) {
      return renderPostFallback(BLOG_POSTS[url.pathname], url);
    }
    // The browser stylesheet for the XML sitemaps must be sent as text/xsl or browsers ignore it (search engines never read it).
    if (url.pathname === "/sitemap.xsl" && response.ok) {
      const headers = new Headers(response.headers);
      headers.set("content-type", "text/xsl; charset=UTF-8");
      headers.set("cache-control", "public, max-age=300");
      headers.delete("content-length");
      headers.delete("content-encoding");
      return new Response(response.body, { status: 200, headers });
    }
    return rewriteResponse(response, url);
  }
};
async function repoAssetResponse(pathname) {
  const assetUrl = `https://raw.githubusercontent.com/M-anil111/seat-outlet/${LIVE_REPO_COMMIT}${pathname}`;
  const response = await fetch(assetUrl, {
    headers: {
      "Accept": "text/plain, application/javascript"
    },
    cf: {
      cacheTtl: 300,
      cacheEverything: true
    }
  });
  const headers = new Headers(response.headers);
  headers.set("content-type", "application/javascript; charset=UTF-8");
  headers.set("cache-control", "public, max-age=300");
  headers.delete("content-security-policy");
  headers.delete("content-length");
  headers.delete("content-encoding");
  return new Response(response.body, {
    status: response.status,
    statusText: response.statusText,
    headers
  });
}
function applyVisitorHeaders(headers, request) {
  const cfIp = request.headers.get("CF-Connecting-IP");
  const forwardedFor = request.headers.get("X-Forwarded-For");
  const clientIp = cfIp || (forwardedFor ? forwardedFor.split(",")[0].trim() : "");
  const cf = request.cf || {};
  if (clientIp) {
    headers.set("X-Forwarded-For", clientIp);
    headers.set("X-Real-IP", clientIp);
    headers.set("True-Client-IP", clientIp);
  }
  setHeaderFromRequestOrCf(headers, request, "CF-IPCity", cf.city);
  setHeaderFromRequestOrCf(headers, request, "CF-IPLatitude", cf.latitude);
  setHeaderFromRequestOrCf(headers, request, "CF-IPLongitude", cf.longitude);
  setHeaderFromRequestOrCf(headers, request, "CF-Region", cf.region);
  setHeaderFromRequestOrCf(headers, request, "CF-Region-Code", cf.regionCode);
  setHeaderFromRequestOrCf(headers, request, "CF-IPCountry", cf.country);
}
function setHeaderFromRequestOrCf(headers, request, name, cfValue) {
  const value = request.headers.get(name) || cfValue;
  if (value) {
    headers.set(name, value);
  }
}
async function locationResponse(response) {
  const data = await readLocationJson(response);
  const location = hasLocation(data) ? data : DEFAULT_LOCATION;
  const headers = new Headers(response.headers);
  headers.set("content-type", "application/json; charset=UTF-8");
  headers.set("cache-control", "private, max-age=300");
  headers.delete("content-length");
  headers.delete("content-encoding");
  return new Response(JSON.stringify(location), {
    status: 200,
    headers
  });
}
async function readLocationJson(response) {
  if (!response || !response.ok) {
    return null;
  }
  try {
    return await response.json();
  } catch (error) {
    return null;
  }
}
function hasLocation(data) {
  return data && data.city && data.state && data.lat && data.lng;
}
async function rewriteResponse(response, requestUrl) {
  const headers = new Headers(response.headers);
  rewriteLocationHeader(headers, requestUrl);
  const contentType = headers.get("content-type") || "";
  if (!isTextResponse(contentType)) {
    return new Response(response.body, {
      status: response.status,
      statusText: response.statusText,
      headers
    });
  }
  let body = await response.text();
  const liveOrigin = `${requestUrl.protocol}//${requestUrl.hostname}`;
  body = body.replaceAll(BETA_ORIGIN, liveOrigin).replaceAll("https:\\/\\/beta.seatoutlet.com", liveOrigin.replaceAll("/", "\\/")).replace(/<meta\s+name=["']robots["']\s+content=["']noindex,\s*nofollow["']\s*>\s*/gi, "").replace(/<meta\s+name=["']robots["']\s+content=["']nofollow,\s*noindex["']\s*>\s*/gi, "");
  if (requestUrl.pathname === "/" || requestUrl.pathname === "") {
    body = body.replace("</body>", `${homepageBootstrapScript()}</body>`);
  }
  headers.delete("content-length");
  headers.delete("content-encoding");
  return new Response(body, {
    status: response.status,
    statusText: response.statusText,
    headers
  });
}
function homepageBootstrapScript() {
  return `<script data-cfasync="false">
(function(){
  var style = document.createElement('style');
  style.textContent = 'html,body{max-width:100%;overflow-x:hidden}.category-scroll-wrapper{max-width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch}.so-popweek__wrap,.top-picks,.tab-content,.tab-pane,.so-trust__head{max-width:100%;box-sizing:border-box}';
  document.head.appendChild(style);
  function ready(fn){
    if (document.body) { setTimeout(fn, 0); return; }
    document.addEventListener('DOMContentLoaded', fn, { once: true });
    setTimeout(function(){ if (document.body) fn(); }, 1000);
  }
  function esc(v){ return String(v == null ? '' : v).replace(/[&<>"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
  function validLoc(loc){ return loc && loc.city && loc.state && loc.lat && loc.lng; }
  function locLabel(loc){ return validLoc(loc) ? loc.city + ', ' + loc.state : ''; }
  function setCookie(name, value){
    document.cookie = name + '=' + encodeURIComponent(value) + ';path=/;max-age=2592000;SameSite=Lax;Secure';
  }
  function applyLoc(loc){
    if (!validLoc(loc)) return '';
    var label = locLabel(loc);
    setCookie('so_lat', loc.lat);
    setCookie('so_lng', loc.lng);
    setCookie('so_label', label);
    if (typeof window.soSetLocText === 'function') {
      window.soSetLocText(label);
    } else {
      var target = document.getElementById('locationSelectorText');
      if (target) target.innerHTML = 'Near ' + esc(label) + ' <i class="fa fa-caret-down"></i>';
    }
    var headerInput = document.getElementById('locationInputHeader');
    if (headerInput) headerInput.value = label;
    var cityInput = document.getElementById('cityLocationInput');
    if (cityInput) cityInput.value = label;
    return label;
  }
  function getIpLoc(){
    return fetch('/ajax/get_ip_details.php', { cache: 'no-store' })
      .then(function(r){ return r.ok ? r.json() : null; })
      .then(function(loc){ return validLoc(loc) ? loc : null; })
      .catch(function(){ return null; });
  }
  function fallbackCard(ev){
    var slug = String(ev.name || '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '') + '-' + esc(ev.id || '');
    return '<article class="so-evc"><div class="so-evc__top"><span class="so-evc__badge">Event</span></div><h3 class="so-evc__name"><a class="so-evc__link" href="/event/' + slug + '">' + esc(ev.name) + '</a></h3><ul class="so-evc__meta"><li><span>' + esc(ev.date || ev.iso || '') + '</span></li>' + (ev.venue ? '<li><span>' + esc(ev.venue) + (ev.loc ? '<small>' + esc(ev.loc) + '</small>' : '') + '</span></li>' : '') + '</ul><div class="so-evc__foot">' + (ev.price ? '<span class="so-evc__price">From <strong>' + esc(ev.price) + '</strong></span>' : '<span class="so-evc__price so-evc__price--none">View tickets</span>') + '</div></article>';
  }
  function card(ev, opts){ return typeof window.soEvCard === 'function' ? window.soEvCard(ev, opts || {}) : fallbackCard(ev); }
  function renderHero(loc){
    var pane = document.querySelector('.tab-pane.show.active') || document.getElementById('concerts');
    var track = pane && pane.querySelector('.custom-slider');
    if (!track || track.querySelector('.so-evc:not(.so-evc--skeleton)')) return;
    var tab = pane.id || 'concerts';
    var localized = validLoc(loc);
    var url = '/ajax/get-location-category-events.php?tab=' + encodeURIComponent(tab) +
      '&type=' + encodeURIComponent(localized ? 'll' : '') +
      '&loc1=' + encodeURIComponent(localized ? loc.lat : '') +
      '&loc2=' + encodeURIComponent(localized ? loc.lng : '');
    fetch(url)
      .then(function(r){ return r.json(); })
      .then(function(events){
        if (!events || !events.length || track.querySelector('.so-evc:not(.so-evc--skeleton)')) return;
        track.classList.remove('skeleton-loading');
        track.innerHTML = events.map(function(ev){ return '<div class="so-evc-slide">' + card(ev) + '</div>'; }).join('');
        if (window.jQuery && jQuery.fn && jQuery.fn.slick) { var s = jQuery(track); if (!s.hasClass('slick-initialized')) s.slick({slidesToShow:4,slidesToScroll:1,infinite:false,arrows:true,dots:false,responsive:[{breakpoint:992,settings:{slidesToShow:3}},{breakpoint:768,settings:{slidesToShow:2}},{breakpoint:576,settings:{slidesToShow:1.5}}]}); }
      }).catch(function(){});
  }
  function feedUrl(kind, loc){
    var localized = validLoc(loc);
    if (kind === 'weekend' && !localized) return '';
    var params = new URLSearchParams();
    if (kind === 'weekend') {
      params.set('kind', 'near');
      params.set('when', 'weekend');
      params.set('cat', 'all');
    } else {
      params.set('kind', kind);
    }
    if (localized) {
      params.set('lat', loc.lat);
      params.set('lng', loc.lng);
    }
    return '/ajax/get-home-feed.php?' + params.toString();
  }
  function retitleFeed(box, kind, label){
    if (!label || kind === 'popweekend') return;
    var title = box.querySelector('h2, h3, .so-feed-title, [data-so-feed-title]');
    if (!title) return;
    if (kind === 'weekend') title.textContent = 'This weekend near ' + label;
    if (kind === 'lastminute') title.textContent = 'Last-minute tickets near ' + label;
    if (kind === 'trending') title.textContent = 'Trending events near ' + label;
  }
  function renderFeeds(loc){
    var label = locLabel(loc);
    document.querySelectorAll('[data-so-feed]').forEach(function(box){
      var kind = box.getAttribute('data-so-feed');
      var track = box.querySelector('[data-so-feed-track]');
      if (!track || track.querySelector('.so-evc:not(.so-evc--skeleton)')) return;
      var url = feedUrl(kind, loc);
      if (!url) return;
      retitleFeed(box, kind, label);
      fetch(url)
        .then(function(r){ return r.json(); })
        .then(function(data){
          var events = data && data.events || [];
          if (!events.length || track.querySelector('.so-evc:not(.so-evc--skeleton)')) return;
          box.hidden = false;
          track.innerHTML = events.map(function(ev){ return card(ev, { tint: kind === 'popweekend' }); }).join('');
          track.scrollLeft = 0;
        }).catch(function(){});
    });
  }
  ready(function(){
    setTimeout(function(){
      getIpLoc().then(function(loc){
        if (loc) applyLoc(loc);
        if (!document.querySelector('.custom-slider .so-evc:not(.so-evc--skeleton)')) renderHero(loc);
        if (!document.querySelector('[data-so-feed-track] .so-evc:not(.so-evc--skeleton)')) renderFeeds(loc);
      });
    }, 1500);
  });
})();
<\/script>`;
}
function isTextResponse(contentType) {
  return contentType.includes("text/") || contentType.includes("application/json") || contentType.includes("application/ld+json") || contentType.includes("application/javascript") || contentType.includes("application/xml") || contentType.includes("image/svg+xml");
}
function rewriteLocationHeader(headers, requestUrl) {
  const location = headers.get("location");
  if (!location) {
    return;
  }
  const liveOrigin = `${requestUrl.protocol}//${requestUrl.hostname}`;
  headers.set("location", location.replace(BETA_ORIGIN, liveOrigin));
}
function robotsResponse(requestUrl) {
  return new Response(`User-agent: *
${ROBOTS_DISALLOW.map((path) => `Disallow: ${path}`).join("\n")}

Sitemap: ${CANONICAL_ORIGIN}/sitemaps/sitemap.xml
`, {
    headers: {
      "cache-control": "public, max-age=300",
      "content-type": "text/plain; charset=UTF-8"
    }
  });
}
function renderPostFallback(post, requestUrl) {
  const origin = `${requestUrl.protocol}//${requestUrl.hostname}`;
  const canonical = `${origin}${requestUrl.pathname}`;
  const body = `<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>${escapeHtml(post.title)} - Seat Outlet</title>
  <meta name="description" content="${escapeHtml(post.description)}">
  <link rel="canonical" href="${canonical}">
  <meta property="og:title" content="${escapeHtml(post.title)}">
  <meta property="og:description" content="${escapeHtml(post.description)}">
  <meta property="og:url" content="${canonical}">
  <meta property="og:type" content="article">
  <meta property="og:image" content="${origin}${post.image}">
  <link rel="stylesheet" href="${origin}/css/bootstrap.min.css">
  <link rel="stylesheet" href="${origin}/css/style.min.css">
  <style>
    body { background: #f6f8fb; color: #172033; font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
    .so-article { max-width: 920px; margin: 0 auto; padding: 40px 20px 72px; }
    .so-article__crumb { display: inline-block; margin-bottom: 18px; color: #2556e0; font-weight: 700; text-decoration: none; }
    .so-article__category { color: #2556e0; font-weight: 800; text-transform: uppercase; font-size: 13px; letter-spacing: .08em; }
    .so-article h1 { margin: 10px 0 18px; font-size: clamp(34px, 6vw, 58px); line-height: 1.02; letter-spacing: 0; }
    .so-article__dek { font-size: 20px; line-height: 1.6; color: #46556f; }
    .so-article__hero { width: 100%; margin: 28px 0; border-radius: 8px; display: block; }
    .so-article__body { background: white; border: 1px solid #dfe5ef; border-radius: 8px; padding: 28px; font-size: 18px; line-height: 1.75; }
    .so-article__body h2 { margin-top: 0; font-size: 26px; }
    .so-article__cta { display: inline-block; margin-top: 18px; padding: 12px 18px; border-radius: 6px; background: #2556e0; color: white; font-weight: 800; text-decoration: none; }
  </style>
</head>
<body>
  <main class="so-article">
    <a class="so-article__crumb" href="/blog">Seat Outlet Blog</a>
    <div class="so-article__category">${escapeHtml(post.category)}</div>
    <h1>${escapeHtml(post.title)}</h1>
    <p class="so-article__dek">${escapeHtml(post.description)}</p>
    <img class="so-article__hero" src="${post.image}" alt="${escapeHtml(post.alt)}" width="1200" height="630">
    <article class="so-article__body">
      <h2>Quick Guide</h2>
      <p>${escapeHtml(post.description)}</p>
      <p>This page is part of the Seat Outlet ticket buying guide series. Use it as a starting point, then compare event dates, venue details, delivery notes, fees and the seller guarantee before checking out.</p>
      <p>For live event inventory and deeper marketplace browsing, continue on the beta Seat Outlet experience.</p>
      <a class="so-article__cta" href="${BETA_ORIGIN}/search">Search Tickets</a>
    </article>
  </main>
</body>
</html>`;
  return new Response(body, {
    headers: {
      "cache-control": "public, max-age=0, s-maxage=120, stale-while-revalidate=600",
      "content-type": "text/html; charset=UTF-8"
    }
  });
}
function escapeHtml(value) {
  return value.replace(/[&<>"']/g, (char) => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#39;"
  })[char]);
}
export {
  index_default as default
};
