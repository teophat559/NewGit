<?php
/**
 * BVOTE System Integrity Dashboard
 * Web interface for monitoring system integrity
 */

// Load integrity report if available
$reportFile = __DIR__ . '/../logs/integrity-report.json';
$report = null;
if (file_exists($reportFile)) {
    $report = json_decode(file_get_contents($reportFile), true);
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BVOTE - System Integrity Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            border-top: 4px solid #667eea;
        }
        
        .stat-card.warning {
            border-top-color: #ffc107;
        }
        
        .stat-card.danger {
            border-top-color: #dc3545;
        }
        
        .stat-card.success {
            border-top-color: #28a745;
        }
        
        .stat-number {
            font-size: 3em;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #666;
            text-transform: uppercase;
            font-size: 0.9em;
            letter-spacing: 1px;
        }
        
        .section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .section h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }
        
        .issue-list {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #eee;
            border-radius: 5px;
            padding: 10px;
        }
        
        .issue-item {
            padding: 8px 12px;
            margin-bottom: 5px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 0.9em;
        }
        
        .issue-item.critical {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .issue-item.warning {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }
        
        .issue-item.info {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .actions {
            margin-top: 20px;
            text-align: center;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 25px;
            margin: 10px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        
        .btn:hover {
            background: #5a67d8;
            transform: translateY(-2px);
        }
        
        .btn.success {
            background: #28a745;
        }
        
        .btn.success:hover {
            background: #218838;
        }
        
        .btn.warning {
            background: #ffc107;
            color: #333;
        }
        
        .btn.warning:hover {
            background: #e0a800;
        }
        
        .btn.danger {
            background: #dc3545;
        }
        
        .btn.danger:hover {
            background: #c82333;
        }
        
        .timestamp {
            color: #666;
            font-size: 0.9em;
            text-align: center;
            margin-top: 20px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-badge.pass {
            background: #d4edda;
            color: #155724;
        }
        
        .status-badge.fail {
            background: #f8d7da;
            color: #721c24;
        }
        
        .no-data {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 40px;
        }
        
        .refresh-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #667eea;
            color: white;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            font-size: 1.5em;
        }
        
        .refresh-btn:hover {
            background: #5a67d8;
            transform: scale(1.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Sistema de Integridad BVOTE</h1>
            <p>Monitoreo y Control de Integridad del Sistema</p>
        </div>

        <?php if ($report): ?>
            <div class="stats-grid">
                <div class="stat-card <?php echo $report['summary']['status'] === 'PASS' ? 'success' : 'danger'; ?>">
                    <div class="stat-number"><?php echo $report['summary']['status']; ?></div>
                    <div class="stat-label">Estado General</div>
                </div>
                
                <div class="stat-card <?php echo $report['summary']['critical_issues'] > 0 ? 'danger' : 'success'; ?>">
                    <div class="stat-number"><?php echo $report['summary']['critical_issues']; ?></div>
                    <div class="stat-label">Problemas Críticos</div>
                </div>
                
                <div class="stat-card <?php echo $report['summary']['duplicate_files'] > 0 ? 'warning' : 'success'; ?>">
                    <div class="stat-number"><?php echo $report['summary']['duplicate_files']; ?></div>
                    <div class="stat-label">Archivos Duplicados</div>
                </div>
                
                <div class="stat-card <?php echo $report['summary']['orphan_files'] > 0 ? 'warning' : 'success'; ?>">
                    <div class="stat-number"><?php echo $report['summary']['orphan_files']; ?></div>
                    <div class="stat-label">Archivos Huérfanos</div>
                </div>
            </div>

            <?php if (!empty($report['issues'])): ?>
            <div class="section">
                <h2>🚨 Problemas Críticos (<?php echo count($report['issues']); ?>)</h2>
                <div class="issue-list">
                    <?php foreach ($report['issues'] as $issue): ?>
                        <div class="issue-item critical">
                            <?php echo htmlspecialchars($issue); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($report['duplicates'])): ?>
            <div class="section">
                <h2>⚠️ Archivos Duplicados (<?php echo count($report['duplicates']); ?>)</h2>
                <div class="issue-list">
                    <?php foreach ($report['duplicates'] as $duplicate): ?>
                        <div class="issue-item warning">
                            <?php echo htmlspecialchars($duplicate['duplicate']); ?> 
                            → duplicado de <?php echo htmlspecialchars($duplicate['original']); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($report['orphanFiles'])): ?>
            <div class="section">
                <h2>📁 Archivos Huérfanos (<?php echo count($report['orphanFiles']); ?>)</h2>
                <div class="issue-list">
                    <?php foreach ($report['orphanFiles'] as $orphan): ?>
                        <div class="issue-item info">
                            <?php echo htmlspecialchars($orphan); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="timestamp">
                📅 Última actualización: <?php echo $report['timestamp']; ?>
            </div>

        <?php else: ?>
            <div class="section">
                <div class="no-data">
                    <h2>📊 No hay datos de integridad disponibles</h2>
                    <p>Ejecute la verificación de integridad para generar un reporte.</p>
                </div>
            </div>
        <?php endif; ?>

        <div class="actions">
            <button class="btn" onclick="runIntegrityCheck()">🔍 Ejecutar Verificación</button>
            <button class="btn success" onclick="runRepair()">🔧 Reparar Sistema</button>
            <button class="btn warning" onclick="runHealthCheck()">🏥 Check de Salud</button>
            <a href="../" class="btn">🏠 Volver al Inicio</a>
        </div>
    </div>

    <button class="refresh-btn" onclick="location.reload()" title="Actualizar">
        🔄
    </button>

    <script>
        function runIntegrityCheck() {
            if (confirm('¿Desea ejecutar la verificación de integridad del sistema?')) {
                window.open('system-integrity-checker.php', '_blank');
                setTimeout(() => {
                    location.reload();
                }, 3000);
            }
        }

        function runRepair() {
            if (confirm('¿Desea ejecutar la herramienta de reparación automática?')) {
                window.open('system-integrity-repair.php', '_blank');
                setTimeout(() => {
                    location.reload();
                }, 5000);
            }
        }

        function runHealthCheck() {
            if (confirm('¿Desea ejecutar la verificación de salud del sistema?')) {
                window.open('system-health-check.php', '_blank');
            }
        }

        // Auto-refresh every 5 minutes
        setInterval(() => {
            location.reload();
        }, 300000);
    </script>
</body>
</html>