<?php
require __DIR__ . '/vendor/autoload.php';

require __DIR__ . '/src/connections/DatabaseConnection.php';

require __DIR__ . '/src/handlers/ExtensionsHandler.php';
require __DIR__ . '/src/handlers/DomainHandler.php';
require __DIR__ . '/src/handlers/SearchHandler.php';
require __DIR__ . '/src/handlers/RegistrarsHandler.php';

require __DIR__ . '/src/helpers/DomainHelper.php';

// Import info from .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

if ($_ENV['SHOW_ERRORS']) {
  ini_set('display_errors', '1');
  ini_set('display_startup_errors', '1');
  error_reporting(E_ALL);
} else {
  ini_set('display_errors', '0');
  ini_set('display_startup_errors', '0');
  error_reporting(0);
}

// Set up router
$router = new \Bramus\Router\Router();

// Get CORS request origin
$origin = $_SERVER['HTTP_ORIGIN'];

// Set the whitelisted origins
$whitelistedOrigins = [
  'local' => 'http://localhost:3000',
  'live' => 'https://mono.domains'
];

// Set headers
if ($origin === $whitelistedOrigins['local']) {
  header('Access-Control-Allow-Origin: ' . $whitelistedOrigins['local']);
} else {
  header('Access-Control-Allow-Origin: ' . $whitelistedOrigins['live']);
}
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type');
header('Content-Type: application/json');

/*
 * CORS Options request
 */
$router->options('.*', function() {
  if (!in_array($origin, $whitelistedOrigins, true)) {
    http_response_code(403);

    return;
  }

  http_response_code(200);
});


/*
 * Status query
 */
$router->get('/status', function() {
  $databaseHandler = new DatabaseConnection();
  $connection = $databaseHandler->getConnection();

  $extensionsHandler = new ExtensionsHandler($connection);
  $registrarsHandler = new RegistrarsHandler($connection);

  $extensionsCount = $extensionsHandler->getExtensionCount();
  $registrarsCount = $registrarsHandler->getRegistrarsCount();
  $registrarExtensionInfo = $registrarsHandler->getRegistrarExtensionInfo();

  $databaseHandler->closeConnection();

  die(json_encode([
    'scrapedRegistrars' => count($registrarExtensionInfo) . '/' . $registrarsCount,
    'extensionsCount' => $extensionsCount,
    'registrarInfo' => $registrarExtensionInfo
  ]));
});


/*
 *  All extensions query
 */
$router->get('/extension/all', function() {
  $databaseHandler = new DatabaseConnection();
  $connection = $databaseHandler->getConnection();

  $extensionsHandler = new ExtensionsHandler($connection);

  $extensionPricing = $extensionsHandler->getAllExtensionPricing();

  $databaseHandler->closeConnection();

  die(json_encode([
    'success' => true,
    'results' => $extensionPricing
  ]));
});

/*
 *  Extensions query
 */
$router->get('/extension/([a-zA-Z0-9\-.]+)', function($extension) {
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
$router->get('/availability/([a-zA-Z0-9\-.]+)', function($domain) {
  $domainHandler = new DomainHandler();

  $whoisInfo = $domainHandler->getAvailabilityOfDomain($domain);

  die(json_encode($whoisInfo));
});


/*
 *  Domain hack search
 */
$router->get('/search/([a-zA-Z0-9\-.]+)', function($search) {
  $databaseHandler = new DatabaseConnection();
  $connection = $databaseHandler->getConnection();

  $searchHandler = new SearchHandler($connection);

  $searchResults = $searchHandler->getSearchResults($search);

  $databaseHandler->closeConnection();

  die(json_encode([
    'success' => true,
    'results' => $searchResults
  ]));
});


/*
 *  Homepage stats
 */
$router->get('/homepageStats', function() {
  $databaseHandler = new DatabaseConnection();
  $connection = $databaseHandler->getConnection();

  // First off we want to get the pricing results for a .com domain
  $extensionsHandler = new ExtensionsHandler($connection);

  $comInfo = $extensionsHandler->getExtensionInfo('.com');
  $comRegistrars = array_slice($comInfo['registrars'], 0, 3);

  // Next off we want to get an example domain hack search, e.g. 'cheapcars'
  $searchHandler = new SearchHandler($connection);

  $searchResults = $searchHandler->getSearchResults('cheapcars');
  $domainHacks = array_slice($searchResults, 0, 3);

  // Now we wanna get the first and last extension in the db
  $firstAndLastTLD = $extensionsHandler->getFirstAndLastTLD();

  // After that we can get the total number of extensions and registrars
  $extensionsCount = $extensionsHandler->getExtensionCount();

  $registrarsHandler = new RegistrarsHandler($connection);

  $registrarsCount = $registrarsHandler->getActiveRegistrarsCount();

  // Then we want to get the cheapest extensions currently available
  $cheapestExtensions = $extensionsHandler->getCheapestExtensions();

  // Now we've got all that, close the connection and spit it out
  $databaseHandler->closeConnection();

  die(json_encode([
    'success' => true,
    'comRegistrars' => $comRegistrars,
    'domainHacks' => $domainHacks,
    'firstAndLastTLD' => $firstAndLastTLD,
    'extensionsCount' => $extensionsCount,
    'registrarsCount' => $registrarsCount,
    'cheapestExtensions' => $cheapestExtensions
  ]));
});

$router->run();
?>