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
$router->get('/extension/([\w-]+)', function($extension) {
  $extension = strtolower($extension);

  if (!ctype_alpha($extension) && substr($extension, 0, 4) !== 'xn--') {
    die(json_encode([
      'success' => false,
      'error' => 'Invalid extension'
    ]));
  }

  $databaseHandler = new Database();
  $connection = $databaseHandler->getConnection();

  $extensionsHandler = new Extensions($connection);

  $extensionInfo = $extensionsHandler->getExtensionInfo($extension);

  $databaseHandler->closeConnection();

  die(json_encode($extensionInfo));
});

// Domain hack search
$router->get('/search/([\w-]+)', function($domain) {
  die('hack search');
});

$router->run();
?>