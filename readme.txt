=== Spindle Market Research Hub ===
Tags: market data, stock market, financial dashboard, tradingview, ngx
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.8.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==

Market research dashboard for NGX market data, reports, stock charts, dividends, sector performance, and macroeconomic indicators.

Features include:

* Six research content areas: daily market, equity, sector, macroeconomic, corporate results, and dividends.
* Frontend research dashboard with tabbed navigation.
* NGX All Share Index chart with 1D, 5D, 1M, and YTD views.
* Sector performance chart.
* Top gainers and top losers with price and percentage change.
* Market capitalization, turnover volume, and turnover value, with snapshot metrics shown only when their corresponding values are supplied.
* Fixed-income instruments.
* Foreign exchange rates.
* Macroeconomic indicators.
* Daily market summaries and downloadable price-list/report files.
* Market data search and TradingView-powered market widgets.
* ACF-powered structured data entry and CSV-assisted market data management.

* The plugin uses Advanced Custom Fields PRO for its structured content fields.
* The plugin displays an admin notice on its Research Hub and research-entry screens when ACF PRO is not installed and active.
* Administrator-controlled Google Fonts dropdowns for a consistent frontend and plugin-admin typography system, with Josefin Sans for body/text and Poppins for headings by default, plus separate admin-area toggles for each selected font. Snapshot labels intentionally follow the body/text family so the default snapshot typography remains Josefin Sans.
* The market ticker speed is administrator-controlled and remains independent of the number of gainers or losers supplied.
* Frontend snapshot/ticker styles are loaded early when their shortcodes are present to avoid an unstyled-content flash.

== External Services ==

This plugin can connect to third-party services to provide market visualization, embedded market information, and administrator-selected typography.

= Google Fonts =

The typography settings provide administrator-selectable Google Fonts dropdowns for body/text and headings. The selected font stylesheet is requested from Google's Fonts service when the plugin's frontend or plugin-admin screens are displayed. The selected typography is applied consistently to the plugin frontend and plugin administration screens.

Service: https://fonts.google.com/
Terms: https://developers.google.com/fonts/faq/privacy
Privacy information: https://policies.google.com/privacy

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
* Advanced Custom Fields PRO installed and activated. The free ACF plugin alone is not sufficient because the plugin uses ACF PRO Repeater fields.

== Installation ==

1. Install and activate Advanced Custom Fields PRO.
2. Upload the Spindle Market Research Hub plugin to `/wp-content/plugins/`.
3. Activate the plugin from Plugins in WordPress.
4. Create and populate the supported research post types from the Research Hub menu.
5. Add one of the plugin's shortcodes to the page, post, or widget area where you want the research content to appear.

== Shortcodes ==

The plugin provides three frontend shortcodes:

`[market_research_dashboard]`
Displays the Research Hub dashboard, including the dashboard tabs, NGX All Share Index chart, sector performance, market data, reports, price lists, market summaries, corporate results, dividends, and market-data search.

`[market_research_home_snapshot]`
Displays a compact four-column market snapshot with the NGX All-Share Index, top gainer, top loser, and a MARKET TURNOVER & SIZE panel. Volume, Value, and Cap are displayed on one line with their corresponding values underneath; any metric that has no supplied value is omitted.

`[market_research_price_ticker]`
Displays the market price ticker with supported NGX market movement information.

Use the shortcodes independently. You do not need to place all three on the same page.

== Frequently Asked Questions ==

= What shortcodes are available? =

The plugin provides three shortcodes:

* `[market_research_dashboard]` — Research Hub dashboard.
* `[market_research_home_snapshot]` — Compact market snapshot.
* `[market_research_price_ticker]` — Market price ticker.

= How do I display the Research Hub dashboard? =

Add `[market_research_dashboard]` to the page or post where you want the full dashboard to appear.

= How do I display the market snapshot? =

Add `[market_research_home_snapshot]` to the page, post, or widget area where you want the compact market snapshot to appear.

= How do I display the market price ticker? =

Add `[market_research_price_ticker]` to the page, post, or widget area where you want the market ticker to appear.

= Can I control the ticker speed? =

Yes. Go to Research Hub → Settings and set the Ticker Speed in pixels per second. The ticker repeats available market items as necessary, so the setting remains effective whether there are many or only a few gainers and losers.

= Can I change the plugin fonts? =

Yes. Research Hub → Settings provides Google Fonts dropdowns for Body / Text Font and Heading Font. The defaults are Josefin Sans for Body / Text and Poppins for the general Heading Font. Each font has its own option to also apply that selected family to the plugin administration screens.

The public market snapshot intentionally keeps its snapshot labels and values on the Body / Text font so the snapshot remains visually consistent with the requested Josefin Sans default, independently of the general Heading Font selection.

= What happens if ACF PRO is not installed or active? =

The plugin will remain safely inactive for its ACF-powered data-entry and frontend data features, and it will display an admin notice on the Research Hub and research-entry screens explaining that Advanced Custom Fields PRO is required. Install and activate ACF PRO, then return to the Research Hub.

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

= 1.8.1 =
* Restored all report inner-tab, report action-button, ticker, and market-mover presentation rules while preserving existing functionality.
* Centered the complete four-column market snapshot group on larger screens while preserving responsive alignment and internal left alignment.
* Final correction pass: removed the obsolete TradingView explanatory line from Market Data Search.
* Final correction pass: ensured macro inner-tab active underline/state stays synchronized with the selected content pane.
* Final correction pass: standardized the four snapshot section headings to the same compact Josefin Sans size and tightened the spacing between the four sections.
* Final correction pass: displayed Volume | Value | Cap on one line with the corresponding values directly underneath; missing metrics are omitted without leaving stray separators.
* Final correction pass: strengthened ticker initialization and full-width left anchoring so the ticker controller can operate independently of the host theme's centering/flex rules.
* Added a clear admin dependency notice when Advanced Custom Fields PRO is not installed and active.
* Guarded ACF field registration and ACF-powered frontend/admin data access behind the ACF PRO dependency check.
* Added an administrator-controlled ticker speed setting that remains independent of the number of gainers or losers.
* Improved ticker initialization and responsive continuous scrolling, including repeated content for short market-data lists.
* Refined the four-column market snapshot with a compact MARKET TURNOVER & SIZE panel for Volume, Value, and conditional Cap.
* Preserved the homepage and inner-page timestamp positioning while keeping the snapshot responsive.
* Improved spacing for Change (%) values in the top gainers and top losers tables.
* Removed the dynamic presentation-style manipulation from the public JavaScript controller and moved the affected state presentation into scoped CSS classes.
* Fixed macro report inner-tab active states so the selected tab underline and visible content remain synchronized.
* Hardened the outer Research Hub tab navigation so each dashboard instance controls only its own panes.
* Corrected ticker left anchoring so host-theme centering/flex rules cannot hide the scrolling market content.
* Preserved the ticker CSS fallback until JavaScript successfully measures the ticker, including short or uneven gainers/losers lists.
* Standardized snapshot metric rows to Volume / Value / Cap with conditional omission of missing metrics and restored the MARKET TURNOVER & SIZE heading on homepage and inner-page snapshots.
* Updated the requirements documentation to clarify that ACF PRO, not ACF Free alone, is required.
* Reduced spacing between the four market snapshot sections and slightly reduced Top Gainer / Top Loser snapshot values.
* Standardized View Report and Download Report typography and kept both action labels on a single line.
* Removed the duplicate-looking divider around Market Turnover & Size and standardized the intended divider before that section.
* Reworked Volume, Value, and Cap as equal-width metric cells with responsive spacing to prevent overlap on inner pages.
* Moved Ticker Speed into its own Ticker Settings section in Research Hub Settings; ticker behavior and saved values remain unchanged.


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

= 1.8.1 =
Refined ticker reliability, snapshot presentation, responsive behavior, typography controls, and ACF PRO dependency handling. Review the requirements and external-services documentation before deployment.

== Short Description ==

Market research dashboard for NGX market data, reports, stock charts, dividends, sector performance, and macroeconomic indicators.
