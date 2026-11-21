<?php
defined( 'ABSPATH' ) || exit;

/**
 * Gestor Inmobiliario - Lead & Contact Handler
 *
 * Procesa el envío seguro del formulario de contacto para prospectos inmobiliarios,
 * validación de Nonces, protección Honeypot antispam y notificación por correo.
 *
 * @package GestorInmobiliario
 * @subpackage Contact
 * @since 1.5.0
 */
final class GIP_Contact {

    /**
     * Inicializa los hooks de captura del formulario en admin-post.php.
     */
    public static function init(): void {
        add_action( 'admin_post_gip_contact', array( __CLASS__, 'handle_submission' ) );
        add_action( 'admin_post_nopriv_gip_contact', array( __CLASS__, 'handle_submission' ) );
    }

    /**
     * Procesa la solicitud POST del formulario de contacto.
     */
    public static function handle_submission(): void {
        $redirect = wp_get_referer() ?: home_url( '/' );

        // 1. Verificación CSRF (Nonce), aceptación de privacidad y trampa Honeypot antispam.
        if (
            ! isset( $_POST['gip_contact_nonce'] ) ||
            ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gip_contact_nonce'] ) ), 'gip_contact' ) ||
            empty( $_POST['privacy'] ) ||
            ! empty( $_POST['website'] ) // El campo honeypot debe estar vacío para usuarios legítimos.
        ) {
            wp_safe_redirect( add_query_arg( 'gip_contact', 'error', $redirect ) );
            exit;
        }

        // 2. Sanitización estricta de las entradas.
        $property_id = absint( $_POST['property_id'] ?? 0 );
        $name        = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
        $email       = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
        $phone       = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
        $message     = sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) );

        // 3. Validación de campos mínimos obligatorios.
        if ( ! $name || ! $email || ! $property_id || ! is_email( $email ) ) {
            wp_safe_redirect( add_query_arg( 'gip_contact', 'error', $redirect ) );
            exit;
        }

        // 4. Construcción y despacho de la notificación por correo.
        $property_title = get_the_title( $property_id );
        $property_url   = get_permalink( $property_id );
        $admin_email    = get_option( 'admin_email' );

        $subject = sprintf( '[Gestor Inmobiliario] Nueva consulta: %s', $property_title );

        $body  = "Has recibido una nueva consulta inmobiliaria desde el sitio web:\n\n";
        $body .= "Inmueble: {$property_title}\n";
        $body .= "Enlace: {$property_url}\n";
        $body .= "--------------------------------------------------\n";
        $body .= "Nombre del interesado: {$name}\n";
        $body .= "Correo electrónico: {$email}\n";
        $body .= "Teléfono: {$phone}\n\n";
        $body .= "Mensaje:\n{$message}\n";

        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            sprintf( 'Reply-To: %s <%s>', $name, $email ),
        );

        $sent = wp_mail( $admin_email, $subject, $body, $headers );

        if ( $sent ) {
            wp_safe_redirect( add_query_arg( 'gip_contact', 'success', $redirect ) );
        } else {
            wp_safe_redirect( add_query_arg( 'gip_contact', 'error', $redirect ) );
        }

        exit;
    }
}
