<?php
defined( 'ABSPATH' ) || exit;

/**
 * Gestor Inmobiliario - AJAX Handler
 *
 * Administra las solicitudes asíncronas de filtrado de propiedades inmobiliarias en tiempo real.
 *
 * @package GestorInmobiliario
 * @subpackage AJAX
 * @since 1.5.0
 */
final class GIP_Ajax {

    /**
     * Inicializa los endpoints AJAX de WordPress para usuarios autenticados y no autenticados.
     */
    public static function init(): void {
        add_action( 'wp_ajax_gip_filter_properties', array( __CLASS__, 'filter_properties' ) );
        add_action( 'wp_ajax_nopriv_gip_filter_properties', array( __CLASS__, 'filter_properties' ) );
    }

    /**
     * Procesa la solicitud AJAX de búsqueda y devuelve el HTML renderizado.
     */
    public static function filter_properties(): void {
        check_ajax_referer( 'gip_filter', 'nonce' );

        $raw_filters = isset( $_POST['filters'] ) && is_array( $_POST['filters'] )
            ? wp_unslash( $_POST['filters'] )
            : array();

        $sanitized_filters = array_map( 'sanitize_text_field', $raw_filters );

        $query = GIP_Query::get_properties( $sanitized_filters );

        echo GIP_Query::render_listing( $query );

        wp_die();
    }
}
