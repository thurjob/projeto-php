<?php
require_once 'config.php';
session_start();

if(!isset($_SESSION["user_id"]) || $_SESSION["role"] != "admin") {
    header("location: login.php");
    exit;
}

$name = $email = $password = $confirm_password = $phone = "";
$name_err = $email_err = $password_err = $confirm_password_err = $phone_err = "";
$success_msg = $error_msg = "";

if(isset($_GET["delete"]) && !empty($_GET["delete"])) {
    $id = $_GET["delete"];
    
    $check_sql = "SELECT COUNT(*) as count FROM appointments WHERE barber_id = :id";
    if($check_stmt = $pdo->prepare($check_sql)) {
        $check_stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $check_stmt->execute();
        $row = $check_stmt->fetch();
        
        if($row["count"] > 0) {
            $error_msg = "Este barbeiro não pode ser excluído pois possui agendamentos associados.";
        } else {
            $delete_sql = "DELETE FROM users WHERE id = :id AND role = 'barber'";
            if($delete_stmt = $pdo->prepare($delete_sql)) {
                $delete_stmt->bindParam(":id", $id, PDO::PARAM_INT);
                
                if($delete_stmt->execute()) {
                    $success_msg = "Barbeiro excluído com sucesso!";
                } else {
                    $error_msg = "Ocorreu um erro ao excluir o barbeiro.";
                }
                
                unset($delete_stmt);
            }
        }
        
        unset($check_stmt);
    }
}

if(isset($_POST["edit_barber"]) && !empty($_POST["edit_barber"])) {
    $id = $_POST["edit_barber"];
    
    if(empty(trim($_POST["name"]))) {
        $name_err = "Por favor, informe o nome.";
    } else {
        $name = trim($_POST["name"]);
    }
    
    if(empty(trim($_POST["email"]))) {
        $email_err = "Por favor, informe o email.";
    } else {
        $sql = "SELECT id FROM users WHERE email = :email AND id != :id";
        
        if($stmt = $pdo->prepare($sql)) {
            $stmt->bindParam(":email", $param_email, PDO::PARAM_STR);
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            
            $param_email = trim($_POST["email"]);
            
            if($stmt->execute()) {
                if($stmt->rowCount() > 0) {
                    $email_err = "Este email já está em uso.";
                } else {
                    $email = trim($_POST["email"]);
                }
            } else {
                echo "Ops! Algo deu errado. Por favor, tente novamente mais tarde.";
            }
            
            unset($stmt);
        }
    }
    
    if(empty(trim($_POST["phone"]))) {
        $phone_err = "Por favor, informe o telefone.";
    } else {
        $phone = trim($_POST["phone"]);
    }
    
    if(!empty(trim($_POST["password"]))) {
        if(strlen(trim($_POST["password"])) < 6) {
            $password_err = "A senha deve ter pelo menos 6 caracteres.";
        } else {
            $password = trim($_POST["password"]);
        }
        
        if(empty(trim($_POST["confirm_password"]))) {
            $confirm_password_err = "Por favor, confirme a senha.";
        } else {
            $confirm_password = trim($_POST["confirm_password"]);
            if(empty($password_err) && ($password != $confirm_password)) {
                $confirm_password_err = "As senhas não coincidem.";
            }
        }
    }
    
    if(empty($name_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err) && empty($phone_err)) {
        if(!empty($password)) {
            $sql = "UPDATE users SET name = :name, email = :email, password = :password, phone = :phone WHERE id = :id AND role = 'barber'";
        } else {
            $sql = "UPDATE users SET name = :name, email = :email, phone = :phone WHERE id = :id AND role = 'barber'";
        }
        
        if($stmt = $pdo->prepare($sql)) {
            $stmt->bindParam(":name", $name, PDO::PARAM_STR);
            $stmt->bindParam(":email", $email, PDO::PARAM_STR);
            $stmt->bindParam(":phone", $phone, PDO::PARAM_STR);
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            
            if(!empty($password)) {
                $param_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt->bindParam(":password", $param_password, PDO::PARAM_STR);
            }
            
            if($stmt->execute()) {
                $success_msg = "Barbeiro atualizado com sucesso!";
                $name = $email = $password = $confirm_password = $phone = "";
                
                header("location: admin-barbers.php");
                exit;
            } else {
                $error_msg = "Ocorreu um erro ao atualizar o barbeiro.";
            }
            
            unset($stmt);
        }
    }
}

if(isset($_POST["add_barber"]) && $_POST["add_barber"] == 1) {
    if(empty(trim($_POST["name"]))) {
        $name_err = "Por favor, informe o nome.";
    } else {
        $name = trim($_POST["name"]);
    }
    
    if(empty(trim($_POST["email"]))) {
        $email_err = "Por favor, informe o email.";
    } else {
        $sql = "SELECT id FROM users WHERE email = :email";
        
        if($stmt = $pdo->prepare($sql)) {
            $stmt->bindParam(":email", $param_email, PDO::PARAM_STR);
            
            $param_email = trim($_POST["email"]);
            
            if($stmt->execute()) {
                if($stmt->rowCount() > 0) {
                    $email_err = "Este email já está em uso.";
                } else {
                    $email = trim($_POST["email"]);
                }
            } else {
                echo "Ops! Algo deu errado. Por favor, tente novamente mais tarde.";
            }
            
            unset($stmt);
        }
    }
    
    if(empty(trim($_POST["phone"]))) {
        $phone_err = "Por favor, informe o telefone.";
    } else {
        $phone = trim($_POST["phone"]);
    }
    
    if(empty(trim($_POST["password"]))) {
        $password_err = "Por favor, informe uma senha.";
    } elseif(strlen(trim($_POST["password"])) < 6) {
        $password_err = "A senha deve ter pelo menos 6 caracteres.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    if(empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Por favor, confirme a senha.";
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if(empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = "As senhas não coincidem.";
        }
    }
    
    if(empty($name_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err) && empty($phone_err)) {
        $sql = "INSERT INTO users (name, email, password, role, phone) VALUES (:name, :email, :password, 'barber', :phone)";
        
        if($stmt = $pdo->prepare($sql)) {
            $stmt->bindParam(":name", $name, PDO::PARAM_STR);
            $stmt->bindParam(":email", $email, PDO::PARAM_STR);
            $stmt->bindParam(":password", $param_password, PDO::PARAM_STR);
            $stmt->bindParam(":phone", $phone, PDO::PARAM_STR);
            
            $param_password = password_hash($password, PASSWORD_DEFAULT);
            
            if($stmt->execute()) {
                $success_msg = "Barbeiro adicionado com sucesso!";
                $name = $email = $password = $confirm_password = $phone = "";
            } else {
                $error_msg = "Ocorreu um erro ao adicionar o barbeiro.";
            }
            
            unset($stmt);
        }
    }
}

if(isset($_GET["edit"]) && !empty($_GET["edit"])) {
    $id = $_GET["edit"];
    
    $sql = "SELECT * FROM users WHERE id = :id AND role = 'barber'";
    if($stmt = $pdo->prepare($sql)) {
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        
        if($stmt->execute()) {
            if($stmt->rowCount() == 1) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $name = $row["name"];
                $email = $row["email"];
                $phone = $row["phone"];
            } else {
                header("location: admin-barbers.php");
                exit;
            }
        } else {
            echo "Ops! Algo deu errado. Por favor, tente novamente mais tarde.";
        }
        
        unset($stmt);
    }
}

$barbers = [];
$sql = "SELECT u.*, 
        (SELECT COUNT(*) FROM appointments a WHERE a.barber_id = u.id) as total_appointments,
        (SELECT SUM(s.price) FROM appointments a JOIN services s ON a.service_id = s.id WHERE a.barber_id = u.id) as total_revenue
        FROM users u 
        WHERE u.role = 'barber' 
        ORDER BY u.name";

if($stmt = $pdo->prepare($sql)) {
    if($stmt->execute()) {
        $barbers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    unset($stmt);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Barbeiros - Barbearia Elite</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/responsive-menu.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .admin-content-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        @media (min-width: 768px) {
            .admin-content-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        
        .admin-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        
        .admin-card h3 {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 1.2rem;
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        
        .admin-form {
            display: flex !important;
            flex-direction: column !important;
            gap: 15px !important;
            min-height: 300px !important;
            opacity: 1 !important;
            visibility: visible !important;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table th, table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        table th {
            background-color: #f8f8f8;
            font-weight: 600;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 0.8rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 30px;
            background-color: #f9f9f9;
            border-radius: 8px;
        }
        
        .empty-state i {
            font-size: 3rem;
            color: #ccc;
            margin-bottom: 15px;
            display: block;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .form-group {
            margin-bottom: 15px;
            display: block !important;
            opacity: 1 !important;
            visibility: visible !important;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
        }
        
        .has-error .form-control {
            border-color: #dc3545;
        }
        
        .invalid-feedback {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 5px;
            display: block;
        }
        
        .password-container {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .badge-success {
            background-color: #28a745;
            color: #fff;
        }
        
        .page-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .page-header i {
            margin-right: 10px;
            font-size: 1.5rem;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 16px;
            border-radius: 4px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
        }
        
        .btn i {
            margin-right: 5px;
        }
        
        .btn-primary {
            background-color: #ffc107;
            color: #212529;
        }
        
        .btn-primary:hover {
            background-color: #e0a800;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: #fff;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        .btn-danger {
            background-color: #dc3545;
            color: #fff;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
        }
        
        .btn-info {
            background-color: #17a2b8;
            color: #fff;
        }
        
        .btn-info:hover {
            background-color: #138496;
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
                <h2><i class="fas fa-user-tie"></i> Gerenciar Barbeiros</h2>
            </div>
            
            <?php if(!empty($success_msg)): ?>
                <div class="alert alert-success">
                    <?php echo $success_msg; ?>
                </div>
            <?php endif; ?>
            
            <?php if(!empty($error_msg)): ?>
                <div class="alert alert-danger">
                    <?php echo $error_msg; ?>
                </div>
            <?php endif; ?>
            
            <div class="admin-content-grid">
                <div class="admin-card">
                    <h3><?php echo isset($_GET["edit"]) ? "Editar Barbeiro" : "Adicionar Novo Barbeiro"; ?></h3>
                    
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="admin-form">
                        <?php if(isset($_GET["edit"])): ?>
                            <input type="hidden" name="edit_barber" value="<?php echo $_GET["edit"]; ?>">
                        <?php else: ?>
                            <input type="hidden" name="add_barber" value="1">
                        <?php endif; ?>
                        
                        <div class="form-group <?php echo (!empty($name_err)) ? 'has-error' : ''; ?>">
                            <label for="name"><i class="fas fa-user"></i> Nome Completo</label>
                            <input type="text" name="name" id="name" class="form-control" value="<?php echo $name; ?>" required>
                            <span class="invalid-feedback"><?php echo $name_err; ?></span>
                        </div>
                        
                        <div class="form-group <?php echo (!empty($email_err)) ? 'has-error' : ''; ?>">
                            <label for="email"><i class="fas fa-envelope"></i> Email</label>
                            <input type="email" name="email" id="email" class="form-control" value="<?php echo $email; ?>" required>
                            <span class="invalid-feedback"><?php echo $email_err; ?></span>
                        </div>
                        
                        <div class="form-group <?php echo (!empty($phone_err)) ? 'has-error' : ''; ?>">
                            <label for="phone"><i class="fas fa-phone"></i> Telefone</label>
                            <input type="text" name="phone" id="phone" class="form-control" value="<?php echo $phone; ?>" placeholder="(11) 99999-9999" required>
                            <span class="invalid-feedback"><?php echo $phone_err; ?></span>
                        </div>
                        
                        <div class="form-group <?php echo (!empty($password_err)) ? 'has-error' : ''; ?>">
                            <label for="password"><i class="fas fa-lock"></i> Senha <?php echo isset($_GET["edit"]) ? "(deixe em branco para manter a atual)" : ""; ?></label>
                            <div class="password-container">
                                <input type="password" name="password" id="password" class="form-control" <?php echo isset($_GET["edit"]) ? "" : "required"; ?>>
                                <i class="fas fa-eye password-toggle" onclick="togglePassword('password')"></i>
                            </div>
                            <span class="invalid-feedback"><?php echo $password_err; ?></span>
                        </div>
                        
                        <div class="form-group <?php echo (!empty($confirm_password_err)) ? 'has-error' : ''; ?>">
                            <label for="confirm_password"><i class="fas fa-lock"></i> Confirmar Senha</label>
                            <div class="password-container">
                                <input type="password" name="confirm_password" id="confirm_password" class="form-control" <?php echo isset($_GET["edit"]) ? "" : "required"; ?>>
                                <i class="fas fa-eye password-toggle" onclick="togglePassword('confirm_password')"></i>
                            </div>
                            <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?php echo isset($_GET["edit"]) ? "Atualizar Barbeiro" : "Adicionar Barbeiro"; ?>
                            </button>
                            
                            <?php if(isset($_GET["edit"])): ?>
                                <a href="admin-barbers.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancelar
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                
                <div class="admin-card">
                    <h3>Lista de Barbeiros</h3>
                    
                    <?php if(empty($barbers)): ?>
                        <div class="empty-state">
                            <i class="fas fa-user-tie"></i>
                            <p>Nenhum barbeiro cadastrado. Adicione seu primeiro barbeiro!</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>Email</th>
                                        <th>Telefone</th>
                                        <th>Agendamentos</th>
                                        <th>Receita Total</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($barbers as $barber): ?>
                                    <tr>
                                        <td><?php echo $barber["name"]; ?></td>
                                        <td><?php echo $barber["email"]; ?></td>
                                        <td><?php echo !empty($barber["phone"]) ? $barber["phone"] : "-"; ?></td>
                                        <td><?php echo $barber["total_appointments"] ? $barber["total_appointments"] : "0"; ?></td>
                                        <td>R$ <?php echo number_format($barber["total_revenue"] ? $barber["total_revenue"] : 0, 2, ',', '.'); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="admin-barbers.php?edit=<?php echo $barber["id"]; ?>" class="btn btn-sm btn-primary" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="admin-barbers.php?delete=<?php echo $barber["id"]; ?>" class="btn btn-sm btn-danger" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este barbeiro?');">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                                <a href="admin-barber-details.php?id=<?php echo $barber["id"]; ?>" class="btn btn-sm btn-info" title="Ver Detalhes">
                                                    <i class="fas fa-chart-line"></i>
                                                </a>
                                            </div>
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
        <div class="container">  ?>
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

    <script src="js/menu.js"></script>
    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.nextElementSibling;
            
            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                input.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const adminForm = document.querySelector('.admin-form');
            if (adminForm) {
                adminForm.style.display = 'flex';
                adminForm.style.visibility = 'visible';
                adminForm.style.opacity = '1';
            }
            
            const formGroups = document.querySelectorAll('.form-group');
            formGroups.forEach(group => {
                group.style.display = 'block';
                group.style.visibility = 'visible';
                group.style.opacity = '1';
            });
            
            const phoneInput = document.getElementById('phone');
            if (phoneInput) {
                phoneInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 0) {
                        value = '(' + value;
                        if (value.length > 3) {
                            value = value.substring(0, 3) + ') ' + value.substring(3);
                        }
                        if (value.length > 10) {
                            value = value.substring(0, 10) + '-' + value.substring(10);
                        }
                        if (value.length > 15) {
                            value = value.substring(0, 15);
                        }
                    }
                    e.target.value = value;
                });
            }
            
            const tableRows = document.querySelectorAll('tbody tr');
            tableRows.forEach((row, index) => {
                row.style.opacity = '0';
                row.style.transform = 'translateY(10px)';
                row.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                
                setTimeout(() => {
                    row.style.opacity = '1';
                    row.style.transform = 'translateY(0)';
                }, 50 * index);
            });
        });
    </script>
</body>
</html>

