=== AviaPages Charter Suite ===
Contributors: aviapages-charter
Tags: aviation, charter, private jet, flight calculator, empty legs, price calculator
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Production-ready private jet charter suite. Secure server-side proxy to AviaPages APIs.

== Description ==

AviaPages Charter Suite v2 transforms any WordPress site into a complete private aviation portal, built exactly per the AviaPages integration guide:

**Security Architecture**
The API key is stored server-side only and added as a `Bearer` token in PHP — never exposed to the browser. All AJAX handlers verify a WordPress nonce, sanitise inputs through a dedicated `APC_Validator` class, and wrap every API call in try/catch with clean error surfacing.

**Chained API Pattern (per integration guide)**
A single browser request to `apc_flight_and_price` triggers two AviaPages API calls in sequence:
1. `POST /v3/flight_calculator/` — routing, flight time, fuel, tech stops
2. `POST /v3/price_calculator/` — cost breakdown with your commission applied

Only the merged JSON result is returned to the client.

**Widgets (shortcodes)**
* `[aviapages_calculator]` — Combined flight time + price calculator with tab toggle
* `[aviapages_aircraft]` — Searchable, filterable jet browser
* `[aviapages_empty_legs]` — Live empty leg deals board with filters
* `[aviapages_charter_form]` — Full charter inquiry form → lead email + API submission

**Performance**
* Airport/aircraft lookups cached via WordPress transients (TTL configurable)
* Calculators and form submissions are never cached
* Autocomplete requests debounced at 280ms client-side
* Graceful error handling at every layer — API errors never surface raw to users

== Installation ==

1. Upload `aviapages-charter.zip` via **Plugins → Add New → Upload**
2. Activate the plugin
3. Navigate to **Charter Suite** in the admin sidebar
4. Paste your AviaPages API key (get it at aviapages.com/aviapages_api)
5. Set your broker commission percentage and lead email address
6. Add shortcodes to any page

== Shortcodes ==

`[aviapages_calculator title="Flight & Price Calculator" show_price="yes"]`
`[aviapages_aircraft title="Available Jets" limit="12"]`
`[aviapages_empty_legs title="Empty Leg Deals" limit="20"]`
`[aviapages_charter_form title="Request a Charter Flight"]`

== Frequently Asked Questions ==

= Where do I get my API key? =
Visit https://aviapages.com/aviapages_api/ — the key used in your settings is stored encrypted in wp_options and never transmitted to the browser.

= Is the API key safe? =
Yes. The key is added as a `Bearer` header in PHP (server-side) only. Browser requests go to `admin-ajax.php`, which passes through `APC_API_Proxy`. There is no way for a visitor to discover your key.

= Can I set my own commission? =
Yes — set your percentage in Charter Suite → Settings. It is passed directly to the `/v3/price_calculator/` endpoint so the final price shown already includes your margin.

= How does caching work? =
GET requests (airports, aircraft lists, aircraft classes) are cached via `set_transient()`. The TTL defaults to 300 seconds and is configurable in settings. POST requests (calculators, form submissions) are never cached.

== Changelog ==

= 2.0.0 =
* Complete rebuild following official AviaPages integration guide
* Server-side API proxy — key never exposed to browser
* `APC_Validator` class for all input sanitisation
* Chained Flight → Price calculator (single browser request, two API calls server-side)
* `Bearer` token authentication (per guide)
* Configurable transient cache TTL
* Clean error handling with user-friendly messages
* Refined editorial design system

= 1.0.0 =
* Initial release
