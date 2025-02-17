<?php

//--------------------------------------------------------------------------
// Documentos
//--------------------------------------------------------------------------


// Agregar filtro para categorías de documentos en el listado de administración
function gsl_add_categoria_documento_filter() {
    $screen = get_current_screen();
    if ($screen->post_type === 'documento') {
        // Obtenemos las categorías de documentos
        $terms = get_terms([
            'taxonomy' => 'categoria_documento',
            'hide_empty' => false, // Mostrar incluso las categorías vacías
        ]);
        
        // Si hay categorías, las mostramos como un filtro
        if (!empty($terms)) {
            echo '<select name="categoria_documento_filter" id="categoria_documento_filter">';
            echo '<option value="">' . __('Todas las Categorías', 'gsl') . '</option>';
            
            // Opción para documentos sin categoría
            echo '<option value="0"' . (isset($_GET['categoria_documento_filter']) && $_GET['categoria_documento_filter'] == '0' ? ' selected="selected"' : '') . '>' . __('Sin categoría', 'gsl') . '</option>';
            
            foreach ($terms as $term) {
                // Comprobamos si la categoría está seleccionada
                $selected = (isset($_GET['categoria_documento_filter']) && $_GET['categoria_documento_filter'] == $term->term_id) ? ' selected="selected"' : '';
                echo '<option value="' . esc_attr($term->term_id) . '" ' . $selected . '>' . esc_html($term->name) . '</option>';
            }
            echo '</select>';
        }
    }
}
add_action('restrict_manage_posts', 'gsl_add_categoria_documento_filter');

// Filtrar documentos por categoría
function gsl_filter_documentos_by_categoria($query) {
    global $pagenow;

    // Verificar que estamos en el área de administración, en la página correcta y con el post_type adecuado
    if (is_admin() && $pagenow === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'documento' && isset($_GET['categoria_documento_filter'])) {
        
        $categoria_filter = $_GET['categoria_documento_filter'];
        
        if ($categoria_filter !== '') {
            $tax_query = array();
            
            if ($categoria_filter === '0') {
                // Documentos SIN categoría
                $tax_query[] = array(
                    'taxonomy' => 'categoria_documento',
                    'operator' => 'NOT EXISTS', // No tiene términos en esta taxonomía
                );
            } else {
                // Filtrar por categoría específica
                $tax_query[] = array(
                    'taxonomy' => 'categoria_documento',
                    'field'    => 'term_id',
                    'terms'    => array($categoria_filter),
                );
            }
            
            // Aplicar la tax_query a la consulta principal
            $query->set('tax_query', $tax_query);
        }
    }
}
add_action('pre_get_posts', 'gsl_filter_documentos_by_categoria');


//--------------------------------------------------------------------------
// Productos
//--------------------------------------------------------------------------

// Agregar filtro para marcas de productos en el listado de administración
function gsl_add_marca_producto_filter() {
    $screen = get_current_screen();
    if ($screen->post_type === 'producto') {
        // Obtenemos las marcas
        $terms = get_terms([
            'taxonomy' => 'marca',
            'hide_empty' => false, // Mostrar incluso las marcas vacías
        ]);
        
        // Si hay marcas, las mostramos como un filtro
        if (!empty($terms)) {
            echo '<select name="marca_producto_filter" id="marca_producto_filter">';
            echo '<option value="">' . __('Todas las Marcas', 'gsl') . '</option>';
            
            // Opción para productos sin marca
            echo '<option value="0"' . (isset($_GET['marca_producto_filter']) && $_GET['marca_producto_filter'] == '0' ? ' selected="selected"' : '') . '>' . __('Sin Marca', 'gsl') . '</option>';
            
            foreach ($terms as $term) {
                // Comprobamos si la marca está seleccionada
                $selected = (isset($_GET['marca_producto_filter']) && $_GET['marca_producto_filter'] == $term->term_id) ? ' selected="selected"' : '';
                echo '<option value="' . esc_attr($term->term_id) . '" ' . $selected . '>' . esc_html($term->name) . '</option>';
            }
            echo '</select>';
        }
    }
}
add_action('restrict_manage_posts', 'gsl_add_marca_producto_filter');

// Filtrar productos por marca
function gsl_filter_productos_by_marca($query) {
    global $pagenow;

    // Verificar que estamos en el área de administración, en la página correcta y con el post_type adecuado
    if (is_admin() && $pagenow === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'producto' && isset($_GET['marca_producto_filter'])) {
        
        $marca_filter = $_GET['marca_producto_filter'];
        
        if ($marca_filter !== '') {
            $tax_query = array();
            
            if ($marca_filter === '0') {
                // Productos SIN marca
                $tax_query[] = array(
                    'taxonomy' => 'marca',
                    'operator' => 'NOT EXISTS', // No tiene términos en esta taxonomía
                );
            } else {
                // Filtrar por marca específica
                $tax_query[] = array(
                    'taxonomy' => 'marca',
                    'field'    => 'term_id',
                    'terms'    => array($marca_filter),
                );
            }
            
            // Aplicar la tax_query a la consulta principal
            $query->set('tax_query', $tax_query);
        }
    }
}
add_action('pre_get_posts', 'gsl_filter_productos_by_marca');

// Agregar filtro para cliente relacionado en el listado de administración
function gsl_add_cliente_relacionado_filter() {
    $screen = get_current_screen();
    if ($screen->post_type === 'producto') {
        // Obtener todos los clientes
        $clientes = get_users(['role' => 'cliente']);

        // Mostrar el filtro de clientes relacionados
        echo '<select name="cliente_relacionado_filter" id="cliente_relacionado_filter">';
        echo '<option value="">' . __('Todos los Clientes', 'gsl') . '</option>';

        // Opción para productos sin cliente relacionado
        echo '<option value="0"' . (isset($_GET['cliente_relacionado_filter']) && $_GET['cliente_relacionado_filter'] === '0' ? ' selected="selected"' : '') . '>' . __('No relacionado', 'gsl') . '</option>';

        // Opciones para clientes existentes
        foreach ($clientes as $cliente) {
            $selected = (isset($_GET['cliente_relacionado_filter']) && $_GET['cliente_relacionado_filter'] == $cliente->ID) ? ' selected="selected"' : '';
            echo '<option value="' . esc_attr($cliente->ID) . '"' . $selected . '>' . esc_html($cliente->display_name) . '</option>';
        }
        echo '</select>';
    }
}
add_action('restrict_manage_posts', 'gsl_add_cliente_relacionado_filter');

// Filtrar productos por cliente relacionado
function gsl_filter_productos_by_cliente_relacionado($query) {
    global $pagenow;

    // Verificar que estamos en el área de administración, en la página correcta y con el post_type adecuado
    if (is_admin() && $pagenow === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'producto' && isset($_GET['cliente_relacionado_filter'])) {
        $cliente_filter = $_GET['cliente_relacionado_filter'];

        if ($cliente_filter !== '') {
            if ($cliente_filter === '0') {
                // Productos SIN cliente relacionado
                $meta_query = [
                    'relation' => 'OR',
                    [
                        'key'     => 'gsl_cliente_relacionado',
                        'compare' => 'NOT EXISTS', // No tiene el metadato
                    ],
                    [
                        'key'     => 'gsl_cliente_relacionado',
                        'value'   => '', // Metadato vacío
                        'compare' => '=',
                    ],
                ];
            } else {
                // Filtrar por cliente relacionado específico
                $meta_query = [
                    [
                        'key'     => 'gsl_cliente_relacionado',
                        'value'   => $cliente_filter,
                        'compare' => '=',
                    ],
                ];
            }

            // Aplicar la meta_query a la consulta principal
            $query->set('meta_query', $meta_query);
        }
    }
}
add_action('pre_get_posts', 'gsl_filter_productos_by_cliente_relacionado');
