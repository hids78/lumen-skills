jQuery(document).ready(function ($) {
    'use strict';
    
    // --- Lógica para el menú móvil ---
    const layout = $('.neo-ai-dashboard-layout');
    const menuToggle = $('.neo-ai-mobile-menu-toggle');
    const overlay = $('.neo-ai-mobile-overlay');

    if (menuToggle.length) {
        menuToggle.on('click', function() {
            layout.toggleClass('sidebar-open');
        });

        overlay.on('click', function() {
            layout.removeClass('sidebar-open');
        });
    }

    // --- Lógica para el menú desplegable ---
    const submenuToggle = $('.has-submenu');
    
    if (submenuToggle.length) {
        submenuToggle.on('click', function(e) {
            e.preventDefault(); // Previene la navegación del link principal
            var parentContainer = $(this).closest('.neo-ai-submenu-container');
            
            // Cierra otros submenús que puedan estar abiertos
            $('.neo-ai-submenu-container').not(parentContainer).removeClass('is-open');

            // Abre o cierra el submenú actual
            parentContainer.toggleClass('is-open');
        });
    }

    if (typeof neo_ai_ajax === 'undefined') {
        console.error('NEO AI: El objeto de datos AJAX no está definido.');
        return;
    }

    function showNeoAINotice(message, type = 'success') {
        const container = $('#neo-ai-notice-container');
        if (!container.length) return;

        const icon_success = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>';
        const icon_error = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>';
        
        const noticeHtml = `
            <div class="neo-ai-notice ${type}">
                ${type === 'success' ? icon_success : icon_error}
                <p>${message}</p>
            </div>`;
        
        const noticeElement = $(noticeHtml).appendTo(container);
        
        setTimeout(() => {
            noticeElement.fadeOut(500, function() {
                $(this).remove();
            });
        }, 5000); // La notificación desaparece después de 5 segundos
    }

    // CONTEXTO 1: GESTIÓN DE AGENTES
    const agentSelector = $('#agent-selector');
    console.log("Paso 1.0");
    if (agentSelector.length) {
        const editorWrapper = $('#agent-editor-wrapper');
        const editorForm = $('#agent-editor-form');
        const spinner = $('#agent-editor-spinner');

        agentSelector.on('change', function () {
            const agentId = $(this).val();
            if (!agentId || agentId === '0') {
                editorWrapper.slideUp(200);
                return;
            }
            editorWrapper.slideUp(100, () => spinner.addClass('is-active').css('visibility', 'visible'));
            $.ajax({
                url: neo_ai_ajax.ajax_url,
                type: 'POST',
                data: { action: 'get_agent_details', nonce: neo_ai_ajax.nonce, agent_id: agentId },
                success: function (response) {
                   if (response.success) {
                        $('#edit-agent-id').val(response.data.id);
                        $('#edit-agent-name').val(response.data.name);
                        $('#edit-agent-description').val(response.data.description);
                        $('#edit-system-prompt').val(response.data.system_prompt);
                        $('#edit-agent-model').val(response.data.model);
                        $('#edit-agent-temperature').val(response.data.temperature);
                        $('#edit-bg-color').val(response.data.bg_color_page || '#f8f9fa');
                        $('#edit-container-color').val(response.data.bg_color_container || '#ffffff');
                        $('#edit-agent-bubble-color').val(response.data.bubble_color_agent || '#e9e9eb');
                        $('#edit-user-bubble-color').val(response.data.bubble_color_user || '#0084ff');
                        $('#edit-button-color').val(response.data.button_color || '#4f46e5');
                        $('#edit-font-family').val(response.data.font_family || 'Inter');
                        let fontSizeToSet = 16;
                        if (response.data.hasOwnProperty('font_size') && response.data.font_size) {
                            fontSizeToSet = parseInt(response.data.font_size, 10);
                        }
                        $('#edit-font-size').val(fontSizeToSet);
                        $('#edit-thinking-message').val(response.data.thinking_message || 'El agente está pensando...');
                        $('#preview-avatar-agent').attr('src', response.data.avatar_agent_url || neo_ai_ajax.default_agent_avatar);
                        $('#preview-avatar-user').attr('src', response.data.avatar_user_url || neo_ai_ajax.default_user_avatar);
                        $('#edit-avatar-agent, #edit-avatar-user').val('');
                        editorWrapper.slideDown(300);
                    } else {
                        showNeoAINotice(response.data.message, 'error');
                    }
                },
                error: function () {
                    showNeoAINotice('Hubo un error de comunicación.', 'error');
                },
                complete: function() {
                    spinner.removeClass('is-active').css('visibility', 'hidden');
                }
            });
        });
        
        $('#agent-editor-form').on('submit', function (e) {
            e.preventDefault();
            const button = $(this).find('button[type="submit"]');
            spinner.addClass('is-active').css('visibility', 'visible');
            button.prop('disabled', true);
            const formData = new FormData(this);
            formData.append('action', 'update_agent_details');
            formData.append('nonce', neo_ai_ajax.nonce);
            
            $.ajax({
                url: neo_ai_ajax.ajax_url,
                type: 'POST',
                processData: false,
                contentType: false,
                data: formData,
                success: function (response) {
                    if (response.success) {
                        showNeoAINotice(response.data.message, 'success');
                        agentSelector.find('option:selected').text($('#edit-agent-name').val());
                    } else {
                        let errorMessage = (response.data && response.data.message) ? response.data.message : 'Ocurrió un error al guardar.';
                        if (response.data === -1 || (response.data && response.data.code === 'invalid_nonce')) {
                            errorMessage = 'Error de seguridad. Por favor, recarga la página e inténtalo de nuevo.';
                        }
                        showNeoAINotice(errorMessage, 'error');
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    showNeoAINotice('Error de conexión al guardar.', 'error');
                },
                complete: function () {
                    spinner.removeClass('is-active').css('visibility', 'hidden');
                    button.prop('disabled', false);
                }
            });
        });
    }

    // CONTEXTO 2: ANALISIS DE CONVERSACIONES
    const analysisSelector = $('#analysis-agent-selector');
    if (analysisSelector.length) {
        const analyzeButton = $('#analyze-button');
        const resultsWrapper = $('#analysis-results-wrapper');
        const spinner = $('#analysis-spinner');
        const contentDiv = $('#analysis-content');

        analysisSelector.on('change', function() {
            if ($(this).val() && $(this).val() !== '0') {
                analyzeButton.prop('disabled', false);
            } else {
                analyzeButton.prop('disabled', true);
            }
        });

        analyzeButton.on('click', function() {
            const agentId = analysisSelector.val();
            if (!agentId || agentId === '0') return;

            $(this).prop('disabled', true);
            contentDiv.empty();
            resultsWrapper.slideDown();
            spinner.show();

            $.ajax({
                url: neo_ai_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'handle_conversation_analysis',
                    nonce: neo_ai_ajax.nonce,
                    agent_id: agentId
                },
                success: function(response) {
                    if (response.success) {
                        const data = response.data;
                        let clientHtml = '<h4>Información de Cliente Detectada</h4>';

                        // 1. Revisa si el array 'detected_clients' existe y tiene elementos
                        if (data.detected_clients && data.detected_clients.length > 0) {
                            // 2. Recorre el array para mostrar cada cliente
                            data.detected_clients.forEach(client => {
                                clientHtml += `<div class="client-card">`; // Contenedor para cada cliente
                                clientHtml += `<p><strong>Nombre:</strong> ${client.Nombre || '<em>No detectado</em>'}</p>`;
                                clientHtml += `<p><strong>Email:</strong> ${client.Correo || '<em>No detectado</em>'}</p>`;
                                clientHtml += `<p><strong>Teléfono:</strong> ${client.Telefono || '<em>No detectado</em>'}</p>`;
                                clientHtml += `</div>`;
                            });
                        } else {
                            // Mensaje si el array está vacío
                            clientHtml += "<p><strong>Nombre:</strong> <em>No detectado</em></p>";
                            clientHtml += "<p><strong>Email:</strong> <em>No detectado</em></p>";
                            clientHtml += "<p><strong>Teléfono:</strong> <em>No detectado</em></p>";
                        }

                        let html = `
                            <div class="analysis-card full-width">
                                <h4>Resumen General</h4>
                                <p>${data.summary || 'No se pudo generar un resumen.'}</p>
                            </div>
                            <div class="analysis-card">
                                <h4>Sentimiento General</h4>
                                <p class="sentiment ${data.overall_sentiment?.toLowerCase()}">${data.overall_sentiment || 'No detectado'}</p>
                            </div>
                            <div class="analysis-card">
                                <h4>Temas Principales</h4>
                                ${(data.main_topics && data.main_topics.length > 0) ? `<ul>${data.main_topics.map(topic => `<li>${topic}</li>`).join('')}</ul>` : '<p>No se detectaron temas.</p>'}
                            </div>
                            <div class="analysis-card">
                                ${clientHtml}
                            </div>`;

                        contentDiv.html('<div class="analysis-grid">' + html + '</div>');

                    } else {
                        showNeoAINotice(response.data.message, 'error');
                        resultsWrapper.slideUp();
                    }
                },
                error: function() {
                    showNeoAINotice('Hubo un error de comunicación al realizar el análisis.', 'error');
                    resultsWrapper.slideUp();
                },
                complete: function() {
                    spinner.hide();
                    analyzeButton.prop('disabled', false);
                }
            });
        });
    }

    // CONTEXTO 3: CHAT PÚBLICO
    if ($('.single-agent-view').length) {
        $(document).on('keydown', '.neo-ai-user-prompt', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                $(this).closest('.neo-ai-user-input').find('.neo-ai-submit-button').trigger('click');
            }
        });
        
        $(document).on('click', '.neo-ai-submit-button', function () {
            const clickedButton = $(this);
            const chatbotContainer = clickedButton.closest('.neo-ai-chatbot');
            const resultContainer = chatbotContainer.find('.chat-history');
            const promptInput = chatbotContainer.find('.neo-ai-user-prompt');
            const userPrompt = promptInput.val().trim();
            const activeAgentId = chatbotContainer.closest('.tools-wrapper').data('agent-id');
            const avatarAgentUrl = chatbotContainer.data('avatar-agent');
            const avatarUserUrl = chatbotContainer.data('avatar-user');
            //const thinkingMessage = chatbotContainer.data('thinking-message') || 'El agente está pensando...';
            
            if (!userPrompt) {
                showNeoAINotice('Por favor, introduce un prompt.', 'error');
                return;
            }

            const sanitizer = document.createElement('div');
            sanitizer.textContent = userPrompt;
            const userMessageHtml = `<div class="message-wrapper user-message-wrapper"><div class="message user-message">${sanitizer.innerHTML.replace(/\n/g, '<br>')}</div><img src="${avatarUserUrl}" class="avatar" alt="Avatar Usuario"></div>`;
            resultContainer.append(userMessageHtml);
            
            resultContainer.scrollTop(resultContainer[0].scrollHeight);
            promptInput.val('').css('height', 'auto');
            clickedButton.prop('disabled', true);
           
            // 1. Muestra los puntos suspensivos (...) en una burbuja de chat
            const typingIndicatorHtml = `<div class="message-wrapper typing-indicator-wrapper"><img src="${avatarAgentUrl}" class="avatar" alt="Avatar Agente"><div class="message assistant-message typing-indicator"><span></span><span></span><span></span></div></div>`;
            resultContainer.append(typingIndicatorHtml);
            resultContainer.scrollTop(resultContainer[0].scrollHeight);

            // 2. Muestra el texto de estado debajo del historial de chat
            const thinkingMessage = chatbotContainer.data('thinking-message') || 'El agente está pensando...';
            chatbotContainer.find('#agent-status-message').text(thinkingMessage).show();

            $.ajax({
                url: neo_ai_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'handle_chatbot_prompt',
                    nonce: neo_ai_ajax.nonce,
                    prompt: userPrompt,
                    agent_id: activeAgentId
                },
                success: (response) => {
                    chatbotContainer.find('#agent-status-message').hide();
                    resultContainer.find('.typing-indicator-wrapper').remove();
                    let messageContent = '';
                    if (response.success) {
                        const cleanMessage = response.data.message.replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\n/g, '<br>');
                        messageContent = `<div class="message assistant-message">${cleanMessage}</div>`;
                    } else {
                        messageContent = `<div class="message assistant-message" style="color:red;">Error: ${response.data.message}</div>`;
                    }
                    const assistantMessageHtml = `<div class="message-wrapper"><img src="${avatarAgentUrl}" class="avatar" alt="Avatar Agente">${messageContent}</div>`;
                    resultContainer.append(assistantMessageHtml);
                },
                error: () => {
                    chatbotContainer.find('#agent-status-message').hide();
                    resultContainer.find('.typing-indicator-wrapper').remove();
                    const errorHtml = `<div class="message-wrapper"><img src="${avatarAgentUrl}" class="avatar" alt="Avatar Agente"><div class="message assistant-message" style="color:red;">Error de conexión.</div></div>`;
                    resultContainer.append(errorHtml);
                },
                complete: () => {
                    chatbotContainer.find('#agent-status-message').hide();
                    clickedButton.prop('disabled', false);
                    resultContainer.scrollTop(resultContainer[0].scrollHeight);
                    promptInput.focus();
                }
            });
        });
    }

    // --- Lógica para Pestañas ---
    console.log('Paso 2');
    $('.neo-ai-tabs .tab-link').on('click', function() {
        var tab_id = $(this).attr('data-tab');

        $('.neo-ai-tabs .tab-link').removeClass('active');
        $('.tab-content').removeClass('active');

        $(this).addClass('active');
        $("#"+tab_id).addClass('active');
    });

    // --- Lógica para Guardar Rutas de Análisis de CV ---
    $('#save-settings-btn').on('click', function(e) {
        e.preventDefault();
        const button = $(this);
        const profilesPath = $('#job-profiles-path').val();
        const resumesPath = $('#resumes-path').val();

        button.text('Guardando...').prop('disabled', true);

        $.ajax({
            url: neo_ai_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'save_cv_analysis_paths',
                nonce: neo_ai_ajax.nonce,
                profiles_path: profilesPath,
                resumes_path: resumesPath
            },
            success: function(response) {
                if (response.success) {
                    showNeoAINotice(response.data.message, 'success');
                } else {
                    showNeoAINotice(response.data.message || 'Error al guardar.', 'error');
                }
            },
            error: function() {
                showNeoAINotice('Error de comunicación con el servidor.', 'error');
            },
            complete: function() {
                button.text('Guardar Configuración').prop('disabled', false);
            }
        });
    });

    // --- Lógica para Activar el Botón de Análisis de CV ---
    const profileSelector = $('#job-profile-selector');
    const startButton = $('#start-analysis-btn');

    // Nos aseguramos de que ambos elementos existan en la página actual
    if (profileSelector.length && startButton.length) {
        
        // Escuchamos el evento 'change', que se dispara cada vez que el usuario elige una opción
        profileSelector.on('change', function() {
            
            // Obtenemos el valor de la opción seleccionada
            const selectedValue = $(this).val();

            // Si se ha seleccionado un valor válido (no está vacío)
            if (selectedValue && selectedValue !== '') {
                startButton.prop('disabled', false); // Habilita el botón
            } else {
                startButton.prop('disabled', true); // Deshabilita el botón si se elige la opción por defecto
            }
        });
    }

    // --- Lógica para Iniciar y Procesar el Análisis de CVs por Lotes ---
    $('#start-analysis-btn').on('click', function() {
        const startButton = $(this);
        const profileSelector = $('#job-profile-selector');
        const selectedProfile = profileSelector.val();

        if (!selectedProfile) {
            showNeoAINotice('Por favor, selecciona un perfil de puesto.', 'error');
            return;
        }

        // Prepara la interfaz para el análisis
        startButton.prop('disabled', true).text('Iniciando...');
        $('#analysis-progress-wrapper').slideDown();
        updateProgressBar(0, 1); // Inicia la barra en 0%

        // 1. Llama para iniciar la tarea y obtener el ID del trabajo
        $.ajax({
            url: neo_ai_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'start_cv_analysis',
                nonce: neo_ai_ajax.nonce,
                profile: selectedProfile
            },
            //success: function(response) {
            //    if (response.success) {
            //        const jobId = response.data.job_id;
            //        const totalFiles = response.data.total_files;
            //        const remainingFiles = response.data.remaining;

            //        startButton.text('Análisis en progreso...');
            //        $('#progress-text').text(`Preparando ${totalFiles} archivos...`);
                    // Actualizamos la barra de progreso con el primer lote ya procesado
            //        updateProgressBar(totalFiles - remainingFiles, totalFiles);

                    // Si el trabajo no se completó en la primera llamada, continuamos con los lotes
            //        if (remainingFiles > 0) {
            //            processBatch(jobId, totalFiles, remainingFiles);
            //        } else {
                        // Si con el primer lote fue suficiente, finalizamos
            //            $('#progress-text').text('¡Análisis completado! Obteniendo resultados...');
            //            $('#start-analysis-btn').text('Análisis Finalizado');
            //            showNeoAINotice('El análisis de todos los currículums ha finalizado.', 'success');
            //            fetchAndDisplayResults(jobId);
            //        }
            //    } else {
            //        showNeoAINotice(response.data.message, 'error');
            //        resetAnalysisUI();
            //    }
            //},

            success: function(response) {
                if (response.success) {
                    const jobId = response.data.job_id;
                    const totalFiles = response.data.total_files;
                    
                    startButton.text('Análisis en progreso...');
                    $('#progress-text').text(`Preparando ${totalFiles} archivos...`);

                    // Después de crear la tarea, SIEMPRE iniciamos el procesamiento del primer lote.
                    processBatch(jobId, totalFiles, totalFiles);

                } else {
                    showNeoAINotice(response.data.message || 'Error al iniciar la tarea.', 'error');
                    resetAnalysisUI();
                }
            },

            error: function() {
                showNeoAINotice('Error al iniciar la tarea de análisis.', 'error');
                resetAnalysisUI();
            }
        });
    });

    // Función recursiva para procesar los lotes
    function processBatch(jobId, totalFiles, remainingFiles) {
        $.ajax({
            url: neo_ai_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'process_cv_batch',
                nonce: neo_ai_ajax.nonce,
                job_id: jobId
            },
            success: function(response) {
                if (response.success) {
                    const processedCount = totalFiles - (response.data.remaining || 0);
                    updateProgressBar(processedCount, totalFiles);

                    if (response.data.status === 'processing') {
                        // Si aún quedan, llama al siguiente lote
                        processBatch(jobId, totalFiles, response.data.remaining);
                    } else if (response.data.status === 'complete') {
                        // Si se completó
                        $('#progress-text').text('¡Análisis completado! Obteniendo resultados...');
                        $('#start-analysis-btn').text('Análisis Finalizado');
                        showNeoAINotice('El análisis de todos los currículums ha finalizado.', 'success');

                        // --> LLAMAMOS A LA NUEVA FUNCIÓN PARA OBTENER Y MOSTRAR LOS DATOS
                        fetchAndDisplayResults(jobId);
                    }
                } else {
                    showNeoAINotice('Ocurrió un error durante el procesamiento.', 'error');
                    resetAnalysisUI();
                }
            },
            error: function() {
                showNeoAINotice('Error de comunicación durante el procesamiento.', 'error');
                resetAnalysisUI();
            }
        });
    }

    // Nueva función para obtener y mostrar los resultados en la tabla
    function fetchAndDisplayResults(jobId) {
        const tableBody = $('#results-table-body');
        const resultsWrapper = $('#analysis-results-wrapper');

        $.ajax({
            url: neo_ai_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'get_cv_analysis_results',
                nonce: neo_ai_ajax.nonce,
                job_id: jobId
            },
            success: function(response) {
                resultsWrapper.slideDown();
                tableBody.empty(); // Limpiamos cualquier resultado anterior

                if (response.success && response.data.length > 0) {
                    // Ordenamos los candidatos por el porcentaje de coincidencia (de mayor a menor)
                    response.data.sort((a, b) => b.porcentaje_coincidencia - a.porcentaje_coincidencia);

                    // Creamos una fila en la tabla por cada candidato
                    response.data.forEach(candidate => {
                        const matchColor = candidate.porcentaje_coincidencia > 85 ? 'var(--neo-success-color)' : (candidate.porcentaje_coincidencia > 70 ? '#f59e0b' : 'var(--neo-text-light)');
                        const rowHtml = `
                            <tr>
                                <td>${candidate.nombre_candidato || 'N/A'}</td>
                                <td>${candidate.email || 'N/A'}</td>
                                <td>${candidate.telefono || 'N/A'}</td>
                                <td style="color: ${matchColor}; font-weight: 700;">${candidate.porcentaje_coincidencia}%</td>
                            </tr>
                        `;
                        tableBody.append(rowHtml);
                    });
                } else {
                    // Si no hay candidatos compatibles, mostramos un mensaje
                    const noResultsHtml = '<tr><td colspan="4">No se encontraron candidatos con un alto porcentaje de compatibilidad.</td></tr>';
                    tableBody.html(noResultsHtml);
                }
            },
            error: function() {
                showNeoAINotice('Error al obtener los resultados del análisis.', 'error');
            }
        });
    }

    // Funciones de ayuda para la interfaz
    function updateProgressBar(processed, total) {
        if (total === 0) return;
        const percentage = (processed / total) * 100;
        $('.progress-bar-fill').css('width', percentage + '%');
        $('#progress-text').text(`Analizados ${processed} de ${total} currículums...`);
    }

    function resetAnalysisUI() {
        $('#start-analysis-btn').prop('disabled', false).text('Iniciar Análisis de Currículums');
        $('#analysis-progress-wrapper').slideUp();
        updateProgressBar(0, 1);
    }

});
