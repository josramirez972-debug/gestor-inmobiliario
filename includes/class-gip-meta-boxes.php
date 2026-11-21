<?php
defined( 'ABSPATH' ) || exit;

/**
 * Gestor Inmobiliario - Meta Boxes
 *
 * Registra y gestiona los paneles personalizados de metadatos del CPT de propiedades,
 * incluyendo sanitización a la entrada y vinculación con WordPress Media API.
 *
 * @package GestorInmobiliario
 * @subpackage Admin
 * @since 1.5.0
 */
final class GIP_Meta_Boxes {

    /**
     * Campos de metadatos admitidos y sus etiquetas descriptivas.
     *
     * @var array<string, string>
     */
    private static array $meta_fields = array(
        'bedrooms'       => 'Habitaciones',
        'bathrooms'      => 'Baños',
        'parking'        => 'Parqueaderos',
        'built_area'     => 'Área construida (m²)',
        'lot_area'       => 'Área de lote (m²)',
        'price'          => 'Precio',
        'currency'       => 'Moneda',
        'address'        => 'Dirección',
        'latitude'       => 'Latitud',
        'longitude'      => 'Longitud',
        'tour_url'       => 'URL del tour 360°',
        'featured'       => 'Propiedad destacada',
        'agent_name'     => 'Nombre del agente',
        'agent_title'    => 'Cargo del agente',
        'agent_photo'    => 'Foto del agente',
        'agent_whatsapp' => 'WhatsApp del agente',
        'agent_email'    => 'Correo del agente',
    );

    /**
     * Inicializa los hooks de administración para Meta Boxes.
     */
    public static function init(): void {
        add_action( 'add_meta_boxes', array( __CLASS__, 'register_boxes' ) );
        add_action( 'save_post_gip_property', array( __CLASS__, 'save_meta' ) );
    }

    /**
     * Registra las cajas de metadatos en la pantalla de edición de propiedades.
     */
    public static function register_boxes(): void {
        add_meta_box(
            'gip_property_details',
            'Detalles y ubicación de la propiedad',
            array( __CLASS__, 'render_details_box' ),
            'gip_property',
            'normal',
            'high'
        );

        add_meta_box(
            'gip_property_gallery',
            'Galería de imágenes de la propiedad',
            array( __CLASS__, 'render_gallery_box' ),
            'gip_property',
            'normal',
            'high'
        );

        add_meta_box(
            'gip_property_agent',
            'Agente de esta propiedad',
            array( __CLASS__, 'render_agent_box' ),
            'gip_property',
            'normal',
            'default'
        );
    }

    /**
     * Helper para renderizar campos de entrada estándar o casillas de verificación.
     */
    private static function render_input( int $post_id, string $key, string $type = 'text' ): void {
        $value = get_post_meta( $post_id, '_gip_' . $key, true );

        if ( 'checkbox' === $type ) {
            printf(
                '<p><label><input type="checkbox" name="gip_%1$s" value="1" %2$s> %3$s</label></p>',
                esc_attr( $key ),
                checked( $value, '1', false ),
                esc_html( self::$meta_fields[ $key ] ?? $key )
            );
            return;
        }

        printf(
            '<p><label for="gip_%1$s"><strong>%2$s</strong></label><br><input class="widefat" type="%3$s" id="gip_%1$s" name="gip_%1$s" value="%4$s"></p>',
            esc_attr( $key ),
            esc_html( self::$meta_fields[ $key ] ?? $key ),
            esc_attr( $type ),
            esc_attr( $value )
        );
    }

    /**
     * Helper para seleccionar términos taxonómicos desde un selector desplegable.
     */
    private static function render_taxonomy_select( WP_Post $post, string $taxonomy, string $label, string $field_name ): void {
        $terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) );
        $assigned = wp_get_post_terms( $post->ID, $taxonomy, array( 'fields' => 'ids' ) );
        $selected = ( ! empty( $assigned ) && ! is_wp_error( $assigned ) ) ? (int) $assigned[0] : 0;

        echo '<p><label for="' . esc_attr( $field_name ) . '"><strong>' . esc_html( $label ) . '</strong></label><br>';
        echo '<select class="widefat" id="' . esc_attr( $field_name ) . '" name="' . esc_attr( $field_name ) . '">';
        echo '<option value="">Seleccionar</option>';
        if ( ! is_wp_error( $terms ) ) {
            foreach ( $terms as $term ) {
                echo '<option value="' . esc_attr( $term->term_id ) . '" ' . selected( $selected, $term->term_id, false ) . '>' . esc_html( $term->name ) . '</option>';
            }
        }
        echo '</select></p>';
    }

    /**
     * Renderiza el panel de detalles técnicos, precio y ubicación.
     */
    public static function render_details_box( WP_Post $post ): void {
        wp_nonce_field( 'gip_save_property', 'gip_nonce' );

        echo '<div class="gip-fields">';
        self::render_input( $post->ID, 'bedrooms', 'number' );
        self::render_input( $post->ID, 'bathrooms', 'number' );
        self::render_input( $post->ID, 'parking', 'number' );
        self::render_input( $post->ID, 'built_area', 'number' );
        self::render_input( $post->ID, 'lot_area', 'number' );
        self::render_input( $post->ID, 'price', 'number' );

        $currency = get_post_meta( $post->ID, '_gip_currency', true ) ?: 'COP';
        echo '<p><label><strong>Moneda</strong></label><br><select class="widefat" name="gip_currency">';
        echo '<option value="COP" ' . selected( $currency, 'COP', false ) . '>COP (Pesos Colombianos)</option>';
        echo '<option value="USD" ' . selected( $currency, 'USD', false ) . '>USD (Dólares Americanos)</option>';
        echo '</select></p>';

        self::render_taxonomy_select( $post, 'gip_city', 'Ciudad', 'gip_city_term' );
        self::render_taxonomy_select( $post, 'gip_neighborhood', 'Barrio', 'gip_neighborhood_term' );
        echo '</div>';

        echo '<hr>';
        echo '<h3>Ubicación y Tour Virtual</h3>';
        echo '<div class="gip-fields">';
        self::render_input( $post->ID, 'address' );
        self::render_input( $post->ID, 'latitude' );
        self::render_input( $post->ID, 'longitude' );
        self::render_input( $post->ID, 'tour_url', 'url' );
        echo '</div>';

        self::render_input( $post->ID, 'featured', 'checkbox' );
        echo '<p><em>También puedes administrar las taxonomías desde los paneles laterales estándar de WordPress.</em></p>';
    }

    /**
     * Renderiza el panel de agente inmobiliario asociado.
     */
    public static function render_agent_box( WP_Post $post ): void {
        foreach ( array( 'agent_name', 'agent_title', 'agent_whatsapp', 'agent_email' ) as $field ) {
            self::render_input( $post->ID, $field, 'agent_email' === $field ? 'email' : 'text' );
        }

        $photo_id  = absint( get_post_meta( $post->ID, '_gip_agent_photo_id', true ) );
        $photo_url = get_post_meta( $post->ID, '_gip_agent_photo', true );

        echo '<p><strong>Foto del agente</strong></p>';
        echo '<input type="hidden" id="gip_agent_photo_id" name="gip_agent_photo_id" value="' . esc_attr( $photo_id ) . '">';
        echo '<button type="button" class="button gip-select-agent-photo">Seleccionar foto</button>';
        echo '<div id="gip-agent-photo-preview">';
        if ( $photo_id ) {
            echo wp_get_attachment_image( $photo_id, 'thumbnail' );
        } elseif ( $photo_url ) {
            echo '<img src="' . esc_url( $photo_url ) . '" alt="">';
        }
        echo '</div>';
        echo '<p><em>Para WhatsApp usa código de país sin signo más y solo números. Ejemplo: 573001234567.</em></p>';
    }

    /**
     * Renderiza el panel de la galería multi-imagen interactiva.
     */
    public static function render_gallery_box( WP_Post $post ): void {
        $ids = array_filter( array_map( 'absint', explode( ',', (string) get_post_meta( $post->ID, '_gip_gallery', true ) ) ) );

        echo '<input type="hidden" id="gip_gallery" name="gip_gallery" value="' . esc_attr( implode( ',', $ids ) ) . '">';
        echo '<p><button type="button" class="button button-primary gip-select-gallery">Añadir imágenes a la galería</button> ';
        echo '<button type="button" class="button gip-clear-gallery">Limpiar galería</button></p>';
        echo '<div id="gip-gallery-preview">';

        foreach ( $ids as $id ) {
            echo '<span class="gip-gallery-item" data-id="' . esc_attr( $id ) . '">';
            echo wp_get_attachment_image( $id, 'thumbnail' );
            echo '<button type="button" class="gip-remove-gallery" title="Eliminar">×</button></span>';
        }

        echo '</div>';
        echo '<p><em>Puedes seleccionar múltiples imágenes con la Media API. La imagen destacada principal se gestiona en su panel nativo.</em></p>';
    }

    /**
     * Guarda y sanitiza los metadatos de la propiedad previa verificación de seguridad.
     */
    public static function save_meta( int $post_id ): void {
        if (
            ! isset( $_POST['gip_nonce'] ) ||
            ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gip_nonce'] ) ), 'gip_save_property' ) ||
            ! current_user_can( 'edit_post', $post_id ) ||
            wp_is_post_autosave( $post_id ) ||
            wp_is_post_revision( $post_id )
        ) {
            return;
        }

        foreach ( self::$meta_fields as $key => $label ) {
            if ( 'featured' === $key ) {
                update_post_meta( $post_id, '_gip_featured', isset( $_POST['gip_featured'] ) ? '1' : '0' );
                continue;
            }

            if ( isset( $_POST[ 'gip_' . $key ] ) ) {
                $raw = wp_unslash( $_POST[ 'gip_' . $key ] );

                if ( in_array( $key, array( 'bedrooms', 'bathrooms', 'parking', 'built_area', 'lot_area', 'price', 'latitude', 'longitude' ), true ) ) {
                    $value = sanitize_text_field( $raw );
                } elseif ( 'tour_url' === $key ) {
                    $value = esc_url_raw( $raw );
                } elseif ( 'agent_email' === $key ) {
                    $value = sanitize_email( $raw );
                } else {
                    $value = sanitize_text_field( $raw );
                }

                update_post_meta( $post_id, '_gip_' . $key, $value );
            }
        }

        if ( isset( $_POST['gip_gallery'] ) ) {
            $ids = array_filter( array_map( 'absint', explode( ',', sanitize_text_field( wp_unslash( $_POST['gip_gallery'] ) ) ) ) );
            update_post_meta( $post_id, '_gip_gallery', implode( ',', $ids ) );
        }

        if ( isset( $_POST['gip_agent_photo_id'] ) ) {
            $photo_id = absint( $_POST['gip_agent_photo_id'] );
            update_post_meta( $post_id, '_gip_agent_photo_id', $photo_id );
            if ( $photo_id ) {
                update_post_meta( $post_id, '_gip_agent_photo', esc_url_raw( (string) wp_get_attachment_url( $photo_id ) ) );
            }
        }

        foreach ( array(
            'gip_city_term'         => 'gip_city',
            'gip_neighborhood_term' => 'gip_neighborhood',
        ) as $field => $taxonomy ) {
            if ( isset( $_POST[ $field ] ) ) {
                $term_id = absint( $_POST[ $field ] );
                if ( $term_id ) {
                    wp_set_object_terms( $post_id, array( $term_id ), $taxonomy, false );
                } else {
                    wp_set_object_terms( $post_id, array(), $taxonomy, false );
                }
            }
        }
    }
}
