<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

$allPublicationInfos = array();

$set = (isset($_POST['set'])) ? $_POST['set'] : null;
$metadataFormat = (isset($_POST['metadataFormat'])) ? $_POST['metadataFormat'] : null;
$fromDate = (isset($_POST['fromDate'])) ? $_POST['fromDate'] : null;
$toDate = (isset($_POST['toDate'])) ? $_POST['toDate'] : null;

require_once 'Oai.php';
require_once 'Piwik.php';
$oai = new Oai($set, $metadataFormat);
$piwik = new Piwik();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title>Webstatistik Intranda Viewer</title>
	
	<style>
	
		* {
			font-family: sans-serif;
			font-size: 15px;
		}
		
		table, tr, td, th {
			border: 1px solid black;
			border-collapse: collapse;
		}
		
		td, th {
			padding: 3px;
			
		}
		
	</style>
</head>
<body>

<h1>Piwik Datan</h1>

<form method="post" action="<?=$_SERVER['PHP_SELF']?>">
	<select name="set" id="set">
		<option value="zeitschriften.akaktuell.*">AK Aktuell</option>
		<option value="zeitschriften.akstadt.*">AK Stadt</option>
		<option value="zeitschriften.arbeitsmarktimfokus.*">Arbeitsmark im Fokus</option>
		<option value="zeitschriften.arbeitwirtschaft.*">Arbeit &amp; Wirtschaft</option>
		<option value="zeitschriften.gesundearbeit.*">Gesunde Arbeit</option>
		<option value="zeitschriften.gesundheitsoziales.*">Gesundheit &amp; Soziales</option>
		<option value="zeitschriften.ifaminfo.*">IFAM Info</option>
		<option value="zeitschriften.infobriefeuinternational.*">Infobrief EU &amp; International</option>
		<option value="zeitschriften.sozialundwirtschaftsstatistikaktuell.*">Sozial- und Wirtschaftsstatistik aktuell</option>
		<option value="zeitschriften.statistischeinformationen.*">Statistische Informationen</option>
		<option value="zeitschriften.sterreichinzahlen.*">Österreich in Zahlen</option>
		<option value="zeitschriften.wirtschaftgesellschaft.*">Wirtschaft &amp; Gesellschaft</option>
		<option value="zeitschriften.wirtschaftspolitikstandpunkte.*">Wirtschaftspolitik - Standpunkte</option>
		<option value="zeitschriften.wirtschaftumwelt.*">Wirtschaft &amp; Umwelt</option>		
		<option value="serien.materialienzuwirtschaftundgesellschaft*">Materialien zu Wirtschaft und Gesellschaft</option>
	</select>
	<br />
	von <input type="text" name="fromDate" value="<?php echo $fromDate; ?>" />&nbsp;bis&nbsp;<input type="text" name="toDate" value="<?php echo $toDate; ?>"/> (Format: JJJJ-MM-TT)
	<br />
	<input type="radio" name="metadataFormat" value="marcxml" checked>MarcXML (schneller)&nbsp;&nbsp;
	<input type="radio" name="metadataFormat" value="mets">Mets (langsamer)
	<br />
	<button type="submit" name="submit" id="submit">Auswählen</button>
</form>
<?php

echo '<br><br>';
echo 'Set: '.$set.'<br>';
echo 'Format: '.$metadataFormat.'<br>';
echo '<br><br>';

if ($fromDate == null || $toDate == null) {
	echo '<br />Bitte "von" und "bis" Datum im Format JJJJ-MM-TT eingeben.<br />';
	exit;
}

if ($set != null) {
	// Check metadata format and get publication infos:
	if ($metadataFormat == 'mets') {
		$allPublicationInfos = $oai->getPublicationInfosFromMets();
	} else if ($metadataFormat == 'marcxml') {
		$allPublicationInfos = $oai->getPublicationInfosFromMarcXml();
	}

	/*
	echo '<pre>';
	print_r($allPublicationInfos);
	echo '</pre>';
	*/
	
} else {
	echo '<br />Bitte Publikation wählen<br />';
	exit;
}





$piwikVisits = $piwik->getVisits($allPublicationInfos['values'], $fromDate, $toDate);
$piwikPdfDownloads = $piwik->getPdfDownloads($allPublicationInfos['values'], $fromDate, $toDate);

// Table head
echo '<table>';
echo '<thead>';
	echo '<tr>';
		echo '<th>Nr.</th>';
		foreach ($allPublicationInfos['labels'] as $label) {
			echo '<th>'.$label.'</th>';
		}
		echo '<th>Eindeutige Besuche</th>';
		echo '<th>Alle Besuche</th>';
		echo '<th>Eindeutige Downloads</th>';
		echo '<th>Alle Downloads</th>';
	echo '</tr>';
echo '</thead>';


// Table body
echo '<tbody>';
foreach ($allPublicationInfos['values'] as $key => $values) {

	if (!empty($piwikVisits[$key][0])) {
		$uniqueVisits = ($piwikVisits[$key][0]['nb_visits'] != null) ? $piwikVisits[$key][0]['nb_visits'] : '0';
		$allVisits = ($piwikVisits[$key][0]['nb_hits'] != null) ? $piwikVisits[$key][0]['nb_hits'] : '0';
	} else {
		$uniqueVisits = '0';
		$allVisits = '0';
	}
	
	if (!empty($piwikPdfDownloads[$key][0])) {
		$uniqueDownloads = ($piwikPdfDownloads[$key][0]['nb_visits'] != null) ? $piwikPdfDownloads[$key][0]['nb_visits'] : '0';
		$allDownloads = ($piwikPdfDownloads[$key][0]['nb_hits'] != null) ? $piwikPdfDownloads[$key][0]['nb_hits'] : '0';
	} else {
		$uniqueDownloads = '0';
		$allDownloads = '0';
	}
	
	
	echo '<tr>';
		echo '<td>'.($key+1).'</td>';
		foreach ($values as $value) {
			echo '<td>'.$value.'</td>';
		}
		echo '<td>'.$uniqueVisits.'</td>';
		echo '<td>'.$allVisits.'</td>';
		echo '<td>'.$uniqueDownloads.'</td>';
		echo '<td>'.$allDownloads.'</td>';
	echo '</tr>';
}

echo '</tbody>';
echo '</table>';

?>
</body>
</html>