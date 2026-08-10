<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


class CBAH_ACF_Fields {
    
    public function __construct() {
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
    }

    public function enqueue_admin_assets() {
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

        if ( ! $screen || ! in_array( $screen->post_type, array( 'cbah_market_report' ), true ) ) {
            return;
        }

        wp_enqueue_script(
            'cbah-acf-admin',
            CBAH_PLUGIN_URL . 'admin/js/cbah-acf-admin.js',
            array( 'jquery' ),
            CBAH_VERSION,
            true
        );

        wp_enqueue_style(
            'cbah-acf-admin',
            CBAH_PLUGIN_URL . 'admin/css/cbah-acf-admin.css',
            array(),
            CBAH_VERSION
        );
    }

    public function register_local_field_groups() {
        if ( ! function_exists( 'acf_add_local_field_group' ) ) return;

       // 1. MARKET AGGREGATES
        acf_add_local_field_group( array(
            'key' => 'group_cbah_market_metrics', 'title' => 'Daily Market Core Aggregates',
            'fields' => array(
                array( 'key' => 'field_m_open', 'label' => 'NGX Opening Index', 'name' => 'ngx_open', 'type' => 'number' ),
                array( 'key' => 'field_m_close', 'label' => 'NGX Closing Index', 'name' => 'ngx_close', 'type' => 'number' ),
                array( 'key' => 'field_m_cap', 'label' => 'Market Capitalization', 'name' => 'market_cap', 'type' => 'text', 'instructions' => 'e.g. 56 Billion or 56,000,000,000' ),
                array( 'key' => 'field_m_vol', 'label' => 'Turnover Volume (M)', 'name' => 'turnover_volume', 'type' => 'text' ),
                array( 'key' => 'field_m_val', 'label' => 'Turnover Value (B₦)', 'name' => 'turnover_value', 'type' => 'text' ),
                array( 'key' => 'field_h_1d', 'label' => '1D Chart (Comma separated)', 'name' => 'hist_1d', 'type' => 'text' ),
                array( 'key' => 'field_h_5d', 'label' => '5D Chart (Comma separated)', 'name' => 'hist_5d', 'type' => 'text' ),
                array( 'key' => 'field_h_1m', 'label' => '1M Chart (Comma separated)', 'name' => 'hist_1m', 'type' => 'text' ),
                array( 'key' => 'field_h_ytd', 'label' => 'YTD Chart (Comma separated)', 'name' => 'hist_ytd', 'type' => 'text' ),
                array(
                    'key' => 'field_m_gainers', 'label' => 'Top Gainers', 'name' => 'market_gainers', 'type' => 'repeater', 'layout' => 'table',
                    'sub_fields' => array( 
                        array( 'key' => 'field_g_tick', 'label' => 'Symbol', 'name' => 'ticker', 'type' => 'text' ), 
                        array( 'key' => 'field_g_price', 'label' => 'Price (₦)', 'name' => 'price', 'type' => 'text' ), 
                        array( 'key' => 'field_g_pct', 'label' => 'Change (%)', 'name' => 'percentage', 'type' => 'text' ) 
                    ),
                ),
                array(
                    'key' => 'field_m_losers', 'label' => 'Top Losers', 'name' => 'market_losers', 'type' => 'repeater', 'layout' => 'table',
                    'sub_fields' => array( 
                        array( 'key' => 'field_l_tick', 'label' => 'Symbol', 'name' => 'ticker', 'type' => 'text' ), 
                        array( 'key' => 'field_l_price', 'label' => 'Price (₦)', 'name' => 'price', 'type' => 'text' ), 
                        array( 'key' => 'field_l_pct', 'label' => 'Change (%)', 'name' => 'percentage', 'type' => 'text' ) 
                    ),
                ),
                /*array(
                    'key' => 'field_m_forex', 'label' => 'Foreign Exchange', 'name' => 'forex_rates', 'type' => 'repeater', 'layout' => 'table',
                    'sub_fields' => array( array( 'key' => 'field_fx_pair', 'label' => 'Pair', 'name' => 'pair', 'type' => 'text' ), array( 'key' => 'field_fx_rate', 'label' => 'Rate (₦)', 'name' => 'rate', 'type' => 'text' ) ),
                ),*/
                array(
                    'key' => 'field_m_fixed', 'label' => 'Fixed Income', 'name' => 'fixed_income', 'type' => 'repeater', 'layout' => 'table',
                    'sub_fields' => array( 
                        array( 'key' => 'field_fi_type', 'label' => 'Type', 'name' => 'fi_type', 'type' => 'select', 'choices' => array('Bonds'=>'Bonds', 'Bills'=>'Bills') ),
                        array( 'key' => 'field_fi_tenor', 'label' => 'Instrument Name', 'name' => 'tenor', 'type' => 'text' ),
                        array( 'key' => 'field_fi_link', 'label' => 'Action URL', 'name' => 'action_link', 'type' => 'url' ),
                    ),
                ),
                array(
                    'key' => 'field_m_summaries', 'label' => 'Daily Market Summaries (Files)', 'name' => 'market_summaries', 'type' => 'repeater', 'layout' => 'table',
                    'sub_fields' => array( array( 'key' => 'field_ms_date', 'label' => 'Date', 'name' => 'date', 'type' => 'date_picker', 'display_format' => 'Y-m-d', 'return_format' => 'Y-m-d' ), array( 'key' => 'field_ms_url', 'label' => 'File URL', 'name' => 'url', 'type' => 'url' ) ),
                ),
                array(
                    'key' => 'field_m_pricelists', 'label' => 'Daily Price Lists (Files)', 'name' => 'daily_price_lists', 'type' => 'repeater', 'layout' => 'table',
                    'sub_fields' => array( array( 'key' => 'field_pl_date', 'label' => 'Date', 'name' => 'date', 'type' => 'date_picker', 'display_format' => 'Y-m-d', 'return_format' => 'Y-m-d' ), array( 'key' => 'field_pl_url', 'label' => 'File URL', 'name' => 'url', 'type' => 'url' ) ),
                ),
            ),
            'location' => array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'cbah_market_report' ) ) ),
        ));
        
        
        // 2. EQUITY APPRAISALS
        acf_add_local_field_group( array(
            'key' => 'group_cbah_equity_metrics', 'title' => 'Equity Research Details',
            'fields' => array(
                array( 'key' => 'field_e_rating', 'label' => 'Strategy', 'name' => 'equity_recommendation', 'type' => 'select', 'choices' => array( 'Buy'=>'Buy', 'Hold'=>'Hold', 'Sell'=>'Sell' ) ),
                array( 'key' => 'field_e_target', 'label' => 'Target Price (₦)', 'name' => 'equity_target_price', 'type' => 'text' ),
                array( 'key' => 'field_e_analysis', 'label' => 'Company Analysis', 'name' => 'company_analysis', 'type' => 'textarea', 'rows' => 4 ),
                array( 'key' => 'field_e_financial', 'label' => 'Financial Performance Notes', 'name' => 'financial_performance', 'type' => 'textarea', 'rows' => 3 ),
                array( 'key' => 'field_e_file', 'label' => 'Full Report File URL (PDF)', 'name' => 'report_file_url', 'type' => 'url' ),
            ),
            'location' => array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'cbah_equity' ) ) ),
        ));

        // 3. SECTOR PERFORMANCE

        acf_add_local_field_group( array(
            'key' => 'group_cbah_sector_metrics', 'title' => 'Sector Allocation Metrics',
            'fields' => array(
                array( 'key' => 'field_s_bank', 'label' => 'Banking Index (%)', 'name' => 'performance_banking', 'type' => 'number' ),
                array( 'key' => 'field_s_ins', 'label' => 'Insurance Index (%)', 'name' => 'performance_insurance', 'type' => 'number' ),
                array( 'key' => 'field_s_ind', 'label' => 'Industrial Index (%)', 'name' => 'performance_industrial', 'type' => 'number' ),
                array( 'key' => 'field_s_oil', 'label' => 'Oil & Gas Index (%)', 'name' => 'performance_oil_gas', 'type' => 'number' ),
                array( 'key' => 'field_s_fmcg', 'label' => 'Consumer Goods Index (%)', 'name' => 'performance_consumer_goods', 'type' => 'number' ),
                
                // NEW: Global Sector Report Download
                array( 'key' => 'field_s_main_url', 'label' => 'Main Sector Report File (PDF URL)', 'name' => 'main_sector_report_url', 'type' => 'url', 'instructions' => 'Optional: Paste the main report link here instead of inside the text editor for cleaner formatting.' ),

                array(
                    'key' => 'field_sector_repeater', 
                    'label' => 'Sector-by-Sector Breakdown Reports', 
                    'name' => 'sector_breakdown_reports', 
                    'type' => 'repeater', 
                    'layout' => 'block', 
                    'button_label' => 'Add Sector Report',
                    'collapsed' => 'field_srep_name', 
                    'sub_fields' => array(
                        array( 'key' => 'field_srep_name', 'label' => 'Sector Name', 'name' => 'sector_name', 'type' => 'select', 'choices' => array( 'Banking' => 'Banking', 'Insurance' => 'Insurance', 'Industrial' => 'Industrial', 'Oil & Gas' => 'Oil & Gas', 'Consumer Goods' => 'Consumer Goods' ) ),
                        array( 'key' => 'field_srep_rec', 'label' => 'Recommendation', 'name' => 'recommendation', 'type' => 'select', 'choices' => array( 'Overweight' => 'Overweight', 'Neutral' => 'Neutral', 'Underweight' => 'Underweight' ) ),
                        array( 'key' => 'field_srep_drivers', 'label' => 'Growth Drivers', 'name' => 'growth_drivers', 'type' => 'textarea', 'rows' => 3 ),
                        array( 'key' => 'field_srep_outlook', 'label' => 'Sector Outlook', 'name' => 'sector_outlook', 'type' => 'wysiwyg', 'media_upload' => 0 ),
                        array( 'key' => 'field_srep_pdf', 'label' => 'Downloadable Report (PDF URL)', 'name' => 'sector_pdf', 'type' => 'url' ),
                    )
                ),
            ),
            'location' => array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'cbah_sector' ) ) ),
        ));

        // 4. MACRO INDICATORS
        acf_add_local_field_group( array(
            'key' => 'group_cbah_macro_metrics', 'title' => 'Macroeconomic Environment',
            'fields' => array(
                array( 'key' => 'field_mac_inf', 'label' => 'Headline Inflation (%)', 'name' => 'macro_inflation', 'type' => 'text' ),
                array( 'key' => 'field_mac_int', 'label' => 'Interest Rate (MPR) (%)', 'name' => 'macro_interest_rate', 'type' => 'text' ),
                array( 'key' => 'field_mac_crude', 'label' => 'Brent Crude Oil Price ($/bbl)', 'name' => 'macro_crude_oil', 'type' => 'text' ),
                
                array(
                    'key' => 'field_mac_forex', 'label' => 'Foreign Exchange Rates', 'name' => 'macro_forex_rates', 'type' => 'repeater', 'layout' => 'table', 'button_label' => 'Add Currency Pair',
                    'sub_fields' => array( 
                        array( 'key' => 'field_mac_fx_pair', 'label' => 'Currency Pair (e.g. USD/NGN)', 'name' => 'pair', 'type' => 'text' ), 
                        array( 'key' => 'field_mac_fx_rate', 'label' => 'Rate (₦)', 'name' => 'rate', 'type' => 'text' ) 
                    ),
                ),
                
                array( 'key' => 'field_mac_gov', 'label' => 'Government Policies & Overview', 'name' => 'government_policies', 'type' => 'textarea', 'rows' => 4 ),
                array( 'key' => 'field_mac_url', 'label' => 'Macro Report File URL (PDF)', 'name' => 'macro_report_url', 'type' => 'url', 'instructions' => 'Link to the full macroeconomic PDF document.' ),
            ),
            'location' => array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'cbah_macro' ) ) ),
        ));

        // 5. CORPORATE RESULTS
        acf_add_local_field_group( array(
            'key' => 'group_cbah_corporate', 'title' => 'Corporate Result Details',
            'fields' => array(
                array( 'key' => 'field_cr_ticker', 'label' => 'Company Ticker', 'name' => 'corp_ticker', 'type' => 'text' ),
                array( 'key' => 'field_cr_period', 'label' => 'Financial Period (e.g. Q1 2026)', 'name' => 'corp_period', 'type' => 'text' ),
                array( 'key' => 'field_cr_url', 'label' => 'Result Document URL (PDF)', 'name' => 'corp_url', 'type' => 'url' ),
            ),
            'location' => array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'cbah_corporate' ) ) ),
        ));

        // 6. DIVIDEND TRACKER
        acf_add_local_field_group( array(
            'key' => 'group_cbah_dividend', 'title' => 'Dividend Information',
            'fields' => array(
                array( 'key' => 'field_div_ticker', 'label' => 'Company Ticker', 'name' => 'div_ticker', 'type' => 'text' ),
                array( 'key' => 'field_div_amount', 'label' => 'Dividend Amount (₦)', 'name' => 'div_amount', 'type' => 'text' ),
                array( 'key' => 'field_div_closure', 'label' => 'Closure Date', 'name' => 'div_closure', 'type' => 'date_picker', 'display_format' => 'Y-m-d', 'return_format' => 'Y-m-d' ),
                array( 'key' => 'field_div_payment', 'label' => 'Payment Date', 'name' => 'div_payment', 'type' => 'date_picker', 'display_format' => 'Y-m-d', 'return_format' => 'Y-m-d' ),
            ),
            'location' => array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'cbah_dividend' ) ) ),
        ));
    }
}