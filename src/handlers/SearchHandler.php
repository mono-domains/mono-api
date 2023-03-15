<?php
class SearchHandler {
  static $db = '';

  function __construct($connection) {
    $this->db = $connection;
  }

  function getSearchResults($search) {
    $search = $this->getSanitizedSearchString($search);

    // Init results array
    $results = [];

    // Now get the potential extensions from the first string
    $potentialExtensions = $this->getPotentialExtensionsForString($search);

    // We want to first find if there has been any of these extensions searched for specifically
    // Let's use the DomainHelper for that
    $domainHelper = new DomainHelper();

    // We want the longest extension, since any smaller ones will be unavailable for register (e.g .uk vs .co.uk)
    $longestExtension = $domainHelper->getLongestExtensionInDomain($potentialExtensions, $search);

    // So we've found one, let's add it to the results
    if ($longestExtension !== false) {
      // Let's start by getting the position that the extension starts
      $extensionPosition = strrpos($search, $longestExtension);

      // Which will then let us get the bits before
      $searchDomain = substr($search, 0, $extensionPosition);

      // This isn't necessarily the actual registerable bit though, since we only want the first level
      // So let's figure out which bits are which
      $domainLevels = $domainHelper->getDomainLevelsForString($searchDomain);

      // Get the extension info
      $extensionInfo = $this->getExtensionInfo($longestExtension);

      // Cool, so now we've got all that info we can add it to the results
      $results[] = [
        'extension' => $extensionInfo,
        'domain' => $domainLevels['domain'],
        'subdomains' => $domainLevels['subdomains']
      ];

      // Once we've got this info too, we can update the search (since we don't want to do hack searches on the extension)
      $search = $domainLevels['subdomains'] . $domainLevels['domain'];

      // However just before we continue, let's do one last search for the domain with the extension flattened
      $flattenedExtension = str_replace('.', '', $longestExtension);
      $flattenedDomainHacks = $this->getDomainHacksForString($domainLevels['subdomains'] . $domainLevels['domain'] . $flattenedExtension);

      // If we've found any domain hacks, let's combine them with the existing ones 
      if (!empty($flattenedDomainHacks)) {
        $results = $this->combineDomainHackResultArrays($results, $flattenedDomainHacks);
      }
    }

    // So now that we've here we've either dealt with the extensions in the search or there aren't any
    // In the case that there aren't any, we need to determine the domain levels and udpate the search
    if (empty($domainLevels)) {
      $domainLevels = $domainHelper->getDomainLevelsForString($search);

      $search = $domainLevels['subdomains'] . $domainLevels['domain'];
    }

    // Now we can do a hack search on the domain itself to find anything further
    $domainHacks = $this->getDomainHacksForString($search);

    $results = $this->combineDomainHackResultArrays($results, $domainHacks);

    // So we've finished all the hack searches, let's just add on some generic extensions and call it a day
    $genericExtensions = ['.com', '.net', '.org', '.co', '.io'];

    $genericExtensionResults = array_map(function ($extension) use ($domainLevels) {
      $extensionInfo = $this->getExtensionInfo($extension);

      return [
        'extension' => $extensionInfo,
        'domain' => $domainLevels['domain'],
        'subdomains' => $domainLevels['subdomains']
      ];
    }, $genericExtensions);

    $results = $this->combineDomainHackResultArrays($results, $genericExtensionResults);

    return $results;
  }

  function getSanitizedSearchString($string) {
    $string = strtolower($string);
    $string = trim($string, ".");

    return $string;
  }

  function getPotentialExtensionsForString($string) {
    // Get last two characters of search
    $stringSuffix = substr($string, -2);

    // Now init the ExtensionHandler
    $extensionHandler = new ExtensionsHandler($this->db);

    // And find extensions ending with those characters
    return $extensionHandler->getExtensionsFromSuffix($stringSuffix);
  }

  function getExtensionInfo($extension) {
    // Init the ExtensionHandler
    $extensionsHandler = new ExtensionsHandler($this->db);

    // Get the extension info
    $extensionInfo = $extensionsHandler->getExtensionInfo($extension);

    // Since this is originally used for an API call, we need to update some of the information
    unset($extensionInfo['success']);

    return $extensionInfo;
  }

  function getDomainHacksForString($string) {
    // We don't want to search for domain hacks if the user has passed in a punycoded search
    // So let's just check for that and return nothing if so
    if (substr($string, 0, 4) === 'xn--') {
      return [];
    }

    // Init results array
    $results = [];

    // Let's get the levels for the inputted string
    $domainHelper = new DomainHelper();
    $domainLevels = $domainHelper->getDomainLevelsForString($string);

    $domain = $domainLevels['domain'];

    // Start by getting the potential extensions for the domain
    $potentialExtensions = $this->getPotentialExtensionsForString($domain);

    // Then sort them so the longest (most relevant) ones are first
    usort($potentialExtensions, function($a, $b) {
      return strlen($b) - strlen($a);
    });

    // Now we need to flatten all of these extensions and search through them to see if any fit
    foreach ($potentialExtensions as $extension) {
      $flattenedExtension = str_replace('.', '', $extension);

      $extensionPosition = strrpos($domain, $flattenedExtension);

      if ($extensionPosition !== false && $extensionPosition > 0) {
        $hackedDomain = substr($domain, 0, $extensionPosition);

        // If the resulting domain is invalid, we shouldn't add it to the results
        if (!$domainHelper->isValidDomain($hackedDomain)) {
          continue;
        }

        $extensionInfo = $this->getExtensionInfo($extension);

        // Otherwise, add it in
        $results[] = [
          'extension' => $extensionInfo,
          'domain' => $hackedDomain,
          'subdomains' => $domainLevels['subdomains']
        ];
      }
    }

    return $results;
  }

  function combineDomainHackResultArrays($currentArray, $newArray) {
    $combinedArray = $currentArray;

    // Let's go through the new array to be added and check each item for duplicates
    foreach ($newArray as $hack) {
      $existingItem = array_filter($combinedArray, function ($item) use ($hack) {
        return $item['extension'] === $hack['extension'] && $item['domain'] === $hack['domain'];
      });

      // There isn't a duplicate, so we can add it in to the new array
      if (empty($existingItem)) {
        $combinedArray[] = $hack;
      }
    }

    return $combinedArray;
  }
}