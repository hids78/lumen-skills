<?php
/**
 * Clase para manejar roles personalizados en el plugin Lumen Skills.
 * 
 * Esta clase crea roles específicos para super administradores y administradores de clientes.
 * Los roles definen qué permisos tiene cada usuario en WordPress.
 * 
 * @package LumenSkills
 * @since 1.0.0
 */

// Evitamos que se acceda directamente a este archivo por seguridad.
if (!defined('ABSPATH')) {
    exit;
}

class Lumen_Skills_Roles {

    /**
     * Método estático para agregar roles personalizados.
     * Se ejecuta cuando se activa el plugin.
     */
    public static function add_custom_roles() {
        // Creamos el rol de 'Super Admin Lumen' con permisos completos.
        // Este rol hereda de 'administrator' pero lo personalizamos.
        add_role('lumen_super_admin', __('Super Admin Lumen', LUMEN_SKILLS_TEXT_DOMAIN), array(
            'read' => true, // Puede leer contenido.
            'edit_posts' => true, // Puede editar posts.
            'delete_posts' => true, // Puede borrar posts.
            'manage_options' => true, // Puede gestionar opciones del sitio (permiso clave para admins).
            // Agregamos permisos personalizados para nuestro plugin.
            'manage_lumen_skills' => true, // Permiso general para el plugin.
            'manage_assessments' => true, // Para assessments.
            'manage_career_plans' => true, // Para planes de carrera.
            // Puedes agregar más permisos según necesites para cada paso del proceso.
        ));

        // Creamos el rol de 'Admin Cliente Lumen' con permisos limitados.
        // Este rol solo puede acceder a partes específicas, no a todo WordPress.
        add_role('lumen_client_admin', __('Admin Cliente Lumen', LUMEN_SKILLS_TEXT_DOMAIN), array(
            'read' => true, // Puede leer.
            'edit_own_content' => true, // Puede editar su propio contenido.
            // Permisos limitados para el plugin.
            'view_lumen_dashboard' => true, // Ver el dashboard del cliente.
            'manage_own_assessments' => true, // Gestionar assessments propios.
            // No le damos 'manage_options' para que no vea el dashboard completo de WP.
        ));

        // Asignamos estos roles a usuarios existentes si es necesario (opcional).
        // Por ahora, lo dejamos así; puedes asignar roles manualmente en WP.
    }
}