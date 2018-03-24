
<!-- scrape.php - 03/24/2018-->
<?php
    // config.php defines SQL information and API keys.
    include "config.php";
    // A web scraping library.
    include "simple_html_dom.php";

    function getLocation($address){
        // Format the address to be inserted into an api request url.
        $formatted_address = str_replace(" ","+",str_replace(",","", trim($address)));
        // Build the gmaps geocode http request url.
        $url = "https://maps.googleapis.com/maps/api/geocode/json?address=" . $formatted_address . "&key=" . GMAPS_KEY;
        // Retrieves jSON object from gmaps geocode api and decodes it into an array.
        $response = json_decode(file_get_contents($url), true);
        // Retrieves location array with children "lat" and "lng".
        $location = $response["results"][0]["geometry"]["location"];

        return $location;
    }
    
    function scrape(){
        // Open database connection.
        $db = mysqli_connect(SQL_HOST, SQL_USER, SQL_PASSWORD, SQL_DATABASE)
        or die("Error connecting to MySQL server.");

        $page_max = 2;
        $id       = 1;
        for($page = 1; $page <= $page_max; $page++){
            $fn_url = "http://www.foodnetwork.com/restaurants/shows/diners-drive-ins-and-dives/a-z/p/" . $page;
            $html   = file_get_html($fn_url);

            foreach ($html->find("div[class=m-MediaBlock o-Capsule__m-MediaBlock]") as $candidate){
                // Candidate is a restaurant header if it has a child h3 tag.
                if($candidate->find("h3")){
                    // The header is the first h3 tag of the candidate div.
                    $header   = $candidate -> find("h3[class=m-MediaBlock__a-Headline]", 0);
                    $name     = $header -> find("span", 0) -> plaintext;
                    $url      = str_replace("//", "", $header -> find("a", 0) -> href);
                    $address  = trim(str_replace("&nbsp;", " ", $candidate -> find("div[class=m-Info__a-Address]",0) -> plaintext));
                    $location = getLocation($address);
                    $lat      = $location["lat"];
                    $lng      = $location["lng"];

                    $addInfo    = "(id, name, latitude, longitude, address, url) VALUES (" . $id . ", \"" . $name . "\", " . $lat . ", " . $lng . ", \"" . $address . "\", \"" . $url . "\")";
                    $updateInfo = "name=\"" . $name . "\", latitude=" . $lat . ", longitude=" . $lng . ", address=\"" . $address . "\", url=\"" . $url  . "\"";
                    $query      = "INSERT INTO " . SQL_TABLE . " " . $addInfo . " ON DUPLICATE KEY UPDATE " . $updateInfo;

                    mysqli_query($db, $query);
                    $id += 1;
                }
            }
        }
        
        // Commit database changes.
        mysqli_commit($db);
        // Close database connection.
        mysqli_close($db);
    }

    
?>
