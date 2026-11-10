<?php
defined( 'ABSPATH' ) || exit;

abstract class GIP_Elementor_Base extends \Elementor\Widget_Base {
    public function get_categories(): array {
        return array( 'gestor-inmobiliario' );
    }
}

final class GIP_Elementor_Listing extends GIP_Elementor_Base {
    public function get_name(): string { return 'gip_listing'; }
    public function get_title(): string { return 'Listado de propiedades'; }
    public function get_icon(): string { return 'eicon-posts-grid'; }

    protected function register_controls(): void {
        $this->start_controls_section( 'content', array( 'label' => 'Consulta' ) );
        $this->add_control( 'operation', array(
            'label'   => 'Operación',
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => array( '' => 'Todas', 'venta' => 'Venta', 'arriendo' => 'Arriendo' ),
        ) );
        $this->add_control( 'limit', array(
            'label'   => 'Propiedades',
            'type'    => \Elementor\Controls_Manager::NUMBER,
            'default' => 9,
            'min'     => 1,
            'max'     => 24,
        ) );
        $this->end_controls_section();
    }

    protected function render(): void {
        $s = $this->get_settings_for_display();
        echo do_shortcode( '[gestor_inmobiliario_listado operation="' . esc_attr( $s['operation'] ) . '" limit="' . absint( $s['limit'] ) . '"]' );
    }
}

final class GIP_Elementor_Search extends GIP_Elementor_Base {
    public function get_name(): string { return 'gip_search'; }
    public function get_title(): string { return 'Buscador de propiedades'; }
    public function get_icon(): string { return 'eicon-search'; }

    protected function render(): void {
        echo do_shortcode( '[gestor_inmobiliario_busqueda]' );
    }
}

final class GIP_Elementor_Featured extends GIP_Elementor_Base {
    public function get_name(): string { return 'gip_featured'; }
    public function get_title(): string { return 'Propiedades destacadas'; }
    public function get_icon(): string { return 'eicon-star-o'; }

    protected function register_controls(): void {
        $this->start_controls_section( 'content', array( 'label' => 'Consulta' ) );
        $this->add_control( 'limit', array(
            'label'   => 'Propiedades',
            'type'    => \Elementor\Controls_Manager::NUMBER,
            'default' => 3,
            'min'     => 1,
            'max'     => 24,
        ) );
        $this->end_controls_section();
    }

    protected function render(): void {
        $s = $this->get_settings_for_display();
        echo do_shortcode( '[gestor_inmobiliario_listado featured="1" limit="' . absint( $s['limit'] ) . '"]' );
    }
}
