<?php
class DomainHandler {
  function getWhoisForDomain($domain) {
    $whois = Iodev\Whois\Factory::get()->createWhois();

    try {
      $isDomainAvailable = $whois->isDomainAvailable($domain);
      $whoisInfo = $whois->lookupDomain($domain);

      return [
        'isDomainAvailable' => $isDomainAvailable,
        'whoisInfo' => $whoisInfo->text
      ];
    } catch (Exception $e) {
      return [
        'isDomainAvailable' => null,
        'whoisInfo' => null,
        'error' => $e->getMessage()
      ];
    }
  }
}