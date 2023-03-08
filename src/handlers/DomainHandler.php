<?php
class DomainHandler {
  function getWhoisForDomain($domain) {
    $whois = Iodev\Whois\Factory::get()->createWhois();

    try {
      $isDomainAvailable = $whois->isDomainAvailable($domain);
      $whoisInfo = $whois->lookupDomain($domain);

      return [
        'success' => true,
        'isDomainAvailable' => $isDomainAvailable,
        'whoisInfo' => $whoisInfo->text
      ];
    } catch (Exception $e) {
      return [
        'success' => false,
        'error' => $e->getMessage()
      ];
    }
  }
}