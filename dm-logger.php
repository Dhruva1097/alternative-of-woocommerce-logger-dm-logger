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
        return "<span class='dm-logger-data'>[{$date}]</span> <span class='dm-logger-level'>{$level}</span>: <br><span class='dm-logger-message'>".esc_html($message)."</span> <span class='dm-logger-context'>{$context_string}</span>\n";
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
// Enqueue admin styles for Custom Logger
add_action('admin_enqueue_scripts', 'custom_logger_enqueue_admin_styles');
function custom_logger_enqueue_admin_styles($hook) {
    // Only enqueue on the custom logs page
    if ($hook !== 'toplevel_page_custom-logs') {
        return;
    }

   $version = filemtime(plugin_dir_path(__FILE__) . 'admin-style.css');

    wp_enqueue_style(
        'custom-logger-admin-style',
        plugins_url('admin-style.css', __FILE__),
        array(),
        $version
    );
	// Enqueue Prism.js for syntax highlighting
	wp_enqueue_script('prism-js', 'https://cdnjs.cloudflare.com/ajax/libs/prism/1.23.0/prism.min.js', array(), null, false);
	wp_enqueue_script('prism-js-json', 'https://cdnjs.cloudflare.com/ajax/libs/prism/1.23.0/prism-json.min.js', array(), null, false);
    wp_enqueue_style('prism-css', 'https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/themes/prism.min.css');

    // Enqueue custom JS for JSON prettifying
    $script = '
	jQuery(document).ready(function ($) {
    $(".dm-logger-message").each(function () {
        // Get the raw HTML content of the element
        const jsonString = $(this).html().trim(); // Use .html() and trim whitespace

        // Attempt to parse the JSON string
        try {
            const parsedJson = JSON.parse(jsonString); // Parse the JSON

            // If parsing is successful, stringify it with indentation for better readability
            const highlightedJson = Prism.highlight(
                JSON.stringify(parsedJson, null, 2), // Format JSON for pretty printing
                Prism.languages.js, // Specify the language for highlighting
                "json" // Specify the language name
            );

            // Update the HTML with the highlighted JSON
            $(this).html(highlightedJson);
        } catch (e) {
            console.error("Invalid JSON:", e); // Log the error for debugging
            // Optionally, display an error message in the HTML
        }
    });
});


	';
    wp_add_inline_script('prism-js', $script);
}


// Display Logs in the Admin Page
function display_custom_logs() {
    if (!current_user_can('manage_options')) {
        return;
    }

    global $dm_logger;
    $logs = $dm_logger->get_logs();

    echo '<div class="wrap dm-logger-wrap">';
    echo '<h1>DM Logger</h1>';
    echo '<pre class="dm-logger-pre">';
    if ($logs) {
        foreach ($logs as $log) {
            echo $log;
        }
    } else {
        echo 'No logs available.';
    }
    echo '</pre>';
    echo '</div>';
}
