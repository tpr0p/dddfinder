<!-- find-nearest.php -->
<!-- Print all restaurants from database.-->
<?php
    include("config.inc");

    // Open database connection.
    $db = mysqli_connect($sql_host, $sql_user, $sql_password, $sql_db)
            or die("Error connecting to MySQL server.");

    $me_lat = $_POST['lat'];
    $me_lng = $_POST['lng'];

    // Query restaurants from database.
    $target_table  = "restaurants";
    $result_limit  = "5";
    $hav_distance  = "12742 * ASIN(SQRT(POW(SIN((RADIANS(latitude -" . $me_lat . "))/2),2) + COS(RADIANS(latitude)) * COS(RADIANS(" . $me_lat . ")) * POW(SIN((RADIANS(longitude - " . $me_lng . "))/2),2)))";
    $query         = "SELECT *, " . $hav_distance . " as distance FROM " . $target_table . " ORDER BY distance LIMIT " . $result_limit;
    $result        = mysqli_query($db, $query) or die("Error querying database.");

    // Print results from query.
    while ($row = mysqli_fetch_array($result)) {
        echo $row["id"] . " " . $row["name"] . " " . $row["latitude"] . " " . $row["longitude"] . " " . $row["address"] . " " . $row["url"] . "<br />";
    }

    // Close database connection.
    mysqli_close($db);
?>