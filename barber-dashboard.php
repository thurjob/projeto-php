<?php
require_once 'config.php';
session_start();

if(!isset($_SESSION["user_id"]) || $_SESSION["role"] != "barber") {
    header("location: login.php");
    exit;
}

if(isset($_GET['id']) && !empty($_GET['id']) && isset($_GET['status']) && !empty($_GET['status'])) {
    $appointment_id = $_GET['id'];
    $status = $_GET['status'];
    
    if($status == 'completed' || $status == 'cancelled') {
        $sql = "UPDATE appointments SET status = :status WHERE id = :id AND barber_id = :barber_id";
        if($stmt = $pdo->prepare($sql)) {
            $stmt->bindParam(":status", $status, PDO::PARAM_STR);
            $stmt->bindParam(":id", $appointment_id, PDO::PARAM_INT);
            $stmt->bindParam(":barber_id", $_SESSION["user_id"], PDO::PARAM_INT);
            $stmt->execute();
            $_SESSION['status_success'] = "Status do agendamento atualizado com sucesso!";
            
            unset($stmt);
        }
    }
    
    header("location: barber-dashboard.php");
    exit;
}

$today = date('Y-m-d');

$today_appointments = [];
$sql = "SELECT a.*, s.name as service_name, s.duration, u.name as client_name, u.phone as client_phone 
        FROM appointments a 
        JOIN services s ON a.service_id = s.id 
        JOIN users u ON a.user_id = u.id 
        WHERE a.barber_id = :barber_id AND a.appointment_date = :today AND a.status = 'pending'
        ORDER BY a.appointment_time ASC";

if($stmt = $pdo->prepare($sql)) {
    $stmt->bindParam(":barber_id", $_SESSION["user_id"], PDO::PARAM_INT);
    $stmt->bindParam(":today", $today, PDO::PARAM_STR);
    
    if($stmt->execute()) {
        $today_appointments = $stmt->fetchAll();
    }
    unset($stmt);
}

$upcoming_appointments = [];
$sql = "SELECT a.*, s.name as service_name, s.duration, u.name as client_name, u.phone as client_phone 
        FROM appointments a 
        JOIN services s ON a.service_id = s.id 
        JOIN users u ON a.user_id = u.id 
        WHERE a.barber_id = :barber_id AND a.appointment_date > :today AND a.status = 'pending'
        ORDER BY a.appointment_date ASC, a.appointment_time ASC";

if($stmt = $pdo->prepare($sql)) {
    $stmt->bindParam(":barber_id", $_SESSION["user_id"], PDO::PARAM_INT);
    $stmt->bindParam(":today", $today, PDO::PARAM_STR);
    
    if($stmt->execute()) {
        $upcoming_appointments = $stmt->fetchAll();
    }
    unset($stmt);
}

$past_appointments = [];
$sql = "SELECT a.*, s.name as service_name, u.name as client_name
        FROM appointments a 
        JOIN services s ON a.service_id = s.id 
        JOIN users u ON a.user_id = u.id 
        WHERE a.barber_id = :barber_id AND (a.appointment_date < :today OR (a.appointment_date = :today AND a.status != 'pending'))
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
        LIMIT 10";

if($stmt = $pdo->prepare($sql)) {
    $stmt->bindParam(":barber_id", $_SESSION["user_id"], PDO::PARAM_INT);
    $stmt->bindParam(":today", $today, PDO::PARAM_STR);
    
    if($stmt->execute()) {
        $past_appointments = $stmt->fetchAll();
    }
    unset($stmt);
}

$total_completed = 0;
$sql = "SELECT COUNT(*) FROM appointments WHERE barber_id = :barber_id AND status = 'completed'";
if($stmt = $pdo->prepare($sql)) {
    $stmt->bindParam(":barber_id", $_SESSION["user_id"], PDO::PARAM_INT);
    if($stmt->execute()) {
        $total_completed = $stmt->fetchColumn();
    }
    unset($stmt);
}

$total_pending = 0;
$sql = "SELECT COUNT(*) FROM appointments WHERE barber_id = :barber_id AND status = 'pending'";
if($stmt = $pdo->prepare($sql)) {
    $stmt->bindParam(":barber_id", $_SESSION["user_id"], PDO::PARAM_INT);
    if($stmt->execute()) {
        $total_pending = $stmt->fetchColumn();
    }
    unset($stmt);
}

$total_cancelled = 0;
$sql = "SELECT COUNT(*) FROM appointments WHERE barber_id = :barber_id AND status = 'cancelled'";
if($stmt = $pdo->prepare($sql)) {
    $stmt->bindParam(":barber_id", $_SESSION["user_id"], PDO::PARAM_INT);
    if($stmt->execute()) {
        $total_cancelled = $stmt->fetchColumn();
    }
    unset($stmt);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Barbeiro - Barbearia Elite</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background-color: #fff;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 1.5rem;
            text-align: center;
            transition: var(--transition);
            border-top: 4px solid var(--primary-color);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }
        
        .stat-icon {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--secondary-color);
        }
        
        .stat-label {
            color: var(--text-light);
            font-size: 0.9rem;
        }
        
        .appointment-time {
            font-weight: 700;
            font-size: 1.2rem;
            color: var(--primary-color);
        }
        
        .appointment-card {
            position: relative;
            overflow: hidden;
        }
        
        .appointment-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background-color: var(--primary-color);
        }
        
        .appointment-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-complete {
            background-color: var(--success-color);
            color: white;
        }
        
        .btn-cancel {
            background-color: var(--danger-color);
            color: white;
        }
        
        .dashboard-tabs {
            margin-bottom: 2rem;
        }
        
        .tab-buttons {
            display: flex;
            border-bottom: 1px solid #eee;
            margin-bottom: 1.5rem;
        }
        
        .tab-button {
            padding: 0.75rem 1.5rem;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-weight: 600;
            color: var(--text-light);
            transition: var(--transition);
        }
        
        .tab-button:hover {
            color: var(--primary-color);
        }
        
        .tab-button.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1><i class="fas fa-cut"></i> Barbearia Elite</h1>
            <nav>
                <ul>
                    <li><a href="barber-dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Painel</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="page-wrapper">
        <div class="container">
            <div class="dashboard-header" style="margin-bottom: 2rem;">
                <h2><i class="fas fa-user-tie"></i> Painel do Barbeiro</h2>
                <p>Bem-vindo, <strong><?php echo $_SESSION["name"]; ?></strong>! Gerencie seus agendamentos e visualize seu histórico.</p>
                
                <?php if(isset($_SESSION['status_success'])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $_SESSION['status_success']; ?>
                    </div>
                    <?php unset($_SESSION['status_success']); ?>
                <?php endif; ?>
            </div>
            
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-value"><?php echo $total_completed; ?></div>
                    <div class="stat-label">Atendimentos Concluídos</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar"></i>
                    </div>
                    <div class="stat-value"><?php echo $total_pending; ?></div>
                    <div class="stat-label">Agendamentos Pendentes</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-times"></i>
                    </div>
                    <div class="stat-value"><?php echo $total_cancelled; ?></div>
                    <div class="stat-label">Agendamentos Cancelados</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stat-value"><?php echo count($today_appointments); ?></div>
                    <div class="stat-label">Agendamentos Hoje</div>
                </div>
            </div>
            
            <div class="dashboard-tabs">
                <div class="tab-buttons">
                    <button class="tab-button active" data-tab="today">Hoje</button>
                    <button class="tab-button" data-tab="upcoming">Próximos</button>
                    <button class="tab-button" data-tab="history">Histórico</button>
                </div>
                
                <div class="tab-content active" id="today-tab">
                    <h3><i class="fas fa-calendar-day"></i> Agendamentos de Hoje (<?php echo date('d/m/Y'); ?>)</h3>
                    
                    <?php if(empty($today_appointments)): ?>
                        <div class="empty-state" style="text-align: center; padding: 3rem 0;">
                            <i class="fas fa-calendar" style="font-size: 3rem; color: #ddd; margin-bottom: 1rem;"></i>
                            <p>Não há agendamentos para hoje.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($today_appointments as $appointment): ?>
                            <div class="appointment-card">
                                <div class="appointment-details">
                                    <div>
                                        <span class="appointment-time"><?php echo date('H:i', strtotime($appointment['appointment_time'])); ?></span>
                                    </div>
                                    <div>
                                        <strong>Cliente:</strong> <?php echo $appointment['client_name']; ?>
                                    </div>
                                    <div>
                                        <strong>Telefone:</strong> <?php echo $appointment['client_phone']; ?>
                                    </div>
                                    <div>
                                        <strong>Serviço:</strong> <?php echo $appointment['service_name']; ?> (<?php echo $appointment['duration']; ?> min)
                                    </div>
                                </div>
                                <div class="appointment-actions">
                                    <a href="barber-dashboard.php?id=<?php echo $appointment['id']; ?>&status=completed" class="btn btn-complete" onclick="return confirm('Marcar este agendamento como concluído?');">
                                        <i class="fas fa-check"></i> Concluir
                                    </a>
                                    <a href="barber-dashboard.php?id=<?php echo $appointment['id']; ?>&status=cancelled" class="btn btn-cancel" onclick="return confirm('Tem certeza que deseja cancelar este agendamento?');">
                                        <i class="fas fa-times"></i> Cancelar
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="tab-content" id="upcoming-tab">
                    <h3><i class="fas fa-calendar-alt"></i> Próximos Agendamentos</h3>
                    
                    <?php if(empty($upcoming_appointments)): ?>
                        <div class="empty-state" style="text-align: center; padding: 3rem 0;">
                            <i class="fas fa-calendar" style="font-size: 3rem; color: #ddd; margin-bottom: 1rem;"></i>
                            <p>Não há agendamentos futuros.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($upcoming_appointments as $appointment): ?>
                            <div class="appointment-card">
                                <div class="appointment-details">
                                    <div>
                                        <strong>Data:</strong> <?php echo date('d/m/Y', strtotime($appointment['appointment_date'])); ?>
                                    </div>
                                    <div>
                                        <strong>Horário:</strong> <?php echo date('H:i', strtotime($appointment['appointment_time'])); ?>
                                    </div>
                                    <div>
                                        <strong>Cliente:</strong> <?php echo $appointment['client_name']; ?>
                                    </div>
                                    <div>
                                        <strong>Telefone:</strong> <?php echo $appointment['client_phone']; ?>
                                    </div>
                                    <div>
                                        <strong>Serviço:</strong> <?php echo $appointment['service_name']; ?> (<?php echo $appointment['duration']; ?> min)
                                    </div>
                                </div>
                                <div class="appointment-actions">
                                    <a href="barber-dashboard.php?id=<?php echo $appointment['id']; ?>&status=cancelled" class="btn btn-cancel" onclick="return confirm('Tem certeza que deseja cancelar este agendamento?');">
                                        <i class="fas fa-times"></i> Cancelar
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="tab-content" id="history-tab">
                    <h3><i class="fas fa-history"></i> Histórico de Agendamentos</h3>
                    
                    <?php if(empty($past_appointments)): ?>
                        <div class="empty-state" style="text-align: center; padding: 3rem 0;">
                            <i class="fas fa-calendar" style="font-size: 3rem; color: #ddd; margin-bottom: 1rem;"></i>
                            <p>Não há histórico de agendamentos.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive" style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr>
                                        <th style="text-align: left; padding: 0.75rem; border-bottom: 2px solid #eee;">Data</th>
                                        <th style="text-align: left; padding: 0.75rem; border-bottom: 2px solid #eee;">Horário</th>
                                        <th style="text-align: left; padding: 0.75rem; border-bottom: 2px solid #eee;">Cliente</th>
                                        <th style="text-align: left; padding: 0.75rem; border-bottom: 2px solid #eee;">Serviço</th>
                                        <th style="text-align: left; padding: 0.75rem; border-bottom: 2px solid #eee;">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($past_appointments as $appointment): ?>
                                        <tr>
                                            <td style="padding: 0.75rem; border-bottom: 1px solid #eee;"><?php echo date('d/m/Y', strtotime($appointment['appointment_date'])); ?></td>
                                            <td style="padding: 0.75rem; border-bottom: 1px solid #eee;"><?php echo date('H:i', strtotime($appointment['appointment_time'])); ?></td>
                                            <td style="padding: 0.75rem; border-bottom: 1px solid #eee;"><?php echo $appointment['client_name']; ?></td>
                                            <td style="padding: 0.75rem; border-bottom: 1px solid #eee;"><?php echo $appointment['service_name']; ?></td>
                                            <td style="padding: 0.75rem; border-bottom: 1px solid #eee;">
                                                <span class="status-<?php echo strtolower($appointment['status']); ?>">
                                                    <?php 
                                                    switch($appointment['status']) {
                                                        case 'completed':
                                                            echo 'Concluído';
                                                            break;
                                                        case 'cancelled':
                                                            echo 'Cancelado';
                                                            break;
                                                        default:
                                                            echo 'Pendente';
                                                    }
                                                    ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
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
            const tabButtons = document.querySelectorAll('.tab-button');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabButtons.forEach(button => {
                button.addEventListener('click', () => {
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));
                    
                    button.classList.add('active');
                    
                    const tabId = button.getAttribute('data-tab');
                    document.getElementById(`${tabId}-tab`).classList.add('active');
                });
            });
            
            const appointmentCards = document.querySelectorAll('.appointment-card');
            appointmentCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 100 * index);
            });
        });
    </script>
</body>
</html>