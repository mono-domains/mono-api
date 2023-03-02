<?php
class DomainHandler {
  function getWhoisForDomain($domain) {
    $whois = Iodev\Whois\Factory::get()->createWhois();

    $isDomainAvailable = $whois->isDomainAvailable($domain);
    $whoisInfo = $whois->lookupDomain($domain);

    return [
      'isDomainAvailable' => $isDomainAvailable,
      'whoisInfo' => $whoisInfo->text
    ];
  }
}