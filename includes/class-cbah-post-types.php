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
        add_action( 'admin_menu', array( $this, 'order_research_hub_submenu' ), 999 );

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

    /**
     * Keep the dashboard as the first Research Hub submenu, followed by Settings,
     * then the research content types in their existing order.
     *
     * @return void
     */
    public function order_research_hub_submenu() {
        global $submenu;

        if ( empty( $submenu['cbah-research-hub'] ) || ! is_array( $submenu['cbah-research-hub'] ) ) {
            return;
        }

        $items = $submenu['cbah-research-hub'];
        $dashboard = array();
        $settings = array();
        $others = array();

        foreach ( $items as $item ) {
            $slug = isset( $item[2] ) ? $item[2] : '';
            if ( 'cbah-research-hub' === $slug ) {
                $dashboard[] = $item;
            } elseif ( 'cbah-settings' === $slug ) {
                $settings[] = $item;
            } else {
                $others[] = $item;
            }
        }

        $submenu['cbah-research-hub'] = array_merge( $dashboard, $settings, $others );
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

        if ( ! empty( $latest_market ) && cbah_is_acf_pro_active() && function_exists( 'get_field' ) ) {
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
        
        <?php
        $site_title = trim( (string) get_bloginfo( 'name' ) );
        if ( '' === $site_title ) {
            $site_title = 'Spindle Market Research Hub';
        } else {
            $site_title .= ' Research Hub';
        }
        ?>
        <div class="wrap cbah-dashboard-wrap">
            
            <div class="cbah-dashboard-header">
                <div>
                    <h1 class="cbah-dashboard-title"><?php echo esc_html( $site_title ); ?></h1>
                    <p class="cbah-dashboard-subtitle">
                        <?php echo esc_html( "$greeting, $user_name. Here is your platform overview for $current_date." ); ?>
                    </p>
                </div>
            </div>

            <div class="cbah-inline-25a11240">
                <?php if ( !empty($tv_symbols) ) : ?>
                    <div class="tradingview-widget-container">
                        <div class="tradingview-widget-container__widget"></div>
                        

                    </div>
                <?php else : ?>
                    <div class="cbah-inline-59670f38">
                        <span class="dashicons dashicons-chart-line cbah-inline-1ebfa731"></span>
                        <p class="cbah-inline-c4e078e1">No market data available yet.</p>
                        <p class="cbah-inline-69bc8b18">Please add a Daily Market Report to activate the live NGX ticker.</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="cbah-inline-bf07a2f5">
                
                <div>
                    <div class="cbah-inline-35baa09f">
                        <div class="cbah-inline-bf59e2ae">
                            <h4 class="cbah-inline-8f35850d">Total Market Reports</h4>
                            <span class="cbah-inline-74ad1f5a"><?php echo esc_html( $total_market ); ?></span>
                        </div>
                        <div class="cbah-inline-4b3f93a8">
                            <h4 class="cbah-inline-8f35850d">Published This Week</h4>
                            <span class="cbah-inline-74ad1f5a"><?php echo esc_html( $published_this_week ); ?></span>
                        </div>
                        <div class="cbah-inline-6bdeaeeb">
                            <h4 class="cbah-inline-8f35850d">Corporate Results</h4>
                            <span class="cbah-inline-74ad1f5a"><?php echo esc_html( $total_corp ); ?></span>
                        </div>
                    </div>

                    <h2 class="cbah-inline-e5e4fa30">Research Categories</h2>
                    <div class="cbah-inline-fa5a8685">
                        <?php foreach ( $boxes as $slug => $data ) : ?>
                            <div class="cbah-inline-85494db6">
                                <div class="cbah-inline-7a07f30c">
                                    <span class="dashicons <?php echo esc_attr( $data['icon'] ); ?> cbah-inline-448e4bc2"></span>
                                    <h3 class="cbah-inline-5bcbbc55"><?php echo esc_html( $data['title'] ); ?></h3>
                                </div>
                                <p class="cbah-inline-467706a4">
                                    <?php echo esc_html( $data['desc'] ); ?>
                                </p>
                                <div class="cbah-inline-8b39e52c">
                                    <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . $slug ) ); ?>" class="button" class="cbah-inline-c16becd9">View All</a>
                                    <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . $slug ) ); ?>" class="button button-primary" class="cbah-inline-c16becd9">Add New</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div>
                    <div class="cbah-inline-b4f4c2fe">
                        <h3 class="cbah-inline-5120d8c3">Recent Activity</h3>
                        <?php if ( $recent_posts->have_posts() ) : ?>
                            <ul class="cbah-inline-1908c8d8">
                                <?php while ( $recent_posts->have_posts() ) : $recent_posts->the_post(); 
                                    $pt_obj = get_post_type_object( get_post_type() );
                                ?>
                                    <li class="cbah-inline-a7bac389">
                                        <a href="<?php echo esc_url( get_edit_post_link() ); ?>" class="cbah-inline-6efc1fac">
                                            <?php the_title(); ?>
                                        </a>
                                        <span class="cbah-inline-7e5662ca">
                                            <?php echo esc_html( $pt_obj->labels->singular_name ); ?> &bull; <?php echo esc_html( get_the_modified_date() ); ?>
                                        </span>
                                    </li>
                                <?php endwhile; wp_reset_postdata(); ?>
                            </ul>
                        <?php else : ?>
                            <div class="cbah-inline-a205c22e">
                                <span class="dashicons dashicons-welcome-write-blog cbah-inline-13d5b5f7"></span>
                                <p class="cbah-inline-42d8c5ff">No research reports published yet. Your recent uploads will appear here.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="cbah-inline-6725b22f">
                        <h3 class="cbah-inline-8e7aac1f">Research Resources</h3>
                        <ul class="cbah-inline-024a6d28">
                            <li class="cbah-inline-2d98adf5"><a class="cbah-inline-0f9243d8" href="<?php echo esc_url( admin_url( 'edit.php?post_type=cbah_market_report' ) ); ?>"><span class="dashicons dashicons-book cbah-inline-57052121"></span> Market Reports</a></li>
                            <li class="cbah-inline-2d98adf5"><a class="cbah-inline-0f9243d8" href="<?php echo esc_url( admin_url( 'admin.php?page=cbah-settings' ) ); ?>"><span class="dashicons dashicons-admin-settings cbah-inline-57052121"></span> Market Data Settings</a></li>
                            <li class="cbah-inline-648149ce"><a class="cbah-inline-0f9243d8" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=cbah_market_report' ) ); ?>"><span class="dashicons dashicons-plus-alt cbah-inline-57052121"></span> Add Research Report</a></li>
                        </ul>
                    </div>
                </div>

            </div>
        </div>
        <?php
    }
}