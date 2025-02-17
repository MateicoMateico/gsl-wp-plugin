
// Código que se ejecuta para la búsqueda de documento


jQuery(document).ready(function($) {
    var currentPage = 1;
    var searchTimer;

    // Función para cargar documentos vía AJAX
    function loadDocumentos(page, query) {
        // Obtener documentos ya asignados para evitar duplicados
        var assigned = $("#gsl_documentos_relacionados").val();
        $("#unassigned-list").html('<li>Cargando...</li>');
        $.ajax({
            url: gsl_vars.ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'get_documentos',
                nonce: gsl_vars.nonce,
                page: page,
                query: query,
                assigned: assigned
            },
            success: function(response) {
                if (response.success) {
                    $("#unassigned-list").html(response.data.html);
                    $("#current-page").text(page);
                    
                    // Actualizar estado de los botones de paginación
                    if (page >= response.data.max_pages) {
                        $("#next-page").prop('disabled', true);
                    } else {
                        $("#next-page").prop('disabled', false);
                    }
                    $("#prev-page").prop('disabled', page <= 1);
                }
            },
            error: function() {
                $("#unassigned-list").html('<li>Error al cargar documentos.</li>');
            }
        });
    }
    
    // Cargar la primera página inicialmente sin filtro
    loadDocumentos(currentPage, '');
    
    // Evento de búsqueda con debounce para evitar múltiples llamadas
    $("#gsl-buscar-documentos").on("input", function() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function() {
            var query = $("#gsl-buscar-documentos").val();
            currentPage = 1;
            loadDocumentos(currentPage, query);
        }, 300);
    });
    
    // Botón "Siguiente"
    $("#next-page").on("click", function() {
        var query = $("#gsl-buscar-documentos").val();
        currentPage++;
        loadDocumentos(currentPage, query);
    });
    
    // Botón "Anterior"
    $("#prev-page").on("click", function() {
        if (currentPage > 1) {
            var query = $("#gsl-buscar-documentos").val();
            currentPage--;
            loadDocumentos(currentPage, query);
        }
    });
    
    // Inicializar sortable en ambas listas
    $("#unassigned-list, #assigned-list").sortable({
        connectWith: ".list-box ul",
        placeholder: "ui-state-highlight",
        update: function() {
            var assigned = [];
            $("#assigned-list li").each(function() {
                var id = $(this).data("id");
                if (id) assigned.push(id);
            });
            $("#gsl_documentos_relacionados").val(assigned.join(","));
        },
        receive: function(event, ui) {
            // En la lista de asignados, evitar duplicados
            if ($(this).attr('id') === 'assigned-list') {
                var newId = ui.item.data("id");
                var duplicate = false;
                $("#assigned-list li").each(function() {
                    if ($(this).data("id") === newId && this !== ui.item[0]) {
                        duplicate = true;
                    }
                });
                if (duplicate) {
                    $(ui.sender).sortable('cancel');
                }
            }
        }
    }).disableSelection();
});



// Código jQuery para el media uploader de archivos y de imagen
jQuery(document).ready(function($) {
    // -----------------------------------------------------------
    // Media uploader para seleccionar archivo (documentos)
    // -----------------------------------------------------------
    var frame;
    
    $('#gsl_select_archivo').on('click', function(e) {
        e.preventDefault();
        // Si ya se creó el frame, lo reabre
        if ( frame ) {
            frame.open();
            return;
        }
        
        // Crear el frame del media uploader para archivos
        frame = wp.media({
            title: 'Seleccionar archivo',
            button: { text: 'Usar este archivo' },
            multiple: false
        });
        
        // Al seleccionar el archivo
        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            // Actualizar el campo oculto con el attachment ID
            $('#gsl_documento_attachment_id').val( attachment.id );
            
            // Formatear la información a mostrar
            var info_html = '<p>Archivo seleccionado: <a href="' + attachment.url + '" target="_blank">' + attachment.filename + '</a></p>';
            info_html += '<p>Tipo: ' + attachment.subtype.toUpperCase() + '</p>';
            if ( attachment.filesizeInBytes ) {
                var formattedSize = wp.media.utils ? wp.media.utils.filesize( attachment.filesizeInBytes ) : attachment.filesizeInBytes;
                info_html += '<p>Tamaño: ' + formattedSize + '</p>';
            }
            $('#gsl_archivo_info').html( info_html );
            $('#gsl_remove_archivo').show();
        });
        
        frame.open();
    });
    
    // Botón para eliminar el archivo seleccionado
    $('#gsl_remove_archivo').on('click', function(e) {
        e.preventDefault();
        $('#gsl_documento_attachment_id').val('');
        $('#gsl_archivo_info').html('<p>Ningún archivo seleccionado.</p>');
        $(this).hide();
    });
    
    // -----------------------------------------------------------
    // Media uploader para seleccionar imagen (Imagen del Cliente)
    // -----------------------------------------------------------
    var mediaUploader;
    
    $('#gsl_upload_image_button').on('click', function(e) {
        e.preventDefault();
        // Si el uploader ya existe, se reabre
        if ( mediaUploader ) {
            mediaUploader.open();
            return;
        }
        // Crear el frame del media uploader para imágenes
        mediaUploader = wp.media({
            title: 'Selecciona una imagen',
            button: { text: 'Usar esta imagen' },
            multiple: false
        });
        
        // Al seleccionar una imagen, se guarda el ID y se muestra el preview
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#gsl_imagen_cliente').val( attachment.id );
            
            // Usar el tamaño thumbnail si está disponible
            var thumb = ( attachment.sizes && attachment.sizes.thumbnail ) ? attachment.sizes.thumbnail.url : attachment.url;
            $('#gsl_imagen_cliente_preview').html('<img src="' + thumb + '" style="max-width:150px;" />');
        });
        
        mediaUploader.open();
    });
    
    // Botón para eliminar la imagen seleccionada
    $('#gsl_remove_image_button').on('click', function(e) {
        e.preventDefault();
        $('#gsl_imagen_cliente').val('');
        $('#gsl_imagen_cliente_preview').html('');
    });
});


/* QR PDF*/
jQuery(document).ready(function($) {
    // Detectar cuando el usuario hace clic en el botón de "Generar PDF"
    $('.gsl-generate-pdf-btn').on('click', function(e) {
        e.preventDefault(); // Evita que el botón realice su acción por defecto (como recargar la página)

        console.log('Generar PDF: botón clickeado'); // Mensaje en la consola para depuración

        // Selecciona el contenedor que queremos convertir en PDF
        var element = document.getElementById('gsl-qr-container');
        if (!element) { // Si no se encuentra el contenedor, muestra un error y detiene el proceso
            console.error('No se encontró el contenedor #gsl-qr-container');
            return;
        }

        // Obtiene el título del post desde el elemento oculto con la clase 'gsl-post-title'
        var postTitle = $('.gsl-post-title').text().trim(); // Elimina espacios extra antes y después del título
        if (!postTitle) {
            postTitle = 'QR'; // Si no hay título disponible, usa "QR" como nombre por defecto
        }

        // Reemplaza caracteres no válidos en el nombre del archivo (evita problemas al guardar)
        postTitle = postTitle.replace(/[^a-zA-Z0-9-_]/g, '_');

        // Captura el contenedor en una imagen usando html2canvas con una mayor escala para mejor calidad
        html2canvas(element, { scale: 4 }).then(function(canvas) {
            // Convierte la imagen del canvas a formato PNG
            var imgData = canvas.toDataURL('image/png');

            // Inicializa jsPDF para crear un nuevo documento en formato A4 (210x297 mm)
            const { jsPDF } = window.jspdf;
            var pdf = new jsPDF('p', 'mm', 'a4'); // 'p' = orientación vertical, 'mm' = unidad de medida, 'a4' = tamaño de página

            // Obtiene el ancho de la página del PDF
            var pageWidth = pdf.internal.pageSize.getWidth();
            
            // Define el ancho de la imagen en el PDF (% del ancho total)
            var pdfWidth = pageWidth * 0.5;
            
            // Calcula la altura de la imagen para mantener la proporción original
            var pdfHeight = (canvas.height * pdfWidth) / canvas.width;
            

            // Establece el margen izquierdo en 10 (o cualquier valor que desees)
            /* Centrado
            var xMargin = (pageWidth - pdfWidth) / 2;
            */
            var xMargin = 5;
            
            // Ajusta la coordenada y para mover la imagen más abajo (por ejemplo, y = 20)
            var yMargin = 5; 

            // Agrega la imagen al PDF en posición (x, y) con tamaño ajustado
            pdf.addImage(imgData, 'PNG', xMargin, yMargin, pdfWidth, pdfHeight);

            // Guarda el archivo PDF con el nombre "QR_TituloDelPost.pdf"
            pdf.save('QR_' + postTitle + '.pdf');
        }).catch(function(error) {
            console.error('Error al generar PDF:', error); // Mensaje de error en caso de fallo
        });
    });
});

/* QR PNG*/
/* PNG */
jQuery(document).ready(function($) {
    // Detectar cuando el usuario hace clic en el botón de "Generar PNG"
    $('.gsl-generate-png-btn').on('click', function(e) {
        e.preventDefault(); // Evita la acción por defecto del botón
        console.log('Generar PNG: botón clickeado'); // Mensaje en la consola para depuración

        // Selecciona el contenedor que queremos convertir en imagen PNG
        var element = document.getElementById('gsl-qr-container');
        if (!element) {
            console.error('No se encontró el contenedor #gsl-qr-container');
            return;
        }

        // Obtiene el título del post desde el elemento oculto con la clase 'gsl-post-title'
        var postTitle = $('.gsl-post-title').text().trim();
        if (!postTitle) {
            postTitle = 'QR'; // Si no hay título disponible, usa "QR" por defecto
        }
        // Reemplaza caracteres no válidos en el nombre del archivo
        postTitle = postTitle.replace(/[^a-zA-Z0-9-_]/g, '_');

        // Captura el contenedor en una imagen usando html2canvas con una mayor escala para mejor calidad
        html2canvas(element, { scale: 4 }).then(function(canvas) {
            // Convierte la imagen del canvas a formato PNG
            var imgData = canvas.toDataURL('image/png');

            // Crea un enlace temporal para descargar la imagen
            var link = document.createElement('a');
            link.href = imgData;
            link.download = 'QR_' + postTitle + '.png';

            // Simula un clic en el enlace para iniciar la descarga
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }).catch(function(error) {
            console.error('Error al generar PNG:', error);
        });
    });
});




