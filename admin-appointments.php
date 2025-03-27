<?php
require_once 'config.php';
session_start();

if(!isset($_SESSION["user_id"]) || $_SESSION["role"] != "admin") {
    header("location: login.php");
    exit;
}

if(isset($_GET['cancel']) && !empty($_GET['cancel'])) {
    $id = $_GET['cancel'];
    
    $sql = "UPDATE appointments SET status = 'cancelled' WHERE id = :id";
    if($stmt = $pdo->prepare($sql)) {
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        if($stmt->execute()) {
            $_SESSION['success_msg'] = "Agendamento cancelado com sucesso!";
        } else {
            $_SESSION['error_msg'] = "Não foi possível cancelar o agendamento.";
        }
    }
    
    header("location: admin-appointments.php");
    exit;
}

if(isset($_GET['complete']) && !empty($_GET['complete'])) {
    $id = $_GET['complete'];
    
    $sql = "UPDATE appointments SET status = 'completed' WHERE id = :id";
    if($stmt = $pdo->prepare($sql)) {
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        if($stmt->execute()) {
            $_SESSION['success_msg'] = "Agendamento marcado como concluído!";
        } else {
            $_SESSION['error_msg'] = "Não foi possível atualizar o agendamento.";
        }
    }
    
    header("location: admin-appointments.php");
    exit;
}

$date_filter = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$barber_filter = isset($_GET['barber']) ? $_GET['barber'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

$sql = "
    SELECT a.*, 
           s.name as service_name, 
           s.price, 
           c.name as client_name, 
           c.phone as client_phone,
           b.name as barber_name
    FROM appointments a
    JOIN services s ON a.service_id = s.id
    JOIN users c ON a.user_id = c.id
    JOIN users b ON a.barber_id = b.id
    WHERE 1=1
";

$params = [];

if (!empty($date_filter)) {
    $sql .= " AND a.appointment_date = :date";
    $params[':date'] = $date_filter;
}

if (!empty($barber_filter)) {
    $sql .= " AND a.barber_id = :barber_id";
    $params[':barber_id'] = $barber_filter;
}

if (!empty($status_filter)) {
    $sql .= " AND a.status = :status";
    $params[':status'] = $status_filter;
}

$sql .= " ORDER BY a.appointment_date, a.appointment_time";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT id, name FROM users WHERE role = 'barber' ORDER BY name");
$barbers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Agendamentos - Barbearia Elite</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/style-admin.css">
    <link rel="stylesheet" href="css/style-admin-fixes.css">
    <link rel="stylesheet" href="css/responsive-menu.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .filter-section {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .filter-buttons {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .status-normal {
            background-color: #6c757d;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.875rem;
            white-space: nowrap;
        }
        
        .date-navigation {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .date-nav-btn {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 5px 10px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .date-nav-btn:hover {
            background-color: #e9ecef;
        }
        
        .current-date {
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .appointment-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            background-color: white;
        }
        
        .appointment-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .appointment-time {
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .appointment-status {
            margin-left: auto;
        }
    </style>
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
                    <li><a href="admin-dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                    <li><a href="admin-appointments.php" class="active"><i class="fas fa-calendar-alt"></i> Agendamentos</a></li>
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
            <h2><i class="fas fa-calendar-alt"></i> Gerenciar Agendamentos</h2>
            
            <?php if(isset($_SESSION['success_msg'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success_msg']; ?>
                </div>
                <?php unset($_SESSION['success_msg']); ?>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['error_msg'])): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error_msg']; ?>
                </div>
                <?php unset($_SESSION['error_msg']); ?>
            <?php endif; ?>
            
            <div class="date-navigation">
                <a href="?date=<?php echo date('Y-m-d', strtotime($date_filter . ' -1 day')); ?>&barber=<?php echo $barber_filter; ?>&status=<?php echo $status_filter; ?>" class="date-nav-btn">
                    <i class="fas fa-chevron-left"></i> Dia Anterior
                </a>
                <span class="current-date"><?php echo date('d/m/Y', strtotime($date_filter)); ?></span>
                <a href="?date=<?php echo date('Y-m-d', strtotime($date_filter . ' +1 day')); ?>&barber=<?php echo $barber_filter; ?>&status=<?php echo $status_filter; ?>" class="date-nav-btn">
                    Próximo Dia <i class="fas fa-chevron-right"></i>
                </a>
                <a href="?date=<?php echo date('Y-m-d'); ?>&barber=<?php echo $barber_filter; ?>&status=<?php echo $status_filter; ?>" class="date-nav-btn">
                    <i class="fas fa-calendar-day"></i> Hoje
                </a>
            </div>
            
            <div class="filter-section">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="get" class="filter-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="date"><i class="fas fa-calendar"></i> Data</label>
                            <input type="date" name="date" id="date" class="form-control" value="<?php echo $date_filter; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="barber"><i class="fas fa-user-tie"></i> Barbeiro</label>
                            <select name="barber" id="barber" class="form-control">
                                <option value="">Todos os Barbeiros</option>
                                <?php foreach($barbers as $barber): ?>
                                    <option value="<?php echo $barber['id']; ?>" <?php echo ($barber_filter == $barber['id']) ? 'selected' : ''; ?>>
                                        <?php echo $barber['name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="status"><i class="fas fa-tasks"></i> Status</label>
                            <select name="status" id="status" class="form-control">
                                <option value="">Todos os Status</option>
                                <option value="scheduled" <?php echo ($status_filter == 'scheduled') ? 'selected' : ''; ?>>Agendado</option>
                                <option value="completed" <?php echo ($status_filter == 'completed') ? 'selected' : ''; ?>>Concluído</option>
                                <option value="cancelled" <?php echo ($status_filter == 'cancelled') ? 'selected' : ''; ?>>Cancelado</option>
                            </select>
                        </div>
                        
                        <div class="form-group filter-buttons">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Filtrar
                            </button>
                            <a href="admin-appointments.php" class="btn btn-outline">
                                <i class="fas fa-sync-alt"></i> Limpar Filtros
                            </a>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="admin-card">
                <h3>Lista de Agendamentos</h3>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Horário</th>
                                <th>Cliente</th>
                                <th>Telefone</th>
                                <th>Barbeiro</th>
                                <th>Serviço</th>
                                <th>Valor</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($appointments)): ?>
                                <tr>
                                    <td colspan="9" class="text-center">Nenhum agendamento encontrado para os filtros selecionados.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($appointments as $appointment): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($appointment['appointment_date'])); ?></td>
                                        <td><?php echo date('H:i', strtotime($appointment['appointment_time'])); ?></td>
                                        <td><?php echo $appointment['client_name']; ?></td>
                                        <td><?php echo $appointment['client_phone']; ?></td>
                                        <td><?php echo $appointment['barber_name']; ?></td>
                                        <td><?php echo $appointment['service_name']; ?></td>
                                        <td>R$ <?php echo number_format($appointment['price'], 2, ',', '.'); ?></td>
                                        <td>
                                            <?php 
                                            switch($appointment['status']) {
                                                case 'scheduled':
                                                    echo '<span class="status-pending">Agendado</span>';
                                                    break;
                                                case 'completed':
                                                    echo '<span class="status-completed">Concluído</span>';
                                                    break;
                                                case 'cancelled':
                                                    echo '<span class="status-cancelled">Cancelado</span>';
                                                    break;
                                                default:
                                                    echo $appointment['status'];
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <?php if($appointment['status'] == 'scheduled'): ?>
                                                    <a href="admin-appointments.php?complete=<?php echo $appointment['id']; ?>" class="btn btn-sm btn-success" title="Marcar como Concluído">
                                                        <i class="fas fa-check"></i>
                                                    </a>
                                                    <a href="admin-appointments.php?cancel=<?php echo $appointment['id']; ?>" class="btn btn-sm btn-danger" title="Cancelar" onclick="return confirm('Tem certeza que deseja cancelar este agendamento?');">
                                                        <i class="fas fa-times"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
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
            const dateInput = document.getElementById('date');
            if (dateInput.value === '') {
                const today = new Date();
                const year = today.getFullYear();
                const month = String(today.getMonth() + 1).padStart(2, '0');
                const day = String(today.getDate()).padStart(2, '0');
                dateInput.value = `${year}-${month}-${day}`;
            }
            
            const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
            const mainMenu = document.getElementById('main-menu');
            
            if (mobileMenuToggle) {
                mobileMenuToggle.addEventListener('click', function() {
                    mainMenu.classList.toggle('show');
                });
            }
        });
    </script>
</body>
</html>