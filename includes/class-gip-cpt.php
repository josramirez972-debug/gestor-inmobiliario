<?php
defined( 'ABSPATH' ) || exit;

/**
 * Gestor Inmobiliario - Custom Post Type & Taxonomies
 *
 * Registra y gestiona el CPT gip_property y sus taxonomías asociadas.
 *
 * @package GestorInmobiliario
 * @subpackage Core
 * @since 1.5.0
 */
final class GIP_CPT {

    /**
     * Inicializa los hooks de registro de contenido.
     */
    public static function init(): void {
        add_action( 'init', array( __CLASS__, 'register_content' ) );
    }

    /**
     * Registra el Custom Post Type y las taxonomías comerciales y geográficas.
     */
    public static function register_content(): void {
        register_post_type( 'gip_property', array(
            'labels' => array(
                'name'               => 'Propiedades',
                'singular_name'      => 'Propiedad',
                'add_new'            => 'Añadir propiedad',
                'add_new_item'       => 'Añadir nueva propiedad',
                'edit_item'          => 'Editar propiedad',
                'new_item'           => 'Nueva propiedad',
                'view_item'          => 'Ver propiedad',
                'search_items'       => 'Buscar propiedades',
                'not_found'          => 'No se encontraron propiedades',
                'not_found_in_trash' => 'No hay propiedades en la papelera',
                'all_items'          => 'Todas las propiedades',
                'menu_name'          => 'Propiedades',
            ),
            'public'       => true,
            'has_archive'  => 'propiedades',
            'rewrite'      => array( 'slug' => 'propiedades', 'with_front' => false ),
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
                'labels'            => array(
                    'singular_name' => $data[0],
                    'name'          => $data[1],
                    'search_items'  => 'Buscar ' . strtolower( $data[1] ),
                    'all_items'     => 'Todos(as) los(as) ' . strtolower( $data[1] ),
                    'edit_item'     => 'Editar ' . strtolower( $data[0] ),
                    'update_item'   => 'Actualizar ' . strtolower( $data[0] ),
                    'add_new_item'  => 'Añadir nuevo(a) ' . strtolower( $data[0] ),
                ),
                'public'            => true,
                'show_in_rest'      => true,
                'hierarchical'      => $data[2],
                'show_admin_column' => true,
            ) );
        }

        foreach ( array( 'Ascensor', 'Balcón', 'Terraza', 'Piscina', 'Vigilancia', 'Gimnasio', 'Depósito' ) as $name ) {
            if ( ! term_exists( $name, 'gip_amenity' ) ) {
                wp_insert_term( $name, 'gip_amenity' );
            }
        }
    }

    /**
     * Rutina de activación: siembra términos base y regenera reglas de reescritura.
     */
    public static function activate(): void {
        self::register_content();

        foreach ( array( 'Apartamento', 'Casa' ) as $name ) {
            if ( ! term_exists( $name, 'gip_property_type' ) ) {
                wp_insert_term( $name, 'gip_property_type' );
            }
        }
        foreach ( array( 'Venta', 'Arriendo' ) as $name ) {
            if ( ! term_exists( $name, 'gip_operation' ) ) {
                wp_insert_term( $name, 'gip_operation' );
            }
        }
        foreach ( array( 'Disponible', 'Reservada', 'Vendida', 'Arrendada', 'Inactiva' ) as $name ) {
            if ( ! term_exists( $name, 'gip_status' ) ) {
                wp_insert_term( $name, 'gip_status' );
            }
        }

        flush_rewrite_rules();
    }

    /**
     * Rutina de desactivación: limpia las reglas de reescritura.
     */
    public static function deactivate(): void {
        flush_rewrite_rules();
    }
}
