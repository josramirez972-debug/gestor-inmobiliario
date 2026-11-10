<?php
defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
    the_post();

    $id          = get_the_ID();
    $gallery     = array_values( array_filter( array_map( 'absint', explode( ',', (string) get_post_meta( $id, '_gip_gallery', true ) ) ) ) );
    $price       = get_post_meta( $id, '_gip_price', true );
    $currency    = get_post_meta( $id, '_gip_currency', true ) ?: 'COP';
    $address     = get_post_meta( $id, '_gip_address', true );
    $cities      = wp_get_post_terms( $id, 'gip_city', array( 'fields' => 'names' ) );
    $hoods       = wp_get_post_terms( $id, 'gip_neighborhood', array( 'fields' => 'names' ) );
    $operations  = wp_get_post_terms( $id, 'gip_operation', array( 'fields' => 'names' ) );
    $types       = wp_get_post_terms( $id, 'gip_property_type', array( 'fields' => 'names' ) );
    $statuses    = wp_get_post_terms( $id, 'gip_status', array( 'fields' => 'names' ) );
    $lat         = get_post_meta( $id, '_gip_latitude', true );
    $lng         = get_post_meta( $id, '_gip_longitude', true );
    $agent_name  = get_post_meta( $id, '_gip_agent_name', true );
    $agent_title = get_post_meta( $id, '_gip_agent_title', true );
    $agent_photo = get_post_meta( $id, '_gip_agent_photo', true );
    $agent_email = get_post_meta( $id, '_gip_agent_email', true );
    $whatsapp    = preg_replace( '/\D+/', '', (string) get_post_meta( $id, '_gip_agent_whatsapp', true ) );
    $policy      = get_privacy_policy_url();

    $icon = static function( string $name ): string {
        $icons = array(
            'bedrooms' => '<svg viewBox="0 0 24 24"><path d="M3 11V6a2 2 0 0 1 2-2h5v7m-7 0h18v7M3 18v2m18-2v2M10 11h8a3 3 0 0 1 3 3v1H3v-1a3 3 0 0 1 3-3h4"/></svg>',
            'bathrooms'=> '<svg viewBox="0 0 24 24"><path d="M6 3v5m0-5h6m-3 0v9m-6 0h18v5H3v-5Zm3 5h3m5 4v-2a2 2 0 0 0-2-2h-3"/></svg>',
            'parking'  => '<svg viewBox="0 0 24 24"><path d="M5 21V3h7a5 5 0 0 1 0 10H8v8m0-18v10m0-10h4a5 5 0 0 1 0 10H8"/></svg>',
            'area'     => '<svg viewBox="0 0 24 24"><path d="M4 9V4h5M15 4h5v5M20 15v5h-5M9 20H4v-5"/></svg>',
            'lot'      => '<svg viewBox="0 0 24 24"><path d="M4 20 20 4M7 4h13v13M4 7v13h13"/></svg>',
            'location' => '<svg viewBox="0 0 24 24"><path d="M12 21s7-5.1 7-12a7 7 0 1 0-14 0c0 6.9 7 12 7 12Zm0-9.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z"/></svg>',
            'mail'     => '<svg viewBox="0 0 24 24"><path d="M3 6h18v12H3zM3 7l9 6 9-6"/></svg>',
            'phone'    => '<svg viewBox="0 0 24 24"><path d="M7 3h3l2 5-2 1.5a14 14 0 0 0 4.5 4.5L16 12l5 2v3c0 1.1-.9 2-2 2C10.2 19 5 13.8 5 5c0-1.1.9-2 2-2Z"/></svg>',
            'whatsapp' => '<svg viewBox="0 0 24 24"><path d="M20 11.5a8 8 0 0 1-11.9 7L4 20l1.5-4.1A8 8 0 1 1 20 11.5Z"/><path d="M8.8 8.5c.2-.5.5-.6.8-.6h.5c.2 0 .4.1.5.4l.8 1.9c.1.3.1.5-.1.7l-.6.7c.5 1 1.3 1.8 2.4 2.3l.7-.7c.2-.2.4-.2.7-.1l1.8.9c.3.1.4.3.4.5v.5c0 .4-.2.7-.6.8-1 .3-3.3-.5-5.3-2.5S8.4 9.5 8.8 8.5Z"/></svg>',
            'send'     => '<svg viewBox="0 0 24 24"><path d="m21 3-8.5 18-2.2-7.3L3 11.5 21 3Z"/><path d="m10.3 13.7 4.3-4.3"/></svg>',
            'arrow'    => '<svg viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg>',
        );
        return '<span class="gip-icon">' . ( $icons[ $name ] ?? '' ) . '</span>';
    };

    $images = array();
    if ( has_post_thumbnail() ) $images[] = get_post_thumbnail_id( $id );
    foreach ( $gallery as $image_id ) if ( ! in_array( $image_id, $images, true ) ) $images[] = $image_id;

    $location_line = array_filter( array( $cities ? implode( ', ', $cities ) : '', $hoods ? implode( ', ', $hoods ) : '' ) );
    // Operación: primero taxonomía, con compatibilidad para instalaciones antiguas que guardaron el dato como meta.
    $operation_meta = get_post_meta( $id, '_gip_operation', true );
    $operation = ! empty( $operations ) ? $operations[0] : ( $operation_meta ? $operation_meta : '' );
    $type = ! empty( $types ) ? $types[0] : '';
    $status = ! empty( $statuses ) ? $statuses[0] : '';
?>
<main class="gip-single-page">
    <div class="gip-single-inner">
        <article class="gip-single-main">
            <?php if ( $images ) : ?>
            <section class="gip-property-gallery gip-reference-gallery" aria-label="Galería de la propiedad" data-gip-gallery>
                <div class="gip-gallery-stage">
                    <button type="button" class="gip-gallery-nav gip-gallery-prev" aria-label="Imagen anterior"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg></button>
                    <img class="gip-gallery-main-image" src="<?php echo esc_url( wp_get_attachment_image_url( $images[0], 'large' ) ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" data-gip-main-image>
                    <button type="button" class="gip-gallery-nav gip-gallery-next" aria-label="Imagen siguiente"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg></button>
                    <div class="gip-gallery-counter"><span data-gip-current>1</span> / <?php echo esc_html( count( $images ) ); ?></div>
                </div>
                <?php if ( count( $images ) > 1 ) : ?>
                <div class="gip-gallery-thumbnails">
                    <?php foreach ( array_slice( $images, 0, 6 ) as $index => $image_id ) : ?>
                    <button type="button" class="gip-gallery-thumbnail <?php echo 0 === $index ? 'is-active' : ''; ?>" data-gip-index="<?php echo esc_attr( $index ); ?>" data-gip-full="<?php echo esc_url( wp_get_attachment_image_url( $image_id, 'large' ) ); ?>">
                        <?php echo wp_get_attachment_image( $image_id, 'medium_large', false, array( 'alt' => esc_attr( get_the_title() ) ) ); ?>
                        <?php if ( 5 === $index && count( $images ) > 6 ) : ?><span class="gip-gallery-more">+<?php echo esc_html( count( $images ) - 5 ); ?></span><?php endif; ?>
                    </button>
                    <?php endforeach; ?>
                </div>
                <script type="application/json" class="gip-gallery-data"><?php echo wp_json_encode( array_map( static fn( $image_id ) => wp_get_attachment_image_url( $image_id, 'large' ), $images ) ); ?></script>
                <?php endif; ?>
            </section>
            <?php endif; ?>

            <section class="gip-mobile-summary">
                <?php if ( $operation ) : ?><span class="gip-operation-badge"><?php echo esc_html( $operation ); ?></span><?php endif; ?>
                <h1><?php the_title(); ?></h1>
                <?php if ( $location_line || $address ) : ?><div class="gip-summary-location"><?php echo $icon( 'location' ); ?><div><?php if ( $location_line ) : ?><span><?php echo esc_html( implode( ', ', $location_line ) ); ?></span><?php endif; ?><?php if ( $address ) : ?><small><?php echo esc_html( $address ); ?></small><?php endif; ?></div></div><?php endif; ?>
                <?php if ( '' !== $price ) : ?><div class="gip-mobile-price"><?php echo esc_html( $currency . ' ' . number_format_i18n( (float) $price, 0 ) ); ?></div><?php endif; ?>
            </section>

            <section class="gip-reference-features" aria-label="Características principales">
                <?php
                $single_features = array(
                    array( 'bedrooms', 'Hab.', 'bedrooms', '' ), array( 'bathrooms', 'Baños', 'bathrooms', '' ),
                    array( 'parking', 'Parq.', 'parking', '' ), array( 'built_area', 'Área', 'area', ' m²' ),
                );
                foreach ( $single_features as $feature ) : $value = get_post_meta( $id, '_gip_' . $feature[0], true ); if ( '' === $value ) continue; ?>
                    <div class="gip-reference-feature"><?php echo $icon( $feature[2] ); ?><div><strong><?php echo esc_html( $value . $feature[3] ); ?></strong><span><?php echo esc_html( $feature[1] ); ?></span></div></div>
                <?php endforeach; ?>
            </section>

            <section class="gip-description-wrap"><h2>Descripción</h2><div class="gip-description"><?php the_content(); ?></div></section>

            <section class="gip-characteristics-wrap">
                <h2>Características</h2>
                <div class="gip-characteristics-grid">
                    <?php
                    $details = array(
                        array( 'bedrooms', 'Habitaciones', 'bedrooms', '' ), array( 'bathrooms', 'Baños', 'bathrooms', '' ), array( 'parking', 'Parqueaderos', 'parking', '' ),
                        array( 'built_area', 'Área construida', 'area', ' m²' ), array( 'lot_area', 'Área de lote', 'lot', ' m²' ),
                    );
                    foreach ( $details as $detail ) : $value = get_post_meta( $id, '_gip_' . $detail[0], true ); if ( '' === $value ) continue; ?>
                        <div class="gip-characteristic"><?php echo $icon( $detail[2] ); ?><div><span><?php echo esc_html( $detail[1] ); ?></span><strong><?php echo esc_html( $value . $detail[3] ); ?></strong></div></div>
                    <?php endforeach; ?>
                    <?php if ( $type ) : ?><div class="gip-characteristic"><span class="gip-char-symbol">⌂</span><div><span>Tipo de propiedad</span><strong><?php echo esc_html( $type ); ?></strong></div></div><?php endif; ?>
                    <?php if ( $operation ) : ?><div class="gip-characteristic"><span class="gip-char-symbol">◇</span><div><span>Operación</span><strong><?php echo esc_html( $operation ); ?></strong></div></div><?php endif; ?>
                    <?php if ( $status ) : ?><div class="gip-characteristic"><span class="gip-char-symbol">✓</span><div><span>Estado</span><strong><?php echo esc_html( $status ); ?></strong></div></div><?php endif; ?>
                </div>
            </section>

            <?php $amenities = get_the_terms( $id, 'gip_amenity' ); if ( $amenities && ! is_wp_error( $amenities ) ) : ?>
            <section class="gip-amenities-wrap"><h2>Amenidades</h2><div class="gip-amenities-list"><?php foreach ( $amenities as $amenity ) echo '<span>' . esc_html( $amenity->name ) . '</span>'; ?></div></section>
            <?php endif; ?>

            <?php if ( $lat && $lng ) : ?>
            <section class="gip-map-section"><h2>Ubicación</h2><div class="gip-map-wrap"><div id="gip-map" data-lat="<?php echo esc_attr( $lat ); ?>" data-lng="<?php echo esc_attr( $lng ); ?>" data-title="<?php echo esc_attr( get_the_title() ); ?>"></div><?php if ( $address || $location_line ) : ?><div class="gip-map-address"><?php echo $icon( 'location' ); ?><span><?php echo esc_html( trim( ( $address ? $address : '' ) . ( $address && $location_line ? ', ' : '' ) . implode( ', ', $location_line ) ) ); ?></span><a target="_blank" rel="noopener" href="https://www.google.com/maps/dir/?api=1&destination=<?php echo rawurlencode( $lat . ',' . $lng ); ?>">Cómo llegar <?php echo $icon( 'arrow' ); ?></a></div><?php endif; ?></div></section>
            <?php endif; ?>
        </article>

        <aside class="gip-reference-sidebar">
            <section class="gip-property-summary-card">
                <?php if ( $operation ) : ?><span class="gip-operation-badge"><?php echo esc_html( $operation ); ?></span><?php endif; ?>
                <h1><?php the_title(); ?></h1>
                <?php if ( $location_line || $address ) : ?><div class="gip-summary-location"><?php echo $icon( 'location' ); ?><div><?php if ( $location_line ) : ?><span><?php echo esc_html( implode( ', ', $location_line ) ); ?></span><?php endif; ?><?php if ( $address ) : ?><small><?php echo esc_html( $address ); ?></small><?php endif; ?></div></div><?php endif; ?>
                <?php if ( '' !== $price ) : ?><div class="gip-summary-price"><?php echo esc_html( $currency . ' ' . number_format_i18n( (float) $price, 0 ) ); ?><small><?php echo esc_html( $currency ); ?></small></div><?php endif; ?>
                <div class="gip-summary-features">
                    <?php foreach ( $single_features as $feature ) : $value = get_post_meta( $id, '_gip_' . $feature[0], true ); if ( '' === $value ) continue; ?><div><?php echo $icon( $feature[2] ); ?><strong><?php echo esc_html( $value . $feature[3] ); ?></strong><span><?php echo esc_html( $feature[1] ); ?></span></div><?php endforeach; ?>
                </div>
                <?php if ( $whatsapp ) : ?><a class="gip-summary-whatsapp" target="_blank" rel="noopener" href="https://wa.me/<?php echo esc_attr( $whatsapp ); ?>?text=<?php echo rawurlencode( 'Hola, me interesa la propiedad: ' . get_the_title() ); ?>"><?php echo $icon( 'whatsapp' ); ?>Contactar por WhatsApp</a><?php endif; ?>
                <a class="gip-summary-info" href="#gip-contact-form"><?php echo $icon( 'mail' ); ?>Solicitar más información</a>
            </section>

            <?php if ( $agent_name || $agent_title || $agent_photo || $whatsapp || $agent_email ) : ?>
            <section class="gip-agent-card"><h2>Agente de la propiedad</h2><div class="gip-agent-card-main"><?php if ( $agent_photo ) : ?><img class="gip-agent-photo" src="<?php echo esc_url( $agent_photo ); ?>" alt="<?php echo esc_attr( $agent_name ); ?>"><?php endif; ?><div><strong><?php echo esc_html( $agent_name ); ?></strong><?php if ( $agent_title ) : ?><span><?php echo esc_html( $agent_title ); ?></span><?php endif; ?><?php if ( $whatsapp ) : ?><a href="https://wa.me/<?php echo esc_attr( $whatsapp ); ?>" target="_blank" rel="noopener"><?php echo $icon( 'whatsapp' ); ?><?php echo esc_html( $whatsapp ); ?></a><?php endif; ?><?php if ( $agent_email ) : ?><a href="mailto:<?php echo esc_attr( $agent_email ); ?>"><?php echo $icon( 'mail' ); ?><?php echo esc_html( $agent_email ); ?></a><?php endif; ?></div></div></section>
            <?php endif; ?>

            <section class="gip-contact-card" id="gip-contact-form"><h2>¿Te interesa esta propiedad?</h2><p class="gip-contact-intro">Déjanos tus datos y te contactaremos.</p>
                <?php $contact_status = isset( $_GET['gip_contact'] ) ? sanitize_key( wp_unslash( $_GET['gip_contact'] ) ) : ''; if ( 'success' === $contact_status ) : ?><p class="gip-success">Tu solicitud fue enviada.</p><?php elseif ( 'error' === $contact_status ) : ?><p class="gip-error">Revisa los campos e inténtalo de nuevo.</p><?php endif; ?>
                <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post"><input type="hidden" name="action" value="gip_contact"><input type="hidden" name="property_id" value="<?php echo esc_attr( $id ); ?>"><?php wp_nonce_field( 'gip_contact', 'gip_contact_nonce' ); ?><input name="website" class="gip-honeypot" tabindex="-1" autocomplete="off">
                    <p><label>Nombre completo<input required name="name" placeholder="Nombre completo"></label></p><p><label>Correo electrónico<input required type="email" name="email" placeholder="Correo electrónico"></label></p><p><label>Teléfono<input name="phone" placeholder="Teléfono"></label></p><p><label>Mensaje<textarea name="message" placeholder="Hola, me interesa esta propiedad. ¿Podrían darme más información?"></textarea></label></p>
                    <p class="gip-privacy"><label><input required type="checkbox" name="privacy" value="1"> Acepto la <a target="_blank" rel="noopener" href="<?php echo esc_url( $policy ?: home_url( '/politica-de-privacidad/' ) ); ?>">política de privacidad</a>.</label></p><button type="submit"><?php echo $icon( 'send' ); ?>Enviar mensaje</button>
                </form>
            </section>
        </aside>
    </div>
</main>
<?php endwhile; get_footer(); ?>
