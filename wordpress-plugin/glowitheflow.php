<?php
/**
 * Plugin Name: Glow with the Flow
 * Description: Track flow state and energy cycles.
 * Version: 1.0.0
 * Author: Glow team
 */

$autoload_callback = function ($class) {
    if (strpos($class, 'Glow_') !== 0) {
        return;
    }
    $class_name = strtolower(substr($class, 5));
    $class_name = str_replace('_', '-', $class_name);
    $file = __DIR__ . '/includes/class-glow-' . $class_name . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
};
spl_autoload_register($autoload_callback);

register_activation_hook(__FILE__, ['Glow_DB', 'activate']);
register_deactivation_hook(__FILE__, ['Glow_DB', 'deactivate']);

add_action('rest_api_init', [new Glow_API(), 'register_routes']);
