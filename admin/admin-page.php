<?php
/**
 * Maneja todas las páginas de administración del plugin NEO AI.
 */
if ( ! defined( 'WPINC' ) ) { die; }

class Neo_AI_Admin {
    
    private $options;
    
    public function __construct() {
        $this->options = get_option( 'neo_ai_options' );
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'show_user_profile', array( $this, 'add_api_key_field_to_profile' ) );
        add_action( 'edit_user_profile', array( $this, 'add_api_key_field_to_profile' ) );
        add_action( 'personal_options_update', array( $this, 'save_api_key_from_profile' ) );
        add_action( 'edit_user_profile_update', array( $this, 'save_api_key_from_profile' ) );
    }
    
    public function add_admin_menu() {
        add_menu_page('NEO AI', 'NEO AI', 'manage_options', 'neo-ai', array( $this, 'render_settings_page' ), 'dashicons-superhero-alt', 6);
        add_submenu_page('neo-ai', 'Gestionar Agentes', 'Gestionar Agentes', 'manage_options', 'neo-ai-agents', array( $this, 'render_agents_page' ));
        add_submenu_page('neo-ai', 'Ajustes', 'Ajustes', 'manage_options', 'neo-ai');
    }
    
    public function register_settings() {
        register_setting('neo_ai_settings_group', 'neo_ai_options', array( $this, 'sanitize_options' ));
        add_settings_section('neo_ai_text_section', 'Ajustes por Defecto (Texto)', null, 'neo-ai-text');
        add_settings_field('model', 'Modelo de IA por Defecto', array( $this, 'render_model_field_callback' ), 'neo-ai-text', 'neo_ai_text_section');
        add_settings_section('neo_ai_cost_section', 'Ajustes de Costos', null, 'neo-ai-cost');
        add_settings_field(
            'cost_per_1k_tokens', 
            'Costo por 1000 Tokens (en USD)', 
            array( $this, 'render_cost_field_callback' ), 
            'neo-ai-cost', 
            'neo_ai_cost_section'
        );
        add_settings_section('neo_ai_image_section', 'Ajustes por Defecto (Imágenes)', null, 'neo-ai-image');
        add_settings_field('image_size', 'Tamaño de Imagen por Defecto', array( $this, 'render_image_size_field_callback' ), 'neo-ai-image', 'neo_ai_image_section');
    }
    
    public function sanitize_options( $input ) {
        $existing_options = get_option( 'neo_ai_options', array() );
        $new_options = $existing_options;
        if ( isset( $input['model'] ) ) { 
            $new_options['model'] = sanitize_text_field( $input['model'] ); 
        }
        if ( isset( $input['cost_per_1k_tokens'] ) ) { 
            $new_options['cost_per_1k_tokens'] = floatval( $input['cost_per_1k_tokens'] ); 
        }
        if ( isset( $input['image_size'] ) ) { 
            $new_options['image_size'] = sanitize_text_field( $input['image_size'] ); 
        }
        return $new_options;
    }
    
    public function render_agents_page() {
        global $wpdb; $agents_table_name = $wpdb->prefix . 'neo_ai_agents'; $action = isset($_GET['action']) ? sanitize_key($_GET['action']) : 'list'; $agent_id = isset($_GET['agent_id']) ? intval($_GET['agent_id']) : 0;
        if ($action === 'delete' && $agent_id > 0 && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_agent_' . $agent_id)) { $wpdb->delete($agents_table_name, array('id' => $agent_id), array('%d')); echo '<div class="notice notice-success is-dismissible"><p>Agente borrado.</p></div>'; }
        if (isset($_POST['action']) && $_POST['action'] == 'update_agent' && isset($_POST['neo_ai_agent_nonce'])) {
            if (wp_verify_nonce($_POST['neo_ai_agent_nonce'], 'neo_ai_update_agent_' . $agent_id)) {
                $wpdb->update( $agents_table_name, array('name' => sanitize_text_field($_POST['agent_name']), 'description' => sanitize_textarea_field($_POST['agent_description']), 'user_id' => intval($_POST['assign_to_user']), 'system_prompt' => sanitize_textarea_field($_POST['system_prompt']), 'model' => sanitize_text_field($_POST['model']), 'temperature' => floatval($_POST['temperature']), 'font_size' => intval($_POST['font_size']), 'is_public' => isset($_POST['is_public']) ? 1 : 0), array('id' => $agent_id), array('%s', '%s', '%d', '%s', '%s', '%f', '%d', '%d'), array('%d') );
                echo '<div class="notice notice-success is-dismissible"><p>Agente actualizado.</p></div>'; $action = 'list';
            }
        }
        if (isset($_POST['action']) && $_POST['action'] == 'create_agent' && isset($_POST['neo_ai_agent_nonce'])) {
            if (wp_verify_nonce($_POST['neo_ai_agent_nonce'], 'neo_ai_create_agent')) {
                $wpdb->insert( $agents_table_name, array('user_id' => intval($_POST['assign_to_user']), 'name' => sanitize_text_field($_POST['agent_name']), 'description' => sanitize_textarea_field($_POST['agent_description']), 'system_prompt' => sanitize_textarea_field($_POST['system_prompt']), 'model' => sanitize_text_field($_POST['model']), 'temperature' => floatval($_POST['temperature']), 'font_size' => intval($_POST['font_size']), 'is_public' => isset($_POST['is_public']) ? 1 : 0, 'created_at' => current_time('mysql')), array('%d', '%s', '%s', '%s', '%s', '%f', '%d', '%d', '%s'));
                echo '<div class="notice notice-success is-dismissible"><p>Agente creado.</p></div>';
            }
        }
        echo '<div class="wrap">'; echo '<h1>Gestionar Agentes de IA</h1>';
        if ($action === 'edit' && $agent_id > 0) {
            $agent = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$agents_table_name} WHERE id = %d", $agent_id)); $client_users = get_users(array('role__in' => array('neo_cliente', 'administrator')));
            ?><h2>Editando Agente: <?php echo esc_html($agent->name); ?></h2><form method="post" action="?page=neo-ai-agents&action=edit&agent_id=<?php echo $agent_id; ?>"><input type="hidden" name="action" value="update_agent"><?php wp_nonce_field('neo_ai_update_agent_' . $agent->id, 'neo_ai_agent_nonce'); ?><table class="form-table"><tbody>
            <tr><th><label for="agent_name">Nombre</label></th><td><input type="text" name="agent_name" value="<?php echo esc_attr($agent->name); ?>" class="regular-text" required></td></tr>
            <tr><th><label for="agent_description">Descripción</label></th><td><textarea name="agent_description" rows="2" class="large-text"><?php echo esc_textarea($agent->description ?? ''); ?></textarea></td></tr>
            <tr><th><label for="assign_to_user">Asignar a</label></th><td><select name="assign_to_user" required><?php foreach ($client_users as $user) { echo '<option value="'.esc_attr($user->ID).'"'.selected($agent->user_id, $user->ID, false).'>'.esc_html($user->display_name).'</option>'; } ?></select></td></tr>
            <tr><th><label for="is_public">Agente Público</label></th><td><label><input type="checkbox" name="is_public" value="1" <?php checked($agent->is_public ?? 0, 1); ?>> Visible y usable por cualquier visitante (usará la API Key del propietario).</label></td></tr>
            <tr><th><label for="initial_prompt">Prompt Inicial (Oculto para el cliente)</label></th><td><textarea name="initial_prompt" rows="5" class="large-text"><?php echo esc_textarea($agent->initial_prompt ?? ''); ?></textarea><p class="description">Estas instrucciones se colocarán ANTES del prompt de sistema del cliente. No será visible ni editable por él.</p></td></tr>
            <tr><th><label for="system_prompt">Prompt de Sistema (Editable por el cliente)</label></th><td><textarea name="system_prompt" rows="8" class="large-text" required><?php echo esc_textarea($agent->system_prompt); ?></textarea></td></tr>
            <tr><th><label for="model">Modelo</label></th><td><select name="model"><?php $models = ['gpt-3.5-turbo', 'gpt-4', 'gpt-4-turbo']; foreach ($models as $m) { echo '<option value="'.$m.'"'.selected($agent->model, $m, false).'>'.$m.'</option>'; } ?></select></td></tr>
            <tr><th><label for="temperature">Temperatura</label></th><td><input name="temperature" type="number" value="<?php echo esc_attr($agent->temperature); ?>" step="0.1" min="0" max="2"></td></tr>
            <tr><th><label for="font_size">Tamaño de Fuente (px)</label></th><td><input name="font_size" type="number" value="<?php echo esc_attr($agent->font_size ?? 16); ?>" step="1" min="12" max="24"></td></tr>
            </tbody></table><?php submit_button('Actualizar Agente'); ?><a href="?page=neo-ai-agents" class="button">Cancelar</a></form><?php
        } else {
            $all_agents = $wpdb->get_results("SELECT * FROM {$agents_table_name} ORDER BY id DESC"); $client_users = get_users(array('role' => 'neo_cliente'));
            ?><div id="col-container"><div id="col-right"><div class="col-wrap"><h2>Agentes Creados</h2><table class="wp-list-table widefat fixed striped"><thead><tr><th>Nombre</th><th>Usuario</th><th>Visibilidad</th><th>Shortcode</th></tr></thead><tbody>
            <?php if (!empty($all_agents)) : foreach ($all_agents as $agent) : $user_data = get_userdata($agent->user_id); ?>
            <tr><td><strong><a href="?page=neo-ai-agents&action=edit&agent_id=<?php echo $agent->id; ?>"><?php echo esc_html($agent->name); ?></a></strong><div class="row-actions"><span class="edit"><a href="?page=neo-ai-agents&action=edit&agent_id=<?php echo $agent->id; ?>">Editar</a> | </span><span class="trash"><a href="<?php echo wp_nonce_url('?page=neo-ai-agents&action=delete&agent_id=' . $agent->id, 'delete_agent_' . $agent->id); ?>" onclick="return confirm('¿Estás seguro?')" class="submitdelete">Borrar</a></span></div></td>
            <td><?php echo esc_html($user_data->display_name ?? 'N/A'); ?></td>
            <td><?php echo $agent->is_public ? 'Público' : 'Privado'; ?></td>
            <td><input type="text" value="[neo_ai_agent id=&quot;<?php echo $agent->id; ?>&quot;]" readonly onfocus="this.select();" class="widefat"></td></tr>
            <?php endforeach; else : ?> <tr><td colspan="4">No hay agentes creados.</td></tr> <?php endif; ?>
            </tbody></table></div></div><div id="col-left"><div class="col-wrap"><h2>Crear Nuevo Agente</h2>
            <form method="post" action="?page=neo-ai-agents"><input type="hidden" name="action" value="create_agent"><?php wp_nonce_field('neo_ai_create_agent', 'neo_ai_agent_nonce'); ?>
            <div class="form-field"><label for="agent_name">Nombre</label><input type="text" name="agent_name" required></div>
            <div class="form-field"><label for="agent_description">Descripción</label><textarea name="agent_description" rows="2"></textarea></div>
            <div class="form-field"><label for="assign_to_user">Asignar a</label><select name="assign_to_user" required><option value="">Selecciona cliente</option><?php foreach ($client_users as $user) { echo '<option value="'.esc_attr($user->ID).'">'.esc_html($user->display_name).'</option>'; } ?></select></div>
            <div class="form-field"><label for="is_public"><input type="checkbox" name="is_public" value="1"> Hacer público</label></div>
            <div class="form-field"><label for="initial_prompt">Prompt Inicial (Oculto para el cliente)</label><textarea name="initial_prompt" rows="5"></textarea><p class="description">Instrucciones base que el cliente no podrá ver ni editar.</p></div>
            <div class="form-field"><label for="system_prompt">Prompt de Sistema (Editable por el cliente)</label><textarea name="system_prompt" rows="8" required></textarea></div>
            <div class="form-field"><label for="model">Modelo</label><select name="model"><option value="gpt-3.5-turbo">GPT-3.5-Turbo</option><option value="gpt-4">GPT-4</option><option value="gpt-4-turbo">GPT-4-Turbo</option></select></div>
            <div class="form-field"><label for="temperature">Temperatura</label><input name="temperature" type="number" value="1.0" step="0.1" min="0" max="2"></div>
            <div class="form-field"><label for="font_size">Tamaño de Fuente (px)</label><input name="font_size" type="number" value="16" step="1" min="12" max="24"></div>
            <?php submit_button('Crear Agente'); ?>
            </form></div></div></div><?php
        }
        echo '</div>';
    }
    
    public function render_settings_page() {
        $active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'text';
        ?>
        <div class="wrap"> <?php settings_errors(); ?> <h1>Ajustes por Defecto de NEO AI</h1>
            <nav class="nav-tab-wrapper">
                <a href="?page=neo-ai&tab=text" class="nav-tab <?php echo $active_tab == 'text' ? 'nav-tab-active' : ''; ?>">Ajustes de Texto</a>
                <a href="?page=neo-ai&tab=image" class="nav-tab <?php echo $active_tab == 'image' ? 'nav-tab-active' : ''; ?>">Ajustes de Imágenes</a>
                <a href="?page=neo-ai&tab=cost" class="nav-tab <?php echo $active_tab == 'cost' ? 'nav-tab-active' : ''; ?>">Ajustes de Costos</a>
            </nav>
            <form action="options.php" method="post">
                <?php settings_fields( 'neo_ai_settings_group' ); 
                switch ( $active_tab ) { 
                    case 'text': 
                        do_settings_sections( 'neo-ai-text' ); 
                        break; 
                    case 'image': 
                        do_settings_sections( 'neo-ai-image' ); 
                        break;
                    case 'cost': 
                        do_settings_sections( 'neo-ai-cost' ); 
                        break;
                    default: 
                        do_settings_sections( 'neo-ai-text' ); 
                        break; 
                } 
                submit_button( 'Guardar Cambios' ); ?>
            </form></div>
        <?php
    }
    
    public function add_api_key_field_to_profile( $user ) {
        if ( !in_array( 'neo_cliente', $user->roles) && !in_array('administrator', $user->roles) ) { return; }
        ?><h3>Ajustes de NEO AI</h3><table class="form-table"><tr><th><label for="neo_ai_api_key">Clave de API Personal</label></th><td><input type="password" name="neo_ai_api_key" value="<?php echo esc_attr( get_user_meta( $user->ID, 'neo_ai_api_key', true ) ); ?>" class="regular-text" /><p class="description">API Key para agentes privados de este usuario.</p></td></tr></table><?php
    }
   
    public function save_api_key_from_profile( $user_id ) {
        if ( ! current_user_can( 'edit_user', $user_id ) ) { return; }
        if ( isset( $_POST['neo_ai_api_key'] ) ) { update_user_meta( $user_id, 'neo_ai_api_key', sanitize_text_field( $_POST['neo_ai_api_key'] ) ); }
    }
    
    public function render_model_field_callback() {
        $current_model = isset( $this->options['model'] ) ? $this->options['model'] : 'gpt-3.5-turbo'; 
        $models = array( 'gpt-4', 'gpt-4-turbo', 'gpt-3.5-turbo' ); 
        echo '<select name="neo_ai_options[model]">'; 
        foreach ( $models as $model ) { 
            printf('<option value="%s" %s>%s</option>', esc_attr( $model ), selected( $current_model, $model, false ), esc_html( $model )); 
        } 
        echo '</select>'; 
    }
    
    public function render_image_size_field_callback() { 
        $current_size = isset( $this->options['image_size'] ) ? $this->options['image_size'] : '1024x1024'; 
        $sizes = array( '1024x1024', '1792x1024', '1024x1792' ); 
        echo '<select name="neo_ai_options[image_size]">'; 
        foreach ( $sizes as $size ) { 
            printf('<option value="%s" %s>%s</option>', esc_attr( $size ), selected( $current_size, $size, false ), esc_html( $size ));
         } 
         echo '</select>'; 
    }

    public function render_cost_field_callback() {
        $cost = isset( $this->options['cost_per_1k_tokens'] ) ? $this->options['cost_per_1k_tokens'] : '0.002'; // Un valor por defecto, ej: $0.002
        printf(
            '<input type="number" step="0.0001" name="neo_ai_options[cost_per_1k_tokens]" value="%s" />',
            esc_attr( $cost )
        );
        echo '<p class="description">Introduce el costo en dólares por cada 1,000 tokens (ej: para GPT-3.5-turbo, puede ser 0.002).</p>';
    }
}