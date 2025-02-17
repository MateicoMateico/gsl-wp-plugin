<?php

/**
 * Encolar scripts y estilos para el área de administración
 */
function gsl_admin_enqueue_scripts() {

    // Encolar el CSS de Select2
    wp_enqueue_style( 'select2-css', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css' );

    // Encolar el script de Select2, dependiente de jQuery
    wp_enqueue_script( 'select2-js', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', array('jquery'), '4.0.13', true );
    
    // Inicializar Select2 en nuestro campo específico
    wp_add_inline_script( 'select2-js', "
        jQuery(document).ready(function($) {
            $('select[name=\"gsl_cliente_relacionado\"]').select2({
                placeholder: '" . esc_js( __( 'Seleccione un cliente', 'gsl' ) ) . "',
                allowClear: true
            });
        });
    " );

    // Importante para usar el media uploader de WordPress
    wp_enqueue_media();
    
    // Encolar el script personalizado (asegúrate de que el archivo exista en la ruta indicada)
    wp_enqueue_script(
        'gsl-custom-js',
        plugin_dir_url( dirname(__FILE__) ) . 'assets/js/gsl-custom-scripts.js',
        array('jquery'),
        '1.0',
        true
    );


    // Encolar Thickbox en páginas de usuarios (por ejemplo, para el uploader en el perfil)
    $screen = get_current_screen();
    if ( $screen && 'users' === $screen->base ) {
        wp_enqueue_script('thickbox');
        wp_enqueue_style('thickbox');
    }
    
    // Encolar para busqueda de docs
    wp_enqueue_script('jquery-ui-sortable');
    wp_localize_script('gsl-custom-js', 'gsl_vars', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('gsl_documentos_nonce')
    ]);

     // Encolar estilos
     wp_enqueue_style(
        'gsl-documentos-style',
        plugin_dir_url( dirname(__FILE__) ) . 'assets/css/gsl-custom-styles.css',
        array(),
        '1.0.0'
    );
}
add_action('admin_enqueue_scripts', 'gsl_admin_enqueue_scripts');

/**
 * Encolar estilos y scripts para el frontend
 */
function gsl_enqueue_documentos_assets() {
    // Encolar estilos
    wp_enqueue_style(
        'gsl-documentos-style',
        plugin_dir_url( dirname(__FILE__) ) . 'assets/css/gsl-custom-styles.css',
        array(),
        '1.0.0'
    );

    // Encolar las librerías para generar el PDF
    wp_enqueue_script(
        'html2canvas',
        'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js',
        array(),
        '1.4.1',
        true
    );

    wp_enqueue_script(
        'jspdf',
        'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js',
        array(),
        '2.5.1',
        true
    );

    // Encolar tu script personalizado (asegúrate de que este se cargue DESPUÉS de las librerías anteriores)
    wp_enqueue_script(
        'gsl-custom-js',
        plugin_dir_url( dirname(__FILE__) ) . 'assets/js/gsl-custom-scripts.js',
        array('jquery', 'html2canvas', 'jspdf'),
        '1.0',
        true
    );
}
add_action('wp_enqueue_scripts', 'gsl_enqueue_documentos_assets');

