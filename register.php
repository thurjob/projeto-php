<?php
require_once 'config.php';
session_start();

if(isset($_SESSION["user_id"])){
    header("location: index.php");
    exit;
}

$name = $email = $phone = $password = $confirm_password = "";
$name_err = $email_err = $phone_err = $password_err = $confirm_password_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    
    if(empty(trim($_POST["name"]))){
        $name_err = "Por favor, informe seu nome.";
    } else {
        $name = trim($_POST["name"]);
    }
    
    if(empty(trim($_POST["email"]))){
        $email_err = "Por favor, informe seu email.";
    } else {
        $sql = "SELECT id FROM users WHERE email = :email";
        
        if($stmt = $pdo->prepare($sql)){
            $stmt->bindParam(":email", $param_email, PDO::PARAM_STR);
            
            $param_email = trim($_POST["email"]);
            
            if($stmt->execute()){
                if($stmt->rowCount() == 1){
                    $email_err = "Este email já está em uso.";
                } else{
                    $email = trim($_POST["email"]);
                }
            } else{
                echo "Ops! Algo deu errado. Por favor, tente novamente mais tarde.";
            }

            unset($stmt);
        }
    }
    
    
    if(empty(trim($_POST["phone"]))){
        $phone_err = "Por favor, informe seu telefone.";
    } else {
        $phone = trim($_POST["phone"]);
    }
    
    if(empty(trim($_POST["password"]))){
        $password_err = "Por favor, informe uma senha.";     
    } elseif(strlen(trim($_POST["password"])) < 6){
        $password_err = "A senha deve ter pelo menos 6 caracteres.";
    } else{
        $password = trim($_POST["password"]);
    }
    
    if(empty(trim($_POST["confirm_password"]))){
        $confirm_password_err = "Por favor, confirme a senha.";     
    } else{
        $confirm_password = trim($_POST["confirm_password"]);
        if(empty($password_err) && ($password != $confirm_password)){
            $confirm_password_err = "As senhas não conferem.";
        }
    }
    
    if(empty($name_err) && empty($email_err) && empty($phone_err) && empty($password_err) && empty($confirm_password_err)){
        
        $sql = "INSERT INTO users (name, email, phone, password, role) VALUES (:name, :email, :phone, :password, 'client')";
         
        if($stmt = $pdo->prepare($sql)){
            $stmt->bindParam(":name", $param_name, PDO::PARAM_STR);
            $stmt->bindParam(":email", $param_email, PDO::PARAM_STR);
            $stmt->bindParam(":phone", $param_phone, PDO::PARAM_STR);
            $stmt->bindParam(":password", $param_password, PDO::PARAM_STR);
            
            $param_name = $name;
            $param_email = $email;
            $param_phone = $phone;
            $param_password = password_hash($password, PASSWORD_DEFAULT); 
            
            if($stmt->execute()){
                $_SESSION['register_success'] = "Conta criada com sucesso! Faça login para continuar.";
                header("location: login.php");
            } else{
                echo "Ops! Algo deu errado. Por favor, tente novamente mais tarde.";
            }

            unset($stmt);
        }
    }
    
    unset($pdo);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Barbearia Elite</title>
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
                    <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                    <li><a href="register.php" class="active"><i class="fas fa-user-plus"></i> Cadastro</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="page-wrapper">
        <div class="container">
            <div class="form-container animate-in">
                <div class="form-header">
                    <div class="form-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h2>Criar Conta</h2>
                    <p>Preencha o formulário abaixo para criar sua conta</p>
                </div>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="register-form">
                    <div class="form-row" style="display: flex; gap: 1rem; flex-wrap: wrap;">
                        <div class="form-group <?php echo (!empty($name_err)) ? 'has-error' : ''; ?>" style="flex: 1; min-width: 250px;">
                            <label for="name"><i class="fas fa-user"></i> Nome Completo</label>
                            <input type="text" name="name" id="name" class="form-control" value="<?php echo $name; ?>" placeholder="Seu nome completo">
                            <span class="invalid-feedback"><?php echo $name_err; ?></span>
                        </div>
                        
                        <div class="form-group <?php echo (!empty($email_err)) ? 'has-error' : ''; ?>" style="flex: 1; min-width: 250px;">
                            <label for="email"><i class="fas fa-envelope"></i> Email</label>
                            <input type="email" name="email" id="email" class="form-control" value="<?php echo $email; ?>" placeholder="Seu email">
                            <span class="invalid-feedback"><?php echo $email_err; ?></span>
                        </div>
                    </div>
                    
                    <div class="form-group <?php echo (!empty($phone_err)) ? 'has-error' : ''; ?>">
                        <label for="phone"><i class="fas fa-phone"></i> Telefone</label>
                        <input type="tel" name="phone" id="phone" class="form-control" value="<?php echo $phone; ?>" placeholder="(00) 00000-0000">
                        <span class="invalid-feedback"><?php echo $phone_err; ?></span>
                    </div>
                    
                    <div class="form-row" style="display: flex; gap: 1rem; flex-wrap: wrap;">
                        <div class="form-group <?php echo (!empty($password_err)) ? 'has-error' : ''; ?>" style="flex: 1; min-width: 250px;">
                            <label for="password"><i class="fas fa-lock"></i> Senha</label>
                            <div class="password-container">
                                <input type="password" name="password" id="password" class="form-control" value="<?php echo $password; ?>" placeholder="Mínimo 6 caracteres">
                                <i class="fas fa-eye password-toggle" onclick="togglePassword('password')"></i>
                            </div>
                            <span class="invalid-feedback"><?php echo $password_err; ?></span>
                        </div>
                        
                        <div class="form-group <?php echo (!empty($confirm_password_err)) ? 'has-error' : ''; ?>" style="flex: 1; min-width: 250px;">
                            <label for="confirm_password"><i class="fas fa-lock"></i> Confirmar Senha</label>
                            <div class="password-container">
                                <input type="password" name="confirm_password" id="confirm_password" class="form-control" value="<?php echo $confirm_password; ?>" placeholder="Confirme sua senha">
                                <i class="fas fa-eye password-toggle" onclick="togglePassword('confirm_password')"></i>
                            </div>
                            <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-user-plus"></i> Criar Conta
                        </button>
                    </div>
                    
                    <div class="form-footer">
                        <p>Já tem uma conta? <a href="login.php">Faça Login</a></p>
                    </div>
                </form>
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
            const formGroups = document.querySelectorAll('.form-group');
            
            formGroups.forEach((group, index) => {
                setTimeout(() => {
                    group.classList.add('form-group-animate');
                }, 100 * index);
            });
            
            const phoneInput = document.getElementById('phone');
            phoneInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 0) {
                    value = '(' + value;
                }
                if (value.length > 3) {
                    value = value.substring(0, 3) + ') ' + value.substring(3);
                }
                if (value.length > 10) {
                    value = value.substring(0, 10) + '-' + value.substring(10, 15);
                }
                e.target.value = value;
            });
        });
    </script>
</body>
</html>