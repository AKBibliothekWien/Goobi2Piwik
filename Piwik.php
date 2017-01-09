<?php

class Piwik {
	
	private $piwik_api_key;
	
	function __construct() {
		// Parse config file and get values:
		$configArray = parse_ini_file('config.ini');
		$this->piwik_api_key = $configArray['piwik_api_key'];
	}
	
	function getPdfDownloads($publicationValues, $fromDate, $toDate) {
		
		$urls = null;
		foreach ($publicationValues as $key => $value) {
			// Get URL for each publication ID and put it into a multi-dimensional array:
			$urls[$key] = array();
			$pageUrlString = "";
			array_push($urls[$key], 'downloadUrl=@viewer/file?pi='.$value['permanentId']);
		
			// Rework multidimensional array into a one-dimensional array. Each array element represents the query URL for one publication:
			$pageUrlString = implode(",", $urls[$key]);
			$urlsForQuery[$key] = urlencode($pageUrlString);
		}
				
		$queryString = 'module=API&method=API.getBulkRequest';
		$queryString .= '&format=PHP&filter_limit=-1&flat=0';
		$queryString .= '&token_auth='.$this->piwik_api_key;
		foreach ($urlsForQuery as $key => $url) {
			$urlString = urlencode('method=Actions.getDownloads&idSite=1&period=range&date='.$fromDate.','.$toDate.'&segment='.$url);
			$queryString .= '&urls['.$key.']='.$urlString;
		}
				
		// Prepare options for POST request:
		$opts = array('http' =>
				array(
						'method'  => 'POST',
						'header'  => 'Content-type: application/x-www-form-urlencoded',
						'content' => $queryString
				)
		);
		
		// Setting context for POST request:
		$context  = stream_context_create($opts);
		
		// Base URL for POST request:
		$baseUrl = 'http://emedien.arbeiterkammer.at/piwik/index.php';
		
		// Query Piwik API:
		$result = file_get_contents($baseUrl, false, $context);
		
		// Unserialize Piwik result to get an array:
		$content = unserialize($result);
		
		// Return array if it's not empty:
		if (!empty($content)) {
			return $content;
		} else {
			return null;
		}
	}
	
	function getVisits($publicationValues, $fromDate, $toDate) {
	
		$urls = null;
		foreach ($publicationValues as $key => $value) {
			// Get URL for each publication ID and put it into a multi-dimensional array:
			$urls[$key] = array();
			$pageUrlString = "";
			array_push($urls[$key], 'pageUrl=@viewer/image/'.$value['permanentId']);
	
			// Rework multidimensional array into a one-dimensional array. Each array element represents the query URL for one publication:
			$pageUrlString = implode(",", $urls[$key]);
			$urlsForQuery[$key] = urlencode($pageUrlString);
		}
	
		$queryString = 'module=API&method=API.getBulkRequest';
		$queryString .= '&format=PHP&filter_limit=-1&flat=0';
		$queryString .= '&token_auth='.$this->piwik_api_key;
		foreach ($urlsForQuery as $key => $url) {
			$urlString = urlencode('method=Actions.getPageUrls&idSite=1&period=range&date='.$fromDate.','.$toDate.'&segment='.$url);
			$queryString .= '&urls['.$key.']='.$urlString;
		}
	
		// Prepare options for POST request:
		$opts = array('http' =>
				array(
						'method'  => 'POST',
						'header'  => 'Content-type: application/x-www-form-urlencoded',
						'content' => $queryString
				)
		);
	
		// Setting context for POST request:
		$context  = stream_context_create($opts);
	
		// Base URL for POST request:
		$baseUrl = 'http://emedien.arbeiterkammer.at/piwik/index.php';	
		
		// Query Piwik API:
		$result = file_get_contents($baseUrl, false, $context);
	
		// Unserialize Piwik result to get an array:
		$content = unserialize($result);
	
		// Return array if it's not empty:
		if (!empty($content)) {
			return $content;
		} else {
			return null;
		}

	}
	
}
?>