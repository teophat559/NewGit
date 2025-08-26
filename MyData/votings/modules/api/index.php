<?php
/**
 * BVOTE api Module Entry Point
 */

// Security check
if (!defined('BVOTE_SYSTEM')) {
    http_response_code(403);
    exit('Direct access not allowed');
}

echo "<h1>BVOTE Api Module</h1>";
echo "<p>Module is operational and ready.</p>";
