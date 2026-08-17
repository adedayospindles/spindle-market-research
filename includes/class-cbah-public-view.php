<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


class CBAH_Public_View {

    public function __construct() {
        // Enqueue the component stylesheet in wp_head whenever a supported shortcode
        // exists on the current page. This prevents the unstyled-content flash that
        // can occur when assets are first enqueued from shortcode rendering.
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_styles_early' ), 20 );
    }

    /**
     * Enqueue only the scoped component stylesheet early enough to avoid FOUC.
     * Google Fonts and scripts remain conditional on actual shortcode rendering.
     *
     * @return void
     */
    public function enqueue_public_styles_early() {
        if ( ! $this->page_contains_public_shortcode() ) {
            return;
        }

        wp_enqueue_style(
            'cbah-frontend-style',
            CBAH_PLUGIN_URL . 'public/css/cbah-style.css',
            array(),
            CBAH_VERSION
        );

        // Load the selected Google Fonts and CSS variables at the same early stage
        // so typography is also applied before the page content first paints.
        CBAH_Settings::enqueue_frontend_fonts();
        CBAH_Settings::add_frontend_typography_variables( 'cbah-frontend-style' );

        $post = get_queried_object();
        $content = ( $post instanceof WP_Post ) ? (string) $post->post_content : '';

        // Load the ticker controller early when the ticker shortcode is present.
        // The controller itself is scoped to .cbah-price-ticker, so unrelated
        // site JavaScript is not affected.
        $ticker_present = false !== strpos( $content, '[market_research_price_ticker' );
        if ( $post instanceof WP_Post ) {
            $elementor_data = (string) get_post_meta( $post->ID, '_elementor_data', true );
            $ticker_present = $ticker_present || false !== strpos( $elementor_data, '[market_research_price_ticker' );
        }
        if ( $ticker_present ) {
            wp_enqueue_script(
                'cbah-ticker',
                CBAH_PLUGIN_URL . 'public/js/cbah-ticker.js',
                array(),
                CBAH_VERSION,
                true
            );
        }
    }

    private function page_contains_public_shortcode() {
        if ( is_admin() ) {
            return false;
        }

        $shortcodes = array(
            '[market_research_dashboard',
            '[market_research_home_snapshot',
            '[market_research_price_ticker',
        );

        $post = get_queried_object();
        $content = ( $post instanceof WP_Post ) ? (string) $post->post_content : '';
        foreach ( $shortcodes as $shortcode ) {
            if ( false !== strpos( $content, $shortcode ) ) {
                return true;
            }
        }

        // Elementor stores shortcode widgets in serialized JSON. Only inspect the
        // current post's builder data; no global frontend assets are loaded.
        if ( $post instanceof WP_Post ) {
            $elementor_data = (string) get_post_meta( $post->ID, '_elementor_data', true );
            foreach ( $shortcodes as $shortcode ) {
                if ( false !== strpos( $elementor_data, $shortcode ) ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Enqueue the public styles used by the market snapshot and ticker.
     * These shortcodes can be used independently of the full dashboard.
     *
     * @return void
     */
    private function enqueue_public_styles() {
        wp_enqueue_style(
            'cbah-frontend-style',
            CBAH_PLUGIN_URL . 'public/css/cbah-style.css',
            array(),
            CBAH_VERSION
        );

        CBAH_Settings::enqueue_frontend_fonts();
        CBAH_Settings::add_frontend_typography_variables( 'cbah-frontend-style' );
    }

    /**
     * Enqueue the complete dashboard assets.
     *
     * @return void
     */
    public function enqueue_assets() {
        // Chart.js is bundled locally so the plugin does not depend on a third-party CDN.
        wp_enqueue_script(
            'cbah-chartjs',
            CBAH_PLUGIN_URL . 'vendor/chartjs/chart.umd.min.js',
            array(),
            '4.5.1',
            true
        );

        wp_enqueue_script(
            'cbah-tradingview',
            'https://s3.tradingview.com/tv.js',
            array(),
            '1.0.0',
            true
        );

        wp_enqueue_script(
            'cbah-public',
            CBAH_PLUGIN_URL . 'public/js/cbah-public.js',
            array( 'cbah-chartjs', 'cbah-tradingview' ),
            CBAH_VERSION,
            true
        );

        wp_enqueue_script(
            'cbah-ticker',
            CBAH_PLUGIN_URL . 'public/js/cbah-ticker.js',
            array(),
            CBAH_VERSION,
            true
        );

        $this->enqueue_public_styles();

        wp_enqueue_style(
            'cbah-dashboard-style',
            CBAH_PLUGIN_URL . 'public/css/cbah-dashboard.css',
            array( 'cbah-frontend-style' ),
            CBAH_VERSION
        );
    }

    private function parse_history($string) {
        if(empty($string)) return [];
        return array_map('floatval', array_map('trim', explode(',', $string)));
    }
    
    // Smart auto-formatting for turnover volumes and values
    private function format_market_number($val) {
        // Strip out anything that isn't a number or decimal point
        $clean = preg_replace('/[^0-9.]/', '', $val);
        if (!is_numeric($clean)) return $val; // Return as-is if it contains custom text
        
        $num = (float)$clean;
        
        if ($num >= 1000000000000) {
            return number_format($num / 1000000000000, 2) . ' T';
        } elseif ($num >= 1000000000) {
            return number_format($num / 1000000000, 2) . ' B';
        } elseif ($num >= 1000000) {
            return number_format($num / 1000000, 2) . ' M';
        } elseif ($num >= 1000) {
            return number_format($num / 1000, 2) . ' K';
        }
        
        return number_format($num, 2);
    }

    private function get_latest_analytics_matrix() {
        $market = get_posts( array( 'post_type' => 'cbah_market_report', 'posts_per_page' => 1 ) );
        $sector = get_posts( array( 'post_type' => 'cbah_sector', 'posts_per_page' => 1 ) );
        $macro  = get_posts( array( 'post_type' => 'cbah_macro', 'posts_per_page' => 1 ) );
        
        $m_id = !empty($market) ? $market[0]->ID : 0;
        $s_id = !empty($sector) ? $sector[0]->ID : 0;
        $mac_id = !empty($macro) ? $macro[0]->ID : 0;

        $open  = (float) get_field('ngx_open', $m_id) ?: 0;
        $close = (float) get_field('ngx_close', $m_id) ?: 0;
        
        // Market Capitalization
        $m_cap = get_field('market_cap', $m_id);
        if ($m_cap === '' || $m_cap === null) {
            $m_cap = '';
        }

        $vol   = get_field('turnover_volume', $m_id) ?: '0';
        $val   = get_field('turnover_value', $m_id) ?: '0';
        
        $h_1d = $this->parse_history(get_field('hist_1d', $m_id));
        if(empty($h_1d)) $h_1d = [$open, $close];

        $gainers   = get_field('market_gainers', $m_id) ?: [];
        $losers    = get_field('market_losers', $m_id) ?: [];
        $gainers   = array_slice($gainers, 0, 7);
        $losers    = array_slice($losers, 0, 7);

        $forex     = get_field('forex_rates', $m_id) ?: [];
        $fixed     = get_field('fixed_income', $m_id) ?: [];

        // Expanded Macros (with FX Repeater)
        $inf      = get_field('macro_inflation', $mac_id) ?: '0';
        $int_r    = get_field('macro_interest_rate', $mac_id) ?: '0';
        $crude    = get_field('macro_crude_oil', $mac_id) ?: '0';
        $macro_fx = get_field('macro_forex_rates', $mac_id) ?: []; 

        // 5 NGX Sectors
        $s_bank = (float) get_field('performance_banking', $s_id) ?: 0;
        $s_ins  = (float) get_field('performance_insurance', $s_id) ?: 0;
        $s_ind  = (float) get_field('performance_industrial', $s_id) ?: 0;
        $s_oil  = (float) get_field('performance_oil_gas', $s_id) ?: 0;
        $s_fmcg = (float) get_field('performance_consumer_goods', $s_id) ?: 0;

        // Implementation #1: Sector Details Array (Repeater)
        $sector_reports = get_field('sector_breakdown_reports', $s_id) ?: [];
        
        // Fetch the global WP Content brief for the Sector post
        $sector_brief = !empty($s_id) ? get_post_field('post_content', $s_id) : 'Select a sector to view the detailed analysis and outlook.';

        return array(
            'open' => $open, 'close' => $close, 'm_cap' => $m_cap, 'vol' => $vol, 'val' => $val,
            'hist_1d' => $h_1d, 
            'hist_5d' => $this->parse_history(get_field('hist_5d', $m_id)),
            'hist_1m' => $this->parse_history(get_field('hist_1m', $m_id)),
            'hist_ytd'=> $this->parse_history(get_field('hist_ytd', $m_id)),
            'gainers' => $gainers, 'losers' => $losers, 'forex' => $forex, 'fixed' => $fixed,
            
            // 5 Sectors
            's_banking' => $s_banking = $s_bank, 's_insurance' => $s_ins, 's_industrial' => $s_ind, 
            's_oil' => $s_oil, 's_fmcg' => $s_fmcg,
            'sector_reports' => $sector_reports,
            'sector_brief' => $sector_brief,
            
            // Expanded Macros
            'inf' => $inf, 'int_r' => $int_r, 'macro_fx' => $macro_fx, 'crude' => $crude,
            
            'report_date' => get_the_date('M d, Y • g:i a', $m_id)
        );
    }
    
    public function render_dashboard() {
        if ( ! cbah_is_acf_pro_active() || ! function_exists( 'get_field' ) ) {
            return '<p>' . esc_html__( 'Spindle Market Research Hub requires Advanced Custom Fields PRO to display this dashboard.', 'spindle-market-research' ) . '</p>';
        }

        // Enqueue dashboard assets only when this shortcode is actually rendered.
        // This is required for the separated frontend controller to run and keeps
        // the plugin from loading dashboard assets site-wide.
        $this->enqueue_assets();

        ob_start();
        $data = $this->get_latest_analytics_matrix();

        // Pass dynamic data to the separated frontend controller without embedding PHP in JavaScript.
        wp_add_inline_script(
            'cbah-public',
            'window.CBAH_PUBLIC_DATA = ' . wp_json_encode(
                array(
                    'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
                    'chartData' => array(
                        'hist' => array(
                            '1d'  => $data['hist_1d'],
                            '5d'  => $data['hist_5d'],
                            '1m'  => $data['hist_1m'],
                            'ytd' => $data['hist_ytd'],
                        ),
                        'sectors' => array(
                            'banking'        => (float) $data['s_banking'],
                            'insurance'      => (float) $data['s_insurance'],
                            'industrial'     => (float) $data['s_industrial'],
                            'oil'            => (float) $data['s_oil'],
                            'consumer_goods' => (float) $data['s_fmcg'],
                        ),
                        'turnover' => array(
                            'volume' => (float) $data['vol'],
                            'value'  => (float) $data['val'],
                        ),
                    ),
                ),
                JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
            ) . ';',
            'before'
        );
        ?>


        <div class="cbah-app-wrapper">
            <aside class="cbah-sidebar">
                <div class="cbah-nav-item active" data-tab="tab-dashboard"><span class="dashicons dashicons-dashboard"></span> Dashboard</div>
                <div class="cbah-nav-item" data-tab="tab-reports"><span class="dashicons dashicons-media-document"></span> Equity Reports</div>
                <div class="cbah-nav-item" data-tab="tab-sector-reports"><span class="dashicons dashicons-chart-pie"></span> Sector Reports</div>
                <div class="cbah-nav-item" data-tab="tab-macro-reports"><span class="dashicons dashicons-chart-area"></span> Macro Reports</div>
                <div class="cbah-nav-item" data-tab="tab-pricelists"><span class="dashicons dashicons-money-alt"></span> Price Lists</div>
                <div class="cbah-nav-item" data-tab="tab-market"><span class="dashicons dashicons-chart-line"></span> Market Data Search</div>
                <div class="cbah-nav-item" data-tab="tab-summaries"><span class="dashicons dashicons-list-view"></span> Market Summaries</div>
                <div class="cbah-nav-item" data-tab="tab-corporate"><span class="dashicons dashicons-portfolio"></span> Corporate Results</div>
                <div class="cbah-nav-item" data-tab="tab-dividend"><span class="dashicons dashicons-update-alt"></span> Dividend Tracker</div>
            </aside>

            <main class="cbah-main-content">
                
                <div class="cbah-tab-pane active" id="tab-dashboard">
                    
                    <div class="cbah-inline-08b70455">
                        <span class="dashicons dashicons-calendar-alt cbah-inline-405a231b"></span>
                        Market Data as of: <strong class="cbah-inline-e4ea4584"><?php echo esc_html($data['report_date']); ?></strong>
                    </div>

                    <!-- ROW 1: Market Core -->
                    <div class="cbah-grid-3">
                        <div class="cbah-card">
                            <div class="cbah-card-header">NGX ALL SHARE INDEX</div>
                            <div class="cbah-card-body">
                                <div class="cbah-chart-filters" id="cbah-history-pills">
                                    <span data-period="1d">1D</span><span class="active" data-period="5d">5D</span><span data-period="1m">1M</span><span data-period="ytd">YTD</span>
                                </div>
                                <div class="cbah-inline-6584bac0"><canvas id="cbahMarketChart"></canvas></div>
                            </div>
                        </div>

                        <div class="cbah-card">
                            <div class="cbah-card-header">TOP GAINERS</div>
                            <div class="cbah-card-body cbah-no-pad">
                                <table class="cbah-data-table cbah-market-movers-table cbah-table-hover">
                                    <thead><tr>
                                        <th>Symbol</th>
                                        <th class="cbah-table-price">Price (₦)</th>
                                        <th class="cbah-table-value">Change(%)</th>
                                    </tr></thead>
                                    <tbody id="cbah_table_gainers">
                                        <?php if($data['gainers']): foreach($data['gainers'] as $g): 
                                            $clean_val = str_replace(array('+', '-', '%', ' '), '', $g['percentage']);
                                        ?>
                                            <tr>
                                                <td><strong><?php echo esc_html($g['ticker']); ?></strong></td>
                                                <td class="cbah-table-price-value"><?php echo esc_html($g['price']); ?></td>
                                                <td class="cbah-table-value cbah-txt-green">+<?php echo esc_html($clean_val); ?>%</td>
                                            </tr>
                                        <?php endforeach; else: echo '<tr><td colspan="3" class="cbah-empty">No Data</td></tr>'; endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="cbah-card">
                            <div class="cbah-card-header">TOP LOSERS</div>
                            <div class="cbah-card-body cbah-no-pad">
                                <table class="cbah-data-table cbah-market-movers-table cbah-table-hover">
                                    <thead><tr>
                                        <th>Symbol</th>
                                        <th class="cbah-table-price">Price (₦)</th>
                                        <th class="cbah-table-value">Change(%)</th>
                                    </tr></thead>
                                    <tbody id="cbah_table_losers">
                                        <?php if($data['losers']): foreach($data['losers'] as $l): 
                                            $clean_val = str_replace(array('+', '-', '%', ' '), '', $l['percentage']);
                                        ?>
                                            <tr>
                                                <td><strong><?php echo esc_html($l['ticker']); ?></strong></td>
                                                <td class="cbah-table-price-value"><?php echo !empty($l['price']) ? esc_html($l['price']) : '-'; ?></td>
                                                <td class="cbah-table-value cbah-txt-red">-<?php echo esc_html($clean_val); ?>%</td>
                                            </tr>
                                        <?php endforeach; else: echo '<tr><td colspan="3" class="cbah-empty">No Data</td></tr>'; endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- ROW 2: Sectors & Turnover -->
                    <div class="cbah-grid-3">
                        <div class="cbah-card">
                            <div class="cbah-card-header">SECTOR PERFORMANCE</div>
                            <div class="cbah-card-body"><div class="cbah-inline-298bc5cf"><canvas id="cbahSectorChart"></canvas></div></div>
                        </div>

                        <div class="cbah-card">
                            <div class="cbah-card-header cbah-inline-41f999b3">
                                <span class="cbah-fixed-income-label">FIXED INCOME</span>
                                <select id="cbah-fixed-filter" class="cbah-dropdown-minimal">
                                    <option value="all">Instruments</option><option value="Bonds">Bonds</option><option value="Bills">Bills</option>
                                </select>
                            </div>
                            <div class="cbah-card-body cbah-no-pad cbah-scroll-y">
                                <table class="cbah-data-table cbah-table-hover">
                                    <thead><tr><th>Tenor</th><th class="cbah-inline-a527bac1">Action</th></tr></thead>
                                    <tbody id="cbah_table_fixed">
                                        <?php if($data['fixed']): foreach($data['fixed'] as $f): ?>
                                            <tr data-type="<?php echo esc_attr($f['fi_type']); ?>">
                                                <td><?php echo esc_html($f['tenor']); ?></td>
                                                <td class="cbah-inline-a527bac1"><a href="<?php echo esc_url($f['action_link']); ?>" target="_blank" class="cbah-btn-view">View</a></td>
                                            </tr>
                                        <?php endforeach; else: echo '<tr><td colspan="2" class="cbah-empty">No Data</td></tr>'; endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- MARKET TURNOVER & SIZE CARD -->
                        <div class="cbah-card">
                            <div class="cbah-card-header">MARKET TURNOVER & SIZE</div>
                            <div class="cbah-card-body cbah-no-pad">
                                <table class="cbah-data-table cbah-inline-76084ee4">
                                    <tbody>
                                        <?php if ( '' !== trim( (string) $data['m_cap'] ) ) : ?>
                                            <tr>
                                                <td class="cbah-inline-5aa5719b">Market Capitalization</td>
                                                <td class="cbah-inline-d722b363">
                                                    <strong class="cbah-inline-720d28d2">
                                                        <?php
                                                        $raw_mcap = trim( (string) $data['m_cap'] );
                                                        if ( is_numeric( str_replace( ',', '', $raw_mcap ) ) ) {
                                                            echo esc_html( '₦' . $this->format_market_number( $raw_mcap ) );
                                                        } else {
                                                            echo esc_html( ( false === stripos( $raw_mcap, '₦' ) && false === stripos( $raw_mcap, '$' ) ) ? '₦' . $raw_mcap : $raw_mcap );
                                                        }
                                                        ?>
                                                    </strong>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                        <tr>
                                            <td class="cbah-inline-5aa5719b">Turnover Volume</td>
                                            <td class="cbah-inline-d722b363">
                                                <strong class="cbah-inline-720d28d2"><?php echo esc_html($this->format_market_number($data['vol'])); ?></strong>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="cbah-inline-0da16adb">Turnover Value</td>
                                            <td class="cbah-inline-24233262">
                                                <strong class="cbah-inline-720d28d2">₦<?php echo esc_html($this->format_market_number($data['val'])); ?></strong>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <!--<div class="cbah-inline-62de3eb9"><canvas id="cbahTurnoverChart"></canvas></div>-->
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- ROW 3: Economy & Actions -->
                    <div class="cbah-grid-3">
                        <div class="cbah-card">
                            <div class="cbah-card-header">MACROECONOMIC INDICATORS</div>
                            <div class="cbah-card-body cbah-no-pad">
                                <table class="cbah-data-table cbah-table-hover">
                                    <tbody>
                                        <tr><td>Headline Inflation</td><td class="cbah-inline-a527bac1"><strong><?php echo esc_html($data['inf']); ?>%</strong></td></tr>
                                        <tr><td>Interest Rate (MPR)</td><td class="cbah-inline-a527bac1"><strong><?php echo esc_html($data['int_r']); ?>%</strong></td></tr>
                                        <tr><td>Brent Crude Oil</td><td class="cbah-inline-a527bac1"><strong>$<?php echo esc_html($data['crude']); ?>/bbl</strong></td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="cbah-card">
                            <div class="cbah-card-header">FOREIGN EXCHANGE RATES</div>
                            <div class="cbah-card-body cbah-no-pad cbah-scroll-y cbah-inline-e36a561e">
                                <table class="cbah-data-table cbah-table-hover">
                                    <thead><tr><th>Currency Pair</th><th class="cbah-inline-a527bac1">Rate (₦)</th></tr></thead>
                                    <tbody>
                                        <?php if($data['macro_fx']): foreach($data['macro_fx'] as $fx): ?>
                                            <tr>
                                                <td><strong><?php echo esc_html($fx['pair']); ?></strong></td>
                                                <td class="cbah-inline-a527bac1">₦<?php echo esc_html($fx['rate']); ?></td>
                                            </tr>
                                        <?php endforeach; else: echo '<tr><td colspan="2" class="cbah-empty">No FX Data</td></tr>'; endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="cbah-card">
                            <div class="cbah-card-header">RECENT DIVIDENDS</div>
                            <div class="cbah-card-body cbah-no-pad cbah-scroll-y cbah-inline-e36a561e">
                                <table class="cbah-data-table cbah-table-hover">
                                    <thead><tr><th>Symbol</th><th class="cbah-inline-a527bac1">Amount (₦)</th></tr></thead>
                                    <tbody>
                                    <?php
                                    $snap_divs = get_posts( array( 'post_type' => 'cbah_dividend', 'posts_per_page' => 4 ) );
                                    if ( ! empty( $snap_divs ) ) {
                                        foreach ( $snap_divs as $div ) {
                                            $t = get_field('div_ticker', $div->ID) ?: '-';
                                            $a = get_field('div_amount', $div->ID) ?: '-';
                                            echo '<tr><td><strong>'.esc_html($t).'</strong></td><td class="cbah-txt-green cbah-inline-a527bac1">'.esc_html($a).'</td></tr>';
                                        }
                                    } else { echo '<tr><td colspan="2" class="cbah-empty">No recent dividends.</td></tr>'; }
                                    ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- EQUITY REPORTS -->
                <div class="cbah-tab-pane" id="tab-reports">
                    <div class="cbah-reports-layout">
                        
                        <!-- LEFT DIRECTORY LIST -->
                        <div class="cbah-reports-list">
                            <div class="cbah-card-header">REPORTS DIRECTORY</div>
                            <div class="cbah-card-body cbah-no-pad cbah-scroll-y cbah-inline-e37482cd">
                                <table class="cbah-data-table cbah-interactive-table">
                                    <thead>
                                        <tr>
                                            <th>Report Title</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    $equities = get_posts( array( 'post_type' => 'cbah_equity', 'posts_per_page' => 15 ) );
                                    if ( ! empty( $equities ) ) {
                                        foreach ( $equities as $index => $eq ) {
                                            $analysis = get_field('company_analysis', $eq->ID) ?: 'No summary provided.';
                                            $file = get_field('report_file_url', $eq->ID) ?: '#';
                                            $active_class = ($index === 0) ? ' active-row' : '';
                                            ?>
                                            <tr class="<?php echo esc_attr( trim( $active_class ) ); ?>" onclick="cbahLoadReport(this)" data-title="<?php echo esc_attr( $eq->post_title ); ?>" data-desc="<?php echo esc_attr( $analysis ); ?>" data-file="<?php echo esc_attr( $file ); ?>">
                                                <td><?php echo esc_html( $eq->post_title ); ?></td>
                                                <td class="cbah-inline-80d5e1cf"><?php echo esc_html( get_the_date( 'M d, Y', $eq->ID ) ); ?></td>
                                            </tr>
                                            <?php
                                        }
                                    } else { 
                                        echo '<tr><td colspan="2" class="cbah-empty">No reports available.</td></tr>'; 
                                    }
                                    ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- RIGHT PREVIEW PANE WITH DUAL BUTTONS -->
                        <div class="cbah-reports-preview cbah-inline-26d95a02">
                            <h2 class="cbah-inline-09ce3e2d" id="rep-preview-title">Select a report</h2>
                            
                            <!-- DUAL VIEW / DOWNLOAD BUTTONS -->
                            <div class="cbah-inline-7e3efbe6" id="rep-preview-actions">
                                <a href="#" id="rep-preview-view-btn" target="_blank" class="cbah-btn-view cbah-inline-48abaa1b">
                                    <span class="dashicons dashicons-visibility cbah-inline-b8d09f84"></span> View Report
                                </a>
                                <a href="#" id="rep-preview-dl-btn" target="_blank" class="cbah-btn-large cbah-btn-download cbah-inline-03513952" download>
                                    <span class="dashicons dashicons-download cbah-inline-b8d09f84"></span> Download Report
                                </a>
                            </div>

                            <p id="rep-preview-desc" class="cbah-preview-text cbah-inline-5a135f0c">...</p>
                        </div>

                    </div>
                </div>
                
                
                <!-- SECTOR REPORTS -->
                <div class="cbah-tab-pane" id="tab-sector-reports">
                    <div class="cbah-reports-layout">
                        
                        <!-- LEFT DIRECTORY LIST -->
                        <div class="cbah-reports-list">
                            <div class="cbah-card-header">SECTOR REPORTS DIRECTORY</div>
                            <div class="cbah-card-body cbah-no-pad cbah-scroll-y cbah-inline-e37482cd">
                                <table class="cbah-data-table cbah-interactive-table">
                                    <thead>
                                        <tr>
                                            <th>Report Title</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody id="sector-reports-list">
                                    <?php
                                    $sector_posts = get_posts( array( 'post_type' => 'cbah_sector', 'posts_per_page' => 15 ) );
                                    if ( ! empty( $sector_posts ) ) {
                                        foreach ( $sector_posts as $index => $sp ) {
                                            $title = get_the_title($sp->ID);
                                            $date = get_the_date('M d, Y', $sp->ID);
                                            
                                            $raw_content = $sp->post_content ?: 'No general overview provided for this report.';
                                            $content = wpautop( wp_kses_post( $raw_content ) );
                                            
                                            $main_pdf = get_field('main_sector_report_url', $sp->ID) ?: '#';

                                            $active_class = ($index === 0) ? ' active-row' : '';
                                            ?>
                                            <tr class="<?php echo esc_attr( trim( $active_class ) ); ?>" onclick="cbahLoadSectorPost(this)" data-title="<?php echo esc_attr( $title ); ?>" data-mainpdf="<?php echo esc_attr( $main_pdf ); ?>">
                                            <?php
                                            echo '<td><strong>' . esc_html( $title ) . '</strong></td>';
                                            echo '<td class="cbah-inline-80d5e1cf">' . esc_html( $date ) . '</td>';
                                            
                                            echo "<td class='hidden-payload cbah-inline-c8be1ccb'>";
                                            echo "<div class='sp-content'>" . wp_kses_post( $content ) . "</div>";
                                            
                                            // SECTOR REPEATER ITEMS
                                            $repeater = get_field('sector_breakdown_reports', $sp->ID);
                                            echo "<div class='sp-sectors cbah-inline-c8be1ccb'>";
                                            if ( $repeater ) {
                                                foreach ( $repeater as $row ) {
                                                    $s_name = $row['sector_name'] ?: 'Sector';
                                                    $s_rec  = $row['recommendation'] ?: 'Neutral';
                                                    $s_driv = $row['growth_drivers'] ?: '';
                                                    $s_out  = $row['sector_outlook'] ?: '';
                                                    $s_pdf  = $row['sector_pdf'] ?: '';
                                                    
                                                    $badge = 'cbah-txt-green';
                                                    if ( stripos($s_rec, 'underweight') !== false ) $badge = 'cbah-txt-red';
                                                    if ( stripos($s_rec, 'neutral') !== false ) $badge = '';

                                                    echo "<div class='cbah-sector-toggle-card' data-name='".esc_attr($s_name)."'>";
                                                    echo "<div class='cbah-inline-8be405a4'>";
                                                    echo "<h3 class='cbah-inline-0ee55f72'>" . esc_html($s_name) . "</h3>";
                                                    ?>
                                                    <span class="<?php echo esc_attr( $badge ); ?>" class="cbah-inline-b29070cf"><?php echo esc_html( $s_rec ); ?></span>
                                                    <?php
                                                    echo "</div>";
                                                    
                                                    if ( $s_driv ) {
                                                        echo "<h4 class='cbah-inline-0e3f0bdd'>Growth Drivers</h4>";
                                                        echo "<p class='cbah-inline-7a4f9370'>" . esc_html($s_driv) . "</p>";
                                                    }
                                                    if ( $s_out ) {
                                                        echo "<h4 class='cbah-inline-0e3f0bdd'>Sector Outlook</h4>";
                                                        echo "<div class='cbah-inline-b6156395'>" . wp_kses_post(wpautop($s_out)) . "</div>";
                                                    }
                                                    if ( $s_pdf ) {
                                                        echo "<div class='cbah-inline-41cce30b'>";
                                                        echo "<a href='".esc_url($s_pdf)."' target='_blank' class='cbah-btn-view cbah-inline-d44ac06b'><span class='dashicons dashicons-visibility cbah-inline-b8d09f84'></span> View Report</a>";
                                                        echo "<a href='".esc_url($s_pdf)."' target='_blank' class='cbah-btn-large cbah-btn-download cbah-inline-05add553' download><span class='dashicons dashicons-download cbah-inline-b8d09f84'></span> Download Report</a>";
                                                        echo "</div>";
                                                    }
                                                    echo "</div>"; 
                                                }
                                            }
                                            echo "</div></td></tr>";
                                        }
                                    } else { 
                                        echo '<tr><td colspan="2" class="cbah-empty">No sector reports available.</td></tr>'; 
                                    }
                                    ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- RIGHT PANE: Sector Tabbed Preview -->
                        <div class="cbah-reports-preview cbah-inline-26d95a02">
                            <h2 class="cbah-inline-09ce3e2d" id="sec-preview-title">Select a Report</h2>
                            
                            <!-- MAIN SECTOR PDF BUTTONS -->
                            <div class="cbah-inline-000fa159" id="sec-preview-actions">
                                <a href="#" id="sec-preview-view-btn" target="_blank" class="cbah-btn-view cbah-inline-c19589a6">
                                    <span class="dashicons dashicons-visibility cbah-inline-f0e997ab"></span> View Report
                                </a>
                                <a href="#" id="sec-preview-main-btn" target="_blank" class="cbah-btn-large cbah-btn-download cbah-inline-a8b353d2" download>
                                    <span class="dashicons dashicons-download cbah-inline-f0e997ab"></span> Download Report
                                </a>
                            </div>
                            
                            <!-- GLOBAL SECTOR BRIEF (Post Content) -->
                            <div id="sec-preview-content" class="cbah-preview-text cbah-inline-2df3697a"></div>
                            
                            <!-- SECTOR TOGGLE TABS BAR -->
                            <div id="sec-tabs-bar" class="cbah-macro-tabs cbah-inline-3468836b">
                                <!-- Dynamically injected sector tabs go here -->
                            </div>

                            <!-- SECTOR TOGGLE PANES CONTAINER -->
                            <div id="sec-panes-container">
                                <!-- Dynamically injected sector panes go here -->
                            </div>
                        </div>

                    </div>
                </div>
                

                <!-- MACRO REPORTS -->
                <div class="cbah-tab-pane" id="tab-macro-reports">
                    <div class="cbah-reports-layout">
                        
                        <!-- LEFT DIRECTORY LIST -->
                        <div class="cbah-reports-list">
                            <div class="cbah-card-header">MACRO REPORTS DIRECTORY</div>
                            <div class="cbah-card-body cbah-no-pad cbah-scroll-y cbah-inline-e37482cd">
                                <table class="cbah-data-table cbah-interactive-table">
                                    <thead>
                                        <tr>
                                            <th>Report Title</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody id="macro-reports-list">
                                    <?php
                                    $macros = get_posts( array( 'post_type' => 'cbah_macro', 'posts_per_page' => 15 ) );
                                    if ( ! empty( $macros ) ) {
                                        foreach ( $macros as $index => $mac ) {
                                            $title = get_the_title($mac->ID);
                                            $date = get_the_date('M d, Y', $mac->ID);
                                            
                                            $raw_content = $mac->post_content ?: 'No general overview provided for this report.';
                                            $content = wpautop(wp_kses_post($raw_content));
                                            
                                            $inf = get_field('macro_inflation', $mac->ID) ?: 'N/A';
                                            $int = get_field('macro_interest_rate', $mac->ID) ?: 'N/A';
                                            $crude = get_field('macro_crude_oil', $mac->ID) ?: 'N/A';
                                            $gov = get_field('government_policies', $mac->ID) ?: 'No government policies or overview provided for this report.';
                                            $file = get_field('macro_report_url', $mac->ID) ?: '#';

                                            $active_class = ($index === 0) ? ' active-row' : '';
                                            ?>
                                            <tr class="<?php echo esc_attr( trim( $active_class ) ); ?>" onclick="cbahLoadMacroPost(this)" data-title="<?php echo esc_attr( $title ); ?>" data-file="<?php echo esc_attr( $file ); ?>">
                                            <?php
                                            echo '<td>' . esc_html( $title ) . '</td>';
                                            echo '<td class="cbah-inline-80d5e1cf">' . esc_html( $date ) . '</td>';
                                            
                                            echo "<td class='hidden-payload cbah-inline-c8be1ccb'>";
                                            echo "<div class='mac-content'>" . wp_kses_post( $content ) . "</div>";
                                            echo "<div class='mac-inf'>" . esc_html($inf) . "</div>";
                                            echo "<div class='mac-int'>" . esc_html($int) . "</div>";
                                            echo "<div class='mac-crude'>" . esc_html($crude) . "</div>";
                                            echo "<div class='mac-gov'>" . wp_kses_post(wpautop($gov)) . "</div>";
                                            echo "</td>";
                                            echo '</tr>';
                                        }
                                    } else { 
                                        echo '<tr><td colspan="2" class="cbah-empty">No macro reports available.</td></tr>'; 
                                    }
                                    ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- RIGHT PREVIEW PANE WITH DUAL BUTTONS -->
                        <div class="cbah-reports-preview cbah-inline-26d95a02">
                            <h2 class="cbah-inline-09ce3e2d" id="mac-preview-title">Select a report</h2>
                            
                            <!-- DUAL VIEW / DOWNLOAD BUTTONS -->
                            <div class="cbah-inline-7e3efbe6" id="mac-preview-actions">
                                <a href="#" id="mac-preview-view-btn" target="_blank" class="cbah-btn-view cbah-inline-48abaa1b">
                                    <span class="dashicons dashicons-visibility cbah-inline-b8d09f84"></span> View Report
                                </a>
                                <a href="#" id="mac-preview-dl-btn" target="_blank" class="cbah-btn-large cbah-btn-download cbah-inline-03513952" download>
                                    <span class="dashicons dashicons-download cbah-inline-b8d09f84"></span> Download Report
                                </a>
                            </div>

                            <div id="mac-preview-content" class="cbah-preview-text cbah-inline-646bda85"></div>
                            
                            <!-- MINI TABS UI -->
                            <div class="cbah-macro-tabs">
                                <span class="cbah-mac-tab active" data-target="mac-tab-indicators" role="tab" aria-selected="true">Key Indicators</span>
                                <span class="cbah-mac-tab" data-target="mac-tab-gov" role="tab" aria-selected="false">Government Policies</span>
                            </div>

                            <div id="mac-tab-indicators" class="cbah-mac-pane active">
                                <table class="cbah-data-table cbah-table-hover cbah-inline-1da9facb">
                                    <tr><td>Headline Inflation</td><td class="cbah-inline-a527bac1"><strong id="mac-val-inf"></strong>%</td></tr>
                                    <tr><td>Interest Rate (MPR)</td><td class="cbah-inline-a527bac1"><strong id="mac-val-int"></strong>%</td></tr>
                                    <tr><td>Brent Crude Oil</td><td class="cbah-inline-a527bac1">$<strong id="mac-val-crude"></strong>/bbl</td></tr>
                                </table>
                            </div>

                            <div id="mac-tab-gov" class="cbah-mac-pane">
                                <div id="mac-val-gov"></div>
                            </div>
                            
                        </div>

                    </div>
                </div>
                
                <!-- PRICE LISTS -->
                <div class="cbah-tab-pane" id="tab-pricelists">
                    <div class="cbah-card">
                        <div class="cbah-card-header">DAILY PRICE LISTS</div>
                        <div class="cbah-card-body cbah-no-pad cbah-scroll-y cbah-inline-eb6e6d6f">
                            <table class="cbah-data-table cbah-table-hover">
                                <thead><tr><th>Date</th><th class="cbah-inline-a527bac1">Action</th></tr></thead>
                                <tbody>
                                    <?php 
                                    $pl_reports = get_posts( array( 'post_type' => 'cbah_market_report', 'posts_per_page' => 30 ) );
                                    $has_pricelists = false;
                                    
                                    if ( ! empty( $pl_reports ) ) {
                                        foreach ( $pl_reports as $rep ) {
                                            $pricelists = get_field('daily_price_lists', $rep->ID);
                                            if ( $pricelists ) {
                                                foreach ( $pricelists as $p ) {
                                                    $has_pricelists = true;
                                                    echo '<tr><td><strong>' . esc_html($p['date']) . '</strong></td><td class="cbah-inline-a527bac1"><a href="' . esc_url($p['url']) . '" target="_blank" class="cbah-btn-large cbah-btn-download cbah-inline-04370ea0">Download</a></td></tr>';
                                                }
                                            }
                                        }
                                    }
                                    
                                    if ( ! $has_pricelists ) { echo '<tr><td colspan="2" class="cbah-empty">No Price Lists found in recent reports.</td></tr>'; }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- MARKET DATA Search UI -->
                <div class="cbah-tab-pane" id="tab-market">
                    <div class="cbah-card cbah-inline-7dde5e56">
                        <div class="cbah-card-header cbah-inline-87bd79fd">
                            <span>MARKET DATA SEARCH: <span class="cbah-inline-de647afc">Search for your prefered stock using the stock name</span></span>
                            <div class="cbah-search-wrapper cbah-inline-3d444a0c">
                                <span class="dashicons dashicons-search cbah-inline-968a61e7"></span>
                                <input type="text" id="cbah-stock-search" placeholder="Type symbol e.g., MTNN..." class="cbah-search-input" value="MTNN">
                                <button type="button" id="cbah-search-btn" class="cbah-btn-view cbah-inline-2e78f9fb">Search</button>
                            </div>
                        </div>

                        <div class="cbah-inline-7b20d4bc">
                            <span><strong>Quick Look:</strong> Select a popular NGX symbol to generate instant charts:</span>
                            <div class="cbah-ticker-pills cbah-inline-c5f72a8b">
                                <button type="button" class="cbah-pill-btn" onclick="cbahTriggerSearch('MTNN')">MTNN</button>
                                <button type="button" class="cbah-pill-btn" onclick="cbahTriggerSearch('DANGCEM')">DANGCEM</button>
                                <button type="button" class="cbah-pill-btn" onclick="cbahTriggerSearch('GTCO')">GTCO</button>
                                <button type="button" class="cbah-pill-btn" onclick="cbahTriggerSearch('ZENITHBANK')">ZENITHBANK</button>
                                <button type="button" class="cbah-pill-btn" onclick="cbahTriggerSearch('AIRTELAFRI')">AIRTELAFRI</button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="cbah-reports-layout">
                        <div class="cbah-card cbah-inline-7623f055">
                            <div class="cbah-card-header">MARKET HIGHLIGHT</div>
                            <div class="cbah-card-body" id="market-data-highlight">
                                <h3>MTNN</h3>
                                <p class="cbah-inline-8749e525">Selected stock indicator. Interactive price history loaded on right.</p>
                            </div>
                        </div>
                        <div class="cbah-card cbah-inline-331e9253">
                            <div class="cbah-card-header">PRICE MOVEMENT CHART</div>
                            <div class="cbah-card-body" id="market-data-chart">
                                <div class="cbah-inline-12a9ecb7" id="tv_chart_container"></div>
                            </div>
                        </div>
                    </div>
                </div>
        
                <!--DAILY MARKET SUMMARIES -->
                <div class="cbah-tab-pane" id="tab-summaries">
                    <div class="cbah-card">
                        <div class="cbah-card-header">DAILY MARKET SUMMARIES</div>
                        <div class="cbah-card-body cbah-no-pad cbah-scroll-y cbah-inline-eb6e6d6f">
                            <table class="cbah-data-table cbah-table-hover">
                                <thead><tr><th>Date</th><th class="cbah-inline-a527bac1">Action</th></tr></thead>
                                <tbody>
                                    <?php 
                                    $sum_reports = get_posts( array( 'post_type' => 'cbah_market_report', 'posts_per_page' => 30 ) );
                                    $has_summaries = false;
                                    
                                    if ( ! empty( $sum_reports ) ) {
                                        foreach ( $sum_reports as $rep ) {
                                            $summaries = get_field('market_summaries', $rep->ID);
                                            if ( $summaries ) {
                                                foreach ( $summaries as $s ) {
                                                    $has_summaries = true;
                                                    echo '<tr><td><strong>' . esc_html($s['date']) . '</strong></td><td class="cbah-inline-a527bac1"><a href="' . esc_url($s['url']) . '" target="_blank" class="cbah-btn-large cbah-btn-download cbah-inline-04370ea0">Download</a></td></tr>';
                                                }
                                            }
                                        }
                                    }
                                    
                                    if ( ! $has_summaries ) { echo '<tr><td colspan="2" class="cbah-empty">No Market Summaries found in recent reports.</td></tr>'; }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!--CORPORATE RESULTS -->
                <div class="cbah-tab-pane" id="tab-corporate">
                    <div class="cbah-card">
                        <div class="cbah-card-header">CORPORATE RESULTS</div>
                        <div class="cbah-card-body cbah-no-pad cbah-scroll-y cbah-inline-eb6e6d6f">
                            <table class="cbah-data-table cbah-table-hover">
                                <thead><tr><th>Company Ticker</th><th>Period</th><th>Published</th><th class="cbah-inline-a527bac1">Document</th></tr></thead>
                                <tbody>
                                <?php
                                $corporates = get_posts( array( 'post_type' => 'cbah_corporate', 'posts_per_page' => 20 ) );
                                if ( ! empty( $corporates ) ) {
                                    foreach ( $corporates as $corp ) {
                                        $ticker = get_field('corp_ticker', $corp->ID) ?: 'N/A';
                                        $period = get_field('corp_period', $corp->ID) ?: 'N/A';
                                        
                                        // Bulletproof file fetching (Handles URL strings OR File Arrays)
                                        $file_data = get_field('corp_url', $corp->ID);
                                        $url = '#';
                                        if ( is_array($file_data) && isset($file_data['url']) ) {
                                            $url = $file_data['url'];
                                        } elseif ( is_string($file_data) && !empty($file_data) ) {
                                            $url = $file_data;
                                        }

                                        echo '<tr>';
                                        echo '<td><strong>' . esc_html( $ticker ) . '</strong></td>';
                                        echo '<td>' . esc_html( $period ) . '</td>';
                                        echo '<td>' . get_the_date('Y-m-d', $corp->ID) . '</td>';
                                        
                                        if ($url !== '#') {
                                            echo '<td class="cbah-inline-a527bac1"><a href="'.esc_url($url).'" target="_blank" class="cbah-btn-large cbah-btn-download">Download</a></td>';
                                        } else {
                                            echo '<td class="cbah-inline-4283c1b7">No File</td>';
                                        }
                                        
                                        echo '</tr>';
                                    }
                                } else { echo '<tr><td colspan="4" class="cbah-empty">No corporate results published yet.</td></tr>'; }
                                ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- DIVIDEND TRACKER -->
                <div class="cbah-tab-pane" id="tab-dividend">
                    <div class="cbah-card">
                        <div class="cbah-card-header">DIVIDEND TRACKER</div>
                        <div class="cbah-card-body cbah-no-pad cbah-scroll-y cbah-inline-eb6e6d6f">
                            <table class="cbah-data-table cbah-table-hover">
                                <thead><tr><th>Company Ticker</th><th>Dividend Amount</th><th>Closure Date</th><th>Payment Date</th></tr></thead>
                                <tbody>
                                <?php
                                $dividends = get_posts( array( 'post_type' => 'cbah_dividend', 'posts_per_page' => 20 ) );
                                if ( ! empty( $dividends ) ) {
                                    foreach ( $dividends as $div ) {
                                        // Checking multiple possible ACF field names just in case
                                        $ticker = get_field('div_ticker', $div->ID) ?: get_field('ticker', $div->ID) ?: 'N/A';
                                        $amt = get_field('div_amount', $div->ID) ?: get_field('amount', $div->ID) ?: 'N/A';
                                        $close = get_field('div_closure', $div->ID) ?: get_field('closure_date', $div->ID) ?: 'N/A';
                                        $pay = get_field('div_payment', $div->ID) ?: get_field('payment_date', $div->ID) ?: 'N/A';
                                        
                                        echo '<tr>';
                                        echo '<td><strong>' . esc_html( $ticker ) . '</strong></td>';
                                        echo '<td class="cbah-txt-green">₦' . esc_html( $amt ) . '</td>';
                                        echo '<td>' . esc_html( $close ) . '</td>';
                                        echo '<td>' . esc_html( $pay ) . '</td>';
                                        echo '</tr>';
                                    }
                                } else { echo '<tr><td colspan="4" class="cbah-empty">No dividends tracked yet.</td></tr>'; }
                                ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </main>
        </div>

        

        <?php
        return ob_get_clean();
    }

    public function handle_ajax_data_request() {
        if ( ! cbah_is_acf_pro_active() || ! function_exists( 'get_field' ) ) { wp_send_json_error( array( 'message' => __( 'Advanced Custom Fields PRO is required.', 'spindle-market-research' ) ) ); }
        wp_send_json_success( $this->get_latest_analytics_matrix() );
    }

    // Display on homepage and other pages
    public function render_homepage_snapshot() {
        $this->enqueue_public_styles();

        if ( ! cbah_is_acf_pro_active() || ! function_exists( 'get_field' ) ) return '';
        
        // Get the single latest market report
        $market = get_posts( array( 'post_type' => 'cbah_market_report', 'posts_per_page' => 1 ) );
        if ( empty( $market ) ) return '<p>No market data available.</p>';
        $m_id = $market[0]->ID;
        
        $report_date = get_the_date( 'M d, Y • g:i a', $m_id );
        
        // ASI Calculation
        $open = (float) get_field( 'ngx_open', $m_id );
        $close = (float) get_field( 'ngx_close', $m_id );
        $diff = $close - $open;
        $pct = ( $open > 0 ) ? ( $diff / $open ) * 100 : 0;
        
        $asi_icon = ( $diff >= 0 ) ? 'dashicons-arrow-up-alt2' : 'dashicons-arrow-down-alt2';
        $asi_color = ( $diff >= 0 ) ? 'cbah-txt-green' : 'cbah-txt-red';
        
        // Get arrays
        $gainers = get_field( 'market_gainers', $m_id ) ?: [];
        $losers  = get_field( 'market_losers', $m_id ) ?: [];
        
        // DYNAMIC SORTING
        if ( ! empty( $gainers ) ) {
            usort( $gainers, function( $a, $b ) {
                $valA = (float) str_replace( array('%','+','-'), '', $a['percentage'] );
                $valB = (float) str_replace( array('%','+','-'), '', $b['percentage'] );
                return $valB <=> $valA;
            });
        }
        $top_g = ! empty( $gainers ) ? $gainers[0] : array( 'ticker' => '-', 'percentage' => '0', 'price' => '0.00' );
        
        if ( ! empty( $losers ) ) {
            usort( $losers, function( $a, $b ) {
                $valA = (float) str_replace( array('%','+','-'), '', $a['percentage'] );
                $valB = (float) str_replace( array('%','+','-'), '', $b['percentage'] );
                return $valB <=> $valA;
            });
        }
        $top_l = ! empty( $losers ) ? $losers[0] : array( 'ticker' => '-', 'percentage' => '0', 'price' => '0.00' );
        
        // Extract prices safely
        $top_g_price = !empty($top_g['price']) ? ' ₦' . $top_g['price'] : '';
        $top_l_price = !empty($top_l['price']) ? ' ₦' . $top_l['price'] : '';
        
        // Automated Signs for Snapshot
        $clean_g_pct = str_replace(array('+', '-', '%', ' '), '', $top_g['percentage']);
        $clean_l_pct = str_replace(array('+', '-', '%', ' '), '', $top_l['percentage']);

        // Turnover with intelligent auto-formatting
        $raw_vol = trim( (string) get_field( 'turnover_volume', $m_id ) );
        $raw_val = trim( (string) get_field( 'turnover_value', $m_id ) );
        $has_volume = '' !== $raw_vol;
        $has_value  = '' !== $raw_val;
        
        $vol = $has_volume ? $this->format_market_number($raw_vol) : '';
        $val = $has_value ? $this->format_market_number($raw_val) : '';
        $raw_mcap = trim( (string) get_field( 'market_cap', $m_id ) );
        $has_market_cap = '' !== $raw_mcap;
        $market_cap_display = '';
        if ( $has_market_cap ) {
            if ( is_numeric( str_replace( ',', '', $raw_mcap ) ) ) {
                $market_cap_display = '₦' . $this->format_market_number( $raw_mcap );
            } else {
                $market_cap_display = ( false === stripos( $raw_mcap, '₦' ) && false === stripos( $raw_mcap, '$' ) ) ? '₦' . $raw_mcap : $raw_mcap;
            }
        }

        $snapshot_context = ( is_front_page() || is_home() ) ? 'cbah-snapshot-home' : 'cbah-snapshot-page';

        ob_start();
        ?>
        <div class="cbah-marketdata-date <?php echo esc_attr( $snapshot_context === 'cbah-snapshot-home' ? 'cbah-marketdata-date-home' : 'cbah-marketdata-date-page' ); ?>">
            <span class="dashicons dashicons-clock"></span>
            Data as of: <strong class="mkt-date"><?php echo esc_html( $report_date ); ?></strong>
        </div>

        <div class="cbah-home-snapshot <?php echo esc_attr( $snapshot_context . ( $has_market_cap ? ' cbah-snapshot-has-market-cap' : '' ) ); ?>">
            <div class="cbah-snap-item">
                <span class="cbah-snap-label">NGX All-Share Index</span>
                <span class="cbah-snap-value"><?php echo number_format( $close, 2 ); ?></span>
                <span class="cbah-snap-change <?php echo esc_attr( $asi_color ); ?>">
                    <span class="dashicons <?php echo esc_attr( $asi_icon ); ?>"></span> 
                    <?php echo number_format( abs( $pct ), 2 ); ?>%
                </span>
            </div>
            
            <div class="cbah-snap-item">
                <span class="cbah-snap-label">Top Gainer</span>
                <span class="cbah-snap-value">
                    <?php echo esc_html( $top_g['ticker'] ); ?>
                    <span class="cbah-snap-price">
                        <?php echo esc_html( $top_g_price ); ?>
                    </span>
                </span>
                <span class="cbah-snap-change cbah-txt-green">
                    <span class="dashicons dashicons-arrow-up-alt2"></span> 
                    +<?php echo esc_html( $clean_g_pct ); ?>%
                </span>
            </div>
            
            <div class="cbah-snap-item">
                <span class="cbah-snap-label">Top Loser</span>
                <span class="cbah-snap-value">
                    <?php echo esc_html( $top_l['ticker'] ); ?>
                    <span class="cbah-snap-price">
                        <?php echo esc_html( $top_l_price ); ?>
                    </span>
                </span>
                <span class="cbah-snap-change cbah-txt-red">
                    <span class="dashicons dashicons-arrow-down-alt2"></span> 
                    -<?php echo esc_html( $clean_l_pct ); ?>%
                </span>
            </div>
            
            <?php if ( $has_volume || $has_value || $has_market_cap ) : ?>
                <?php
                $metric_count = (int) $has_volume + (int) $has_value + (int) $has_market_cap;
                $metric_labels = array();
                $metric_values = array();
                if ( $has_volume ) {
                    $metric_labels[] = 'Volume';
                    $metric_values[] = $vol;
                }
                if ( $has_value ) {
                    $metric_labels[] = 'Value';
                    $metric_values[] = '₦' . $val;
                }
                if ( $has_market_cap ) {
                    $metric_labels[] = 'Cap';
                    $metric_values[] = $market_cap_display;
                }
                ?>
                <div class="cbah-snap-item cbah-snap-market-metrics cbah-snap-metrics-count-<?php echo esc_attr( $metric_count ); ?>">
                    <span class="cbah-snap-market-title">MARKET TURNOVER &amp; SIZE</span>
                    <div class="cbah-snap-metrics-grid">
                        <?php foreach ( $metric_labels as $index => $metric_label ) : ?>
                            <div class="cbah-snap-metric-cell">
                                <span class="cbah-snap-metric-label"><?php echo esc_html( $metric_label ); ?></span>
                                <span class="cbah-snap-metric-value"><?php echo esc_html( $metric_values[ $index ] ); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

     // Market Ticker
    public function render_market_ticker() {
        $this->enqueue_public_styles();

        $settings = CBAH_Settings::get_settings();
        wp_enqueue_script(
            'cbah-ticker',
            CBAH_PLUGIN_URL . 'public/js/cbah-ticker.js',
            array(),
            CBAH_VERSION,
            true
        );

        if ( ! cbah_is_acf_pro_active() || ! function_exists( 'get_field' ) ) return '';
        
        $market = get_posts( array( 'post_type' => 'cbah_market_report', 'posts_per_page' => 1 ) );
        if ( empty( $market ) ) return '';
        $m_id = $market[0]->ID;
        
        $ticker_items = array();

        $report_date = get_the_date( 'M d, Y • g:i a', $m_id );

        // 1. NGX ASI (Main Index)
        $open  = (float) get_field( 'ngx_open', $m_id );
        $close = (float) get_field( 'ngx_close', $m_id );
        $diff  = $close - $open;
        $pct   = ( $open > 0 ) ? ( $diff / $open ) * 100 : 0;
        $asi_color = ( $diff >= 0 ) ? 'cbah-txt-green' : 'cbah-txt-red';
        $asi_arrow = ( $diff >= 0 ) ? '▲' : '▼';
        
        $ticker_items[] = 'NGX ASI: ' . number_format( $close, 2 ) . ' <span class="' . $asi_color . '">' . $asi_arrow . ' ' . number_format( abs( $pct ), 2 ) . '%</span>';

        // 2. Loop ALL Gainers
        $gainers = get_field( 'market_gainers', $m_id ) ?: [];
        foreach ( $gainers as $g ) {
            $clean_pct = esc_html( str_replace( array('+', '-', '%', ' '), '', $g['percentage'] ) );
            $price_val = !empty($g['price']) ? ' ₦' . esc_html($g['price']) : '';
            $ticker_items[] = esc_html( $g['ticker'] ) . $price_val . ' <span class="cbah-txt-green">▲ +' . $clean_pct . '%</span>';
        }

        // 3. Loop ALL Losers
        $losers = get_field( 'market_losers', $m_id ) ?: [];
        foreach ( $losers as $l ) {
            $clean_pct = esc_html( str_replace( array('+', '-', '%', ' '), '', $l['percentage'] ) );
            $price_val = !empty($l['price']) ? ' ₦' . esc_html($l['price']) : '';
            $ticker_items[] = esc_html( $l['ticker'] ) . $price_val . ' <span class="cbah-txt-red">▼ -' . $clean_pct . '%</span>';
        }

        $ticker_string = '';
        foreach ( $ticker_items as $item ) {
            $ticker_string .= '<div class="cbah-ticker-item">' . $item . '</div>';
        }

        ob_start();
        ?>
        <div class="cbah-price-ticker cbah-ticker-full-bleed" data-ticker-speed="<?php echo esc_attr( isset( $settings['ticker_speed'] ) ? (int) $settings['ticker_speed'] : 60 ); ?>">
            <div class="cbah-ticker-date">
                <span class="dashicons dashicons-clock"></span>
                <?php echo esc_html( $report_date ); ?>
            </div>

            <div class="cbah-ticker-wrapper">
                <div class="cbah-ticker-track">
                    <div class="cbah-ticker-group cbah-ticker-group-primary"><?php echo wp_kses_post( $ticker_string ); ?></div>
                    <div class="cbah-ticker-group cbah-ticker-group-secondary" aria-hidden="true"><?php echo wp_kses_post( $ticker_string ); ?></div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}