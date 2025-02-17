<?php
//-------------------------------------------------------
// Listar documentos relacionados con un producto
//-------------------------------------------------------

function gsl_documentos_relacionados_producto_shortcode($atts)
{
    global $post;

    // Se espera que en el producto se guarde un array de IDs de documentos relacionados
    $documentos_relacionados = get_post_meta($post->ID, 'gsl_documentos_relacionados', true);

    if (empty($documentos_relacionados)) {
        return '<div class="gsl-documentos-grid"><p>No hay documentos relacionados.</p></div>';
    }

    $output = '<div class="gsl-documentos-grid">';

    foreach ($documentos_relacionados as $documento_id) {
        // Se obtiene el attachment ID del documento
        $attachment_id = get_post_meta($documento_id, 'gsl_documento_attachment_id', true);
        $titulo = get_the_title($documento_id);

        // Obtener la categoría del documento (asumiendo que usas una taxonomía "categoria_documento")
        $categoria_terms = wp_get_post_terms($documento_id, 'categoria_documento', ['fields' => 'names']);
        $categoria_nombre = !empty($categoria_terms) ? esc_html(implode(', ', $categoria_terms)) : __('Sin categoría', 'gsl');

        $post_url = get_permalink($documento_id);

        if ($attachment_id) {
            // Obtener la URL del archivo a partir del attachment ID
            $archivo_url = wp_get_attachment_url($attachment_id);

            // Obtener el path físico del archivo para calcular el tamaño
            $file_path = get_attached_file($attachment_id);
            $filesize = '';
            if (file_exists($file_path)) {
                $size_in_bytes = filesize($file_path);
                if ($size_in_bytes >= 1048576) {
                    $filesize = round($size_in_bytes / 1048576, 2) . ' MB';
                } else {
                    $filesize = round($size_in_bytes / 1024, 2) . ' KB';
                }
            }

            // Obtener la extensión o tipo de archivo
            $file_extension = strtoupper(pathinfo($archivo_url, PATHINFO_EXTENSION));

            // Construir el HTML para cada documento
            $output .= '<div class="gsl-documento-box">';
            $output .= '<a href="' . esc_url($archivo_url) . '" class="documento-link" target="_blank">';
            $output .= '<h3 class="documento-titulo">' . esc_html($titulo) . '</h3>';
            $output .= '<div class="documento-meta">';
            $output .= '<span><strong>Categoría:</strong> ' . esc_html($categoria_nombre) . '</span>';
            $output .= '<span><strong>Tipo:</strong> ' . esc_html($file_extension) . '</span>';
            $output .= '<span><strong>Peso:</strong> ' . esc_html($filesize) . '</span>';
            $output .= '</div>';
            $output .= '</a>';
            $output .= '</div>';
        }
    }

    $output .= '</div>';

    return $output;
}
add_shortcode('gsl_documentos_relacionados_producto', 'gsl_documentos_relacionados_producto_shortcode');


//-------------------------------------------------------
// Mostrar el cliente relacionado al producto
//-------------------------------------------------------
function gsl_cliente_relacionado_shortcode($atts)
{
    global $post;

    if ($post->post_type !== 'producto') {
        return '<p>' . __('Este shortcode solo puede usarse en productos.', 'gsl') . '</p>';
    }

    $cliente_id = get_post_meta($post->ID, 'gsl_cliente_relacionado', true);
    if (!$cliente_id) {
        return '<p>' . __('No hay cliente relacionado para este producto.', 'gsl') . '</p>';
    }

    $cliente = get_user_by('ID', $cliente_id);
    if (!$cliente) {
        return '<p>' . __('El cliente relacionado no existe.', 'gsl') . '</p>';
    }

    return '<div class="gsl-cliente-relacionado"><p>' . esc_html($cliente->display_name) . '</p></div>';
}
add_shortcode('gsl_cliente_relacionado', 'gsl_cliente_relacionado_shortcode');

//-------------------------------------------------------
// Mostrar datos producto
//-------------------------------------------------------
function gsl_datos_producto_shortcode($atts)
{
    global $post;

    if ($post->post_type !== 'producto') {
        return '<p>' . __('Este shortcode solo puede usarse en productos.', 'gsl') . '</p>';
    }

    $titulo = get_the_title($post->ID);

    $marca = wp_get_post_terms($post->ID, 'marca', ['fields' => 'names']);
    $marca_text = !empty($marca) ? esc_html(implode(', ', $marca)) : __('NA', 'gsl');

    $modelo = get_post_meta($post->ID, '_gsl_producto_modelo', true);
    $modelo_text = $modelo ? esc_html($modelo) : __('NA', 'gsl');

    $codigo = get_post_meta($post->ID, '_gsl_producto_codigo', true);
    $codigo_text = $codigo ? esc_html($codigo) : __('NA', 'gsl');

    return '
        <div class="gsl-datos-producto-texto">
            <p><strong>' . __('Producto:', 'gsl') . '</strong> ' . esc_html($titulo) . '</p>
            <p><strong>' . __('Marca:', 'gsl') . '</strong> ' . $marca_text . '</p>
            <p><strong>' . __('Modelo:', 'gsl') . '</strong> ' . $modelo_text . '</p>
            <p><strong>' . __('Código:', 'gsl') . '</strong> ' . $codigo_text . '</p>
        </div>
    ';
}
add_shortcode('gsl_datos_producto', 'gsl_datos_producto_shortcode');

//-------------------------------------------------------
// Listar productos relacionados con el cliente logueado
//-------------------------------------------------------
function gsl_productos_relacionados_cliente_shortcode($atts)
{
    if (!is_user_logged_in()) {
        return '<p>' . __('Debes estar logueado para ver tus productos relacionados.', 'gsl') . '</p>';
    }

    $current_user = wp_get_current_user();
    /*
    if (!in_array('cliente', $current_user->roles)) {
        return '<p>' . __('No tienes permisos para ver esta información.', 'gsl') . '</p>';
    }
    */

    $query = new WP_Query([
        'post_type' => 'producto',
        'meta_query' => [
            [
                'key' => 'gsl_cliente_relacionado',
                'value' => $current_user->ID,
                'compare' => '='
            ]
        ]
    ]);


    if (!$query->have_posts()) {
        return '<p>' . __('No tienes productos relacionados.', 'gsl') . '</p>';
    }

    // Contenedor principal de boxes
    $output = '<div class="gsl-productos-relacionados-boxes">';

    while ($query->have_posts()) {
        $query->the_post();

        $post_id    = get_the_ID();
        $titulo     = get_the_title();
        $permalink  = get_permalink();

        // Obtener la imagen destacada del producto (tamaño "medium" o el que desees)
        $imagen = get_the_post_thumbnail($post_id, 'medium');

        // Obtener la marca del producto (se asume que está en la taxonomía "marca")
        $marca = wp_get_post_terms($post_id, 'marca', ['fields' => 'names']);
        $marca_text = !empty($marca) ? esc_html(implode(', ', $marca)) : __('Sin marca', 'gsl');

        // Obtener el modelo del producto (desde el meta campo '_gsl_producto_modelo')
        $modelo = get_post_meta($post_id, '_gsl_producto_modelo', true);
        $modelo_text = $modelo ? esc_html($modelo) : __('Sin modelo', 'gsl');

        // Armamos el HTML para cada box
        $output .= '<div class="gsl-producto-box">';
        // Imagen del producto
        $output .= '<div class="gsl-producto-imagen">' . $imagen . '</div>';
        // Detalles del producto
        $output .= '<div class="gsl-producto-detalle">';
        $output .= '<p><strong>' . __('Producto:', 'gsl') . '</strong> ' . $titulo . '</p>';
        $output .= '<p><strong>' . __('Marca:', 'gsl') . '</strong> ' . $marca_text . '</p>';
        $output .= '<p><strong>' . __('Modelo:', 'gsl') . '</strong> ' . $modelo_text . '</p>';
        // Botón para ver el producto
        $output .= '<a href="' . $permalink . '" class="button gsl-ver-producto" target="_blank">' . __('Ver Producto', 'gsl') . '</a>';
        $output .= '</div>';
        $output .= '</div>';
    }

    $output .= '</div>';
    wp_reset_postdata();

    return $output;
}
add_shortcode('gsl_productos_relacionados', 'gsl_productos_relacionados_cliente_shortcode');

//-------------------------------------------------------
// Mostrar un documento relacionado al post "documento"
//-------------------------------------------------------

function gsl_documento_relacionado_shortcode($atts)
{
    global $post;
    $attachment_id = get_post_meta($post->ID, 'gsl_documento_attachment_id', true);

    if ($attachment_id) {
        $archivo_url    = wp_get_attachment_url($attachment_id);
        $file_path      = get_attached_file($attachment_id);
        $filesize       = '';
        if (file_exists($file_path)) {
            $size_in_bytes = filesize($file_path);
            if ($size_in_bytes >= 1048576) {
                $filesize = round($size_in_bytes / 1048576, 2) . ' MB';
            } else {
                $filesize = round($size_in_bytes / 1024, 2) . ' KB';
            }
        }
        $file_extension = strtoupper(pathinfo($archivo_url, PATHINFO_EXTENSION));

        ob_start();
?>
        <div class="gsl-documento-relacionado">
            <?php if ($file_extension) : ?>
                <p><?php _e("Tipo de documento:", "gsl"); ?> <?php echo esc_html($file_extension); ?></p>
            <?php endif; ?>
            <?php if ($filesize) : ?>
                <p><?php _e("Tamaño:", "gsl"); ?> <?php echo esc_html($filesize); ?></p>
            <?php endif; ?>
            <a class="gsl-boton" href="<?php echo esc_url($archivo_url); ?>" target="_blank"><?php _e("Ver Documento", "gsl"); ?></a>
        </div>
<?php
        return ob_get_clean();
    }

    return '<p>' . __('No hay documento relacionado.', 'gsl') . '</p>';
}
add_shortcode('gsl_documento_relacionado', 'gsl_documento_relacionado_shortcode');


//-------------------------------------------------------
// Mostrar la imagen del cliente relacionado al producto
//-------------------------------------------------------
function gsl_imagen_cliente_relacionado_shortcode($atts)
{
    global $post;

    // Verificar que el tipo de contenido sea "producto"
    if ($post->post_type !== 'producto') {
        return '<p>' . __('Este shortcode solo puede usarse en productos.', 'gsl') . '</p>';
    }

    // Obtener el ID del cliente relacionado al producto
    $cliente_id = get_post_meta($post->ID, 'gsl_cliente_relacionado', true);

    if (!$cliente_id) {
        return '<p>' . __('No hay cliente relacionado para este producto.', 'gsl') . '</p>';
    }

    // Obtener el ID de la imagen del cliente desde los metadatos
    $attachment_id = get_user_meta($cliente_id, 'gsl_imagen_cliente', true);

    if (!$attachment_id) {
        return '<p>' . __('El cliente relacionado no tiene una imagen configurada.', 'gsl') . '</p>';
    }

    // Obtener la URL de la imagen en tamaño medio
    $imagen_url = wp_get_attachment_image_url($attachment_id, 'medium');

    if (!$imagen_url) {
        return '<p>' . __('Error al cargar la imagen del cliente.', 'gsl') . '</p>';
    }

    // Generar salida HTML con la imagen
    $output = '
        <div class="gsl-cliente-imagen">
            <img src="' . esc_url($imagen_url) . '" 
                 alt="' . esc_attr(__('Imagen del Cliente', 'gsl')) . '" 
                 style="max-width: 150px; border-radius: 10px; margin: 10px auto; display: block;">
        </div>
    ';

    return $output;
}
add_shortcode('gsl_imagen_cliente', 'gsl_imagen_cliente_relacionado_shortcode');


//-------------------------------------------------------
// Mostrar QR Code solo para el cliente relacionado o admin
//-------------------------------------------------------
function gsl_kaya_qrcode_acceso_shortcode($atts)
{
    if (! is_user_logged_in()) {
        return '';
    }
    $current_user = wp_get_current_user();
    global $post;
    if (! $post || $post->post_type !== 'producto') {
        return '<p>' . __('Este contenido solo está disponible en productos.', 'gsl') . '</p>';
    }
    $cliente_relacionado = get_post_meta($post->ID, 'gsl_cliente_relacionado', true);
    $es_admin = in_array('administrator', $current_user->roles);
    if (! $es_admin && intval($cliente_relacionado) !== $current_user->ID) {
        return '';
    }

    // Se arma el HTML sin saltos de línea innecesarios
    $output  = '<p>Vista Previa QR</p>';
    $output .= '<div id="gsl-qr-container">';
    $output .= '<span class="gsl-post-title" style="display: none;">' . esc_html(get_the_title($post->ID)) . '</span>';
    $output .= '<div class="gsl-qr-code">' . do_shortcode('[kaya_qrcode]') . '</div>';
    $output .= '<div class="gsl-qr-bottom">';
    $output .= '<div class="gsl-qr-text-column"><p class="gsl-qr-text">AR</p></div>';
    $output .= '<div class="gsl-qr-image-column"><img class="gsl-qr-icon" src="'.plugin_dir_url( dirname(__FILE__) ) . 'assets/docs/qrbluetics.png' .'" alt="Icono" /></div>';
    $output .= '</div></div>';

    // Contenedor para botones de descarga (arriba)
    $output .= '<div class="gsl-qr-download-container">';

    // Primera fila con dos botones
    $output .= '<div class="gsl-qr-download-types">';
    $output .= '<button class="gsl-generate-pdf-btn">' . __('Descargar PDF', 'gsl') . '</button>';
    $output .= '<button class="gsl-generate-png-btn">' . __('Descargar PNG', 'gsl') . '</button>';
    $output .= '</div>';

    // Segunda fila con un solo botón centrado
    $output .= '<div class="gsl-qr-medidas" style="display: flex; justify-content: center;">';
    $output .= '<a href="'.plugin_dir_url( dirname(__FILE__) ) . 'assets/docs/QR_Medidas_Ejemplo.pdf' .'" class="gsl-download-qr-ejemplo" target="_blank">';
    $output .= __('Ejemplo de QR con medidas', 'gsl');
    $output .= '</a>';
    $output .= '</div>';
    

    $output .= '</div>'; // Cierre del contenedor principal

    return $output;
}
add_shortcode('gsl_qrcode', 'gsl_kaya_qrcode_acceso_shortcode');
