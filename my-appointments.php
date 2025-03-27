<?php
require_once 'config.php';
session_start();

if(!isset($_SESSION["user_id"])) {
    header("location: login.php");
    exit;
}

if(isset($_GET['cancel']) && !empty($_GET['cancel'])) {
    $appointment_id = $_GET['cancel'];
    
    $sql = "SELECT * FROM appointments WHERE id = :id AND user_id = :user_id";
    if($stmt = $pdo->prepare($sql)) {
        $stmt->bindParam(":id", $appointment_id, PDO::PARAM_INT);
        $stmt->bindParam(":user_id", $_SESSION["user_id"], PDO::PARAM_INT);
        
        if($stmt->execute()) {
            if($stmt->rowCount() == 1) {
                $update_sql = "UPDATE appointments SET status = 'cancelled' WHERE id = :id";
                if($update_stmt = $pdo->prepare($update_sql)) {
                    $update_stmt->bindParam(":id", $appointment_id, PDO::PARAM_INT);
                    $update_stmt->execute();
                }
                unset($update_stmt);
            }
        }
        unset($stmt);
    }
    
    header("location: my-appointments.php");
    exit;
}

$appointments = [];
$sql = "SELECT a.*, s.name as service_name, s.price, u.name as barber_name 
        FROM appointments a 
        JOIN services s ON a.service_id = s.id 
        JOIN users u ON a.barber_id = u.id 
        WHERE a.user_id = :user_id 
        ORDER BY a.appointment_date DESC, a.appointment_time DESC";

if($stmt = $pdo->prepare($sql)) {
    $stmt->bindParam(":user_id", $_SESSION["user_id"], PDO::PARAM_INT);
    
    if($stmt->execute()) {
        $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    unset($stmt);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Agendamentos - Barbearia Elite</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/responsive-menu.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
                    <li><a href="index.php"><i class="fas fa-home"></i> Início</a></li>
                    <li><a href="services.php"><i class="fas fa-list"></i> Serviços</a></li>
                    <li><a href="appointment.php"><i class="fas fa-calendar-alt"></i> Agendar</a></li>
                    <li><a href="my-appointments.php" class="active"><i class="fas fa-clock"></i> Meus Horários</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="page-wrapper">
        <div class="container">
            <h2><i class="fas fa-clock"></i> Meus Agendamentos</h2>
            
            <?php if(empty($appointments)): ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <p>Você ainda não possui agendamentos. <a href="appointment.php">Agende um horário</a> agora!</p>
                </div>
            <?php else: ?>
                <div class="appointments">
                    <?php foreach($appointments as $appointment): ?>
                        <div class="appointment-card">
                            <h3>Agendamento #<?php echo $appointment['id']; ?></h3>
                            <div class="appointment-details">
                                <div>
                                    <strong>Serviço:</strong> <?php echo $appointment['service_name']; ?>
                                </div>
                                <div>
                                    <strong>Valor:</strong> R$ <?php echo number_format($appointment['price'], 2, ',', '.'); ?>
                                </div>
                                <div>
                                    <strong>Barbeiro:</strong> <?php echo $appointment['barber_name']; ?>
                                </div>
                                <div>
                                    <strong>Data:</strong> <?php echo date('d/m/Y', strtotime($appointment['appointment_date'])); ?>
                                </div>
                                <div>
                                    <strong>Horário:</strong> <?php echo date('H:i', strtotime($appointment['appointment_time'])); ?>
                                </div>
                                <div>
                                    <strong>Status:</strong> 
                                    <span class="status-<?php echo strtolower($appointment['status']); ?>">
                                        <?php 
                                        switch($appointment['status']) {
                                            case 'pending':
                                                echo 'Agendado';
                                                break;
                                            case 'completed':
                                                echo 'Concluído';
                                                break;
                                            case 'cancelled':
                                                echo 'Cancelado';
                                                break;
                                            default:
                                                echo ucfirst($appointment['status']);
                                        }
                                        ?>
                                    </span>
                                </div>
                            </div>
                            
                            <?php if($appointment['status'] == 'pending'): ?>
                                <div class="appointment-actions">
                                    <a href="my-appointments.php?cancel=<?php echo $appointment['id']; ?>" class="btn btn-danger" onclick="return confirm('Tem certeza que deseja cancelar este agendamento?');">
                                        <i class="fas fa-times"></i> Cancelar Agendamento
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <div class="text-center" style="margin-top: 2rem;">
                <a href="appointment.php" class="btn btn-primary">
                    <i class="fas fa-calendar-plus"></i> Fazer Novo Agendamento
                </a>
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
            
            const appointmentCards = document.querySelectorAll('.appointment-card');
            appointmentCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 100 * index);
            });
        });
    </script>
</body>
</html>