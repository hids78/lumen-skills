<?php
/**
 * Maneja todas las consultas a la base de datos para obtener estadísticas.
 * v2.3 - Añadida función para gráfico de consumo de tokens.
 */

if ( ! defined( 'WPINC' ) ) { die; }

class Neo_AI_Stats {

    private $wpdb;
    private $log_table;
    private $agents_table;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->log_table = $this->wpdb->prefix . 'neo_ai_log';
        $this->agents_table = $this->wpdb->prefix . 'neo_ai_agents';
    }

    /**
     * Obtiene las estadísticas de resumen históricas.
     */
    public function get_summary_stats( $user_id ) {
        if ( empty($user_id) ) { 
            return [ 'total_queries' => 0, 'total_tokens' => 0, 'weekly_queries' => 0, 'weekly_tokens' => 0 ]; 
        }

        $start_of_week = date('Y-m-d H:i:s', strtotime('-7 days'));
        
        $query = $this->wpdb->prepare(
            "SELECT
                COUNT(id) as total_queries,
                COALESCE(SUM(total_tokens), 0) as total_tokens,
                SUM(CASE WHEN timestamp >= %s THEN 1 ELSE 0 END) as weekly_queries,
                COALESCE(SUM(CASE WHEN timestamp >= %s THEN total_tokens ELSE 0 END), 0) as weekly_tokens
            FROM {$this->log_table}
            WHERE user_id = %d",
            $start_of_week,
            $start_of_week,
            $user_id
        );

        $stats = $this->wpdb->get_row( $query, ARRAY_A );

        return [
            'total_queries'  => (int) ($stats['total_queries'] ?? 0),
            'total_tokens'   => (int) ($stats['total_tokens'] ?? 0),
            'weekly_queries' => (int) ($stats['weekly_queries'] ?? 0),
            'weekly_tokens'  => (int) ($stats['weekly_tokens'] ?? 0),
        ];
    }

    /**
     * Obtiene las estadísticas de resumen para el mes actual.
     */
    public function get_monthly_stats( $user_id ) {
        if ( empty($user_id) ) { 
            return array( 'monthly_queries' => 0, 'monthly_tokens'  => 0 ); 
        }

        $start_of_month = date('Y-m-01 00:00:00');
        
        $stats = array();
        $stats['monthly_queries'] = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT COUNT(id) FROM {$this->log_table} WHERE user_id = %d AND timestamp >= %s", $user_id, $start_of_month ) );
        $stats['monthly_tokens'] = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT SUM(total_tokens) FROM {$this->log_table} WHERE user_id = %d AND timestamp >= %s", $user_id, $start_of_month ) );

        return [
            'monthly_queries' => $stats['monthly_queries'] ?? 0,
            'monthly_tokens'  => $stats['monthly_tokens'] ?? 0,
        ];
    }

    /**
     * Obtiene las estadísticas detalladas por agente.
     */
    public function get_stats_by_agent( $user_id ) {
        if (empty($user_id)) return [];

        $start_of_week = date('Y-m-d H:i:s', strtotime('-7 days'));
        
        $query = $this->wpdb->prepare(
            "SELECT
                agents.id as agent_id,
                agents.name as agent_name,
                COUNT(log.id) as total_queries,
                COALESCE(SUM(log.total_tokens), 0) as total_tokens,
                SUM(CASE WHEN log.timestamp >= %s THEN 1 ELSE 0 END) as weekly_queries,
                COALESCE(SUM(CASE WHEN log.timestamp >= %s THEN log.total_tokens ELSE 0 END), 0) as weekly_tokens
            FROM {$this->agents_table} as agents
            LEFT JOIN {$this->log_table} as log ON agents.id = log.agent_id AND log.user_id = agents.user_id
            WHERE agents.user_id = %d
            GROUP BY agents.id, agents.name
            ORDER BY total_tokens DESC",
            $start_of_week,
            $start_of_week,
            $user_id
        );

        return $this->wpdb->get_results( $query );
    }

    /**
     * Obtiene los últimos registros del log para un usuario específico.
     */
    public function get_recent_logs( $user_id, $limit = 5 ) {
        if ( empty($user_id) ) {
            return null;
        }

        $query = $this->wpdb->prepare(
            "SELECT tool_used, prompt_text, total_tokens, timestamp 
            FROM {$this->log_table} 
            WHERE user_id = %d ORDER BY timestamp DESC LIMIT %d",
            $user_id,
            $limit
        );

        return $this->wpdb->get_results( $query );
    }

    /**
     * Obtiene el número de consultas diarias de la última semana para el gráfico.
     */
    public function get_daily_usage_last_week( $user_id ) {
        if ( empty($user_id) ) {
            $empty_chart_data = ['labels' => [], 'data' => []];
            for ($i = 6; $i >= 0; $i--) {
                $empty_chart_data['labels'][] = date_i18n('D, j M', strtotime("-{$i} days"));
                $empty_chart_data['data'][] = 0;
            }
            return $empty_chart_data;
        }

        $start_date = date( 'Y-m-d H:i:s', strtotime('-7 days') );

        $query = $this->wpdb->prepare(
            "SELECT DATE(timestamp) as date, COUNT(id) as query_count
            FROM {$this->log_table}
            WHERE user_id = %d AND timestamp >= %s
            GROUP BY DATE(timestamp)
            ORDER BY DATE(timestamp) ASC",
            $user_id,
            $start_date
        );
        $results = $this->wpdb->get_results( $query );
        
        $chart_data = array( 'labels' => array(), 'data'   => array() );
        $period = new DatePeriod( new DateTime('-6 days'), new DateInterval('P1D'), new DateTime('+1 day') );

        $db_results_by_date = array();
        foreach ($results as $result) {
            $db_results_by_date[$result->date] = $result->query_count;
        }

        foreach ($period as $date) {
            $day_string = $date->format('Y-m-d');
            $chart_data['labels'][] = date_i18n('D, j M', strtotime($day_string));
            $chart_data['data'][] = (int)($db_results_by_date[$day_string] ?? 0);
        }
        
        return $chart_data;
    }

    /**
     * Obtiene el consumo de tokens diario de la última semana para el gráfico.
     */
    public function get_daily_token_usage_last_week( $user_id, $data_type = 'queries' ) {
        if ( empty($user_id) ) {
            $empty_chart_data = ['labels' => [], 'data' => []];
            for ($i = 6; $i >= 0; $i--) {
                $empty_chart_data['labels'][] = date_i18n('D, j M', strtotime("-{$i} days"));
                $empty_chart_data['data'][] = 0;
            }
            return $empty_chart_data;
        }

        $start_date = date( 'Y-m-d H:i:s', strtotime('-7 days') );
        $column_to_aggregate = ($data_type === 'tokens') ? 'SUM(total_tokens)' : 'COUNT(id)';

        $query = $this->wpdb->prepare(
            "SELECT DATE(timestamp) as date, {$column_to_aggregate} as count
            FROM {$this->log_table}
            WHERE user_id = %d AND timestamp >= %s
            GROUP BY DATE(timestamp) ORDER BY DATE(timestamp) ASC",
            $user_id, $start_date
        );
        
        $results = $this->wpdb->get_results( $query );
        $db_results_by_date = [];
        foreach ($results as $result) {
            $db_results_by_date[$result->date] = $result->count;
        }
        
        $chart_data = [ 'labels' => [], 'data' => [] ];
        $period = new DatePeriod( new DateTime('-6 days'), new DateInterval('P1D'), new DateTime('+1 day') );
        foreach ($period as $date) {
            $day_string = $date->format('Y-m-d');
            $chart_data['labels'][] = date_i18n('D, j', strtotime($day_string));
            $chart_data['data'][] = (int)($db_results_by_date[$day_string] ?? 0);
        }
        
        return $chart_data;
    }

    /**
     * Obtiene los logs de la última semana de un agente para análisis.
     * @param int $user_id El ID del usuario propietario.
     * @param int $agent_id El ID del agente a analizar.
     * @return array Un array de objetos con los logs.
     */
    public function get_all_logs_for_agent( $user_id, $agent_id ) {
        if ( empty($user_id) || empty($agent_id) ) {
            return [];
        }

        // Definir el rango de fechas para la última semana
        $start_of_week = date('Y-m-d H:i:s', strtotime('-7 days'));

        $query = $this->wpdb->prepare(
            "SELECT prompt_text, response_text 
            FROM {$this->log_table} 
            WHERE user_id = %d AND agent_id = %d AND timestamp >= %s
            ORDER BY timestamp ASC",
            $user_id,
            $agent_id,
            $start_of_week
        );

        return $this->wpdb->get_results( $query );
    }
}
