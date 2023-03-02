<?php
class DomainHelper {
  function getLongestExtensionInDomain($extensions, $domain) {
    // Let's first sort the extensions array by extension length
    usort($extensions, function($a, $b) {
      return strlen($b) - strlen($a);
    });

    // Now we can loop over those extensions to find the first one entirely included
    foreach ($extensions as $extension) {
      $extensionLength = strlen($extension);

      if (substr_compare($domain, $extension, -$extensionLength, $extensionLength) === 0) {
        // We've found it, so let's just return it
        return $extension;
      }
    }

    // We didn't find anything matching
    return false;
  }

  function getDomainLevelsForString($string) {
    // We wanna first split this up by dots, since they delimit each domain level
    $explodedString = explode('.', $string);

    // Because of the nature of domains we can determine that the last bit will always be the domain
    $domain = end($explodedString);

    // So now to get the subdomains, we just have to find that string's position
    $domainPosition = strpos($string, $domain);

    // And then substr everything before
    $subdomains = substr($string, 0, $domainPosition);

    return [
      'domain' => $domain,
      'subdomains' => $subdomains
    ];
  }

  function isValidDomain($domain) {
    // When calculating domain hacks we have to know whether the domain is valid or not

    // For now, we can do that by just checking that the first and last characters are ctype_alnum
    if (ctype_alnum(substr($domain, 0, 1)) && ctype_alnum(substr($domain, -1))) {
      return true;
    }

    return false;
  }
}