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
              extensions.extension = :extension';

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
        'name' => $registrar['registrarName'],
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
}