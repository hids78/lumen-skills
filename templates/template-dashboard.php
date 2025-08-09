<?php
/** * Template Name: Panel de IA (Herramientas) 
 * v2.5 - Final con 6 tarjetas de resumen y 3 gráficos.
 */
if ( ! is_user_logged_in() ) { 
    wp_redirect( wp_login_url( get_permalink() ) ); 
    exit; 
}

$current_user_id = get_current_user_id();
$stats_handler = new Neo_AI_Stats();
$neo_ai_options = get_option('neo_ai_options', []);
$cost_per_1k_tokens = isset($neo_ai_options['cost_per_1k_tokens']) ? floatval($neo_ai_options['cost_per_1k_tokens']) : 0.002; // Costo por defecto si no está configurado
$summary_stats     = $stats_handler->get_summary_stats( $current_user_id );
$by_agent_stats    = $stats_handler->get_stats_by_agent( $current_user_id );
$usage_chart_data  = $stats_handler->get_daily_token_usage_last_week( $current_user_id, 'queries' );
$token_chart_data  = $stats_handler->get_daily_token_usage_last_week( $current_user_id, 'tokens' );
$recent_logs       = $stats_handler->get_recent_logs( $current_user_id, 10 ); // <<< Se obtienen los logs recientes
$current_user      = wp_get_current_user();
$total_cost   = ($summary_stats['total_tokens'] / 1000) * $cost_per_1k_tokens;
$weekly_cost = ($summary_stats['weekly_tokens'] / 1000) * $cost_per_1k_tokens;
$cost_chart_data = ['labels' => $token_chart_data['labels'], 'data' => []];
foreach ($token_chart_data['data'] as $daily_tokens) {
    $cost_chart_data['data'][] = ($daily_tokens / 1000) * $cost_per_1k_tokens;
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
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                        <a href="#" class="nav-tab has-submenu <?php if (is_page_template('templates/template-playground.php') || is_page_template('templates/template-analysis.php')) echo 'nav-tab-active'; ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8V4H8"/><rect x="4" y="8" width="8" height="12" rx="2"/><path d="M12 12h4"/><path d="M16 8h4"/><path d="M16 16h4"/></svg>
                            <span>Asistentes Inteligentes</span>
                            <svg class="submenu-arrow" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                        </a>
                        <div class="neo-ai-submenu">
                            <a href="<?php echo esc_url(home_url('/playground-de-ia/')); ?>" class="nav-tab-submenu <?php if (is_page_template('templates/template-playground.php')) echo 'nav-tab-active'; ?>">
                                <span>Configuración de Asistentes</span>
                            </a>
                            <a href="<?php echo esc_url(home_url('/analisis-de-conversaciones/')); ?>" class="nav-tab-submenu <?php if (is_page_template('templates/template-analysis.php')) echo 'nav-tab-active'; ?>">
                                <span>Análisis de Conversaciones</span>
                            </a>
                        </div>
                    </div>
                    <div class="neo-ai-submenu-container">
                        <a href="#" class="nav-tab has-submenu">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                            <span>Talento y Cultura</span>
                            <svg class="submenu-arrow" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                        </a>
                        <div class="neo-ai-submenu">
                            <a href="<?php echo esc_url(home_url('/analisis-de-curriculums/')); ?>" class="nav-tab-submenu">
                                <span>Análisis de Currículums</span>
                            </a>
                            <a href="<?php echo esc_url(home_url('/analisis-de-sentimientos-colaboradores/')); ?>" class="nav-tab-submenu">
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
                        <h1>Dashboard</h1>
                    </div>
                    <div class="user-profile-area">
                        <?php echo get_avatar( $current_user->ID, 40 ); ?>
                        <span class="user-name"><?php echo esc_html( $current_user->display_name ); ?></span>
                        <a class="logout-link" href="<?php echo wp_logout_url( home_url('/acceso-de-clientes/') ); ?>" title="Cerrar sesión">Salir</a>
                    </div>
                </header>
                
                <main>
                    <!-- Tarjetas de Resumen -->
                    <div class="summary-boxes">
                        <div class="summary-box"><h3>Consultas (últ. 7 días)</h3><div class="value"><?php echo number_format_i18n( $summary_stats['weekly_queries'] ); ?></div></div>
                        <div class="summary-box"><h3>Tokens (últ. 7 días)</h3><div class="value"><?php echo number_format_i18n( $summary_stats['weekly_tokens'] ); ?></div></div>
                        <div class="summary-box"><h3>Costo (últ. 7 días)</h3><div class="value">$<?php echo number_format_i18n( $weekly_cost, 4 ); ?></div></div>
                        <div class="summary-box"><h3>Consultas (histórico)</h3><div class="value"><?php echo number_format_i18n( $summary_stats['total_queries'] ); ?></div></div>
                        <div class="summary-box"><h3>Tokens (histórico)</h3><div class="value"><?php echo number_format_i18n( $summary_stats['total_tokens'] ); ?></div></div>
                        <div class="summary-box"><h3>Costo (histórico)</h3><div class="value">$<?php echo number_format_i18n( $total_cost, 4 ); ?></div></div>
                    </div>

                    <!-- Contenedor de Gráficos -->
                    <div class="charts-grid-container">
                        <div class="chart-container">
                            <h2>Consultas</h2>
                            <canvas id="dailyUsageChart"></canvas>
                        </div>
                        <div class="chart-container">
                            <h2>Tokens</h2>
                            <canvas id="dailyTokenUsageChart"></canvas>
                        </div>
                        <div class="chart-container">
                            <h2>Costo Diario (USD)</h2>
                            <canvas id="dailyCostChart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Tabla de Desglose por Agente -->
                    <div class="recent-logs">
                        <h2>Uso por Agente</h2>
                        <table class="recent-logs-table">
                            <thead>
                                <tr>
                                    <th>Nombre del Agente</th>
                                    <th>Consultas (últ. 7 días)</th>
                                    <th>Costo (últ. 7 días)</th>
                                    <th>Consultas (histórico)</th>
                                    <th>Costo (histórico)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ( ! empty( $by_agent_stats ) ) : foreach ( $by_agent_stats as $agent_stat ) : ?>
                                    <tr>
                                        <td><?php echo esc_html( $agent_stat->agent_name ?? 'Agente Borrado' ); ?></td>
                                        <td><?php echo number_format_i18n( $agent_stat->weekly_queries ); ?></td>
                                        <td>$<?php echo number_format_i18n( ($agent_stat->weekly_tokens / 1000) * $cost_per_1k_tokens, 4 ); ?></td>
                                        <td><?php echo number_format_i18n( $agent_stat->total_queries ); ?></td>
                                        <td>$<?php echo number_format_i18n( ($agent_stat->total_tokens / 1000) * $cost_per_1k_tokens, 4 ); ?></td>
                                    </tr>
                                <?php endforeach; else : ?>
                                    <tr><td colspan="5">Aún no hay actividad registrada para ningún agente.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <!-- INICIO: Tabla de Consultas Recientes -->
                    <div class="recent-logs">
                        <h2>Últimas Consultas</h2>
                        <?php if (current_user_can('manage_options')) { echo '<!-- DEBUG: Recent Logs Variable: '; var_dump($recent_logs); echo ' -->'; } ?>
                        <table class="recent-logs-table">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Agente</th>
                                    <th>Prompt (resumen)</th>
                                    <th>Tokens</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ( ! empty( $recent_logs ) ) : foreach ( $recent_logs as $log ) : ?>
                                    <tr>
                                        <td><?php echo date_i18n( 'd/m/Y H:i', strtotime( $log->timestamp ) ); ?></td>
                                        <td><?php echo esc_html( $log->tool_used ); ?></td>
                                        <td><?php echo esc_html( wp_trim_words( $log->prompt_text, 10, '...' ) ); ?></td>
                                        <td><?php echo number_format_i18n( $log->total_tokens ); ?></td>
                                    </tr>
                                <?php endforeach; else : ?>
                                    <tr><td colspan="4">No hay consultas recientes.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <!-- FIN: Tabla de Consultas Recientes -->
                </main>
            </div>
        </div>
        
        <div id="neo-ai-notice-container" class="neo-ai-notice-container"></div>
        <?php wp_footer(); ?>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Gráfico 1: Consultas por Día
            if (document.getElementById('dailyUsageChart')) {
                new Chart(document.getElementById('dailyUsageChart').getContext('2d'), { 
                    type: 'bar', data: { labels: <?php echo wp_json_encode($usage_chart_data['labels']); ?>, datasets: [{ label: 'Consultas', data: <?php echo wp_json_encode($usage_chart_data['data']); ?>, backgroundColor: 'rgba(79, 70, 229, 0.6)', borderColor: 'rgba(79, 70, 229, 1)', borderWidth: 1, borderRadius: 4 }] }, options: { scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }, responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } } 
                });
            }
            // Gráfico 2: Tokens por Día
            if (document.getElementById('dailyTokenUsageChart')) {
                new Chart(document.getElementById('dailyTokenUsageChart').getContext('2d'), { 
                    type: 'line', data: { labels: <?php echo wp_json_encode($token_chart_data['labels']); ?>, datasets: [{ label: 'Tokens', data: <?php echo wp_json_encode($token_chart_data['data']); ?>, backgroundColor: 'rgba(22, 163, 74, 0.1)', borderColor: 'rgba(22, 163, 74, 1)', borderWidth: 2, fill: true, tension: 0.4 }] }, options: { scales: { y: { beginAtZero: true } }, responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } } 
                });
            }
            // Gráfico 3: Costo por Día
            if (document.getElementById('dailyCostChart')) {
                new Chart(document.getElementById('dailyCostChart').getContext('2d'), { 
                    type: 'line', data: { labels: <?php echo wp_json_encode($cost_chart_data['labels']); ?>, datasets: [{ label: 'Costo (USD)', data: <?php echo wp_json_encode($cost_chart_data['data']); ?>, backgroundColor: 'rgba(234, 179, 8, 0.1)', borderColor: 'rgba(234, 179, 8, 1)', borderWidth: 2, fill: true, tension: 0.4 }] }, 
                    options: { 
                        scales: { y: { beginAtZero: true, ticks: { callback: function(value) { return '$' + value.toFixed(4); } } } }, 
                        responsive: true, maintainAspectRatio: false, 
                        plugins: { legend: { display: false }, tooltip: { callbacks: { label: function(context) { return 'Costo: $' + context.parsed.y.toFixed(4); } } } }
                    } 
                });
            }
        });
        </script>
    </body>
</html>
