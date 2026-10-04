<?xml version="1.0" encoding="UTF-8"?>
<!-- Makes the XML sitemaps readable in a browser. Search engines ignore this file and read the XML as before. -->
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:s="http://www.sitemaps.org/schemas/sitemap/0.9">
  <xsl:output method="html" encoding="UTF-8" indent="yes"/>
  <xsl:template match="/">
    <html lang="en">
      <head>
        <meta charset="UTF-8"/>
        <meta name="viewport" content="width=device-width, initial-scale=1"/>
        <meta name="robots" content="noindex"/>
        <title>Seat Outlet sitemap</title>
        <style>
          body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;margin:0;background:#f5f5f7;color:#1d1d1f}
          main{max-width:960px;margin:0 auto;padding:32px 20px 64px}
          h1{font-size:28px;letter-spacing:-.02em;margin:0 0 6px}
          p.lead{color:#6e6e73;margin:0 0 24px;font-size:15px;line-height:1.5}
          table{width:100%;border-collapse:collapse;background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 0 0 1px rgba(0,0,0,.06)}
          th,td{text-align:left;padding:11px 16px;font-size:14px;border-bottom:1px solid #eee;word-break:break-all}
          th{background:#fafafa;color:#6e6e73;font-weight:600;font-size:12px;text-transform:uppercase;letter-spacing:.04em}
          td.d{white-space:nowrap;color:#6e6e73;width:120px}
          tr:last-child td{border-bottom:0}
          a{color:#0066cc;text-decoration:none}
          a:hover{text-decoration:underline}
        </style>
      </head>
      <body>
        <main>
          <h1>Seat Outlet sitemap</h1>
          <xsl:choose>
            <xsl:when test="s:sitemapindex">
              <p class="lead"><xsl:value-of select="count(s:sitemapindex/s:sitemap)"/> sitemap files. Each lists up to 5,000 addresses: pages, events, performers, venues and cities.</p>
              <table>
                <tr><th>Sitemap file</th><th>Updated</th></tr>
                <xsl:for-each select="s:sitemapindex/s:sitemap">
                  <tr><td><a href="{s:loc}"><xsl:value-of select="s:loc"/></a></td><td class="d"><xsl:value-of select="s:lastmod"/></td></tr>
                </xsl:for-each>
              </table>
            </xsl:when>
            <xsl:otherwise>
              <p class="lead"><xsl:value-of select="count(s:urlset/s:url)"/> addresses in this file.</p>
              <table>
                <tr><th>Address</th><th>Updated</th></tr>
                <xsl:for-each select="s:urlset/s:url">
                  <tr><td><a href="{s:loc}"><xsl:value-of select="s:loc"/></a></td><td class="d"><xsl:value-of select="s:lastmod"/></td></tr>
                </xsl:for-each>
              </table>
            </xsl:otherwise>
          </xsl:choose>
        </main>
      </body>
    </html>
  </xsl:template>
</xsl:stylesheet>
