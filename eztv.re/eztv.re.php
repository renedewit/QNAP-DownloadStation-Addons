<?php
class eztv implements ISite, ISearch {
    /*
     * eztv()
     * @param {string} $url
     * @param {string} $username
     * @param {string} $password
     * @param {string} $meta
     */
    public function __construct($url = null, $username = null, $password = null, $meta = null) {
    }
    
    /*
     * UnitSize()
     * @param {string} $unit
     * @return {number} sizeof byte
     */
    static function UnitSize($unit) {
        switch (strtoupper($unit)) {
        case "KB": return 1000;
        case "MB": return 1000000;
        case "GB": return 1000000000;
        case "TB": return 1000000000000;
        default: return 1;
        }
    }
	
	public function SigFig($value, $digits) {
		if ($value == 0) {
			$decimalPlaces = $digits - 1;
		} elseif ($value < 0) {
			$decimalPlaces = $digits - floor(log10($value * -1)) - 1;
		} else {
			$decimalPlaces = $digits - floor(log10($value)) - 1;
		}

		$answer = round($value, $decimalPlaces);
		return $answer;
	}	
    
    /*
     * Search()
     * @param {string} $keyword
     * @param {integer} $limit
     * @param {string} $category
     * @return {array} SearchLink array
     */
    public function Search($keyword, $limit, $category) {
        $page = 1;
        
        $ajax = new Ajax();
        $found = array();
        $success = function ($ignore1, $ignore2, $ignore3, $body, $url) use(&$page, &$found, &$limit) {
            preg_match_all(
                "`".
                    "<tr .*name=\"hover\" class=\"forum_header_border\">.*".
						"<td .*>.*</a>.*</td>.*<td .*>.*".
                        "<a href=\"(?P<link>[^\"]+)\" .*class=\"epinfo\">(?P<name>.*)<.*/a>.*".
						"</td>.*<td .*>.*".
                        "<a href=\"(?P<magnet>[^\"]+)\".*</a>.*".
						"<a href=\".*>.*</a>.*</td>.*".
                        "<td .*\"forum_thread_post\">(?P<size>[^ ]+) +(?P<unit>[a-zA-Z]*)</td>.*".
						"<td .*\"forum_thread_post\">(?P<time>.*)</td>.*".
						"<td .*<font color=\"green\">(?P<seeds>\d+)</font></td>.*".
                    "</tr>".
                "`siU",
                $body,
                $result
            );
            
            if (!$result || ($len = count($result["name"])) == 0 ) {
                $page = false;
                return;
            }
            
            $url = parse_url($url);
            $url = sprintf("%s://%s", $url['scheme'], $url['host']);
            
            for ($i = 0 ; $i < $len ; ++$i) {
                $tlink = new SearchLink;

                $tlink->src           = "eztv";
                $tlink->link          = $url . $result["link"][$i];
                $tlink->name          = $result["name"][$i];
                $tlink->size          = eztv::SigFig(($result["size"][$i] + 0), 3) * eztv::UnitSize($result["unit"][$i]);

                $date = new DateTime();
				$time = $result["time"][$i];
				// examples: 46m; 22h 16m; 3d 10h; 2 week(s); 5 mo; 3 year(s)
				$time = preg_replace("/h/", "hour", $time);				
				$time = preg_replace("/mo/", "Month", $time);
				$time = preg_replace("/m/", "minute", $time);	
				$time = preg_replace("/d/", "day", $time);
				
                $tlink->time          = date_sub($date, date_interval_create_from_date_string($time));
                $tlink->seeds         = $result["seeds"][$i] + 0;
                $tlink->peers         = 0;
                $tlink->category      = "TV shows";
                $tlink->enclosure_url = urldecode(urldecode($result["magnet"][$i]));
                
                $found []= $tlink;
                
                if (count($found) >= $limit) {
                    $page = false;
                    return;
                }
            }
            
            ++$page;
        };
        
        while ($page !== false && count($found) < $limit) {
            $request = array(
                "url" => "https://eztv.proxyninja.org/search/?q1=" . $keyword . "&search=Search",
                "body" => true
            );
            if (!$ajax->request($request, $success)) {
                break;
            }
        }
        
        return $found;
    }
}
?>
