<?php
$host = "localhost";
$dbname = "barbearia";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbname`");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
        `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `email` VARCHAR(100) NOT NULL UNIQUE,
        `phone` VARCHAR(20) NOT NULL,
        `password` VARCHAR(255) NOT NULL,
        `role` VARCHAR(20) NOT NULL DEFAULT 'client',
        `bio` TEXT DEFAULT NULL,
        `specialties` TEXT DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS `services` (
        `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `description` TEXT DEFAULT NULL,
        `price` DECIMAL(10,2) NOT NULL,
        `duration` INT NOT NULL COMMENT 'Duração em minutos',
        `image` VARCHAR(255) DEFAULT NULL,
        `active` TINYINT(1) NOT NULL DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS `appointments` (
        `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `barber_id` INT NOT NULL,
        `service_id` INT NOT NULL,
        `appointment_date` DATE NOT NULL,
        `appointment_time` TIME NOT NULL,
        `status` VARCHAR(20) NOT NULL DEFAULT 'scheduled',
        `notes` TEXT DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
        FOREIGN KEY (`barber_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
        FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS `available_hours` (
        `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `barber_id` INT NOT NULL,
        `day_of_week` INT NOT NULL COMMENT '0=Domingo, 1=Segunda, ..., 6=Sábado',
        `start_time` TIME NOT NULL,
        `end_time` TIME NOT NULL,
        `is_available` TINYINT(1) NOT NULL DEFAULT 1,
        FOREIGN KEY (`barber_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM services");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO `services` (`name`, `description`, `price`, `duration`, `active`) VALUES
            ('Corte Masculino', 'Corte tradicional com tesoura e máquina', 35.00, 30, 1),
            ('Barba', 'Modelagem e hidratação da barba', 25.00, 20, 1),
            ('Corte + Barba', 'Combo de corte masculino e barba', 55.00, 50, 1),
            ('Degradê', 'Corte com técnica de degradê', 40.00, 40, 1),
            ('Hidratação', 'Tratamento de hidratação para cabelos', 45.00, 30, 1)");
    }
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $admin_password = password_hash("admin123", PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO `users` (`name`, `email`, `phone`, `password`, `role`) VALUES
            ('Administrador', 'admin@barbearia.com', '(11) 99999-9999', '$admin_password', 'admin')");
    }
    
    echo "Banco de dados e tabelas criados com sucesso!";
    
} catch(PDOException $e) {
    die("Erro na criação do banco de dados: " . $e->getMessage());
}
?>