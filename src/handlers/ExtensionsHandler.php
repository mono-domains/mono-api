<?php
class ExtensionsHandler {
  static $db = '';

  function __construct($connection) {
    $this->db = $connection;
  }

  function getExtensionInfo($extension) {
    $sql = 'SELECT
              registrars.name AS registrar_name,
              pricing.register_price,
              pricing.renewal_price,
              pricing.url AS register_url
            FROM extension_pricing
            AS pricing
            INNER JOIN extensions
            ON pricing.extension_id = extensions.id
            INNER JOIN registrars
            ON pricing.registrar_id = registrars.id
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
        'registrarName' => $registrar['registrar_name'],
        'registerPrice' => $registrar['register_price'],
        'renewalPrice'  => $registrar['renewal_price'],
        'registerUrl'   => $registrar['register_url']
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