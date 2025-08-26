<?php
/**
 * BVOTE Core Bootstrap
 */

namespace BVOTE\Core;

class Bootstrap {
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function initialize() {
        // Define system constants
        define('BVOTE_SYSTEM', true);
        
        // Set timezone
        date_default_timezone_set('UTC');
        
        // Start session if needed
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return $this;
    }
}
