<?php
/**
 * Maneja los shortcodes y los endpoints de AJAX para el frontend.
 */
if ( ! defined( 'WPINC' ) ) { die; }

class Neo_AI_Shortcodes {
    
    private $options;
    
    public function __construct() {
        $this->options = get_option( 'neo_ai_options' );
        add_action('wp_ajax_handle_chatbot_prompt', array($this, 'ajax_handler')); 
        add_action('wp_ajax_nopriv_handle_chatbot_prompt', array($this, 'ajax_handler'));
        add_action('wp_ajax_handle_content_generation', array($this, 'ajax_content_generator_handler')); 
        add_action('wp_ajax_nopriv_handle_content_generation', array($this, 'ajax_content_generator_handler'));
        add_action('wp_ajax_handle_image_generation', array($this, 'ajax_image_generator_handler')); 
        add_action('wp_ajax_nopriv_handle_image_generation', array($this, 'ajax_image_generator_handler'));
        add_action('wp_ajax_get_agent_details', array($this, 'ajax_get_agent_details_handler'));
        add_action('wp_ajax_update_agent_details', array($this, 'ajax_update_agent_details_handler'));
        add_action('wp_ajax_handle_conversation_analysis', array($this, 'ajax_conversation_analysis_handler'));
        $this->register_shortcodes();
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_app_assets' ) );
        add_action('wp_ajax_save_cv_analysis_paths', array($this, 'ajax_save_cv_analysis_paths_handler'));
        add_action('wp_ajax_start_cv_analysis', array($this, 'ajax_start_cv_analysis_handler'));
        add_action('wp_ajax_process_cv_batch', array($this, 'ajax_process_cv_batch_handler'));
        add_action('wp_ajax_get_cv_analysis_results', array($this, 'ajax_get_cv_analysis_results_handler'));
    }

    public function enqueue_app_assets() {
        if ( 
            is_page_template('templates/template-dashboard.php') || 
            is_page_template('templates/template-playground.php') || 
            is_page_template('templates/template-analysis.php') ||
            is_page_template('templates/template-analysis-curriculum.php') ||
            is_page_template('templates/template-analisis-de-comportamiento-ux.php') ||
            is_page_template('templates/template-analisis-de-sentimientos-colaboradores.php') ||
            is_page_template('templates/template-analisis-de-sentimientos.php') ||
            is_page_template('templates/template-gestion-de-datos-y-privacidad.php') ||
            is_page_template('templates/template-monitoreo-de-riesgos.php') ||
            is_page_template('templates/template-optimizacion-de-procesos.php') ||
            is_page_template('templates/template-personalizacion-y-marketing.php') ||
            is_page_template('templates/template-procesamiento-de-documentos.php') || 
            ( get_the_content() && has_shortcode( get_the_content(), 'neo_ai_agent' ) ))
        {
            wp_enqueue_script( 'neo-ai-main-script', plugin_dir_url( __FILE__ ) . '../assets/js/neo-ai-main.js', array('jquery'), NEO_AI_VERSION, true );
            wp_enqueue_style( 'neo-ai-main-style', plugin_dir_url( __FILE__ ) . '../assets/css/neo-ai-main.css', array(), NEO_AI_VERSION );
            
            wp_localize_script( 'neo-ai-main-script', 'neo_ai_ajax', array(
                'ajax_url' => admin_url( 'admin-ajax.php' ), 
                'nonce' => wp_create_nonce( 'neo_ai_nonce' ),
                'default_agent_avatar' => plugin_dir_url( __FILE__ ) . '../assets/img/default-agent.png',
                'default_user_avatar'  => plugin_dir_url( __FILE__ ) . '../assets/img/default-user.png'
            ));
        }
    }
    
    public function register_shortcodes() {
        add_shortcode( 'neo_ai_login_form', array( $this, 'render_login_form_shortcode' ) );
        add_shortcode( 'neo_ai_lost_password_form', array( $this, 'render_lost_password_form_shortcode' ) );
        add_shortcode( 'neo_ai_agent', array( $this, 'render_single_agent_shortcode' ) );
    }
    
    public function ajax_handler() {
        check_ajax_referer( 'neo_ai_nonce', 'nonce' );
        $prompt = isset( $_POST['prompt'] ) ? sanitize_textarea_field( $_POST['prompt'] ) : ''; 
        $agent_id = isset( $_POST['agent_id'] ) ? intval( $_POST['agent_id'] ) : 0;
        if ( empty( $prompt ) || empty($agent_id) ) { wp_send_json_error( array( 'message' => 'Faltan datos requeridos.' ) ); }
        $response = $this->call_ai_api( $prompt, $agent_id, 'chatbot' );
        if ( is_wp_error( $response ) ) { wp_send_json_error( array( 'message' => $response->get_error_message() ) ); } 
        else { wp_send_json_success( array( 'message' => $response ) ); }
    }

    public function ajax_get_agent_details_handler() {
        check_ajax_referer('neo_ai_nonce', 'nonce');
        global $wpdb;
        $agent_id = intval($_POST['agent_id']);
        $table = $wpdb->prefix . 'neo_ai_agents';
        $agent = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d AND user_id = %d", $agent_id, get_current_user_id()));

        if (!$agent) {
            wp_send_json_error(['message' => 'Agente no encontrado.']);
        }
        wp_send_json_success($agent);
    }

    public function ajax_update_agent_details_handler() {
        check_ajax_referer('neo_ai_nonce', 'nonce');
        global $wpdb;
        $table = $wpdb->prefix . 'neo_ai_agents';
        $agent_id = isset($_POST['agent_id']) ? intval($_POST['agent_id']) : 0;
        if (empty($agent_id)) {
            wp_send_json_error(['message' => 'Error: ID de agente no válido. Imposible guardar.']);
            return;
        }

        $table = $wpdb->prefix . 'neo_ai_agents';
        $user_id = get_current_user_id();

        $data = [
            'name' => isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '',
            'description' => isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '',
            'system_prompt' => isset($_POST['system_prompt']) ? sanitize_textarea_field($_POST['system_prompt']) : '',
            'model' => isset($_POST['model']) ? sanitize_text_field($_POST['model']) : 'gpt-3.5-turbo',
            'temperature' => isset($_POST['temperature']) ? floatval($_POST['temperature']) : 1.0,
            'thinking_message' => isset($_POST['thinking_message']) ? sanitize_text_field($_POST['thinking_message']) : 'El agente está pensando...',
            'bg_color_page' => isset($_POST['bg_color_page']) ? sanitize_hex_color($_POST['bg_color_page']) : '#f8f9fa',
            'bg_color_container' => isset($_POST['bg_color_container']) ? sanitize_hex_color($_POST['bg_color_container']) : '#ffffff',
            'bubble_color_agent' => isset($_POST['bubble_color_agent']) ? sanitize_hex_color($_POST['bubble_color_agent']) : '#e9e9eb',
            'bubble_color_user' => isset($_POST['bubble_color_user']) ? sanitize_hex_color($_POST['bubble_color_user']) : '#0084ff',
            'button_color' => isset($_POST['button_color']) ? sanitize_hex_color($_POST['button_color']) : '#4f46e5',
            'font_family' => isset($_POST['font_family']) ? sanitize_text_field($_POST['font_family']) : 'Inter',
            'font_size' => isset($_POST['font_size']) ? intval($_POST['font_size']) : 16
        ];

        if (!empty($_FILES['avatar_agent_file']['tmp_name'])) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            $uploaded = media_handle_upload('avatar_agent_file', 0);
            if (!is_wp_error($uploaded)) {
                $data['avatar_agent_url'] = wp_get_attachment_url($uploaded);
            }
        }
        
        if (!empty($_FILES['avatar_user_file']['tmp_name'])) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            $uploaded_user = media_handle_upload('avatar_user_file', 0);
            if (!is_wp_error($uploaded_user)) {
                $data['avatar_user_url'] = wp_get_attachment_url($uploaded_user);
            }
        }
        
        //$updated = $wpdb->update($table, $data, ['id' => $agent_id, 'user_id' => $user_id]);
        $updated = $wpdb->update(
            $table,
            $data,
            ['id' => $agent_id, 'user_id' => $user_id],
            null,
            ['%d', '%d']
        );

        if ($updated !== false) {
            wp_send_json_success(['message' => 'Agente actualizado correctamente.']);
        } else {
            wp_send_json_error(['message' => 'Error al actualizar el agente o no se realizaron cambios.']);
        }
    }

    public function ajax_conversation_analysis_handler() {
        check_ajax_referer( 'neo_ai_nonce', 'nonce' );
        $agent_id = isset( $_POST['agent_id'] ) ? intval( $_POST['agent_id'] ) : 0;
        if ( empty($agent_id) ) { wp_send_json_error( array( 'message' => 'ID de agente no válido.' ) ); }
        $user_id = get_current_user_id();
        $stats_handler = new Neo_AI_Stats();
        $logs = $stats_handler->get_all_logs_for_agent( $user_id, $agent_id );
        if ( empty($logs) ) { wp_send_json_error( array( 'message' => 'No hay conversaciones para analizar en la última semana para este agente.' ) ); }
        $conversation_text = "";
        foreach ($logs as $log) {
            $conversation_text .= "Usuario: " . $log->prompt_text . "\n";
            $conversation_text .= "Agente: " . $log->response_text . "\n\n";
        }
        
        $analysis_response = $this->call_ai_for_analysis( $conversation_text, $user_id );
        if ( is_wp_error( $analysis_response ) ) { wp_send_json_error( array( 'message' => $analysis_response->get_error_message() ) ); }
        else { wp_send_json_success( $analysis_response ); }
    }
    
    public function ajax_content_generator_handler() {
        check_ajax_referer( 'neo_ai_nonce', 'nonce' ); $prompt = isset( $_POST['prompt'] ) ? sanitize_textarea_field( $_POST['prompt'] ) : ''; $agent_id = isset( $_POST['agent_id'] ) ? intval( $_POST['agent_id'] ) : 0;
        if ( empty( $prompt ) || empty($agent_id) ) { wp_send_json_error( array( 'message' => 'Faltan datos requeridos.' ) ); }
        $response = $this->call_ai_api( $prompt, $agent_id, 'content_generator' );
        if ( is_wp_error( $response ) ) { wp_send_json_error( array( 'message' => $response->get_error_message() ) ); } else { wp_send_json_success( array( 'content' => nl2br($response) ) ); }
    }
    
    public function ajax_image_generator_handler() {
        check_ajax_referer( 'neo_ai_nonce', 'nonce' ); 
        $prompt = isset( $_POST['prompt'] ) ? sanitize_textarea_field( $_POST['prompt'] ) : ''; $agent_id = isset( $_POST['agent_id'] ) ? intval( $_POST['agent_id'] ) : 0;
        if ( empty( $prompt ) || empty($agent_id) ) { wp_send_json_error( array( 'message' => 'Faltan datos requeridos.' ) ); }
        $response = $this->call_image_api( $prompt, $agent_id );
        if ( is_wp_error( $response ) ) { wp_send_json_error( array( 'message' => $response->get_error_message() ) ); } else { wp_send_json_success( array( 'image_url' => $response ) ); }
    }
    
    public function render_single_agent_shortcode( $atts ) {
        $a = shortcode_atts( array( 'id' => '0' ), $atts ); 
        $agent_id = intval( $a['id'] );
        if ( empty( $agent_id ) ) { 
            return '<p>Error: Se requiere un ID de agente en el shortcode.</p>'; 
        }
        global $wpdb; 
        $agent = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}neo_ai_agents WHERE id = %d", $agent_id ) );
        if ( ! $agent ) { 
            return '<p>Error: Agente no encontrado.</p>'; 
        }
        if ( (int)$agent->is_public !== 1 ) {
            if ( ! is_user_logged_in() ) { 
                return $this->render_login_form_shortcode(); 
            }
            if ( $agent->user_id != get_current_user_id() && ! current_user_can('manage_options') ) { 
                return '<p>No tienes permiso para acceder a este agente privado.</p>'; 
            }
        }
        $this->enqueue_app_assets();
        $style_defaults = [
            'bg_color_page'      => '#f0f2f5',
            'bg_color_container' => '#ffffff',
            'bubble_color_agent' => '#e9e9eb',
            'bubble_color_user'  => '#0084ff',
            'button_color'       => '#007bff',
            'font_family'        => 'Arial, sans-serif',
            'default_avatar_agent' => plugin_dir_url( __FILE__ ) . '../assets/img/default-agent.png',
            'default_avatar_user'  => plugin_dir_url( __FILE__ ) . '../assets/img/default-user.png'
        ];
        $bg_page      = !empty($agent->bg_color_page)      ? esc_attr($agent->bg_color_page)      : $style_defaults['bg_color_page'];
        $bg_container = !empty($agent->bg_color_container) ? esc_attr($agent->bg_color_container) : $style_defaults['bg_color_container'];
        $bubble_agent = !empty($agent->bubble_color_agent) ? esc_attr($agent->bubble_color_agent) : $style_defaults['bubble_color_agent'];
        $bubble_user  = !empty($agent->bubble_color_user)  ? esc_attr($agent->bubble_color_user)  : $style_defaults['bubble_color_user'];
        $button_color = !empty($agent->button_color)       ? esc_attr($agent->button_color)       : $style_defaults['button_color'];
        $font_family  = !empty($agent->font_family)        ? esc_attr($agent->font_family)        : $style_defaults['font_family'];
        $avatar_agent = !empty($agent->avatar_agent_url)   ? esc_url($agent->avatar_agent_url)   : $style_defaults['default_avatar_agent'];
        $avatar_user  = !empty($agent->avatar_user_url)    ? esc_url($agent->avatar_user_url)    : $style_defaults['default_avatar_user'];
        $unique_id = 'neo-ai-chat-' . esc_attr($agent->id);
        ob_start();
        ?>
        <style>
            #<?php echo $unique_id; ?> {
                --bg-page: <?php echo $bg_page; ?>;
                --bg-container: <?php echo $bg_container; ?>;
                --bubble-agent: <?php echo $bubble_agent; ?>;
                --bubble-user: <?php echo $bubble_user; ?>;
                --button-color: <?php echo $button_color; ?>;
                --font-family: '<?php echo $font_family; ?>', sans-serif;
                --font-size: <?php echo intval($agent->font_size ?? 16); ?>px;
                --text-color-user: <?php echo $this->get_text_color($bubble_user); ?>;
                --text-color-agent: <?php echo $this->get_text_color($bubble_agent); ?>;
            }
            #<?php echo $unique_id; ?> .message {
                font-size: var(--font-size);
            }
            body { background-color: var(--bg-page) !important; }
        </style>
        <div id="<?php echo $unique_id; ?>" class="neo-ai-app-container single-agent-view">
            <main class="neo-ai-app-content">
                <?php if( !empty($agent->description) ): ?>
                    <p>
                        <em>
                            <?php echo esc_html($agent->description); ?>
                        </em>
                    </p>
                <?php endif; ?>
                <div class="tools-wrapper" data-agent-id="<?php echo esc_attr($agent->id); ?>">
                    <div class="tools-container" style="padding-top: 20px;">
                        <div class="tool-content active tool-chatbot"> 
                            <div class="neo-ai-chatbot" 
                                 data-avatar-agent="<?php echo $avatar_agent; ?>"
                                 data-avatar-user="<?php echo $avatar_user; ?>"
                                 data-thinking-message="<?php echo esc_attr($agent->thinking_message ?? 'El agente está pensando...'); ?>">
                                <div class="chat-window">
                                    <div class="chat-history">
                                        <div class="message-wrapper">
                                            <img src="<?php echo $avatar_agent; ?>" class="avatar" alt="Avatar Agente">
                                            <div class="message assistant-message">
                                                Agente '<?php echo esc_html($agent->name); ?>' listo.
                                            </div>
                                        </div>
                                    </div>
                                    <div id="agent-status-message" style="display: none; text-align: center; padding: 10px; font-style: italic; color: #6b7280;">
                                    </div>
                                    <div class="neo-ai-user-input">
                                        <textarea class="neo-ai-user-prompt" rows="3" placeholder="Escribe tu consulta aquí..."></textarea>
                                        <button class="neo-ai-submit-button">Enviar</button>
                                    </div>
                                    <div class="neo-ai-loading" style="display:none; margin-top: 10px;">Generando respuesta...</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
        <div class="neo-ai-disclaimer-banner">
            <p class="disclaimer-text">
                <strong>Aviso:</strong> Este es un asistente de IA. La información debe ser validada. 
                <a href="https://neocloud05.com/neo/neo-ai-terminos-y-condiciones/" target="_blank">Leer el descargo de responsabilidad completo.</a>
                </p>
            <p class="copyright-notice">
                &copy; <span id="current-year"></span> DOCUMENTUX. Todos los derechos reservados. <a href="https://www.documentux.com">www.documentux.com</a>
            </p>
        </div>

        <script>
            // Este script actualiza el año del copyright automáticamente.
            document.getElementById('current-year').textContent = new Date().getFullYear();
        </script>
        <?php
        return ob_get_clean();
    }

    private function get_text_color($hex_color) {
        $hex_color = ltrim($hex_color, '#');
        if (strlen($hex_color) == 3) {
            $hex_color = $hex_color[0] . $hex_color[0] . $hex_color[1] . $hex_color[1] . $hex_color[2] . $hex_color[2];
        }
        if (strlen($hex_color) != 6) { return '#000000'; }
        $r = hexdec(substr($hex_color, 0, 2));
        $g = hexdec(substr($hex_color, 2, 2));
        $b = hexdec(substr($hex_color, 4, 2));
        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
        return $luminance > 0.5 ? '#000000' : '#ffffff';
    }
    
    public function render_login_form_shortcode() {
        if ( is_user_logged_in() ) { $playground_url = home_url('/playground-de-ia/'); return '<div class="neo-ai-login-container"><p>Ya has iniciado sesión.</p><a class="button" href="' . esc_url( $playground_url ) . '">Ir al Playground de IA</a></div>'; }
        $output = ''; if ( isset( $_GET['checkemail'] ) ) { $output .= '<p class="neo-ai-login-success">Revisa tu correo para el enlace de restablecimiento.</p>'; }
        if ( isset( $_GET['login'] ) && $_GET['login'] == 'failed' ) { $output .= '<p class="neo-ai-login-error"><strong>ERROR:</strong> Usuario o contraseña incorrectos.</p>'; }
        if ( isset( $_GET['login_error'] ) ) {
            $error_code = sanitize_key($_GET['login_error']);
            if ( $error_code === 'invalid_username' || $error_code === 'incorrect_password' || $error_code === 'invalid_email' ) { $output .= '<p class="neo-ai-login-error"><strong>ERROR:</strong> El usuario o la contraseña son incorrectos.</p>'; } 
            elseif ( $error_code === 'empty_password' || $error_code === 'empty_username' ) { $output .= '<p class="neo-ai-login-error"><strong>ERROR:</strong> Los campos no pueden estar vacíos.</p>'; }
        }
        $redirect_url = home_url('/panel-de-ia/'); if ( isset( $_REQUEST['redirect_to'] ) ) { $redirect_url = esc_url_raw( $_REQUEST['redirect_to'] ); }
        $args = [ 'echo' => false, 'redirect' => $redirect_url, 'form_id' => 'loginform' ];
        $login_form = wp_login_form( $args );
        $lost_password_link = '<p style="text-align:center;"><a href="' . esc_url( home_url('/recuperar-contrasena/') ) . '">¿Olvidaste tu contraseña?</a></p>';
        $login_form_with_link = str_replace( '</form>', $lost_password_link . '</form>', $login_form );
        return '<style>.neo-ai-login-container { max-width: 400px; margin: 50px auto; padding: 0 20px; } .neo-ai-login-error, .neo-ai-login-success { border-radius: 4px; padding: 12px; margin-bottom: 20px; } .neo-ai-login-error { background: #ffebe8; border: 1px solid #c00; } .neo-ai-login-success { background: #e8f5e9; border: 1px solid #4caf50; } #loginform { background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }</style><div class="neo-ai-login-container">' . $output . $login_form_with_link . '</div>';
    }
    
    public function render_lost_password_form_shortcode() {
        ob_start(); $output_message = '';
        if ( 'POST' === $_SERVER['REQUEST_METHOD'] && !empty( $_POST['user_login'] ) && isset( $_POST['neo_ai_lost_password_nonce'] ) ) {
            if ( wp_verify_nonce( $_POST['neo_ai_lost_password_nonce'], 'neo_ai_lost_password' ) ) {
                $result = retrieve_password();
                if ( is_wp_error( $result ) ) { $output_message = '<p class="neo-ai-login-error"><strong>ERROR:</strong> ' . esc_html($result->get_error_message()) . '</p>'; } 
                else { wp_redirect( add_query_arg( 'checkemail', 'confirm', home_url('/acceso-de-clientes/') ) ); exit; }
            }
        }
        ?>
        <div class="neo-ai-login-container"><?php echo $output_message; ?>
            <form id="lostform" name="lostform" action="" method="post">
                <h3>¿Olvidaste tu contraseña?</h3><p>Introduce tu usuario o correo. Recibirás un enlace para crear una contraseña nueva.</p>
                <p><label for="user_login">Usuario o Correo</label><input type="text" name="user_login" id="user_login" class="input" value="" size="20" required/></p>
                <?php wp_nonce_field( 'neo_ai_lost_password', 'neo_ai_lost_password_nonce' ); ?>
                <p class="submit"><input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="Obtener contraseña nueva" /></p>
            </form>
            <a href="<?php echo esc_url( home_url('/acceso-de-clientes/') ); ?>">Volver a Iniciar Sesión</a>
        </div>
        <?php
        return ob_get_clean();
    }
    
    private function call_ai_api( $prompt, $agent_id, $tool = 'unknown' ) {
        global $wpdb;
        $agent = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}neo_ai_agents WHERE id = %d", $agent_id ) );
        if ( ! $agent ) { 
            return new WP_Error('agent_not_found', 'Agente no válido.'); }
        $api_key = get_user_meta( $agent->user_id, 'neo_ai_api_key', true );
        if ( empty( $api_key ) ) { 
            return new WP_Error('api_key_missing', 'El propietario de este agente no ha configurado su clave de API.'); 
        }
        $api_url = 'https://api.openai.com/v1/chat/completions';
        $model = $agent->model;
        $temperature = (float) $agent->temperature;
        $final_system_prompt = '';
        if ( ! empty( trim( $agent->initial_prompt ) ) ) {
            $final_system_prompt .= trim( $agent->initial_prompt );
        }
        if ( ! empty( trim( $agent->system_prompt ) ) ) {
            if ( ! empty( $final_system_prompt ) ) {
                $final_system_prompt .= "\n\n---\n\n"; //
            }
            $final_system_prompt .= trim( $agent->system_prompt );
        }
        $headers = array( 'Authorization' => 'Bearer ' . $api_key, 'Content-Type'  => 'application/json' );
        $messages = array( array( 'role' => 'system', 'content' => $final_system_prompt ), array( 'role' => 'user', 'content' => $prompt ) );
        $body = array( 'model' => $model, 'messages' => $messages, 'temperature' => $temperature );
        $args = array( 'body' => json_encode( $body ), 'headers' => $headers, 'timeout' => 60 );
        $response = wp_remote_post( $api_url, $args );
        if ( is_wp_error( $response ) ) { error_log('NEO AI - WP_Error: ' . $response->get_error_message()); return new WP_Error( 'api_connection_error', 'Error de conexión con la API.' ); }
        $response_code = wp_remote_retrieve_response_code( $response ); $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( $response_code !== 200 ) {
            $error_message = 'La API devolvió un error.'; if ( isset( $response_body['error']['message'] ) ) { $error_message = 'Error de la API: ' . $response_body['error']['message']; }
            error_log('NEO AI - API Error Response: ' . print_r($response_body, true)); return new WP_Error( 'api_response_error', $error_message );
        }
        if ( isset( $response_body['choices'][0]['message']['content'] ) ) {
            $response_text = trim( $response_body['choices'][0]['message']['content'] );
            $log_data = array( 'user_id' => $agent->user_id, 'agent_id' => $agent_id, 'tool_used' => $agent->name, 'prompt_text' => $prompt, 'response_text' => $response_text, 'prompt_tokens' => $response_body['usage']['prompt_tokens'] ?? 0, 'completion_tokens' => $response_body['usage']['completion_tokens'] ?? 0, 'total_tokens' => $response_body['usage']['total_tokens'] ?? 0, );
            $this->log_api_usage($log_data);
            return $response_text;
        }
        return new WP_Error( 'api_unknown_error', 'La respuesta de la API no tuvo el formato esperado.' );
    }

    private function call_ai_for_analysis( $conversation, $user_id ) {
        $api_key = get_user_meta( $user_id, 'neo_ai_api_key', true );
        if ( empty( $api_key ) ) {
            return new WP_Error('api_key_missing', 'No has configurado tu clave de API en tu perfil.');
        }

        // --- INICIO: ARQUITECTURA DE MÚLTIPLES LLAMADAS ---

        $final_analysis_data = [
            'summary' => '',
            'overall_sentiment' => 'No detectado',
            'main_topics' => [],
            'detected_clients' => []
        ];

        $api_url = 'https://api.openai.com/v1/chat/completions';
        $headers = array( 'Authorization' => 'Bearer ' . $api_key, 'Content-Type'  => 'application/json' );

        // Tarea 1: Obtener el Resumen
        $prompt_summary = "Analiza el siguiente conjunto de conversaciones y genera un resumen conciso de uno o dos párrafos que consolide el propósito y resultado de TODAS las interacciones. Responde únicamente con el texto del resumen.";
        $body_summary = ['model' => 'gpt-4-turbo', 'messages' => [['role' => 'system', 'content' => $prompt_summary], ['role' => 'user', 'content' => $conversation]], 'temperature' => 0.3];
        $response_summary = wp_remote_post($api_url, ['body' => json_encode($body_summary), 'headers' => $headers, 'timeout' => 90]);
        if (!is_wp_error($response_summary) && wp_remote_retrieve_response_code($response_summary) === 200) {
            $body = json_decode(wp_remote_retrieve_body($response_summary), true);
            if (isset($body['choices'][0]['message']['content'])) {
                $final_analysis_data['summary'] = trim($body['choices'][0]['message']['content']);
            }
        }

        // Tarea 2: Obtener el Sentimiento
        $prompt_sentiment = "Analiza el sentimiento general de las siguientes conversaciones. Responde únicamente con una de estas tres palabras: Positivo, Neutral, o Negativo.";
        $body_sentiment = ['model' => 'gpt-4-turbo', 'messages' => [['role' => 'system', 'content' => $prompt_sentiment], ['role' => 'user', 'content' => $conversation]], 'temperature' => 0.1];
        $response_sentiment = wp_remote_post($api_url, ['body' => json_encode($body_sentiment), 'headers' => $headers, 'timeout' => 90]);
        if (!is_wp_error($response_sentiment) && wp_remote_retrieve_response_code($response_sentiment) === 200) {
            $body = json_decode(wp_remote_retrieve_body($response_sentiment), true);
            if (isset($body['choices'][0]['message']['content'])) {
                $final_analysis_data['overall_sentiment'] = trim($body['choices'][0]['message']['content']);
            }
        }

        // Tarea 3: Obtener Temas Principales
        $prompt_topics = "Analiza las siguientes conversaciones y lista los 5 temas principales. Responde con los temas separados por comas (ej: Tema 1, Tema 2, Tema 3).";
        $body_topics = ['model' => 'gpt-4-turbo', 'messages' => [['role' => 'system', 'content' => $prompt_topics], ['role' => 'user', 'content' => $conversation]], 'temperature' => 0.2];
        $response_topics = wp_remote_post($api_url, ['body' => json_encode($body_topics), 'headers' => $headers, 'timeout' => 90]);
        if (!is_wp_error($response_topics) && wp_remote_retrieve_response_code($response_topics) === 200) {
            $body = json_decode(wp_remote_retrieve_body($response_topics), true);
            if (isset($body['choices'][0]['message']['content'])) {
                $final_analysis_data['main_topics'] = array_map('trim', explode(',', $body['choices'][0]['message']['content']));
            }
        }
        
        // Tarea 4: Extraer Clientes
        $prompt_clients = "Tu única tarea es extraer información de contacto de las siguientes conversaciones. Busca nombres, correos y teléfonos. Responde únicamente con un objeto JSON que contenga una clave 'clients', que a su vez contenga un array de objetos. Cada objeto debe tener las claves 'Nombre', 'Correo' y 'Telefono'. Si un dato no se encuentra, usa null. Si no encuentras ningún cliente, devuelve un array vacío. Ejemplo de formato: {\"clients\": [{\"Nombre\": \"Juan Perez\", \"Correo\": \"juan@email.com\", \"Telefono\": null}]}";
        $body_clients = ['model' => 'gpt-4-turbo', 'messages' => [['role' => 'system', 'content' => $prompt_clients], ['role' => 'user', 'content' => $conversation]], 'temperature' => 0.1, 'response_format' => ['type' => 'json_object']];
        $response_clients = wp_remote_post($api_url, ['body' => json_encode($body_clients), 'headers' => $headers, 'timeout' => 90]);
        if (!is_wp_error($response_clients) && wp_remote_retrieve_response_code($response_clients) === 200) {
            $body = json_decode(wp_remote_retrieve_body($response_clients), true);
            if (isset($body['choices'][0]['message']['content'])) {
                $client_data = json_decode($body['choices'][0]['message']['content'], true);
                if (isset($client_data['clients']) && is_array($client_data['clients'])) {
                    $final_analysis_data['detected_clients'] = $client_data['clients'];
                }
            }
        }

        // Guardamos el resultado final para la depuración
        update_option('debug_final_analysis_output', json_encode($final_analysis_data, JSON_PRETTY_PRINT));

        return $final_analysis_data;
        // --- FIN: ARQUITECTURA DE MÚLTIPLES LLAMADAS ---
    }

    private function call_image_api( $prompt, $agent_id ) {
        global $wpdb;
        $agent = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}neo_ai_agents WHERE id = %d", $agent_id ) );
        if ( ! $agent ) { return new WP_Error('agent_not_found', 'Agente no válido.'); }
        $api_key = get_user_meta( $agent->user_id, 'neo_ai_api_key', true );
        if ( empty( $api_key ) ) { return new WP_Error('api_key_missing', 'El propietario de este agente no ha configurado su clave de API.'); }
        $api_url = 'https://api.openai.com/v1/images/generations'; $image_size = ! empty( $this->options['image_size'] ) ? $this->options['image_size'] : '1024x1024';
        $headers = array( 'Authorization' => 'Bearer ' . $api_key, 'Content-Type'  => 'application/json' );
        $body = array( 'model' => 'dall-e-3', 'prompt' => $prompt, 'n' => 1, 'size' => $image_size );
        $args = array( 'body' => json_encode( $body ), 'headers' => $headers, 'timeout' => 120 );
        $response = wp_remote_post( $api_url, $args );
        if ( is_wp_error( $response ) ) { error_log('NEO AI - WP_Error Image: ' . $response->get_error_message()); return new WP_Error( 'api_connection_error', 'Error de conexión con la API de imágenes.' ); }
        $response_code = wp_remote_retrieve_response_code( $response ); $response_body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( $response_code !== 200 ) {
            $error_message = 'La API de imágenes devolvió un error.'; if ( isset( $response_body['error']['message'] ) ) { $error_message = 'Error de la API de imágenes: ' . $response_body['error']['message']; }
            error_log('NEO AI - Image API Error Response: ' . print_r($response_body, true)); return new WP_Error( 'api_image_response_error', $error_message );
        }
        if ( isset( $response_body['data'][0]['url'] ) ) {
            $image_url = $response_body['data'][0]['url'];
            $log_data = array('user_id' => $agent->user_id, 'agent_id' => $agent_id, 'tool_used' => $agent->name, 'prompt_text' => $prompt, 'response_text' => $image_url);
            $this->log_api_usage($log_data);
            return $image_url;
        }
        return new WP_Error( 'api_unknown_image_error', 'Respuesta de la API de imágenes desconocida.' );
    }

    private function log_api_usage( $data ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'neo_ai_log';
        $defaults = ['timestamp' => current_time('mysql'), 'user_id' => 0, 'agent_id' => 0, 'tool_used' => 'unknown', 'prompt_text' => '', 'response_text' => '', 'prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0];
        $log_data = wp_parse_args( $data, $defaults );
        $formats = array('%s', '%d', '%d', '%s', '%s', '%s', '%d', '%d', '%d');
        $wpdb->insert( $table_name, $log_data, $formats );
    }

    public function ajax_save_cv_analysis_paths_handler() {
        // 1. Verificar seguridad
        check_ajax_referer('neo_ai_nonce', 'nonce');

        // 2. Obtener y sanitizar las rutas del POST
        $profiles_path = isset($_POST['profiles_path']) ? sanitize_text_field($_POST['profiles_path']) : '';
        $resumes_path = isset($_POST['resumes_path']) ? sanitize_text_field($_POST['resumes_path']) : '';

        // 3. Guardar las rutas como un array en una única opción de WordPress
        $paths_data = [
            'profiles_path' => $profiles_path,
            'resumes_path'  => $resumes_path,
        ];
        update_option('neo_ai_cv_analysis_paths', $paths_data);

        // 4. Enviar respuesta de éxito
        wp_send_json_success(['message' => 'Rutas guardadas correctamente.']);
    }

    public function ajax_start_cv_analysis_handler() {
        neo_ai_write_log("==== TAREA DE ANÁLISIS (Base64) INICIADA ====");
        check_ajax_referer('neo_ai_nonce', 'nonce');
        global $wpdb;

        $paths = get_option('neo_ai_cv_analysis_paths', []);
        $profiles_path = trailingslashit($paths['profiles_path'] ?? '');
        $resumes_path = trailingslashit($paths['resumes_path'] ?? '');
        $selected_profile_file = $_POST['profile'];
        $profile_full_path = $profiles_path . $selected_profile_file;
        if (!file_exists($profile_full_path)) { wp_send_json_error(['message' => 'Error: El archivo del perfil de puesto no se pudo encontrar en el servidor.']); return; }
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf_profile = $parser->parseFile($profile_full_path);
            $job_requirements = ['puesto_requerido' => 'Asistente Administrativo', 'habilidades_clave' => ['PHP', 'WordPress']];
        } catch (\Exception $e) { wp_send_json_error(['message' => 'Error al leer el archivo PDF del perfil: ' . $e->getMessage()]); return; }
        
        $all_resumes = [];
        if (!empty($resumes_path) && is_dir($resumes_path)) {
            $files = scandir($resumes_path);
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'pdf') { 
                    $all_resumes[] = $file; 
                }
            }
        }
        if (empty($all_resumes)) { 
            wp_send_json_error(['message' => 'No se encontraron currículums en PDF.']); 
            return; 
        }

        // "Limpiamos" los nombres de archivo para asegurar que sean UTF-8 válido
        $sanitized_resumes = [];
        foreach ($all_resumes as $file) {
            // Esta función asegura que el string sea UTF-8, reemplazando caracteres inválidos
            if (mb_check_encoding($file, 'UTF-8')) {
                $sanitized_resumes[] = $file;
            } else {
                // Si no es UTF-8, intentamos convertirlo. Si no, lo ignoramos para no romper el JSON.
                $converted_file = @mb_convert_encoding($file, 'UTF-8', 'auto');
                if ($converted_file) {
                    $sanitized_resumes[] = $converted_file;
                }
            }
        }
    
        $job_id = 'cv_analysis_job_' . uniqid();
        $job_data = [
            'job_requirements'      => $job_requirements,
            'resumes_to_process'    => $all_resumes,
            'results'               => [],
            'total_files'           => count($all_resumes)
        ];

        $table_name = $wpdb->prefix . 'neo_ai_jobs';
        $encoded_data = base64_encode(json_encode($job_data));

        $inserted = $wpdb->insert($table_name, [
        'job_id'     => $job_id,
        'job_status' => 'starting',
        'job_data'   => $encoded_data
        ], ['%s', '%s', '%s']);

        if ($inserted === false) {
            wp_send_json_error(['message' => 'Error de base de datos.']);
            return;
        }
        
        neo_ai_write_log("--> ÉXITO: Tarea creada en la base de datos.");
        wp_send_json_success(['job_id' => $job_id, 'total_files' => $job_data['total_files']]);
    }


    public function ajax_process_cv_batch_handler() {
        check_ajax_referer('neo_ai_nonce', 'nonce');
        global $wpdb;

        $job_id = sanitize_text_field($_POST['job_id']);
        neo_ai_write_log("===== PROCESANDO LOTE PARA TAREA: {$job_id} =====");
        
        $table_name = $wpdb->prefix . 'neo_ai_jobs';
        $job = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE job_id = %s", $job_id));

        if (!$job) {
            neo_ai_write_log("--> ERROR: No se encontró la tarea en la BD.");
            wp_send_json_error(['message' => 'La tarea de análisis no se encontró.']);
            return;
        }
        neo_ai_write_log("-> Tarea encontrada. Intentando decodificar datos Base64/JSON.");
        
        $job_data = json_decode(base64_decode($job->job_data), true);

        if (!is_array($job_data)) {
            neo_ai_write_log("--> ERROR FATAL: json_decode falló. El dato de la BD no es un JSON válido o la decodificación Base64 falló. Dato crudo: " . $job->job_data);
            wp_send_json_error(['message' => 'Error crítico al decodificar los datos de la tarea.']);
            return;
        }
        neo_ai_write_log("--> ÉXITO: Datos decodificados correctamente.");
        
        if (!isset($job_data['resumes_to_process'])) { $job_data['resumes_to_process'] = []; }
        
        $paths = get_option('neo_ai_cv_analysis_paths', []);
        $resumes_path = trailingslashit($paths['resumes_path'] ?? '');
        $batch_size = 3;
        $batch = array_splice($job_data['resumes_to_process'], 0, $batch_size);
        neo_ai_write_log("-> Lote preparado con " . count($batch) . " archivos.");

        $parser = new \Smalot\PdfParser\Parser();
        foreach ($batch as $file) {
        neo_ai_write_log("  --> Procesando archivo: {$file}");
        try {
                $pdf = $parser->parseFile($resumes_path . $file);
                $analysis_result = ['nombre_candidato' => pathinfo($file, PATHINFO_FILENAME), 'porcentaje_coincidencia' => rand(65, 99)];
                if ($analysis_result['porcentaje_coincidencia'] > 70) {
                    $job_data['results'][] = $analysis_result;
                    neo_ai_write_log("    --> COINCIDENCIA ALTA: Guardando " . $analysis_result['nombre_candidato']);
                }
            } catch (\Exception $e) { 
                neo_ai_write_log("    --> ERROR al procesar {$file}: " . $e->getMessage());
                continue; 
            }
        }
        
        neo_ai_write_log("-> Fin del lote. Total resultados acumulados: " . count($job_data['results']));
        
        if (!empty($job_data['resumes_to_process'])) {
            $wpdb->update($table_name, 
                ['job_data' => base64_encode(json_encode($job_data))],
                ['job_id' => $job_id], ['%s'], ['%s']
            );
            neo_ai_write_log("-> Aún quedan " . count($job_data['resumes_to_process']) . " archivos. Actualizando BD.");
            wp_send_json_success(['status' => 'processing', 'remaining' => count($job_data['resumes_to_process'])]);
        } else {
            $wpdb->update($table_name,
                ['job_status' => 'complete', 'job_data' => base64_encode(json_encode($job_data))],
                ['job_id' => $job_id], ['%s', '%s'], ['%s']
            );
            neo_ai_write_log("<<<<< PROCESO COMPLETADO para tarea {$job_id}. >>>>>");
            wp_send_json_success(['status' => 'complete']);
        }
    }


    public function ajax_get_cv_analysis_results_handler() {
        check_ajax_referer('neo_ai_nonce', 'nonce');
        global $wpdb;

        $job_id = sanitize_text_field($_POST['job_id']);
        neo_ai_write_log("===== OBTENIENDO RESULTADOS para tarea: {$job_id} =====");
        
        $table_name = $wpdb->prefix . 'neo_ai_jobs';
        $job = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE job_id = %s", $job_id));
        
        if (!$job || $job->job_status !== 'complete') {
            neo_ai_write_log("--> ERROR: La tarea no está completada o no existe.");
            wp_send_json_error(['message' => 'Los resultados aún no están listos o la tarea no existe.']);
            return;
        }
        
        $job_data = json_decode(base64_decode($job->job_data), true);
        $results = $job_data['results'] ?? [];
        neo_ai_write_log("-> Se encontraron " . count($results) . " resultados para entregar.");
        
        $wpdb->delete($table_name, ['job_id' => $job_id]);
        neo_ai_write_log("-> Tarea {$job_id} eliminada de la BD después de entregar resultados.");

        wp_send_json_success($results);
    }
}