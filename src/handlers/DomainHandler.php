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
      ];
    } catch (Exception $e) {
      return [
        'success' => false,
        'error' => $e->getMessage()
      ];
    }
  }

  function getAvailabilityOfDomain($domain) {
    $nameserversAreSet = checkdnsrr($domain, 'NS');

    // If the NS are set then the domain must be registered, so return false
    if ($nameserversAreSet) {
      return [
        'success' => true,
        'isDomainAvailable' => false,
      ];
    }

    // Otherwise, let's check the whois
    return $this->getWhoisForDomain($domain);
  }
}