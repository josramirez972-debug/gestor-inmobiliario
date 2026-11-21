# Changelog

Todos los cambios notables en este proyecto serán documentados en este archivo.

El formato se basa en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/)
y este proyecto se adhiere a [Semantic Versioning](https://semver.org/lang/es/).

---

## [1.5.0] - 2026-11-21

### Añadido
- **Modularización de Arquitectura OOP:**
  - `GIP_CPT`: Registro encapsulado del Custom Post Type (`gip_property`) y sus 6 taxonomías asociadas.
  - `GIP_Meta_Boxes`: Gestión desacoplada de paneles de administración y persistencia segura de metadatos.
  - `GIP_Query`: Servicio centralizado para construcción de consultas `WP_Query`, paginación y renderizado de tarjetas.
  - `GIP_Ajax`: Manejador específico para el endpoint asíncrono con verificación estricta de Nonces.
  - `GIP_Contact`: Controlador de captura de prospectos con validación de privacidad y Honeypot antispam.
- Soporte para parámetros de rango de precios (`min_price` y `max_price`) en `GIP_Query`.
- Documentación exhaustiva en bloques PHPDoc y tipado estricto en métodos.

### Modificado
- `GIP_Plugin` ahora actúa como orquestador ligero (Singleton) cargando dependencias de forma limpia.
- Actualización de cabeceras de plugin a versión 1.5.0.

---

## [1.4.1] - 2026-09-07

### Corregido
- Prevención de doble inicialización accidental del archivo principal mediante la constante `GIP_BOOTSTRAPPED` para evitar warnings y errores de "headers already sent".
- Mejoras en la responsividad de la galería y alineación de miniaturas en dispositivos móviles.

---

## [1.4.0] - 2026-09-07

### Añadido
- Mecanismo Honeypot antispam en el formulario de contacto para bloquear bots sin necesidad de captchas invasivos.
- Enrutamiento y respuesta de éxito/error mediante `admin-post.php` y `wp_safe_redirect`.

---

## [1.3.0] - 2026-09-07

### Añadido
- Integración nativa con la API de Elementor (`\Elementor\Widget_Base`):
  - Widget de listado dinámico (`gip_listing`).
  - Widget de buscador reactivo (`gip_search`).
  - Widget de propiedades destacadas (`gip_featured`).
- Nueva categoría dedicada en el panel de Elementor: *Gestor Inmobiliario*.
- Galería multi-imagen integrada directamente con `wp.media` de WordPress en el panel de administración.

---

## [1.2.0] - 2026-09-07

### Añadido
- Integración de mapas georreferenciados mediante **Leaflet.js y OpenStreetMap** sin requerir API Keys de pago.
- Botón interactivo de navegación "Cómo llegar" enlazado dinámicamente con Google Maps Directions.
- Enlace directo de contacto por WhatsApp con mensaje preconfigurado y contextualizado con el título de la propiedad.

---

## [1.1.0] - 2026-09-07

### Añadido
- Lanzamiento inicial del Custom Post Type `gip_property`.
- Taxonomías base: `gip_property_type`, `gip_city`, `gip_neighborhood`, `gip_operation`, `gip_status` y `gip_amenity`.
- Meta boxes para habitaciones, baños, parqueaderos, área construida, área de lote y precio (COP/USD).
- Plantillas de fallback para archivo (`archive-gip_property.php`) y ficha de detalle (`single-gip_property.php`).
