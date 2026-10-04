# Cloudflare go-live fixes (need Cloudflare dashboard access)

The live site seatoutlet.com is served by the Worker `seatoutlet-blog-proxy`, which fetches every page from
beta.seatoutlet.com. The site code already covers what it can (www redirect, noindex on non-page paths, live always gets
the deployed main/home JavaScript). The four items below can only be done in Cloudflare.

## 1. Workers plan (do first)

Workers Free allows 100,000 requests per day (then visitors get error 1027) and 10 ms of CPU per request. Every page view
makes many requests through the Worker. Cloudflare dashboard > Workers & Pages > Plans: switch to **Workers Paid**.
Then Workers Routes > the seatoutlet.com route > set request limit failure mode to **Fail open** (if the Worker ever fails,
requests go straight to the origin instead of an error page).

## 2. robots.txt and sitemap (Worker code)

The Worker serves its own robots.txt (no Disallow lines) and a 21-URL sitemap. The real sitemap index at `/sitemap.php`
lists about 75,000 URLs (events, performers, venues, cities, pages). In the Worker editor replace the two functions below,
and make the sitemap call awaited:

```js
// in fetch(): replace   return sitemapResponse(url);   with
return await sitemapResponse(url);

function robotsResponse(requestUrl) {
  const origin = `${requestUrl.protocol}//${requestUrl.hostname}`;
  const disallow = ["/admin/", "/ajax/", "/cache/", "/vendor/", "/db/", "/tools/", "/cron/", "/deploy/", "/docs/", "/inc/",
    "/search", "/checkout", "/newsletter", "/unsubscribe", "/thank-you", "/order-confirmation"];
  return new Response(`User-agent: *\n${disallow.map((p) => `Disallow: ${p}`).join("\n")}\n\nSitemap: ${origin}/sitemap.php\n`, {
    headers: { "cache-control": "public, max-age=3600", "content-type": "text/plain; charset=UTF-8" }
  });
}

// /sitemap.xml now serves the real sitemap index (the same document as /sitemap.php), with live URLs.
async function sitemapResponse(requestUrl) {
  const origin = `${requestUrl.protocol}//${requestUrl.hostname}`;
  const upstream = await fetch(`${BETA_ORIGIN}/sitemap.php`, { headers: { "Host": "beta.seatoutlet.com" } });
  const body = (await upstream.text()).replaceAll(BETA_ORIGIN, origin);
  return new Response(body, {
    status: upstream.status,
    headers: { "cache-control": "public, max-age=3600", "content-type": "application/xml; charset=UTF-8" }
  });
}
```

Also in `renderPostFallback`: change the button link `${BETA_ORIGIN}/search` to `/search` and delete the sentence
"For live event inventory and deeper marketplace browsing, continue on the beta Seat Outlet experience." so the
fallback page never sends visitors to beta.

The Worker's `REPO_JS_ASSETS` / `LIVE_REPO_COMMIT` block is no longer used by the site (pages load `/js//main.min.js`),
so it can be deleted.

After deploying: open https://seatoutlet.com/robots.txt and https://seatoutlet.com/sitemap.xml to check, then in Google
Search Console (property seatoutlet.com) > Sitemaps, submit `https://seatoutlet.com/sitemap.php`.

## 3. Rocket Loader off

Speed > Optimization > Content Optimization > **Rocket Loader: Off**. It rewrites every script on the page; the seat-map
widget needs to load in order, and when the site's scripts opted out of it (Oct 4) it froze `document.readyState` and the
home page Top Performers and Top Venues never loaded. Off is the only setting that is safe for this site.

## 4. Always Use HTTPS

SSL/TLS > Edge Certificates > **Always Use HTTPS: On** (sends http:// visitors to https://). Could not be verified from
outside.

## Better long-term setup (instead of the Worker)

Give seatoutlet.com its own site on the server with production settings (`HOME_URL=https://seatoutlet.com`,
`SITE_INDEXABLE=1`, `GTM_ID`, live `BASE_URL`), point the DNS record at the server (proxied), and delete the Worker. The
code already handles robots.txt (robots.php), sitemaps, canonical URLs and noindex per host. Then Cloudflare can cache the
pages (the site already sends `s-maxage=120, stale-while-revalidate=600`) with one Cache Rule.
