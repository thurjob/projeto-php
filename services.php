<?php
require_once 'config.php';
session_start();

try {
    $pdo->query("SELECT 1 FROM services LIMIT 1");
} catch(PDOException $e) {
    header("Location: setup.php");
    exit;
}

// Obter todos os serviços
$sql = "SELECT * FROM services ORDER BY price ASC";
$stmt = $pdo->query($sql);
$services = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nossos Serviços - Barbearia Elite</title>
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
                    <li><a href="services.php" class="active"><i class="fas fa-list"></i> Serviços</a></li>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li><a href="appointment.php"><i class="fas fa-calendar-alt"></i> Agendar</a></li>
                        <li><a href="my-appointments.php"><i class="fas fa-clock"></i> Meus Horários</a></li>
                        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a></li>
                    <?php else: ?>
                        <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                        <li><a href="register.php"><i class="fas fa-user-plus"></i> Cadastro</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <div class="page-wrapper">
        <div class="container">
            <h2 class="section-title"><i class="fas fa-list"></i> Nossos Serviços</h2>
            <p class="text-center mb-4">Oferecemos uma ampla gama de serviços profissionais de barbearia para manter você com o melhor visual.</p>
            
            <div class="services-grid">
                <?php foreach($services as $service): ?>
                <div class="service-card">
                    <div class="service-icon">
                        <?php
                        switch(strtolower($service['name'])) {
                            case 'corte de cabelo':
                                echo '<i class="fas fa-cut"></i>';
                                break;
                            case 'barba':
                                echo '<i class="fas fa-user-tie"></i>';
                                break;
                            case 'corte + barba':
                                echo '<i class="fas fa-user-alt"></i>';
                                break;
                            case 'degradê':
                                echo '<i class="fas fa-sort"></i>';
                                break;
                            case 'coloração':
                                echo '<i class="fas fa-palette"></i>';
                                break;
                            case 'hidratação':
                                echo '<i class="fas fa-tint"></i>';
                                break;
                            default:
                                echo '<i class="fas fa-cut"></i>';
                        }
                        ?>
                    </div>
                    <h3><?php echo $service['name']; ?></h3>
                    <div class="service-price">R$ <?php echo number_format($service['price'], 2, ',', '.'); ?></div>
                    <p class="service-description"><?php echo $service['description']; ?></p>
                    <p class="mb-2"><i class="fas fa-clock"></i> Duração: <?php echo $service['duration']; ?> minutos</p>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <a href="appointment.php?service=<?php echo $service['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-calendar-plus"></i> Agendar Agora
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline">
                            <i class="fas fa-sign-in-alt"></i> Login para Agendar
                        </a>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="additional-info mt-5">
                <h3 class="mb-3"><i class="fas fa-info-circle"></i> Informações Adicionais</h3>
                <div class="info-cards" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
                    <div class="info-card p-3 bg-white rounded shadow">
                        <h4><i class="fas fa-clock text-primary"></i> Horários de Atendimento</h4>
                        <ul style="list-style: none; padding-left: 0;">
                            <li><strong>Segunda a Sexta:</strong> 9h às 20h</li>
                            <li><strong>Sábado:</strong> 9h às 18h</li>
                            <li><strong>Domingo:</strong> Fechado</li>
                        </ul>
                    </div>
                    
                    <div class="info-card p-3 bg-white rounded shadow">
                        <h4><i class="fas fa-credit-card text-primary"></i> Formas de Pagamento</h4>
                        <ul style="list-style: none; padding-left: 0;">
                            <li><i class="far fa-money-bill-alt"></i> Dinheiro</li>
                            <li><i class="fas fa-credit-card"></i> Cartão de Crédito</li>
                            <li><i class="fas fa-credit-card"></i> Cartão de Débito</li>
                            <li><i class="fas fa-mobile-alt"></i> PIX</li>
                        </ul>
                    </div>
                    
                    <div class="info-card p-3 bg-white rounded shadow">
                        <h4><i class="fas fa-gift text-primary"></i> Promoções</h4>
                        <p>Temos promoções especiais para clientes frequentes e pacotes para noivos e formandos. Entre em contato para mais informações.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <section class="cta">
        <div class="container">
            <h2>Pronto para um Novo Visual?</h2>
            <p>Agende seu horário agora e experimente o melhor serviço de barbearia da cidade.</p>
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="appointment.php" class="btn btn-primary">
                    <i class="fas fa-calendar-alt"></i> Agendar Agora
                </a>
            <?php else: ?>
                <a href="register.php" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Criar Conta
                </a>
            <?php endif; ?>
        </div>
    </section>

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
            const animateOnScroll = function() {
                const elements = document.querySelectorAll('.service-card, .info-card');
                
                elements.forEach(element => {
                    const elementPosition = element.getBoundingClientRect().top;
                    const screenPosition = window.innerHeight / 1.3;
                    
                    if(elementPosition < screenPosition) {
                        element.classList.add('animate');
                    }
                });
            };
            
            const elements = document.querySelectorAll('.service-card, .info-card');
            elements.forEach(element => {
                element.style.opacity = '0';
                element.style.transform = 'translateY(20px)';
                element.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            });
            
            const addAnimateClass = function() {
                elements.forEach((element, index) => {
                    setTimeout(() => {
                        element.style.opacity = '1';
                        element.style.transform = 'translateY(0)';
                    }, 100 * index);
                });
            };
            
            addAnimateClass();
            window.addEventListener('scroll', animateOnScroll);
        });
    </script>
</body>
</html>