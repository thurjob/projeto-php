<?php
// Configurações do banco de dados
$host = "localhost";
$dbname = "barbershop";
$username = "root";
$password = "";

try {
    // Conectar ao servidor MySQL sem selecionar um banco de dados
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    

    $sql = "CREATE DATABASE IF NOT EXISTS $dbname";
    $pdo->exec($sql);
    

    $pdo->exec("USE $dbname");
    
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        phone VARCHAR(20) NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('client', 'barber', 'admin') NOT NULL DEFAULT 'client',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    
    $sql = "CREATE TABLE IF NOT EXISTS services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        duration INT NOT NULL,
        image VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    
    $sql = "CREATE TABLE IF NOT EXISTS appointments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        barber_id INT NOT NULL,
        service_id INT NOT NULL,
        appointment_date DATE NOT NULL,
        appointment_time TIME NOT NULL,
        status ENUM('pending', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (barber_id) REFERENCES users(id),
        FOREIGN KEY (service_id) REFERENCES services(id)
    )";
    $pdo->exec($sql);
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM services");
    $serviceCount = $stmt->fetchColumn();
    
    if ($serviceCount == 0) {
        $services = [
            [
                'name' => 'Corte de Cabelo',
                'description' => 'Corte tradicional com tesoura e máquina.',
                'price' => 35.00,
                'duration' => 30
            ],
            [
                'name' => 'Barba',
                'description' => 'Aparar e modelar a barba com navalha.',
                'price' => 25.00,
                'duration' => 20
            ],
            [
                'name' => 'Corte + Barba',
                'description' => 'Combo de corte de cabelo e barba.',
                'price' => 55.00,
                'duration' => 45
            ],
            [
                'name' => 'Degradê',
                'description' => 'Corte com técnica de degradê.',
                'price' => 40.00,
                'duration' => 35
            ],
            [
                'name' => 'Coloração',
                'description' => 'Aplicação de tintura para cabelo.',
                'price' => 60.00,
                'duration' => 60
            ],
            [
                'name' => 'Hidratação',
                'description' => 'Tratamento para hidratar cabelo e barba.',
                'price' => 45.00,
                'duration' => 40
            ]
        ];
        
        $stmt = $pdo->prepare("INSERT INTO services (name, description, price, duration) VALUES (?, ?, ?, ?)");
        
        foreach ($services as $service) {
            $stmt->execute([
                $service['name'],
                $service['description'],
                $service['price'],
                $service['duration']
            ]);
        }
    }
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'barber'");
    $barberCount = $stmt->fetchColumn();
    
    if ($barberCount == 0) {
        $barbers = [
            [
                'name' => 'Carlos Silva',
                'email' => 'carlos@barbearia.com',
                'phone' => '(11) 98765-4321',
                'password' => 'barber123'
            ],
            [
                'name' => 'Ricardo Oliveira',
                'email' => 'ricardo@barbearia.com',
                'phone' => '(11) 91234-5678',
                'password' => 'barber123'
            ],
            [
                'name' => 'André Santos',
                'email' => 'andre@barbearia.com',
                'phone' => '(11) 99876-5432',
                'password' => 'barber123'
            ]
        ];
        
        $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, 'barber')");
        
        foreach ($barbers as $barber) {
            $hashedPassword = password_hash($barber['password'], PASSWORD_DEFAULT);
            $stmt->execute([
                $barber['name'],
                $barber['email'],
                $barber['phone'],
                $hashedPassword
            ]);
        }
    }
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $adminCount = $stmt->fetchColumn();
    
    if ($adminCount == 0) {
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, 'admin')");
        $stmt->execute([
            'Administrador',
            'admin@barbearia.com',
            '(11) 99999-9999',
            $hashedPassword
        ]);
    }
    
    header("Location: index.php");
    exit;
    
} catch(PDOException $e) {
    echo "<div style='background-color: #f8d7da; color: #721c24; padding: 15px; margin: 20px 0; border-radius: 5px; text-align: center;'>
            <h3>Erro na configuração do banco de dados</h3>
            <p>Ocorreu um erro ao configurar o banco de dados: " . $e->getMessage() . "</p>
            <p>Verifique se o XAMPP está em execução e se as credenciais do banco de dados estão corretas.</p>
          </div>";
}
?>