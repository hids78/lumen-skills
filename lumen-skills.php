<?php
/**
 * Plugin Name: Lumen Skills University Corporate Manager
 * Plugin URI: https://aurora-web.com
 * Description: Plugin de Lumen Skills
 * Version: 1.0.0
 * Author: Henry Sanchez
 * Author URI: https://aurora-web.com
 * License: GPL-2.0+
 * Text Domain: lumen-skills
 * Domain Path: /languages
 */

// Evitamos acceso directo al archivo por seguridad.
if (!defined('ABSPATH')) {
    exit;
}

// Definimos constantes del plugin (rutas y versión).
define('LUMEN_SKILLS_VERSION', '1.0.0');
define('LUMEN_SKILLS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LUMEN_SKILLS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('LUMEN_SKILLS_TEXT_DOMAIN', 'lumen-skills');

// Cargamos el dominio de texto para traducciones.
function lumen_skills_load_textdomain() {
    load_plugin_textdomain(LUMEN_SKILLS_TEXT_DOMAIN, false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'lumen_skills_load_textdomain');

// Incluimos archivos necesarios (clases para roles y autenticación).
require_once LUMEN_SKILLS_PLUGIN_DIR . 'includes/class-lumen-skills-roles.php'; // Para roles personalizados.
require_once LUMEN_SKILLS_PLUGIN_DIR . 'includes/class-lumen-skills-auth.php'; // Para autenticación personalizada.

// Activamos roles personalizados al instalar el plugin.
function lumen_skills_activate() {
    Lumen_Skills_Roles::add_custom_roles();
}
register_activation_hook(__FILE__, 'lumen_skills_activate');

// Cargamos CSS y JS para el super admin (en el dashboard de WP).
function lumen_skills_enqueue_admin_assets($hook) {
    // Solo cargamos en páginas del plugin.
    if (strpos($hook, 'lumen-skills') === false) {
        return;
    }

    // Cargamos CSS.
    wp_enqueue_style(
        'lumen-skills-admin-css',
        LUMEN_SKILLS_PLUGIN_URL . 'assets/css/admin-style.css',
        array(),
        LUMEN_SKILLS_VERSION
    );

    // Cargamos JS.
    wp_enqueue_script(
        'lumen-skills-admin-js',
        LUMEN_SKILLS_PLUGIN_URL . 'assets/js/admin-script.js',
        array('jquery'),
        LUMEN_SKILLS_VERSION,
        true
    );

    // Pasamos datos a JS (como URL de AJAX).
    wp_localize_script('lumen-skills-admin-js', 'lumenSkillsAdmin', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('lumen-skills-admin-nonce')
    ));
}
add_action('admin_enqueue_scripts', 'lumen_skills_enqueue_admin_assets');

// Cargamos CSS y JS para la interfaz del cliente (frontend independiente).
function lumen_skills_enqueue_frontend_assets() {
    // Verificamos si estamos en la página del dashboard del cliente (cambia el slug si es diferente).
    if (!is_page('client-admin-dashboard')) {
        return;
    }

    // Cargamos CSS personalizado (sin look de WP).
    wp_enqueue_style(
        'lumen-skills-frontend-css',
        LUMEN_SKILLS_PLUGIN_URL . 'assets/css/frontend-style.css',
        array(),
        LUMEN_SKILLS_VERSION
    );

    // Cargamos JS.
    wp_enqueue_script(
        'lumen-skills-frontend-js',
        LUMEN_SKILLS_PLUGIN_URL . 'assets/js/frontend-script.js',
        array('jquery'),
        LUMEN_SKILLS_VERSION,
        true
    );

    // Pasamos datos a JS.
    wp_localize_script('lumen-skills-frontend-js', 'lumenSkillsFrontend', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('lumen-skills-frontend-nonce')
    ));
}
add_action('wp_enqueue_scripts', 'lumen_skills_enqueue_frontend_assets');

// Agregamos el menú para el super admin en el dashboard de WP.
function lumen_skills_add_admin_menu() {
    // Página principal del menú.
    add_menu_page(
        __('Lumen Skills Manager', LUMEN_SKILLS_TEXT_DOMAIN),
        __('Lumen Skills', LUMEN_SKILLS_TEXT_DOMAIN),
        'manage_options', // Permiso para super admin.
        'lumen-skills-dashboard',
        'lumen_skills_dashboard_callback',
        'dashicons-book-alt', // Icono.
        20 // Posición en el menú.
    );

    // Submenús para cada uno de los 11 pasos.
    add_submenu_page(
        'lumen-skills-dashboard',
        __('Corporate Assessment', LUMEN_SKILLS_TEXT_DOMAIN),
        __('1. Corporate Assessment', LUMEN_SKILLS_TEXT_DOMAIN),
        'manage_options',
        'lumen-skills-corporate-assessment',
        'lumen_skills_corporate_assessment_callback'
    );

    add_submenu_page(
        'lumen-skills-dashboard',
        __('Individual Assessment', LUMEN_SKILLS_TEXT_DOMAIN),
        __('2. Individual Assessment', LUMEN_SKILLS_TEXT_DOMAIN),
        'manage_options',
        'lumen-skills-individual-assessment',
        'lumen_skills_individual_assessment_callback'
    );

    add_submenu_page(
        'lumen-skills-dashboard',
        __('Career Plans Definition', LUMEN_SKILLS_TEXT_DOMAIN),
        __('3. Career Plans', LUMEN_SKILLS_TEXT_DOMAIN),
        'manage_options',
        'lumen-skills-career-plans',
        'lumen_skills_career_plans_callback'
    );

    add_submenu_page(
        'lumen-skills-dashboard',
        __('Personalized Study Plans', LUMEN_SKILLS_TEXT_DOMAIN),
        __('4. Study Plans Design', LUMEN_SKILLS_TEXT_DOMAIN),
        'manage_options',
        'lumen-skills-study-plans',
        'lumen_skills_study_plans_callback'
    );

    add_submenu_page(
        'lumen-skills-dashboard',
        __('Follow-up Plans', LUMEN_SKILLS_TEXT_DOMAIN),
        __('5. Follow-up Plans', LUMEN_SKILLS_TEXT_DOMAIN),
        'manage_options',
        'lumen-skills-follow-up',
        'lumen_skills_follow_up_callback'
    );

    add_submenu_page(
        'lumen-skills-dashboard',
        __('Content Creation', LUMEN_SKILLS_TEXT_DOMAIN),
        __('6. Content Creation', LUMEN_SKILLS_TEXT_DOMAIN),
        'manage_options',
        'lumen-skills-content-creation',
        'lumen_skills_content_creation_callback'
    );

    add_submenu_page(
        'lumen-skills-dashboard',
        __('LMS Mounting', LUMEN_SKILLS_TEXT_DOMAIN),
        __('7. LMS Mounting', LUMEN_SKILLS_TEXT_DOMAIN),
        'manage_options',
        'lumen-skills-lms-mounting',
        'lumen_skills_lms_mounting_callback'
    );

    add_submenu_page(
        'lumen-skills-dashboard',
        __('LMS Administration', LUMEN_SKILLS_TEXT_DOMAIN),
        __('8. LMS Admin', LUMEN_SKILLS_TEXT_DOMAIN),
        'manage_options',
        'lumen-skills-lms-admin',
        'lumen_skills_lms_admin_callback'
    );

    add_submenu_page(
        'lumen-skills-dashboard',
        __('Managed Service', LUMEN_SKILLS_TEXT_DOMAIN),
        __('9. Managed Service', LUMEN_SKILLS_TEXT_DOMAIN),
        'manage_options',
        'lumen-skills-managed-service',
        'lumen_skills_managed_service_callback'
    );

    add_submenu_page(
        'lumen-skills-dashboard',
        __('Audits', LUMEN_SKILLS_TEXT_DOMAIN),
        __('10. Audits', LUMEN_SKILLS_TEXT_DOMAIN),
        'manage_options',
        'lumen-skills-audits',
        'lumen_skills_audits_callback'
    );

    add_submenu_page(
        'lumen-skills-dashboard',
        __('Certifications', LUMEN_SKILLS_TEXT_DOMAIN),
        __('11. Certifications', LUMEN_SKILLS_TEXT_DOMAIN),
        'manage_options',
        'lumen-skills-certifications',
        'lumen_skills_certifications_callback'
    );

    // Submenú adicional para configuraciones.
    add_submenu_page(
        'lumen-skills-dashboard',
        __('Settings', LUMEN_SKILLS_TEXT_DOMAIN),
        __('Settings', LUMEN_SKILLS_TEXT_DOMAIN),
        'manage_options',
        'lumen-skills-settings',
        'lumen_skills_settings_callback'
    );
}
add_action('admin_menu', 'lumen_skills_add_admin_menu');

// Función helper para cargar templates (best practice: permite overrides por temas).
function lumen_skills_load_template($template_name, $args = array()) {
    // Extraemos variables para pasar al template.
    if (!empty($args) && is_array($args)) {
        extract($args);
    }

    // Buscamos template en tema primero, luego en plugin.
    $template_path = locate_template('lumen-skills/' . $template_name);
    if (!$template_path) {
        $template_path = LUMEN_SKILLS_PLUGIN_DIR . 'templates/' . $template_name;
    }

    // Incluimos si existe.
    if (file_exists($template_path)) {
        include $template_path;
    } else {
        echo '<p>Template no encontrado: ' . esc_html($template_name) . '</p>';
    }
}

// Callbacks para las páginas del super admin (usamos templates donde posible; placeholders para los demás).
function lumen_skills_dashboard_callback() {
    // Cargamos template para el dashboard principal.
    echo '<div class="wrap">';
    lumen_skills_load_template('admin-dashboard.php');
    echo '</div>';
}

function lumen_skills_corporate_assessment_callback() {
    echo '<div class="wrap"><h1>' . __('Corporate Assessment', LUMEN_SKILLS_TEXT_DOMAIN) . '</h1><p>' . __('Manage corporate maturity analysis.', LUMEN_SKILLS_TEXT_DOMAIN) . '</p></div>';
}

function lumen_skills_individual_assessment_callback() {
    echo '<div class="wrap"><h1>' . __('Individual Assessment', LUMEN_SKILLS_TEXT_DOMAIN) . '</h1><p>' . __('Manage individual maturity analysis.', LUMEN_SKILLS_TEXT_DOMAIN) . '</p></div>';
}

function lumen_skills_career_plans_callback() {
    echo '<div class="wrap"><h1>' . __('Career Plans Definition', LUMEN_SKILLS_TEXT_DOMAIN) . '</h1><p>' . __('Define career plans.', LUMEN_SKILLS_TEXT_DOMAIN) . '</p></div>';
}

function lumen_skills_study_plans_callback() {
    echo '<div class="wrap"><h1>' . __('Personalized Study Plans', LUMEN_SKILLS_TEXT_DOMAIN) . '</h1><p>' . __('Design study plans.', LUMEN_SKILLS_TEXT_DOMAIN) . '</p></div>';
}

function lumen_skills_follow_up_callback() {
    echo '<div class="wrap"><h1>' . __('Follow-up Plans', LUMEN_SKILLS_TEXT_DOMAIN) . '</h1><p>' . __('Design follow-up plans.', LUMEN_SKILLS_TEXT_DOMAIN) . '</p></div>';
}

function lumen_skills_content_creation_callback() {
    echo '<div class="wrap"><h1>' . __('Content Creation', LUMEN_SKILLS_TEXT_DOMAIN) . '</h1><p>' . __('Create educational content.', LUMEN_SKILLS_TEXT_DOMAIN) . '</p></div>';
}

function lumen_skills_lms_mounting_callback() {
    echo '<div class="wrap"><h1>' . __('LMS Mounting', LUMEN_SKILLS_TEXT_DOMAIN) . '</h1><p>' . __('Mount content in LMS.', LUMEN_SKILLS_TEXT_DOMAIN) . '</p></div>';
}

function lumen_skills_lms_admin_callback() {
    echo '<div class="wrap"><h1>' . __('LMS Administration', LUMEN_SKILLS_TEXT_DOMAIN) . '</h1><p>' . __('Administer LMS.', LUMEN_SKILLS_TEXT_DOMAIN) . '</p></div>';
}

function lumen_skills_managed_service_callback() {
    echo '<div class="wrap"><h1>' . __('Managed Service', LUMEN_SKILLS_TEXT_DOMAIN) . '</h1><p>' . __('Managed education service.', LUMEN_SKILLS_TEXT_DOMAIN) . '</p></div>';
}

function lumen_skills_audits_callback() {
    echo '<div class="wrap"><h1>' . __('Audits', LUMEN_SKILLS_TEXT_DOMAIN) . '</h1><p>' . __('Perform audits.', LUMEN_SKILLS_TEXT_DOMAIN) . '</p></div>';
}

function lumen_skills_certifications_callback() {
    echo '<div class="wrap"><h1>' . __('Certifications', LUMEN_SKILLS_TEXT_DOMAIN) . '</h1><p>' . __('Manage certifications.', LUMEN_SKILLS_TEXT_DOMAIN) . '</p></div>';
}

function lumen_skills_settings_callback() {
    echo '<div class="wrap"><h1>' . __('Settings', LUMEN_SKILLS_TEXT_DOMAIN) . '</h1><p>' . __('Plugin settings.', LUMEN_SKILLS_TEXT_DOMAIN) . '</p></div>';
}

// Registramos el shortcode para la interfaz del cliente.
function lumen_skills_register_client_shortcode() {
    add_shortcode('lumen_client_dashboard', 'lumen_skills_client_dashboard_shortcode');
}
add_action('init', 'lumen_skills_register_client_shortcode');

// Shortcode para el dashboard del cliente (usa template).
function lumen_skills_client_dashboard_shortcode($atts) {
    // Verificamos si el usuario está logueado y tiene permiso.
    if (!is_user_logged_in() || !current_user_can('view_lumen_dashboard')) {
        wp_redirect(home_url('/client-login/'));
        exit;
    }

    // Iniciamos buffering para capturar output.
    ob_start();

    // Cargamos el template del cliente.
    lumen_skills_load_template('client-dashboard.php', array());

    return ob_get_clean();
}

// Registramos un shortcode para el formulario de login del cliente.
function lumen_skills_register_login_shortcode() {
    add_shortcode('lumen_client_login_form', 'lumen_skills_client_login_form_shortcode');
}
add_action('init', 'lumen_skills_register_login_shortcode');

// Función que genera el formulario de login.
function lumen_skills_client_login_form_shortcode($atts) {
    // Si el usuario ya está logueado, mostramos un mensaje o redirigimos.
    if (is_user_logged_in()) {
        return '<p>Ya estás logueado. <a href="' . home_url('/client-dashboard/') . '">Ve al dashboard</a>.</p>';
    }

    // Generamos el formulario usando wp_login_form() de WordPress, pero customizado.
    $args = array(
        'redirect' => home_url('/client-dashboard/'), // Redirige al dashboard después de login.
        'form_id' => 'lumen-login-form',
        'label_username' => __('Usuario', LUMEN_SKILLS_TEXT_DOMAIN),
        'label_password' => __('Contraseña', LUMEN_SKILLS_TEXT_DOMAIN),
        'label_remember' => __('Recordarme', LUMEN_SKILLS_TEXT_DOMAIN),
        'label_log_in' => __('Iniciar Sesión', LUMEN_SKILLS_TEXT_DOMAIN),
        'remember' => true,
        'value_remember' => true,
    );

    // Agregamos enlace a "olvidé contraseña" custom.
    ob_start();
    wp_login_form($args);
    echo '<p><a href="' . wp_lostpassword_url() . '">¿Olvidaste tu contraseña?</a></p>';
    return ob_get_clean();
}

// Función para registrar plantillas personalizadas desde el plugin (best practice para que aparezcan en el dropdown de WordPress).
function lumen_skills_register_page_templates($templates) {
    // Agregamos la plantilla para el dashboard del cliente.
    $templates['client-dashboard-template.php'] = __('Dashboard del Cliente Lumen', LUMEN_SKILLS_TEXT_DOMAIN);
    
    // Puedes agregar más, ej: $templates['login-template.php'] = __('Login del Cliente Lumen', LUMEN_SKILLS_TEXT_DOMAIN);
    return $templates;
}
add_filter('theme_page_templates', 'lumen_skills_register_page_templates');

// Función para cargar la plantilla custom cuando se selecciona (override del tema).
function lumen_skills_load_page_template($template) {
    global $post;
    
    // Verificamos si la página usa nuestra plantilla.
    if ($post) {
        $page_template = get_post_meta($post->ID, '_wp_page_template', true);
        
        if ($page_template === 'client-dashboard-template.php') {
            // Cargamos el template del plugin en lugar del tema.
            $plugin_template = LUMEN_SKILLS_PLUGIN_DIR . 'templates/client-dashboard-template.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
    }
    
    return $template;
}
add_filter('template_include', 'lumen_skills_load_page_template');

