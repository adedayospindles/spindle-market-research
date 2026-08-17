=== Spindle Market Research Hub ===
Tags: market data, stock market, financial dashboard, tradingview, ngx
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.8.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Market research dashboard for NGX market data, reports, stock charts, dividends, sector performance, and macroeconomic indicators.
== Description ==

Spindle Market Research Hub provides a structured research and market-data workspace for WordPress. A modular financial research dashboard for WordPress, built for publishing market reports, equity research, sector reports, macroeconomic reports, corporate results, dividend records, and market summary data.

Features include:

* Six research content areas: daily market, equity, sector, macroeconomic, corporate results, and dividends.
* Frontend research dashboard with tabbed navigation.
* NGX All Share Index chart with 1D, 5D, 1M, and YTD views.
* Sector performance chart.
* Top gainers and top losers with price and percentage change.
* Market capitalization, turnover volume, and turnover value.
* Fixed-income instruments.
* Foreign exchange rates.
* Macroeconomic indicators.
* Daily market summaries and downloadable price-list/report files.
* Market data search and TradingView-powered market widgets.
* ACF-powered structured data entry and CSV-assisted market data management.

The plugin uses Advanced Custom Fields PRO for its structured content fields.

== External Services ==

This plugin can connect to third-party services to provide market visualization and embedded market information.

= TradingView =

TradingView is used for embedded market widgets and market-data visualizations where enabled by the plugin. TradingView may receive the visitor's browser request when a TradingView widget is displayed.

Service: https://www.tradingview.com/
Terms of use: https://www.tradingview.com/policies/
Privacy: https://www.tradingview.com/privacy-policy/ 

= Chart.js =

Chart.js 4.5.1 is bundled locally with the plugin and is used by the frontend charting layer.

Chart.js project: https://www.chartjs.org/
License: https://github.com/chartjs/Chart.js/blob/master/LICENSE.md

For WordPress.org directory distribution, non-service JavaScript dependencies should be bundled with the plugin rather than loaded from a third-party CDN.

= Source Code =

Source code and development history: https://github.com/adedayospindles/spindle-market-research

== Requirements ==

* WordPress 6.0 or later.
* PHP 7.4 or later.
* Advanced Custom Fields PRO installed and activated.

== Installation ==

1. Install and activate Advanced Custom Fields PRO.
2. Upload the Spindle Market Research Hub plugin to `/wp-content/plugins/`.
3. Activate the plugin from Plugins in WordPress.
4. Create and populate the supported research post types from the Research Hub menu.
5. Add one of the plugin's shortcodes to the page, post, or widget area where you want the research content to appear.

== Shortcodes ==

The plugin provides three frontend shortcodes:

`[market_research_dashboard]`
Displays the full Market Research dashboard, including the dashboard tabs, NGX All Share Index chart, sector performance, market data, reports, price lists, market summaries, corporate results, dividends, and market-data search.

`[market_research_home_snapshot]`
Displays a compact market snapshot with key NGX market indicators, top gainer, top loser, and market turnover information.

`[market_research_price_ticker]`
Displays the market price ticker with supported NGX market movement information.

Use the shortcodes independently. You do not need to place all three on the same page.

== Frequently Asked Questions ==

= What shortcodes are available? =

The plugin provides three shortcodes:

* `[market_research_dashboard]` — Full Market Research dashboard.
* `[market_research_home_snapshot]` — Compact market snapshot.
* `[market_research_price_ticker]` — Market price ticker.

= How do I display the full Market Research dashboard? =

Add `[market_research_dashboard]` to the page or post where you want the full dashboard to appear.

= How do I display the market snapshot? =

Add `[market_research_home_snapshot]` to the page, post, or widget area where you want the compact market snapshot to appear.

= How do I display the market price ticker? =

Add `[market_research_price_ticker]` to the page, post, or widget area where you want the market ticker to appear.

= Does the plugin require ACF PRO? =

Yes. Advanced Custom Fields PRO is required because the plugin registers and uses structured fields, including repeater fields, for the research data.

= Does the plugin provide market charts? =

Yes. The dashboard includes an NGX All Share Index chart with 1D, 5D, 1M, and YTD views, as well as sector performance visualization. Chart.js is bundled locally with the plugin.

= Does the plugin integrate with TradingView? =

Yes. Supported market-data search and visualizations use TradingView integrations. TradingView may make requests from the visitor's browser when its widgets or charts are displayed. See the External Services section for details.

= Can I use the three shortcodes separately? =

Yes. Each shortcode renders a different frontend component and can be placed independently on pages, posts, or other WordPress areas that support shortcodes.

== Screenshots ==

1. Research Hub dashboard for managing market reports, research categories, corporate results, recent activity, and analyst resources.
2. Dividend Tracker displaying company tickers, dividend amounts, closure dates, and payment dates.
3. Macro Reports directory with report previews, downloads, key indicators, and government-policy information.
4. Market Data Search with NGX stock lookup, quick symbols, and a live TradingView price chart.
5. Market dashboard showing the NGX All Share Index, top gainers, top losers, sector performance, fixed income, turnover, and market capitalization.
6. Public-facing market snapshot showing live market indicators, gainers, losers, turnover, and related NGX information.

== Changelog ==

= 1.8.0 =
* Refined the modular research dashboard and tabbed frontend navigation.
* Added the NGX All Share Index chart with 1D, 5D, 1M, and YTD views.
* Added sector performance visualization.
* Added market summary modules for turnover, market capitalization, macroeconomic indicators, FX, fixed income, and recent dividends.
* Added price columns to top gainers and top losers.
* Separated frontend and admin JavaScript/CSS responsibilities.
* Added the `market_research_dashboard` shortcode.
* Improved compatibility and packaging for WordPress.org distribution.

For earlier release history, see `changelog.txt`.

== Upgrade Notice ==

= 1.8.0 =
Major dashboard and architecture refinement. Review the requirements and external-services documentation before deployment.
