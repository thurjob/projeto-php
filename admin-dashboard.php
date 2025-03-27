<?php
require_once 'config.php';
session_start();

if(!isset($_SESSION["user_id"]) || $_SESSION["role"] != "admin") {
    header("location: login.php");
    exit;
}

$stats = [];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'client'");
$stats['total_clients'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'barber'");
$stats['total_barbers'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM appointments");
$stats['total_appointments'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM appointments WHERE DATE(appointment_date) = CURDATE()");
$stats['today_appointments'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->query("
    SELECT u.name, COUNT(a.id) as total_appointments 
    FROM users u 
    LEFT JOIN appointments a ON u.id = a.barber_id AND a.appointment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    WHERE u.role = 'barber' 
    GROUP BY u.id 
    ORDER BY total_appointments DESC
");
$barber_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("
    SELECT s.name, COUNT(a.id) as total 
    FROM services s 
    LEFT JOIN appointments a ON s.id = a.service_id 
    GROUP BY s.id 
    ORDER BY total DESC 
    LIMIT 5
");
$popular_services = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("
    SELECT u.name, SUM(s.price) as total_revenue 
    FROM users u 
    LEFT JOIN appointments a ON u.id = a.barber_id AND a.appointment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    LEFT JOIN services s ON a.service_id = s.id
    WHERE u.role = 'barber' 
    GROUP BY u.id 
    ORDER BY total_revenue DESC
");
$revenue_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("
    SELECT 
        DAYNAME(appointment_date) as day_name,
        COUNT(*) as total
    FROM appointments
    WHERE appointment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DAYOFWEEK(appointment_date)
    ORDER BY DAYOFWEEK(appointment_date)
");
$weekday_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

$barber_names = [];
$barber_appointments = [];
$barber_revenue = [];

foreach ($barber_stats as $stat) {
    $barber_names[] = $stat['name'];
    $barber_appointments[] = $stat['total_appointments'];
}

foreach ($revenue_stats as $stat) {
    $barber_revenue[] = $stat['total_revenue'];
}

$weekday_names = [];
$weekday_counts = [];

$pt_days = [
    'Sunday' => 'Domingo',
    'Monday' => 'Segunda',
    'Tuesday' => 'Terça',
    'Wednesday' => 'Quarta',
    'Thursday' => 'Quinta',
    'Friday' => 'Sexta',
    'Saturday' => 'Sábado'
];

foreach ($weekday_stats as $stat) {
    $weekday_names[] = $pt_days[$stat['day_name']] ?? $stat['day_name'];
    $weekday_counts[] = $stat['total'];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Barbearia Elite</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/style-admin.css">
    <link rel="stylesheet" href="css/style-admin-fixes.css">
    <link rel="stylesheet" href="css/responsive-menu.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <header>
        <div class="container">
            <h1><i class="fas fa-cut"></i> Barbearia Elite</h1>
            <button class="mobile-menu-toggle" id="mobile-menu-toggle">
                <i class="fas fa-bars"></i>
            </button>
            <nav>
                <ul id="main-menu">
                    <li><a href="admin-dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="admin-appointments.php"><i class="fas fa-calendar-alt"></i> Agendamentos</a></li>
                    <li><a href="admin-services.php"><i class="fas fa-list"></i> Serviços</a></li>
                    <li><a href="admin-barbers.php"><i class="fas fa-user-tie"></i> Barbeiros</a></li>
                    <li><a href="admin-users.php"><i class="fas fa-users"></i> Usuários</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="page-wrapper">
        <div class="container">
            <h2><i class="fas fa-tachometer-alt"></i> Dashboard</h2>
            
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Clientes</h3>
                        <p class="stat-number"><?php echo $stats['total_clients']; ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Barbeiros</h3>
                        <p class="stat-number"><?php echo $stats['total_barbers']; ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Agendamentos</h3>
                        <p class="stat-number"><?php echo $stats['total_appointments']; ?></p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Hoje</h3>
                        <p class="stat-number"><?php echo $stats['today_appointments']; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <h3><i class="fas fa-chart-bar"></i> Desempenho dos Barbeiros (30 dias)</h3>
                    <div class="chart-container">
                        <canvas id="barberPerformanceChart"></canvas>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <h3><i class="fas fa-money-bill-wave"></i> Receita por Barbeiro (30 dias)</h3>
                    <div class="chart-container">
                        <canvas id="barberRevenueChart"></canvas>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <h3><i class="fas fa-calendar-week"></i> Agendamentos por Dia da Semana</h3>
                    <div class="chart-container">
                        <canvas id="weekdayChart"></canvas>
                    </div>
                </div>
                
                <div class="dashboard-card">
                    <h3><i class="fas fa-list"></i> Serviços Mais Populares</h3>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Serviço</th>
                                    <th>Agendamentos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($popular_services as $service): ?>
                                <tr>
                                    <td><?php echo $service['name']; ?></td>
                                    <td><?php echo $service['total']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-card mt-4">
                <h3><i class="fas fa-user-tie"></i> Desempenho Detalhado dos Barbeiros</h3>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Barbeiro</th>
                                <th>Agendamentos (30 dias)</th>
                                <th>Receita (30 dias)</th>
                                <th>Média por Atendimento</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $combined_stats = [];
                            foreach ($barber_stats as $index => $stat) {
                                $combined_stats[$index] = [
                                    'name' => $stat['name'],
                                    'appointments' => $stat['total_appointments'],
                                    'revenue' => isset($revenue_stats[$index]) ? $revenue_stats[$index]['total_revenue'] : 0
                                ];
                            }
                            
                            foreach($combined_stats as $stat): 
                                $avg = $stat['appointments'] > 0 ? $stat['revenue'] / $stat['appointments'] : 0;
                            ?>
                            <tr>
                                <td><?php echo $stat['name']; ?></td>
                                <td><?php echo $stat['appointments']; ?></td>
                                <td>R$ <?php echo number_format($stat['revenue'], 2, ',', '.'); ?></td>
                                <td>R$ <?php echo number_format($avg, 2, ',', '.'); ?></td>
                                <td>
                                    <a href="admin-barber-details.php?name=<?php echo urlencode($stat['name']); ?>" class="btn btn-sm" title="Ver Detalhes">
                                        <i class="fas fa-chart-line"></i> Detalhes
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
            const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
            const mainMenu = document.getElementById('main-menu');
            
            if (mobileMenuToggle) {
                mobileMenuToggle.addEventListener('click', function() {
                    mainMenu.classList.toggle('show');
                });
            }
            
            const barberCtx = document.getElementById('barberPerformanceChart').getContext('2d');
            const barberChart = new Chart(barberCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($barber_names); ?>,
                    datasets: [{
                        label: 'Agendamentos nos últimos 30 dias',
                        data: <?php echo json_encode($barber_appointments); ?>,
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
            
            const revenueCtx = document.getElementById('barberRevenueChart').getContext('2d');
            const revenueChart = new Chart(revenueCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($barber_names); ?>,
                    datasets: [{
                        label: 'Receita nos últimos 30 dias (R$)',
                        data: <?php echo json_encode($barber_revenue); ?>,
                        backgroundColor: 'rgba(75, 192, 192, 0.7)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
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
            
            const weekdayCtx = document.getElementById('weekdayChart').getContext('2d');
            const weekdayChart = new Chart(weekdayCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($weekday_names); ?>,
                    datasets: [{
                        label: 'Agendamentos por dia da semana',
                        data: <?php echo json_encode($weekday_counts); ?>,
                        backgroundColor: 'rgba(255, 159, 64, 0.2)',
                        borderColor: 'rgba(255, 159, 64, 1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
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
        });
    </script>
</body>
</html>