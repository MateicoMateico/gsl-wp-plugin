<?php
function gsl_registrar_custom_post_types() {

// Registrar Productos
register_post_type('producto', [
    'labels' => [
        'name' => __('Productos', 'gsl'),
        'singular_name' => __('Producto', 'gsl'),
        'menu_name' => __('Productos', 'gsl'),
        'name_admin_bar' => __('Producto', 'gsl'),
        'add_new' => __('Agregar Nuevo', 'gsl'),
        'add_new_item' => __('Agregar Nuevo Producto', 'gsl'),
        'edit_item' => __('Editar Producto', 'gsl'),
        'new_item' => __('Nuevo Producto', 'gsl'),
        'view_item' => __('Ver Producto', 'gsl'),
        'search_items' => __('Buscar Productos', 'gsl'),
        'not_found' => __('No se encontraron productos', 'gsl'),
        'not_found_in_trash' => __('No se encontraron productos en la papelera', 'gsl'),
        'all_items' => __('Todos los Productos', 'gsl'),
        'archives' => __('Archivos de Productos', 'gsl'),
        'attributes' => __('Atributos de Producto', 'gsl'),
        'insert_into_item' => __('Insertar en Producto', 'gsl'),
        'uploaded_to_this_item' => __('Subido a este Producto', 'gsl'),
        'featured_image' => __('Imagen Destacada', 'gsl'),
        'set_featured_image' => __('Establecer Imagen Destacada', 'gsl'),
        'remove_featured_image' => __('Eliminar Imagen Destacada', 'gsl'),
        'use_featured_image' => __('Usar como Imagen Destacada', 'gsl'),
    ],
    'public' => true,
    'has_archive' => true,
    'supports' => ['title', 'editor', 'thumbnail'],
    'rewrite' => ['slug' => 'productos'],
    'show_in_rest' => true,
    'menu_icon' => 'dashicons-cart'
]);

// Registrar Documentos
register_post_type('documento', [
    'labels' => [
        'name' => __('Documentos', 'gsl'),
        'singular_name' => __('Documento', 'gsl'),
        'menu_name' => __('Documentos', 'gsl'),
        'name_admin_bar' => __('Documento', 'gsl'),
        'add_new' => __('Agregar Nuevo', 'gsl'),
        'add_new_item' => __('Agregar Nuevo Documento', 'gsl'),
        'edit_item' => __('Editar Documento', 'gsl'),
        'new_item' => __('Nuevo Documento', 'gsl'),
        'view_item' => __('Ver Documento', 'gsl'),
        'search_items' => __('Buscar Documentos', 'gsl'),
        'not_found' => __('No se encontraron documentos', 'gsl'),
        'not_found_in_trash' => __('No se encontraron documentos en la papelera', 'gsl'),
        'all_items' => __('Todos los Documentos', 'gsl'),
        'archives' => __('Archivos de Documentos', 'gsl'),
        'attributes' => __('Atributos de Documento', 'gsl'),
        'insert_into_item' => __('Insertar en Documento', 'gsl'),
        'uploaded_to_this_item' => __('Subido a este Documento', 'gsl'),
        'featured_image' => __('Imagen Destacada', 'gsl'),
        'set_featured_image' => __('Establecer Imagen Destacada', 'gsl'),
        'remove_featured_image' => __('Eliminar Imagen Destacada', 'gsl'),
        'use_featured_image' => __('Usar como Imagen Destacada', 'gsl'),
    ],
    'public' => true,
    'has_archive' => true,
    'supports' => ['title', 'editor', 'thumbnail'],
    'rewrite' => ['slug' => 'documentos'],
    'show_in_rest' => true,
    'menu_icon' => 'dashicons-media-document'
]);
}
add_action('init', 'gsl_registrar_custom_post_types');