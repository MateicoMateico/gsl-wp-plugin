<?php
function gsl_registrar_rol_cliente() {
    add_role('cliente', __('Cliente', 'gsl'), [
        'read' => true,
    ]);
}
add_action('init', 'gsl_registrar_rol_cliente');



// ---------------------------------------------------------------------------------
//                  Agregar campo de imagen al perfil del cliente
// ---------------------------------------------------------------------------------
function gsl_agregar_campo_imagen_cliente($usuario) {
    if (in_array('cliente', (array) $usuario->roles)) {
        // Obtener el ID del attachment si existe
        $attachment_id = get_user_meta($usuario->ID, 'gsl_imagen_cliente', true);
        $img_url = '';
        if ($attachment_id) {
            // Obtener la URL de la imagen en tamaño thumbnail
            $img = wp_get_attachment_image_src($attachment_id, 'thumbnail');
            if( is_array($img) )
                $img_url = $img[0];
        }
        ?>
        <h3><?php _e('Imagen del Cliente', 'gsl'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="gsl_imagen_cliente"><?php _e('Imagen', 'gsl'); ?></label></th>
                <td>
                    <!-- Contenedor para mostrar la imagen seleccionada -->
                    <div id="gsl_imagen_cliente_preview" style="margin-bottom:10px;">
                        <?php if($img_url) { ?>
                            <img src="<?php echo esc_url($img_url); ?>" style="max-width:150px;" />
                        <?php } ?>
                    </div>
                    <!-- Campo oculto que almacena el ID del attachment -->
                    <input type="hidden" name="gsl_imagen_cliente" id="gsl_imagen_cliente" value="<?php echo esc_attr($attachment_id); ?>" />
                    <!-- Botones para abrir el uploader y eliminar la imagen -->
                    <button type="button" class="button" id="gsl_upload_image_button"><?php _e('Seleccionar Imagen', 'gsl'); ?></button>
                    <button type="button" class="button" id="gsl_remove_image_button"><?php _e('Eliminar Imagen', 'gsl'); ?></button>
                </td>
            </tr>
        </table>
        
        <?php
    }
}
add_action('show_user_profile', 'gsl_agregar_campo_imagen_cliente');
add_action('edit_user_profile', 'gsl_agregar_campo_imagen_cliente');

// ---------------------------------------------------------------------------------
//                  Guardar la imagen subida del cliente
// ---------------------------------------------------------------------------------
function gsl_guardar_imagen_cliente($id_usuario) {
    // Verificar permisos
    if (!current_user_can('edit_user', $id_usuario)) {
        return false;
    }
    
    // Si se envió el campo (ya sea con imagen o vaciado para eliminarla)
    if ( isset($_POST['gsl_imagen_cliente']) ) {
        // Sanitizar el valor recibido (se espera un ID numérico)
        $attachment_id = intval($_POST['gsl_imagen_cliente']);
        update_user_meta($id_usuario, 'gsl_imagen_cliente', $attachment_id);
    }
}
add_action('personal_options_update', 'gsl_guardar_imagen_cliente');
add_action('edit_user_profile_update', 'gsl_guardar_imagen_cliente');
