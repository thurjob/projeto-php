<?php
require_once 'config.php';
session_start();

if(isset($_SESSION["user_id"])){
    if($_SESSION["role"] == "admin"){
        header("location: admin-dashboard.php");
    } elseif($_SESSION["role"] == "barber"){
        header("location: barber-dashboard.php");
    } else {
        header("location: index.php");
    }
    exit;
}

$email = $password = "";
$email_err = $password_err = $login_err = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
 
    if(empty(trim($_POST["email"]))){
        $email_err = "Por favor, informe seu email.";
    } else{
        $email = trim($_POST["email"]);
    }
    
    if(empty(trim($_POST["password"]))){
        $password_err = "Por favor, informe sua senha.";
    } else{
        $password = trim($_POST["password"]);
    }
    
    if(empty($email_err) && empty($password_err)){
        $sql = "SELECT id, name, email, password, role FROM users WHERE email = :email";
        
        if($stmt = $pdo->prepare($sql)){
            $stmt->bindParam(":email", $param_email, PDO::PARAM_STR);
            
            $param_email = trim($_POST["email"]);
            
            if($stmt->execute()){
                if($stmt->rowCount() == 1){
                    if($row = $stmt->fetch()){
                        $id = $row["id"];
                        $name = $row["name"];
                        $email = $row["email"];
                        $hashed_password = $row["password"];
                        $role = $row["role"];
                        
                        if(password_verify($password, $hashed_password)){
                            session_start();
                            
                            $_SESSION["loggedin"] = true;
                            $_SESSION["user_id"] = $id;
                            $_SESSION["name"] = $name;
                            $_SESSION["email"] = $email;
                            $_SESSION["role"] = $role;
                            
                            if($role == "admin"){
                                header("location: admin-dashboard.php");
                            } elseif($role == "barber"){
                                header("location: barber-dashboard.php");
                            } else {
                                header("location: index.php");
                            }
                        } else{
                            $login_err = "Email ou senha inválidos.";
                        }
                    }
                } else{
                    $login_err = "Email ou senha inválidos.";
                }
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
    <title>Login - Barbearia Elite</title>
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
                    <li><a href="login.php" class="active"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                    <li><a href="register.php"><i class="fas fa-user-plus"></i> Cadastro</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="page-wrapper">
        <div class="container">
            <div class="form-container animate-in">
                <div class="form-header">
                    <div class="form-icon">
                        <i class="fas fa-sign-in-alt"></i>
                    </div>
                    <h2>Login</h2>
                    <p>Entre com suas credenciais para acessar sua conta</p>
                </div>
                
                <?php 
                if(!empty($login_err)){
                    echo '<div class="alert alert-danger">' . $login_err . '</div>';
                }
                if(isset($_SESSION['register_success'])){
                    echo '<div class="alert alert-success">' . $_SESSION['register_success'] . '</div>';
                    unset($_SESSION['register_success']);
                }
                ?>
                
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="login-form">
                    <div class="form-group <?php echo (!empty($email_err)) ? 'has-error' : ''; ?>">
                        <label for="email"><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" name="email" id="email" class="form-control" value="<?php echo $email; ?>" placeholder="Seu email">
                        <span class="invalid-feedback"><?php echo $email_err; ?></span>
                    </div>    
                    
                    <div class="form-group <?php echo (!empty($password_err)) ? 'has-error' : ''; ?>">
                        <label for="password"><i class="fas fa-lock"></i> Senha</label>
                        <div class="password-container">
                            <input type="password" name="password" id="password" class="form-control" placeholder="Sua senha">
                            <i class="fas fa-eye password-toggle" onclick="togglePassword('password')"></i>
                        </div>
                        <span class="invalid-feedback"><?php echo $password_err; ?></span>
                    </div>
                    
                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="remember">
                            <label class="form-check-label" for="remember">Lembrar de mim</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-sign-in-alt"></i> Entrar
                        </button>
                    </div>
                    
                    <div class="form-footer">
                        <p>Não tem uma conta? <a href="register.php">Cadastre-se</a></p>
                        <p><a href="forgot-password.php">Esqueceu sua senha?</a></p>
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
            const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
            const mainMenu = document.getElementById('main-menu');
            
            if (mobileMenuToggle) {
                mobileMenuToggle.addEventListener('click', function() {
                    mainMenu.classList.toggle('show');
                });
            }
            
            const formGroups = document.querySelectorAll('.form-group');
            
            formGroups.forEach((group, index) => {
                setTimeout(() => {
                    group.classList.add('form-group-animate');
                }, 100 * index);
            });
        });
    </script>
</body>
</html>