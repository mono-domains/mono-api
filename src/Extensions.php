<?php
class Extensions {
  static $db = '';

  function __construct($connection) {
    $this->db = $connection;
  }

  function getExtensionInfo($extension) {
    $sql = "SELECT
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
              extensions.extension = :extension";

    $stmt = $this->db->prepare($sql);
    $stmt->execute([':extension' => ".{$extension}"]);
    $extensionInfo = $stmt->fetchAll();

    if (empty($extensionInfo)) {
      return [
        'success' => false,
        'error'   => 'Extension not found in DB'
      ];
    }

    $outputArray = [
      'success'     => true,
      'extension'   => ".{$extension}",
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
}