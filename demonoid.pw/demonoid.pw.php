<?php
class demonoid implements ISite, ISearch {
    const SITE = "https://www.demonoid.to";
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
        $page = 0;
        
        $request = array(
            "url" => "https://www.demonoid.to/files/",
            "body" => true,
            "params" => array(
                "category" => 0,
                "subcategory" => "All",
                "quality" => "All",
                "seeded" => 2,
                "external" => 2,
                "query" => $keyword,
                "to" => 1,
                "sort" => "S",
                "page" => &$page
            )
        );
        
        $ajax = new Ajax();
        $found = array();
        $success = function ($_, $_, $_, $body, $_) use(&$page, &$found, &$limit) {
            preg_match_all(
                "`".
                    "<tr>.*".
                        "<td.+><a.+><img.+title=\"(?P<category>[^\"]+)\".+></a></td>.*".
                        "<td.+><a.+href=\"(?P<link>[^\"]+)\">(?P<name>.+)</a></td>.*".
                    "</tr>.*".
                    "<tr>.*".
                        "<td.+/td>.*".
                        "<td.+/td>.*".
                        "<td.+><a href=\"(?P<torrent>[^\"]+www.hypercache.pw[^\"]+)\">.*</td>.*".
                        "<td.+>(?P<size>[^ ]+) +(?P<unit>[a-zA-Z]*)</td>.*".
                        "<td.+/td>.*".
                        "<td.+/td>.*".
                        "<td.+><font.+>(?P<seeds>\d+)</font></td>.*".
                        "<td.+><font.+>(?P<leechers>\d+)</font></td>.*".
                    "</tr>".
                "`siU",
                $body,
                $result
            );
            
            if (!$result || ($len = count($result["name"])) == 0 ) {
                $page = false;
                return;
            }
            
            for ($i = 0 ; $i < $len ; ++$i) {
                $tlink = new SearchLink;
                
                $tlink->src           = "Demonoid";
                $tlink->link          = demonoid::SITE.$result["link"][$i];
                $tlink->name          = $result["name"][$i];
                $tlink->size          = ($result["size"][$i] + 0) * demonoid::UnitSize($result["unit"][$i]);
                //$tlink->time          = DateTime::createFromFormat("M d, Y", $result["time"][$i]);
                $tlink->seeds         = $result["seeds"][$i] + 0;
                $tlink->peers         = $result["leechers"][$i] + 0;
                $tlink->category      = strtolower($result["category"][$i]);
                $tlink->enclosure_url = $result["torrent"][$i];
                
                $found []= $tlink;
                
                if (count($found) >= $limit) {
                    $page = false;
                    return;
                }
            }
            
            ++$page;
        };
        
        while ($page !== false && count($found) < $limit) {
            if (!$ajax->request($request, $success)) {
                break;
            }
        }
        
        return $found;
    }
}
?>
