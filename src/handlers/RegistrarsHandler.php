<?php
class RegistrarsHandler {
  static $db = '';

  function __construct($connection) {
    $this::$db = $connection;
  }

  function getRegistrarsCount() {
    $sql = 'SELECT COUNT(name) as registrarsCount
            FROM registrars';

    $stmt = $this::$db->prepare($sql);
    $stmt->execute();
    $registrarsCount = $stmt->fetchAll();

    return $registrarsCount[0]['registrarsCount'];
  }

  function getActiveRegistrarsCount() {
    $sql = 'SELECT COUNT(DISTINCT(registrarId)) as activeRegistrarsCount
            FROM extension_pricing';

    $stmt = $this::$db->prepare($sql);
    $stmt->execute();
    $registrarsCount = $stmt->fetchAll();

    return $registrarsCount[0]['activeRegistrarsCount'];
  }

  function getRegistrarExtensionInfo() {
    $sql = 'SELECT
              registrars.name AS registrarName,
              COUNT(extensionId) AS extensionCount,
              lastUpdate
            FROM extension_pricing
            AS pricing
            INNER JOIN registrars
            ON pricing.registrarId = registrars.id
            GROUP BY registrarId';

    $stmt = $this::$db->prepare($sql);
    $stmt->execute();
    $registrarExtensionInfo = $stmt->fetchAll();

    $output = [];

    foreach ($registrarExtensionInfo as $registrar) {
      $output[$registrar['registrarName']] = [
        'extensionCount' => $registrar['extensionCount'],
        'lastUpdate' => $registrar['lastUpdate']
      ];
    }

    return $output;
  }
}