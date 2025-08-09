<?php
/** * Template Name: Análisis de Currículums (Herramientas)
 * v1 - Plantilla para la herramienta de análisis de CVs.
 */
if ( ! is_user_logged_in() ) {
    wp_redirect( wp_login_url( get_permalink() ) );
    exit;
}

$current_user = wp_get_current_user();

$neo_ai_paths = get_option('neo_ai_cv_analysis_paths', [
    'profiles_path' => '',
    'resumes_path' => ''
]);

$job_profiles = []; // Inicializa un array vacío para los perfiles
$profiles_path = $neo_ai_paths['profiles_path'];

// Verifica que la ruta no esté vacía y que sea un directorio válido
if ( ! empty($profiles_path) && is_dir($profiles_path) ) {
    // Escanea el directorio para obtener todos los archivos
    $files = scandir($profiles_path);

    // Filtra los resultados para eliminar '.' y '..' y asegurarse de que son archivos
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && is_file($profiles_path . $file)) {
            $job_profiles[] = $file;
        }
    }
}

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
    <head>
        <meta charset="<?php bloginfo( 'charset' ); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        <?php wp_head(); ?>
    </head>
    <body <?php body_class( 'dashboard-page no-admin-bar' ); ?>>
        <div class="neo-ai-dashboard-layout">
            <div class="neo-ai-mobile-overlay"></div>
            <aside class="neo-ai-sidebar">
                <div class="neo-ai-sidebar-header">NEO<span>AI</span></div>
                <nav class="neo-ai-sidebar-nav">
                    <a href="<?php echo esc_url(home_url('/panel-de-ia/')); ?>" class="nav-tab <?php if (is_page_template('templates/template-dashboard.php')) echo 'nav-tab-active'; ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                        <span>Dashboard</span>
                    </a>
                    <div class="neo-ai-submenu-container">
                        <a href="#" class="nav-tab has-submenu">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8V4H8"/><rect x="4" y="8" width="8" height="12" rx="2"/><path d="M12 12h4"/><path d="M16 8h4"/><path d="M16 16h4"/></svg>
                            <span>Asistentes Inteligentes</span>
                            <svg class="submenu-arrow" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                        </a>
                        <div class="neo-ai-submenu">
                            <a href="<?php echo esc_url(home_url('/playground-de-ia/')); ?>" class="nav-tab-submenu">
                                <span>Configuración de Asistentes</span>
                            </a>
                            <a href="<?php echo esc_url(home_url('/analisis-de-conversaciones/')); ?>" class="nav-tab-submenu">
                                <span>Análisis de Conversaciones</span>
                            </a>
                        </div>
                    </div>
                    <div class="neo-ai-submenu-container">
                        <a href="#" class="nav-tab has-submenu <?php if (is_page_template('templates/template-analysis-curriculum.php') ) echo 'nav-tab-active'; ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                            <span>Talento y Cultura</span>
                            <svg class="submenu-arrow" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                        </a>
                        <div class="neo-ai-submenu">
                            <a href="<?php echo esc_url(home_url('/analisis-de-curriculums/')); ?>" class="nav-tab-submenu <?php if ( is_page_template('templates/template-analysis-curriculum.php') ) echo 'nav-tab-active'; ?>">
                                <span>Análisis de Currículums</span>
                            </a>
                            <a href="<?php echo esc_url(home_url('/analisis-de-sentimientos-colaboradores/')); ?>" class="nav-tab-submenu <?php if ( is_page_template('templates/template-sentiment-analysis.php') ) echo 'nav-tab-active'; ?>">
                                <span>Clima y Sentimiento Laboral</span>
                            </a>
                        </div>
                    </div>
                    <div class="neo-ai-submenu-container">
                        <a href="#" class="nav-tab has-submenu">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" x2="4" y1="21" y2="14"/><line x1="4" x2="4" y1="10" y2="3"/><line x1="12" x2="12" y1="21" y2="12"/><line x1="12" x2="12" y1="8" y2="3"/><line x1="20" x2="20" y1="21" y2="16"/><line x1="20" x2="20" y1="12" y2="3"/><line x1="1" x2="7" y1="14" y2="14"/><line x1="9" x2="15" y1="8" y2="8"/><line x1="17" x2="23" y1="16" y2="16"/></svg>
                            <span>Inteligencia Operativa</span>
                            <svg class="submenu-arrow" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                        </a>
                        <div class="neo-ai-submenu">
                            <a href="<?php echo esc_url(home_url('/procesamiento-de-documentos/')); ?>" class="nav-tab-submenu">
                                <span>Procesamiento de Documentos</span>
                            </a>
                            <a href="<?php echo esc_url(home_url('/optimizacion-de-procesos/')); ?>" class="nav-tab-submenu">
                                <span>Optimización de Procesos</span>
                            </a>
                        </div>
                    </div>
                    <div class="neo-ai-submenu-container">
                        <a href="#" class="nav-tab has-submenu">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
                            <span>Experiencia del Cliente</span>
                            <svg class="submenu-arrow" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                        </a>
                        <div class="neo-ai-submenu">
                            <a href="<?php echo esc_url(home_url('/personalizacion-y-marketing/')); ?>" class="nav-tab-submenu">
                                <span>Personalización y Marketing</span>
                            </a>
                            <a href="<?php echo esc_url(home_url('/analisis-de-comportamiento-ux/')); ?>" class="nav-tab-submenu">
                                <span>Análisis de Comportamiento (UX)</span>
                            </a>
                            <a href="<?php echo esc_url(home_url('/analisis-de-sentimientos/')); ?>" class="nav-tab-submenu">
                                <span>Análisis de sentimientos</span>
                            </a>
                        </div>
                    </div>
                    <div class="neo-ai-submenu-container">
                        <a href="#" class="nav-tab has-submenu">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                            <span>Riesgo y Cumplimiento</span>
                            <svg class="submenu-arrow" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                        </a>
                        <div class="neo-ai-submenu">
                            <a href="<?php echo esc_url(home_url('/gestion-de-datos-y-privacidad/')); ?>" class="nav-tab-submenu">
                                <span>Gestión de Datos y Privacidad</span>
                            </a>
                            <a href="<?php echo esc_url(home_url('/monitoreo-de-riesgos/')); ?>" class="nav-tab-submenu">
                                <span>Monitoreo de Riesgos</span>
                            </a>
                        </div>
                    </div>
                </nav>
            </aside>
            <div class="neo-ai-main-content">
                <header class="neo-ai-page-header">
                    <div class="neo-ai-page-header-left">
                        <button class="neo-ai-mobile-menu-toggle">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                        </button>
                        <h1>Análisis de Currículums</h1>
                    </div>
                    <div class="user-profile-area">
                        <?php echo get_avatar( $current_user->ID, 40 ); ?>
                        <span class="user-name"><?php echo esc_html( $current_user->display_name ); ?></span>
                        <a class="logout-link" href="<?php echo wp_logout_url( home_url('/acceso-de-clientes/') ); ?>" title="Cerrar sesión">Salir</a>
                    </div>
                </header>

                <main>
                    <div class="neo-ai-tabs">
                        <button class="tab-link active" data-tab="tab-execute">Ejecución de Análisis</button>
                        <button class="tab-link" data-tab="tab-settings">Configuración</button>
                    </div>

                    <div id="tab-execute" class="tab-content active">
                        <div class="form-section">
                            <h3 class="form-section-title">Paso 1: Seleccionar Perfil de Puesto</h3>
                            <div class="form-field">
                                <label for="job-profile-selector">Elige el perfil que deseas analizar:</label>
                                <select id="job-profile-selector">
                                    <option value="">-- Elige un perfil --</option>
                                    <?php if ( ! empty($job_profiles) ) : ?>
                                        <?php foreach ($job_profiles as $profile) : ?>
                                            <option value="<?php echo esc_attr($profile); ?>"><?php echo esc_html($profile); ?></option>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <option value="" disabled>No se encontraron perfiles en la ruta configurada.</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <button id="start-analysis-btn" class="button button-primary" disabled>Iniciar Análisis de Currículums</button>
                        </div>

                        <div id="analysis-progress-wrapper" class="form-section" style="display: none;">
                            <h3 class="form-section-title">Análisis en Progreso...</h3>
                            <div class="progress-bar-container">
                                <div class="progress-bar-fill"></div>
                            </div>
                            <p id="progress-text" class="progress-status"></p>
                        </div>

                        <div id="analysis-results-wrapper" class="form-section" style="display: none;">
                            <h3 class="form-section-title">Resultados del Análisis</h3>
                            <table class="recent-logs-table">
                                <thead>
                                    <tr>
                                        <th>Candidato</th>
                                        <th>Email</th>
                                        <th>Teléfono</th>
                                        <th>Coincidencia</th>
                                    </tr>
                                </thead>
                                <tbody id="results-table-body">
                                    </tbody>
                            </table>
                        </div>
                    </div>

                    <div id="tab-settings" class="tab-content">
                        <div class="form-section">
                            <h3 class="form-section-title">Rutas de las Carpetas</h3>
                            <div class="form-field">
                                <label for="job-profiles-path">Ruta a la Carpeta de Perfiles de Puesto</label>
                                <input type="text" id="job-profiles-path" name="job_profiles_path" class="regular-text" value="<?php echo esc_attr($neo_ai_paths['profiles_path']); ?>">
                                <p class="description">Ejemplo: `https://neocloud05.com/documentux/perfiles_de_puestos/`</p>
                            </div>
                            <div class="form-field">
                                <label for="resumes-path">Ruta a la Carpeta de Currículums</label>
                                <input type="text" id="resumes-path" name="resumes_path" class="regular-text" value="<?php echo esc_attr($neo_ai_paths['resumes_path']); ?>">
                                <p class="description">Ejemplo: `https://neocloud05.com/documentux/curriculums/`</p>
                            </div>
                            <button id="save-settings-btn" class="button button-primary">Guardar Configuración</button>
                        </div>
                    </div>
                </main>
            </div>
        </div>
        <div id="neo-ai-notice-container" class="neo-ai-notice-container"></div>
        <?php wp_footer(); ?>
    </body>
</html>