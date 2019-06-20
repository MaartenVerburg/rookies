<?php
	
	// ***********************************************************************************
	//
	// Generates .ics (ical) calendar file from dutch NKBV competitions on was2.shiftf5.nl
	// exports 3 years, current, next and past year
	// URL Options: www.hexentric.nl/rookies/?events=[day]&action=[download]
	//
	//			events = day, shows all competitions as a all-day event
	//			action = download, sends an ics-file to the browser
	// Author: Maarten Verburg, 2018-2019
	//
	// ***********************************************************************************
	
	//if (isset($links[0])) : echo "URL:https://was2.shiftf5.nl" . $links[0] . "\r\n"; endif;
	if (isset($_GET['events'] ) ) : $events = $_GET['events']; endif;
	if (isset($_GET['action'] ) ) : $action = $_GET['action']; endif;
	if ($action == 'download') : header ('Content-Type: text/calendar'); header ('Content-Disposition: attachment; filename=nkbvwedstrijden.ics'); endif;
	date_default_timezone_set('Europe/Amsterdam');
	
    $content1 = file_get_contents('https://was2.shiftf5.nl/competitions/year/' . (date("Y") - 1));
		$first_step1 = explode( '<tbody>' , $content1 );
		$second_step1 = explode('</tbody>' , $first_step1[1] );

    $content2 = file_get_contents('https://was2.shiftf5.nl/competitions/year/' . date("Y") );
    	$first_step2 = explode( '<tbody>' , $content2 );
		$second_step2 = explode('</tbody>' , $first_step2[1] );
		
	$content3 = file_get_contents('https://was2.shiftf5.nl/competitions/year/' . (date("Y") + 1 ));
    	$first_step3 = explode( '<tbody>' , $content3 );
		$second_step3 = explode('</tbody>' , $first_step3[1] );

    $content = $second_step1[0] . $second_step2[0] . $second_step3[0]; 
    //echo $content; exit();
        
    function getIcalDate($time, $incl_time = true) {
	    return $incl_time ? date('Ymd\THis', $time) : date('Ymd', $time);
	}  
	
	function uuid_make($string){
		$string = substr($string, 0, 8 ) .'-'.
		substr($string, 8, 4) .'-'.
		substr($string, 12, 4) .'-'.
		substr($string, 16, 4) .'-'.
		substr($string, 20);
		return $string;
	}
    
    $dom = new DomDocument();
    $dom->loadHTML( $content );
    $rows = $dom->getElementsByTagName("tr");
    
    echo "BEGIN:VCALENDAR\r\n";
    echo "PRODID:-//Google Inc//Google Calendar 70.9054//NL\r\n";
    echo "VERSION:2.0\r\n";
	echo "CALSCALE:GREGORIAN\r\n";
	echo "METHOD:PUBLISH\r\n";
    echo "X-WR-CALNAME:NKBV ". date("Y") ."\r\n";
	echo "X-WR-TIMEZONE:Europe/Amsterdam\r\n";

    foreach($rows as $n=>$item) {
        $line = preg_split( '#\n(?!s)#' , $item->nodeValue);
		$date = strtotime( trim(addslashes($line[1])));
		$tomorrow = strtotime( trim(addslashes($line[1])). "+1 days"); 
		$reminder = strtotime(trim(addslashes($line[1]). "-7 days") . " 12:00:00"); 
		if (!isset($line[4])) : $summary = $line[4]; else : $summary = $line[0]; endif;
		//echo '<pre>';echo '<h2>' . $n . '</h2>'; print_r($item); echo '</pre>';
		//echo '<pre>';echo '<h2>' . $n . '</h2>'; print_r($line); echo '</pre>';
		
		$arr = $item->getElementsByTagName("a");
		$links = array();
		foreach($arr as $url) {
			$href =  $url->getAttribute("href");
			$links[] = $href;
		}
	    //echo '<pre>';print_r($links); echo '</pre>';
	    
        echo "BEGIN:VEVENT\r\n";
	        echo "SEQUENCE:0\r\n";
	        echo "CLASS:PRIVATE\r\n";
			echo "CREATED:" . getIcalDate($date , false) . "T000000Z\r\n";
	        echo "UID:nkbv_" .md5(uniqid(getIcalDate($date , false) . $links[0], false)) . "\r\n";
	        //echo "UID:" .md5(uniqid(mt_rand(), true)) . "$n\r\n";
			//echo "ORGANIZER;CN=NKBV\r\n";
			if(isset($links[0])) : echo "URL:https://was2.shiftf5.nl" . $links[0] . "\r\n"; endif;
			if($events == "day") {
				echo "DTSTART:" . getIcalDate($date , false) . "\r\n";
				echo "DTEND:" . getIcalDate($date , false) . "\r\n";
				echo "DTSTAMP:" . getIcalDate($date , false) . "\r\n";
			} else {
				echo "DTSTART:" . getIcalDate($date , false) . "T100000Z\r\n";
				echo "DTEND:"   . getIcalDate($date , false) . "T190000Z\r\n";
				echo "DTSTAMP:" . getIcalDate($date , false) . "T000000Z\r\n";
			}

			echo "BEGIN:VALARM\r\n";
				echo "ACTION:DISPLAY\r\n";
				echo "TRIGGER;VALUE=DATE-TIME:" . getIcalDate($reminder , true) . "Z\r\n";
				echo "DESCRIPTION:Nog aanmelden voor deze wedstrijd?\r\n";
			echo "END:VALARM\r\n";

			echo "LOCATION:" . str_replace(array(",","'"), array("\,","\'"), trim(addslashes($line[2]))) . "\r\n";
			echo "DESCRIPTION:Competitie: " . trim(addslashes($line[4])); 
			if(isset($links[0])) : echo ", https://was2.shiftf5.nl" . $links[0] . "\r\n"; else : echo "\r\n"; endif;
			echo "SUMMARY:" . trim(addslashes($summary)) . " - " . str_replace(array(",","'"), array("\,","\'"), trim(addslashes($line[2]))) . "\r\n";
			echo "TRANSP:TRANSPARENT\r\n";
	    echo "END:VEVENT\r\n";
    }
     
    echo "END:VCALENDAR";
?>