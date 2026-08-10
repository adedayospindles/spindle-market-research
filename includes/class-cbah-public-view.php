<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


class CBAH_Public_View {

    public function enqueue_assets() {
        wp_enqueue_script(
            'cbah-chartjs',
            CBAH_PLUGIN_URL . 'vendor/chartjs/chart.umd.min.js',
            array(),
            '4.4.1',
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

        wp_enqueue_style(
            'cbah-frontend-style',
            CBAH_PLUGIN_URL . 'public/css/cbah-style.css',
            array(),
            CBAH_VERSION
        );

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
            $m_cap = '0.00';
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
        if ( ! function_exists( 'get_field' ) ) return '<p>ACF Pro Required.</p>';

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
                    
                    <div style="display: flex; justify-content: flex-end; margin-bottom: 16px; font-size: 12px; color: #64748b; align-items: center;">
                        <span class="dashicons dashicons-calendar-alt" style="margin-right: 6px; font-size: 14px; width: 14px; height: 14px;"></span>
                        Market Data as of: <strong style="color: #0f172a; margin-left: 5px;"><?php echo esc_html($data['report_date']); ?></strong>
                    </div>

                    <!-- ROW 1: Market Core -->
                    <div class="cbah-grid-3">
                        <div class="cbah-card">
                            <div class="cbah-card-header">NGX ALL SHARE INDEX</div>
                            <div class="cbah-card-body">
                                <div class="cbah-chart-filters" id="cbah-history-pills">
                                    <span data-period="1d">1D</span><span class="active" data-period="5d">5D</span><span data-period="1m">1M</span><span data-period="ytd">YTD</span>
                                </div>
                                <div style="height: 250px;"><canvas id="cbahMarketChart"></canvas></div>
                            </div>
                        </div>

                        <div class="cbah-card">
                            <div class="cbah-card-header">TOP GAINERS</div>
                            <div class="cbah-card-body cbah-no-pad">
                                <table class="cbah-data-table cbah-table-hover">
                                    <thead><tr>
                                        <th>Symbol</th>
                                        <th style="text-align:center;">Price (₦)</th>
                                        <th style="text-align:right;padding-right: 10px;">Change(%)</th>
                                    </tr></thead>
                                    <tbody id="cbah_table_gainers">
                                        <?php if($data['gainers']): foreach($data['gainers'] as $g): 
                                            $clean_val = str_replace(array('+', '-', '%', ' '), '', $g['percentage']);
                                        ?>
                                            <tr>
                                                <td><strong><?php echo esc_html($g['ticker']); ?></strong></td>
                                                <td style="text-align:center; color:#64748b;"><?php echo esc_html($g['price']); ?></td>
                                                <td style="text-align:right;padding-right: 10px;" class="cbah-txt-green">+<?php echo esc_html($clean_val); ?>%</td>
                                            </tr>
                                        <?php endforeach; else: echo '<tr><td colspan="3" class="cbah-empty">No Data</td></tr>'; endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="cbah-card">
                            <div class="cbah-card-header">TOP LOSERS</div>
                            <div class="cbah-card-body cbah-no-pad">
                                <table class="cbah-data-table cbah-table-hover">
                                    <thead><tr>
                                        <th>Symbol</th>
                                        <th style="text-align:center;">Price (₦)</th>
                                        <th style="text-align:right;padding-right: 10px;">Change(%)</th>
                                    </tr></thead>
                                    <tbody id="cbah_table_losers">
                                        <?php if($data['losers']): foreach($data['losers'] as $l): 
                                            $clean_val = str_replace(array('+', '-', '%', ' '), '', $l['percentage']);
                                        ?>
                                            <tr>
                                                <td><strong><?php echo esc_html($l['ticker']); ?></strong></td>
                                                <td style="text-align:center; color:#64748b;"><?php echo !empty($l['price']) ? esc_html($l['price']) : '-'; ?></td>
                                                <td style="text-align:right;padding-right: 10px;" class="cbah-txt-red">-<?php echo esc_html($clean_val); ?>%</td>
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
                            <div class="cbah-card-body"><div style="height: 220px;"><canvas id="cbahSectorChart"></canvas></div></div>
                        </div>

                        <div class="cbah-card">
                            <div class="cbah-card-header" style="display:flex; justify-content:space-between; align-items:center;">
                                <span style="font-family: Libre Caslon Text; font-size: 0.7rem;">FIXED INCOME</span>
                                <select id="cbah-fixed-filter" class="cbah-dropdown-minimal">
                                    <option value="all">Instruments</option><option value="Bonds">Bonds</option><option value="Bills">Bills</option>
                                </select>
                            </div>
                            <div class="cbah-card-body cbah-no-pad cbah-scroll-y">
                                <table class="cbah-data-table cbah-table-hover">
                                    <thead><tr><th>Tenor</th><th style="text-align:right;">Action</th></tr></thead>
                                    <tbody id="cbah_table_fixed">
                                        <?php if($data['fixed']): foreach($data['fixed'] as $f): ?>
                                            <tr data-type="<?php echo esc_attr($f['fi_type']); ?>">
                                                <td><?php echo esc_html($f['tenor']); ?></td>
                                                <td style="text-align:right;"><a href="<?php echo esc_url($f['action_link']); ?>" target="_blank" class="cbah-btn-view">View</a></td>
                                            </tr>
                                        <?php endforeach; else: echo '<tr><td colspan="2" class="cbah-empty">No Data</td></tr>'; endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- EQUITY MARKET TURNOVER & SIZE CARD -->
                        <div class="cbah-card">
                            <div class="cbah-card-header">EQUITY MARKET TURNOVER & SIZE</div>
                            <div class="cbah-card-body cbah-no-pad">
                                <table class="cbah-data-table" style="margin-bottom:0;">
                                    <tbody>
                                        <tr>
                                            <td style="padding: 12px 16px; border-bottom: 1px solid #f1f5f9; color: #64748b;">Market Capitalization</td>
                                            <td style="padding: 12px 16px; border-bottom: 1px solid #f1f5f9; text-align: right;">
                                                <strong style="color: #0f172a; font-size:13px;">
                                                    <?php 
                                                    $raw_mcap = trim($data['m_cap']);
                                                    // Check if numeric and format with the smart helper function
                                                    if (is_numeric(str_replace(',', '', $raw_mcap))) {
                                                        echo '₦' . $this->format_market_number($raw_mcap);
                                                    } else {
                                                        // Automatically prepend ₦ if custom text is provided without currency symbols
                                                        echo (stripos($raw_mcap, '₦') === false && stripos($raw_mcap, '$') === false) ? '₦' . esc_html($raw_mcap) : esc_html($raw_mcap);
                                                    }
                                                    ?>
                                                </strong>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 12px 16px; border-bottom: 1px solid #f1f5f9; color: #64748b;">Turnover Volume</td>
                                            <td style="padding: 12px 16px; border-bottom: 1px solid #f1f5f9; text-align: right;">
                                                <strong style="color: #0f172a; font-size:13px;"><?php echo esc_html($this->format_market_number($data['vol'])); ?></strong>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 12px 16px; color: #64748b;">Turnover Value</td>
                                            <td style="padding: 12px 16px; text-align: right;">
                                                <strong style="color: #0f172a; font-size:13px;">₦<?php echo esc_html($this->format_market_number($data['val'])); ?></strong>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <!--<div style="height:120px; padding:10px;"><canvas id="cbahTurnoverChart"></canvas></div>-->
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
                                        <tr><td>Headline Inflation</td><td style="text-align:right;"><strong><?php echo esc_html($data['inf']); ?>%</strong></td></tr>
                                        <tr><td>Interest Rate (MPR)</td><td style="text-align:right;"><strong><?php echo esc_html($data['int_r']); ?>%</strong></td></tr>
                                        <tr><td>Brent Crude Oil</td><td style="text-align:right;"><strong>$<?php echo esc_html($data['crude']); ?>/bbl</strong></td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="cbah-card">
                            <div class="cbah-card-header">FOREIGN EXCHANGE RATES</div>
                            <div class="cbah-card-body cbah-no-pad cbah-scroll-y" style="max-height: 180px;">
                                <table class="cbah-data-table cbah-table-hover">
                                    <thead><tr><th>Currency Pair</th><th style="text-align:right;">Rate (₦)</th></tr></thead>
                                    <tbody>
                                        <?php if($data['macro_fx']): foreach($data['macro_fx'] as $fx): ?>
                                            <tr>
                                                <td><strong><?php echo esc_html($fx['pair']); ?></strong></td>
                                                <td style="text-align:right;">₦<?php echo esc_html($fx['rate']); ?></td>
                                            </tr>
                                        <?php endforeach; else: echo '<tr><td colspan="2" class="cbah-empty">No FX Data</td></tr>'; endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="cbah-card">
                            <div class="cbah-card-header">RECENT DIVIDENDS</div>
                            <div class="cbah-card-body cbah-no-pad cbah-scroll-y" style="max-height: 180px;">
                                <table class="cbah-data-table cbah-table-hover">
                                    <thead><tr><th>Symbol</th><th style="text-align:right;">Amount (₦)</th></tr></thead>
                                    <tbody>
                                    <?php
                                    $snap_divs = get_posts( array( 'post_type' => 'cbah_dividend', 'posts_per_page' => 4 ) );
                                    if ( ! empty( $snap_divs ) ) {
                                        foreach ( $snap_divs as $div ) {
                                            $t = get_field('div_ticker', $div->ID) ?: '-';
                                            $a = get_field('div_amount', $div->ID) ?: '-';
                                            echo '<tr><td><strong>'.esc_html($t).'</strong></td><td style="text-align:right;" class="cbah-txt-green">'.esc_html($a).'</td></tr>';
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
                            <div class="cbah-card-body cbah-no-pad cbah-scroll-y" style="max-height: 550px;">
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
                                            $active = ($index === 0) ? 'class="active-row"' : '';
                                            echo "<tr $active onclick='cbahLoadReport(this)' data-title='".esc_attr($eq->post_title)."' data-desc='".esc_attr($analysis)."' data-file='".esc_attr($file)."'>";
                                            echo '<td>' . esc_html( $eq->post_title ) . '</td>';
                                            echo '<td style="color:#64748b; font-size:12px;">' . get_the_date('M d, Y', $eq->ID) . '</td>';
                                            echo '</tr>';
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
                        <div class="cbah-reports-preview" style="background:#f8fafc; overflow-y:auto; max-height: 550px; padding:24px;">
                            <h2 id="rep-preview-title" style="margin-top:0; color:#0f172a; font-size:20px; margin-bottom:10px;">Select a report</h2>
                            
                            <!-- DUAL VIEW / DOWNLOAD BUTTONS -->
                            <div id="rep-preview-actions" style="display:flex; gap:10px; margin-bottom: 20px; display:none;">
                                <a href="#" id="rep-preview-view-btn" target="_blank" class="cbah-btn-view" style="display:inline-flex; align-items:center; background:#ffffff; border:1px solid #cbd5e1; padding:8px 16px; font-size:12px;">
                                    <span class="dashicons dashicons-visibility" style="margin-right:4px; font-size:14px; width:14px; height:14px;"></span> View Report
                                </a>
                                <a href="#" id="rep-preview-dl-btn" target="_blank" class="cbah-btn-large" style="display:inline-flex; align-items:center; border:1px solid #cbd5e1; padding:8px 16px; font-size:12px;" download>
                                    <span class="dashicons dashicons-download" style="margin-right:4px; font-size:14px; width:14px; height:14px;"></span> Download Report
                                </a>
                            </div>

                            <p id="rep-preview-desc" class="cbah-preview-text" style="color:#334155; font-size:14px; line-height:1.6;">...</p>
                        </div>

                    </div>
                </div>
                
                
                <!-- SECTOR REPORTS -->
                <div class="cbah-tab-pane" id="tab-sector-reports">
                    <div class="cbah-reports-layout">
                        
                        <!-- LEFT DIRECTORY LIST -->
                        <div class="cbah-reports-list">
                            <div class="cbah-card-header">SECTOR REPORTS DIRECTORY</div>
                            <div class="cbah-card-body cbah-no-pad cbah-scroll-y" style="max-height: 550px;">
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
                                            $content = apply_filters('the_content', $raw_content);
                                            
                                            $main_pdf = get_field('main_sector_report_url', $sp->ID) ?: '#';

                                            $active = ($index === 0) ? 'class="active-row"' : '';
                                            echo "<tr $active onclick='cbahLoadSectorPost(this)' data-title='".esc_attr($title)."' data-mainpdf='".esc_attr($main_pdf)."'>";
                                            echo '<td><strong>' . esc_html( $title ) . '</strong></td>';
                                            echo '<td style="color:#64748b; font-size:12px;">' . esc_html( $date ) . '</td>';
                                            
                                            echo "<td style='display:none;' class='hidden-payload'>";
                                            echo "<div class='sp-content'>" . $content . "</div>";
                                            
                                            // SECTOR REPEATER ITEMS
                                            $repeater = get_field('sector_breakdown_reports', $sp->ID);
                                            echo "<div class='sp-sectors' style='display:none;'>";
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
                                                    echo "<div style='display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; border-bottom:1px solid #f1f5f9; padding-bottom:8px;'>";
                                                    echo "<h3 style='margin:0; font-size:14px; color:#0f172a;'>" . esc_html($s_name) . "</h3>";
                                                    echo "<span class='{$badge}' style='background:#f8fafc; padding:4px 8px; border-radius:4px; font-size:11px; font-weight:700;'>" . esc_html($s_rec) . "</span>";
                                                    echo "</div>";
                                                    
                                                    if ( $s_driv ) {
                                                        echo "<h4 style='margin:0 0 4px 0; font-size:11px; color:#64748b; text-transform:uppercase; letter-spacing:0.5px;'>Growth Drivers</h4>";
                                                        echo "<p style='font-size:12px; color:#334155; margin-top:0; margin-bottom:14px;'>" . esc_html($s_driv) . "</p>";
                                                    }
                                                    if ( $s_out ) {
                                                        echo "<h4 style='margin:0 0 4px 0; font-size:11px; color:#64748b; text-transform:uppercase; letter-spacing:0.5px;'>Sector Outlook</h4>";
                                                        echo "<div style='font-size:12px; color:#334155; margin-bottom:14px;'>" . wp_kses_post(wpautop($s_out)) . "</div>";
                                                    }
                                                    if ( $s_pdf ) {
                                                        echo "<div style='display:flex; gap:10px; margin-top:10px;'>";
                                                        echo "<a href='".esc_url($s_pdf)."' target='_blank' class='cbah-btn-view' style='display:inline-flex; align-items:center; background:#ffffff; border:1px solid #cbd5e1;'><span class='dashicons dashicons-visibility' style='margin-right:4px; font-size:14px; width:14px; height:14px;'></span> View</a>";
                                                        echo "<a href='".esc_url($s_pdf)."' target='_blank' class='cbah-btn-view' style='display:inline-flex; align-items:center; background:#f1f5f9; border:1px solid #cbd5e1;' download><span class='dashicons dashicons-download' style='margin-right:4px; font-size:14px; width:14px; height:14px;'></span> Download</a>";
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
                        <div class="cbah-reports-preview" style="background:#f8fafc; overflow-y:auto; max-height: 550px; padding:24px;">
                            <h2 id="sec-preview-title" style="margin-top:0; color:#0f172a; font-size:20px; margin-bottom:10px;">Select a Report</h2>
                            
                            <!-- MAIN SECTOR PDF BUTTONS -->
                            <div id="sec-preview-actions" style="display:flex; gap:12px; margin-bottom: 20px; display:none;">
                                <a href="#" id="sec-preview-view-btn" target="_blank" class="cbah-btn-view" style="padding:8px 16px; display:inline-flex; align-items:center; font-size:12px;">
                                    <span class="dashicons dashicons-visibility" style="margin-right:4px;"></span> View Report
                                </a>
                                <a href="#" id="sec-preview-main-btn" target="_blank" class="cbah-btn-large" style="width:fit-content; display:inline-flex; align-items:center; padding:8px 16px; font-size:12px;" download>
                                    <span class="dashicons dashicons-download" style="margin-right:4px;"></span> Download Report
                                </a>
                            </div>
                            
                            <!-- GLOBAL SECTOR BRIEF (Post Content) -->
                            <div id="sec-preview-content" class="cbah-preview-text" style="color:#334155; font-size:14px; line-height:1.6; margin-bottom:20px; border-top:1px solid #cbd5e1; padding-top:16px;"></div>
                            
                            <!-- SECTOR TOGGLE TABS BAR -->
                            <div id="sec-tabs-bar" class="cbah-macro-tabs" style="display:flex; gap:15px; margin-bottom:15px; border-bottom:1px solid #cbd5e1; padding-bottom:0; display:none;">
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
                            <div class="cbah-card-body cbah-no-pad cbah-scroll-y" style="max-height: 550px;">
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
                                            $content = apply_filters('the_content', $raw_content);
                                            
                                            $inf = get_field('macro_inflation', $mac->ID) ?: 'N/A';
                                            $int = get_field('macro_interest_rate', $mac->ID) ?: 'N/A';
                                            $crude = get_field('macro_crude_oil', $mac->ID) ?: 'N/A';
                                            $gov = get_field('government_policies', $mac->ID) ?: 'No government policies or overview provided for this report.';
                                            $file = get_field('macro_report_url', $mac->ID) ?: '#';

                                            $active = ($index === 0) ? 'class="active-row"' : '';
                                            echo "<tr $active onclick='cbahLoadMacroPost(this)' data-title='".esc_attr($title)."' data-file='".esc_attr($file)."'>";
                                            echo '<td>' . esc_html( $title ) . '</td>';
                                            echo '<td style="color:#64748b; font-size:12px;">' . esc_html( $date ) . '</td>';
                                            
                                            echo "<td style='display:none;' class='hidden-payload'>";
                                            echo "<div class='mac-content'>" . $content . "</div>";
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
                        <div class="cbah-reports-preview" style="background:#f8fafc; overflow-y:auto; max-height: 550px; padding:24px;">
                            <h2 id="mac-preview-title" style="margin-top:0; color:#0f172a; font-size:20px; margin-bottom:10px;">Select a report</h2>
                            
                            <!-- DUAL VIEW / DOWNLOAD BUTTONS -->
                            <div id="mac-preview-actions" style="display:flex; gap:10px; margin-bottom: 20px; display:none;">
                                <a href="#" id="mac-preview-view-btn" target="_blank" class="cbah-btn-view" style="display:inline-flex; align-items:center; background:#ffffff; border:1px solid #cbd5e1; padding:8px 16px; font-size:12px;">
                                    <span class="dashicons dashicons-visibility" style="margin-right:4px; font-size:14px; width:14px; height:14px;"></span> View Report
                                </a>
                                <a href="#" id="mac-preview-dl-btn" target="_blank" class="cbah-btn-large" style="display:inline-flex; align-items:center; border:1px solid #cbd5e1; padding:8px 16px; font-size:12px;" download>
                                    <span class="dashicons dashicons-download" style="margin-right:4px; font-size:14px; width:14px; height:14px;"></span> Download Report
                                </a>
                            </div>

                            <div id="mac-preview-content" class="cbah-preview-text" style="color:#334155; font-size:14px; line-height:1.6; margin-bottom:20px;"></div>
                            
                            <!-- MINI TABS UI -->
                            <div class="cbah-macro-tabs" style="display:flex; gap:15px; margin-bottom:15px; border-bottom:1px solid #cbd5e1; padding-bottom:0;">
                                <span class="cbah-mac-tab active" data-target="mac-tab-indicators" style="cursor:pointer; font-size:11px; text-transform:uppercase; letter-spacing:0.5px; font-weight:700; color:#0f172a; border-bottom:2px solid #3b82f6; padding-bottom:10px; margin-bottom:-1px;">Key Indicators</span>
                                <span class="cbah-mac-tab" data-target="mac-tab-gov" style="cursor:pointer; font-size:11px; text-transform:uppercase; letter-spacing:0.5px; font-weight:700; color:#64748b; padding-bottom:10px; margin-bottom:-1px; border-bottom:2px solid transparent;">Government Policies</span>
                            </div>

                            <div id="mac-tab-indicators" class="cbah-mac-pane active" style="display:block; background:#ffffff; padding:15px; border:1px solid #e2e8f0; border-radius:6px;">
                                <table class="cbah-data-table cbah-table-hover" style="margin:0;">
                                    <tr><td>Headline Inflation</td><td style="text-align:right;"><strong id="mac-val-inf"></strong>%</td></tr>
                                    <tr><td>Interest Rate (MPR)</td><td style="text-align:right;"><strong id="mac-val-int"></strong>%</td></tr>
                                    <tr><td>Brent Crude Oil</td><td style="text-align:right;">$<strong id="mac-val-crude"></strong>/bbl</td></tr>
                                </table>
                            </div>

                            <div id="mac-tab-gov" class="cbah-mac-pane" style="display:none; background:#ffffff; padding:20px; border:1px solid #e2e8f0; border-radius:6px; color:#334155; font-size:13px; line-height:1.6;">
                                <div id="mac-val-gov"></div>
                            </div>
                            
                        </div>

                    </div>
                </div>
                
                <!-- PRICE LISTS -->
                <div class="cbah-tab-pane" id="tab-pricelists">
                    <div class="cbah-card">
                        <div class="cbah-card-header">DAILY PRICE LISTS</div>
                        <div class="cbah-card-body cbah-no-pad cbah-scroll-y" style="max-height: 600px;">
                            <table class="cbah-data-table cbah-table-hover">
                                <thead><tr><th>Date</th><th style="text-align:right;">Action</th></tr></thead>
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
                                                    echo '<tr><td><strong>' . esc_html($p['date']) . '</strong></td><td style="text-align:right;"><a href="' . esc_url($p['url']) . '" target="_blank" class="cbah-btn-view" style="width:100px; text-align:center;">Download</a></td></tr>';
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
                    <div class="cbah-card" style="margin-bottom:20px;">
                        <div class="cbah-card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                            <span>MARKET DATA SEARCH: <span style="font-size: 10px;text-transform: uppercase;">Search for your prefered stock using the stock name</span></span>
                            <div class="cbah-search-wrapper" style="display:flex; align-items:center; gap:6px;">
                                <span class="dashicons dashicons-search" style="color:#94a3b8; font-size:16px; margin-top:3px;"></span>
                                <input type="text" id="cbah-stock-search" placeholder="Type symbol e.g., MTNN..." class="cbah-search-input" value="MTNN">
                                <button type="button" id="cbah-search-btn" class="cbah-btn-view" style="background:#3b82f6; color:#fff !important; border:none; padding:6px 14px; cursor:pointer;">Search</button>
                            </div>
                        </div>

                        <div style="background:#f1f5f9; padding:10px 18px; font-size:12px; color:#475569; display:flex; align-items:center; justify-content:space-between; border-top:1px solid #e2e8f0; flex-wrap:wrap; gap:8px;">
                            <span><strong>Quick Look:</strong> Select a popular NGX symbol to generate instant charts:</span>
                            <div class="cbah-ticker-pills" style="display:flex; gap:6px;">
                                <button type="button" class="cbah-pill-btn" onclick="cbahTriggerSearch('MTNN')">MTNN</button>
                                <button type="button" class="cbah-pill-btn" onclick="cbahTriggerSearch('DANGCEM')">DANGCEM</button>
                                <button type="button" class="cbah-pill-btn" onclick="cbahTriggerSearch('GTCO')">GTCO</button>
                                <button type="button" class="cbah-pill-btn" onclick="cbahTriggerSearch('ZENITHBANK')">ZENITHBANK</button>
                                <button type="button" class="cbah-pill-btn" onclick="cbahTriggerSearch('AIRTELAFRI')">AIRTELAFRI</button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="cbah-reports-layout">
                        <div class="cbah-card" style="flex:1;">
                            <div class="cbah-card-header">MARKET HIGHLIGHT</div>
                            <div class="cbah-card-body" id="market-data-highlight">
                                <h3>MTNN</h3>
                                <p style="color:#64748b; font-size:13px;">Selected stock indicator. Interactive price history loaded on right.</p>
                            </div>
                        </div>
                        <div class="cbah-card" style="flex:2;">
                            <div class="cbah-card-header">PRICE MOVEMENT CHART</div>
                            <div class="cbah-card-body" id="market-data-chart">
                                <div id="tv_chart_container" style="height:400px; width:100%;"></div>
                            </div>
                        </div>
                    </div>
                </div>
        
                <!--DAILY MARKET SUMMARIES -->
                <div class="cbah-tab-pane" id="tab-summaries">
                    <div class="cbah-card">
                        <div class="cbah-card-header">DAILY MARKET SUMMARIES</div>
                        <div class="cbah-card-body cbah-no-pad cbah-scroll-y" style="max-height: 600px;">
                            <table class="cbah-data-table cbah-table-hover">
                                <thead><tr><th>Date</th><th style="text-align:right;">Action</th></tr></thead>
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
                                                    echo '<tr><td><strong>' . esc_html($s['date']) . '</strong></td><td style="text-align:right;"><a href="' . esc_url($s['url']) . '" target="_blank" class="cbah-btn-view" style="width:100px; text-align:center;">Download</a></td></tr>';
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
                        <div class="cbah-card-body cbah-no-pad cbah-scroll-y" style="max-height: 600px;">
                            <table class="cbah-data-table cbah-table-hover">
                                <thead><tr><th>Company Ticker</th><th>Period</th><th>Published</th><th style="text-align:right;">Document</th></tr></thead>
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
                                            echo '<td style="text-align:right;"><a href="'.esc_url($url).'" target="_blank" class="cbah-btn-view">Download</a></td>';
                                        } else {
                                            echo '<td style="text-align:right; color:#94a3b8; font-size:12px;">No File</td>';
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
                        <div class="cbah-card-body cbah-no-pad cbah-scroll-y" style="max-height: 600px;">
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
        if ( ! function_exists( 'get_field' ) ) { wp_send_json_error(); }
        wp_send_json_success( $this->get_latest_analytics_matrix() );
    }

    // Display on homepage and other pages
    public function render_homepage_snapshot() {    
        if ( ! function_exists( 'get_field' ) ) return '';
        
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
        $raw_vol = get_field( 'turnover_volume', $m_id ) ?: '0';
        $raw_val = get_field( 'turnover_value', $m_id ) ?: '0';
        
        $vol = $this->format_market_number($raw_vol);
        $val = $this->format_market_number($raw_val);

        ob_start();
        ?>
        <div class="marketdata-date" style="display: flex; justify-content: flex-end; align-items: center; font-size: 12px; color: #64748b; margin-bottom: 8px;">
            <span class="dashicons dashicons-clock" style="margin-right: 4px; font-size: 14px; width: 14px; height: 14px;"></span>
            Data as of: <strong class="mkt-date" style="margin-left: 4px;"><?php echo esc_html( $report_date ); ?></strong>
        </div>

        <div class="cbah-home-snapshot" style="margin-top: 0;">
            <div class="cbah-snap-item">
                <span class="cbah-snap-label">NGX All-Share Index</span>
                <span class="cbah-snap-value"><?php echo number_format( $close, 2 ); ?></span>
                <span class="cbah-snap-change <?php echo $asi_color; ?>">
                    <span class="dashicons <?php echo $asi_icon; ?>"></span> 
                    <?php echo number_format( abs( $pct ), 2 ); ?>%
                </span>
            </div>
            
            <div class="cbah-snap-item">
                <span class="cbah-snap-label">Top Gainer</span>
                <span class="cbah-snap-value">
                    <?php echo esc_html( $top_g['ticker'] ); ?>
                    <span style="font-size: 0.85em; font-weight: normal; margin-left: 4px; opacity: 0.9;">
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
                    <span style="font-size: 0.85em; font-weight: normal; margin-left: 4px; opacity: 0.9;">
                        <?php echo esc_html( $top_l_price ); ?>
                    </span>
                </span>
                <span class="cbah-snap-change cbah-txt-red">
                    <span class="dashicons dashicons-arrow-down-alt2"></span> 
                    -<?php echo esc_html( $clean_l_pct ); ?>%
                </span>
            </div>
            
            <div class="cbah-snap-item">
                <span class="cbah-snap-label">Volume / Value</span>
                <span class="cbah-snap-value" style="font-size: 14px;"><?php echo esc_html( $vol ); ?> / ₦<?php echo esc_html( $val ); ?></span>
                <span class="cbah-snap-change" style="color:#64748b;">Market Turnover</span>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

     // Market Ticker
    public function render_market_ticker() {
        if ( ! function_exists( 'get_field' ) ) return '';
        
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
        <div class="cbdata_date" style="display: flex; align-items: stretch; overflow: hidden; background: #ffffff; border: 0; border-radius: 0; margin-bottom: 0;">
            
            <div style="background: #0f172a; color: #f8fafc; padding: 12px 20px; font-size: 12px; font-weight: 600; white-space: nowrap; display: flex; align-items: center; box-shadow: 3px 0 10px rgba(0,0,0,0.1); z-index: 2;">
                <span class="dashicons dashicons-clock" style="margin-right: 6px; font-size: 13px; width: 14px; height: 14px; color: #94a3b8;"></span>
                <?php echo esc_html( $report_date ); ?>
            </div>

            <div class="cbah-ticker-wrapper" style="flex-grow: 1; padding: 12px 0; border: none; background: transparent;">
                <div class="cbah-ticker-track">
                    <div class="cbah-ticker-group"><?php echo $ticker_string; ?></div>
                    <div class="cbah-ticker-group" aria-hidden="true"><?php echo $ticker_string; ?></div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}