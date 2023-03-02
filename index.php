<?php
require __DIR__ . '/vendor/autoload.php';

require __DIR__ . '/src/connections/DatabaseConnection.php';

require __DIR__ . '/src/handlers/ExtensionsHandler.php';
require __DIR__ . '/src/handlers/DomainHandler.php';
require __DIR__ . '/src/handlers/SearchHandler.php';

require __DIR__ . '/src/helpers/DomainHelper.php';

// Import info from .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Set up router
$router = new \Bramus\Router\Router();

// Set headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('Content-Type: application/json');


/*
 *  Extensions Query
 */
$router->get('/extension/([\w\-.]+)', function($extension) {
  $extension = strtolower($extension);

  $databaseHandler = new DatabaseConnection();
  $connection = $databaseHandler->getConnection();

  $extensionsHandler = new ExtensionsHandler($connection);

  $extensionInfo = $extensionsHandler->getExtensionInfo($extension);

  $databaseHandler->closeConnection();

  die(json_encode($extensionInfo));
});


/*
 *  Domain availability search
 */
$router->get('/availability/([\w\-.]+)', function($domain) {
  $domainHandler = new DomainHandler();

  $whoisInfo = $domainHandler->getWhoisForDomain($domain);

  die(json_encode($whoisInfo));
});


/*
 *  Domain hack search
 */
$router->get('/search/([\w\-.]+)', function($search) {
  $search = strtolower($search);

  $databaseHandler = new DatabaseConnection();
  $connection = $databaseHandler->getConnection();

  $searchHandler = new SearchHandler($connection);

  $searchResults = $searchHandler->getSearchResults($search);

  $databaseHandler->closeConnection();

  die(json_encode([
    'query' => $search,
    'results' => $searchResults
  ]));
});

$router->run();
?>