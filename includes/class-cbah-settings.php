<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Plugin settings, including scoped typography controls.
 */
class CBAH_Settings {

    const OPTION_KEY = 'cbah_settings';

    /**
     * Google Fonts available in the administrator font selectors.
     *
     * The selected family is requested directly from Google Fonts and then
     * applied through the plugin's scoped typography variables on both the
     * frontend and the plugin administration screens.
     *
     * @return array<string> Font family names.
     */
    public static function get_font_choices() {
        return array(
            'Josefin Sans',
            'Poppins',
            'Inter',
            'Roboto',
            'Open Sans',
            'Lato',
            'Montserrat',
            'Nunito',
            'Nunito Sans',
            'DM Sans',
            'Manrope',
            'Source Sans 3',
            'Source Serif 4',
            'Work Sans',
            'Plus Jakarta Sans',
            'Outfit',
            'Figtree',
            'Sora',
            'Space Grotesk',
            'Space Mono',
            'Raleway',
            'Noto Sans',
            'Noto Serif',
            'Oswald',
            'Merriweather',
            'Merriweather Sans',
            'Playfair Display',
            'Libre Caslon Text',
            'Libre Baskerville',
            'Libre Franklin',
            'Cormorant Garamond',
            'Crimson Text',
            'Lora',
            'Bitter',
            'Roboto Slab',
            'IBM Plex Sans',
            'IBM Plex Serif',
            'Archivo',
            'Barlow',
            'Barlow Condensed',
            'Cabin',
            'Catamaran',
            'Quicksand',
            'Mulish',
            'Karla',
            'Titillium Web',
            'Jost',
            'Josefin Slab',
            'League Spartan',
            'Bebas Neue',
            'Anton',
            'Ubuntu',
            'Rubik',
            'PT Sans',
            'PT Serif',
            'DM Serif Display',
            'Source Code Pro',
            'Inconsolata',
        );
    }

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_settings_page' ), 20 );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        add_filter( 'admin_body_class', array( $this, 'add_admin_body_class' ) );
    }

    public static function get_settings() {
        $defaults = array(
            'body_font'    => 'Josefin Sans',
            'heading_font' => 'Poppins',
            'ticker_speed' => 60,
            'body_font_admin'    => true,
            'heading_font_admin' => true,
                    );

        $saved = get_option( self::OPTION_KEY, array() );
        $saved = is_array( $saved ) ? $saved : array();

        // Correct the temporary 1.8.1 typography migration from the immediately
        // preceding build. Only an option carrying that build's marker is eligible;
        // an administrator's other explicitly selected heading font is preserved.
        if ( isset( $saved['typography_defaults_v181'] ) && ! isset( $saved['typography_defaults_v181_corrected'] ) ) {
            if ( isset( $saved['heading_font'] ) && 'Josefin Sans' === $saved['heading_font'] ) {
                $saved['heading_font'] = 'Poppins';
            }
            unset( $saved['typography_defaults_v181'] );
            $saved['typography_defaults_v181_corrected'] = 1;
            update_option( self::OPTION_KEY, $saved );
        }

        return wp_parse_args( $saved, $defaults );
    }

    public static function sanitize_font( $font ) {
        $font = sanitize_text_field( (string) $font );
        $font = preg_replace( '/[^A-Za-z0-9 ._-]/', '', $font );
        $font = trim( preg_replace( '/\s+/', ' ', $font ) );

        return $font ? $font : 'Josefin Sans';
    }

    private function get_settings_page_title() {
        $site_title = trim( (string) get_bloginfo( 'name' ) );
        return '' !== $site_title
            ? $site_title . ' Research Hub Settings'
            : 'Market Research Hub Settings';
    }

    public function register_settings_page() {
        add_submenu_page(
            'cbah-research-hub',
            $this->get_settings_page_title(),
            __( 'Settings', 'spindle-market-research' ),
            'manage_options',
            'cbah-settings',
            array( $this, 'render_settings_page' )
        );
    }

    public function register_settings() {
        register_setting(
            'cbah_settings_group',
            self::OPTION_KEY,
            array(
                'type'              => 'array',
                'sanitize_callback' => array( $this, 'sanitize_settings' ),
                'default'           => array(
                    'body_font'    => 'Josefin Sans',
                    'heading_font' => 'Poppins',
                    'ticker_speed' => 60,
                    'body_font_admin'    => true,
                    'heading_font_admin' => true,
                                    ),
            )
        );
    }

    public function sanitize_settings( $input ) {
        $input = is_array( $input ) ? $input : array();

        return array(
            'body_font'    => self::sanitize_font( isset( $input['body_font'] ) ? $input['body_font'] : 'Josefin Sans' ),
            'heading_font' => self::sanitize_font( isset( $input['heading_font'] ) ? $input['heading_font'] : 'Poppins' ),
            'ticker_speed' => min( 200, max( 10, absint( isset( $input['ticker_speed'] ) ? $input['ticker_speed'] : 60 ) ) ),
            'body_font_admin'    => ! empty( $input['body_font_admin'] ),
            'heading_font_admin' => ! empty( $input['heading_font_admin'] ),
                    );
    }

    public function enqueue_admin_assets( $hook_suffix ) {
        if ( ! $this->is_plugin_admin_screen( $hook_suffix ) ) {
            return;
        }

        wp_enqueue_style(
            'cbah-admin-typography',
            CBAH_PLUGIN_URL . 'admin/css/cbah-typography.css',
            array(),
            CBAH_VERSION
        );

        $settings = self::get_settings();
        $admin_body_font    = ! empty( $settings['body_font_admin'] ) ? $settings['body_font'] : '';
        $admin_heading_font = ! empty( $settings['heading_font_admin'] ) ? $settings['heading_font'] : '';
        $this->enqueue_google_fonts( $admin_body_font, $admin_heading_font, 'cbah-admin-google-fonts' );
        $this->add_typography_variables( 'cbah-admin-typography', $admin_body_font, $admin_heading_font );
    }

    public function add_admin_body_class( $classes ) {
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( ! $screen ) {
            return $classes;
        }

        if ( false !== strpos( (string) $screen->id, 'cbah-settings' ) || false !== strpos( (string) $screen->id, 'cbah-research-hub' ) || in_array( $screen->post_type, array( 'cbah_market_report', 'cbah_equity', 'cbah_sector', 'cbah_macro', 'cbah_corporate', 'cbah_dividend' ), true ) ) {
            $classes .= ' cbah-plugin-admin-screen';

            $settings = self::get_settings();
            if ( ! empty( $settings['body_font_admin'] ) ) {
                $classes .= ' cbah-admin-use-body-font';
            }
            if ( ! empty( $settings['heading_font_admin'] ) ) {
                $classes .= ' cbah-admin-use-heading-font';
            }
        }

        return $classes;
    }

    private function is_plugin_admin_screen( $hook_suffix ) {
        if ( false !== strpos( (string) $hook_suffix, 'cbah-settings' ) || false !== strpos( (string) $hook_suffix, 'cbah-research-hub' ) ) {
            return true;
        }

        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( ! $screen ) {
            return false;
        }

        return in_array(
            $screen->post_type,
            array( 'cbah_market_report', 'cbah_equity', 'cbah_sector', 'cbah_macro', 'cbah_corporate', 'cbah_dividend' ),
            true
        );
    }

    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $settings = self::get_settings();
        $choices  = self::get_font_choices();

        // Preserve an existing saved family if it came from an older custom entry
        // and is not part of the curated dropdown list.
        foreach ( array( $settings['body_font'], $settings['heading_font'] ) as $saved_font ) {
            if ( $saved_font && ! in_array( $saved_font, $choices, true ) ) {
                $choices[] = $saved_font;
            }
        }
        ?>
        <div class="wrap cbah-settings-wrap">
            <h1><?php echo esc_html( $this->get_settings_page_title() ); ?></h1>
            <p class="description">
                <?php echo esc_html__( 'Set one typography system for the Spindle Market Research Hub frontend and plugin administration screens.', 'spindle-market-research' ); ?>
            </p>

            <form method="post" action="options.php">
                <?php settings_fields( 'cbah_settings_group' ); ?>

                <div class="cbah-settings-card">
                    <h2><?php echo esc_html__( 'Typography', 'spindle-market-research' ); ?></h2>
                    <p><?php echo esc_html__( 'Defaults: Josefin Sans for Body / Text and Poppins for Heading Font. Select a Google Fonts family from either dropdown; each selected family is applied consistently to the plugin frontend and, when its admin toggle is enabled, the plugin administration screens.', 'spindle-market-research' ); ?></p>

                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><label for="cbah-body-font"><?php echo esc_html__( 'Body / Text Font', 'spindle-market-research' ); ?></label></th>
                            <td>
                                <select id="cbah-body-font" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[body_font]" class="regular-text cbah-google-font-select">
                                    <?php foreach ( $choices as $font ) : ?>
                                        <option value="<?php echo esc_attr( $font ); ?>" <?php selected( $settings['body_font'], $font ); ?>><?php echo esc_html( $font ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php echo esc_html__( 'Used for dashboard text, ticker text, snapshot text, tables, controls and other body content.', 'spindle-market-research' ); ?></p>
                                <label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[body_font_admin]" value="1" <?php checked( ! empty( $settings['body_font_admin'] ) ); ?> /> <?php echo esc_html__( 'Use this selected font in the plugin admin area', 'spindle-market-research' ); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="cbah-heading-font"><?php echo esc_html__( 'Heading Font', 'spindle-market-research' ); ?></label></th>
                            <td>
                                <select id="cbah-heading-font" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[heading_font]" class="regular-text cbah-google-font-select">
                                    <?php foreach ( $choices as $font ) : ?>
                                        <option value="<?php echo esc_attr( $font ); ?>" <?php selected( $settings['heading_font'], $font ); ?>><?php echo esc_html( $font ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php echo esc_html__( 'Used for headings, card headers and other prominent labels.', 'spindle-market-research' ); ?></p>
                                <label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[heading_font_admin]" value="1" <?php checked( ! empty( $settings['heading_font_admin'] ) ); ?> /> <?php echo esc_html__( 'Use this selected font in the plugin admin area', 'spindle-market-research' ); ?></label>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="cbah-settings-card">
                    <h2><?php echo esc_html__( 'Ticker Settings', 'spindle-market-research' ); ?></h2>
                    <p><?php echo esc_html__( 'Configure the movement speed of the market ticker independently from typography settings.', 'spindle-market-research' ); ?></p>

                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><label for="cbah-ticker-speed"><?php echo esc_html__( 'Ticker Speed', 'spindle-market-research' ); ?></label></th>
                            <td>
                                <input id="cbah-ticker-speed" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[ticker_speed]" type="number" class="small-text" min="10" max="200" step="1" value="<?php echo esc_attr( $settings['ticker_speed'] ); ?>" />
                                <span><?php echo esc_html__( 'pixels per second', 'spindle-market-research' ); ?></span>
                                <p class="description"><?php echo esc_html__( 'Controls the ticker movement independently of how many gainers or losers are entered. Higher values scroll faster. Recommended range: 30–120 px/s.', 'spindle-market-research' ); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public static function enqueue_frontend_fonts() {
        $settings = self::get_settings();
        self::enqueue_google_fonts_static( $settings['body_font'], $settings['heading_font'], 'cbah-google-fonts' );
    }

    public static function add_frontend_typography_variables( $style_handle ) {
        $settings = self::get_settings();
        self::add_typography_variables_static( $style_handle, $settings['body_font'], $settings['heading_font'] );
    }

    private function enqueue_google_fonts( $body_font, $heading_font, $handle ) {
        self::enqueue_google_fonts_static( $body_font, $heading_font, $handle );
    }

    private static function enqueue_google_fonts_static( $body_font, $heading_font, $handle ) {
        $families = array();
        foreach ( array( $body_font, $heading_font ) as $font ) {
            if ( '' !== trim( (string) $font ) ) {
                $families[] = self::sanitize_font( $font );
            }
        }
        $families = array_unique( array_filter( $families ) );
        $query    = array();

        foreach ( $families as $family ) {
            $query[] = 'family=' . rawurlencode( $family ) . ':wght@400;500;600;700';
        }

        if ( empty( $query ) ) {
            return;
        }

        wp_enqueue_style(
            $handle,
            'https://fonts.googleapis.com/css2?' . implode( '&', $query ) . '&display=swap',
            array(),
            null
        );
    }

    private function add_typography_variables( $style_handle, $body_font, $heading_font ) {
        $rules = array();
        if ( '' !== trim( (string) $body_font ) ) {
            $rules[] = '--cbah-admin-body-font:' . wp_json_encode( self::sanitize_font( $body_font ) ) . ',sans-serif';
        }
        if ( '' !== trim( (string) $heading_font ) ) {
            $rules[] = '--cbah-admin-heading-font:' . wp_json_encode( self::sanitize_font( $heading_font ) ) . ',sans-serif';
        }
        if ( empty( $rules ) ) {
            return;
        }

        wp_add_inline_style(
            $style_handle,
            ':root{' . implode( ';', $rules ) . ';}'
        );
    }

    private static function add_typography_variables_static( $style_handle, $body_font, $heading_font ) {
        $body_font    = '' !== (string) $body_font ? self::sanitize_font( $body_font ) : '';
        $heading_font = '' !== (string) $heading_font ? self::sanitize_font( $heading_font ) : '';

        $rules = array();
        if ( '' !== $body_font ) {
            $rules[] = '--cbah-body-font:' . wp_json_encode( $body_font ) . ',sans-serif';
        }
        if ( '' !== $heading_font ) {
            $rules[] = '--cbah-heading-font:' . wp_json_encode( $heading_font ) . ',sans-serif';
        }
        if ( empty( $rules ) ) {
            return;
        }

        wp_add_inline_style(
            $style_handle,
            ':root{' . implode( ';', $rules ) . ';}'
        );
    }
}
