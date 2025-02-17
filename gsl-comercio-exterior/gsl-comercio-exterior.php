<?php
/**
 * Plugin Name: GSL Comercio Exterior
 * Description: Gestión de productos y documentos relacionados con clientes en comercio exterior.
 * Version: 1.0
 * Author: Mateico
 * Text Domain: gsl
 * Domain Path: /languages
 */

// Evitar el acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// ==============================================================================
//                  Registrar Custom Post Types y Taxonomías
// ==============================================================================
require_once plugin_dir_path(__FILE__) . 'includes/post-types.php';
require_once plugin_dir_path(__FILE__) . 'includes/taxonomies.php';

// ==============================================================================
//                  Registrar Role y Capacidades para Clientes
// ==============================================================================
require_once plugin_dir_path(__FILE__) . 'includes/roles.php';

// ==============================================================================
//                                  METABOXES
// ==============================================================================
require_once plugin_dir_path(__FILE__) . 'includes/metaboxes.php';


// ==============================================================================
//                          VISUALIZACIÓN EN COLUMNAS DASHBOARD
// ==============================================================================
require_once plugin_dir_path(__FILE__) . 'includes/dashboard-columns.php';


// ==============================================================================
//                                 FILTROS DASHBOARD
// ==============================================================================
require_once plugin_dir_path(__FILE__) . 'includes/dashboard-filters.php';


// ==============================================================================
//                                  FUNCIONES
// ==============================================================================
require_once plugin_dir_path(__FILE__) . 'includes/custom-functions.php';


// ==============================================================================
//                                  ENQUEUES
// ==============================================================================
require_once plugin_dir_path(__FILE__) . 'includes/enqueues.php';

// ==============================================================================
//                                 SHORTCODES
// ==============================================================================
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes.php';

