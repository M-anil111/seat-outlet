# SEO titles and meta descriptions

Every page has three title variants and three meta description variants. The first variant is the one in use (`inc/seo-keywords.php` for the pages below, the page templates for programmatic pages).

## Rules used

- Title format (updated 4 Oct 2026): plain words, no separators. `Keyword and hook at Seat Outlet`, for example `Dallas Mavericks Tickets 2026 Schedule & Prices at Seat Outlet`. A title never contains a pipe, a dash, a spaced hyphen, a colon or an ellipsis; `soNormalizeTitle()` in `inc/title.php` removes them from every title (templates, the keyword plan, admin page rules, blog meta titles) and `tools/check-seo-titles.php` fails CI when a plan entry breaks the rule. The whole title stays at 59 characters or fewer including the brand. A long name is trimmed to whole words and keeps the word Tickets (no ellipsis). Titles that already contain the brand do not repeat it.
- The variant tables below were written for the old em dash format and are kept as history. The meta descriptions no longer say "our guarantee": the guarantee is TicketNetwork's, and Seat Outlet does not sell the tickets.
- Meta descriptions: 120 to 155 characters, keyword in the first sentence, and a reason to click that is true for every order (100% buyer guarantee, live seat maps, secure checkout). `soMetaFit()` keeps template descriptions in range.
- `{Y}` is replaced with the current year. Lengths below are counted with the year filled in.
- Focus keywords were researched on 30 Sep 2026 (SE Ranking, US) and were kept; every chosen title starts with its keyword.

## Site pages

### `/`

Focus keyword: **buy event tickets**

| # | Title | Length | Meta description | Length |
|---|---|---|---|---|
| 1 (in use) | Buy Event Tickets—Concerts, Sports & Theater—Seat Outlet | 56 | Buy event tickets for concerts, sports, theater and festivals. Compare seats and prices side by side, check out securely and get our 100% guarantee. | 148 |
| 2 | Buy Event Tickets—2026 Shows, 100% Guarantee—Seat Outlet | 56 | Buy event tickets for 2026 concerts, games and shows. Live seat maps, real prices from many sellers and a 100% buyer guarantee on every order. | 142 |
| 3 | Buy Event Tickets Online—Compare Seats & Prices—Seat Outlet | 59 | Buy event tickets from one trusted resale marketplace. Search any performer, team or city, compare seats and prices, and buy with a 100% guarantee. | 147 |

### `/concert-tickets-for-sale`

Focus keyword: **concert tickets for sale**

| # | Title | Length | Meta description | Length |
|---|---|---|---|---|
| 1 (in use) | Concert Tickets for Sale—2026 Tours & Shows—Seat Outlet | 55 | Concert tickets for sale to 2026 tours and shows near you. Compare seats and prices, filter by date and genre, and buy with our 100% buyer guarantee. | 149 |
| 2 | Concert Tickets for Sale—Compare Seats & Prices—Seat Outlet | 59 | Find concert tickets for sale for every genre, from pop and rock to hip hop and country. Live seat maps, real prices and a 100% buyer guarantee. | 144 |
| 3 | Concert Tickets for Sale—100% Buyer Guarantee—Seat Outlet | 57 | Concert tickets for sale from many sellers in one place. See upcoming tour dates near you, compare prices and check out securely with a 100% guarantee. | 151 |

### `/game-day-tickets`

Focus keyword: **game day tickets**

| # | Title | Length | Meta description | Length |
|---|---|---|---|---|
| 1 (in use) | Game Day Tickets—NFL, NBA, MLB & NHL Seats—Seat Outlet | 54 | Game day tickets for NFL, NBA, MLB, NHL, college and soccer. Compare seats and prices on live seat maps and buy with our 100% buyer guarantee. | 142 |
| 2 | Game Day Tickets—2026 Schedules & Prices—Seat Outlet | 52 | Find game day tickets for every 2026 schedule. Filter by team, league, date and price, compare sections, and check out securely with a 100% guarantee. | 150 |
| 3 | Game Day Tickets—Compare Seats, 100% Guarantee—Seat Outlet | 58 | Game day tickets from many sellers in one place. See the next home games near you, compare seats and prices, and buy with a 100% buyer guarantee. | 145 |

### `/buy-broadway-tickets`

Focus keyword: **buy broadway tickets**

| # | Title | Length | Meta description | Length |
|---|---|---|---|---|
| 1 (in use) | Buy Broadway Tickets—2026 Shows & Musicals—Seat Outlet | 54 | Buy Broadway tickets for musicals, plays and touring shows. Compare orchestra and mezzanine seats and prices, then buy with our 100% buyer guarantee. | 149 |
| 2 | Buy Broadway Tickets—Musicals, Plays & Tours—Seat Outlet | 56 | Buy Broadway tickets for 2026 musicals and plays in New York and on tour. Live seat maps, prices from many sellers and a 100% buyer guarantee. | 142 |
| 3 | Buy Broadway Tickets—Compare Seats & Prices—Seat Outlet | 55 | Buy Broadway tickets and theater tickets near you. Filter by show, city and date, compare seats on the seat map, and check out with a 100% guarantee. | 149 |

### `/upcoming-music-festivals`

Focus keyword: **upcoming music festivals**

| # | Title | Length | Meta description | Length |
|---|---|---|---|---|
| 1 (in use) | Upcoming Music Festivals—2026 Lineups & Passes—Seat Outlet | 58 | Upcoming music festivals across the country, with 2026 dates and passes. Compare GA, VIP and single day prices and buy with our 100% buyer guarantee. | 149 |
| 2 | Upcoming Music Festivals—Tickets & Dates—Seat Outlet | 52 | Find upcoming music festivals near you. See festival dates and weekend passes, compare prices from many sellers, and buy with a 100% guarantee. | 143 |
| 3 | Upcoming Music Festivals—Compare Passes—Seat Outlet | 51 | Upcoming music festivals for every genre. Browse lineups by date and city, compare festival pass prices and check out securely with a 100% guarantee. | 149 |

### `/city-events`

Focus keyword: **city events**

| # | Title | Length | Meta description | Length |
|---|---|---|---|---|
| 1 (in use) | City Events—2026 Concerts, Sports & Shows—Seat Outlet | 53 | City events in every major US city. Pick your city to see upcoming concerts, games and shows, compare seats and prices, and buy with a 100% guarantee. | 150 |
| 2 | City Events—Tickets in Top US Cities—Seat Outlet | 48 | Find city events near you for 2026: concerts, sports, theater and festivals by city and state, with real prices and a 100% buyer guarantee. | 139 |
| 3 | City Events—Find Events Near You—Seat Outlet | 44 | Browse city events by state and city. See what is on this week and this month, compare tickets from many sellers and check out with a 100% guarantee. | 149 |

### `/all-artists-and-teams`

Focus keyword: **all artists**

| # | Title | Length | Meta description | Length |
|---|---|---|---|---|
| 1 (in use) | All Artists, Teams & Shows—A to Z—Seat Outlet | 45 | All artists, teams and shows with tickets on sale, A to Z. Pick a name to see upcoming dates, compare seats and prices and buy with a 100% guarantee. | 149 |
| 2 | All Artists & Teams—Browse 2026 Tickets A to Z—Seat Outlet | 58 | Browse all artists and sports teams with 2026 dates on sale. Jump to a letter, open a tour or schedule and compare tickets with a 100% guarantee. | 145 |
| 3 | All Artists, Teams & Shows—Tickets on Sale—Seat Outlet | 54 | Find every artist, team and show on Seat Outlet in one A to Z list. See tour dates and schedules, compare prices and buy with a 100% guarantee. | 143 |

### `/buy-tickets-online`

Focus keyword: **buy tickets online**

| # | Title | Length | Meta description | Length |
|---|---|---|---|---|
| 1 (in use) | Buy Tickets Online—All Events, Secure Checkout—Seat Outlet | 58 | Buy tickets online for concerts, sports, theater and festivals. Search every upcoming event, compare seats and prices, and check out with a 100% guarantee. | 155 |
| 2 | Buy Tickets Online—2026 Concerts, Games & Shows—Seat Outlet | 59 | Buy tickets online for 2026 events near you and across the US. Filter by date, distance and price, see live seat maps and buy with a 100% guarantee. | 148 |
| 3 | Buy Tickets Online—Compare Seats & Prices—Seat Outlet | 53 | Buy tickets online from many sellers in one place. Compare sections and prices, pick seats on the live seat map and check out with a 100% guarantee. | 148 |

### `/tickets-promo-code`

Focus keyword: **tickets promo code**

| # | Title | Length | Meta description | Length |
|---|---|---|---|---|
| 1 (in use) | Tickets Promo Code—Current Seat Outlet Offers | 45 | Looking for a tickets promo code? See current Seat Outlet offers, how to apply a code at checkout, and honest ways to pay less for concerts and games. | 150 |
| 2 | Tickets Promo Code—Save on 2026 Events—Seat Outlet | 50 | Tickets promo code page for 2026: current Seat Outlet offers, where the code box is at checkout and tips to save on live event tickets. | 135 |
| 3 | Tickets Promo Code—How to Apply at Checkout—Seat Outlet | 55 | Find a working tickets promo code, learn how to enter it at checkout, and see other ways to save on concerts, sports and theater tickets. | 137 |

### `/ticket-deals`

Focus keyword: **ticket deals**

| # | Title | Length | Meta description | Length |
|---|---|---|---|---|
| 1 (in use) | Ticket Deals—Save on Concerts & Sports—Seat Outlet | 50 | Ticket deals on concerts, sports and live events. Compare prices from many sellers, sort by lowest price and find seats that fit your budget today. | 147 |
| 2 | Ticket Deals—2026 Prices From Many Sellers—Seat Outlet | 54 | Find ticket deals for 2026 events by comparing prices across sellers. Filter by date and budget and buy any listing with our 100% buyer guarantee. | 146 |
| 3 | Ticket Deals—Find Seats That Fit Your Budget—Seat Outlet | 56 | Ticket deals without the guesswork: sort upcoming events by price, compare sections on live seat maps and check out with a 100% buyer guarantee. | 144 |

### `/hunt-tickets`

Focus keyword: **hunt tickets**

| # | Title | Length | Meta description | Length |
|---|---|---|---|---|
| 1 (in use) | Hunt Tickets—Event Ticket Search—Seat Outlet | 44 | Hunt Tickets is part of the Seat Outlet network. Search events, compare ticket prices and find seats for concerts, sports and theater in a few clicks. | 150 |
| 2 | Hunt Tickets—Part of the Seat Outlet Network | 44 | Hunt Tickets helps fans search live events and compare prices. Learn what it offers and how it connects to the Seat Outlet ticket marketplace. | 142 |
| 3 | Hunt Tickets—Find Seats for Live Events—Seat Outlet | 51 | Use Hunt Tickets to search concerts, games and shows, compare prices from many sellers and buy through Seat Outlet with a 100% buyer guarantee. | 143 |

### `/grab-tickets-now`

Focus keyword: **grab tickets now**

| # | Title | Length | Meta description | Length |
|---|---|---|---|---|
| 1 (in use) | Grab Tickets Now—Simple Ticket Buying—Seat Outlet | 49 | Grab Tickets Now is part of the Seat Outlet network. Buy sports and concert tickets online through a simple, trusted event ticket marketplace. | 142 |
| 2 | Grab Tickets Now—A Seat Outlet Network Site | 43 | Grab Tickets Now offers a quick way to find live event tickets. See what it covers and how it connects to the Seat Outlet ticket marketplace. | 141 |
| 3 | Grab Tickets Now—Concert & Sports Tickets—Seat Outlet | 53 | Grab Tickets Now for concerts, games and shows: compare prices from many sellers and buy through Seat Outlet with a 100% buyer guarantee. | 137 |

### `/ticket-scanner`

Focus keyword: **ticket scanner**

| # | Title | Length | Meta description | Length |
|---|---|---|---|---|
| 1 (in use) | Ticket Scanner—Travel Booking for Your Event—Seat Outlet | 56 | Ticket Scanner is part of the Seat Outlet network: an online travel booking site for flights, hotels and car rentals to plan the trip around your event. | 152 |
| 2 | Ticket Scanner—Flights, Hotels & Car Rentals—Seat Outlet | 56 | Use Ticket Scanner to compare flights, hotels and car rentals for your next concert or game trip. Part of the Seat Outlet network of sites. | 139 |
| 3 | Ticket Scanner—Plan the Trip Around Your Show—Seat Outlet | 57 | Ticket Scanner helps you book travel for live events: flights, hotels and cars in one place. See what it offers and how it connects to Seat Outlet. | 147 |

### `/worry-free-guarantee`

Focus keyword: **worry free guarantee**

| # | Title | Length | Meta description | Length |
|---|---|---|---|---|
| 1 (in use) | Worry Free Guarantee—What Every Order Covers—Seat Outlet | 56 | Our worry free guarantee backs every order: valid tickets, delivery before the event, seats as good or better, and a full refund if the event is canceled. | 154 |
| 2 | Worry Free Guarantee—100% Buyer Protection—Seat Outlet | 54 | See exactly what the Seat Outlet worry free guarantee covers, how to make a claim and what happens if an event is canceled or postponed. | 136 |
| 3 | Worry Free Guarantee—Valid Tickets, On Time—Seat Outlet | 55 | Worry free guarantee on every Seat Outlet ticket: authentic tickets, on time delivery and a refund for canceled events. Read the full terms here. | 145 |

### `/ticket-buyer-protection`

Focus keyword: **ticket buyer protection**

| # | Title | Length | Meta description | Length |
|---|---|---|---|---|
| 1 (in use) | Ticket Buyer Protection—Complete 2026 Guide—Seat Outlet | 55 | Ticket buyer protection on every Seat Outlet order: valid tickets, on time delivery and secure payment. See what is covered and how to get help fast. | 149 |
| 2 | Ticket Buyer Protection—How You Are Covered—Seat Outlet | 55 | How ticket buyer protection works on a resale marketplace: what the guarantee covers, how payment is secured and what to do if something goes wrong. | 148 |
| 3 | Ticket Buyer Protection—Safe Resale Checklist—Seat Outlet | 57 | Ticket buyer protection explained: guarantee terms, secure checkout and the checks to make before you buy resale tickets from any website. | 138 |

### `/customer-testimonials`

Focus keyword: **customer testimonials**

| # | Title | Length | Meta description | Length |
|---|---|---|---|---|
| 1 (in use) | Customer Testimonials—Where to Read Reviews—Seat Outlet | 55 | Seat Outlet does not publish customer testimonials yet. See what we can show today, how our guarantee works and where to find independent reviews. | 146 |
| 2 | Customer Testimonials—Why We Show None Yet—Seat Outlet | 54 | Why this page has no customer testimonials yet, what proof we can show instead, and how to check any ticket seller before you buy. | 130 |
| 3 | Customer Testimonials—Judge a Ticket Site—Seat Outlet | 53 | Customer testimonials should be real. Until we can show verified ones, here is our guarantee, our contact details and where to read reviews. | 140 |

### `/seat-outlet-reviews`

Focus keyword: **seat outlet reviews**

| # | Title | Length | Meta description | Length |
|---|---|---|---|---|
| 1 (in use) | Seat Outlet Reviews—What We Can Show Today | 42 | Looking for Seat Outlet reviews? We do not show reviews on our own site yet. See how the guarantee works and how to judge a ticket seller before you buy. | 153 |
| 2 | Seat Outlet Reviews—Guarantee & Contact Facts | 45 | Seat Outlet reviews: the facts we can share today, including our guarantee, checkout security and how to reach a real person about an order. | 140 |
| 3 | Seat Outlet Reviews—How to Check a Ticket Site | 46 | Before you read Seat Outlet reviews, see the facts: who we are, how orders are fulfilled and guaranteed, and how to contact customer service. | 141 |

### `/seat-outlet-bbb`

Focus keyword: **seat outlet bbb**

| # | Title | Length | Meta description | Length |
|---|---|---|---|---|
| 1 (in use) | Seat Outlet BBB—Our Customer Commitments | 40 | Looking for Seat Outlet on the Better Business Bureau? This page does not show a BBB rating. It explains our customer commitments and how to reach us. | 150 |
| 2 | Seat Outlet BBB—Rating Status & Contact | 39 | Seat Outlet BBB status explained, the commitments we make to every buyer, and how to raise an order problem directly with our customer service team. | 148 |
| 3 | Seat Outlet BBB—How We Handle Complaints | 40 | Seat Outlet BBB page: what we can and cannot show about ratings, how complaints are handled, and the guarantee that covers every ticket order. | 142 |

### `/about-seat-outlet`

Focus keyword: **about seat outlet**

| # | Title | Length | Meta description | Length |
|---|---|---|---|---|
| 1 (in use) | About Seat Outlet—Who We Are & How It Works | 43 | About Seat Outlet: an independent resale ticket marketplace for concerts, sports, theater and festivals, with orders fulfilled through TicketNetwork. | 149 |
| 2 | About Seat Outlet—Independent Ticket Resale | 43 | Who runs Seat Outlet, where the tickets come from, how orders are fulfilled and guaranteed, and how to reach our customer service team. | 135 |
| 3 | About Seat Outlet—Our Story and Guarantee | 41 | Seat Outlet is an independent resale marketplace. Learn how listings, prices, delivery and the 100% guarantee work before you buy tickets. | 138 |

### `/how-to-buy-tickets-online`

Focus keyword: **how to buy tickets online**

| # | Title | Length | Meta description | Length |
|---|---|---|---|---|
| 1 (in use) | How to Buy Tickets Online—A Simple Guide—Seat Outlet | 52 | Learn how to buy tickets online: find your event, compare seats and prices, check out securely and get your tickets on time. A simple step by step guide. | 153 |
| 2 | How to Buy Tickets Online—5 Easy Steps—Seat Outlet | 50 | How to buy tickets online in five steps, from search to seat map to secure checkout and delivery, plus the checks that keep you safe. | 133 |
| 3 | How to Buy Tickets Online Safely—Guide—Seat Outlet | 50 | How to buy tickets online safely: pick the right seats, read the fees, check out securely and know what the 100% buyer guarantee covers. | 136 |

### `/ticket-partner-program`

Focus keyword: **ticket partner program**

| # | Title | Length | Meta description | Length |
|---|---|---|---|---|
| 1 (in use) | Ticket Partner Program—Work With Seat Outlet | 44 | Run a venue, festival, team or business? Send Seat Outlet a partnership enquiry and see what we are, what we are not and what to ask any marketplace. | 149 |
| 2 | Ticket Partner Program—Venues, Teams & Brands—Seat Outlet | 57 | The Seat Outlet ticket partner program for venues, teams, festivals and brands: how partnerships work and how to start a conversation with us. | 142 |
| 3 | Ticket Partner Program—Partnership Enquiries—Seat Outlet | 56 | Ticket partner program details: who we work with, what a partnership with Seat Outlet can include and how to send your enquiry today. | 133 |

### `/ticket-faq`

Focus keyword: **ticket faq**

| # | Title | Length | Meta description | Length |
|---|---|---|---|---|
| 1 (in use) | Ticket FAQ—Delivery, Entry & Refunds—Seat Outlet | 48 | The Seat Outlet ticket FAQ answers common questions on buying tickets, delivery times, entry rules, transfers and refunds for concerts, sports and theater. | 155 |
| 2 | Ticket FAQ—Easy Answers Before You Buy—Seat Outlet | 50 | Ticket FAQ: when tickets arrive, how mobile transfer works, what to bring to the venue and what happens if an event is canceled or postponed. | 141 |
| 3 | Ticket FAQ—Concerts, Sports & Theater—Seat Outlet | 49 | Quick answers in our ticket FAQ: buying, delivery, seat details, entry requirements and the 100% guarantee for every Seat Outlet order. | 135 |

### `/ticket-customer-service`

Focus keyword: **ticket customer service**

| # | Title | Length | Meta description | Length |
|---|---|---|---|---|
| 1 (in use) | Ticket Customer Service—Help With Your Order—Seat Outlet | 56 | Ticket customer service from Seat Outlet. Email us or use the contact form for help with orders, delivery, transfers and event changes. We reply fast. | 150 |
| 2 | Ticket Customer Service—Contact Seat Outlet | 43 | Contact Seat Outlet ticket customer service about an order, a delivery question or an event change. See what to include so we can help quickly. | 143 |
| 3 | Ticket Customer Service—Email & Contact Form—Seat Outlet | 56 | Need ticket customer service? Reach Seat Outlet by email or contact form for order help, delivery updates and questions about the guarantee. | 140 |

### `/why-are-concert-tickets-so-expensive`

Focus keyword: **why are concert tickets so expensive**

| # | Title | Length | Meta description | Length |
|---|---|---|---|---|
| 1 (in use) | Why Are Concert Tickets So Expensive?—Facts—Seat Outlet | 55 | Why are concert tickets so expensive? See who sets ticket prices, fees and release dates, why tickets reach resale marketplaces, and where the money goes. | 154 |
| 2 | Why Are Concert Tickets So Expensive—Explained—Seat Outlet | 58 | Concert tickets cost more than ever. Learn how face value, dynamic pricing, fees and resale demand work, and how to find fair prices for a show. | 144 |
| 3 | Why Concert Tickets Are So Expensive—2026—Seat Outlet | 53 | Why concert tickets are so expensive in 2026: pricing, fees, presales and resale explained, plus practical ways to pay less for the seats you want. | 147 |

### `/terms-and-conditions`

Focus keyword: **terms and conditions**

| # | Title | Length | Meta description | Length |
|---|---|---|---|---|
| 1 (in use) | Terms and Conditions—Rules for Ticket Buyers—Seat Outlet | 56 | Seat Outlet terms and conditions for using our website and buying tickets for live events through our resale marketplace, including orders and refunds. | 151 |
| 2 | Terms and Conditions—Complete Guide for Buyers—Seat Outlet | 58 | Read the Seat Outlet terms and conditions: how orders, prices, delivery, cancellations and refunds work when you buy tickets on our marketplace. | 144 |
| 3 | Terms and Conditions—Orders, Delivery & Refunds—Seat Outlet | 59 | The terms and conditions that apply to every Seat Outlet ticket purchase, from placing an order to delivery, event changes and the guarantee. | 141 |

### `/privacy-policy`

Focus keyword: **privacy policy**

| # | Title | Length | Meta description | Length |
|---|---|---|---|---|
| 1 (in use) | Privacy Policy—How Seat Outlet Uses Your Data | 45 | The Seat Outlet privacy policy explains how we collect, use, share and protect your personal information when you use our site and buy event tickets. | 149 |
| 2 | Privacy Policy—Complete Guide to Your Data—Seat Outlet | 54 | Read how Seat Outlet handles your personal data: what we collect, why, who we share it with, how long we keep it and how to make a request. | 139 |
| 3 | Privacy Policy—Your Data and Your Choices—Seat Outlet | 53 | Privacy policy for Seat Outlet buyers and visitors: data we collect, cookies and analytics, your rights and how to contact us about privacy. | 140 |

### `/cookie-policy`

Focus keyword: **cookie policy**

| # | Title | Length | Meta description | Length |
|---|---|---|---|---|
| 1 (in use) | Cookie Policy—How Seat Outlet Uses Cookies | 42 | The Seat Outlet cookie policy explains how we use cookies and similar technologies on our website, what each type does and how to change your choices. | 150 |
| 2 | Cookie Policy—Complete Guide to Our Cookies—Seat Outlet | 55 | Read which cookies Seat Outlet uses, why we use them, which partners set them and how to opt out or decline cookies in your browser at any time. | 144 |
| 3 | Cookie Policy—Your Cookie Choices—Seat Outlet | 45 | Cookie policy for seatoutlet.com: essential, analytics and advertising cookies explained, plus how Global Privacy Control and opt outs work. | 140 |

### `/blog`

Focus keyword: **ticket buying tips**

| # | Title | Length | Meta description | Length |
|---|---|---|---|---|
| 1 (in use) | Ticket Buying Tips—Guides & Event Picks—Seat Outlet | 51 | Ticket buying tips, event guides and city guides from Seat Outlet. Learn how to find better seats, compare prices and buy tickets with confidence. | 146 |
| 2 | Ticket Buying Tips—Buy Smarter in 2026—Seat Outlet | 50 | Practical ticket buying tips for 2026: avoid scams, read fees, pick the right seats and plan your night out. Guides written by the Seat Outlet team. | 148 |
| 3 | Ticket Buying Tips & Event Guides—Seat Outlet | 45 | Read ticket buying tips and live event guides: safety checklists, venue guides and concert picks to help you buy the right tickets at a fair price. | 147 |

### `/our-network`

Focus keyword: **seat outlet network**

| # | Title | Length | Meta description | Length |
|---|---|---|---|---|
| 1 (in use) | Seat Outlet Network—Partner Sites & Services | 44 | The Seat Outlet network: partner sites for tickets, travel and business software, and how each one connects to our live event ticket marketplace. | 145 |
| 2 | Seat Outlet Network—Our Family of Websites | 42 | See every site in the Seat Outlet network, what each one does and where to go for event tickets, travel booking and business tools. | 131 |
| 3 | Seat Outlet Network—Travel, Tickets & Tools | 43 | Seat Outlet network sites in one place: ticket search, travel booking and software partners, with a short summary and link for each one. | 136 |

### `/image-credits`

Focus keyword: **photo credits**

| # | Title | Length | Meta description | Length |
|---|---|---|---|---|
| 1 (in use) | Photo Credits—Image Sources and Licenses—Seat Outlet | 52 | Photo credits for the images used on Seat Outlet: the source, the photographer or rights holder and the license for every picture on the site. | 142 |
| 2 | Photo Credits—Seat Outlet Image Library | 39 | See where each Seat Outlet image comes from, who owns it and which license allows us to use it, with links back to the original source. | 135 |
| 3 | Photo Credits—Who Took Our Pictures—Seat Outlet | 47 | Image credits and licenses for Seat Outlet artist, venue and event pictures, listed with the source and rights holder for each photo. | 133 |

### `/sitemap`

Focus keyword: **seat outlet sitemap**

| # | Title | Length | Meta description | Length |
|---|---|---|---|---|
| 1 (in use) | Seat Outlet Sitemap—Every Section of the Site | 45 | The Seat Outlet sitemap lists every section of the site: event categories, genres, cities, artists and teams, guides and customer service pages. | 144 |
| 2 | Seat Outlet Sitemap—Browse All Pages | 36 | Browse the full Seat Outlet sitemap to jump to any category, city, artist, team, guide or help page in one click from a single list. | 132 |
| 3 | Seat Outlet Sitemap—Events, Cities & Guides | 43 | Seat Outlet sitemap: links to every events hub, genre, city, artist and team page plus the guides and help pages, in one simple list. | 133 |

### `/dotbooker`

Focus keyword: **dotbooker**

| # | Title | Length | Meta description | Length |
|---|---|---|---|---|
| 1 (in use) | Dotbooker—Booking & Appointment Software—Seat Outlet | 52 | Dotbooker is booking and appointment software for studios, salons and wellness providers, and part of the Seat Outlet network of partner sites. | 143 |
| 2 | Dotbooker—Seat Outlet Network Partner | 37 | Learn what Dotbooker does for service businesses: online booking, reminders and schedules, plus how it fits into the Seat Outlet network. | 137 |
| 3 | Dotbooker—Online Booking for Studios & Salons—Seat Outlet | 57 | Dotbooker partner profile on Seat Outlet: online booking and appointment software for service businesses, with a link to the official site. | 139 |

### `/wingcms`

Focus keyword: **wingcms**

| # | Title | Length | Meta description | Length |
|---|---|---|---|---|
| 1 (in use) | WingCMS—Real Estate Technology—Seat Outlet | 42 | WingCMS is end to end real estate technology for agents and brokers, and part of the Seat Outlet network of partner websites and services. | 138 |
| 2 | WingCMS—Seat Outlet Network Partner | 35 | Learn what WingCMS offers real estate teams, from listing websites to client tools, and how it fits into the Seat Outlet partner network. | 137 |
| 3 | WingCMS—End to End Real Estate Tech—Seat Outlet | 47 | WingCMS partner profile on Seat Outlet: real estate technology for listings, websites and leads, with a link to the official WingCMS site. | 138 |

### `/salespeep`

Focus keyword: **salespeep**

| # | Title | Length | Meta description | Length |
|---|---|---|---|---|
| 1 (in use) | Salespeep—CRM for Sales & Marketing—Seat Outlet | 47 | Salespeep is a CRM for sales, marketing and service teams, and part of the Seat Outlet network of partner websites, tools and services. | 135 |
| 2 | Salespeep—Seat Outlet Network Partner | 37 | Learn what Salespeep offers growing businesses: contacts, pipelines, campaigns and support in one CRM, plus how it fits into our network. | 137 |
| 3 | Salespeep—Sales, Marketing & Service CRM—Seat Outlet | 52 | Salespeep partner profile on Seat Outlet: a CRM for sales, marketing and service teams, with a short overview and a link to the official site. | 142 |

### `/signs-n-more`

Focus keyword: **signs n more**

| # | Title | Length | Meta description | Length |
|---|---|---|---|---|
| 1 (in use) | Signs N More—Branding, Web & Marketing—Seat Outlet | 50 | Signs N More offers branding, signs, web design and digital marketing for businesses, and is part of the Seat Outlet network of partner sites. | 142 |
| 2 | Signs N More—Seat Outlet Network Partner | 40 | Learn what Signs N More does for local businesses, from signs and branding to websites and marketing, and how it fits into our partner network. | 143 |
| 3 | Signs N More—Signs and Digital Marketing—Seat Outlet | 52 | Signs N More partner profile on Seat Outlet: branding, signs, websites and digital marketing, with an overview and a link to the official site. | 143 |

### `/it-sprinkles`

Focus keyword: **it sprinkles**

| # | Title | Length | Meta description | Length |
|---|---|---|---|---|
| 1 (in use) | It Sprinkles—Custom Cakes in Austin—Seat Outlet | 47 | It Sprinkles makes custom cakes, cupcakes and desserts in Austin for birthdays, weddings and celebrations, and is part of the Seat Outlet network. | 146 |
| 2 | It Sprinkles—Seat Outlet Network Partner | 40 | Learn what It Sprinkles bakes for Austin celebrations, from birthday and wedding cakes to cupcakes, and how it fits into the Seat Outlet network. | 145 |
| 3 | It Sprinkles—Cakes for Every Celebration—Seat Outlet | 52 | It Sprinkles partner profile on Seat Outlet: custom cakes and desserts in Austin, Texas, with a short overview and a link to the official website. | 146 |

### `/austin-sign-masters`

Focus keyword: **austin sign masters**

| # | Title | Length | Meta description | Length |
|---|---|---|---|---|
| 1 (in use) | Austin Sign Masters—Custom Signs in Austin—Seat Outlet | 54 | Austin Sign Masters makes custom signs and printing for businesses in Austin, Texas, and is part of the Seat Outlet network of partner sites. | 141 |
| 2 | Austin Sign Masters—Seat Outlet Partner | 39 | Learn what Austin Sign Masters offers Austin businesses, from storefront signs to printed graphics, and how it fits into our partner network. | 141 |
| 3 | Austin Sign Masters—Signs & Printing, Texas—Seat Outlet | 55 | Austin Sign Masters partner profile on Seat Outlet: custom signs and printing in Austin, Texas, with an overview and a link to the official site. | 145 |

### `/viralpep`

Focus keyword: **viralpep**

| # | Title | Length | Meta description | Length |
|---|---|---|---|---|
| 1 (in use) | Viralpep—Social Media Management Tool—Seat Outlet | 49 | Viralpep is a social media management tool to create, schedule, collaborate on and track posts from one dashboard. Part of the Seat Outlet network. | 147 |
| 2 | Viralpep—Seat Outlet Network Partner | 36 | Learn how Viralpep helps teams plan, schedule and analyze social media content across platforms, and how it fits into the Seat Outlet network. | 142 |
| 3 | Viralpep—Schedule & Track Social Posts—Seat Outlet | 50 | Viralpep partner profile on Seat Outlet: a social media scheduling and analytics tool for teams, with an overview and a link to the official site. | 146 |

### `/mindshare-consulting`

Focus keyword: **mindshare consulting**

| # | Title | Length | Meta description | Length |
|---|---|---|---|---|
| 1 (in use) | Mindshare Consulting—Marketing Agency in Austin—Seat Outlet | 59 | Mindshare Consulting is a full service marketing agency in Austin for SEO, advertising, branding and web design, and the team behind this website. | 146 |
| 2 | Mindshare Consulting—SEO, Ads & Web Design—Seat Outlet | 54 | Learn how Mindshare Consulting helps Austin businesses grow with SEO, paid advertising, social media and web design, and its role at Seat Outlet. | 145 |
| 3 | Mindshare Consulting—Seat Outlet Partner | 40 | Mindshare Consulting partner profile on Seat Outlet: an Austin marketing agency for SEO, ads, branding and websites, with a link to its site. | 141 |

## Programmatic page templates

Placeholders: `{Name}` performer or team, `{Event}`, `{Venue}`, `{City, ST}`, `{Category}`, `{Y}` year, `{from}` lowest live price, `{N}` number of upcoming events. When a filled title is too long, the next candidate in the list is used (most specific first), so a result never ends in a half word.

### Artist, team or show (`/artist/...`)

Titles:

1. {Name} Tickets—{Y} Tour Dates & Prices (in use; "Schedule" for teams, "Show Dates" for theater), falls back to {Name} Tickets—{Y} Tour Dates, then {Name} Tickets—{Y}, then {Name} Tickets
2. {Name} Tickets—Compare Seats, 100% Guarantee
3. {Name} {Y} Tickets—Schedule & Seat Maps

Meta descriptions:

1. {Name} tickets from {from} for {N} upcoming concerts. Compare seats on live seat maps and buy with our 100% buyer guarantee. (in use)
2. Buy {Name} tickets for every upcoming {Name} concert. Compare seats on live seat maps and buy with our 100% buyer guarantee.
3. See every {Name} date and venue, compare prices from many sellers and buy {Name} tickets with secure checkout and a 100% guarantee.

### Event (`/event/...`)

Titles:

1. {Event} Tickets—{City, ST}, {Mon D} (in use), falls back to {Event} Tickets—{City, ST}, then {Event} Tickets—{Mon D}, then {Event} Tickets
2. {Event} Tickets—{Venue}, {Mon D}
3. {Event} Tickets—Live Seat Map & Prices

Meta descriptions:

1. {Event} tickets for {Day, Mon D, YYYY} at {Venue} in {City, ST}. Pick seats on the live seat map and buy with our 100% buyer guarantee. (in use)
2. Buy {Event} tickets at {Venue}, {City}. Compare every section and price, then check out securely with a 100% buyer guarantee.
3. {Event} on {Mon D}: see the seating chart, compare prices from many sellers and buy tickets with on time delivery guaranteed.

### Genre or league (`/hip-hop-tickets`, `/nba-tickets`, `/category/...`)

Titles:

1. {Category} Tickets—{Y} Dates & Prices (in use)
2. {Category} Tickets—Events Near You
3. {Category} Tickets—Compare Seats & Prices

Meta descriptions:

1. Compare {category} tickets for {N} upcoming events in {two top cities} and more. See dates, venues and prices, backed by our 100% guarantee. (in use, topped up to 120+ characters)
2. Find {category} tickets near you and across the US. Filter by date and price, use live seat maps and buy with a 100% buyer guarantee.
3. {Category} tickets from many sellers in one place: upcoming dates, venues and prices, plus a 100% buyer guarantee on every order.

### Venue (`/venue/...`)

Titles:

1. {Venue} Tickets—{City, ST} Events & Seats (in use), falls back to {Venue} Tickets—{City, ST}, then {Venue} Tickets
2. {Venue} Tickets—Seating Chart & Events
3. {Venue} Events—{Y} Schedule & Tickets

Meta descriptions:

1. Buy tickets to {N} upcoming events at {Venue} in {City, ST}, from {from}. Pick seats on live seat maps and buy with our 100% buyer guarantee. (in use)
2. See every upcoming event at {Venue}, compare seats and prices from many sellers and check out securely with a 100% guarantee.
3. {Venue} events and tickets: concerts, games and shows on the calendar, with live seat maps and a 100% buyer guarantee.

### City, state or country (`/city/...`, `/state/...`, `/country/...`)

Titles:

1. {Place} Event Tickets—Concerts, Sports & Shows (in use), falls back to {Place} Event Tickets—{Y} Events
2. {Place} Events—This Week & Weekend
3. Things to Do in {Place}—Event Tickets

Meta descriptions:

1. Find tickets to {N} upcoming events in {Place}, from {from}: concerts, sports and theater. Compare prices and buy with our 100% buyer guarantee. (in use)
2. What is on in {Place}: concerts, games, theater and festivals by date, with prices from many sellers and a 100% buyer guarantee.
3. Browse {Place} events by date and category, compare seats on live seat maps and buy tickets with secure checkout.

### Category in a place (`/concerts-city/...`, `/sports-state/...`, etc.)

Titles:

1. {Category} Tickets in {City, ST}—{Y} Dates & Prices (in use), falls back to {Category} Tickets in {City, ST}
2. {Category} in {City}—Tickets & Schedule
3. Best {Category} in {City}—Tickets

Meta descriptions:

1. Buy {category} tickets in {City, ST}. Browse upcoming dates, compare prices from many sellers and buy with our 100% buyer guarantee. (in use)
2. Every {category} event in {City} with tickets on sale: dates, venues and prices, plus live seat maps and secure checkout.
3. Plan your next {category} night in {City}: compare upcoming events and seats, then buy tickets backed by a 100% guarantee.

### Artist in a place (`/artist-city/...` etc.)

Titles:

1. {Name} Tickets in {City, ST}—{Y} Dates (in use), falls back to {Name} Tickets in {City, ST}
2. {Name} {City} Tickets—Dates & Prices
3. {Name} in {City}—Concert Tickets

Meta descriptions:

1. Buy {Name} concert tickets in {City, ST}. Compare prices from many sellers, pick seats on live seat maps and buy with our 100% buyer guarantee. (in use)
2. See every {Name} date in {City}, compare sections and prices and buy tickets with secure checkout and a 100% guarantee.
3. {Name} is coming to {City}: check dates and venues, compare seats and prices and buy tickets with our buyer guarantee.

### Search results (`/search`)

Titles:

1. {Query} Tickets—Search Results (in use)
2. Search Event Tickets—Concerts, Sports & Shows (in use without a query)
3. {Query} Tickets—Dates, Venues & Prices

Meta descriptions:

1. {Query} tickets: every matching event, date and venue on Seat Outlet. Compare seats and prices and buy with our 100% buyer guarantee. (in use)
2. Search event tickets by artist, team, show, venue or city. Compare seats and prices from many sellers and buy with our 100% buyer guarantee. (in use without a query)
3. Find {Query} tickets near you and across the US, sorted by best match, with live seat maps and secure checkout.

### Blog post (`/blog/...`)

Titles:

1. {Post meta title}—Seat Outlet (in use)
2. {Post title}—Seat Outlet
3. {Post title}—Ticket Buying Tips

Meta descriptions:

1. The post meta description written for each article (in use)
2. The post excerpt
3. {First sentence of the post} trimmed to 155 characters

### Blog category and pages (`/blog?category=...`, `/blog?page=2`)

Titles:

1. {Category} Guides—Ticket Buying Tips (in use)
2. {Category} Guides—Page {N}
3. Ticket Buying Tips & Event Guides—Page {N}

Meta descriptions:

1. {Category} guides from the Seat Outlet blog: practical ticket buying tips, venue advice and event picks written for fans. (in use)
2. Read every {category} guide on the Seat Outlet blog.
3. More ticket buying tips and event guides from Seat Outlet.

### A to Z letter pages (`/all-artists-and-teams?letter=B`)

Titles:

1. Artists & Teams Starting With {L}—Tickets (in use)
2. Artists & Teams Starting With {L}—Page {N}
3. {L} Artists, Teams & Shows—Tickets

Meta descriptions:

1. Artists, teams and shows starting with {L} with tickets on sale at Seat Outlet. Compare prices and buy with our 100% buyer guarantee. (in use)
2. Every artist and team starting with {L} on Seat Outlet.
3. Browse tickets for artists and teams starting with {L}.

