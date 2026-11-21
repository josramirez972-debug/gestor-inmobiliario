# 🏢 Gestor Inmobiliario - Plugin para WordPress

[![WordPress](https://img.shields.io/badge/WordPress-6.4%2B-blue.svg)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-777bb4.svg)](https://php.net)
[![Elementor Compatible](https://img.shields.io/badge/Elementor-Compatible-92003b.svg)](https://elementor.com)
[![License: GPL v2+](https://img.shields.io/badge/License-GPL%20v2%2B-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

Plugin profesional y modular para WordPress desarrollado en **PHP 8.1+** con arquitectura orientada a objetos (OOP), diseñado para gestionar inventarios de propiedades inmobiliarias (venta y arriendo), filtrado asíncrono con AJAX, integración nativa de widgets para **Elementor**, mapas interactivos con **Leaflet / OpenStreetMap** y captura de leads con protección antispam.

---

## 🌟 Características Principales

### 1. Gestión de Propiedades (Custom Post Type)
- **CPT `gip_property`**: Registro desacoplado y optimizado con soporte para títulos, editor Gutenberg/clásico, imagen destacada y extractos.
- **Taxonomías Jerárquicas y Personalizadas**:
  - `gip_city`: Ciudades.
  - `gip_neighborhood`: Barrios y sectores.
  - `gip_property_type`: Tipos de inmueble (Apartamento, Casa, etc.).
- **Taxonomías Planas y Estados Comerciales**:
  - `gip_operation`: Modalidad de negocio (Venta, Arriendo).
  - `gip_status`: Control de inventario en tiempo real (Disponible, Reservada, Vendida, Arrendada, Inactiva).
  - `gip_amenity`: Amenidades (Piscina, Ascensor, Vigilancia, etc.).

### 2. Meta Boxes & WordPress Media API
- **Detalles técnicos**: Habitaciones, baños, parqueaderos, área construida ($m^2$), área de lote ($m^2$), precio configurable en múltiples divisas (COP / USD) y switch de propiedad destacada.
- **Galería Multi-Imagen Nativa**: Integración directa con `wp.media` de WordPress para subir y ordenar múltiples imágenes sin recarga y guardadas como metadatos serializados/indexados.
- **Geolocalización & Multimedia**: Campos de latitud y longitud georreferenciadas y URL para tour virtual 360°.
- **Ficha de Agente Inmobiliario**: Asignación de agente con foto de perfil (`wp.media`), cargo, contacto directo vía WhatsApp y correo electrónico.

### 3. Frontend & Experiencia de Usuario (UX)
- **Buscador & Filtros Reactivos (AJAX)**: Filtrado asíncrono en tiempo real por ciudad, tipo, habitaciones y baños con `fetch()` sin dependencias de librerías pesadas en el frontend (Vanilla JavaScript puro).
- **Mapas con Leaflet & OpenStreetMap**: Visualización interactiva y responsive sin costo ni dependencia de API Keys comerciales (Google Maps API), con enlace directo a indicaciones de ruta (*Cómo llegar*).
- **Slider / Galería Interactiva**: Galería responsiva con selector de miniaturas, contador de imágenes y navegación táctil/click.
- **Formulario de Captura de Prospectos (Leads)**:
  - Procesamiento seguro a través de `admin-post.php`.
  - Notificaciones por correo electrónico al administrador con enlace de retorno.
  - Botón directo de contacto a WhatsApp con mensaje preconfigurado y contextualizado al inmueble.
  - Validación de políticas de privacidad / RGPD / Habeas Data.

### 4. Integración con Elementor
- Categoría dedicada: **Gestor Inmobiliario** en el panel de Elementor.
- 3 Widgets nativos:
  1. `GIP_Elementor_Listing`: Parrilla de propiedades configurable por operación y límite.
  2. `GIP_Elementor_Search`: Formulario de búsqueda avanzada.
  3. `GIP_Elementor_Featured`: Módulo de inmuebles destacados.
- Shortcodes disponibles para cualquier constructor o editor:
  ```text
  [gestor_inmobiliario_busqueda]
  [gestor_inmobiliario_listado]
  [gestor_inmobiliario_listado featured="1" limit="3"]
  ```

---

## 🔒 Arquitectura y Buenas Prácticas de Seguridad

- **Patrón Singleton**: Instancia única `GIP_Plugin::instance()` que evita conflictos en el ciclo de vida de WordPress y doble inicialización mediante la guarda `GIP_BOOTSTRAPPED`.
- **Protección CSRF**: Uso estricto de Nonces criptográficos en formularios de administración (`gip_save_property`), peticiones AJAX (`gip_filter`) y formularios de contacto (`gip_contact`).
- **Control de Capacidades y Permisos**: Verificación explícita de `current_user_can('edit_post', $post_id)` previa a la persistencia de datos.
- **Sanitización y Escapado**:
  - Entrada: `sanitize_text_field`, `sanitize_email`, `sanitize_textarea_field`, `absint`, `esc_url_raw`.
  - Salida: `esc_html`, `esc_attr`, `esc_url`, `number_format_i18n`.
- **Antispam Honeypot**: Campo señuelo trampa (`website`) indetectable para humanos que bloquea bots automáticos sin necesidad de captchas invasivos.
- **Template Fallback Pattern**: Detección automática en `template_include` para inyectar `single-gip_property.php` y `archive-gip_property.php` garantizando compatibilidad con cualquier tema activo.

---

## 📂 Estructura del Proyecto

```text
gestor-inmobiliario/
├── assets/
│   ├── css/
│   │   └── public.css              # Estilos modulares, CSS Grid, Flexbox y temas responsive
│   └── js/
│       ├── admin.js                # Integración con wp.media para galería y avatar de agente
│       └── public.js               # Lógica AJAX (fetch), galería interactiva y mapa Leaflet
├── includes/
│   ├── class-gip-plugin.php        # Core orquestador del plugin (Singleton)
│   ├── class-gip-cpt.php           # Registro encapsulado de CPT y 6 taxonomías
│   ├── class-gip-meta-boxes.php    # Meta Boxes, persistencia, permisos y sanitización
│   ├── class-gip-query.php         # Servicio de consulta (WP_Query), filtros y renderizado
│   ├── class-gip-ajax.php          # Endpoint asíncrono con verificación de Nonces
│   ├── class-gip-contact.php       # Enrutamiento de contacto y Honeypot antispam
│   └── class-gip-elementor.php     # Widgets y controles para Elementor Widget API
├── templates/
│   ├── archive-gip_property.php    # Plantilla de listado general de inmuebles
│   └── single-gip_property.php     # Ficha detallada de la propiedad y sidebar de contacto
├── CHANGELOG.md                    # Historial detallado de versiones (v1.0.0 a v1.5.0)
├── gestor-inmobiliario.php         # Archivo bootstrap y cabeceras de WordPress
├── .gitignore                      # Exclusiones de archivos del sistema y temporales
└── README.md                       # Documentación técnica
```

---

## 🚀 Instalación y Despliegue

### Opción 1: Vía Git (Recomendada para desarrolladores)
1. Clona el repositorio dentro de tu directorio de plugins de WordPress:
   ```bash
   cd wp-content/plugins/
   git clone https://github.com/josramirez972-debug/gestor-inmobiliario.git
   ```
2. Activa el plugin desde el panel de administración de WordPress o mediante WP-CLI:
   ```bash
   wp plugin activate gestor-inmobiliario
   ```
3. Guarda los enlaces permanentes en **Ajustes > Enlaces permanentes** para refrescar las reglas de reescritura (*Rewrite Rules*).

### Opción 2: Archivo ZIP
1. Descarga o comprime la carpeta `gestor-inmobiliario` en un archivo `.zip`.
2. Dirígete a **Plugins > Añadir nuevo > Subir plugin**.
3. Selecciona el archivo `.zip`, instálalo y actívalo.

---

## ⚙️ Requisitos del Entorno

- **WordPress**: 6.4 o superior.
- **PHP**: 8.1 o superior (tipado estricto, unión de tipos y match syntax).
- **Elementor** (Opcional): Requerido únicamente para utilizar los widgets visuales de arrastrar y soltar.

---

## 👨‍💻 Autor

Desarrollado por [José Ramírez](https://github.com/josramirez972-debug).
Repositorio publicado bajo licencia [GPL-2.0-or-later](https://www.gnu.org/licenses/gpl-2.0.html).
