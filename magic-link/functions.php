<?php

class Disciple_Tools_Autolink_Magic_Functions
{

    private static $_instance = null;

    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function dt_magic_url_base_allowed_js( $allowed_js ) {
        $allowed_js[] = 'magic_link_scripts';
        return $allowed_js;
    }

    public function dt_magic_url_base_allowed_css( $allowed_css ) {
        $allowed_css[] = 'magic_link_css';
        return $allowed_css;
    }

    public function wp_enqueue_scripts() {
        wp_enqueue_script('magic_link_scripts', trailingslashit( plugin_dir_url( __FILE__ ) ) . '../dist/magic-link.js', [
            'jquery',
            'lodash',
        ], filemtime( plugin_dir_path( __FILE__ ) . 'magic-link.js' ), true);
        wp_localize_script(
            'magic_link_scripts',
            'app',
            [
                'map_key' => DT_Mapbox_API::get_key(),
                'rest_base' => esc_url( rest_url() ),
                'nonce' => wp_create_nonce( 'wp_rest' ),
                'urls' => [
                    'root' => esc_url_raw( trailingslashit( site_url() ) ),
                    'home' => esc_url_raw( trailingslashit( home_url() ) ),
                    'current' => esc_url_raw( dt_get_url_path( true ) ),
                    'app' => esc_url_raw( trailingslashit( $this->get_app_link() ) ),
                    'link' => esc_url_raw( trailingslashit( $this->get_link_url() ) ),
                    'survey' => esc_url_raw( $this->get_app_link() . '?action=survey' ),
                    'logout' => wp_logout_url( $this->get_link_url() ),
                    'reset_password' => wp_lostpassword_url( $this->get_link_url() )
                ],
                'translations' => [
                    'add' => __( 'Add Magic', 'disciple-tools-autolink' ),
                    'dt_nav_label' => __( 'Go to Disciple.Tools', 'disciple-tools-autolink' ),
                    'survey_nav_label' => __( 'Update Survey Answers', 'disciple-tools-autolink' ),
                    'feedback_nav_label' => __( 'Give Feedback', 'disciple-tools-autolink' ),
                    'logout_nav_label' => __( 'Log Out', 'disciple-tools-autolink' ),
                    'toggle_menu' => __( 'Toggle Menu', 'disciple-tools-autolink' ),
                    'user_greeting,' => __( 'Hello,', 'disciple-tools-autolink' ),
                    'coached_by' => __( 'Coached by', 'disciple-tools-autolink' ),
                    'my_link' => __( 'My Link', 'disciple-tools-autolink' ),
                    'my_churches' => __( 'My Churches', 'disciple-tools-autolink' ),
                ]
            ]
        );
        wp_enqueue_style( 'magic_link_css', trailingslashit( plugin_dir_url( __FILE__ ) ) . '../dist/magic-link.css', [], filemtime( plugin_dir_path( __FILE__ ) . 'magic-link.css' ) );
    }

    public function is_activated() {
        global $wpdb;
        $preference_key = 'autolink-app';
        $meta_key = $wpdb->prefix . DT_Magic_URL::get_public_key_meta_key( 'autolink', 'app' );
        $public = get_user_meta( get_current_user_id(), $meta_key, true );
        $secret = get_user_option( $preference_key );

        if ( $public === '' || $public === false || $public === '0' || $secret === '' || $secret === false || $secret === '0' ) {
            return false;
        }

        return true;
    }

    /**
     * Activate the app if it's not already activated
     */
    public function activate() {
        global $wpdb;

        $preference_key = 'autolink-app';
        $meta_key = $wpdb->prefix . DT_Magic_URL::get_public_key_meta_key( 'autolink', 'app' );

        if ( !$this->is_activated() ) {
            delete_user_meta( get_current_user_id(), $meta_key );
            delete_user_option( get_current_user_id(), $preference_key );

            add_user_meta( get_current_user_id(), $meta_key, DT_Magic_URL::create_unique_key() );
            Disciple_Tools_Users::app_switch( get_current_user_id(), $preference_key );
        }

        $preference_key = 'autolink-share';
        $meta_key = $wpdb->prefix . DT_Magic_URL::get_public_key_meta_key( 'autolink', 'share' );

        if ( !$this->is_activated() ) {
            delete_user_meta( get_current_user_id(), $meta_key );
            delete_user_option( get_current_user_id(), $preference_key );

            add_user_meta( get_current_user_id(), $meta_key, DT_Magic_URL::create_unique_key() );
            Disciple_Tools_Users::app_switch( get_current_user_id(), $preference_key );
        }
    }

    /**
     * Get the magic link url
     * @return string
     */
    public function get_app_link() {
        $app_public_key = get_user_option( DT_Magic_URL::get_public_key_meta_key( 'autolink', 'app' ) );
        return DT_Magic_URL::get_link_url( 'autolink', 'app', $app_public_key );
    }

    /**
     * Get the share link url
     * @return string
     */
    public function get_share_link() {
        $current_user_id = get_current_user_id();
        $record = DT_Posts::get_post( 'contacts', Disciple_Tools_Users::get_contact_for_user( $current_user_id ), true, false );
        $meta_key = 'autolink_share_magic_key';
        if ( isset( $record[$meta_key] ) ) {
            $key = $record[$meta_key];
        } else {
            $key = dt_create_unique_key();
            update_post_meta( get_the_ID(), $meta_key, $key );
        }

        return DT_Magic_URL::get_link_url_for_post(
            'contacts',
            $record['ID'],
            'autolink',
            'share'
        );
    }

    /**
     * Get the magic link url
     * @return string
     */
    public function get_link_url() {
        return get_site_url( null, 'autolink' );
    }


    public function redirect_to_app() {
        wp_redirect( $this->get_app_link() );
        exit;
    }

    public function redirect_to_link() {
        wp_redirect( $this->get_link_url() );
        exit;
    }

    public function fetch_logo() {
        $logo_url = $dt_nav_tabs['admin']['site']['icon'] ?? plugin_dir_url( __FILE__ ) . '/images/logo-color.png';
        $custom_logo_url = get_option( 'custom_logo_url' );
        if ( !empty( $custom_logo_url ) ) {
            $logo_url = $custom_logo_url;
        }
        return $logo_url;
    }

    public function add_session_leader() {
        $leader_id = get_transient( 'dt_autolink_leader_id' ) ? sanitize_text_field( get_transient( 'dt_autolink_leader_id' ) ) : null;
        if ( !$leader_id ) {
            return;
        }

        $contact = Disciple_Tools_Users::get_contact_for_user( get_current_user_id() );

        $fields = [
            "coached_by" => [
                "values" => [
                    [ "value" => $leader_id ],
                ],
                "force_values" => false
            ]
        ];
        DT_Posts::update_post( 'contacts', $contact, $fields, true, false );
        delete_transient( 'dt_autolink_leader_id' );
    }

    public function survey(): array {
        $survey = apply_filters('dt_autolink_survey', [
            [
                'name' => 'dt_autolink_number_of_leaders_coached',
                'label' => __( 'How many leaders are you coaching?', 'disciple-tools-autolink' )
            ],
            [
                'name' => 'dt_autolink_number_of_churches_led',
                'label' => __( 'How many churches are you leading?', 'disciple-tools-autolink' )
            ]
        ]);
        if ( !is_array( $survey ) ) {
            return [];
        }
        return $survey;
    }

    public function survey_completed() {
        $survey = $this->survey();
        $user_meta = get_user_meta( get_current_user_id() );
        foreach ( $survey as $question ) {
            if ( !isset( $user_meta[$question['name']] ) ) {
                return false;
            }
        }
        return true;
    }
}
