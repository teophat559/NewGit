<?php
/**
 * BVOTE Interface Lock Configuration
 * Prevents unauthorized frontend modifications
 */

return [
    'protection_status' => 'ACTIVE',
    'lock_date' => date('Y-m-d H:i:s'),
    'message' => 'Interface is locked for production stability',
    'allowed_operations' => ['backend', 'api', 'data']
];