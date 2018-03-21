// main.js - 03/21/2018

// Retrieve closest restaurants to the user's position and ouput them.
function positionSuccess(position){
    // Write user longitude and latitude to position_data payload.
    var positionData = {
        lat: position.coords.latitude,
        lng: position.coords.longitude
    }
    
    // Pass position_data payload to find-nearest.
    $.post("find-nearest.php", positionData, function(response){
        // Output the response of find-nearest.
        document.write(response);
    });

    return;
}

// Handle an error with the navigator.geolocation.getCurrentPosition method.
function positionError(error){
    console.log(error.message);

    return;
}

// Create geolocation options payload to optimize geolocation.
// High accuracy disabled, timeout - 10 seconds, use cached locations up to 5 mins old.
var positionOptions = {
    enableHighAccuracy: false,
    timeout:            10 * 1000,
    age:                5 * 60 * 1000 
}

// Output the closest restaurants to the user.
function getClosestRestaurants(){
    // If geolocation is supported:
    if(navigator.geolocation){
        // This method can have a lengthy response time, consider defaulting to geoip if problem persists.
        navigator.geolocation.getCurrentPosition(positionSuccess, positionError, positionOptions);
    }else{
        console.log("Geolocation not supported.");
    }
    
    return;
}

// Ideally, window.onload would be the only function in main.js 
// and getClosestRestaurants is exported from get-nearest.js
window.onload = function(){
    getClosestRestaurants();
}
