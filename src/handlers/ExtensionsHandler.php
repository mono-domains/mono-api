<?php
class ExtensionsHandler {
  static $db = '';

  function __construct($connection) {
    $this::$db = $connection;
  }

  function getExtensionInfo($extension) {
    if (substr($extension, 0, 1) !== '.') {
      $extension = '.' . $extension;
    }

    $sql = 'SELECT
              registrars.name AS registrarName,
              pricing.registerPrice,
              pricing.renewalPrice,
              pricing.url AS registerUrl,
              pricing.isOnSale
            FROM extension_pricing
            AS pricing
            INNER JOIN extensions
            ON pricing.extensionId = extensions.id
            INNER JOIN registrars
            ON pricing.registrarId = registrars.id
            WHERE
              extensions.extension = :extension
            ORDER BY
              pricing.registerPrice ASC';

    $stmt = $this::$db->prepare($sql);
    $stmt->execute([':extension' => $extension]);
    $extensionInfo = $stmt->fetchAll();

    if (empty($extensionInfo)) {
      return [
        'success' => false,
        'error'   => 'Extension not found'
      ];
    }

    $outputArray = [
      'success'     => true,
      'extension'   => $extension,
      'registrars'  => []
    ];

    foreach ($extensionInfo as $registrar) {
      $outputArray['registrars'][] = [
        'name'          => $registrar['registrarName'],
        'registerPrice' => $registrar['registerPrice'],
        'renewalPrice'  => $registrar['renewalPrice'],
        'registerUrl'   => $registrar['registerUrl'],
        'isOnSale'      => (boolean)$registrar['isOnSale']
      ];
    }

    return $outputArray;
  }

  function getExtensionsFromSuffix($suffix) {
    $sql = 'SELECT extension
            FROM extensions
            WHERE SUBSTRING(extension, -2, 2) = :suffix';

    $stmt = $this::$db->prepare($sql);
    $stmt->execute([
      ':suffix' => $suffix
    ]);
    $extensions = $stmt->fetchAll();

    // Clean up the array, since we're only fetching one thing
    return array_map(function($extension) {
      return $extension['extension'];
    }, $extensions);
  }

  function getFirstAndLastTLD() {
    // I want to fetch the first and last TLD in the extensions table
    // This is used for the homepage, and doesn't include .xx.xx domains

    $sql = '(SELECT extension
            FROM extensions
            WHERE SUBSTRING_INDEX(extension, ".", 2) = extension
            ORDER BY extension ASC
            LIMIT 1)
            UNION ALL
            (SELECT extension
            FROM extensions
            WHERE SUBSTRING_INDEX(extension, ".", 2) = extension
            ORDER BY extension DESC
            LIMIT 1)';
    
    $stmt = $this::$db->prepare($sql);
    $stmt->execute();
    $extensions = $stmt->fetchAll();

    return array_map(function($extension) {
      return $extension['extension'];
    }, $extensions);
  }

  function getExtensionCount() {
    $sql = 'SELECT COUNT(DISTINCT extensionId) as extensionCount
            FROM extension_pricing';

    $stmt = $this::$db->prepare($sql);
    $stmt->execute();
    $extensionCount = $stmt->fetchAll();

    return $extensionCount[0]['extensionCount'];
  }

  function getAllExtensionPricing() {
    $sql = 'SELECT
              extensions.extension AS extensionName,
              registrars.name AS registrarName,
              pricing.registerPrice,
              pricing.renewalPrice,
              pricing.url AS registerUrl,
              pricing.isOnSale
            FROM extension_pricing
            AS pricing
            INNER JOIN extensions
            ON pricing.extensionId = extensions.id
            INNER JOIN registrars
            ON pricing.registrarId = registrars.id
            ORDER BY
              pricing.registerPrice ASC';
    
    $stmt = $this::$db->prepare($sql);
    $stmt->execute();
    $dbExtensions = $stmt->fetchAll();

    // Set the # category first so it's at the top of the list
    $extensions = ['#' => []];

    foreach ($dbExtensions as $extension) {
      // First let's figure out which category this extension is in
      $category = substr($extension['extensionName'], 1, 1);

      // If it's a number domain, or an IDN, add it to the # category
      if (!ctype_alpha($category) || substr($extension['extensionName'], 0, 5) === '.xn--') {
        $category = '#';
      }

      // Create the category if it doesn't exist
      if (!isset($extensions[$category])) {
        $extensions[$category] = [];
      }

      // Now push the row's information to the array
      $registrarArray = [
        'name' => $extension['registrarName'],
        'registerPrice' => $extension['registerPrice'],
        'renewalPrice' => $extension['renewalPrice'],
        'registerUrl' => $extension['registerUrl'],
        'isOnSale' => $extension['isOnSale']
      ];

      if (isset($extensions[$category][$extension['extensionName']])) {
        $extensions[$category][$extension['extensionName']]['registrars'][] = $registrarArray;
      } else {
        $extensions[$category][$extension['extensionName']] = [
          'extension' => $extension['extensionName'],
          'registrars' => [$registrarArray]
        ];
      }
    }

    ksort($extensions);

    return $extensions;
  }

  function getCheapestExtensions() {
    $sql = 'SELECT
              extensions.extension AS extensionName,
              pricing.registerPrice,
              pricing.renewalPrice,
              pricing.isOnSale
            FROM extension_pricing
            AS pricing
            INNER JOIN extensions
            ON pricing.extensionId = extensions.id
            INNER JOIN registrars
            ON pricing.registrarId = registrars.id
            ORDER BY
              pricing.registerPrice ASC
            LIMIT 7';

    $stmt = $this::$db->prepare($sql);
    $stmt->execute();
    $dbCheapestExtensions = $stmt->fetchAll();

    $cheapestExtensions = [];

    foreach ($dbCheapestExtensions as $extension) {
      $cheapestExtensions[] = [
        'name' => $extension['extensionName'],
        'registerPrice' => $extension['registerPrice'],
        'renewalPrice' => $extension['renewalPrice'],
        'isOnSale' => (boolean)$extension['isOnSale']
      ];
    }

    return $cheapestExtensions;
  }
}