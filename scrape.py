# scrape.py
import requests
from BeautifulSoup import BeautifulSoup

#--Create Page URLs--
# Each page url is composed of the 'baseURL' followed by an index value.
baseURL = "http://www.foodnetwork.com/restaurants/shows/diners-drive-ins-and-dives/a-z/p/"
pages = []

# Hard-coding the maximum page number is a bad idea -- current maximum page number is 65. 
# The number of pages will change over time. Find another method.
for i in range(1,10):
    pages.append(baseURL + str(i))

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
    candidateTags = soup.findAll('div', attrs={"class": "m-MediaBlock o-Capsule__m-MediaBlock"})
    restaurantInfo = []

    for tag in candidateTags:
        # Only candidateTags with 'h3' header tags hold restaurant data.
        if tag.find('h3'):
            header  = tag.find('h3', attrs={"class": "m-MediaBlock__a-Headline"})
            name    = header.span.text
            # The link string comes formatted: "//www...". It is formatted to "www...".
            link    = header.a.get('href').replace("//",'')
            # The address string comes formatted: "...[State]&nbsp;[Zipcode]". It is formatted to "...[State] [Zipcode]".
            address = tag.find('div', attrs={"class": "m-Info__a-Address"}).text.replace('&nbsp;',' ') 
            restaurantInfo.append((name, link, address))

    return restaurantInfo

# --Get All Restaurant Data--
restaurants = []

# Access, parse, and scrape each page.
for page in pages:
    soup         = makeSoup(page)
    restaurants += getRestaurantData(soup)

# Print restaurant data.
for restaurant in restaurants:
    print restaurant










