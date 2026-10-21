<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require __DIR__ . "/includes/conexion.php";
include __DIR__ . "/includes/header.php";

// 🔒 SEGURIDAD: Solo Admin
if (($_SESSION['role'] ?? '') !== 'admin') {
    echo "<script>window.location.href='index.php';</script>";
    exit;
}

// ====================================================
// 1. KPI ESTRATÉGICOS
// ====================================================
$totalUsers = $conn->query("SELECT COUNT(*) as c FROM usuarios WHERE role != 'admin'")->fetch_assoc()['c'];
$totalPubs  = $conn->query("SELECT COUNT(*) as c FROM publicaciones")->fetch_assoc()['c'];
$totalReportes = $conn->query("SELECT COUNT(*) as c FROM reportes")->fetch_assoc()['c'];
$avgPrecio  = $conn->query("SELECT AVG(CAST(precio AS UNSIGNED)) as p FROM publicaciones WHERE categoria='venta'")->fetch_assoc()['p'];

// ====================================================
// 2. ANÁLISIS TÁCTICO (Gráficos)
// ====================================================

// A. Calidad: (Estricto: Solo 4 estrellas vs Solo 2 estrellas)
$sqlCalidad = "
    SELECT 
        p.categoria,
        SUM(CASE WHEN c.calificacion = 4 THEN 1 ELSE 0 END) as positivas,
        SUM(CASE WHEN c.calificacion = 2 THEN 1 ELSE 0 END) as negativas
    FROM calificaciones c
    JOIN publicaciones p ON c.publicacion_id = p.id
    GROUP BY p.categoria
";
$resCalidad = $conn->query($sqlCalidad);

$calidadLabels = [];
$calidadPos = [];
$calidadNeg = [];

while($row = $resCalidad->fetch_assoc()){
    $calidadLabels[] = ucfirst($row['categoria']);
    $calidadPos[] = $row['positivas'];
    $calidadNeg[] = $row['negativas'];
}

// B. Tendencia (7 días)
$dateStats = $conn->query("
    SELECT DATE_FORMAT(fecha, '%d-%m') as dia, COUNT(*) as c 
    FROM publicaciones 
    WHERE fecha >= DATE(NOW()) - INTERVAL 7 DAY 
    GROUP BY DATE(fecha) 
    ORDER BY fecha ASC
");
$daysLabels = [];
$daysData = [];
while ($row = $dateStats->fetch_assoc()) {
    $daysLabels[] = $row['dia'];
    $daysData[] = $row['c'];
}

// C. Categorías
$catStats = $conn->query("SELECT categoria, COUNT(*) as c FROM publicaciones GROUP BY categoria");
$catLabels = [];
$catData = [];
while ($row = $catStats->fetch_assoc()) {
    $catLabels[] = ucfirst($row['categoria']);
    $catData[] = $row['c'];
}

// ====================================================
// 3. GESTIÓN OPERACIONAL (Tablas)
// ====================================================

// A. Últimas Publicaciones (RECUPERADO ✅)
$lastPubs = $conn->query("
    SELECT p.id, p.titulo, p.categoria, p.estado, u.username, p.fecha 
    FROM publicaciones p 
    LEFT JOIN usuarios u ON p.usuario_id = u.id 
    ORDER BY p.fecha DESC LIMIT 5
");

// B. Reportes
$lastReportes = $conn->query("
    SELECT r.*, u.username as reportador, p.titulo, p.id as pub_id
    FROM reportes r 
    LEFT JOIN usuarios u ON r.usuario_id = u.id 
    LEFT JOIN publicaciones p ON r.publicacion_id = p.id
    ORDER BY r.fecha DESC LIMIT 5
");

// C. Usuarios Nuevos
$lastUsers = $conn->query("SELECT username, email, role, created_at FROM usuarios ORDER BY created_at DESC LIMIT 5");
?>

<div class="dashboard-wrapper">
    
    <div class="dash-intro">
        <h1>📊 Centro de Comando LofHood</h1>
        <p>Bienvenido, <b>Admin</b>.</p>
    </div>

    <div class="kpi-container">
        <div class="kpi-card blue">
            <div class="kpi-inner">
                <h3>Usuarios</h3>
                <span class="number"><?= $totalUsers ?></span>
                <span class="sub">Registrados</span>
            </div>
            <div class="icon">👥</div>
        </div>
        <div class="kpi-card green">
            <div class="kpi-inner">
                <h3>Publicaciones</h3>
                <span class="number"><?= $totalPubs ?></span>
                <span class="sub">Activas</span>
            </div>
            <div class="icon">📢</div>
        </div>
        <div class="kpi-card reports">
            <div class="kpi-inner">
                <h3>Reportes</h3>
                <span class="number"><?= $totalReportes ?></span>
                <span class="sub">Totales</span>
            </div>
            <div class="icon">🚨</div>
        </div>
        <div class="kpi-card gold">
            <div class="kpi-inner">
                <h3>Ticket Venta</h3>
                <span class="number">$<?= number_format($avgPrecio, 0, ',', '.') ?></span>
                <span class="sub">Promedio</span>
            </div>
            <div class="icon">💰</div>
        </div>
    </div>

    <div class="charts-grid">
        <div class="chart-card">
            <h3>📈 Tendencia Semanal</h3>
            <div class="chart-container"><canvas id="lineChart"></canvas></div>
        </div>
        <div class="chart-card">
            <h3>⭐ Satisfacción (4★ vs 2★)</h3>
            <div class="chart-container"><canvas id="barChart"></canvas></div>
        </div>
        <div class="chart-card">
            <h3>🥧 Categorías</h3>
            <div class="chart-container"><canvas id="pieChart"></canvas></div>
        </div>
    </div>

    <div class="tables-grid">
        
        <div class="table-card full-width">
            <div class="card-header">
                <h3>📝 Actividad Reciente (Últimas Publicaciones)</h3>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Autor</th>
                        <th>Categoría</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($p = $lastPubs->fetch_assoc()): ?>
                    <tr>
                        <td><?= substr(htmlspecialchars($p['titulo']), 0, 25) ?>...</td>
                        <td>@<?= htmlspecialchars($p['username'] ?? 'Anon') ?></td>
                        <td><span class="badge cat-<?= $p['categoria'] ?>"><?= ucfirst($p['categoria']) ?></span></td>
                        <td><?= ucfirst($p['estado']) ?></td>
                        <td><?= date("d/m H:i", strtotime($p['fecha'])) ?></td>
                        <td><a href="ver_publicacion.php?id=<?= $p['id'] ?>" class="action-link">Ver</a></td>
                        <td><a href="ver_publicacion.php?id=<?= $p['id'] ?>&from=dashboard" class="action-link">Ver</a></td></tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="table-card full-width alert-border">
            <div class="card-header">
                <h3>🚨 Últimos Reportes</h3>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Usuario</th>
                        <th>Publicación</th>
                        <th>Motivo</th>
                        <th>Ver</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($lastReportes->num_rows > 0): ?>
                        <?php while($rep = $lastReportes->fetch_assoc()): ?>
                        <tr>
                            <td><?= date("d/m H:i", strtotime($rep['fecha'])) ?></td>
                            <td><?= htmlspecialchars($rep['reportador'] ?? 'Eliminado') ?></td>
                            <td><?= $rep['titulo'] ? substr(htmlspecialchars($rep['titulo']), 0, 20).'...' : '<em style="color:#999;">Eliminada</em>' ?></td>
                            <td style="color:#d33; font-weight:bold;"><?= htmlspecialchars($rep['motivo']) ?></td>
                            <td>
                                <?php if($rep['pub_id']): ?>
                                    <a href="ver_publicacion.php?id=<?= $rep['pub_id'] ?>" class="btn-action-small">Ir</a>
                                    <a href="ver_publicacion.php?id=<?= $rep['pub_id'] ?>&from=dashboard" class="btn-action-small">Ir</a><?php else: ?> - <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center; color:#888;">Sin reportes.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="table-card">
            <h3>👤 Nuevos Usuarios</h3>
            <table>
                <thead><tr><th>Usuario</th><th>Rol</th><th>Fecha ingreso</th></tr></thead>
                <tbody>
                    <?php while($u = $lastUsers->fetch_assoc()): ?>
                    <tr>
                        <td><b><?= htmlspecialchars($u['username']) ?></b></td>
                        <td><span class="badge role-<?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
                        <td><?= date("d/m", strtotime($u['created_at'])) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="table-card">
            <h3>⚡ Gestión</h3>
            <div class="quick-actions">
                <a href="crear_publicacion.php?cat=actividad" class="btn-dash primary">📢 Crear Aviso Oficial</a>
                <a href="mis_publicaciones.php" class="btn-dash secondary">🛠 Administrar Todo</a>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
Chart.defaults.maintainAspectRatio = false; 

// Gráficos
new Chart(document.getElementById('lineChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($daysLabels) ?>,
        datasets: [{
            label: 'Publicaciones',
            data: <?= json_encode($daysData) ?>,
            borderColor: '#2cd19b',
            backgroundColor: 'rgba(44, 209, 155, 0.1)',
            fill: true,
            tension: 0.4
        }]
    }
});

new Chart(document.getElementById('barChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($calidadLabels) ?>,
        datasets: [
            { label: 'Positivas (4★)', data: <?= json_encode($calidadPos) ?>, backgroundColor: '#28a745' },
            { label: 'Negativas (2★)', data: <?= json_encode($calidadNeg) ?>, backgroundColor: '#dc3545' }
        ]
    },
    options: { scales: { x: { stacked: true }, y: { stacked: true } } }
});

new Chart(document.getElementById('pieChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($catLabels) ?>,
        datasets: [{
            data: <?= json_encode($catData) ?>,
            backgroundColor: ['#36a2eb', '#ff6384', '#ffce56', '#4bc0c0']
        }]
    }
});
</script>

<style>
.dashboard-wrapper { max-width: 1200px; margin: 100px auto 40px; padding: 0 20px; font-family: 'Segoe UI', sans-serif; color: #333; }
.dash-intro { margin-bottom: 30px; }
.dash-intro h1 { font-size: 2rem; color: #2c2c2c; margin-bottom: 5px; }

/* KPIs */
.kpi-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
.kpi-card { background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; border-left: 5px solid #ccc; }
.kpi-card.blue { border-left-color: #36a2eb; }
.kpi-card.green { border-left-color: #28a745; }
.kpi-card.gold { border-left-color: #ffd700; }
.kpi-card.reports { border-left-color: #ff6384; }
.kpi-inner h3 { margin: 0; font-size: 14px; color: #777; text-transform: uppercase; }
.kpi-inner .number { display: block; font-size: 28px; font-weight: bold; color: #333; }
.kpi-inner .sub { font-size: 12px; color: #999; }
.kpi-icon { font-size: 30px; opacity: 0.2; }

/* Charts */
.charts-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px; }
.chart-card { background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
.chart-card h3 { margin: 0 0 15px; font-size: 16px; text-align: center; }
.chart-container { position: relative; height: 250px; width: 100%; }

/* Tablas */
.tables-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
@media(max-width:900px){ .tables-grid { grid-template-columns: 1fr; } }

.table-card { background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow-x: auto; margin-bottom: 20px; }
.table-card.full-width { grid-column: 1 / -1; }
.table-card.alert-border { border: 1px solid #ffcccc; }
.table-card h3 { margin: 0 0 15px; font-size: 18px; }
.card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }

table { width: 100%; border-collapse: collapse; font-size: 14px; }
th { text-align: left; color: #888; padding-bottom: 10px; border-bottom: 2px solid #eee; }
td { padding: 10px 0; border-bottom: 1px solid #f9f9f9; }

/* Botones */
.badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; color: #fff; }
.cat-venta { background: #ff6384; }
.cat-trabajo { background: #36a2eb; }
.cat-actividad { background: #ffce56; color: #333; }
.cat-servicio { background: #4bc0c0; }
.badge.role-admin { background: #333; } 
.badge.role-cliente { background: #17a2b8; }

.btn-action-small { background: #6e6e6e; color: #fff; padding: 4px 8px; border-radius: 4px; text-decoration: none; font-size: 12px; }
.quick-actions { display: flex; flex-direction: column; gap: 10px; }
.btn-dash { display: block; padding: 10px; text-align: center; text-decoration: none; border-radius: 6px; font-weight: 600; }
.btn-dash.primary { background: #2cd19b; color: white; }
.btn-dash.secondary { background: #6e6e6e; color: white; }
</style>

<?php include __DIR__ . "/includes/footer.php"; ?>