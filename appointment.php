<?php
require_once 'config.php';
session_start();

if(!isset($_SESSION["user_id"])) {
    header("location: login.php");
    exit;
}

if(isset($_SESSION["role"]) && ($_SESSION["role"] == "barber" || $_SESSION["role"] == "admin")) {
    header("location: index.php");
    exit;
}

$service_id = $barber_id = $date = $time = "";
$service_err = $barber_err = $date_err = $time_err = "";
$success_message = "";

if(isset($_GET['service']) && !empty($_GET['service'])) {
    $service_id = $_GET['service'];
}

if(isset($_GET['barber']) && !empty($_GET['barber'])) {
    $barber_id = $_GET['barber'];
}

if($_SERVER["REQUEST_METHOD"] == "POST") {
    
    if(empty($_POST["service_id"])) {
        $service_err = "Por favor, selecione um serviço.";
    } else {
        $service_id = $_POST["service_id"];
    }
    
    if(empty($_POST["barber_id"])) {
        $barber_err = "Por favor, selecione um barbeiro.";
    } else {
        $barber_id = $_POST["barber_id"];
    }
    
    if(empty($_POST["date"])) {
        $date_err = "Por favor, selecione uma data.";
    } else {
        $date = $_POST["date"];
        if(strtotime($date) < strtotime(date('Y-m-d'))) {
            $date_err = "Por favor, selecione uma data futura.";
        }
    }
    
    if(empty($_POST["time"])) {
        $time_err = "Por favor, selecione um horário.";
    } else {
        $time = $_POST["time"];
    }
    
    if(empty($barber_err) && empty($date_err) && empty($time_err)) {
        $sql = "SELECT * FROM appointments WHERE barber_id = :barber_id AND appointment_date = :date AND appointment_time = :time AND status != 'cancelled'";
        
        if($stmt = $pdo->prepare($sql)) {
            $stmt->bindParam(":barber_id", $barber_id, PDO::PARAM_INT);
            $stmt->bindParam(":date", $date, PDO::PARAM_STR);
            $stmt->bindParam(":time", $time, PDO::PARAM_STR);
            
            if($stmt->execute()) {
                if($stmt->rowCount() > 0) {
                    $time_err = "Este horário já está reservado. Por favor, selecione outro horário.";
                }
            }
            unset($stmt);
        }
    }
    
    if(empty($service_err) && empty($barber_err) && empty($date_err) && empty($time_err)) {
        
        $sql = "INSERT INTO appointments (user_id, barber_id, service_id, appointment_date, appointment_time, status) VALUES (:user_id, :barber_id, :service_id, :date, :time, 'pending')";
         
        if($stmt = $pdo->prepare($sql)) {
            $stmt->bindParam(":user_id", $_SESSION["user_id"], PDO::PARAM_INT);
            $stmt->bindParam(":barber_id", $barber_id, PDO::PARAM_INT);
            $stmt->bindParam(":service_id", $service_id, PDO::PARAM_INT);
            $stmt->bindParam(":date", $date, PDO::PARAM_STR);
            $stmt->bindParam(":time", $time, PDO::PARAM_STR);
            
            if($stmt->execute()) {
                $success_message = "Agendamento realizado com sucesso!";
                $service_id = $barber_id = $date = $time = "";
            } else {
                echo "Ops! Algo deu errado. Por favor, tente novamente mais tarde.";
            }

            unset($stmt);
        }
    }
}

$services = [];
$sql = "SELECT * FROM services ORDER BY name";
if($stmt = $pdo->prepare($sql)) {
    if($stmt->execute()) {
        $services = $stmt->fetchAll();
    }
    unset($stmt);
}

$barbers = [];
$sql = "SELECT id, name FROM users WHERE role = 'barber' ORDER BY name";
if($stmt = $pdo->prepare($sql)) {
    if($stmt->execute()) {
        $barbers = $stmt->fetchAll();
    }
    unset($stmt);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendar Horário - Barbearia Elite</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <header>
        <div class="container">
            <h1><i class="fas fa-cut"></i> Barbearia Elite</h1>
            <nav>
                <ul>
                    <li><a href="index.php"><i class="fas fa-home"></i> Início</a></li>
                    <li><a href="services.php"><i class="fas fa-list"></i> Serviços</a></li>
                    <li><a href="appointment.php" class="active"><i class="fas fa-calendar-alt"></i> Agendar</a></li>
                    <li><a href="my-appointments.php"><i class="fas fa-clock"></i> Meus Horários</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="page-wrapper">
        <div class="container">
            <div class="form-container animate-in">
                <div class="form-header">
                    <i class="fas fa-calendar-alt form-icon"></i>
                    <h2>Agendar Horário</h2>
                    <p>Preencha o formulário abaixo para agendar seu horário na Barbearia Elite.</p>
                </div>
                
                <?php 
                if(!empty($success_message)) {
                    echo '<div class="alert alert-success"><i class="fas fa-check-circle"></i> ' . $success_message . '</div>';
                }
                ?>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="animated-form">
                    <div class="form-group">
                        <label><i class="fas fa-cut"></i> Serviço</label>
                        <select name="service_id" id="service_id" class="form-control <?php echo (!empty($service_err)) ? 'is-invalid' : ''; ?>">
                            <option value="">Selecione o Serviço</option>
                            <?php foreach($services as $service): ?>
                                <option value="<?php echo $service['id']; ?>" <?php echo ($service_id == $service['id']) ? 'selected' : ''; ?>>
                                    <?php echo $service['name']; ?> - R$ <?php echo number_format($service['price'], 2, ',', '.'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="invalid-feedback"><?php echo $service_err; ?></span>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-user-tie"></i> Barbeiro</label>
                        <select name="barber_id" id="barber_id" class="form-control <?php echo (!empty($barber_err)) ? 'is-invalid' : ''; ?>">
                            <option value="">Selecione o Barbeiro</option>
                            <?php foreach($barbers as $barber): ?>
                                <option value="<?php echo $barber['id']; ?>" <?php echo ($barber_id == $barber['id']) ? 'selected' : ''; ?>>
                                    <?php echo $barber['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="invalid-feedback"><?php echo $barber_err; ?></span>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-calendar-day"></i> Data</label>
                        <input type="date" name="date" id="date" class="form-control <?php echo (!empty($date_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $date; ?>" min="<?php echo date('Y-m-d'); ?>">
                        <span class="invalid-feedback"><?php echo $date_err; ?></span>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-clock"></i> Horário</label>
                        <select name="time" id="time" class="form-control <?php echo (!empty($time_err)) ? 'is-invalid' : ''; ?>">
                            <option value="">Selecione o Horário</option>
                            <option value="09:00" <?php echo ($time == "09:00") ? 'selected' : ''; ?>>09:00</option>
                            <option value="09:30" <?php echo ($time == "09:30") ? 'selected' : ''; ?>>09:30</option>
                            <option value="10:00" <?php echo ($time == "10:00") ? 'selected' : ''; ?>>10:00</option>
                            <option value="10:30" <?php echo ($time == "10:30") ? 'selected' : ''; ?>>10:30</option>
                            <option value="11:00" <?php echo ($time == "11:00") ? 'selected' : ''; ?>>11:00</option>
                            <option value="11:30" <?php echo ($time == "11:30") ? 'selected' : ''; ?>>11:30</option>
                            <option value="13:00" <?php echo ($time == "13:00") ? 'selected' : ''; ?>>13:00</option>
                            <option value="13:30" <?php echo ($time == "13:30") ? 'selected' : ''; ?>>13:30</option>
                            <option value="14:00" <?php echo ($time == "14:00") ? 'selected' : ''; ?>>14:00</option>
                            <option value="14:30" <?php echo ($time == "14:30") ? 'selected' : ''; ?>>14:30</option>
                            <option value="15:00" <?php echo ($time == "15:00") ? 'selected' : ''; ?>>15:00</option>
                            <option value="15:30" <?php echo ($time == "15:30") ? 'selected' : ''; ?>>15:30</option>
                            <option value="16:00" <?php echo ($time == "16:00") ? 'selected' : ''; ?>>16:00</option>
                            <option value="16:30" <?php echo ($time == "16:30") ? 'selected' : ''; ?>>16:30</option>
                            <option value="17:00" <?php echo ($time == "17:00") ? 'selected' : ''; ?>>17:00</option>
                            <option value="17:30" <?php echo ($time == "17:30") ? 'selected' : ''; ?>>17:30</option>
                            <option value="18:00" <?php echo ($time == "18:00") ? 'selected' : ''; ?>>18:00</option>
                            <option value="18:30" <?php echo ($time == "18:30") ? 'selected' : ''; ?>>18:30</option>
                        </select>
                        <span class="invalid-feedback"><?php echo $time_err; ?></span>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-block btn-primary">
                            <i class="fas fa-calendar-check"></i> Confirmar Agendamento
                        </button>
                    </div>
                </form>
                
                <div class="appointment-info" style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #eee;">
                    <h3><i class="fas fa-info-circle"></i> Informações Importantes</h3>
                    <ul style="list-style: none; padding: 0; margin: 1rem 0;">
                        <li style="margin-bottom: 0.5rem; display: flex; align-items: flex-start;">
                            <i class="fas fa-check-circle" style="color: var(--primary-color); margin-right: 0.5rem; margin-top: 0.25rem;"></i>
                            <span>Chegue com 10 minutos de antecedência para não perder seu horário.</span>
                        </li>
                        <li style="margin-bottom: 0.5rem; display: flex; align-items: flex-start;">
                            <i class="fas fa-check-circle" style="color: var(--primary-color); margin-right: 0.5rem; margin-top: 0.25rem;"></i>
                            <span>Em caso de cancelamento, avise com pelo menos 2 horas de antecedência.</span>
                        </li>
                        <li style="margin-bottom: 0.5rem; display: flex; align-items: flex-start;">
                            <i class="fas fa-check-circle" style="color: var(--primary-color); margin-right: 0.5rem; margin-top: 0.25rem;"></i>
                            <span>Aceitamos pagamentos em dinheiro, PIX e cartões de crédito/débito.</span>
                        </li>
                    </ul>
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
            const formGroups = document.querySelectorAll('.form-group');
            
            formGroups.forEach((group, index) => {
                setTimeout(() => {
                    group.classList.add('form-group-animate');
                }, 100 * index);
            });
            
            const dateInput = document.getElementById('date');
            const barberSelect = document.getElementById('barber_id');
            const timeSelect = document.getElementById('time');
            
            function checkAvailability() {
                const date = dateInput.value;
                const barberId = barberSelect.value;
                
                if (date && barberId) {
                    console.log(`Verificando disponibilidade para o barbeiro ${barberId} na data ${date}`);
                }
            }
            
            dateInput.addEventListener('change', checkAvailability);
            barberSelect.addEventListener('change', checkAvailability);
        });
    </script>
</body>
</html>