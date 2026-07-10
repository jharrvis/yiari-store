<?php

/**
 * Plugin Deactivator for YIARI Donasi Kukang Plugin
 *
 * Handles plugin deactivation cleanup
 */
class YIARI_Plugin_Deactivator {

    /**
     * Handle plugin deactivation
     *
     * @since    3.1.0
     */
    public static function deactivate() {
        // Clear scheduled cron events
        self::clear_scheduled_hooks();

        // Clear transients
        self::clear_plugin_transients();

        // Log deactivation
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[YIARI Donasi Kukang] Plugin deactivated and cleanup completed');
        }
    }

    /**
     * Clear all scheduled cron hooks
     *
     * @since    3.1.0
     */
    private static function clear_scheduled_hooks() {
        // Clear both old and new hook names to ensure cleanup
        $hooks_to_clear = array(
            'update_exchange_rates',
            'yiari_update_exchange_rates'
        );

        foreach ($hooks_to_clear as $hook) {
            $timestamp = wp_next_scheduled($hook);
            if ($timestamp) {
                wp_unschedule_event($timestamp, $hook);
            }
        }
    }

    /**
     * Clear plugin-specific transients
     *
     * @since    3.1.0
     */
    private static function clear_plugin_transients() {
        delete_transient('kukang_live_exchange_rate');
        delete_transient('kukang_rate_last_update');
        delete_transient('biteship_cities_cache');
    }
}