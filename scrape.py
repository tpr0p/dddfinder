# scrape.py
import config
import json
import googlemaps
import requests
from BeautifulSoup import BeautifulSoup

#--Address to GPS Coordinates--
gmaps = googlemaps.Client(key = config.GMAPS_API_KEY)

def addressToGPS(address):
    # 'locationData' is in JSON format.
    locationData = gmaps.geocode(address)
    location     = locationData[0]['geometry']['location']
    latitude     = location['lat']
    longitutde   = location['lng']
    return (latitude, longitutde)

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
    candidateTags  = soup.findAll('div', attrs={"class": "m-MediaBlock o-Capsule__m-MediaBlock"})
    restaurantData = []

    for tag in candidateTags:
        # Only candidateTags with 'h3' header tags hold restaurant data.
        if tag.find('h3'):
            header   = tag.find('h3', attrs={"class": "m-MediaBlock__a-Headline"})
            name     = header.span.text
            # The url string comes formatted: "//www...". It is formatted to "www...".
            url      = header.a.get('href').replace("//",'')
            # The address string comes formatted: "...[State]&nbsp;[Zipcode]". It is formatted to "...[State] [Zipcode]".
            address  = tag.find('div', attrs={"class": "m-Info__a-Address"}).text.replace('&nbsp;',' ')
            # Location of the restaurant given in GPS coordinates
            location = addressToGPS(address)
            restaurantData.append((name, address, location, url))

    return restaurantData

# --Get All Restaurant Data--
# Create Page URLs
# Each page url is composed of the 'baseURL' followed by an integer index value -- currently 1 - 65 inclusive.
baseURL = "http://www.foodnetwork.com/restaurants/shows/diners-drive-ins-and-dives/a-z/p/"
pages   = []
# Hard-coding the maximum page number is a bad idea.
# The number of pages will change over time. Find another method.
for i in range(1, 2):
    pages.append(baseURL + str(i))

restaurantData = []
# Access, parse, and scrape each page.
for page in pages:
    soup            = makeSoup(page)
    restaurantData += getRestaurantData(soup)

# Print restaurant data.
for restaurant in restaurantData:
    print restaurant