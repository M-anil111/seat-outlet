<?xml version="1.0" encoding="UTF-8"?>
<!--
  Makes the XML sitemaps readable in a browser: a header, a few facts about the file and a searchable table with the time each address
  last changed. Search engines ignore this file and read the XML underneath exactly as before.
  Served as text/xsl from /sitemap.xsl (a static file; docs/server-rewrites.md has the one server line). XSLT 1.0 only (that is what browsers run), no external requests.
-->
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:s="http://www.sitemaps.org/schemas/sitemap/0.9">
  <xsl:output method="html" encoding="UTF-8" indent="no"/>

  <!-- "2026-10-04T17:49:46+00:00" -> "Oct 4, 2026" and "17:49 UTC". A plain date has no time part. -->
  <xsl:template name="when">
    <xsl:param name="d"/>
    <xsl:choose>
      <xsl:when test="string-length($d) &gt;= 10">
        <xsl:variable name="m" select="number(substring($d, 6, 2))"/>
        <span class="day"><xsl:value-of select="substring('JanFebMarAprMayJunJulAugSepOctNovDec', ($m - 1) * 3 + 1, 3)"/><xsl:text> </xsl:text><xsl:value-of select="number(substring($d, 9, 2))"/><xsl:text>, </xsl:text><xsl:value-of select="substring($d, 1, 4)"/></span>
        <xsl:if test="string-length($d) &gt;= 16">
          <span class="time"><xsl:value-of select="substring($d, 12, 5)"/><xsl:choose><xsl:when test="contains($d, '+00:00') or substring($d, string-length($d)) = 'Z'"><xsl:text> UTC</xsl:text></xsl:when><xsl:otherwise><xsl:text> </xsl:text><xsl:value-of select="substring($d, 20)"/></xsl:otherwise></xsl:choose></span>
        </xsl:if>
      </xsl:when>
      <xsl:otherwise><span class="none">&#8212;</span></xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <!-- The kind of address, from its path. -->
  <xsl:template name="kind">
    <xsl:param name="u"/>
    <xsl:choose>
      <xsl:when test="contains($u, '/event/')">event</xsl:when>
      <xsl:when test="contains($u, '/artist/')">artist</xsl:when>
      <xsl:when test="contains($u, '/venue/')">venue</xsl:when>
      <xsl:when test="contains($u, '/city/')">city</xsl:when>
      <xsl:when test="contains($u, '/blog')">blog</xsl:when>
      <xsl:when test="contains($u, '/category/') or contains($u, '-city/') or substring($u, string-length($u) - 7) = '-tickets'">category</xsl:when>
      <xsl:otherwise>page</xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <!-- The path part of a full address: "https://seatoutlet.com/event/x-1" -> "/event/x-1". -->
  <xsl:template name="path">
    <xsl:param name="u"/>
    <xsl:variable name="rest" select="substring-after(substring-after($u, '//'), '/')"/>
    <xsl:text>/</xsl:text><xsl:value-of select="$rest"/>
  </xsl:template>

  <xsl:template match="/">
    <html lang="en">
      <head>
        <meta charset="UTF-8"/>
        <meta name="viewport" content="width=device-width, initial-scale=1"/>
        <meta name="robots" content="noindex"/>
        <meta name="color-scheme" content="light dark"/>
        <link rel="icon" href="/images/favicon-new.webp"/>
        <title>
          <xsl:choose>
            <xsl:when test="s:sitemapindex">Sitemap index</xsl:when>
            <xsl:otherwise>Sitemap file</xsl:otherwise>
          </xsl:choose>
          <xsl:text> - Seat Outlet</xsl:text>
        </title>
        <style>
          :root{--bg:#f5f5f7;--card:#fff;--ink:#1d1d1f;--mute:#6e6e73;--line:#e8e8ed;--accent:#0066cc;--accent-soft:#e8f1fb;--head:#fafafa;--shadow:0 1px 2px rgba(0,0,0,.04),0 0 0 1px rgba(0,0,0,.05)}
          @media (prefers-color-scheme:dark){:root{--bg:#000;--card:#161618;--ink:#f5f5f7;--mute:#a1a1a6;--line:#2c2c2e;--accent:#4da3ff;--accent-soft:#102a43;--head:#1c1c1e;--shadow:0 0 0 1px rgba(255,255,255,.08)}}
          *{box-sizing:border-box}
          body{margin:0;background:var(--bg);color:var(--ink);font:15px/1.5 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;-webkit-font-smoothing:antialiased}
          .top{background:var(--card);box-shadow:0 1px 0 var(--line)}
          .top__in{max-width:1040px;margin:0 auto;padding:14px 20px;display:flex;align-items:center;gap:12px}
          .brand{font-weight:700;letter-spacing:-.02em;font-size:18px;color:var(--ink);text-decoration:none}
          .brand b{color:var(--accent)}
          .pill{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--accent);background:var(--accent-soft);border-radius:999px;padding:3px 10px}
          main{max-width:1040px;margin:0 auto;padding:32px 20px 72px}
          h1{font-size:32px;line-height:1.15;letter-spacing:-.03em;margin:0 0 8px}
          .lead{color:var(--mute);margin:0 0 24px;max-width:70ch}
          .stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;margin:0 0 24px}
          .stat{background:var(--card);border-radius:14px;padding:16px 18px;box-shadow:var(--shadow)}
          .stat__k{font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:var(--mute)}
          .stat__v{font-size:26px;font-weight:700;letter-spacing:-.02em;margin-top:2px}
          .stat__v .time{font-weight:500}
          .stat__v small{display:block;font-size:13px;font-weight:500;letter-spacing:0;color:var(--mute);margin-top:2px}
          .bar{display:flex;gap:12px;align-items:center;margin:0 0 12px}
          .bar input{flex:1;min-width:0;font:inherit;color:var(--ink);background:var(--card);border:0;border-radius:12px;padding:11px 14px;box-shadow:var(--shadow);outline:none}
          .bar input:focus{box-shadow:0 0 0 2px var(--accent)}
          .bar span{color:var(--mute);font-size:13px;white-space:nowrap}
          .wrap{background:var(--card);border-radius:14px;box-shadow:var(--shadow);overflow:hidden}
          table{width:100%;border-collapse:collapse}
          th{position:sticky;top:0;background:var(--head);color:var(--mute);font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;text-align:left;padding:11px 16px;border-bottom:1px solid var(--line)}
          td{padding:11px 16px;border-bottom:1px solid var(--line);vertical-align:middle}
          tr:last-child td{border-bottom:0}
          tbody tr:hover td{background:var(--head)}
          td.n{color:var(--mute);width:56px;font-variant-numeric:tabular-nums}
          td.u{word-break:break-all}
          td.u a{color:var(--accent);text-decoration:none}
          td.u a:hover{text-decoration:underline}
          td.w{white-space:nowrap;width:190px}
          .day{display:block;font-weight:500}
          .time{display:block;color:var(--mute);font-size:12px;font-variant-numeric:tabular-nums}
          .none{color:var(--mute)}
          .chip{display:inline-block;font-size:11px;font-weight:600;border-radius:999px;padding:2px 9px;background:var(--head);color:var(--mute);box-shadow:inset 0 0 0 1px var(--line);text-transform:capitalize}
          .chip.event{background:#e7f6ec;color:#17692e;box-shadow:none}.chip.artist{background:#efe9fb;color:#5b34b3;box-shadow:none}
          .chip.venue{background:#fdf0e1;color:#9a5a05;box-shadow:none}.chip.city{background:#e4f2fb;color:#0b5f93;box-shadow:none}
          .chip.blog{background:#fde9ee;color:#a3153f;box-shadow:none}.chip.category{background:#fbf5d6;color:#7a6200;box-shadow:none}
          @media (prefers-color-scheme:dark){.chip.event{background:#10301b;color:#7ddb98}.chip.artist{background:#261a45;color:#c2a8ff}.chip.venue{background:#3a2610;color:#ffc27a}.chip.city{background:#0f2a3d;color:#7cc4f0}.chip.blog{background:#3d1220;color:#ff9db6}.chip.category{background:#352d08;color:#f0d96a}}
          td.t{width:110px}
          .foot{color:var(--mute);font-size:13px;margin:20px 2px 0;max-width:80ch}
          .foot a{color:var(--accent)}
          @media (max-width:640px){
            h1{font-size:26px}
            td.n,th.n,td.t,th.t{display:none}
            td.w{width:auto;padding-left:8px}
            th,td{padding:10px 12px}
          }
        </style>
      </head>
      <body>
        <header class="top"><div class="top__in"><a class="brand" href="/">Seat <b>Outlet</b></a><span class="pill">XML sitemap</span></div></header>
        <main>
          <xsl:choose>
            <!-- ======================= index of files ======================= -->
            <xsl:when test="s:sitemapindex">
              <h1>Sitemap index</h1>
              <p class="lead">This is the master list. Each file below holds up to 5,000 addresses for one kind of page, and search engines read them in turn. Open any file to see what it lists.</p>
              <div class="stats">
                <div class="stat"><div class="stat__k">Sitemap files</div><div class="stat__v"><xsl:value-of select="format-number(count(s:sitemapindex/s:sitemap), '#,##0')"/></div></div>
                <div class="stat"><div class="stat__k">Last change</div><div class="stat__v">
                  <xsl:for-each select="s:sitemapindex/s:sitemap/s:lastmod"><xsl:sort select="." order="descending"/>
                    <xsl:if test="position() = 1"><xsl:call-template name="when"><xsl:with-param name="d" select="."/></xsl:call-template></xsl:if>
                  </xsl:for-each></div></div>
              </div>
              <div class="bar"><input id="q" type="search" placeholder="Filter files" autocomplete="off" aria-label="Filter"/><span id="shown"></span></div>
              <div class="wrap"><table>
                <thead><tr><th>Sitemap file</th><th class="t">Contains</th><th class="w">Last change</th></tr></thead>
                <tbody>
                <xsl:for-each select="s:sitemapindex/s:sitemap">
                  <tr>
                    <td class="u"><a href="{s:loc}"><xsl:value-of select="substring-after(substring-after(s:loc, '//'), '/')"/></a></td>
                    <td class="t"><span class="chip">
                      <xsl:choose>
                        <xsl:when test="contains(s:loc, 'events-')">events</xsl:when>
                        <xsl:when test="contains(s:loc, 'performers-')">artists</xsl:when>
                        <xsl:when test="contains(s:loc, 'venues-')">venues</xsl:when>
                        <xsl:when test="contains(s:loc, 'cities-')">cities</xsl:when>
                        <xsl:otherwise>pages</xsl:otherwise>
                      </xsl:choose></span></td>
                    <td class="w"><xsl:call-template name="when"><xsl:with-param name="d" select="s:lastmod"/></xsl:call-template></td>
                  </tr>
                </xsl:for-each>
                </tbody>
              </table></div>
            </xsl:when>
            <!-- ======================= one file of addresses ======================= -->
            <xsl:otherwise>
              <xsl:variable name="k"><xsl:call-template name="kind"><xsl:with-param name="u" select="s:urlset/s:url[1]/s:loc"/></xsl:call-template></xsl:variable>
              <h1>
                <xsl:choose>
                  <xsl:when test="$k = 'event'">Event pages</xsl:when>
                  <xsl:when test="$k = 'artist'">Artist and team pages</xsl:when>
                  <xsl:when test="$k = 'venue'">Venue pages</xsl:when>
                  <xsl:when test="$k = 'city'">City pages</xsl:when>
                  <xsl:otherwise>Site pages</xsl:otherwise>
                </xsl:choose>
              </h1>
              <p class="lead">Every address in this file is a page we want search engines to find. Where a date is shown, it is when we last saw that page change, in UTC.</p>
              <div class="stats">
                <div class="stat"><div class="stat__k">Addresses</div><div class="stat__v"><xsl:value-of select="format-number(count(s:urlset/s:url), '#,##0')"/><small>of 50,000 allowed per file</small></div></div>
                <div class="stat"><div class="stat__k">With a change date</div><div class="stat__v"><xsl:value-of select="format-number(count(s:urlset/s:url/s:lastmod), '#,##0')"/><small>of <xsl:value-of select="format-number(count(s:urlset/s:url), '#,##0')"/> addresses</small></div></div>
                <xsl:if test="s:urlset/s:url/s:lastmod">
                  <div class="stat"><div class="stat__k">Newest change</div><div class="stat__v">
                    <xsl:for-each select="s:urlset/s:url/s:lastmod"><xsl:sort select="." order="descending"/>
                      <xsl:if test="position() = 1"><xsl:call-template name="when"><xsl:with-param name="d" select="."/></xsl:call-template></xsl:if>
                    </xsl:for-each></div></div>
                  <div class="stat"><div class="stat__k">Oldest change</div><div class="stat__v">
                    <xsl:for-each select="s:urlset/s:url/s:lastmod"><xsl:sort select="." order="ascending"/>
                      <xsl:if test="position() = 1"><xsl:call-template name="when"><xsl:with-param name="d" select="."/></xsl:call-template></xsl:if>
                    </xsl:for-each></div></div>
                </xsl:if>
              </div>
              <div class="bar"><input id="q" type="search" placeholder="Filter addresses" autocomplete="off" aria-label="Filter"/><span id="shown"></span></div>
              <div class="wrap"><table>
                <thead><tr><th class="n">#</th><th>Address</th><th class="t">Type</th><th class="w">Last change</th></tr></thead>
                <tbody>
                <xsl:for-each select="s:urlset/s:url">
                  <xsl:variable name="rk"><xsl:call-template name="kind"><xsl:with-param name="u" select="s:loc"/></xsl:call-template></xsl:variable>
                  <tr>
                    <td class="n"><xsl:value-of select="position()"/></td>
                    <td class="u"><a href="{s:loc}"><xsl:call-template name="path"><xsl:with-param name="u" select="s:loc"/></xsl:call-template></a></td>
                    <td class="t"><span class="chip {$rk}"><xsl:value-of select="$rk"/></span></td>
                    <td class="w"><xsl:call-template name="when"><xsl:with-param name="d" select="s:lastmod"/></xsl:call-template></td>
                  </tr>
                </xsl:for-each>
                </tbody>
              </table></div>
            </xsl:otherwise>
          </xsl:choose>
          <p class="foot">This page is only a readable view. Search engines read the same file as plain XML. Back to the <a href="/sitemaps/sitemap.xml">sitemap index</a> or the <a href="/">home page</a>.</p>
        </main>
        <script>
          (function () {
            var q = document.getElementById('q'), out = document.getElementById('shown');
            var rows = document.querySelectorAll('tbody tr');
            function show() {
              var t = q.value.toLowerCase().replace(/^\s+|\s+$/g, ''), n = 0, i;
              for (i = 0; i !== rows.length; i++) {
                var hit = t === '' || rows[i].textContent.toLowerCase().indexOf(t) !== -1;
                rows[i].style.display = hit ? '' : 'none';
                if (hit) n++;
              }
              out.textContent = t === '' ? rows.length.toLocaleString('en-US') + ' shown' : n.toLocaleString('en-US') + ' of ' + rows.length.toLocaleString('en-US');
            }
            if (q &amp;&amp; out) { q.addEventListener('input', show); show(); }
          })();
        </script>
      </body>
    </html>
  </xsl:template>
</xsl:stylesheet>
