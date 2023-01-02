<?php
class thepiratebay implements ISite, ISearch {
	const SITE = "https://tpbays.xyz";
    /*
     * thepiratebay()
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
        case "KIB": return 1000;
        case "MIB": return 1000000;
        case "GIB": return 1000000000;
        case "TIB": return 1000000000000;
        default: return 1;
        }
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
        $success = function ($_, $_, $_, $body, $url) use(&$page, &$found, &$limit) {
            preg_match_all(
                "`<tr>.*".
                    "<td class=\"vertTh\">.*".
                        "<center>.*".
                            "<a.*>(?P<category>.*)</a><br>.*".
                        "</center>.*".
                    "</td>.*".
                    "<td>.*".
                        "<div.*<a href=\"(?P<link>.*)\".*>(?P<name>.*)</a>.*</div>.*".
                        "<a href=\"(?P<magnet>magnet:.*)\".*<img.*</a>.*".
                        "<font.*>Uploaded (?P<time>.*), Size (?P<size>.*)&nbsp;(?P<unit>[a-zA-Z]*), ULed by.*</font>.*".
                    "</td>.*".
                    "<td.*>(?P<seeds>\d+)</td>.*".
                    "<td.*>(?P<leechers>\d+)</td>.*".
                "</tr>`siU",
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
                
                $tlink->src         = "ThePirateBay";
                $tlink->link        = $url . $result["link"][$i];
                $tlink->name        = $result["name"][$i];
                $tlink->size        = ($result["size"][$i] + 0) * thepiratebay::UnitSize($result["unit"][$i]);				

                $tlink->time = new DateTime;
                $time = preg_replace("/&nbsp;/", " ", $result["time"][$i]);
				$time = preg_replace("/ /", "_", $time);
                $time = explode("_", strip_tags($time));
                
                if (isset($time[2])) {															// e.g. 11 mins ago
                    $tlink->time->setTimestamp(strtotime("-$time[0] minute"));
                } else if ($time[0] == "Today") {												// e.g. Today 13:29
                    $tlink->time->setTimestamp(strtotime($time[1]));
                } else if ($time[0] == "Y-day") {												// e.g. Y-day 23:58
                    $tlink->time->setTimestamp(strtotime("-1 day $time[1]"));
                } else if (strlen($time[1]) == 5) {												// e.g. 12-20 15:45
                    $tlink->time = DateTime::createFromFormat("m-d H:i", "$time[0] $time[1]");
                } else {																		// e.g. 08-15 2019
                    $tlink->time = DateTime::createFromFormat("m-d Y", "$time[0] $time[1]");
                }				
				
                $tlink->seeds         = $result["seeds"][$i] + 0;
                $tlink->peers         = $result["leechers"][$i] + 0;
                $tlink->category      = $result["category"][$i];
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
                "url" => thepiratebay::SITE . "/search/$keyword/$page/99/0",
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
