<!-- scrape.php - 03/24/2018-->
<?php
/* Imports */

// config.php defines SQL information and API keys.
include "config.php";
// A library to parse html.
include "simple_html_dom.php";

/* Helper Functions */

// get_location takes an address of a physical location (string) and returns the 
// location (association list) which holds the latitude and longitude of the address.
// Geocoding is handled by the google maps geocoding api.
function get_location($address){
    // Format the address to be inserted into an api request url.
    $formatted_address = str_replace(" ", "+", str_replace(",", "", $address));
    // Build the gmaps geocode http request url.
    $request_url = 
        "https://maps.googleapis.com/maps/api/geocode/json?address=" . $formatted_address . "&key=" . GMAPS_KEY;
    // Retrieves jSON object from gmaps geocode api and decodes it into an association list.
    $response = json_decode(file_get_contents($request_url), true);
    // Retrieves location association list that has elements "lat" and "lng".
    $location = $response["results"][0]["geometry"]["location"];

    return $location;
}

// get_max_page_index retrieves the number of result pages that exist on the food network's
// ddd restaurant list site by looking at the index of the navigation buttons at the 
// bottom of the view.
function get_max_page_index(){
    $first_page_url = 
        "http://www.foodnetwork.com/restaurants/shows/diners-drive-ins-and-dives/a-z/p/1";

    // The maximum page index is the plaintext of the second to last navigation button.
    $nav_button_count = 0;
    $html             = file_get_html($first_page_url);
    // The nav bar holds the navigation buttons.
    $nav_bar          = $html -> find("section[class=o-Pagination]", 0);
    // Traverse the nav bar and count how many buttons there are.
    foreach($nav_bar -> find("li[class=o-Pagination__a-ListItem]") as $nav_button){
        $nav_button_count++;
    }

    // The 0-indexed location of the second to last button in the nav bar. 
    $max_page_button_index = $nav_button_count - 2;
    // The plaintext of the second to last button in the nav bar, i.e. the max_page_index.
    $max_page_index_str = $nav_bar -> find("li[class=o-Pagination__a-ListItem]", $max_page_button_index) -> plaintext;
    // Exctract the integer value from the string.
    $max_page_index     = filter_var($max_page_index_str, FILTER_SANITIZE_NUMBER_INT);

    return $max_page_index;
}

/* Main Functions */

// Scrape all restaurant data from the foodnetwork's ddd website including restaurant name,
// address, location, and foodnetwork biography url, and insert this information into a database.
function scrape(){
    // Open database connection.
    $db = mysqli_connect(SQL_HOST, SQL_USER, SQL_PASSWORD, SQL_DATABASE)
        or die("Error connecting to MySQL server.");

    // Traverse all available pages on the foodnetwork's ddd restaurant list website.
    // id is used to index the restaurants as they appear on the foodnetwork website.
    $id             = 1;
    $max_page_index = get_max_page_index();
    for($page_index = 1; $page_index <= $max_page_index; $page_index++){
        // Get the html from the page.
        $url  = "http://www.foodnetwork.com/restaurants/shows/diners-drive-ins-and-dives/a-z/p/" . $page_index;
        $html = file_get_html($url);
        
        // Get the restaurant results and retrieve their information.
        foreach ($html->find("div[class=m-MediaBlock o-Capsule__m-MediaBlock]") as $candidate){
            // A candidate element has restaurant data if it has a child h3 tag.
            if($candidate->find("h3")){
                // Retrieve restaurant information.
                $name     = $candidate -> find("span", 0) -> plaintext;
                $url      = str_replace("//", "", $candidate -> find("a", 0) -> href);
                $address  = trim(str_replace("&nbsp;", " ", $candidate -> find("div[class=m-Info__a-Address]",0) -> plaintext));
                $location = get_location($address);
                $lat      = $location["lat"];
                $lng      = $location["lng"];

                // Build SQL queries to insert restaurant information into a database.
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

// MAIN
scrape();

?>
