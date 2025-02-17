<?php
// ---------------------------------------------------------------------------------
//                                  Crear Metaboxes Personalizados
// ---------------------------------------------------------------------------------

/**
 * Agregar Metaboxes: Relacionar Documentos, Cliente, Modelo y Subir Archivos
 */
function gsl_agregar_metaboxes()
{
    // Metabox para documentos relacionados con productos
    add_meta_box('gsl_producto_documentos', __('Documentos Relacionados', 'gsl'), 'gsl_render_metabox_documentos', 'producto', 'normal');

    // Metabox para cliente relacionado con producto
    add_meta_box('gsl_producto_cliente', __('Cliente Relacionado', 'gsl'), 'gsl_render_metabox_cliente', 'producto', 'normal');

    // Metabox para subir documentos directamente en "documento"
    add_meta_box('gsl_documento_archivo', __('Subir Documento', 'gsl'), 'gsl_render_metabox_archivo', 'documento', 'normal');

    // Metabox para agregar Modelo a productos
    add_meta_box('gsl_producto_modelo', __('Modelo del Producto', 'gsl'), 'gsl_render_metabox_modelo', 'producto', 'normal');

    // Metabox para agregar Código a productos (nuevo)
    add_meta_box('gsl_producto_codigo', __('Código del Producto', 'gsl'), 'gsl_render_metabox_codigo', 'producto', 'normal');
}
add_action('add_meta_boxes', 'gsl_agregar_metaboxes');

// ---------------------------------------------------------------------------------
//                              Renderizado y guardado
// ---------------------------------------------------------------------------------

/**
 * Renderizar Metabox: Documentos Relacionados (Dual List Box)
 */
function gsl_render_metabox_documentos($post) {
    // Añadir nonce para seguridad
    wp_nonce_field('gsl_documentos_nonce', 'gsl_documentos_nonce_field');
    
    // Obtener los documentos ya asignados (si existen)
    $documentos_seleccionados = get_post_meta($post->ID, 'gsl_documentos_relacionados', true) ?: [];
    $documentos_seleccionados = array_map('intval', $documentos_seleccionados);
    ?>
    <div id="dual-list-container">
        <!-- Lista de documentos sin asignar (se cargarán vía AJAX) -->
        <div class="list-box" id="unassigned-box">
            <h4>Sin asignar</h4>
            <input type="text" id="gsl-buscar-documentos" placeholder="Buscar documentos...">
            <ul id="unassigned-list">
                <!-- Se cargarán los documentos vía AJAX -->
            </ul>
            <div id="pagination-controls">
                <button id="prev-page" disabled>Anterior</button>
                <span id="current-page">1</span>
                <button id="next-page">Siguiente</button>
            </div>
        </div>
        <!-- Lista de documentos asignados -->
        <div class="list-box" id="assigned-box">
            <h4>Asignados</h4>
            <ul id="assigned-list">
                <?php 
                if ( !empty($documentos_seleccionados) ) {
                    $args = [
                        'post_type'      => 'documento',
                        'post__in'       => $documentos_seleccionados,
                        'orderby'        => 'title',
                        'order'          => 'ASC',
                        'posts_per_page' => -1
                    ];
                    $asignados_query = new WP_Query($args);
                    if ( $asignados_query->have_posts() ) {
                        while ( $asignados_query->have_posts() ) {
                            $asignados_query->the_post();
                            echo '<li data-id="' . get_the_ID() . '">' . esc_html(get_the_title()) . '</li>';
                        }
                        wp_reset_postdata();
                    }
                }
                ?>
            </ul>
        </div>
    </div>
    <!-- Campo oculto para almacenar los IDs asignados (CSV) -->
    <input type="hidden" name="gsl_documentos_relacionados" id="gsl_documentos_relacionados" value="<?php echo implode(',', $documentos_seleccionados); ?>" />
    <?php
}

/**
 * Guardar Metadatos: Documentos Relacionados
 */
function gsl_guardar_meta_documentos($post_id) {
    if (!isset($_POST['gsl_documentos_nonce_field']) || !wp_verify_nonce($_POST['gsl_documentos_nonce_field'], 'gsl_documentos_nonce')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    $nuevos_documentos = [];
    if (!empty($_POST['gsl_documentos_relacionados'])) {
        $nuevos_documentos = explode(',', $_POST['gsl_documentos_relacionados']);
        $nuevos_documentos = array_map('intval', $nuevos_documentos);
        $nuevos_documentos = array_filter($nuevos_documentos);
    }
    $actuales = get_post_meta($post_id, 'gsl_documentos_relacionados', true) ?: [];
    if ($nuevos_documentos != $actuales) {
        update_post_meta($post_id, 'gsl_documentos_relacionados', $nuevos_documentos);
    }
}
add_action('save_post_producto', 'gsl_guardar_meta_documentos');
/**
 * Endpoint AJAX para cargar documentos paginados y con búsqueda,
 * excluyendo los ya asignados.
 */
function gsl_get_documentos_ajax() {
    check_ajax_referer('gsl_documentos_nonce', 'nonce');

    $paged        = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $search_query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
    $assigned_csv = isset($_POST['assigned']) ? sanitize_text_field($_POST['assigned']) : '';
    
    $args = [
        'post_type'      => 'documento',
        'posts_per_page' => 20,
        'paged'          => $paged,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ];
    
    if (!empty($search_query)) {
        $args['s'] = $search_query;
    }
    
    if (!empty($assigned_csv)) {
        $assigned_ids = array_filter(array_map('intval', explode(',', $assigned_csv)));
        if (!empty($assigned_ids)) {
            $args['post__not_in'] = $assigned_ids;
        }
    }
    
    $query = new WP_Query($args);
    $html = '';
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $html .= '<li data-id="' . get_the_ID() . '">' . esc_html(get_the_title()) . '</li>';
        }
    } else {
        $html .= '<li>No se encontraron documentos.</li>';
    }
    
    $response = [
        'html'      => $html,
        'max_pages' => $query->max_num_pages
    ];
    
    wp_reset_postdata();
    wp_send_json_success($response);
}
add_action('wp_ajax_get_documentos', 'gsl_get_documentos_ajax');




/**
 * Renderizar Metabox: Cliente Relacionado
 */
function gsl_render_metabox_cliente($post)
{
    $clientes = get_users(['role' => 'cliente']);
    $cliente_seleccionado = get_post_meta($post->ID, 'gsl_cliente_relacionado', true);

    echo '<select name="gsl_cliente_relacionado" style="width: 100%;">';
    echo '<option value="">' . __('Ninguno', 'gsl') . '</option>';
    foreach ($clientes as $cliente) {
        $selected = ($cliente->ID == $cliente_seleccionado) ? 'selected' : '';
        echo '<option value="' . $cliente->ID . '" ' . $selected . '>' . esc_html($cliente->display_name) . '</option>';
    }
    echo '</select>';
}

/**
 * Guardar Metadatos: Cliente Relacionado
 */
function gsl_guardar_meta_cliente($post_id)
{
    if (isset($_POST['gsl_cliente_relacionado'])) {
        update_post_meta($post_id, 'gsl_cliente_relacionado', sanitize_text_field($_POST['gsl_cliente_relacionado']));
    }
}
add_action('save_post_producto', 'gsl_guardar_meta_cliente');

/**
 * Renderizar Metabox: Modelo 
 */
function gsl_render_metabox_modelo($post)
{
    $modelo = get_post_meta($post->ID, '_gsl_producto_modelo', true);
    echo '<label for="gsl_producto_modelo">' . __('Ingrese el Modelo:', 'gsl') . '</label>';
    echo '<input type="text" id="gsl_producto_modelo" name="gsl_producto_modelo" value="' . esc_attr($modelo) . '" style="width:100%;" />';
}

/**
 * Guardar Metadatos: Modelo
 */
function gsl_guardar_meta_modelo($post_id)
{
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    if (isset($_POST['gsl_producto_modelo'])) {
        $modelo = sanitize_text_field($_POST['gsl_producto_modelo']);
        update_post_meta($post_id, '_gsl_producto_modelo', $modelo);
    }
}
add_action('save_post_producto', 'gsl_guardar_meta_modelo');

/**
 * Renderizar Metabox: Código
 */
function gsl_render_metabox_codigo($post)
{
    $codigo = get_post_meta($post->ID, '_gsl_producto_codigo', true);
    echo '<label for="gsl_producto_codigo">' . __('Ingrese el Código:', 'gsl') . '</label>';
    echo '<input type="text" id="gsl_producto_codigo" name="gsl_producto_codigo" value="' . esc_attr($codigo) . '" style="width:100%;" />';
}

/**
 * Guardar Metadatos: Código
 */
function gsl_guardar_meta_codigo($post_id)
{
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    if (isset($_POST['gsl_producto_codigo'])) {
        $codigo = sanitize_text_field($_POST['gsl_producto_codigo']);
        update_post_meta($post_id, '_gsl_producto_codigo', $codigo);
    }
}
add_action('save_post_producto', 'gsl_guardar_meta_codigo');


/**
 * Renderizar Metabox: Seleccionar/Actualizar Archivo (usando WP Media Uploader)
 */
function gsl_render_metabox_archivo($post)
{
    // Se guarda el attachment ID en lugar de la URL.
    $attachment_id = get_post_meta($post->ID, 'gsl_documento_attachment_id', true);

    wp_nonce_field('guardar_gsl_documento', 'gsl_nonce');
?>
    <div id="gsl-archivo-wrapper">
        <p>
            <button type="button" class="button" id="gsl_select_archivo"><?php _e('Seleccionar archivo', 'gsl'); ?></button>
            <button type="button" class="button" id="gsl_remove_archivo" style="<?php echo $attachment_id ? '' : 'display:none;'; ?>"><?php _e('Eliminar archivo', 'gsl'); ?></button>
        </p>
        <div id="gsl_archivo_info">
            <?php if ($attachment_id) :
                $archivo_url    = wp_get_attachment_url($attachment_id);
                $file_path      = get_attached_file($attachment_id);

                // Cálculo del tamaño del archivo con 2 decimales
                $file_size = '';
                if (file_exists($file_path)) {
                    $size_in_bytes = filesize($file_path);
                    if ($size_in_bytes >= 1048576) {
                        $file_size = round($size_in_bytes / 1048576, 2) . ' MB';
                    } else {
                        $file_size = round($size_in_bytes / 1024, 2) . ' KB';
                    }
                }

                $file_extension = strtoupper(pathinfo($archivo_url, PATHINFO_EXTENSION));
            ?>
                <p><?php printf(__('Archivo seleccionado: %s', 'gsl'), '<a href="' . esc_url($archivo_url) . '" target="_blank">' . basename($archivo_url) . '</a>'); ?></p>
                <p><?php printf(__('Tipo: %s', 'gsl'), esc_html($file_extension)); ?></p>
                <p><?php printf(__('Tamaño: %s', 'gsl'), esc_html($file_size)); ?></p>
            <?php else: ?>
                <p><?php _e('Ningún archivo seleccionado.', 'gsl'); ?></p>
            <?php endif; ?>
        </div>
        <input type="hidden" name="gsl_documento_attachment_id" id="gsl_documento_attachment_id" value="<?php echo esc_attr($attachment_id); ?>" />
    </div>
<?php
}

/**
 * Guardar Metadatos: Archivo seleccionado con el Media Uploader
 */
function gsl_guardar_meta_archivo($post_id)
{
    // Verificar nonce y permisos
    if (! isset($_POST['gsl_nonce']) || ! wp_verify_nonce($_POST['gsl_nonce'], 'guardar_gsl_documento')) {
        return;
    }
    if (! current_user_can('edit_post', $post_id)) {
        return;
    }

    $new_attachment_id = isset($_POST['gsl_documento_attachment_id']) ? intval($_POST['gsl_documento_attachment_id']) : 0;
    $old_attachment_id = get_post_meta($post_id, 'gsl_documento_attachment_id', true);

    // Si se eliminó la selección, se borra el meta
    if (! $new_attachment_id) {
        // Opcional: eliminar el attachment de la biblioteca si ya no se usa
        // if ( $old_attachment_id ) {
        //     wp_delete_attachment( $old_attachment_id, true );
        // }
        delete_post_meta($post_id, 'gsl_documento_attachment_id');
    } else {
        update_post_meta($post_id, 'gsl_documento_attachment_id', $new_attachment_id);
    }
}
add_action('save_post_documento', 'gsl_guardar_meta_archivo');
