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
    public function __construct($url = null, $username = null, $password = null, $meta = NULL) {
    }
    
    /*
     * UnitSize()
     * @param {string} $unit
     * @return {number} sizeof byte
     */
    static function UnitSize($unit) {
        switch ($unit) {
        case "KiB": return 1000;
        case "MiB": return 1000000;
        case "GiB": return 1000000000;
        case "TiB": return 1000000000000;
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
        $page = 0;
        $ajax = new Ajax();
        $found = array();
        
        $success = function ($_, $_, $_, $body, $url) use(&$page, &$found, &$limit) {
            preg_match(
                "`<table id=\"searchResult\">.*</table>`siU",
                $body,
                $result
				);
            
            if (!$result) {
                return ($page = false);
            }
                        
            preg_match_all(
                "`<tr>.*".
                    "<td class=\"vertTh\">.*".
                        "<center>.*".
                            "<a.*>(?P<category>.*)</a><br />.*".
                        "</center>.*".
                    "</td>.*".
                    "<td>.*".
                        "<div.*<a href=\"(?P<link>.*)\".*>(?P<name>.*)</a>*.</div>.*".
                        "<a href=\"(?P<magnet>magnet:.*)\".*<img.*</a>.*".
                        "<font.*>Uploaded (?P<time>.*), Size (?P<size>.*)&nbsp;(?P<unit>[a-zA-Z]*), ULed by.*</font>.*".
                    "</td>.*".
                    "<td.*>(?P<seeds>\d+)</td>.*".
                    "<td.*>(?P<leechers>\d+)</td>.*".
                "</tr>`siU",
                $body,
                $result
            );
            
            if (!$result || ($len = sizeof($result["name"])) == 0) {
                return ($page = false);
            }
            
            for ($i = 0 ; $i < $len ; ++$i) {
                $tlink = new SearchLink;
                
                $tlink->src           = "ThePirateBay";
                $tlink->link          = thepiratebay::SITE . $result["link"][$i];
                $tlink->name          = $result["name"][$i];
                $tlink->size          = ($result["size"][$i] + 0) * thepiratebay::UnitSize($result["unit"][$i]);
                
                $time = explode(" ", strip_tags($result["time"][$i]));
                
                if (isset($time[2])) {
                    
                    $tlink->time      = new DateTime;
                    $tlink->time->setTimestamp(strtotime("-$time[0] minute"));
                    
                } else if ($time[0] == "Today") {
                    
                    $tlink->time      = new DateTime;
                    $tlink->time->setTimestamp(strtotime($time[1]));
                    
                } else if ($time[0] == "Y-day") {
                    
                    $tlink->time      = new DateTime;
                    $tlink->time->setTimestamp(strtotime("-1 day $time[1]"));
                    
                } else if (strlen($time[1]) == 5) {
                    
                    $tlink->time      = DateTime::createFromFormat("m-d H:i", "$time[0] $time[1]");
                    
                } else {
                    
                    $tlink->time      = DateTime::createFromFormat("m-d Y", "$time[0] $time[1]");
                    
                }
                
                $tlink->seeds         = $result["seeds"][$i] + 0;
                $tlink->peers         = $result["leechers"][$i] + 0;
                $tlink->category      = strtolower($result["category"][$i]);
                $tlink->enclosure_url = $result["magnet"][$i];
                
                $found []= $tlink;
                
                if (count($found) >= $limit) {
                    return ($page = false);
                }
            }
            
            ++$page;
        };
        
        while ($page !== false && count($found) < $limit) {
            $request = array(
                "url"       => thepiratebay::SITE . "/search/$keyword/$page/99/0",
                "body"      => true
            );
            if (!$ajax->request($request, $success)) {
                break;
            }
        }
        
        return $found;
    }
}
?>
