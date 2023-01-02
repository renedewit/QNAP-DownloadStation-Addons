<?php
class kickass implements ISite, ISearch {
    /*
     * kickass()
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
                "`".
                    "<tr .+ id=\"torrent_latest_torrents\">.*".
                        "<a .+ title=\"Torrent magnet link\" href=\"[^\"]+url=(?P<magnet>[^\"]+)\".*</a>.*".
                        "<a href=\"(?P<link>[^\"]+)\" class=\"cellMainLink\">(?P<name>.*)</a>.*".
                        "in <span.*><strong><a.*>(?P<category>.*)</a></strong></span>.*".
                        "<td class=\"nobr center\">(?P<size>[^ ]+) +(?P<unit>[a-zA-Z]*)</span></td>.*".
                        "<td class=\"nobr center\" title=\"[^\"]+\">(?P<time>.*)</td>.*".
                        "<td class=\"green center\">(?P<seeds>\d+)</td>.*".
                        "<td class=\"red lasttd center\">(?P<leechers>\d+)</td>.*".
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
                $date = new DateTime();
                $time = preg_replace("/s? ago/", "", $result["time"][$i]);
                
                $tlink->src           = "Kickass";
                $tlink->link          = $url . $result["link"][$i];
                $tlink->name          = $result["name"][$i];
                $tlink->size          = ($result["size"][$i] + 0) * kickass::UnitSize($result["unit"][$i]);
                $tlink->time          = date_sub($date, date_interval_create_from_date_string($time));
                $tlink->seeds         = $result["seeds"][$i] + 0;
                $tlink->peers         = $result["leechers"][$i] + 0;
                $tlink->category      = strtolower($result["category"][$i]);
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
                "url" => "https://kickassz.com/usearch/$keyword/$page",
                "body" => true,
                "params" => array(
                    "field" => "seeders",
                    "sorder" => "desc"
                )
            );
            if (!$ajax->request($request, $success)) {
                break;
            }
        }
        
        return $found;
    }
}
?>
