<?php
defined( 'ABSPATH' ) || exit;

final class GIP_Plugin {
    private static ?self $instance = null;

    private array $meta_fields = array(
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

    public static function instance(): self {
        return self::$instance ??= new self();
    }

    private function __construct() {
        add_action( 'init', array( $this, 'register_content' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'save_post_gip_property', array( $this, 'save_meta' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'public_assets' ) );
        add_action( 'wp_ajax_gip_filter_properties', array( $this, 'ajax_filter' ) );
        add_action( 'wp_ajax_nopriv_gip_filter_properties', array( $this, 'ajax_filter' ) );
        add_action( 'admin_post_gip_contact', array( $this, 'handle_contact' ) );
        add_action( 'admin_post_nopriv_gip_contact', array( $this, 'handle_contact' ) );
        add_shortcode( 'gestor_inmobiliario_listado', array( $this, 'listing_shortcode' ) );
        add_shortcode( 'gestor_inmobiliario_busqueda', array( $this, 'search_shortcode' ) );
        add_filter( 'template_include', array( $this, 'fallback_templates' ) );

        add_action( 'elementor/elements/categories_registered', array( $this, 'register_elementor_category' ) );
        add_action( 'elementor/widgets/register', array( $this, 'register_elementor_widgets' ) );
    }

    public static function activate(): void {
        self::instance()->register_content();
        foreach ( array( 'Apartamento', 'Casa' ) as $name ) {
            if ( ! term_exists( $name, 'gip_property_type' ) ) wp_insert_term( $name, 'gip_property_type' );
        }
        foreach ( array( 'Venta', 'Arriendo' ) as $name ) {
            if ( ! term_exists( $name, 'gip_operation' ) ) wp_insert_term( $name, 'gip_operation' );
        }
        foreach ( array( 'Disponible', 'Reservada', 'Vendida', 'Arrendada', 'Inactiva' ) as $name ) {
            if ( ! term_exists( $name, 'gip_status' ) ) wp_insert_term( $name, 'gip_status' );
        }
        flush_rewrite_rules();
    }

    public static function deactivate(): void {
        flush_rewrite_rules();
    }

    public function register_content(): void {
        register_post_type( 'gip_property', array(
            'labels' => array(
                'name'          => 'Propiedades',
                'singular_name' => 'Propiedad',
                'add_new_item'  => 'Añadir propiedad',
                'edit_item'     => 'Editar propiedad',
                'all_items'     => 'Propiedades',
            ),
            'public'       => true,
            'has_archive'  => 'propiedades',
            'rewrite'      => array( 'slug' => 'propiedades' ),
            'show_in_rest' => true,
            'menu_icon'    => 'dashicons-building',
            'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
            'menu_position'=> 20,
        ) );

        $taxonomies = array(
            'gip_property_type' => array( 'Tipo de propiedad', 'Tipos de propiedad', true ),
            'gip_city'          => array( 'Ciudad', 'Ciudades', true ),
            'gip_neighborhood'  => array( 'Barrio', 'Barrios', true ),
            'gip_operation'     => array( 'Operación', 'Operaciones', false ),
            'gip_status'        => array( 'Estado comercial', 'Estados comerciales', false ),
            'gip_amenity'       => array( 'Amenidad', 'Amenidades', false ),
        );

        foreach ( $taxonomies as $slug => $data ) {
            register_taxonomy( $slug, 'gip_property', array(
                'labels'            => array( 'singular_name' => $data[0], 'name' => $data[1] ),
                'public'            => true,
                'show_in_rest'      => true,
                'hierarchical'      => $data[2],
                'show_admin_column' => true,
            ) );
        }

        foreach ( array( 'Ascensor', 'Balcón', 'Terraza', 'Piscina', 'Vigilancia', 'Gimnasio', 'Depósito' ) as $name ) {
            if ( ! term_exists( $name, 'gip_amenity' ) ) wp_insert_term( $name, 'gip_amenity' );
        }
    }

    public function add_meta_boxes(): void {
        add_meta_box( 'gip_property_details', 'Detalles y ubicación de la propiedad', array( $this, 'details_box' ), 'gip_property', 'normal', 'high' );
        add_meta_box( 'gip_property_gallery', 'Galería de imágenes de la propiedad', array( $this, 'gallery_box' ), 'gip_property', 'normal', 'high' );
        add_meta_box( 'gip_property_agent', 'Agente de esta propiedad', array( $this, 'agent_box' ), 'gip_property', 'normal', 'default' );
    }

    private function input( int $post_id, string $key, string $type = 'text' ): void {
        $value = get_post_meta( $post_id, '_gip_' . $key, true );

        if ( 'checkbox' === $type ) {
            printf(
                '<p><label><input type="checkbox" name="gip_%1$s" value="1" %2$s> %3$s</label></p>',
                esc_attr( $key ),
                checked( $value, '1', false ),
                esc_html( $this->meta_fields[ $key ] )
            );
            return;
        }

        printf(
            '<p><label for="gip_%1$s"><strong>%2$s</strong></label><br><input class="widefat" type="%3$s" id="gip_%1$s" name="gip_%1$s" value="%4$s"></p>',
            esc_attr( $key ),
            esc_html( $this->meta_fields[ $key ] ),
            esc_attr( $type ),
            esc_attr( $value )
        );
    }

    private function taxonomy_select( WP_Post $post, string $taxonomy, string $label, string $field_name ): void {
        $terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) );
        $assigned = wp_get_post_terms( $post->ID, $taxonomy, array( 'fields' => 'ids' ) );
        $selected = ! empty( $assigned ) ? (int) $assigned[0] : 0;

        echo '<p><label for="' . esc_attr( $field_name ) . '"><strong>' . esc_html( $label ) . '</strong></label><br>';
        echo '<select class="widefat" id="' . esc_attr( $field_name ) . '" name="' . esc_attr( $field_name ) . '">';
        echo '<option value="">Seleccionar</option>';
        foreach ( $terms as $term ) {
            echo '<option value="' . esc_attr( $term->term_id ) . '" ' . selected( $selected, $term->term_id, false ) . '>' . esc_html( $term->name ) . '</option>';
        }
        echo '</select></p>';
    }

    public function details_box( WP_Post $post ): void {
        wp_nonce_field( 'gip_save_property', 'gip_nonce' );

        echo '<div class="gip-fields">';
        $this->input( $post->ID, 'bedrooms', 'number' );
        $this->input( $post->ID, 'bathrooms', 'number' );
        $this->input( $post->ID, 'parking', 'number' );
        $this->input( $post->ID, 'built_area', 'number' );
        $this->input( $post->ID, 'lot_area', 'number' );
        $this->input( $post->ID, 'price', 'number' );

        $currency = get_post_meta( $post->ID, '_gip_currency', true ) ?: 'COP';
        echo '<p><label><strong>Moneda</strong></label><br><select class="widefat" name="gip_currency">';
        echo '<option value="COP" ' . selected( $currency, 'COP', false ) . '>COP</option>';
        echo '<option value="USD" ' . selected( $currency, 'USD', false ) . '>USD</option>';
        echo '</select></p>';

        $this->taxonomy_select( $post, 'gip_city', 'Ciudad', 'gip_city_term' );
        $this->taxonomy_select( $post, 'gip_neighborhood', 'Barrio', 'gip_neighborhood_term' );
        echo '</div>';

        echo '<hr>';
        echo '<h3>Ubicación</h3>';
        echo '<div class="gip-fields">';
        $this->input( $post->ID, 'address' );
        $this->input( $post->ID, 'latitude' );
        $this->input( $post->ID, 'longitude' );
        $this->input( $post->ID, 'tour_url', 'url' );
        echo '</div>';

        $this->input( $post->ID, 'featured', 'checkbox' );
        echo '<p><em>También puedes administrar las taxonomías desde los paneles laterales de WordPress.</em></p>';
    }

    public function agent_box( WP_Post $post ): void {
        foreach ( array( 'agent_name', 'agent_title', 'agent_whatsapp', 'agent_email' ) as $field ) {
            $this->input( $post->ID, $field, 'agent_email' === $field ? 'email' : 'text' );
        }

        $photo_id = absint( get_post_meta( $post->ID, '_gip_agent_photo_id', true ) );
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
        echo '<p><em>Para WhatsApp usa código de país y solo números. Ejemplo: 573001234567.</em></p>';
    }

    public function gallery_box( WP_Post $post ): void {
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
        echo '<p><em>Puedes seleccionar varias imágenes. La imagen destacada se administra aparte desde WordPress.</em></p>';
    }

    public function save_meta( int $post_id ): void {
        if (
            ! isset( $_POST['gip_nonce'] ) ||
            ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gip_nonce'] ) ), 'gip_save_property' ) ||
            ! current_user_can( 'edit_post', $post_id ) ||
            wp_is_post_autosave( $post_id ) ||
            wp_is_post_revision( $post_id )
        ) {
            return;
        }

        foreach ( $this->meta_fields as $key => $label ) {
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

    public function admin_assets( string $hook ): void {
        if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) && 'gip_property' === get_post_type() ) {
            wp_enqueue_media();
            wp_enqueue_script( 'gip-admin', GIP_URL . 'assets/js/admin.js', array( 'jquery' ), GIP_VERSION, true );
        }
    }

    public function public_assets(): void {
        wp_enqueue_style( 'gip-public', GIP_URL . 'assets/css/public.css', array(), GIP_VERSION );
        wp_enqueue_script( 'gip-public', GIP_URL . 'assets/js/public.js', array(), GIP_VERSION, true );
        wp_localize_script( 'gip-public', 'gipData', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'gip_filter' ),
        ) );

        if ( is_singular( 'gip_property' ) ) {
            wp_enqueue_style( 'leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4' );
            wp_enqueue_script( 'leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true );
        }
    }

    public function terms( string $taxonomy ): array {
        return get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) );
    }

    public function search_shortcode( array $atts = array() ): string {
        ob_start(); ?>
        <form class="gip-search" data-gip-search>
            <select name="city"><option value="">Todas las ciudades</option><?php foreach ( $this->terms( 'gip_city' ) as $term ) echo '<option value="' . esc_attr( $term->slug ) . '">' . esc_html( $term->name ) . '</option>'; ?></select>
            <select name="type"><option value="">Todos los tipos</option><?php foreach ( $this->terms( 'gip_property_type' ) as $term ) echo '<option value="' . esc_attr( $term->slug ) . '">' . esc_html( $term->name ) . '</option>'; ?></select>
            <select name="bedrooms"><option value="">Habitaciones</option><?php for ( $i = 1; $i <= 5; $i++ ) echo '<option value="' . $i . '">' . $i . ( 5 === $i ? '+' : '' ) . '</option>'; ?></select>
            <select name="bathrooms"><option value="">Baños</option><?php for ( $i = 1; $i <= 5; $i++ ) echo '<option value="' . $i . '">' . $i . ( 5 === $i ? '+' : '' ) . '</option>'; ?></select>
            <button type="submit">Buscar propiedades</button>
        </form>
        <?php
        return (string) ob_get_clean();
    }

    private function property_query( array $args = array() ): WP_Query {
        $limit = isset( $args['limit'] ) ? absint( $args['limit'] ) : 9;
        $limit = max( 1, min( 24, $limit ) );

        $query = array(
            'post_type'      => 'gip_property',
            'post_status'    => 'publish',
            'posts_per_page' => $limit,
            'paged'          => max( 1, absint( $args['paged'] ?? get_query_var( 'paged' ) ) ),
            'tax_query'      => array( 'relation' => 'AND' ),
        );

        foreach ( array(
            'city'      => 'gip_city',
            'type'      => 'gip_property_type',
            'operation' => 'gip_operation',
        ) as $key => $taxonomy ) {
            if ( ! empty( $args[ $key ] ) ) {
                $query['tax_query'][] = array(
                    'taxonomy' => $taxonomy,
                    'field'    => 'slug',
                    'terms'    => sanitize_title( $args[ $key ] ),
                );
            }
        }

        $query['tax_query'][] = array(
            'taxonomy' => 'gip_status',
            'field'    => 'slug',
            'terms'    => array( 'vendida', 'arrendada', 'inactiva' ),
            'operator' => 'NOT IN',
        );

        $meta_query = array( 'relation' => 'AND' );

        foreach ( array( 'bedrooms', 'bathrooms' ) as $key ) {
            if ( ! empty( $args[ $key ] ) ) {
                $meta_query[] = array(
                    'key'     => '_gip_' . $key,
                    'value'   => absint( $args[ $key ] ),
                    'compare' => '>=',
                    'type'    => 'NUMERIC',
                );
            }
        }

        if ( ! empty( $args['featured'] ) ) {
            $meta_query[] = array(
                'key'   => '_gip_featured',
                'value' => '1',
            );
        }

        if ( count( $meta_query ) > 1 ) {
            $query['meta_query'] = $meta_query;
        }

        return new WP_Query( $query );
    }

    public function listing_shortcode( array $atts = array() ): string {
        $atts = shortcode_atts( array( 'featured' => '', 'operation' => '', 'limit' => 9 ), $atts );
        return $this->render_listing( $this->property_query( $atts ) );
    }

    private function icon( string $name ): string {
        $icons = array(
            'bedrooms' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 11V6a2 2 0 0 1 2-2h5v7m-7 0h18v7M3 18v2m18-2v2M10 11h8a3 3 0 0 1 3 3v1H3v-1a3 3 0 0 1 3-3h4"/></svg>',
            'bathrooms'=> '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3v5m0-5h6m-3 0v9m-6 0h18v5H3v-5Zm3 5h3m5 4v-2a2 2 0 0 0-2-2h-3"/></svg>',
            'parking'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 21V3h7a5 5 0 0 1 0 10H8v8m0-18v10m0-10h4a5 5 0 0 1 0 10H8"/></svg>',
            'area'     => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 9V4h5M15 4h5v5M20 15v5h-5M9 20H4v-5"/></svg>',
            'location' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s7-5.1 7-12a7 7 0 1 0-14 0c0 6.9 7 12 7 12Zm0-9.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z"/></svg>',
        );

        return '<span class="gip-icon">' . ( $icons[ $name ] ?? '' ) . '</span>';
    }

    private function render_feature( string $icon, string $value, string $label = '' ): string {
        if ( '' === $value ) return '';
        return '<span class="gip-feature-item">' . $this->icon( $icon ) . '<span><strong>' . esc_html( $value ) . '</strong>' . ( $label ? '<small>' . esc_html( $label ) . '</small>' : '' ) . '</span></span>';
    }

    private function render_listing( WP_Query $query ): string {
        ob_start();
        echo '<div class="gip-results">';

        if ( $query->have_posts() ) {
            echo '<div class="gip-grid">';
            while ( $query->have_posts() ) {
                $query->the_post();
                echo $this->property_card( get_the_ID() );
            }
            echo '</div>';

            $links = paginate_links( array( 'total' => $query->max_num_pages, 'type' => 'list' ) );
            if ( $links ) echo '<nav class="gip-pagination">' . $links . '</nav>';
        } else {
            echo '<p>No se encontraron propiedades con estos filtros.</p>';
        }

        echo '</div>';
        wp_reset_postdata();
        return (string) ob_get_clean();
    }

    private function property_card( int $id ): string {
        $price = get_post_meta( $id, '_gip_price', true );
        $currency = get_post_meta( $id, '_gip_currency', true );

        $features = '';
        $features .= $this->render_feature( 'bedrooms', (string) get_post_meta( $id, '_gip_bedrooms', true ), 'Hab.' );
        $features .= $this->render_feature( 'bathrooms', (string) get_post_meta( $id, '_gip_bathrooms', true ), 'Baños' );
        $features .= $this->render_feature( 'parking', (string) get_post_meta( $id, '_gip_parking', true ), 'Parq.' );
        $area = get_post_meta( $id, '_gip_built_area', true );
        $features .= $this->render_feature( 'area', $area ? $area . ' m²' : '', 'Área' );

        $operation = wp_get_post_terms( $id, 'gip_operation', array( 'fields' => 'names' ) );
        $city = wp_get_post_terms( $id, 'gip_city', array( 'fields' => 'names' ) );

        $image = get_the_post_thumbnail( $id, 'large' );
        if ( ! $image ) {
            $image = '<div class="gip-no-image">Sin imagen</div>';
        }

        return '<article class="gip-card"><a href="' . esc_url( get_permalink( $id ) ) . '">'
            . $image
            . ( $operation ? '<span class="gip-badge">' . esc_html( implode( ', ', $operation ) ) . '</span>' : '' )
            . '<div class="gip-card-body">'
            . '<div class="gip-card-location">' . $this->icon( 'location' ) . esc_html( implode( ', ', $city ) ) . '</div>'
            . '<h3>' . esc_html( get_the_title( $id ) ) . '</h3>'
            . ( '' !== $price ? '<strong class="gip-card-price">' . esc_html( $currency ) . ' ' . esc_html( number_format_i18n( (float) $price, 0 ) ) . '</strong>' : '' )
            . '<div class="gip-card-features">' . $features . '</div>'
            . '</div></a></article>';
    }

    public function ajax_filter(): void {
        check_ajax_referer( 'gip_filter', 'nonce' );
        $args = array_map( 'sanitize_text_field', wp_unslash( $_POST['filters'] ?? array() ) );
        echo $this->render_listing( $this->property_query( $args ) );
        wp_die();
    }

    public function handle_contact(): void {
        $redirect = wp_get_referer() ?: home_url( '/' );

        if (
            ! isset( $_POST['gip_contact_nonce'] ) ||
            ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gip_contact_nonce'] ) ), 'gip_contact' ) ||
            empty( $_POST['privacy'] ) ||
            ! empty( $_POST['website'] )
        ) {
            wp_safe_redirect( add_query_arg( 'gip_contact', 'error', $redirect ) );
        } else {
            $id = absint( $_POST['property_id'] ?? 0 );
            $name = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
            $email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
            $phone = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
            $message = sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) );

            if ( ! $name || ! $email || ! $id ) {
                wp_safe_redirect( add_query_arg( 'gip_contact', 'error', $redirect ) );
            } else {
                wp_mail(
                    get_option( 'admin_email' ),
                    'Nueva consulta: ' . get_the_title( $id ),
                    "Propiedad: " . get_permalink( $id ) . "\nNombre: $name\nCorreo: $email\nTeléfono: $phone\nMensaje: $message",
                    array( 'Reply-To: ' . $email )
                );
                wp_safe_redirect( add_query_arg( 'gip_contact', 'success', $redirect ) );
            }
        }

        exit;
    }

    public function fallback_templates( string $template ): string {
        if ( is_singular( 'gip_property' ) ) return GIP_DIR . 'templates/single-gip_property.php';
        if ( is_post_type_archive( 'gip_property' ) ) return GIP_DIR . 'templates/archive-gip_property.php';
        return $template;
    }

    public function register_elementor_category( $elements_manager ): void {
        $elements_manager->add_category(
            'gestor-inmobiliario',
            array(
                'title' => 'Gestor Inmobiliario',
                'icon'  => 'fa fa-building',
            )
        );
    }

    public function register_elementor_widgets( $widgets_manager ): void {
        if ( ! did_action( 'elementor/loaded' ) || ! class_exists( '\\Elementor\\Widget_Base' ) ) {
            return;
        }

        require_once GIP_DIR . 'includes/class-gip-elementor.php';

        $widgets_manager->register( new GIP_Elementor_Listing() );
        $widgets_manager->register( new GIP_Elementor_Search() );
        $widgets_manager->register( new GIP_Elementor_Featured() );
    }
}
