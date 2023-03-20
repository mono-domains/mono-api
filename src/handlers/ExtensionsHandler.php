<?php
class ExtensionsHandler {
  static $db = '';

  function __construct($connection) {
    $this->db = $connection;
  }

  function getExtensionInfo($extension) {
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

    $stmt = $this->db->prepare($sql);
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

    $stmt = $this->db->prepare($sql);
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
    
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $extensions = $stmt->fetchAll();

    return array_map(function($extension) {
      return $extension['extension'];
    }, $extensions);
  }

  function getExtensionCount() {
    $sql = 'SELECT COUNT(DISTINCT extensionId) as extensionCount
            FROM extension_pricing';

    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $extensionCount = $stmt->fetchAll();

    return $extensionCount[0]['extensionCount'];
  }

  function getAllExtensionPricing() {
    $sql = 'SELECT
              extensions.extension AS extension,
              pricing.registerPrice,
              pricing.renewalPrice,
              pricing.isOnSale
            FROM extension_pricing
            AS pricing
            INNER JOIN extensions
            ON pricing.extensionId = extensions.id
            ORDER BY
              pricing.registerPrice ASC';
    
    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $dbExtensions = $stmt->fetchAll();

    $extensions = [];

    foreach ($dbExtensions as $extension) {
      if (isset($extensions[$extension['extension']])) {
        continue;
      }

      $extensions[$extension['extension']] = [
        'name'          => $extension['extension'],
        'registerPrice' => $extension['registerPrice'],
        'renewalPrice'  => $extension['renewalPrice'],
        'isOnSale'      => (boolean)$extension['isOnSale']
      ];
    }

    return array_values($extensions);
  }

  function getCheapestExtensions() {
    $allExtensions = $this->getAllExtensionPricing();

    return array_slice($allExtensions, 0, 7);
  }
}