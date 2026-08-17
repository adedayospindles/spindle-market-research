<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


class CBAH_Post_Types {

    public function __construct() {
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
    }

    public function enqueue_admin_assets( $hook_suffix ) {
        if ( 'toplevel_page_cbah-research-hub' !== $hook_suffix ) {
            return;
        }

        wp_enqueue_script(
            'cbah-dashboard-admin',
            CBAH_PLUGIN_URL . 'admin/js/cbah-dashboard.js',
            array(),
            CBAH_VERSION,
            true
        );

        wp_enqueue_style(
            'cbah-dashboard-admin',
            CBAH_PLUGIN_URL . 'admin/css/cbah-dashboard.css',
            array(),
            CBAH_VERSION
        );
    }

    public function register_structures() {
        
        // 1. HOOK TO CREATE THE CUSTOM DASHBOARD MENU
        add_action( 'admin_menu', array( $this, 'create_dashboard_menu' ) );

        // 2. DEFINE YOUR 6 REPORTS
        $registries = array(
            'cbah_market_report' => array( 'singular' => 'Daily Market', 'plural' => 'Daily Market Reports' ),
            'cbah_equity'        => array( 'singular' => 'Equity Research', 'plural' => 'Equity Research' ),
            'cbah_sector'        => array( 'singular' => 'Sector Report', 'plural' => 'Sector Reports' ),
            'cbah_macro'         => array( 'singular' => 'Economic & Macro', 'plural' => 'Economic & Macro' ),
            'cbah_corporate'     => array( 'singular' => 'Corporate Result', 'plural' => 'Corporate Results' ),
            'cbah_dividend'      => array( 'singular' => 'Dividend Record', 'plural' => 'Dividend Tracker' ),
        );

        // 3. REGISTER ALL POST TYPES AS SUB-MENUS
        foreach ( $registries as $key => $props ) {
            register_post_type( $key, array(
                'labels' => array(
                    'name'               => $props['plural'],
                    'singular_name'      => $props['singular'],
                    'all_items'          => $props['plural'], 
                    'add_new_item'       => 'Add New ' . $props['singular'],
                    'edit_item'          => 'Edit ' . $props['singular'],
                    'search_items'       => 'Search ' . $props['plural'],
                ),
                'public'             => true,
                'has_archive'        => true,
                'show_in_menu'       => 'cbah-research-hub', // Nests them safely in the hub
                'show_in_rest'       => true,
                'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
                'rewrite'            => array( 'slug' => str_replace('cbah_', 'capital-', $key) ),
            ));
        }
    }

    // 4. CREATE THE MASTER MENU HUB (FIXED ROUTING)
    public function create_dashboard_menu() {
        // Create the Main Parent Menu
        add_menu_page(
            'Research Hub',
            'Research Hub',
            'edit_posts',
            'cbah-research-hub', 
            array( $this, 'render_dashboard_view' ), 
            'dashicons-chart-pie',
            30
        );

        // Explicitly create the "Dashboard" link and lock it to the correct view page
        add_submenu_page(
            'cbah-research-hub',          // Parent slug
            'Dashboard',                  // Page title
            'Dashboard',                  // Menu title
            'edit_posts',                 // Capability
            'cbah-research-hub',          // Menu slug (Must match parent to act as the default page)
            array( $this, 'render_dashboard_view' ) 
        );
    }

    // 5. RENDER THE PREMIUM DASHBOARD (Beautiful UI is unchanged)
    public function render_dashboard_view() {
        
        // --- DATA LOGIC: Dynamic Greeting ---
        $current_user = wp_get_current_user();
        $user_name = !empty($current_user->user_firstname) ? $current_user->user_firstname : $current_user->display_name;
        $hour = current_time('H');
        if ($hour < 12) $greeting = 'Good morning';
        elseif ($hour < 17) $greeting = 'Good afternoon';
        else $greeting = 'Good evening';
        $current_date = current_time('l, F j, Y');

        // --- DATA LOGIC: Analytics Counters ---
        $total_market = wp_count_posts('cbah_market_report')->publish ?? 0;
        $total_corp   = wp_count_posts('cbah_corporate')->publish ?? 0;
        
        $week_query = new WP_Query(array(
            'post_type'  => array('cbah_market_report', 'cbah_equity', 'cbah_sector', 'cbah_macro', 'cbah_corporate', 'cbah_dividend'),
            'date_query' => array( array('after' => '1 week ago') ),
            'fields'     => 'ids',
            'post_status'=> 'publish'
        ));
        $published_this_week = $week_query->found_posts;

        // --- DATA LOGIC: Recent Activity ---
        $recent_posts = new WP_Query(array(
            'post_type'      => array('cbah_market_report', 'cbah_equity', 'cbah_sector', 'cbah_macro', 'cbah_corporate', 'cbah_dividend'),
            'posts_per_page' => 5,
            'orderby'        => 'modified',
            'order'          => 'DESC'
        ));

        // --- DATA LOGIC: Dynamic TradingView Symbols ---
        $tv_symbols = array();
        
        // Fetch the absolute latest market report
        $latest_market = get_posts(array(
            'post_type' => 'cbah_market_report',
            'posts_per_page' => 1,
            'post_status' => 'publish'
        ));

        if ( !empty($latest_market) && function_exists('get_field') ) {
            $m_id = $latest_market[0]->ID;
            $gainers = get_field('market_gainers', $m_id) ?: array();
            $losers  = get_field('market_losers', $m_id) ?: array();
            
            // Grab up to 5 gainers
            $gainers = array_slice($gainers, 0, 5);
            foreach ($gainers as $g) {
                if (!empty($g['ticker'])) {
                    $clean_ticker = str_replace(' ', '', strtoupper(trim($g['ticker'])));
                    // CHANGE THIS LINE: Use 'NSENG:' instead of 'NGX:'
                    $tv_symbols[] = array( 'description' => $g['ticker'], 'proName' => 'NSENG:' . $clean_ticker );
                }
            }
            
            // Grab up to 5 losers
            $losers = array_slice($losers, 0, 5);
            foreach ($losers as $l) {
                if (!empty($l['ticker'])) {
                    $clean_ticker = str_replace(' ', '', strtoupper(trim($l['ticker'])));
                    // CHANGE THIS LINE: Use 'NSENG:' instead of 'NGX:'
                    $tv_symbols[] = array( 'description' => $l['ticker'], 'proName' => 'NSENG:' . $clean_ticker );
                }
            }
        }

        // Only encode if we actually have symbols to display
        wp_add_inline_script(
            'cbah-dashboard-admin',
            'window.CBAH_ADMIN_DATA = ' . wp_json_encode(
                array(
                    'tradingView' => array(
                        'symbols' => $tv_symbols,
                    ),
                ),
                JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
            ) . ';',
            'before'
        );

        // UI Grid Data
        $boxes = array(
            'cbah_market_report' => array( 'title' => 'Daily Market Reports', 'icon' => 'dashicons-chart-bar', 'desc' => 'Manage and upload daily NGX market reports.' ),
            'cbah_equity'        => array( 'title' => 'Equity Research', 'icon' => 'dashicons-analytics', 'desc' => 'Publish in-depth equity and specific stock analysis.' ),
            'cbah_sector'        => array( 'title' => 'Sector Reports', 'icon' => 'dashicons-category', 'desc' => 'Manage broad industry and sector tracking reports.' ),
            'cbah_macro'         => array( 'title' => 'Economic & Macro', 'icon' => 'dashicons-welcome-widgets-menus', 'desc' => 'Upload macroeconomic and fiscal policy overviews.' ),
            'cbah_corporate'     => array( 'title' => 'Corporate Results', 'icon' => 'dashicons-portfolio', 'desc' => 'Track and post public company earnings results.' ),
            'cbah_dividend'      => array( 'title' => 'Dividend Tracker', 'icon' => 'dashicons-money-alt', 'desc' => 'Manage historical and upcoming dividend payouts.' ),
        );
        ?>
        
        <div class="wrap cbah-dashboard-wrap" style="margin-top: 20px; max-width: 1400px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
            
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px;">
                <div>
                    <h1 style="font-weight: 700; font-size: 28px; color: #0f172a; margin: 0 0 8px 0;">Capital Bancorp Research Hub</h1>
                    <p style="margin: 0; font-size: 15px; color: #64748b;">
                        <?php echo esc_html("$greeting, $user_name. Here is your platform overview for $current_date."); ?>
                    </p>
                </div>
            </div>

            <div style="margin-bottom: 30px; border-radius: 6px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05); background-color: #ffffff;">
                <?php if ( !empty($tv_symbols) ) : ?>
                    <div class="tradingview-widget-container">
                        <div class="tradingview-widget-container__widget"></div>
                        

                    </div>
                <?php else : ?>
                    <div style="padding: 24px; text-align: center; background-color: #f8fafc; display: flex; flex-direction: column; align-items: center;">
                        <span class="dashicons dashicons-chart-line" style="font-size: 32px; width: 32px; height: 32px; color: #94a3b8; margin-bottom: 12px;"></span>
                        <p style="margin: 0; color: #475569; font-size: 14px; font-weight: 500;">No market data available yet.</p>
                        <p style="margin: 4px 0 0 0; color: #64748b; font-size: 13px;">Please add a Daily Market Report to activate the live NGX ticker.</p>
                    </div>
                <?php endif; ?>
            </div>

            <div style="display: grid; grid-template-columns: 2.5fr 1fr; gap: 30px;">
                
                <div>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px;">
                        <div style="background: #ffffff; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; border-left: 4px solid #0ea5e9; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                            <h4 style="margin: 0 0 8px 0; color: #64748b; font-size: 13px; text-transform: uppercase;">Total Market Reports</h4>
                            <span style="font-size: 28px; font-weight: 700; color: #0f172a;"><?php echo esc_html( $total_market ); ?></span>
                        </div>
                        <div style="background: #ffffff; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; border-left: 4px solid #10b981; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                            <h4 style="margin: 0 0 8px 0; color: #64748b; font-size: 13px; text-transform: uppercase;">Published This Week</h4>
                            <span style="font-size: 28px; font-weight: 700; color: #0f172a;"><?php echo esc_html( $published_this_week ); ?></span>
                        </div>
                        <div style="background: #ffffff; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; border-left: 4px solid #6366f1; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                            <h4 style="margin: 0 0 8px 0; color: #64748b; font-size: 13px; text-transform: uppercase;">Corporate Results</h4>
                            <span style="font-size: 28px; font-weight: 700; color: #0f172a;"><?php echo esc_html( $total_corp ); ?></span>
                        </div>
                    </div>

                    <h2 style="font-size: 18px; font-weight: 600; margin-bottom: 16px; color: #1e293b;">Research Categories</h2>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
                        <?php foreach ( $boxes as $slug => $data ) : ?>
                            <div style="background: #ffffff; border: 1px solid #e2e8f0; padding: 24px; border-radius: 8px; display: flex; flex-direction: column; transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,0.08)'" onmouseout="this.style.boxShadow='none'">
                                <div style="display: flex; align-items: center; margin-bottom: 12px;">
                                    <span class="dashicons <?php echo esc_attr( $data['icon'] ); ?>" style="font-size: 24px; width: 24px; height: 24px; color: #0f172a; margin-right: 12px;"></span>
                                    <h3 style="margin: 0; font-size: 16px; font-weight: 600; color: #0f172a;"><?php echo esc_html( $data['title'] ); ?></h3>
                                </div>
                                <p style="color: #64748b; margin-top: 0; font-size: 13px; line-height: 1.5; flex-grow: 1;">
                                    <?php echo esc_html( $data['desc'] ); ?>
                                </p>
                                <div style="margin-top: 20px; border-top: 1px solid #f1f5f9; padding-top: 16px; display: flex; gap: 10px;">
                                    <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . $slug ) ); ?>" class="button" style="width: 50%; text-align: center;">View All</a>
                                    <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . $slug ) ); ?>" class="button button-primary" style="width: 50%; text-align: center;">Add New</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div>
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 24px; margin-bottom: 30px;">
                        <h3 style="font-size: 16px; font-weight: 600; margin: 0 0 16px 0; color: #0f172a; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">Recent Activity</h3>
                        <?php if ( $recent_posts->have_posts() ) : ?>
                            <ul style="list-style: none; padding: 0; margin: 0;">
                                <?php while ( $recent_posts->have_posts() ) : $recent_posts->the_post(); 
                                    $pt_obj = get_post_type_object( get_post_type() );
                                ?>
                                    <li style="margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px solid #f1f5f9;">
                                        <a href="<?php echo esc_url( get_edit_post_link() ); ?>" style="text-decoration: none; font-weight: 600; color: #2271b1; display: block; margin-bottom: 4px;">
                                            <?php the_title(); ?>
                                        </a>
                                        <span style="font-size: 12px; color: #64748b; background: #f8fafc; padding: 2px 6px; border-radius: 4px;">
                                            <?php echo esc_html( $pt_obj->labels->singular_name ); ?> &bull; <?php echo esc_html( get_the_modified_date() ); ?>
                                        </span>
                                    </li>
                                <?php endwhile; wp_reset_postdata(); ?>
                            </ul>
                        <?php else : ?>
                            <div style="text-align: center; padding: 20px 0;">
                                <span class="dashicons dashicons-welcome-write-blog" style="font-size: 24px; width: 24px; height: 24px; color: #cbd5e1; margin-bottom: 8px;"></span>
                                <p style="color: #64748b; font-size: 13px; margin: 0;">No research reports published yet. Your recent uploads will appear here.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 24px;">
                        <h3 style="font-size: 16px; font-weight: 600; margin: 0 0 16px 0; color: #0f172a;">Analyst Resources</h3>
                        <ul style="list-style: none; padding: 0; margin: 0; font-size: 13px;">
                            <li style="margin-bottom: 10px;"><a href="#" style="text-decoration: none; color: #2271b1;"><span class="dashicons dashicons-book" style="font-size: 14px; margin-top:2px;"></span> Formatting Guidelines</a></li>
                            <li style="margin-bottom: 10px;"><a href="#" style="text-decoration: none; color: #2271b1;"><span class="dashicons dashicons-calendar-alt" style="font-size: 14px; margin-top:2px;"></span> NGX Market Holidays</a></li>
                            <li style="margin-bottom: 0;"><a href="" style="text-decoration: none; color: #2271b1;"><span class="dashicons dashicons-email" style="font-size: 14px; margin-top:2px;"></span> IT Support Request</a></li>
                        </ul>
                    </div>
                </div>

            </div>
        </div>
        <?php
    }
}