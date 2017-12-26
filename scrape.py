# scrape.py
import config
import pymysql.cursors
import googlemaps
import requests
from BeautifulSoup import BeautifulSoup

#--Configuration--
gmaps = googlemaps.Client(key = config.GMAPS_API_KEY)
mysql = pymysql.connect(host = config.MYSQL_HOST,
                             user = config.MYSQL_USERNAME ,
                             password = config.MYSQL_PASSWORD,
                             db = config.MYSQL_DATABASE,
                             charset='utf8mb4',
                             cursorclass=pymysql.cursors.DictCursor)

#--Address to GPS Coordinates--
def addressToGPS(address):
    # 'gmaps.geocode()' returns data in JSON format.
    locationData = gmaps.geocode(address)
    location     = locationData[0]['geometry']['location']
    latitude     = location['lat']
    longitude    = location['lng']
    return (latitude, longitude)

#--Access and Parse Webpage-- 
# Access time for url and html parsing is slow. One possible cause is advertisements on the foodnetwork webpages.
def makeSoup(url):
    response = requests.get(url)
    html     = response.content
    return     BeautifulSoup(html)

#--Scrape Restaurant Data--
def getRestaurantData(soup):
    # Search time for 'candidateTags' is slow.
    # One possible solution is to search for candidates within their parent tag as opposed to the entire html document.
    candidateTags  = soup.findAll('div', attrs = {"class": "m-MediaBlock o-Capsule__m-MediaBlock"})
    # A list to hold useful restaurant data.
    restaurantData = []
    # Assign an index to compare restaurants by the order in which they appear. Restaurant names are not distinct.
    index = 1

    for tag in candidateTags:
        # Only candidateTags with 'h3' header tags hold restaurant data.
        if tag.find('h3'):
            header   = tag.find('h3', attrs = {"class": "m-MediaBlock__a-Headline"})
            name     = header.span.text
            # The url string comes formatted: "//www...". It is formatted to "www...".
            url      = header.a.get('href').replace("//",'')
            # The address string comes formatted: "...[State]&nbsp;[Zipcode]". It is formatted to "...[State] [Zipcode]".
            address  = tag.find('div', attrs = {"class": "m-Info__a-Address"}).text.replace('&nbsp;',' ')
            # Location of the restaurant given in GPS coordinates
            location = addressToGPS(address)
            restaurantData.append((index, name, location, address, url))
            index += 1

    return restaurantData

# --Get All Restaurant Data--
# Create Page URLs
# Each page url is composed of the 'baseURL' followed by an unsigned, sequential integer index value -- currently 1 - 66 inclusive.
baseURL = "http://www.foodnetwork.com/restaurants/shows/diners-drive-ins-and-dives/a-z/p/"
pages   = []
# Hard-coding the maximum page number is a bad idea.
# The number of pages will change over time. Find another method.
for i in range(1, 2):
    pages.append(baseURL + str(i))

# Access, parse, and scrape each page.
restaurantData = []
for page in pages:
    soup            = makeSoup(page)
    restaurantData += getRestaurantData(soup)

#--Store Restaurant Data in Database--
for restaurant in restaurantData:
    identifier = str(restaurant[0])
    name       = restaurant[1]
    lat        = str(restaurant[2][0])
    lng        = str(restaurant[2][1])
    address    = restaurant[3]
    url        = restaurant[4]
    cursor     = mysql.cursor()
    addInfo    = "(id, name, latitude, longitude, address, url) VALUES (" + identifier + ", \"" + name + "\", " + lat + ", " + lng + ", \"" + address + "\", \"" + url + "\")"
    # updateInfo modifies all fields except for 'id'
    updateInfo = "name=\"" + name + "\", latitude=" + lat + ", longitude=" + lng + ", address=\"" + address + "\", url=\"" + url  + "\""
    # If the restaurant is not already in the table (i.e. its id is not present in the table) then add it to the table.
    # If the restaurant exists, update its information.
    query  = "INSERT INTO restaurants " + addInfo + "ON DUPLICATE KEY UPDATE " + updateInfo
    cursor.execute(query)
    mysql.commit()
    cursor.close()