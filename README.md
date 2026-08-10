=== Spindle Market Research Hub ===
Tags: market data, stock market, financial dashboard, tradingview, ngx
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.8.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Financial research dashboard for NGX market data, reports, stock charts, dividends, sector performance, and macroeconomic indicators.

== Description ==

Spindle Market Research Hub provides a structured research and market-data workspace for WordPress.

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
Terms: https://www.tradingview.com/legal/terms/
Privacy: https://www.tradingview.com/policies/

= Chart.js =

Chart.js 4.4.1 is bundled locally with the plugin and is used for frontend market charts. No Chart.js JavaScript is loaded from a third-party CDN.

Chart.js project: https://www.chartjs.org/
License: https://github.com/chartjs/Chart.js/blob/master/LICENSE.md

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
5. Add the shortcode `[market_research_dashboard]` to the page where the public research dashboard should appear.

== Frequently Asked Questions ==

= What shortcode displays the research dashboard? =

Use `[market_research_dashboard]`.

= Does the plugin require ACF PRO? =

Yes. Advanced Custom Fields PRO is required because the plugin registers and uses structured fields including repeaters.

= Does the plugin automatically provide live NGX prices? =

The plugin provides fields and TradingView integrations for market-data presentation. It does not claim to be an independent exchange data feed.

= Does the plugin send personal information to third parties? =

The plugin does not intentionally collect visitor personal information for analytics or tracking. TradingView widgets may make requests to TradingView when displayed. See the External Services section above.

== Screenshots ==

1. Research Hub dashboard for managing market reports, research categories, corporate results, recent activity, and analyst resources.
2. Dividend Tracker displaying company tickers, dividend amounts, closure dates, and payment dates.
3. Macro Reports directory with report previews, downloads, key indicators, and government-policy information.
4. Market Data Search with NGX stock lookup, quick symbols, and a live TradingView price chart.
5. Market dashboard showing the NGX All Share Index, top gainers, top losers, sector performance, fixed income, and equity market turnover and size.
6. Public-facing market snapshot showing live market indicators, top gainer and loser data, and market turnover.

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
