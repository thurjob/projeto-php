<?php
require_once 'config.php';
session_start();

if(!isset($_SESSION["user_id"]) || $_SESSION["role"] != "admin") {
    header("location: login.php");
    exit;
}

$name = $description = $price = $duration = "";
$name_err = $price_err = $duration_err = "";
$success_msg = $error_msg = "";

if(isset($_GET["delete"]) && !empty($_GET["delete"])) {
    $id = $_GET["delete"];
    
    $check_sql = "SELECT COUNT(*) as count FROM appointments WHERE service_id = :id";
    if($check_stmt = $pdo->prepare($check_sql)) {
        $check_stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $check_stmt->execute();
        $row = $check_stmt->fetch();
        
        if($row["count"] > 0) {
            $error_msg = "Este serviço não pode ser excluído pois está sendo usado em agendamentos.";
        } else {
            $delete_sql = "DELETE FROM services WHERE id = :id";
            if($delete_stmt = $pdo->prepare($delete_sql)) {
                $delete_stmt->bindParam(":id", $id, PDO::PARAM_INT);
                
                if($delete_stmt->execute()) {
                    $success_msg = "Serviço excluído com sucesso!";
                } else {
                    $error_msg = "Ocorreu um erro ao excluir o serviço.";
                }
                
                unset($delete_stmt);
            }
        }
        
        unset($check_stmt);
    }
}

if(isset($_POST["edit_service"]) && !empty($_POST["edit_service"])) {
    $id = $_POST["edit_service"];
    
    if(empty(trim($_POST["name"]))) {
        $name_err = "Por favor, informe o nome do serviço.";
    } else {
        $name = trim($_POST["name"]);
    }
    
    if(empty(trim($_POST["price"]))) {
        $price_err = "Por favor, informe o preço do serviço.";
    } elseif(!is_numeric(str_replace(",", ".", trim($_POST["price"])))) {
        $price_err = "Por favor, informe um preço válido.";
    } else {
        $price = str_replace(",", ".", trim($_POST["price"]));
    }
    
    if(empty(trim($_POST["duration"]))) {
        $duration_err = "Por favor, informe a duração do serviço.";
    } elseif(!is_numeric(trim($_POST["duration"]))) {
        $duration_err = "Por favor, informe uma duração válida em minutos.";
    } else {
        $duration = trim($_POST["duration"]);
    }
    
    $description = trim($_POST["description"]);
    
    if(empty($name_err) && empty($price_err) && empty($duration_err)) {
        $sql = "UPDATE services SET name = :name, description = :description, price = :price, duration = :duration WHERE id = :id";
        
        if($stmt = $pdo->prepare($sql)) {
            $stmt->bindParam(":name", $name, PDO::PARAM_STR);
            $stmt->bindParam(":description", $description, PDO::PARAM_STR);
            $stmt->bindParam(":price", $price);
            $stmt->bindParam(":duration", $duration, PDO::PARAM_INT);
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            
            if($stmt->execute()) {
                $success_msg = "Serviço atualizado com sucesso!";
                $name = $description = $price = $duration = "";
            } else {
                $error_msg = "Ocorreu um erro ao atualizar o serviço.";
            }
            
            unset($stmt);
        }
    }
}

if(isset($_POST["add_service"]) && $_POST["add_service"] == 1) {
    if(empty(trim($_POST["name"]))) {
        $name_err = "Por favor, informe o nome do serviço.";
    } else {
        $name = trim($_POST["name"]);
    }
    
    if(empty(trim($_POST["price"]))) {
        $price_err = "Por favor, informe o preço do serviço.";
    } elseif(!is_numeric(str_replace(",", ".", trim($_POST["price"])))) {
        $price_err = "Por favor, informe um preço válido.";
    } else {
        $price = str_replace(",", ".", trim($_POST["price"]));
    }
    
    if(empty(trim($_POST["duration"]))) {
        $duration_err = "Por favor, informe a duração do serviço.";
    } elseif(!is_numeric(trim($_POST["duration"]))) {
        $duration_err = "Por favor, informe uma duração válida em minutos.";
    } else {
        $duration = trim($_POST["duration"]);
    }
    
    $description = trim($_POST["description"]);
    
    if(empty($name_err) && empty($price_err) && empty($duration_err)) {
        $sql = "INSERT INTO services (name, description, price, duration) VALUES (:name, :description, :price, :duration)";
        
        if($stmt = $pdo->prepare($sql)) {
            $stmt->bindParam(":name", $name, PDO::PARAM_STR);
            $stmt->bindParam(":description", $description, PDO::PARAM_STR);
            $stmt->bindParam(":price", $price);
            $stmt->bindParam(":duration", $duration, PDO::PARAM_INT);
            
            if($stmt->execute()) {
                $success_msg = "Serviço adicionado com sucesso!";
                $name = $description = $price = $duration = "";
            } else {
                $error_msg = "Ocorreu um erro ao adicionar o serviço.";
            }
            
            unset($stmt);
        }
    }
}

if(isset($_GET["edit"]) && !empty($_GET["edit"])) {
    $id = $_GET["edit"];
    
    $sql = "SELECT * FROM services WHERE id = :id";
    if($stmt = $pdo->prepare($sql)) {
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        
        if($stmt->execute()) {
            if($stmt->rowCount() == 1) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $name = $row["name"];
                $description = $row["description"];
                $price = $row["price"];
                $duration = $row["duration"];
            } else {
                header("location: admin-services.php");
                exit;
            }
        } else {
            echo "Ops! Algo deu errado. Por favor, tente novamente mais tarde.";
        }
        
        unset($stmt);
    }
}

$services = [];
$sql = "SELECT * FROM services ORDER BY name";

if($stmt = $pdo->prepare($sql)) {
    if($stmt->execute()) {
        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    unset($stmt);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Serviços - Barbearia Elite</title>
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
                    <li><a href="admin-services.php" class="active"><i class="fas fa-list"></i> Serviços</a></li>
                    <li><a href="admin-barbers.php"><i class="fas fa-user-tie"></i> Barbeiros</a></li>
                    <li><a href="admin-users.php"><i class="fas fa-users"></i> Usuários</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="page-wrapper">
        <div class="container">
            <div class="page-header">
                <h2><i class="fas fa-list"></i> Gerenciar Serviços</h2>
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
                    <h3><?php echo isset($_GET["edit"]) ? "Editar Serviço" : "Adicionar Novo Serviço"; ?></h3>
                    
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="admin-form">
                        <?php if(isset($_GET["edit"])): ?>
                            <input type="hidden" name="edit_service" value="<?php echo $_GET["edit"]; ?>">
                        <?php else: ?>
                            <input type="hidden" name="add_service" value="1">
                        <?php endif; ?>
                        
                        <div class="form-group <?php echo (!empty($name_err)) ? 'has-error' : ''; ?>">
                            <label for="name"><i class="fas fa-tag"></i> Nome do Serviço</label>
                            <input type="text" name="name" id="name" class="form-control" value="<?php echo $name; ?>" required>
                            <span class="invalid-feedback"><?php echo $name_err; ?></span>
                        </div>
                        
                        <div class="form-group">
                            <label for="description"><i class="fas fa-align-left"></i> Descrição</label>
                            <textarea name="description" id="description" class="form-control" rows="3"><?php echo $description; ?></textarea>
                        </div>
                        
                        <div class="form-group <?php echo (!empty($price_err)) ? 'has-error' : ''; ?>">
                            <label for="price"><i class="fas fa-money-bill-wave"></i> Preço (R$)</label>
                            <input type="text" name="price" id="price" class="form-control" value="<?php echo $price; ?>" required>
                            <span class="invalid-feedback"><?php echo $price_err; ?></span>
                        </div>
                        
                        <div class="form-group <?php echo (!empty($duration_err)) ? 'has-error' : ''; ?>">
                            <label for="duration"><i class="fas fa-clock"></i> Duração (minutos)</label>
                            <input type="number" name="duration" id="duration" class="form-control" value="<?php echo $duration; ?>" required>
                            <span class="invalid-feedback"><?php echo $duration_err; ?></span>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?php echo isset($_GET["edit"]) ? "Atualizar Serviço" : "Adicionar Serviço"; ?>
                            </button>
                            
                            <?php if(isset($_GET["edit"])): ?>
                                <a href="admin-services.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancelar
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                
                <div class="admin-card">
                    <h3>Serviços Disponíveis</h3>
                    
                    <?php if(empty($services)): ?>
                        <div class="empty-state">
                            <i class="fas fa-list"></i>
                            <p>Nenhum serviço cadastrado. Adicione seu primeiro serviço!</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>Descrição</th>
                                        <th>Preço</th>
                                        <th>Duração</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($services as $service): ?>
                                    <tr>
                                        <td><?php echo $service["name"]; ?></td>
                                        <td><?php echo !empty($service["description"]) ? $service["description"] : "-"; ?></td>
                                        <td>R$ <?php echo number_format($service["price"], 2, ',', '.'); ?></td>
                                        <td><?php echo $service["duration"]; ?> min</td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="admin-services.php?edit=<?php echo $service["id"]; ?>" class="btn btn-sm btn-primary" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="admin-services.php?delete=<?php echo $service["id"]; ?>" class="btn btn-sm btn-danger" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este serviço?');">
                                                    <i class="fas fa-trash"></i>
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
            
            const priceInput = document.getElementById('price');
            if (priceInput) {
                priceInput.addEventListener('input', function(e) {
                    let value = e.target.value;
                    
                    value = value.replace(/[^\d,]/g, '');
                    
                    const commaCount = (value.match(/,/g) || []).length;
                    if (commaCount > 1) {
                        const parts = value.split(',');
                        value = parts[0] + ',' + parts.slice(1).join('');
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

