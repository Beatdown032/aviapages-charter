=== AviaPages Charter Suite ===
Contributors: aviapages
Tags: aviation, charter, private jet, flight calculator, empty legs
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0-or-later

Full-featured private jet charter suite powered by AviaPages API.

== Description ==

AviaPages Charter Suite turns any WordPress site into a professional private aviation portal. Using the AviaPages API, it provides:

* **Flight Time & Route Calculator** — Real-time flight time, distance, fuel, and wind impact for 500+ aircraft types.
* **Charter Price Estimator** — Accurate pricing with your own commission percentage built in.
* **Aircraft Search** — Browse and filter available charter jets by class and passenger count.
* **Empty Leg Deals Board** — Live feed of empty leg opportunities with search filters.
* **Charter Request Form** — Full inquiry form that captures leads and submits requests to AviaPages operators.

== Installation ==

1. Upload the `aviapages-charter` folder to `/wp-content/plugins/`
2. Activate via **Plugins → Installed Plugins**
3. Go to **Charter Suite** in the WordPress admin menu
4. Enter your AviaPages API key
5. Configure your commission percentage and lead email
6. Add shortcodes to any page or post

== Shortcodes ==

| Shortcode | Description |
|-----------|-------------|
| `[aviapages_flight_calculator]` | Flight time & route calculator |
| `[aviapages_price_calculator]`  | Price estimator with commission |
| `[aviapages_aircraft_search]`   | Filterable jet browser |
| `[aviapages_empty_legs]`        | Live empty legs board |
| `[aviapages_charter_request]`   | Charter inquiry form |

All shortcodes accept a `title` attribute to override the heading.

== Frequently Asked Questions ==

= Where do I get an API key? =
Visit https://aviapages.com/aviapages_api/ to sign up and obtain a key.

= Is my API key stored securely? =
Yes, it is stored in the WordPress options table and never exposed to front-end users.

= Can I place multiple shortcodes on one page? =
Absolutely. All shortcodes work independently and can be combined.

== Changelog ==

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.0 =
Initial release.
