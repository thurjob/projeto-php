<?php
require_once 'config.php';
session_start();

try {
    $pdo->query("SELECT 1 FROM services LIMIT 1");
} catch(PDOException $e) {
    header("Location: setup.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barbearia Elite - Estilo e Precisão</title>
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
                    <li><a href="index.php" class="active"><i class="fas fa-home"></i> Início</a></li>
                    <li><a href="services.php"><i class="fas fa-list"></i> Serviços</a></li>
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

    <section class="hero">
        <div class="container">
            <h2>Estilo e Precisão</h2>
            <p>Transforme seu visual com os melhores profissionais da cidade. Cortes modernos e atendimento de primeira.</p>
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="appointment.php" class="btn btn-primary">
                    <i class="fas fa-calendar-alt"></i> Agende seu Horário
                </a>
            <?php else: ?>
                <a href="login.php" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Faça Login para Agendar
                </a>
            <?php endif; ?>
        </div>
    </section>

    <section class="features">
        <div class="container">
            <div class="feature">
                <i class="fas fa-cut"></i>
                <h3>Profissionais Qualificados</h3>
                <p>Nossa equipe é formada por barbeiros com anos de experiência e constante atualização nas tendências.</p>
            </div>
            <div class="feature">
                <i class="fas fa-calendar-check"></i>
                <h3>Agendamento Online</h3>
                <p>Marque seu horário de forma rápida e prática pelo nosso sistema online, sem complicações.</p>
            </div>
            <div class="feature">
                <i class="fas fa-clock"></i>
                <h3>Pontualidade</h3>
                <p>Respeitamos seu tempo com atendimento pontual e eficiente, sem longas esperas.</p>
            </div>
        </div>
    </section>

    <section class="services-preview">
        <div class="container">
            <h2 class="section-title"><i class="fas fa-list"></i> Nossos Serviços</h2>
            
            <div class="services-grid">
                <?php
                $sql = "SELECT * FROM services ORDER BY price DESC LIMIT 3";
                $stmt = $pdo->query($sql);
                $services = $stmt->fetchAll();
                
                foreach($services as $service):
                ?>
                <div class="service-card">
                    <div class="service-icon">
                        <i class="fas fa-cut"></i>
                    </div>
                    <h3><?php echo $service['name']; ?></h3>
                    <div class="service-price">R$ <?php echo number_format($service['price'], 2, ',', '.'); ?></div>
                    <p class="service-description"><?php echo $service['description']; ?></p>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <a href="appointment.php?service=<?php echo $service['id']; ?>" class="btn">
                            <i class="fas fa-calendar-plus"></i> Agendar
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="btn">
                            <i class="fas fa-sign-in-alt"></i> Login para Agendar
                        </a>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center" style="margin-top: 2rem; text-align: center;">
                <a href="services.php" class="btn btn-outline">
                    <i class="fas fa-list"></i> Ver Todos os Serviços
                </a>
            </div>
        </div>
    </section>

    <section class="barbers">
        <div class="container">
            <h2 class="section-title"><i class="fas fa-user-tie"></i> Nossos Barbeiros</h2>
            
            <div class="barbers-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 2rem; margin: 2rem 0;">
                <?php
                $sql = "SELECT id, name FROM users WHERE role = 'barber' ORDER BY name LIMIT 4";
                $stmt = $pdo->query($sql);
                $barbers = $stmt->fetchAll();
                
                foreach($barbers as $barber):
                ?>
                <div class="barber-card" style="background-color: #fff; border-radius: 5px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); overflow: hidden; transition: all 0.3s ease; text-align: center;">
                    <div class="barber-image" style="height: 200px; background-color: #f8f8f8; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-user-circle" style="font-size: 5rem; color: #ddd;"></i>
                    </div>
                    <div class="barber-info" style="padding: 1.5rem;">
                        <h3><?php echo $barber['name']; ?></h3>
                        <p style="color: #666; margin-bottom: 1.5rem;">Especialista em cortes masculinos</p>
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <a href="appointment.php?barber=<?php echo $barber['id']; ?>" class="btn btn-outline">
                                <i class="fas fa-calendar-plus"></i> Agendar com este Barbeiro
                            </a>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-outline">
                                <i class="fas fa-sign-in-alt"></i> Login para Agendar
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="testimonials" style="background-color: #f8f8f8; padding: 4rem 0;">
        <div class="container">
            <h2 class="section-title"><i class="fas fa-comments"></i> O que Nossos Clientes Dizem</h2>
            
            <div class="testimonials-slider" style="margin: 2rem 0; position: relative;">
                <div class="testimonial-card" style="background-color: #fff; border-radius: 5px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); padding: 2rem; text-align: center; max-width: 800px; margin: 0 auto;">
                    <div class="testimonial-rating" style="color: #f8c300; font-size: 1.5rem; margin-bottom: 1rem;">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="testimonial-text" style="font-style: italic; margin-bottom: 1.5rem; color: #666; font-size: 1.1rem;">
                        "Melhor barbearia da cidade! Atendimento de primeira, ambiente agradável e o resultado sempre supera minhas expectativas. Recomendo a todos!"
                    </p>
                    <div class="testimonial-author">
                        <h4 style="margin-bottom: 0.25rem;">João Silva</h4>
                        <p style="color: #999;">Cliente desde 2022</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="cta" style="background: linear-gradient(rgba(0, 0, 0, 0.8), rgba(0, 0, 0, 0.8)), url('img/barbershop-bg.jpg'); background-size: cover; background-position: center; color: #fff; text-align: center; padding: 5rem 0;">
        <div class="container">
            <h2 style="font-size: 2.5rem; margin-bottom: 1rem;">Pronto para um Novo Visual?</h2>
            <p style="font-size: 1.2rem; margin-bottom: 2rem; max-width: 800px; margin-left: auto; margin-right: auto;">
                Agende seu horário agora e experimente o melhor serviço de barbearia da cidade.
            </p>
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="appointment.php" class="btn btn-primary" style="font-size: 1.1rem; padding: 1rem 2rem;">
                    <i class="fas fa-calendar-alt"></i> Agendar Agora
                </a>
            <?php else: ?>
                <a href="register.php" class="btn btn-primary" style="font-size: 1.1rem; padding: 1rem 2rem;">
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
            const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
            const mainMenu = document.getElementById('main-menu');
            
            if (mobileMenuToggle) {
                mobileMenuToggle.addEventListener('click', function() {
                    mainMenu.classList.toggle('show');
                });
            }
            
            const animateOnScroll = function() {
                const elements = document.querySelectorAll('.feature, .service-card, .barber-card');
                
                elements.forEach(element => {
                    const elementPosition = element.getBoundingClientRect().top;
                    const screenPosition = window.innerHeight / 1.3;
                    
                    if(elementPosition < screenPosition) {
                        element.style.opacity = '1';
                        element.style.transform = 'translateY(0)';
                    }
                });
            };
            
            const elements = document.querySelectorAll('.feature, .service-card, .barber-card');
            elements.forEach(element => {
                element.style.opacity = '0';
                element.style.transform = 'translateY(20px)';
                element.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            });
            
            animateOnScroll();
            window.addEventListener('scroll', animateOnScroll);
        });
    </script>
</body>
</html>