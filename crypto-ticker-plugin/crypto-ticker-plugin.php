<?php
/*
 * Plugin Name: Crypto Ticker
 * Description: A plugin to display the crypto ticker.
 * Version: 1.0
 * Author: Samvel Budumian
 */

if ( ! defined( 'ABSPATH' ) ) exit;

const CRYPTO_TICKER_OPTION  = 'crypto_ticker_endpoint';
const CRYPTO_TICKER_DEFAULT = 'http://localhost:3000/';
const CRYPTO_TICKER_HANDLE  = 'crypto-ticker-plugin';
const CRYPTO_TICKER_JS      = 'crypto-ticker-plugin.js';
const CRYPTO_TICKER_DOMAIN  = 'crypto-ticker';

function crypto_ticker_load_script($hook) {
    $my_js_ver  = date("ymd-Gis", filemtime( plugin_dir_path( __FILE__ ) . 'crypto-ticker-plugin.js' ));
    wp_enqueue_script_module(
        CRYPTO_TICKER_HANDLE,
        plugins_url( 'crypto-ticker-plugin.js', __FILE__ ),
        ['wp-interactivity'],
        $my_js_ver,
    );
}

add_action('wp_enqueue_scripts', 'crypto_ticker_load_script');

function crypto_render_ticker( $id = 'bitcoin' ) {

    $endpoint = get_option( CRYPTO_TICKER_OPTION, CRYPTO_TICKER_DEFAULT );
    if ( ! filter_var( $endpoint, FILTER_VALIDATE_URL ) ) {
        $endpoint = CRYPTO_TICKER_DEFAULT;
    }

    $endpoint = trailingslashit( esc_url_raw( get_option( CRYPTO_TICKER_OPTION, $endpoint ) ) );

    $id = esc_attr( strtolower( $id ) );
    echo <<<HTML
<span
    data-wp-interactive="cryptonewsTicker"
    data-wp-context='{ "context": { "id":"{$id}", "endpoint":"{$endpoint}", "price":"" } }'
    data-wp-init="actions.init"
    data-wp-text="context.context.price">
</span>
HTML;
}

add_action( 'admin_init', function () {
    register_setting(
        'crypto_ticker_settings',
        CRYPTO_TICKER_OPTION,
        [
            'type'              => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default'           => CRYPTO_TICKER_DEFAULT,
        ]
    );

    add_settings_section(
        'crypto_ticker_api_section',
        __( 'API Settings', CRYPTO_TICKER_DOMAIN ),
        '__return_false',
        'crypto_ticker_settings_page'
    );

    add_settings_field(
        CRYPTO_TICKER_OPTION,
        __( 'API Base URL', CRYPTO_TICKER_DOMAIN ),
        function () {
            printf(
                '<input type="url" name="%s" value="%s" class="regular-text" required>',
                esc_attr( CRYPTO_TICKER_OPTION ),
                esc_attr( get_option( CRYPTO_TICKER_OPTION, CRYPTO_TICKER_DEFAULT ) )
            );
            echo '<p class="description">Example: https://api.example.com/</p>';
        },
        'crypto_ticker_settings_page',
        'crypto_ticker_api_section'
    );
});

add_action( 'admin_menu', function () {
    add_options_page(
        __( 'Crypto Price Ticker', CRYPTO_TICKER_DOMAIN ),
        __( 'Crypto Price Ticker', CRYPTO_TICKER_DOMAIN ),
        'manage_options',
        'crypto_ticker_settings_page',
        function () {
            printf(
                '<div class="wrap">
                    <h1>%s</h1>
                    <form method="post" action="options.php">',
                esc_html__( 'Crypto Price Ticker', CRYPTO_TICKER_DOMAIN )
            );
            settings_fields( 'crypto_ticker_settings' );
            do_settings_sections( 'crypto_ticker_settings_page' );
            submit_button();
            echo '</form></div>';
        }
    );
});

add_action( 'wp_body_open', function () {
    echo '<div class="crypto-price-ticker">BTC:';
    echo crypto_render_ticker( 'bitcoin' );
    echo '</div>';
}, 10 );