<!-- find-nearest.php - 03/19/2018-->
<?php
    // config.php defines all constants begginning with SQL_*
    include "config.php";

    // Open database connection.
    $db = mysqli_connect(SQL_HOST, SQL_USER, SQL_PASSWORD, SQL_DATABASE)
            or die("Error connecting to MySQL server.");

    // Retrieve user position from http post.
    $user_lat = $_POST['lat'];
    $user_lng = $_POST['lng'];

    // Build query that retrieves 5 nearest restaurants to the user from database.
    $result_limit = "5";
    // hav_distance uses the haversine distance formula to compute the distance between the user and a given restaurant from the database.
    // Distance is measured in kilometers (see 12742km used as the diameter of earth).
    $hav_distance = "12742 * ASIN(SQRT(POW(SIN((RADIANS(latitude - " . $user_lat . "))/2),2) + COS(RADIANS(latitude)) * COS(RADIANS(" . $user_lat . ")) * POW(SIN((RADIANS(longitude - " . $user_lng . "))/2),2)))";
    $query        = "SELECT *, " . $hav_distance . " as distance FROM " . SQL_TABLE . " ORDER BY distance LIMIT " . $result_limit;
    $result       = mysqli_query($db, $query) or die("Error querying database.");

    // Return results from query.
    while ($row = mysqli_fetch_array($result)) {
        echo $row["id"] . " " . $row["name"] . " " . $row["latitude"] . " " . $row["longitude"] . " " . $row["address"] . " " . $row["url"] . "<br />";
    }

    // Close database connection.
    mysqli_close($db);
?>
