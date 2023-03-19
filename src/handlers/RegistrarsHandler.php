<?php
class RegistrarsHandler {
  static $db = '';

  function __construct($connection) {
    $this->db = $connection;
  }

  function getRegistrarsCount() {
    $sql = 'SELECT COUNT(name) as registrarsCount
            FROM registrars';

    $stmt = $this->db->prepare($sql);
    $stmt->execute();
    $registrarsCount = $stmt->fetchAll();

    return $registrarsCount[0]['registrarsCount'];
  }
}