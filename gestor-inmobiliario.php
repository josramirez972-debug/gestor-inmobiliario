<?php
/**
 * Plugin Name: Gestor Inmobiliario
 * Plugin URI: https://github.com/josramirez972-debug/gestor-inmobiliario
 * Description: CPT de propiedades, filtros reactivos AJAX, contacto, mapa Leaflet y widgets nativos para Elementor.
 * Version: 1.5.0
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Author: José Ramírez
 * Author URI: https://github.com/josramirez972-debug
 * License: GPL-2.0-or-later
 * Text Domain: gestor-inmobiliario
 */

defined( 'ABSPATH' ) || exit;

/*
 * Evita una segunda inicialización accidental del archivo principal.
 * Esto corrige los warnings de constantes ya definidas y los errores
 * posteriores de "headers already sent".
 */
if ( defined( 'GIP_BOOTSTRAPPED' ) ) {
    return;
}
define( 'GIP_BOOTSTRAPPED', true );

if ( ! defined( 'GIP_VERSION' ) ) {
    define( 'GIP_VERSION', '1.5.0' );
}
if ( ! defined( 'GIP_FILE' ) ) {
    define( 'GIP_FILE', __FILE__ );
}
if ( ! defined( 'GIP_DIR' ) ) {
    define( 'GIP_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'GIP_URL' ) ) {
    define( 'GIP_URL', plugin_dir_url( __FILE__ ) );
}

require_once GIP_DIR . 'includes/class-gip-plugin.php';

register_activation_hook( GIP_FILE, array( 'GIP_Plugin', 'activate' ) );
register_deactivation_hook( GIP_FILE, array( 'GIP_Plugin', 'deactivate' ) );

GIP_Plugin::instance();
