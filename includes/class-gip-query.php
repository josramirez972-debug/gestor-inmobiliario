<?php
defined( 'ABSPATH' ) || exit;

/**
 * Gestor Inmobiliario - Query & Presentation Service
 *
 * Encapsula la lógica de consulta (WP_Query) y renderizado de tarjetas,
 * listados, filtros y paginación para el frontend y endpoints AJAX.
 *
 * @package GestorInmobiliario
 * @subpackage Query
 * @since 1.5.0
 */
final class GIP_Query {

    /**
     * Construye una consulta WP_Query filtrada según parámetros taxonómicos y metadatos.
     *
     * @param array<string, mixed> $args Criterios de filtrado.
     * @return WP_Query
     */
    public static function get_properties( array $args = array() ): WP_Query {
        $limit = isset( $args['limit'] ) ? absint( $args['limit'] ) : 9;
        $limit = max( 1, min( 24, $limit ) );

        $query_args = array(
            'post_type'      => 'gip_property',
            'post_status'    => 'publish',
            'posts_per_page' => $limit,
            'paged'          => max( 1, absint( $args['paged'] ?? get_query_var( 'paged', 1 ) ) ),
            'tax_query'      => array( 'relation' => 'AND' ),
        );

        foreach ( array(
            'city'      => 'gip_city',
            'type'      => 'gip_property_type',
            'operation' => 'gip_operation',
        ) as $key => $taxonomy ) {
            if ( ! empty( $args[ $key ] ) ) {
                $query_args['tax_query'][] = array(
                    'taxonomy' => $taxonomy,
                    'field'    => 'slug',
                    'terms'    => sanitize_title( $args[ $key ] ),
                );
            }
        }

        // Excluir automáticamente propiedades que ya no están disponibles.
        $query_args['tax_query'][] = array(
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

        // Filtro opcional por rango de precios.
        if ( ! empty( $args['min_price'] ) ) {
            $meta_query[] = array(
                'key'     => '_gip_price',
                'value'   => (float) $args['min_price'],
                'compare' => '>=',
                'type'    => 'NUMERIC',
            );
        }

        if ( ! empty( $args['max_price'] ) ) {
            $meta_query[] = array(
                'key'     => '_gip_price',
                'value'   => (float) $args['max_price'],
                'compare' => '<=',
                'type'    => 'NUMERIC',
            );
        }

        if ( ! empty( $args['featured'] ) ) {
            $meta_query[] = array(
                'key'   => '_gip_featured',
                'value' => '1',
            );
        }

        if ( count( $meta_query ) > 1 ) {
            $query_args['meta_query'] = $meta_query;
        }

        return new WP_Query( $query_args );
    }

    /**
     * Renderiza el contenedor HTML del listado con paginación.
     */
    public static function render_listing( WP_Query $query ): string {
        ob_start();
        echo '<div class="gip-results">';

        if ( $query->have_posts() ) {
            echo '<div class="gip-grid">';
            while ( $query->have_posts() ) {
                $query->the_post();
                echo self::render_card( get_the_ID() );
            }
            echo '</div>';

            $links = paginate_links( array(
                'total' => $query->max_num_pages,
                'type'  => 'list',
            ) );

            if ( $links ) {
                echo '<nav class="gip-pagination">' . $links . '</nav>';
            }
        } else {
            echo '<p class="gip-no-results">No se encontraron propiedades con los criterios seleccionados.</p>';
        }

        echo '</div>';
        wp_reset_postdata();

        return (string) ob_get_clean();
    }

    /**
     * Renderiza la tarjeta individual de una propiedad.
     */
    public static function render_card( int $id ): string {
        $price    = get_post_meta( $id, '_gip_price', true );
        $currency = get_post_meta( $id, '_gip_currency', true ) ?: 'COP';

        $features = '';
        $features .= self::render_feature( 'bedrooms', (string) get_post_meta( $id, '_gip_bedrooms', true ), 'Hab.' );
        $features .= self::render_feature( 'bathrooms', (string) get_post_meta( $id, '_gip_bathrooms', true ), 'Baños' );
        $features .= self::render_feature( 'parking', (string) get_post_meta( $id, '_gip_parking', true ), 'Parq.' );
        $area = get_post_meta( $id, '_gip_built_area', true );
        $features .= self::render_feature( 'area', $area ? $area . ' m²' : '', 'Área' );

        $operation = wp_get_post_terms( $id, 'gip_operation', array( 'fields' => 'names' ) );
        $city      = wp_get_post_terms( $id, 'gip_city', array( 'fields' => 'names' ) );

        $image = get_the_post_thumbnail( $id, 'large' );
        if ( ! $image ) {
            $image = '<div class="gip-no-image">Sin imagen</div>';
        }

        $location_text = ( ! empty( $city ) && ! is_wp_error( $city ) ) ? implode( ', ', $city ) : '';
        $operation_badge = ( ! empty( $operation ) && ! is_wp_error( $operation ) )
            ? '<span class="gip-badge">' . esc_html( implode( ', ', $operation ) ) . '</span>'
            : '';

        $price_html = ( '' !== $price && false !== $price )
            ? '<strong class="gip-card-price">' . esc_html( $currency ) . ' ' . esc_html( number_format_i18n( (float) $price, 0 ) ) . '</strong>'
            : '';

        return '<article class="gip-card"><a href="' . esc_url( get_permalink( $id ) ) . '">'
            . $image
            . $operation_badge
            . '<div class="gip-card-body">'
            . '<div class="gip-card-location">' . self::render_icon( 'location' ) . esc_html( $location_text ) . '</div>'
            . '<h3>' . esc_html( get_the_title( $id ) ) . '</h3>'
            . $price_html
            . '<div class="gip-card-features">' . $features . '</div>'
            . '</div></a></article>';
    }

    /**
     * Renderiza un ícono SVG incrustado para evitar peticiones HTTP externas.
     */
    public static function render_icon( string $name ): string {
        $icons = array(
            'bedrooms'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 11V6a2 2 0 0 1 2-2h5v7m-7 0h18v7M3 18v2m18-2v2M10 11h8a3 3 0 0 1 3 3v1H3v-1a3 3 0 0 1 3-3h4"/></svg>',
            'bathrooms' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3v5m0-5h6m-3 0v9m-6 0h18v5H3v-5Zm3 5h3m5 4v-2a2 2 0 0 0-2-2h-3"/></svg>',
            'parking'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 21V3h7a5 5 0 0 1 0 10H8v8m0-18v10m0-10h4a5 5 0 0 1 0 10H8"/></svg>',
            'area'      => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 9V4h5M15 4h5v5M20 15v5h-5M9 20H4v-5"/></svg>',
            'location'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s7-5.1 7-12a7 7 0 1 0-14 0c0 6.9 7 12 7 12Zm0-9.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z"/></svg>',
        );

        return '<span class="gip-icon">' . ( $icons[ $name ] ?? '' ) . '</span>';
    }

    /**
     * Helper para renderizar un item de característica con ícono y etiqueta.
     */
    private static function render_feature( string $icon, string $value, string $label = '' ): string {
        if ( '' === $value ) {
            return '';
        }
        return '<span class="gip-feature-item">'
            . self::render_icon( $icon )
            . '<span><strong>' . esc_html( $value ) . '</strong>'
            . ( $label ? '<small>' . esc_html( $label ) . '</small>' : '' )
            . '</span></span>';
    }

    /**
     * Renderiza el formulario de búsqueda reactivo.
     */
    public static function render_search_form(): string {
        $cities = get_terms( array( 'taxonomy' => 'gip_city', 'hide_empty' => false ) );
        $types  = get_terms( array( 'taxonomy' => 'gip_property_type', 'hide_empty' => false ) );

        ob_start(); ?>
        <form class="gip-search" data-gip-search>
            <select name="city">
                <option value="">Todas las ciudades</option>
                <?php if ( ! is_wp_error( $cities ) ) : foreach ( $cities as $city ) : ?>
                    <option value="<?php echo esc_attr( $city->slug ); ?>"><?php echo esc_html( $city->name ); ?></option>
                <?php endforeach; endif; ?>
            </select>

            <select name="type">
                <option value="">Todos los tipos</option>
                <?php if ( ! is_wp_error( $types ) ) : foreach ( $types as $type ) : ?>
                    <option value="<?php echo esc_attr( $type->slug ); ?>"><?php echo esc_html( $type->name ); ?></option>
                <?php endforeach; endif; ?>
            </select>

            <select name="bedrooms">
                <option value="">Habitaciones</option>
                <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
                    <option value="<?php echo $i; ?>"><?php echo $i . ( 5 === $i ? '+' : '' ); ?></option>
                <?php endfor; ?>
            </select>

            <select name="bathrooms">
                <option value="">Baños</option>
                <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
                    <option value="<?php echo $i; ?>"><?php echo $i . ( 5 === $i ? '+' : '' ); ?></option>
                <?php endfor; ?>
            </select>

            <button type="submit">Buscar propiedades</button>
        </form>
        <?php
        return (string) ob_get_clean();
    }
}
