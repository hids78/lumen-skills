<?php
/**
 * Plugin Name:       LUMEN SKILLS
 * Plugin URI:        https://github.com/hids78/lumen-skills
 * Description:       Plataforma de universidad corporativa.
 * Version:           1.0.0 (Stable)
 * Author:            Henry Sánchez
 * Author URI:        www.aurora-web-com
 */

if ( ! defined( 'WPINC' ) ) { die; }

define( 'NEO_AI_VERSION', '5.1.2' );
define( 'NEO_AI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
$pdf_parser_base_dir = NEO_AI_PLUGIN_DIR . 'lib/pdfparser-master/src/';

// Dependencias que están en subcarpetas
//require_once $pdf_parser_base_dir . 'Decoder/DecoderInterface.php';
//require_once $pdf_parser_base_dir . 'RawData/RawDataParser.php';
//require_once $pdf_parser_base_dir . 'CrossReference/CrossReferenceInterface.php';
//require_once $pdf_parser_base_dir . 'CrossReference/CrossReference.php';

// Clases principales que están en la raíz de src/
//require_once $pdf_parser_base_dir . 'Config.php';
//require_once $pdf_parser_base_dir . 'Header.php';
//require_once $pdf_parser_base_dir . 'Object.php';
//require_once $pdf_parser_base_dir . 'Page.php';
//require_once $pdf_parser_base_dir . 'Document.php';
//require_once $pdf_parser_base_dir . 'Parser.php';


/**
 * =================================================================
 * Autoloader Definitivo para la Librería PDF Parser
 * =================================================================
 * Este código carga automáticamente cualquier clase de la librería PdfParser
 * cuando se necesita, usando la estructura de carpetas correcta.
 */
spl_autoload_register(function ($class) {
    // El prefijo del namespace que estamos buscando
    $prefix = 'Smalot\\PdfParser\\';

    // La ruta base REAL a la carpeta que contiene las clases.
    // Esta es la corrección clave.
    $base_dir = __DIR__ . '/lib/pdfparser-master/src/Smalot/PdfParser/';

    // Comprueba si la clase que se está intentando usar pertenece a esta librería
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return; // No es de nuestra librería, la ignoramos.
    }

    // Obtiene el nombre relativo de la clase (ej. "Decoder\DecoderInterface")
    $relative_class = substr($class, $len);

    // Construye la ruta al archivo .php reemplazando los separadores
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // Si el archivo existe en esa ruta, lo incluye.
    if (file_exists($file)) {
        require $file;
    }
});

final class Neo_AI {
    private static $instance = null;
    private $custom_login_slug = 'acceso-de-clientes';

    private function __construct() {
        $this->include_dependencies();
        $this->setup_hooks();
    }

    public static function get_instance() {
        if ( self::$instance === null ) { self::$instance = new self(); }
        return self::$instance;
    }

    private function include_dependencies() {
        require_once NEO_AI_PLUGIN_DIR . 'admin/admin-page.php';
        require_once NEO_AI_PLUGIN_DIR . 'includes/class-neo-ai-shortcodes.php';
        require_once NEO_AI_PLUGIN_DIR . 'includes/class-neo-ai-stats.php';
    }

    private function setup_hooks() {
        add_action( 'plugins_loaded', array( $this, 'initialize_components' ) );
        add_action( 'admin_init', array( $this, 'run_db_updater' ) );
        add_filter( 'theme_page_templates', array( $this, 'add_custom_templates_to_dropdown' ), 10, 1 );
        add_filter( 'template_include', array( $this, 'load_custom_template' ), 99 );
        add_filter( 'show_admin_bar', array( $this, 'hide_admin_bar_for_clients' ) );
        add_filter( 'login_url', array( $this, 'custom_login_url' ), 10, 2 );
        add_filter( 'login_redirect', array( $this, 'custom_login_redirect' ), 10, 3 );
    }

    public function initialize_components() {
        if ( is_admin() ) { new Neo_AI_Admin(); }
        new Neo_AI_Shortcodes();
    }

    public function run_db_updater() {
        if ( get_option( 'neo_ai_version' ) !== NEO_AI_VERSION ) {
            neo_ai_create_database_tables();
            update_option( 'neo_ai_version', NEO_AI_VERSION );
        }
    }

    public function add_custom_templates_to_dropdown( $templates ) {
        $templates['templates/template-dashboard.php'] = 'Panel de IA (Herramientas)';
        $templates['templates/template-playground.php'] = 'Playground de IA (Herramientas)';
        $templates['templates/template-analysis.php'] = 'Análisis de Conversaciones IA (Herramientas)';
        $templates['templates/template-analysis-curriculum.php'] = 'Análisis de Currículums (Herramientas)';
        $templates['templates/template-analisis-de-comportamiento-ux.php'] = 'Análisis de Comportamiento UX (Herramientas)';
        $templates['templates/template-analisis-de-sentimientos-colaboradores.php'] = 'Análisis de Sentimientos Colaboradores (Herramientas)';
        $templates['templates/template-analisis-de-sentimientos.php'] = 'Análisis de Sentimientos Corporativos (Herramientas)';
        $templates['templates/template-gestion-de-datos-y-privacidad.php'] = 'Gestión de datos y privacidad (Herramientas)';
        $templates['templates/template-monitoreo-de-riesgos.php'] = 'Monitoreo de Riesgo (Herramientas)';
        $templates['templates/template-optimizacion-de-procesos.php'] = 'Optimización de procesos (Herramientas)';
        $templates['templates/template-personalizacion-y-marketing.php'] = 'Personalización y Marketing (Herramientas)';
        $templates['templates/template-procesamiento-de-documentos.php'] = 'Procesamiento de documentos (Herramientas)';
        return $templates;
    }

    public function load_custom_template( $template ) {
        if ( is_page() ) {
            $page_template = get_post_meta( get_the_ID(), '_wp_page_template', true );
            $allowed_templates = [
                'templates/template-dashboard.php',
                'templates/template-playground.php',
                'templates/template-analysis.php',
                'templates/template-analysis-curriculum.php',
                'templates/template-analisis-de-comportamiento-ux.php',
                'templates/template-analisis-de-sentimientos-colaboradores.php',
                'templates/template-analisis-de-sentimientos.php',
                'templates/template-gestion-de-datos-y-privacidad.php',
                'templates/template-monitoreo-de-riesgos.php',
                'templates/template-optimizacion-de-procesos.php',
                'templates/template-personalizacion-y-marketing.php',
                'templates/template-procesamiento-de-documentos.php'
            ];
            if ( in_array($page_template, $allowed_templates) ) {
                $plugin_template = NEO_AI_PLUGIN_DIR . $page_template;
                if ( file_exists( $plugin_template ) ) { return $plugin_template; }
            }
        }
        return $template;
    }

    public function hide_admin_bar_for_clients( $show ) {
        if ( ! current_user_can( 'manage_options' ) && is_user_logged_in() ) {
            return false;
        }
        return $show;
    }

    public function custom_login_url( $login_url, $redirect ) {
        $login_page = home_url( '/' . $this->custom_login_slug . '/' );
        if ( !empty($redirect) ) {
            $login_page = add_query_arg( 'redirect_to', urlencode($redirect), $login_page );
        }
        return $login_page;
    }

    public function custom_login_redirect( $redirect_to, $requested_redirect_to, $user ) {
        if ( isset( $user->roles ) && is_array( $user->roles ) ) {
            if ( in_array( 'administrator', $user->roles, true ) ) {
                return $requested_redirect_to ? $requested_redirect_to : admin_url();
            } else {
                return home_url( '/panel-de-ia/' );
            }
        }
        return $redirect_to;
    }
}

function neo_ai_activate_plugin() {
    update_option( 'neo_ai_version', NEO_AI_VERSION );
    add_role( 'neo_cliente', 'Neo Cliente', array('read' => true) );
    neo_ai_create_database_tables();
}

register_activation_hook( __FILE__, 'neo_ai_activate_plugin' );

function neo_ai_create_database_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php');

    $table_log_name = $wpdb->prefix . 'neo_ai_log';
    $sql_log = "CREATE TABLE $table_log_name ( 
        id bigint(20) NOT NULL AUTO_INCREMENT, 
        timestamp datetime NOT NULL DEFAULT CURRENT_TIMESTAMP, 
        user_id bigint(20) UNSIGNED NOT NULL, 
        agent_id bigint(20) DEFAULT 0 NOT NULL, 
        tool_used varchar(55) DEFAULT '' NOT NULL, 
        prompt_text text, 
        response_text longtext, 
        prompt_tokens int(11) DEFAULT 0 NOT NULL, 
        completion_tokens int(11) DEFAULT 0 NOT NULL, 
        total_tokens int(11) DEFAULT 0 NOT NULL, 
        PRIMARY KEY  (id), 
        KEY user_id_idx (user_id), 
        KEY agent_id_idx (agent_id) ) $charset_collate;";
    dbDelta( $sql_log );

    $table_agents_name = $wpdb->prefix . 'neo_ai_agents';
    $sql_agents = "CREATE TABLE $table_agents_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) UNSIGNED NOT NULL,
        name varchar(255) NOT NULL,
        description text,
        initial_prompt text,
        system_prompt text NOT NULL,
        model varchar(100) NOT NULL,
        temperature decimal(3,2) DEFAULT 1.00 NOT NULL,
        font_size smallint(3) DEFAULT 16 NOT NULL,
        thinking_message TEXT,
        is_public tinyint(1) DEFAULT 0 NOT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        avatar_agent_url TEXT,
        avatar_user_url TEXT,
        bg_color_page VARCHAR(7),
        bg_color_container VARCHAR(7),
        bubble_color_agent VARCHAR(7),
        bubble_color_user VARCHAR(7),
        button_color VARCHAR(7),
        font_family VARCHAR(100),
        PRIMARY KEY  (id),
        KEY user_id_idx (user_id)
    ) $charset_collate;";
    dbDelta( $sql_agents );

    $table_jobs_name = $wpdb->prefix . 'neo_ai_jobs';
    $sql_jobs = "CREATE TABLE $table_jobs_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        job_id varchar(255) NOT NULL,
        job_status varchar(50) NOT NULL, -- <-- LÍNEA CAMBIADA
        job_data longtext NOT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY job_id (job_id)
    ) $charset_collate;";
    dbDelta( $sql_jobs );
}

function neo_ai_run() {
    return Neo_AI::get_instance();
}

function neo_ai_write_log($message) {
    // Define la ruta a la carpeta de logs dentro de tu plugin
    $log_dir = NEO_AI_PLUGIN_DIR . 'logs/';
    $log_file = $log_dir . 'neoai.log';

    // Asegúrate de que la carpeta de logs exista
    if (!file_exists($log_dir)) {
        wp_mkdir_p($log_dir);
    }

    // Formatea el mensaje con fecha y hora
    $formatted_message = '[' . date('Y-m-d H:i:s') . '] - ' . $message . PHP_EOL;

    // Añade el mensaje al final del archivo de log
    file_put_contents($log_file, $formatted_message, FILE_APPEND);
}

neo_ai_run();
