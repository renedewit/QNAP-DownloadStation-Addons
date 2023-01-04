<?php

// Class class1337x ::: 1337X
class class1337x implements ISite, ISearch {

    /** @var int  */
    const HARD_LIMIT = 10;
    /** @var string  */
    private $url;
    /** @var string */
    const SITE = "https://1337x.to";

    /*
     * class1337x()
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
	
    /**
     * @param $keyword
     * @param $limit
     * @param $category
     * @return array
     */
    public function Search($keyword, $limit, $category) {
		static $count = 0;
        $page = 1;
        $ajax = new Ajax();
        $found = array();
		
        if (self::HARD_LIMIT && self::HARD_LIMIT < $limit) {
            $limit = self::HARD_LIMIT;
        }

        // Request the search page, elaborate the HTML and put search results in $found array
        $request = [
            "url" => self::SITE . "/search/$keyword/$page/",
            "body" => true
        ];

        $response = $ajax->request($request, function($_, $_, $_, $body, $url) use(&$page, &$found, &$limit) {
            $this->ElaborateSearchPage($body, $page, $found, $limit, $url);
        });
		
		$count = count($found);	

        // For each search link make sure we have a download link (enclosure_url)
        if ($len = count($found) > 0) {
            for ($i = 0; $i < count($found); ++$i) {
                /** @var SearchLink $searchItem */
                $searchItem = $found[$i];
                if(!isset($searchItem->enclosure_url) || empty($searchItem->enclosure_url)) {
					$detailMagnet = "";
					$detailCategory = "";
					
                    $request = [
                        "url" => $searchItem->link,
                        "body" => true
                    ];
                    $response = $ajax->request($request, function($_, $_, $_, $body, $_) use(&$searchItem) {
						if ($this->ElaborateDetailPage($body, $detailMagnet, $detailCategory)) {
							$searchItem->category = $detailCategory;
							$searchItem->enclosure_url = urldecode(urldecode($detailMagnet));
						}
                    });
                }
            }
        }

        return $found;
    }

    public function ElaborateDetailPage($body, &$detailMagnet, &$detailCategory) {
        $result = false;

        // magnet:?xt=urn:btih:HASH&dn=NAME
        $pattern = '#' .
            'href="(?P<magnet>magnet:\?xt=urn:btih:[^"]*)".*' .
			'<strong>Category</strong> <span>(?P<category>.*)</span> </li>.*' .
            '#siU'
        ;
        preg_match_all($pattern, $body, $matches);

        if(isset($matches["magnet"][0]) && !empty($matches["magnet"][0])) {
            $detailMagnet = $matches["magnet"][0];
			$detailCategory = $matches["category"][0];
        }

		$result = true;
        return $result;
    }

    public function ElaborateSearchPage($body, &$page, &$found, &$limit, $url) {

        $pattern = '#' .
			'<tr>.*' .
			'<td[^>]*><a .*><i .*></i></a><a href="(?P<link>.*)">(?P<name>.*)</a>.*</td>.*' .
			'<td class="coll-2 seeds">(?P<seeds>.*)</td>.*' .
			'<td class="coll-3 leeches">(?P<leechers>.*)</td>.*' .
			'<td class="coll-date">(?P<time>.*)</td>.*' .
			'<td class="coll-4 size [^"]*">(?P<size>.*) (?P<unit>[a-zA-Z]*)<span class="seeds">[^<]*</span></td>.*' .
			'<td class="coll-5 [^"]*"><a href=".*">(?P<uploader>.*)</a></td>.*' .
			'</tr>' .
            '#siU';

        preg_match_all($pattern, $body, $matches);

        if (!$matches || ($len = count($matches["name"])) == 0 ) {
            $page = false;
            return;
        }

		$url = parse_url($url);
		$url = sprintf("%s://%s", $url['scheme'], $url['host']);
		
        for ($i = 0 ; $i < $len ; ++$i) {
			$tlink = new SearchLink;
			
			$tlink->src           = "1337x";
			$tlink->link          = $url . $matches["link"][$i];
			$tlink->name          = $matches["name"][$i];
			$tlink->size          = ($matches["size"][$i] + 0) * class1337x::UnitSize($matches["unit"][$i]);
			
			$tlink->time = new DateTime();
			$time = $matches["time"][$i];
			$time = preg_replace("/&nbsp;/", " ", $time);
			$time = preg_replace("/ /", "_", $time);
			$time = explode("_", strip_tags($time));
			// example				na explode	strpos("'")
			// 6:12am				1			false
			// 12am Jan. 1st		3			false
			// Dec. 31st '22		3			numeric
						
			if (count($time) == 1 && !strpos($time[0], "'")) {									// e.g. 6:12am
				$tlink->time = DateTime::createFromFormat("h:ia", $time[0]);
			} else if (count($time) == 3 && !strpos("$time[0] $time[1] $time[2]", "'")) {		// e.g. 12am Jan. 1st
				$tlink->time = DateTime::createFromFormat("ha M. dS", "$time[0] $time[1] $time[2]");
			} else if (count($time) == 3 && strpos("$time[0] $time[1] $time[2]", "'") > 0) {	// e.g. Dec. 31st '22
				$time[2] = preg_replace("/\'/", "", $time[2]);
				$tlink->time = DateTime::createFromFormat("M. dS y", "$time[0] $time[1] $time[2]");
			} else {																			// other formats
				$tlink->time = Null;
			}										
		
			$tlink->seeds         = $matches["seeds"][$i] + 0;
			$tlink->peers         = $matches["leechers"][$i] + 0;
			$tlink->category      = "init-nr " . count($found);
			$tlink->enclosure_url = "";
			
			$found []= $tlink;		
			
            if (count($found) >= $limit) {
                $page = false;
                break;
            }
        }

        $page++;
    }
}
