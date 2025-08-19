<?php
/**
 * Clase para manejar autenticación personalizada en el plugin Lumen Skills.
 * 
 * Esta clase gestiona el login, olvidé contraseña y recordar contraseña de forma específica,
 * sin usar las interfaces predeterminadas de WordPress.
 * 
 * @package LumenSkills
 * @since 1.0.0
 */

// Evitamos acceso directo por seguridad.
if (!defined('ABSPATH')) {
    exit;
}

class Lumen_Skills_Auth {

    /**
     * Constructor: Aquí registramos los hooks (ganchos) de WordPress.
     */
    public function __construct() {
        // Cambiamos la URL de 'olvidé contraseña' a una personalizada.
        add_filter('lostpassword_url', array($this, 'custom_lostpassword_url'), 10, 2);
        
        // Agregamos acción para manejar el formulario de olvidé contraseña.
        add_action('wp_loaded', array($this, 'handle_lost_password'));
        
        // Opcional: Redirigimos login fallido a una página personalizada.
        add_action('wp_login_failed', array($this, 'custom_login_failed'));
    }

    /**
     * Método para cambiar la URL de 'olvidé contraseña' a una página personalizada.
     */
    public function custom_lostpassword_url($lostpassword_url, $redirect) {
        // Cambiamos a una página que crearemos, como '/client-lost-password/'.
        return home_url('/client-lost-password/') . ($redirect ? '?redirect_to=' . urlencode($redirect) : '');
    }

    /**
     * Método para manejar el envío del formulario de olvidé contraseña.
     */
    public function handle_lost_password() {
        if (isset($_POST['user_login']) && isset($_POST['lumen_lost_password'])) {
            // Aquí procesamos el envío: Enviamos email de reset, etc.
            // Usamos funciones de WP como retrieve_password().
            $errors = retrieve_password();
            if (is_wp_error($errors)) {
                // Manejar errores (mostrar mensaje).
                // Por ahora, un placeholder.
                echo '<p>Error: ' . $errors->get_error_message() . '</p>';
            } else {
                // Éxito: Redirigir o mostrar mensaje.
                echo '<p>Se envió un email para resetear tu contraseña.</p>';
            }
            exit; // Detenemos la ejecución.
        }
    }

    /**
     * Método para redirigir si el login falla, a una página personalizada.
     */
    public function custom_login_failed($username) {
        // Redirigimos a una página de login personalizada con error.
        wp_redirect(home_url('/client-login/?login=failed'));
        exit;
    }
}

// Instanciamos la clase para que se active.
new Lumen_Skills_Auth();