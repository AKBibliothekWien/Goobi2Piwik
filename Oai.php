<?php

class Oai {
	
	private $structTypes = array("PeriodicalVolume", "SerialVolume" , "Monograph");
	private $set = null;
	private $metadataFormat = 'marcxml';
	
	function __construct($set, $metadataFormat) {
		$this->set = $set;
		$this->metadataFormat = $metadataFormat;
	}
	
	
	/**
	 * Gets all desired publication infos from MarcXML.
	 *
	 * @return array
	 */
	public function getPublicationInfosFromMarcXml() {
		$allPublicationInfos = array();
		$oaiAsSimpleXml = $this->getOaiAsSimpleXml(); // Include for production
		//$oaiAsSimpleXml = simplexml_load_file('xmlExamples/oai_marc_wug.xml'); // FOR TESTING: Remove for production
		
		// These values are also exported as field 100 in MarcXML OAI, but they are not real permanent IDs
		$falseMarcPids = array("akburgenland", "akkaernten", "akniederoesterreich", "akoberoesterreich", "aksalzburg", "aksteiermark", "aktirol", "akvorarlberg", "akwien");

		// Labels:
		$allPublicationInfos['labels']['permanentId'] = 'Permanent ID';
		$allPublicationInfos['labels']['title'] = 'Titel';
		$allPublicationInfos['labels']['year'] = 'Jahr/Datum';
		
		
		$counter = -1;
		foreach ($oaiAsSimpleXml->ListRecords->record as $key => $record) {
			$counter = $counter + 1;
			
			foreach ($record->metadata as $metadata) {
				$metadataNs = $metadata->getNameSpaces(true);
				$marc = $metadata->children($metadataNs['marc']);
				$marc->registerXPathNamespace('marc', $metadataNs['marc']);
					
				// Get permanent id
				if (isset($marc->record->controlfield)) {
					foreach($marc->record->controlfield as $key => $controlfield) {
						if ($controlfield->attributes()->tag == '001') {
							$permanentIdTemp = $this->getObjectValueAsString($controlfield);
							if (!in_array($permanentIdTemp, $falseMarcPids)) {
								$permanentId = $permanentIdTemp;
								$allPublicationInfos['values'][$counter]['permanentId'] = $permanentId;
							}
						}
					}
				}
					
				// Get title
				if (isset($marc->record->datafield)) {
					foreach($marc->record->datafield as $key => $datafield) {
							
						$title = null;
							
						// Get main title from field 246
						if ($datafield->attributes()->tag == '246') {
							$title = $this->getObjectValueAsString($datafield->subfield);
							if ($title != null) {
								$allPublicationInfos['values'][$counter]['title'] = $title;
							}
						}
							
						// If main title in field 246 does not exist, try to get title from field 245
						if ($title == null) {
							if ($datafield->attributes()->tag == '245') {
								$title = $this->getObjectValueAsString($datafield->subfield);
								if ($title != null) {
									$allPublicationInfos['values'][$counter]['title'] = $title;
								} else {
									$allPublicationInfos['values'][$counter]['title'] = 'Kein Titel';
								}
									
							}
						}
							
					}
				}
				
				// Get publication date
				if (isset($marc->record->datafield)) {
					foreach($marc->record->datafield as $key => $datafield) {
						$publicationDate = null;

						// Get publication date (normally it's only the year, rarely we have a full date)
						if ($datafield->attributes()->tag == '260') {
							foreach ($datafield->subfield as $subfield) {
								if ($subfield->attributes()->code == 'c') {
									$publicationDate = $this->getObjectValueAsString($subfield);
									$allPublicationInfos['values'][$counter]['year'] = $publicationDate;
								}
							}
						}
					}
				}
			}
			
		}
		
		return $allPublicationInfos;
	}
	
	
	/**
	 * Gets all desired publication infos from METS.
	 * 
	 * @return array
	 */
	public function getPublicationInfosFromMets() {
		$allPublicationInfos = array();
		$oaiAsSimpleXml = $this->getOaiAsSimpleXml(); // Include for production
		//$oaiAsSimpleXml = simplexml_load_file('xmlExamples/oai_mets_wug_small.xml'); // FOR TESTING: Remove for production
	
		$allPublicationInfos['labels']['permanentId'] = 'Permanent ID';
		$allPublicationInfos['labels']['title'] = 'Titel';
		$allPublicationInfos['labels']['year'] = 'Jahr';
		$allPublicationInfos['labels']['volumeNo'] = 'Band Nr.';
		$allPublicationInfos['labels']['issueNo'] = 'Heft Nr.';
		
		$publicationInfos = array();
		
		foreach ($oaiAsSimpleXml->ListRecords->record as $key => $record) {
	
			//$recordInfo = array();
			foreach ($record->metadata as $metadata) {
				$metadataNs = $metadata->getNameSpaces(true);
				$mets = $metadata->children($metadataNs['mets']);
	
				// Get DmdLogIds for appropriate structure types (e. g. PeriodicalVolume, Monograph, etc.)
				$dmdLogIds = null;
				foreach ($mets->mets->structMap as $structMap) {
					if ($structMap->attributes()->TYPE == 'LOGICAL') {
						$dmdLogIds = $this->getDmdLogIds($structMap, $metadata, $metadataNs, $this->structTypes);
					}
				}
	
				// Get publication infos like permanentId, title, volumeNo, etc.
				if ($dmdLogIds != null) {
					$counter = -1;
					foreach ($mets->mets->dmdSec as $dmdSec) {
						
						foreach ($dmdLogIds as $dmdLogId) {
							$counter = $counter + 1;
							if ($dmdSec->attributes()->ID == $dmdLogId) {
								//$recordInfo['values'][$counter] = $this->getPublicationInfoFromMets($dmdSec, $metadataNs);
								//$recordInfoMerged = array_merge($recordInfoMerged, $recordInfo);
								$publicationInfo = $this->getPublicationInfoFromMets($dmdSec, $metadataNs);
								$publicationInfos = array_merge($publicationInfos, $publicationInfo);
							}
						}
					}
					
					
				}
			}
		}
		
		foreach ($publicationInfos as $publicationInfo) {
			$allPublicationInfos['values'][] =  $publicationInfo;
		}
		
		return $allPublicationInfos;
	}
	
	
	
	
	// ------------------------------------------ HELPER FUNCTIONS BEGIN ------------------------------------------ //
	
	/**
	 * Converting XML from OAI to SimpleXML. If the OAI result gives us more than 1 "pages",
	 * it will merge them to 1 object.
	 * 
	 * @return SimpleXMLElement
	 */
	private function getOaiAsSimpleXml() {
	
		$xmlPages = $this->getOaiPagesAsXml();
		
		$mergedDoc = new DOMDocument('1.0', 'UTF-8');
		$mergedDoc->formatOutput = true;
	
		foreach($xmlPages as $key => $xmlPage) {
			if ($key == '0') {
				$firstDoc = new DOMDocument('1.0', 'UTF-8');
				$firstDoc->formatOutput = true;
				$firstDoc->loadXML($xmlPage);
			} else {
				$nextDoc = new DOMDocument('1.0', 'UTF-8');
				$nextDoc->formatOutput = true;
				$nextDoc->loadXML($xmlPage);
					
				foreach ($nextDoc->getElementsByTagName('record') as $record) {
					$recordNode = $firstDoc->importNode($record, true);
					$firstDoc->getElementsByTagName('ListRecords')->item(0)->appendChild($recordNode);
				}
			}
		}
	
		$firstDoc->saveXML();
	
		$mergedDoc = new DOMDocument("1.0", 'UTF-8');
		$mergedDoc->formatOutput = true;
		$mergedDoc->appendChild($mergedDoc->importNode($firstDoc->documentElement, true));
	
		return simplexml_import_dom($mergedDoc);
	}
	
	
	
	
	/**
	 * Gets the desired publication information (title, permanentId, volumeNo, ...) from a dmdSec node in a METS XML.
	 * 
	 * @param SimpleXML $dmdSec
	 * @param array $metadataNs	 * 
	 * @return array
	 */
	private function getPublicationInfoFromMets($dmdSec, $metadataNs) {
		
		$publicationInfo = array();
		$mods = $dmdSec->mdWrap->xmlData->children($metadataNs['mods']);
	
		if (isset($mods)) {
			$permanentId = null;
			
			// Get permanent ID
			if (isset($mods->mods->recordInfo)) {
				foreach($mods->mods->recordInfo as $key => $recordInfo) {
					if ($recordInfo->recordIdentifier->attributes()->source == 'gbv-ppn') {
						$permanentId = $this->getObjectValueAsString($recordInfo->recordIdentifier);
						$publicationInfo[$permanentId]['permanentId'] = $permanentId;
					}
				}
			}
			
			if ($permanentId != null) {		
				// Get title
				if (isset($mods->mods->titleInfo)) {
					foreach($mods->mods->titleInfo as $key => $titleInfo) {
						if ($titleInfo->attributes()->type != 'uniform') {
							$title = $this->getObjectValueAsString($titleInfo->title);
							$publicationInfo[$permanentId]['title'] = $title;
						}
					}
				} else {
					$publicationInfo[$permanentId]['title'] = 'Kein Titel';
				}
				
				// Get year
				if (isset($mods->mods->originInfo)) {
					foreach($mods->mods->originInfo as $key => $originInfo) {
						if (!empty($originInfo->dateIssued)) {
							if ($originInfo->dateIssued->attributes()->keyDate == 'yes') {
								$year = $this->getObjectValueAsString($originInfo->dateIssued);
								$publicationInfo[$permanentId]['year'] = $year;
							}
						}
					}
				} else {
					$publicationInfo[$permanentId]['year'] = 'k. A.';
				}
				
				// Get volume no and issue no:
				if (isset($mods->mods->part->detail)) {
					foreach($mods->mods->part->detail as $key => $detail) {
						if ($detail->attributes()->type == 'volume') {
							$volumeNo = $this->getObjectValueAsString($detail->number);
							$publicationInfo[$permanentId]['volumeNo'] = $volumeNo;
						}
					}
				} else {
					$publicationInfo[$permanentId]['volumeNo'] = 'k. A.';
				}
				
				
				// Get volume no and issue no:
				if (isset($mods->mods->part->detail)) {
					foreach($mods->mods->part->detail as $key => $detail) {				
						if ($detail->attributes()->type == 'issue') {
							$issueNo = $this->getObjectValueAsString($detail->number);
							$publicationInfo[$permanentId]['issueNo'] = $issueNo;
						}
					}
				} else {
					$publicationInfo[$permanentId]['issueNo'] = 'k. A.';
				}
			}
		}
		
		/*
		echo '<pre>';
		print_r($publicationInfo);
		echo '</pre>';
		*/
		// Return array with numerical $keys:
		return array_values($publicationInfo);
	}

	
	/**
	 * Gets the XML from the OAI interface. If there are more than 100 results, there will be multiple "pages" of results.
	 * Each page is stored in an array item.
	 * 
	 * @param string $resumptionToken
	 * @param array $returnArray
	 * @return array
	 */
	private function getOaiPagesAsXml($resumptionToken = null, &$returnArray = array()) {
		
		$resumptionTokenQueryString = ($resumptionToken != null) ? '&resumptionToken='.$resumptionToken : '';
		$contextOai  = stream_context_create(array('http' => array('header' => 'Accept: application/xml')));
		$urlOai = 'http://emedien.arbeiterkammer.at/viewer/oai/?verb=ListRecords&metadataPrefix='.$this->metadataFormat.'&set='.$this->set.$resumptionTokenQueryString;
		$xmlOai = file_get_contents($urlOai, false, $contextOai);
		array_push($returnArray, $xmlOai);
	
		$oaiSimpleXml = simplexml_load_string($xmlOai);
		$resumptionTokenObject = $oaiSimpleXml->ListRecords->resumptionToken;
	
		if (!empty($resumptionTokenObject)) {
			$resumptionTokenString = $resumptionTokenObject->__toString();
			$newArray = $this->getOaiPagesAsXml($resumptionTokenString, $returnArray);
		}
	
		return $returnArray;
	}
	
	/**
	 * Gets the DMDLOG_IDs of the desired structure types in a METS-XML and returns them in an array.
	 * 
	 * @param unknown $structMapLogical
	 * @param unknown $metadata
	 * @param unknown $metadataNs
	 * @param array $validStructTypes
	 * @param array $returnArray
	 * @return array
	 */
	private function getDmdLogIds($structMapLogical, $metadata, $metadataNs, $validStructTypes, $returnArray = array()) {
	
		foreach($structMapLogical->children($metadataNs['mets']) as $nodeName => $structMapLogicalChild) {
			if (count($structMapLogicalChild->children($metadataNs['mets'])) > 0) {
	
				$structTypeObject = $structMapLogicalChild->attributes()->TYPE;
				$structType = $this->getObjectValueAsString($structTypeObject);
	
				$dmdLogIdObject = $structMapLogicalChild->attributes()->DMDID;
				$dmdLogId = $this->getObjectValueAsString($dmdLogIdObject);
	
				if ($this->isValidStructType($structType, $validStructTypes)) {
					array_push($returnArray, $dmdLogId);
				}
	
				$returnArray = $this->getDmdLogIds($structMapLogicalChild, $metadata, $metadataNs, $validStructTypes, $returnArray);
	
			} else {
	
				$structTypeObject = $structMapLogicalChild->attributes()->TYPE;
				$structType = $this->getObjectValueAsString($structTypeObject);
	
				$dmdLogIdObject = $structMapLogicalChild->attributes()->DMDID;
				$dmdLogId = $this->getObjectValueAsString($dmdLogIdObject);
					
				if ($this->isValidStructType($structType, $validStructTypes)) {
					array_push($returnArray, $dmdLogId);
				}
			}
		}
			
		return $returnArray;
	}
	

	/**
	 * Checks if the given structure type is in an array that contains the valid structure types.
	 * The array that contains the valid structure types is hardcoded in this file.
	 * 
	 * @param string $structType
	 * @param array $validStructTypes
	 * @return bool
	 */
	private function isValidStructType($structType, $validStructTypes) {
		if (in_array($structType, $validStructTypes)) {
			return true;
		} else {
			return false;
		}
	}
	
	
	/**
	 * Get string value of an object. Checks for null.
	 * 
	 * @param object $object
	 * @return string or null
	 */
	private function getObjectValueAsString($object) {
		if ($object != null) {
			return $object->__toString();
		} else {
			return null;
		}
	}
	

	
	
}

?>