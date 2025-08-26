<?php
/**
 * BVOTE 2025 - Main Entry Point
 * Sistema de votación seguro con autenticación avanzada
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define project root
define('PROJECT_ROOT', __DIR__);

// Include core bootstrap if available
$bootstrapError = null;
if (file_exists(__DIR__ . '/bootstrap.php')) {
    try {
        ob_start();
        require_once __DIR__ . '/bootstrap.php';
        $output = ob_get_clean();
        // If bootstrap outputs error message, capture it
        if (!empty($output) && strpos($output, 'Composer') !== false) {
            $bootstrapError = $output;
        }
    } catch (Exception $e) {
        $bootstrapError = $e->getMessage();
    }
}

// Simple router to handle basic requests
$request_uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($request_uri, PHP_URL_PATH);
$path = trim($path, '/');

// Remove query string
$path = strtok($path, '?');

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BVOTE 2025 - Sistema de Votación</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 600px;
            width: 90%;
        }
        
        h1 {
            color: #4a5568;
            margin-bottom: 20px;
            font-size: 2.5em;
        }
        
        .subtitle {
            color: #718096;
            margin-bottom: 30px;
            font-size: 1.2em;
        }
        
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .feature {
            padding: 20px;
            background: #f7fafc;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }
        
        .feature h3 {
            color: #4a5568;
            margin-bottom: 10px;
        }
        
        .feature p {
            color: #718096;
            font-size: 0.9em;
        }
        
        .actions {
            margin-top: 30px;
        }
        
        .btn {
            display: inline-block;
            padding: 15px 30px;
            margin: 10px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background: #5a67d8;
            transform: translateY(-2px);
        }
        
        .btn.secondary {
            background: #718096;
        }
        
        .btn.secondary:hover {
            background: #4a5568;
        }
        
        .status {
            margin-top: 20px;
            padding: 15px;
            background: #f0fff4;
            border-radius: 10px;
            border: 1px solid #9ae6b4;
        }
        
        .status.warning {
            background: #fffbeb;
            border-color: #f6e05e;
        }
        
        .status.error {
            background: #fed7d7;
            border-color: #feb2b2;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗳️ BVOTE 2025</h1>
        <p class="subtitle">Sistema de Votación Blockchain Seguro</p>
        
        <div class="features">
            <div class="feature">
                <h3>🔐 Seguridad Avanzada</h3>
                <p>Autenticación OTP y protección multi-capa</p>
            </div>
            <div class="feature">
                <h3>📊 Tiempo Real</h3>
                <p>Monitoreo y análisis en vivo</p>
            </div>
            <div class="feature">
                <h3>🤖 Automatización</h3>
                <p>Bot inteligente con Telegram</p>
            </div>
            <div class="feature">
                <h3>📱 Responsive</h3>
                <p>Interfaz adaptable a todos los dispositivos</p>
            </div>
        </div>
        
        <?php
        // System status check
        $systemStatus = 'OK';
        $statusMessage = 'Sistema funcionando correctamente';
        $statusClass = '';
        
        // Check bootstrap error first
        if ($bootstrapError) {
            $systemStatus = 'ERROR';
            $statusMessage = $bootstrapError;
            $statusClass = 'error';
        }
        // Check if core components exist
        elseif (!file_exists(__DIR__ . '/bootstrap.php')) {
            $systemStatus = 'WARNING';
            $statusMessage = 'Bootstrap no encontrado. Sistema en modo básico.';
            $statusClass = 'warning';
        }
        // Check vendor directory
        elseif (!is_dir(__DIR__ . '/vendor')) {
            $systemStatus = 'WARNING'; 
            $statusMessage = 'Dependencias no instaladas. Ejecute: composer install';
            $statusClass = 'warning';
        }
        ?>
        
        <div class="status <?php echo $statusClass; ?>">
            <strong>Estado del Sistema:</strong> <?php echo $systemStatus; ?><br>
            <?php echo $statusMessage; ?>
        </div>
        
        <div class="actions">
            <?php if (file_exists(__DIR__ . '/user')): ?>
                <a href="user/" class="btn">🗳️ Ir a Votación</a>
            <?php endif; ?>
            
            <?php if (file_exists(__DIR__ . '/admin')): ?>
                <a href="admin/" class="btn">⚙️ Panel Admin</a>
            <?php endif; ?>
            
            <?php if (file_exists(__DIR__ . '/tools/integrity-dashboard.php')): ?>
                <a href="tools/integrity-dashboard.php" class="btn secondary">🔍 Dashboard de Integridad</a>
            <?php endif; ?>
            
            <?php if (file_exists(__DIR__ . '/tools/master-integrity.php')): ?>
                <a href="diagnostic.php" class="btn secondary">📊 Diagnóstico</a>
            <?php endif; ?>
        </div>
        
        <div style="margin-top: 30px; color: #718096; font-size: 0.9em;">
            <p>PHP <?php echo PHP_VERSION; ?> | 
               Server: <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></p>
        </div>
    </div>
</body>
</html>