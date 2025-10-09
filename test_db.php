<?php
// Dados de conexão - exatamente como definimos no docker-compose.yml
$host = 'db'; // IMPORTANTE: O host é o nome do serviço do banco de dados!
$dbname = 'meu_banco';
$user = 'user';
$password = 'user_password';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $password);
    echo "<h1>Conexão com o banco de dados '{$dbname}' realizada com sucesso!</h1>";
} catch (PDOException $e) {
    echo "<h1>Falha na conexão:</h1><p>" . $e->getMessage() . "</p>";
}
?>
