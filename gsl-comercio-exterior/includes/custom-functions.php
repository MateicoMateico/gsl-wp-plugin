<?php
// Ocultar admin bar si es cliente
add_filter('show_admin_bar', function($show) {
    // Verifica si el usuario está logueado y tiene el rol de cliente
    if (is_user_logged_in() && current_user_can('cliente')) {
        return false; // Oculta la barra
    }
    return $show; // Muestra la barra para otros roles
});


