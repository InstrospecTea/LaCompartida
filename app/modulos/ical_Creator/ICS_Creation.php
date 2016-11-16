<?php

class ICS_Creation {

	protected $calendarName;
	protected $file_name;
	protected $events = array();


	/**
	 * Constructor
	 * @param string $calendarName
	 */
	public function __construct($calendarName = "") {
		$this->calendarName = $calendarName;
		if ($this->file_name == "") {
			$this->file_name = $this->calenderName;
		}
	}


	/**
	 * Add event to calendar
	 * @param $start
	 * @param $end
	 * @param string $summary
	 * @param string $description
	 * @param string $url
	 * @internal param string $calendarName
	 */
	public function addEvent($start, $end, $summary = "", $description = "", $url = "") {
		// Formate for Event is timestamp
		if ($start == $end) {
			$end = $end + 3600;   // if $start & $end are the same date + 1 hour
		}

		$this->events[] = array(
			"start" => $start,
			"end" => $end,
			"summary" => $summary,
			"description" => $description,
			"url" => $url
		);
	}


	public function render() {

		//start Variable
		$ics = "";

		//Add header
		$ics .= "BEGIN:VCALENDAR
METHOD:REQUEST
VERSION:2.0
X-WR-CALNAME:" . $this->calendarName . "
PRODID:-//Google Inc//Google Calendar 70.9054//EN";

		//Add events
		foreach ($this->events as $event) {
			$ics .= "
BEGIN:VEVENT
UID:" . md5(uniqid(mt_rand(), true)) . "@EasyPeasyICS.php
DTSTAMP:" . gmdate('Ymd') . 'T' . gmdate('His') . "Z
DTSTART;VALUE=DATE:" . gmdate('Ymd', strtotime($event['start'])) . "
DTEND;VALUE=DATE:" . gmdate('Ymd', strtotime($event['start'] . " +1 days")) . "
SUMMARY:" . str_replace("\n", "\\n", $event['summary']) . "
DESCRIPTION:" . str_replace("\n", "\\n", $event['description']) . "
URL;VALUE=URI:" . $event['url'] . "
END:VEVENT";
		}
		//Footer
		$ics .= "END:VCALENDAR";
		//Output
		header('Content-type: text/calendar; charset=utf-8');
		header('Content-Disposition: inline; filename=' . $this->file_name . '.ics');
		echo $ics;
	}

	public function render_Event() {

		$ics = "";

		//Add header
		$ics .= "BEGIN:VCALENDAR
METHOD:PUBLISH
VERSION:2.0
CALSCALE:GREGORIAN
PRODID:-//Google Inc//Google Calendar 70.9054//EN";

		//Add events
		foreach ($this->events as $event) {
			$ics .= "
BEGIN:VEVENT
UID:" . md5(uniqid(mt_rand(), true)) . "@ICS_Creation.php
DTSTAMP:" . gmdate('Ymd') . 'T' . gmdate('His') . "Z
DTSTART;VALUE=DATE:" . gmdate('Ymd', strtotime($event['start'])) . "
DTEND;VALUE=DATE:" . gmdate('Ymd', strtotime($event['start'] . " +1 days")) . "
SUMMARY:" . str_replace("\n", "\\n", $event['summary']) . "
DESCRIPTION:" . str_replace("\n", "\\n", $event['description']) . "
END:VEVENT";
		}
		//Footer
		$ics .= "
END:VCALENDAR";
		//Output
		/*$output = "header('Content-type: text/calendar; charset=utf-8')";
		$output .= "header('Content-Disposition: inline; filename=calendar.ics')";*/

		$output = $ics;
		return $output;
	}
}
