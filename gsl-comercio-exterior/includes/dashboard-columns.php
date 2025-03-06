<?php

//--------------------------------------------------------------------------
// Productos
//--------------------------------------------------------------------------

// Agregar columnas personalizadas para productos
function gsl_add_producto_columns($columns) {
    $columns['gsl_codigo'] = __('Código', 'gsl');
    $columns['gsl_cliente_relacionado'] = __('Cliente Relacionado', 'gsl');
    $columns['gsl_documentos_relacionados'] = __('Documentos Relacionados', 'gsl');
    $columns['gsl_marca'] = __('Marca', 'gsl');
    return $columns;
}
add_filter('manage_producto_posts_columns', 'gsl_add_producto_columns');

// Renderizar contenido de columnas personalizadas para productos
function gsl_render_producto_columns($column, $post_id) {
    if ($column === 'gsl_cliente_relacionado') {
        $cliente_id = get_post_meta($post_id, 'gsl_cliente_relacionado', true);
        if ($cliente_id) {
            $cliente = get_user_by('ID', $cliente_id);
            echo $cliente ? esc_html($cliente->display_name) : __('Cliente no encontrado', 'gsl');
        } else {
            echo __('No relacionado', 'gsl');
        }
    }

    if ($column === 'gsl_documentos_relacionados') {
        $documentos_ids = get_post_meta($post_id, 'gsl_documentos_relacionados', true);
        if (!empty($documentos_ids)) {
            foreach ($documentos_ids as $doc_id) {
                echo '<a href="' . get_edit_post_link($doc_id) . '">' . esc_html(get_the_title($doc_id)) . '</a><br>';
            }
        } else {
            echo __('No relacionados', 'gsl');
        }
    }

    if ($column === 'gsl_codigo') {
        $codigo = get_post_meta($post_id, '_gsl_producto_codigo', true);
        echo $codigo ? esc_html($codigo) : __('NA', 'gsl');
    }

    if ($column === 'gsl_marca') {
        $marcas = wp_get_post_terms($post_id, 'marca', ['fields' => 'names']);
        if (!empty($marcas)) {
            echo esc_html(implode(', ', $marcas));
        } else {
            echo __('NA', 'gsl');
        }
    }
}
add_action('manage_producto_posts_custom_column', 'gsl_render_producto_columns', 10, 2);



//--------------------------------------------------------------------------
// Documentos
//--------------------------------------------------------------------------

// Agregar columnas personalizadas para documentos
function gsl_add_documento_columns($columns) {
    $columns['gsl_archivo'] = __('Archivo', 'gsl');
    $columns['gsl_categoria_documento'] = __('Categorías', 'gsl');
    return $columns;
}
add_filter('manage_documento_posts_columns', 'gsl_add_documento_columns');

// Renderizar contenido de columnas personalizadas para documentos
function gsl_render_documento_columns($column, $post_id) {
    if ($column === 'gsl_archivo') {
        // Ahora obtenemos el attachment ID en lugar de la URL
        $attachment_id = get_post_meta($post_id, 'gsl_documento_attachment_id', true);
        if ($attachment_id) {
            $archivo_url = wp_get_attachment_url($attachment_id);
            if ($archivo_url) {
                $nombre_archivo = basename($archivo_url); // Obtiene el nombre del archivo a partir de la URL
                echo '<a href="' . esc_url($archivo_url) . '" target="_blank">' . esc_html($nombre_archivo) . '</a>';
            } else {
                echo __('No definido', 'gsl');
            }
        } else {
            echo __('No definido', 'gsl');
        }
    }

    if ($column === 'gsl_categoria_documento') {
        $categorias = wp_get_post_terms($post_id, 'categoria_documento', ['fields' => 'names']);
        if (!empty($categorias)) {
            echo esc_html(implode(', ', $categorias));
        } else {
            echo __('Sin categoría', 'gsl');
        }
    }
}
add_action('manage_documento_posts_custom_column', 'gsl_render_documento_columns', 10, 2);


//--------------------------------------------------------------------------
// Usuarios
//--------------------------------------------------------------------------

// Agregar columnas personalizadas para usuarios
function gsl_add_user_columns($columns) {
    $columns['gsl_imagen_cliente'] = __('Imagen Cliente', 'gsl');
    return $columns;
}
add_filter('manage_users_columns', 'gsl_add_user_columns');

// Renderizar contenido de columnas personalizadas para usuarios
function gsl_render_user_columns($value, $column, $user_id) {
    if ($column === 'gsl_imagen_cliente') {
        $user = get_userdata($user_id);
        
        if (!$user || !in_array('cliente', (array) $user->roles)) {
            return '—';
        }
        
        $attachment_id = get_user_meta($user_id, 'gsl_imagen_cliente', true);
        
        if ($attachment_id && wp_attachment_is_image($attachment_id)) {
            $full_image = wp_get_attachment_image_url($attachment_id, 'full');
            $thumbnail = wp_get_attachment_image($attachment_id, 'thumbnail', false, [
                'style' => 'max-width:50px; height:auto; cursor:pointer;',
                'class' => 'gsl-client-thumb'
            ]);
            
            return sprintf(
                '<a href="%s" class="thickbox" title="%s" data-alt="%s">%s</a>',
                esc_url($full_image),
                esc_attr(get_the_title($attachment_id)),
                esc_attr__('Imagen del cliente', 'gsl'),
                $thumbnail
            );
        }
        
        return '<span class="dashicons dashicons-camera" style="font-size:32px; color:#ddd;"></span>';
    }
    
    return $value;
}
add_filter('manage_users_custom_column', 'gsl_render_user_columns', 10, 3);