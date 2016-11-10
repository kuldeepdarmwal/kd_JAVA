from selenium import webdriver
from xvfbwrapper import Xvfb
from urlparse import urlparse
from selenium.webdriver.chrome.options import Options
import time, datetime
import json
import sys
import os
import random
import threading
import re
import cloudfiles
import MySQLdb as mdb


#Global frame path for frame hierarchy navigation
frame_path = [] 

#Thread object used to get all iframes on a page.
class get_iframes_thread(threading.Thread):
    def __init__(self):
        threading.Thread.__init__(self)

    #Function called when thread starts:
    #sets all_iframes to be an array of all iframes
    def run(self):
        global all_iframes
        all_iframes =  browser.find_elements_by_tag_name("iframe")
        global did_not_timeout
        did_not_timeout = True

#Thread object used to load the page considering the possibility  of a timeout.
class site_load_thread(threading.Thread):
    def __init__(self, site):
        threading.Thread.__init__(self)
        self.site = site

    #Use javascipt to load then new site. Set a flag when it's done.
    def run(self):
        browser.execute_script("window.location = '"+self.site+"';")
        print "SITE LOADED"
        global did_not_timeout
        did_not_timeout = True
        
#Thread object used to timeout in the event of a hang-up (hopefully)
class site_timeout_thread(threading.Thread):
    def __init__(self):
        threading.Thread.__init__(self)
    
    def run(self):
        time.sleep(15)
        print "TIMEOUT REACHED"
        return True

#Recursive function to find every iframe.
#(frame we're looking at, browser window, ad key we're looking for)
def frame_check(frame, b_window, ad, r_depth, max_r):
    if max_r <= r_depth: #In the event of the bot hitting the maximum frame depth
        return False
    print "CHECK: ", r_depth
    should_screenshot = False
    b_window.switch_to_frame(frame)
    time.sleep(1)
    if ad_check(b_window, ad): #If we find the ad in the current frame
        print "AAAAAAAAAAAAAAAAAAAAAAA"
        return True
    local_frames = b_window.find_elements_by_tag_name("iframe") #Gather all iframe tags
    srcs = [] 
    for iframe in local_frames: #Get each src for each iframe
        srcs.append(iframe.get_attribute("src"))
            
    print " # OF CHILD FRAMES: ", len(local_frames)
    if len(local_frames) > 0:
        print "FOUND ", len(local_frames), "FRAMES"
        for t_frame in local_frames:
            print "CHECKING FRAME ", t_frame.get_attribute("id"), " - ", t_frame.get_attribute("width"),"x",t_frame.get_attribute("height")
            frame_path.append(t_frame)
            found = frame_check(t_frame, b_window, ad, r_depth+1, max_r)
            should_screenshot = should_screenshot or found
            print "INNER: ", should_screenshot, " - ", found
            frame_path.pop()
            if should_screenshot:
                print "BAIL OUT"
                return True
            if reality_check(b_window):
                print "BACK IT UP"

    return should_screenshot

#function to bring the browser frame back up one parent frame
#(browser window we're working with)
def reality_check(b_window):
    try:
        b_window.switch_to_default_content()
        for target_frame in frame_path:
            b_window.switch_to_frame(target_frame)
        return True
    except:
        print "UH OOOOOOOOOOH"
        return False
    return True
    
#function to return true or false if ad key is gound in given source
#(source of a frame from frame_check, ad key)
def ad_check(b_window, ad):
    print "LOOKING FOR ", ad
    all_embeds = b_window.find_elements_by_tag_name("embed")
    all_objects =  b_window.find_elements_by_tag_name("object")
    all_embeds = all_embeds + all_objects
    for an_embed in all_embeds:
        temp_id = an_embed.get_attribute("vlid")
        if not temp_id:
            continue
        print temp_id
       # if ad == str.split(temp_id, '-')[0]:
       #     return True
        if temp_id == "engvlx4ie0000223rlp":
            print "WELP WE FOUND PEACOCK LOUNGE"
        if ad == temp_id:
            return True
    
    return False
    
#Function that just plain takes a screenshot:
#Saves screenshot with a timestamp on the name, uploads to CDN and 
#updates database with relevant information.
#(browser window, path to save screenshot, url we found it at, search string we were looking for)
def take_screenshot(b_window, path, found_url, target):
    now = datetime.datetime.now()
    parsed = urlparse(found_url)
    filename = target + "_" + parsed.netloc + "_" + now.strftime("%m_%d_%H_%M") + ".png"
    screenshot_path = path + filename
    b_window.save_screenshot(screenshot_path)

    cdn = connect_to_cdn("localbranding", "fadf29c3cfe25170ffabf4898d9de9e4")
    add_file_to_screenshot_container(filename, screenshot_path, cdn)
    db = connect_to_db("db.vantagelocaldev.com", "vldevuser", "L0cal1s1n!", "vantagelocal_dev")
    add_screenshot_to_db(filename, target, found_url, db)

#Function that loads the list of cookie sites in one line instead of 2.
#(Browser window, list of cookie URLs)
def load_cookie(b_window, path):
    for site in path:
        b_window.get(site)
        print "GET DAT COOKIE: ", site

#Function that determines if a kill command was sent by someone
#Returns true if the killswitch file was found.
def found_kill(kill_id):
    return os.path.exists("/home/adverify/public/screenshots/"+kill_id+".kill")

#connect_to_cdn. Connects to CDN. Huh.
#(username for CDN, API Key, for CDN)
def connect_to_cdn(username, api_key):
    conn = cloudfiles.get_connection(username, api_key)
    return conn
#Adds file to screenshot container on CDN
#(file name to save, path where file can be found on local machine, CDN connection)
def add_file_to_screenshot_container(filename, file_path, connection):
   container = connection.get_container('ad_verify_screen_shots')
   temp_obj = container.create_object(filename)
   temp_obj.load_from_filename(file_path)

#Uh.
def connect_to_db(hostname, username, password, database):
    conn = mdb.connect(hostname, username, password, database)
    return conn

#Executes query to add rows to database for screenshot
#(name of file, string it needed to find it, url of site found on, Database connection)
def add_screenshot_to_db(file_name, seek_string, url, connection):
    parsed = urlparse(url)
    base_url = parsed.netloc
    if "www." in base_url:
        base_url = base_url[4:]
    campaign_id = re.sub("^0+", "", seek_string[9:len(seek_string)-3])
    cur = connection.cursor()
    query = "INSERT INTO ad_verify_screen_shots(file_name, creation_date, campaign_id, base_url, full_url) VALUES('"+"//299c36c924711ee3e271-2c4953a04b78dff13599ea5221ce98c2.r6.cf1.rackcdn.com/"+file_name+"', CURRENT_TIMESTAMP, "+str(campaign_id)+", '"+parsed.netloc+"', '"+url+"')"
    print query
    cur.execute(query)    


#MAIN ==========================

#IF YOU GOOFED UP THE COMMAND LINE, YOU GET NOTHING
if len(sys.argv) != 2:
    print "USAGE: python ad_verify.py [JSON DATA]"
    exit()

#Decode JSON data
encoded_json = sys.argv[1]
decoded_json = json.loads(encoded_json)

find = decoded_json[0]
sites_to_traverse = decoded_json[2]
cookie_path = decoded_json[1]
screenshot_path = decoded_json[3]
max_runs = int(decoded_json[4])
max_frame_depth = int(decoded_json[5])
kill_id = decoded_json[6]


#Get current timestamp, open file for log
now = datetime.datetime.now()
file_name = find + "_" + now.strftime("%m_%d_%H_%M") + ".txt"
file_write = open('/home/adverify/public/adverify.vantagelocalstage.com/screenshots/logs/'+file_name, 'w', 1)



file_write.write("====AD_VERIFY SCREENSHOT BOT====\n")
file_write.write("Search String:"+ find +"\n")

#Start virtual frame buffer to house Chrome window
vdisplay = Xvfb(width=1600, height=4000)
vdisplay.start()

#Start chrome, load Stop-Load extension
chrome_options = Options()
chrome_options.add_extension("/home/vladmin/Stop-Load_v0.2.1.3.crx")
file_write.write("Loaded Stop-Load Extension - Starting Chrome\n")
try:
    browser = webdriver.Chrome("/home/vladmin/chromedriver")
except:
    file_write.write("WELP")
file_write.write("Chrome Initialized.\n")

#Load cookies
load_cookie(browser, cookie_path)

#while start (max check or screenshot)
#Will execute until the site list is empty
while len(sites_to_traverse) > 0: 
    file_write.write("---CYCLE---"+"\n")
    print "---CYCLE---"
    #for eachs site: Check if killswtich triggered, then check cookies, then go to the site and check it for the ad
    for seek_site in sites_to_traverse:
        runs = 0
        while runs < max_runs:
            if seek_site == "":
                sites_to_traverse.remove(seek_site)
                continue
            print seek_site
            if found_kill(kill_id):
                file_write.write("KILL REQUEST FOUND: TERMINATING")
                browser.quit();
                vdisplay.stop();
                exit()
            runs = runs + 1
            load_cookie(browser, cookie_path)
            print "COOKIE LOADED"
            screenshot = False
            file_write.write("RUN: "+seek_site+" - "+str(runs)+"\n")
            print "ATTEMPTING TO LOAD TARGET SITE"
            
            #Threading operations to attempt a timeout for pageloading.
            did_not_timeout = False
            site_load = site_load_thread(seek_site)
            site_load.start()
            time.sleep(10)
            print did_not_timeout
            if not did_not_timeout:
                print "UH OH, WE TIMED OUT"
                file_write.write("TIMED OUT \n")
                try:
                    site_load_thread._Thread__stop()
                except:
                    print "THREAD COULD NOT BE TERMINATED"
                    time.sleep(5)
                    continue
                continue

            
            print "LOADED"
            time.sleep(1)
            did_not_timeout = False
            print "GO TIME!"
            get_frames = get_iframes_thread()
            get_frames.start()
            time.sleep(10)
            if not did_not_timeout:
                print "UH OH, WE TIMED OUT"
                file_write.write("TIMED OUT \n")
                try:
                    get_frames._Thread__stop()
                except:
                    print "THREAD COULD NOT BE TERMINATED"
                    time.sleep(5)
                continue


            #all_iframes = browser.find_elements_by_tag_name("iframe")
            print "MAIN PAGE: # OF CHILD FRAMES: ", len(all_iframes)
            srcs = []
            for iframe in all_iframes:
              try:
                  srcs.append(iframe.get_attribute("src"))
              except:
                  print sys.exc_info()
                  continue
            #print srcs
            for iframe in all_iframes:
                try:
                    print "--MAIN: I FRAME--"
                    print "CHECKING FRAME ", iframe.get_attribute("id"), " - ", iframe.get_attribute("width"),"x",iframe.get_attribute("height")
                    rec_depth = 1
                    frame_path.append(iframe)
                    screenshot = screenshot or frame_check(iframe, browser, find, rec_depth, max_frame_depth)
                    frame_path.pop()
                    browser.switch_to_default_content()
                except:
                    print sys.exc_info()
                    continue
            html = browser.page_source
            screenshot = screenshot or ad_check(browser, find)
            if screenshot:
                print "SCREENSHOTTING"
                file_write.write("FOUND AD ON: "+seek_site+"\n")
                file_write.write("WRITING SCREENSHOT TO"+screenshot_path+".")
                browser.switch_to_default_content()
                time.sleep(3)
                take_screenshot(browser, screenshot_path, seek_site, find)
                print "SCREENSHOTTED"
                sites_to_traverse.remove(seek_site)
                break
            else:
                load_cookie(browser, cookie_path)
                file_write.write("AD NOT FOUND IN "+seek_site+"\n")
        sites_to_traverse.remove(seek_site)
       
        
        file_write.write("NEXT")



file_write.close()
browser.quit()
vdisplay.stop()
exit()
