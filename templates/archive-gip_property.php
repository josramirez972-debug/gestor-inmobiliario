<?php
defined( 'ABSPATH' ) || exit;
get_header(); ?>
<main class="gip-container gip-archive">
    <header><h1>Propiedades</h1></header>
    <?php echo do_shortcode( '[gestor_inmobiliario_busqueda]' ); ?>
    <div data-gip-output><?php echo do_shortcode( '[gestor_inmobiliario_listado]' ); ?></div>
</main>
<?php get_footer();
