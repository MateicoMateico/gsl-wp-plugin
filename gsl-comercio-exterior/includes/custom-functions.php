<?php
// Ocultar admin bar si es cliente
add_filter('show_admin_bar', function($show) {
    // Verifica si el usuario está logueado y tiene el rol de cliente
    if (is_user_logged_in() && current_user_can('cliente')) {
        return false; // Oculta la barra
    }
    return $show; // Muestra la barra para otros roles
});

/**
 * Búsqueda combinada en títulos y meta campos específicos
 */
function gsl_admin_combined_search($search, $wp_query) {
    global $wpdb;

    if (
        is_admin() &&
        $wp_query->is_main_query() &&
        'producto' === $wp_query->get('post_type') &&
        $wp_query->is_search() &&
        $wp_query->get('s')
    ) {
        $search_term = $wp_query->get('s');
        $like = '%' . $wpdb->esc_like($search_term) . '%';
        
        // Búsqueda en título
        $title_search = $wpdb->prepare("{$wpdb->posts}.post_title LIKE %s", $like);
        
        // Búsqueda en meta campos
        $meta_search = $wpdb->prepare(
            "EXISTS (
                SELECT 1 
                FROM {$wpdb->postmeta} pm 
                WHERE pm.post_id = {$wpdb->posts}.ID 
                AND pm.meta_key IN ('_gsl_producto_modelo', '_gsl_producto_codigo') 
                AND pm.meta_value LIKE %s
            )",
            $like
        );

        // Combina ambas condiciones
        $search = " AND ({$title_search} OR {$meta_search}) ";
    }

    return $search;
}
add_filter('posts_search', 'gsl_admin_combined_search', 10, 2);


