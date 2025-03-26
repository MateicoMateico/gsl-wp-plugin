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
            // Obtener la URL del ícono usando plugin_dir_url() y la ruta relativa al directorio principal del plugin
            $icon_svg_url = plugin_dir_url(dirname(__FILE__)) . 'assets/docs/pdfIconVector.svg';
            $output .= '<img src="' . esc_url($icon_svg_url) . '" alt="Ícono PDF" class="documento-icono">';
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

    $codigo = get_post_meta($post->ID, '_gsl_producto_codigo', true);
    $codigo_text = $codigo ? esc_html($codigo) : __('NA', 'gsl');

    return '
        <div class="gsl-datos-producto-texto">
            <p><strong>' . __('Producto:', 'gsl') . '</strong> ' . esc_html($titulo) . '</p>
            <p><strong>' . __('Marca:', 'gsl') . '</strong> ' . $marca_text . '</p>
            <p><strong>' . __('Código:', 'gsl') . '</strong> ' . $codigo_text . '</p>
        </div>
    ';
}
add_shortcode('gsl_datos_producto', 'gsl_datos_producto_shortcode');

//-------------------------------------------------------
// Modificar cláusula WHERE de WP_Query para búsqueda
//-------------------------------------------------------
function gsl_productos_search_where( $search, $wp_query ) {
    global $wpdb;
    $custom_search = $wp_query->get('gsl_search');
    if ( $custom_search ) {
        $search_term = esc_sql( $wpdb->esc_like( $custom_search ) );
        $search = " AND (
            ({$wpdb->posts}.post_title LIKE '%{$search_term}%')
            OR ({$wpdb->posts}.post_content LIKE '%{$search_term}%')
            OR EXISTS (
                SELECT * FROM {$wpdb->postmeta} pm
                WHERE pm.post_id = {$wpdb->posts}.ID
                AND pm.meta_key IN ('_gsl_producto_modelo', '_gsl_producto_codigo')
                AND pm.meta_value LIKE '%{$search_term}%'
            )
            OR EXISTS (
                SELECT * FROM {$wpdb->term_relationships} tr
                INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
                WHERE tr.object_id = {$wpdb->posts}.ID
                AND tt.taxonomy = 'marca'
                AND t.name LIKE '%{$search_term}%'
            )
        )";
    }
    return $search;
}

//-------------------------------------------------------
// Listar productos relacionados  cliente logueado con búsqueda y paginación
//-------------------------------------------------------
function gsl_productos_relacionados_cliente_shortcode($atts) {
    if ( ! is_user_logged_in() ) {
        return '<p>' . __( 'Debes estar logueado para ver tus productos relacionados.', 'gsl' ) . '</p>';
    }

    $current_user = wp_get_current_user();

    // Recuperar el valor de búsqueda (por GET)
    $search_query = isset( $_GET['gsl_search'] ) ? sanitize_text_field( $_GET['gsl_search'] ) : '';

    // Formulario de búsqueda
    $form  = '<form method="GET" class="gsl-productos-search-form">';
    $form .= '<input type="text" name="gsl_search" placeholder="' . __( 'Buscar producto...', 'gsl' ) . '" value="' . esc_attr( $search_query ) . '" />';
    $form .= '<button type="submit">' . __( 'Filtrar', 'gsl' ) . '</button>';
    if ( $search_query ) {
         $form .= ' <a href="' . esc_url( remove_query_arg( 'gsl_search' ) ) . '">' . __( 'Limpiar filtros', 'gsl' ) . '</a>';
    }
    $form .= '</form>';

    // Paginación: obtener la página actual (por defecto 1)
    $paged = ( get_query_var('paged') ) ? get_query_var('paged') : 1;

    // Construir argumentos para WP_Query
    $query_args = array(
        'post_type'      => 'producto',
        'posts_per_page' => 12, // ajusta el número de productos por página según necesites
        'paged'          => $paged,
        'meta_query'     => array(
            array(
                'key'     => 'gsl_cliente_relacionado',
                'value'   => $current_user->ID,
                'compare' => '='
            )
        )
    );

    // Agregar búsqueda personalizada si se ingresó un término
    if ( ! empty( $search_query ) ) {
         $query_args['gsl_search'] = $search_query;
         add_filter( 'posts_search', 'gsl_productos_search_where', 10, 2 );
    }

    $query = new WP_Query( $query_args );

    $output = $form;

    if ( ! $query->have_posts() ) {
        $output .= '<p style="text-align:center;margin: 10px;">' . __( 'No tienes productos relacionados.', 'gsl' ) . '</p>';
    } else {
        $output .= '<div class="gsl-productos-relacionados-boxes">';
        while ( $query->have_posts() ) {
            $query->the_post();
            $post_id   = get_the_ID();
            $titulo    = get_the_title();
            $permalink = get_permalink();

            // Imagen destacada (tamaño "medium")
            $imagen = get_the_post_thumbnail( $post_id, 'medium' );

            // Obtener la marca (taxonomía "marca")
            $marca = wp_get_post_terms( $post_id, 'marca', array( 'fields' => 'names' ) );
            $marca_text = ! empty( $marca ) ? esc_html( implode( ', ', $marca ) ) : __( 'Sin marca', 'gsl' );

            // Obtener el código (meta campo "_gsl_producto_codigo")
            $codigo = get_post_meta( $post_id, '_gsl_producto_codigo', true );
            $codigo_text = $codigo ? esc_html( $codigo ) : __( 'NA', 'gsl' );

            // Construir el HTML para cada producto
            $output .= '<div class="gsl-producto-box">';
            $output .= '<div class="gsl-producto-imagen">' . $imagen . '</div>';
            $output .= '<div class="gsl-producto-detalle">';
            $output .= '<p><strong>' . __( 'Producto:', 'gsl' ) . '</strong> ' . $titulo . '</p>';
            $output .= '<p><strong>' . __( 'Marca:', 'gsl' ) . '</strong> ' . $marca_text . '</p>';
            $output .= '<p><strong>' . __( 'Código:', 'gsl' ) . '</strong> ' . $codigo_text . '</p>';
            $output .= '<a href="' . $permalink . '" class="button gsl-ver-producto" target="_blank">' . __( 'Ver Producto', 'gsl' ) . '</a>';
            $output .= '</div>';
            $output .= '</div>';
        }
        $output .= '</div>';

        // Paginación
        $big = 999999999; // número poco probable
        $pagination = paginate_links( array(
            'base'      => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
            'format'    => '?paged=%#%',
            'current'   => max( 1, $paged ),
            'total'     => $query->max_num_pages,
            'type'      => 'list',
        ) );
        if ( $pagination ) {
            $output .= '<div class="gsl-pagination">' . $pagination . '</div>';
        }
    }

    wp_reset_postdata();

    if ( ! empty( $search_query ) ) {
         remove_filter( 'posts_search', 'gsl_productos_search_where', 10, 2 );
    }

    return $output;
}
add_shortcode( 'gsl_productos_relacionados', 'gsl_productos_relacionados_cliente_shortcode' );






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
    $output .= '<div class="gsl-qr-image-column"><img class="gsl-qr-icon" src="' . plugin_dir_url(dirname(__FILE__)) . 'assets/docs/qrbluetics.png' . '" alt="Icono" /></div>';
    $output .= '</div></div>';

    // Contenedor para descargas
    $output .= '<div class="gsl-qr-download-container">';
    $output .= '<h3 class="gsl-downloads-title">DESCARGAS</h3>';

    // Fila con los 3 botones
    $output .= '<div class="gsl-qr-download-types">';
    $output .= '<button class="gsl-generate-pdf-btn">PDF ↗</button>';
    $output .= '<button class="gsl-generate-png-btn">PNG ↗</button>';
    $output .= '<button class="gsl-download-qr-solo-btn">SOLO QR ↗</button>';
    $output .= '</div>';

    // Enlace para ver medidas de ejemplo (debajo)
    $output .= '<p class="gsl-qr-medidas-link">';
    $output .= '<a href="' . plugin_dir_url(dirname(__FILE__)) . 'assets/docs/QR_Medidas_Ejemplo.pdf" class="gsl-download-qr-ejemplo" target="_blank">';
    $output .= __('Ver medidas de ejemplo', 'gsl');
    $output .= '</a>';
    $output .= '</p>';

    $output .= '</div>'; // Fin contenedor descargas

    return $output;
}
add_shortcode('gsl_qrcode', 'gsl_kaya_qrcode_acceso_shortcode');


//-------------------------------------------------------
// Listar modelos relacionados con un producto
//-------------------------------------------------------
function gsl_modelos_relacionados_producto_shortcode($atts) {
    global $post;

    // Se espera que en el producto se guarde un array de modelos en el meta '_gsl_producto_modelo'
    $modelos = get_post_meta($post->ID, '_gsl_producto_modelo', true);

    if (empty($modelos) || !is_array($modelos)) {
        return '<div class="gsl-modelos-grid"><p>' . __('No hay modelos asociados a este producto.', 'gsl') . '</p></div>';
    }

    // Ordenar alfabéticamente los modelos
    sort($modelos, SORT_STRING);

    $output = '<div class="gsl-modelos-grid">';
    
    // Recorrer cada modelo y mostrarlo en un "box"
    foreach ($modelos as $modelo) {
        $output .= '<div class="gsl-modelo-box">';
        $output .= '<p class="gsl-modelo-texto">' . esc_html($modelo) . '</p>';
        $output .= '</div>';
    }
    
    $output .= '</div>';

    return $output;
}
add_shortcode('gsl_modelos_relacionados_producto', 'gsl_modelos_relacionados_producto_shortcode');
