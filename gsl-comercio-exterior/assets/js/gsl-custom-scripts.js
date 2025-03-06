/**********************************************
 * CÓDIGO PRINCIPAL - BÚSQUEDA DE DOCUMENTOS
 **********************************************/
jQuery(document).ready(function($) {
    // ==============================================
    // Variables y Configuración Inicial
    // ==============================================
    var currentPage = 1;
    var searchTimer;
    const searchInput = $("#gsl-buscar-documentos");
    const unassignedList = $("#unassigned-list");
    const assignedList = $("#assigned-list");

    // ==============================================
    // Funciones Principales
    // ==============================================
    
    /**
     * Carga documentos mediante AJAX
     * @param {number} page - Número de página a cargar
     * @param {string} query - Término de búsqueda
     */
    function loadDocumentos(page, query) {
        const assigned = $("#gsl_documentos_relacionados").val();
        unassignedList.html('<li>Cargando...</li>');

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
                    unassignedList.html(response.data.html);
                    $("#current-page").text(page);
                    
                    // Actualizar estado de paginación
                    $("#next-page").prop('disabled', page >= response.data.max_pages);
                    $("#prev-page").prop('disabled', page <= 1);
                }
            },
            error: function() {
                unassignedList.html('<li>Error al cargar documentos.</li>');
            }
        });
    }

    // ==============================================
    // Eventos de Búsqueda y Paginación
    // ==============================================
    
    // Búsqueda con debounce de 300ms
    searchInput.on("input", function() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            currentPage = 1;
            loadDocumentos(currentPage, searchInput.val());
        }, 300);
    });

    // Navegación entre páginas
    $("#next-page").on("click", function() {
        currentPage++;
        loadDocumentos(currentPage, searchInput.val());
    });

    $("#prev-page").on("click", function() {
        if (currentPage > 1) {
            currentPage--;
            loadDocumentos(currentPage, searchInput.val());
        }
    });

    // ==============================================
    // Funcionalidad Drag & Drop (Sortable)
    // ==============================================
    unassignedList.add(assignedList).sortable({
        connectWith: ".list-box ul",
        placeholder: "ui-state-highlight",
        update: function() {
            const assignedIds = assignedList.find("li")
                .map((i, el) => $(el).data("id"))
                .get()
                .join(",");
            $("#gsl_documentos_relacionados").val(assignedIds);
        },
        receive: function(event, ui) {
            // Evitar duplicados en lista asignada
            if ($(this).is(assignedList)) {
                const newId = ui.item.data("id");
                const isDuplicate = assignedList.find(`li[data-id="${newId}"]`).length > 1;
                if (isDuplicate) $(ui.sender).sortable('cancel');
            }
        }
    }).disableSelection();

    // ==============================================
    // Inicialización
    // ==============================================
    loadDocumentos(currentPage, '');
});


/**********************************************
 * MEDIA UPLOADERS (ARCHIVOS E IMÁGENES)
 **********************************************/
jQuery(document).ready(function($) {
    // ==============================================
    // Uploader para Archivos (Documentos)
    // ==============================================
    let fileFrame;
    const fileInput = $('#gsl_documento_attachment_id');
    const fileInfo = $('#gsl_archivo_info');

    $('#gsl_select_archivo').on('click', function(e) {
        e.preventDefault();
        
        if (fileFrame) {
            fileFrame.open();
            return;
        }

        fileFrame = wp.media({
            title: 'Seleccionar archivo',
            button: { text: 'Usar este archivo' },
            multiple: false
        });

        fileFrame.on('select', () => {
            const attachment = fileFrame.state().get('selection').first().toJSON();
            fileInput.val(attachment.id);
            
            let fileSize = attachment.filesizeInBytes;
            if (wp.media.utils) {
                fileSize = wp.media.utils.filesize(fileSize);
            }

            fileInfo.html(`
                <p>Archivo seleccionado: <a href="${attachment.url}" target="_blank">${attachment.filename}</a></p>
                <p>Tipo: ${attachment.subtype.toUpperCase()}</p>
                ${fileSize ? `<p>Tamaño: ${fileSize}</p>` : ''}
            `);
            $('#gsl_remove_archivo').show();
        });

        fileFrame.open();
    });

    $('#gsl_remove_archivo').on('click', function(e) {
        e.preventDefault();
        fileInput.val('');
        fileInfo.html('<p>Ningún archivo seleccionado.</p>');
        $(this).hide();
    });

    // ==============================================
    // Uploader para Imágenes (Cliente)
    // ==============================================
    let imageFrame;
    const imageInput = $('#gsl_imagen_cliente');
    const imagePreview = $('#gsl_imagen_cliente_preview');

    $('#gsl_upload_image_button').on('click', function(e) {
        e.preventDefault();
        
        if (imageFrame) {
            imageFrame.open();
            return;
        }

        imageFrame = wp.media({
            title: 'Selecciona una imagen',
            button: { text: 'Usar esta imagen' },
            multiple: false
        });

        imageFrame.on('select', () => {
            const attachment = imageFrame.state().get('selection').first().toJSON();
            imageInput.val(attachment.id);
            const thumb = attachment.sizes?.thumbnail?.url || attachment.url;
            imagePreview.html(`<img src="${thumb}" style="max-width:150px;" />`);
        });

        imageFrame.open();
    });

    $('#gsl_remove_image_button').on('click', function(e) {
        e.preventDefault();
        imageInput.val('');
        imagePreview.html('');
    });
});


/**********************************************
 * GENERACIÓN DE QR (PDF, PNG Y SOLO QR)
 **********************************************/
jQuery(document).ready(function($) {
    // ==============================================
    // Configuración Común
    // ==============================================
    const qrContainer = '#gsl-qr-container';
    const qrElement = '.gsl-qr-code';
    const postTitleElement = '.gsl-post-title';

    /**
     * Obtiene el título del post formateado para nombre de archivo
     */
    function getFormattedTitle() {
        let title = $(postTitleElement).text().trim() || 'QR';
        return title.replace(/[^a-zA-Z0-9-_]/g, '_');
    }

    // ==============================================
    // Generación de PDF
    // ==============================================
    $('.gsl-generate-pdf-btn').on('click', function(e) {
        e.preventDefault();
        const element = document.querySelector(qrContainer);
        
        if (!element) {
            console.error('Contenedor QR no encontrado');
            return;
        }
        
        html2canvas(element, { scale: 4 }).then(canvas => {
            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF('p', 'mm', 'a4');
            const pageWidth = pdf.internal.pageSize.getWidth();
            const pageHeight = pdf.internal.pageSize.getHeight();
            const pdfWidth = pageWidth * 0.5;
            const pdfHeight = (canvas.height * pdfWidth) / canvas.width;
            
            // Agregar la imagen del canvas
            pdf.addImage(canvas, 'PNG', 5, 5, pdfWidth, pdfHeight);
            
            // URL a la que se vincularán los textos "etiqueta A" y "etiqueta B"
            const linkUrl = gsl_vars.pdfLinkUrl; // Accede a la variable localizada
            
            // Configurar el tamaño de fuente a 8 pts
            pdf.setFontSize(8);
            
            const margin = 10; // margen en milímetros
            const lineHeight = 7;
            // Posición vertical para la primera línea (se ubica dejando 10mm del borde inferior en la última línea)
            const startY = pageHeight - margin - lineHeight;
            
            // --- Primera línea ---
            
            const line1Part1 = "Los productos que por su geometría o tamaño no puedan cumplir con las dimensiones mínimas de diseño señaladas en  "; 
            const line1Link = "etiqueta A";
            const line1Part2 = ";"; // parte final de la línea
            
            // Escribe la parte previa
            pdf.text(line1Part1, margin, startY);
            const widthPart1 = pdf.getTextWidth(line1Part1);
            
            // Configura el color azul para el enlace
            pdf.setTextColor(0, 0, 255);
            // Agrega el enlace con opción target (la apertura en nueva ventana depende del lector de PDF)
            pdf.textWithLink(line1Link, margin + widthPart1, startY, { url: linkUrl, target: '_blank' });
            const widthLink1 = pdf.getTextWidth(line1Link);
            
            // Dibuja una línea debajo del texto del enlace para simular subrayado
            const underlineY = startY + 1; // 1mm por debajo de la línea base
            pdf.setLineWidth(0.1);
            pdf.line(margin + widthPart1, underlineY, margin + widthPart1 + widthLink1, underlineY);
            
            // Restaura el color negro para el resto del texto
            pdf.setTextColor(0, 0, 0);
            // Agrega la parte final de la primera línea
            pdf.text(line1Part2, margin + widthPart1 + widthLink1, startY);
            
            // --- Segunda línea ---
            const line2Part1 = "podrán ser marcados con una etiqueta más pequeña, cuyas dimensiones no podrán ser inferiores a la  ";
            const line2Link = "etiqueta B.";
            const startY2 = startY + lineHeight;
            
            pdf.text(line2Part1, margin, startY2);
            const widthPart2 = pdf.getTextWidth(line2Part1);
            
            pdf.setTextColor(0, 0, 255);
            pdf.textWithLink(line2Link, margin + widthPart2, startY2, { url: linkUrl, target: '_blank' });
            const widthLink2 = pdf.getTextWidth(line2Link);
            const underlineY2 = startY2 + 1;
            pdf.setLineWidth(0.1);
            pdf.line(margin + widthPart2, underlineY2, margin + widthPart2 + widthLink2, underlineY2);
            
            pdf.setTextColor(0, 0, 0);
            
            pdf.save(`QR_${getFormattedTitle()}.pdf`);
        }).catch(console.error);
    });
    
    

    // ==============================================
    // Generación de PNG
    // ==============================================
    $('.gsl-generate-png-btn').on('click', function(e) {
        e.preventDefault();
        const element = document.querySelector(qrContainer);
        
        if (!element) {
            console.error('Contenedor QR no encontrado');
            return;
        }

        html2canvas(element, { scale: 4 }).then(canvas => {
            const link = document.createElement('a');
            link.href = canvas.toDataURL('image/png');
            link.download = `QR_${getFormattedTitle()}.png`;
            document.body.appendChild(link).click();
            document.body.removeChild(link);
        }).catch(console.error);
    });

    // ==============================================
    // Descarga de QR Solo
    // ==============================================
    $('.gsl-download-qr-solo-btn').on('click', function(e) {
        e.preventDefault();
        const element = document.querySelector(qrElement);
        
        if (!element) {
            console.error('Elemento QR no encontrado');
            return;
        }

        html2canvas(element, { scale: 4 }).then(canvas => {
            const link = document.createElement('a');
            link.href = canvas.toDataURL('image/png');
            link.download = `QR_Solo_${getFormattedTitle()}.png`;
            document.body.appendChild(link).click();
            document.body.removeChild(link);
        }).catch(console.error);
    });
});