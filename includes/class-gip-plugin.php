<?php
defined( 'ABSPATH' ) || exit;

/**
 * Gestor Inmobiliario - Plugin Core Orchestrator
 *
 * Clase principal que inicializa los módulos desacoplados, assets, shortcodes,
 * plantillas de fallback y la integración con constructores visuales (Elementor).
 *
 * @package GestorInmobiliario
 * @subpackage Core
 * @since 1.5.0
 */
final class GIP_Plugin {

    /**
     * Instancia única del plugin (Patrón Singleton).
     *
     * @var self|null
     */
    private static ?self $instance = null;

    /**
     * Obtiene la instancia única de la clase.
     */
    public static function instance(): self {
        return self::$instance ??= new self();
    }

    /**
     * Constructor privado: carga submódulos y registra hooks centrales.
     */
    private function __construct() {
        $this->load_dependencies();

        // Inicialización de submódulos de dominio.
        GIP_CPT::init();
        GIP_Meta_Boxes::init();
        GIP_Ajax::init();
        GIP_Contact::init();

        // Assets y recursos.
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_assets' ) );

        // Shortcodes reutilizables.
        add_shortcode( 'gestor_inmobiliario_listado', array( $this, 'listing_shortcode' ) );
        add_shortcode( 'gestor_inmobiliario_busqueda', array( $this, 'search_shortcode' ) );

        // Plantillas de presentación (Fallback pattern).
        add_filter( 'template_include', array( $this, 'fallback_templates' ) );

        // Integración con Elementor.
        add_action( 'elementor/elements/categories_registered', array( $this, 'register_elementor_category' ) );
        add_action( 'elementor/widgets/register', array( $this, 'register_elementor_widgets' ) );
    }

    /**
     * Carga los archivos de dependencias modulares del plugin.
     */
    private function load_dependencies(): void {
        require_once GIP_DIR . 'includes/class-gip-cpt.php';
        require_once GIP_DIR . 'includes/class-gip-meta-boxes.php';
        require_once GIP_DIR . 'includes/class-gip-query.php';
        require_once GIP_DIR . 'includes/class-gip-ajax.php';
        require_once GIP_DIR . 'includes/class-gip-contact.php';
    }

    /**
     * Hook de activación: inicializa taxonomías base y reescritura de enlaces.
     */
    public static function activate(): void {
        require_once GIP_DIR . 'includes/class-gip-cpt.php';
        GIP_CPT::activate();
    }

    /**
     * Hook de desactivación: limpia las reglas de reescritura.
     */
    public static function deactivate(): void {
        require_once GIP_DIR . 'includes/class-gip-cpt.php';
        GIP_CPT::deactivate();
    }

    /**
     * Carga scripts y estilos en el panel de administración de WordPress.
     */
    public function enqueue_admin_assets( string $hook ): void {
        if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) && 'gip_property' === get_post_type() ) {
            wp_enqueue_media();
            wp_enqueue_script(
                'gip-admin',
                GIP_URL . 'assets/js/admin.js',
                array( 'jquery' ),
                GIP_VERSION,
                true
            );
        }
    }

    /**
     * Carga estilos, scripts y librerías públicas (Leaflet) en el frontend.
     */
    public function enqueue_public_assets(): void {
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

    /**
     * Shortcode para el formulario reactivo de búsqueda.
     */
    public function search_shortcode( array $atts = array() ): string {
        return GIP_Query::render_search_form();
    }

    /**
     * Shortcode para la cuadrícula o listado de propiedades.
     */
    public function listing_shortcode( array $atts = array() ): string {
        $atts = shortcode_atts( array(
            'featured'  => '',
            'operation' => '',
            'limit'     => 9,
            'city'      => '',
            'type'      => '',
            'min_price' => '',
            'max_price' => '',
        ), $atts );

        $query = GIP_Query::get_properties( $atts );
        return GIP_Query::render_listing( $query );
    }

    /**
     * Intercepta la jerarquía de temas para proveer plantillas de fallback.
     */
    public function fallback_templates( string $template ): string {
        if ( is_singular( 'gip_property' ) ) {
            $single_template = GIP_DIR . 'templates/single-gip_property.php';
            if ( file_exists( $single_template ) ) {
                return $single_template;
            }
        }

        if ( is_post_type_archive( 'gip_property' ) ) {
            $archive_template = GIP_DIR . 'templates/archive-gip_property.php';
            if ( file_exists( $archive_template ) ) {
                return $archive_template;
            }
        }

        return $template;
    }

    /**
     * Registra una categoría propia en el panel de Elementor.
     *
     * @param \Elementor\Elements_Manager $elements_manager
     */
    public function register_elementor_category( $elements_manager ): void {
        $elements_manager->add_category(
            'gestor-inmobiliario',
            array(
                'title' => 'Gestor Inmobiliario',
                'icon'  => 'fa fa-building',
            )
        );
    }

    /**
     * Registra los widgets nativos de Gestor Inmobiliario en Elementor.
     *
     * @param \Elementor\Widgets_Manager $widgets_manager
     */
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
