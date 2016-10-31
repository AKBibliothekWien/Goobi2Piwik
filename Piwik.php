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
				
		$queryString = "";
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
		/*
		 TODO: Add urls to $baseUrl (getBulkRequest, Doku: https://developer.piwik.org/2.x/api-reference/reporting-api#passing-an-array-of-data-as-a-parameter).
		 Working example API request for test in the Browser (don't forget to use your real Piwik API key):
		 http://emedien.arbeiterkammer.at/piwik/index.php/?module=API&method=API.getBulkRequest&format=xml&token_auth=PIWIK_API_KEY_WITH_SUFFICIENT_RIGHTS
		 &urls[0]=method%3DActions.getPageUrls%26idSite%3D1%26period%3Dmonth%26date%3Dtoday%26segment%3DpageUrl%3D%40viewer%2Fimage%2FAC00564651_2013_001%2CpageUrl%3D%40viewer%2Fimage%2FAC00564651_2015_002
		 &urls[1]=method%3DActions.getPageUrls%26idSite%3D1%26period%3Dmonth%26date%3Dtoday%26segment%3DpageUrl%3D%40viewer%2Fimage%2FAC00564651_2014_001
		 */
		$baseUrl = 'http://emedien.arbeiterkammer.at/piwik/index.php';
		$baseUrl .= '?module=API&method=API.getBulkRequest';
		$baseUrl .= '&format=PHP&filter_limit=-1&flat=0';
		$baseUrl .= '&token_auth=TEST'.$this->piwik_api_key;
		
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
	
	//function piwikResultsBulkRequest($publicationValues) {
	function getVisits($publicationValues, $fromDate, $toDate) {
	
		$urls = null;
		foreach ($publicationValues as $key => $value) {
			// Get URL for each publication ID and put it into a multi-dimensional array:
			$urls[$key] = array();
			$pageUrlString = "";
			array_push($urls[$key], 'pageUrl=@viewer/image/'.$value['permanentId']);
	
			// Rework multidimensional array into a one-dimensional array. Each array element represents the query URL for one publication:
			$pageUrlString = implode(",", $urls[$key]);
			//$urlsForQuery[$key] = urlencode('method=Actions.getPageUrls&idSite=1&period=month&date=today&segment='.$pageUrlString);
			$urlsForQuery[$key] = urlencode($pageUrlString);
		}
	
		$queryString = "";
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
		/*
		 TODO: Add urls to $baseUrl (getBulkRequest, Doku: https://developer.piwik.org/2.x/api-reference/reporting-api#passing-an-array-of-data-as-a-parameter).
		 Working example API request for test in the Browser (don't forget to use your real Piwik API key):
		 http://emedien.arbeiterkammer.at/piwik/index.php/?module=API&method=API.getBulkRequest&format=xml&token_auth=PIWIK_API_KEY_WITH_SUFFICIENT_RIGHTS
		 &urls[0]=method%3DActions.getPageUrls%26idSite%3D1%26period%3Dmonth%26date%3Dtoday%26segment%3DpageUrl%3D%40viewer%2Fimage%2FAC00564651_2013_001%2CpageUrl%3D%40viewer%2Fimage%2FAC00564651_2015_002
		 &urls[1]=method%3DActions.getPageUrls%26idSite%3D1%26period%3Dmonth%26date%3Dtoday%26segment%3DpageUrl%3D%40viewer%2Fimage%2FAC00564651_2014_001
		 */
		$baseUrl = 'http://emedien.arbeiterkammer.at/piwik/index.php';
		$baseUrl .= '?module=API&method=API.getBulkRequest';
		$baseUrl .= '&format=PHP&filter_limit=-1&flat=0';
		$baseUrl .= '&token_auth='.$this->piwik_api_key;
	
		
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