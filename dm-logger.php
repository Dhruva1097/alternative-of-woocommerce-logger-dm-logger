<?php
/*
Plugin Name: DM Logger
Description: A custom logger for WordPress with an admin interface, similar to WooCommerce logger.
Version: 1.0
Author: Dhruv Majithiya
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// CustomLogger Class
class dm_logger {
    private $log_file;

    public function __construct($log_file = null) {
        // Default log file path in wp-content directory
        $this->log_file = $log_file ? $log_file : WP_CONTENT_DIR . '/custom-log.log';
    }

    // Generic method for adding a log entry
    private function add_log($level, $message, $context = []) {
        $log_message = $this->format_message($level, $message, $context);
        $this->write_to_log($log_message);
    }

    // Specific log level methods
    public function info($message, $context = []) {
        $this->add_log('INFO', $message, $context);
    }

    public function warning($message, $context = []) {
        $this->add_log('WARNING', $message, $context);
    }

    public function error($message, $context = []) {
        $this->add_log('ERROR', $message, $context);
    }

    public function debug($message, $context = []) {
        $this->add_log('DEBUG', $message, $context);
    }

    public function critical($message, $context = []) {
        $this->add_log('CRITICAL', $message, $context);
    }

    // Retrieve the latest logs (default: 50 lines)
    public function get_logs($lines = 50) {
        if (!file_exists($this->log_file)) return [];
        $file = file($this->log_file);
        return array_slice(array_reverse($file), 0, $lines);
    }

    // Format the log message
    private function format_message($level, $message, $context) {
        $date = date('Y-m-d H:i:s');
        $context_string = !empty($context) ? json_encode($context) : '';
        return "[{$date}] {$level}: {$message} {$context_string}\n";
    }

    // Write a message to the log file
    private function write_to_log($message) {
        error_log($message, 3, $this->log_file);
    }
}

// Instantiate the logger globally
$dm_logger = new dm_logger();

// Add Admin Menu for Custom Logs
add_action('admin_menu', 'custom_logger_admin_menu');
function custom_logger_admin_menu() {
    add_menu_page(
        'DM Logs',
        'DM Logs',
        'manage_options',
        'custom-logs',
        'display_custom_logs',
        'dashicons-clipboard',
        20
    );
}

// Display Logs in the Admin Page
function display_custom_logs() {
    if (!current_user_can('manage_options')) {
        return;
    }

    global $dm_logger;
    $logs = $dm_logger->get_logs();

    echo '<div class="wrap">';
    echo '<h1>Custom Logs</h1>';
    echo '<pre style="background: #f1f1f1; padding: 15px; max-height: 500px; overflow-y: scroll;">';
    if ($logs) {
        foreach ($logs as $log) {
            echo esc_html($log);
        }
    } else {
        echo 'No logs available.';
    }
    echo '</pre>';
    echo '</div>';
}
