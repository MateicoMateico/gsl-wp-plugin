<?php
function gsl_registrar_taxonomias() {


// Registrar Taxonomía para Productos -> Marca
register_taxonomy('marca', 'producto', [
    'labels' => [
        'name' => __('Marcas', 'gsl'),
        'singular_name' => __('Marca', 'gsl'),
        'menu_name' => __('Marcas', 'gsl'),
        'all_items' => __('Todas las Marcas', 'gsl'),
        'edit_item' => __('Editar Marca', 'gsl'),
        'view_item' => __('Ver Marca', 'gsl'),
        'update_item' => __('Actualizar Marca', 'gsl'),
        'add_new_item' => __('Agregar Nueva Marca', 'gsl'),
        'new_item_name' => __('Nuevo Nombre de Marca', 'gsl'),
        'search_items' => __('Buscar Marcas', 'gsl'),
        'popular_items' => __('Marcas Populares', 'gsl'),
        'separate_items_with_commas' => __('Separar marcas con comas', 'gsl'),
        'add_or_remove_items' => __('Agregar o Eliminar Marcas', 'gsl'),
        'choose_from_most_used' => __('Elegir entre las más usadas', 'gsl'),
        'not_found' => __('No se encontraron marcas', 'gsl'),
    ],
    'public' => true,
    'hierarchical' => true,
    'show_in_rest' => true
]);

// Registrar Taxonomía para Documentos -> Categorías de Documentos
register_taxonomy('categoria_documento', 'documento', [
    'labels' => [
        'name' => __('Categorías de Documentos', 'gsl'),
        'singular_name' => __('Categoría de Documento', 'gsl'),
        'menu_name' => __('Categorías de Documentos', 'gsl'),
        'all_items' => __('Todas las Categorías de Documentos', 'gsl'),
        'edit_item' => __('Editar Categoría de Documento', 'gsl'),
        'view_item' => __('Ver Categoría de Documento', 'gsl'),
        'update_item' => __('Actualizar Categoría de Documento', 'gsl'),
        'add_new_item' => __('Agregar Nueva Categoría de Documento', 'gsl'),
        'new_item_name' => __('Nuevo Nombre de Categoría de Documento', 'gsl'),
        'search_items' => __('Buscar Categorías de Documentos', 'gsl'),
        'popular_items' => __('Categorías de Documentos Populares', 'gsl'),
        'separate_items_with_commas' => __('Separar categorías con comas', 'gsl'),
        'add_or_remove_items' => __('Agregar o Eliminar Categorías', 'gsl'),
        'choose_from_most_used' => __('Elegir entre las más usadas', 'gsl'),
        'not_found' => __('No se encontraron categorías', 'gsl'),
    ],
    'public' => true,
    'hierarchical' => true,
    'show_in_rest' => true
]);
}
add_action('init', 'gsl_registrar_taxonomias');