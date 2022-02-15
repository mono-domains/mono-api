<?php
require __DIR__ . '/vendor/autoload.php';

require __DIR__ . '/src/Database.php';
require __DIR__ . '/src/Extensions.php';

// Import info from .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Set up router
$router = new \Bramus\Router\Router();

// Extension query
$router->get('/extension/(\w+)', function($extension) {
  $databaseHandler = new Database();
  $connection = $databaseHandler->getConnection();

  $extensionsHandler = new Extensions($connection);

  $extensionInfo = $extensionsHandler->getExtensionInfo($extension);

  $databaseHandler->closeConnection();

  die(json_encode($extensionInfo));
});

$router->run();

// Test DB Call
// $databaseHandler = new Database();
// $connection = $databaseHandler->getConnection();

// $stmt = $connection->prepare('SELECT name FROM registrars WHERE id = :id');
// $stmt->execute([':id' => 1]);
// $output = $stmt->fetchAll();

// var_dump($output);

// echo "DB name is {$_ENV['DB_NAME']}";
?>