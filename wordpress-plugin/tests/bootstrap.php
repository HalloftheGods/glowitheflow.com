<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__) . '/');
}

if (!defined('ARRAY_A')) {
    define('ARRAY_A', 'ARRAY_A');
}

if (!defined('OBJECT')) {
    define('OBJECT', 'OBJECT');
}

if (!class_exists('WP_REST_Controller')) {
    class WP_REST_Controller {}
}

if (!class_exists('WP_REST_Response')) {
    class WP_REST_Response {
        public $data;
        public $status;
        public function __construct($data = null, $status = 200, $headers = []) {
            $this->data = $data;
            $this->status = $status;
        }
        public function get_data() {
            return $this->data;
        }
        public function get_status() {
            return $this->status;
        }
    }
}

if (!class_exists('WP_Error')) {
    class WP_Error {
        public $code;
        public $message;
        public $data;
        public function __construct($code = '', $message = '', $data = '') {
            $this->code = $code;
            $this->message = $message;
            $this->data = $data;
        }
        public function get_error_code() {
            return $this->code;
        }
        public function get_error_message() {
            return $this->message;
        }
        public function get_error_data() {
            return $this->data;
        }
    }
}

if (!class_exists('WP_REST_Request')) {
    class WP_REST_Request {
        public $params = [];
        public $headers = [];
        public $body = '';
        public function get_param($key) {
            return isset($this->params[$key]) ? $this->params[$key] : null;
        }
        public function set_param($key, $value) {
            $this->params[$key] = $value;
        }
        public function get_json_params() {
            return $this->params;
        }
        public function get_header($key) {
            return isset($this->headers[$key]) ? $this->headers[$key] : null;
        }
        public function set_header($key, $value) {
            $this->headers[$key] = $value;
        }
        public function get_body() {
            return $this->body;
        }
        public function set_body($body) {
            $this->body = $body;
        }
    }
}

if (!class_exists('WP_REST_Server')) {
    class WP_REST_Server {
        const READABLE = 'GET';
        const CREATABLE = 'POST';
    }
}

require_once dirname(__DIR__) . '/includes/class-glow-db.php';
require_once dirname(__DIR__) . '/glowitheflow.php';
