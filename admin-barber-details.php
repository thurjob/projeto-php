<?php
require_once 'config.php';
session_start();

if(!isset($_SESSION["user_id"]) || $_SESSION["role"] != "admin") {
    header("location: login.php");
    exit;
}

if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("location: admin-barbers.php");
    exit;
}

$barber_id = $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id AND role = 'barber'");
$stmt->bindParam(":id", $barber_id, PDO::PARAM_INT);
$stmt->execute();
$barber = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$barber) {
    header("location: admin-barbers.php");
    exit;
}

if(isset($barber['specialties'])) {
    $barber['specialties'] = json_decode($barber['specialties'], true) ?: [];
} else {
    $barber['specialties'] = [];
}

$specialties = [];
if(!empty($barber['specialties'])) {
    $placeholders = str_repeat('?,', count($barber['specialties']) - 1) . '?';
    $stmt = $pdo->prepare("SELECT id, name FROM services WHERE id IN ($placeholders)");
    $stmt->execute($barber['specialties']);
    $specialties = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$stats = [];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM appointments WHERE barber_id = :id");
$stmt->bindParam(":id", $barber_id, PDO::PARAM_INT);
$stmt->execute();
$stats['total_appointments'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM appointments WHERE barber_id = :id AND appointment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
$stmt->bindParam(":id", $barber_id, PDO::PARAM_INT);
$stmt->execute();
$stats['recent_appointments'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->prepare("
    SELECT SUM(s.price) as total 
    FROM appointments a 
    JOIN services s ON a.service_id = s.id 
    WHERE a.barber_id = :id
");
$stmt->bindParam(":id", $barber_id, PDO::PARAM_INT);
$stmt->execute();
$stats['total_revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?: 0;

$stmt = $pdo->prepare("
    SELECT SUM(s.price) as total 
    FROM appointments a 
    JOIN services s ON a.service_id = s.id 
    WHERE a.barber_id = :id AND a.appointment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
");
$stmt->bindParam(":id", $barber_id, PDO::PARAM_INT);
$stmt->execute();
$stats['recent_revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?: 0;

$stats['avg_value'] = $stats['total_appointments'] > 0 ? $stats['total_revenue'] / $stats['total_appointments'] : 0;

$stmt = $pdo->prepare("
    SELECT s.name, COUNT(a.id) as total, SUM(s.price) as revenue
    FROM appointments a 
    JOIN services s ON a.service_id = s.id 
    WHERE a.barber_id = :id
    GROUP BY s.id
    ORDER BY total DESC
");
$stmt->bindParam(":id", $barber_id, PDO::PARAM_INT);
$stmt->execute();
$top_services = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(appointment_date, '%Y-%m') as month,
        COUNT(*) as total,
        SUM(s.price) as revenue
    FROM appointments a
    JOIN services s ON a.service_id = s.id
    WHERE a.barber_id = :id AND a.appointment_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(appointment_date, '%Y-%m')
    ORDER BY month
");
$stmt->bindParam(":id", $barber_id, PDO::PARAM_INT);
$stmt->execute();
$monthly_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

$months = [];
$monthly_appointments = [];
$monthly_revenue = [];

$pt_months = [
    '01' => 'Janeiro',
    '02' => 'Fevereiro',
    '03' => 'Março',
    '04' => 'Abril',
    '05' => 'Maio',
    '06' => 'Junho',
    '07' => 'Julho',
    '08' => 'Agosto',
    '09' => 'Setembro',
    '10' => 'Outubro',
    '11' => 'Novembro',
    '12' => 'Dezembro'
];

foreach ($monthly_stats as $stat) {
    $month_parts = explode('-', $stat['month']);
    $month_name = $pt_months[$month_parts[1]] . ' ' . $month_parts[0];
    
    $months[] = $month_name;
    $monthly_appointments[] = $stat['total'];
    $monthly_revenue[] = $stat['revenue'];
}

$stmt = $pdo->prepare("
    SELECT a.*, s.name as service_name, s.price, u.name as client_name, u.phone as client_phone
    FROM appointments a
    JOIN services s ON a.service_id = s.id
    JOIN users u ON a.user_id = u.id
    WHERE a.barber_id = :id AND a.appointment_date >= CURDATE()
    ORDER BY a.appointment_date, a.appointment_time
    LIMIT 10
");
$stmt->bindParam(":id", $barber_id, PDO::PARAM_INT);
$stmt->execute();
$upcoming_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Desempenho do Barbeiro - Barbearia Elite</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/style-admin.css">
    <link rel="stylesheet" href="css/responsive-menu.css">
    <link rel="stylesheet" href="css/style-admin-fixes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <header>
        <div class="container">
            <h1><i class="fas fa-cut"></i> Barbearia Elite</h1>
            <nav>
                <ul>
                    <li><a href="admin-dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="admin-appointments.php"><i class="fas fa-calendar-alt"></i> Agendamentos</a></li>
                    <li><a href="admin-services.php"><i class="fas fa-list"></i> Serviços</a></li>
                    <li><a href="admin-barbers.php" class="active"><i class="fas fa-user-tie"></i> Barbeiros</a></li>
                    <li><a href="admin-users.php"><i class="fas fa-users"></i> Usuários</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="page-wrapper">
        <div class="container">
            <div class="page-header">
                <h2><i class="fas fa-chart-line"></i> Desempenho do Barbeiro</h2>
                <div class="page-actions">
                    <a href="admin-barbers.php" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i> Voltar para Lista
                    </a>
                    <a href="admin-barbers.php?edit=<?php echo $barber['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Editar Barbeiro
                    </a>
                </div>
            </div>
            
            <div class="barber-profile">
                <div class="barber-info">
                    <div class="barber-avatar">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <div class="barber-details">
                        <h3><?php echo $barber['name']; ?></h3>
                        <p><i class="fas fa-envelope"></i> <?php echo $barber['email']; ?></p>
                        <p><i class="fas fa-phone"></i> <?php echo $barber['phone']; ?></p>
                        <?php if(!empty($barber['bio'])): ?>
                            <p class="barber-bio"><?php echo $barber['bio']; ?></p>
                        <?php endif; ?>
                        
                        <?php if(!empty($specialties)): ?>
                            <div class="barber-specialties">
                                <h4>Especialidades:</h4>
                                <div class="specialty-tags">
                                    <?php foreach($specialties as $specialty): ?>
                                        <span class="specialty-tag"><?php echo $specialty['name']; ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total de Atendimentos</h3>
                        <p class="stat-number"><?php echo $stats['total_appointments']; ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Últimos 30 dias</h3>
                        <p class="stat-number"><?php echo $stats['recent_appointments']; ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Receita Total</h3>
                        <p class="stat-number">R$ <?php echo number_format($stats['total_revenue'], 2, ',', '.'); ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Média por Atendimento</h3>
                        <p class="stat-number">R$ <?php echo number_format($stats['avg_value'], 2, ',', '.'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <h3><i class="fas fa-chart-bar"></i> Atendimentos por Mês</h3>
                    <div class="chart-container">
                        <canvas id="monthlyAppointmentsChart"></canvas>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <h3><i class="fas fa-money-bill-wave"></i> Receita por Mês</h3>
                    <div class="chart-container">
                        <canvas id="monthlyRevenueChart"></canvas>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <h3><i class="fas fa-list"></i> Serviços Mais Realizados</h3>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Serviço</th>
                                    <th>Quantidade</th>
                                    <th>Receita</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($top_services as $service): ?>
                                <tr>
                                    <td><?php echo $service['name']; ?></td>
                                    <td><?php echo $service['total']; ?></td>
                                    <td>R$ <?php echo number_format($service['revenue'], 2, ',', '.'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <h3><i class="fas fa-calendar-alt"></i> Próximos Agendamentos</h3>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Horário</th>
                                    <th>Cliente</th>
                                    <th>Serviço</th>
                                    <th>Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($upcoming_appointments)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">Nenhum agendamento próximo.</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach($upcoming_appointments as $appointment): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($appointment['appointment_date'])); ?></td>
                                        <td><?php echo date('H:i', strtotime($appointment['appointment_time'])); ?></td>
                                        <td><?php echo $appointment['client_name']; ?></td>
                                        <td><?php echo $appointment['service_name']; ?></td>
                                        <td>R$ <?php echo number_format($appointment['price'], 2, ',', '.'); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3><i class="fas fa-cut"></i> Barbearia Elite</h3>
                    <p>O melhor em cortes masculinos e tratamentos de barba.</p>
                    <div class="social-icons">
                        <a href="#" class="social-icon"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
                <div class="footer-section">
                    <h3>Contato</h3>
                    <p><i class="fas fa-map-marker-alt"></i> Rua dos Barbeiros, 123</p>
                    <p><i class="fas fa-phone"></i> (11) 99999-9999</p>
                    <p><i class="fas fa-envelope"></i> contato@barbearia.com</p>
                </div>
                <div class="footer-section">
                    <h3>Horário de Funcionamento</h3>
                    <p><i class="fas fa-clock"></i> Segunda a Sexta: 9h às 20h</p>
                    <p><i class="fas fa-clock"></i> Sábado: 9h às 18h</p>
                    <p><i class="fas fa-clock"></i> Domingo: Fechado</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date("Y"); ?> Barbearia Elite. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const appointmentsCtx = document.getElementById('monthlyAppointmentsChart').getContext('2d');
            const appointmentsChart = new Chart(appointmentsCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($months); ?>,
                    datasets: [{
                        label: 'Atendimentos por Mês',
                        data: <?php echo json_encode($monthly_appointments); ?>,
                        backgroundColor: 'rgba(54, 162, 235, 0.7)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
            
            const revenueCtx = document.getElementById('monthlyRevenueChart').getContext('2d');
            const revenueChart = new Chart(revenueCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($months); ?>,
                    datasets: [{
                        label: 'Receita por Mês (R$)',
                        data: <?php echo json_encode($monthly_revenue); ?>,
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>