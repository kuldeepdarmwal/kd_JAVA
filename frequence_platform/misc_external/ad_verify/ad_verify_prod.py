from selenium import webdriver
from xvfbwrapper import Xvfb
from urlparse import urlparse
from selenium.webdriver.chrome.options import Options
import time, datetime
from datetime import date, timedelta
import json
import sys
import os, psutil, signal
import random
import threading
from multiprocessing import Manager, Process
import re
import cloudfiles
import MySQLdb as mdb


#Global frame path for frame hierarchy navigation
frame_path = [] 
start = datetime.datetime.now()
lines = [line.strip() for line in open('/home/adverify/bin/prod_creds.txt')]
print lines

db_hostname = lines[0]
db_username = lines[1]
db_password = lines[2]
db_database = lines[3]

#Multiprocessing function to load a site
def load_dat_site(d):
   # global did_not_timeout, seek_site
    browser.execute_script("window.location = '"+seek_site+"';")
    print "SITE LOADED"
    d[0] = True

#Same as a bovem just done with a cookie page instead
def load_dat_cookie(d, cookie):
    # global did_not_timeout, seek_site
    browser.execute_script("window.location = '"+cookie+"';")
    print "SITE LOADED"
    d[0] = True

#Multiprocessing function to restart the browser IT WORKED IN TESTING I SWEAR
def restart_browser(d):
    chrome_ops = Options()
    print "OPTIONS"
    chrome_ops.add_extension("/home/vladmin/Stop-Load_v0.2.1.3.crx")
    print "ABOUT TO LUNCH"
    new_browser = webdriver.Chrome("/home/vladmin/chromedriver")
    print "LUNCHED"
    d[0] = new_browser
    print "DONE"
    
#Check system and grab all pids of chromedriver processes
def get_all_pids():
    procs = []
    for proc in psutil.process_iter():
        if proc.name != 'Xvfb' or proc.name != 'python':
            if proc.name == "chromedriver" or proc.name == 'chrome-sandbox' or proc.name == 'chrome':
                print proc.get_cpu_times()
            procs.append(proc.pid)
    return procs

def get_xvfb_pids():
    chromes = []
    babby_chrome = ""
    for proc in psutil.process_iter():
        if proc.name == "Xvfb":
            print "XVFB: ", proc.pid, " - ",  proc.parent.pid
        if proc.name == "Xvfb" and proc.parent.pid == 1:
            print "PARENT: ", proc.parent.pid
            chromes.append(proc.pid)
    return chromes



#Check system and grab chromedriver pids whose parent process is this script execution
def get_chrome_pids():
    chromes = []
    babby_chrome = ""
    for proc in psutil.process_iter():
        if proc.name == "chromedriver" and proc.parent.pid == os.getpid():
            print "PARENT: ", proc.parent.pid
            chromes.append(proc.pid)
    return chromes

#Get difference of two lists, pretty much
def diff_chrome_pids(first, second):
    return list(set(first) - set(second))

def kill_old_xvfb(pids):
    kill_old_chrome(pids)
    kill_old_chrome(pids)
    kill_old_chrome(pids)
    kill_old_chrome(pids)
    
#Kill a list of pids
def kill_old_chrome(pids):
    print "GONNA KILL: ", pids
    for pid in pids:
        try:
            print "KILLING ", pid
            os.kill(pid, signal.SIGKILL)
        except:
            print sys.exc_info()[0]
            print "FAILED KILL"

#Thread to gather iframes
class get_iframes_thread(threading.Thread):
    def __init__(self):
        threading.Thread.__init__(self)

    def run(self):
        global all_iframes
        all_iframes =  browser.find_elements_by_tag_name("iframe")
        global did_not_timeout
        did_not_timeout = True

#Threading function to load a page. (Depreciated)
class site_load_thread(threading.Thread):
    def __init__(self, site):
        threading.Thread.__init__(self)
        self.site = site

    def run(self):
        browser.execute_script("window.location = '"+self.site+"';")
        print "SITE LOADED"
        global did_not_timeout
        did_not_timeout = True
        
#Threading function to for timeout countdown (Depreciated)        
class site_timeout_thread(threading.Thread):
    def __init__(self):
        threading.Thread.__init__(self)
    
    def run(self):
        time.sleep(15)
        print "TIMEOUT REACHED"
        return True

#Recursive function to find every iframe.#
#(frame we're looking at, browser window, ad key we're looking for)
def frame_check(frame, b_window, ad, r_depth, max_r):
    current = datetime.datetime.now()
    if max_r <= r_depth:
        print "FRAMESSSS"
        return false
    print "CHECK: ", r_depth
    should_screenshot = False
    b_window.switch_to_frame(frame)
    time.sleep(1)
    if ad_check(b_window, "engvlx4ie"):
        print "AAAAAAAAAAAAAAAAAAAAAAA"
        return True
    local_frames = b_window.find_elements_by_tag_name("iframe")
    srcs = []
    for iframe in local_frames:
        srcs.append(iframe.get_attribute("src"))
            
    print " # OF CHILD FRAMES: ", len(local_frames)
  # print srcs
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
    print "ADCHECK"
    print "LOOKING FOR ", ad
    HTML = b_window.page_source
    if ad in HTML:
        print "FOUND IT!"
        return True
    else:
        return False

    file_write("HEY UH WE'RE CHECKING IT THE WRONG WAY\n")
    all_embeds = b_window.find_elements_by_tag_name("embed")
    all_objects =  b_window.find_elements_by_tag_name("object")
    all_embeds = all_embeds + all_objects
    all_scripts = b_window.find_elements_by_tag_name("script")
    print "SCRIPTS: ", len(all_scripts)
    all_embeds = all_embeds + all_scripts
    for an_embed in all_embeds:
        try:
            temp_id = an_embed.get_attribute("vlid")
        except:
            continue
        print "VLID = ", temp_id

        if temp_id == "engvlx4ie0000223rlp":
            print "WELP WE FOUND PEACOCK LOUNGE"
        if ad == temp_id:
            return True
    
    return False
    
#Function called to take screenshots, save them, and then add them to the CDN
def take_screenshot(b_window, path, found_url, target):
    now = datetime.datetime.now()
    parsed = urlparse(found_url)
    filename = target + "_" + parsed.netloc + "_" + now.strftime("%m_%d_%H_%M") + ".png"
    screenshot_location = path + filename
    b_window.save_screenshot(screenshot_location)

    cdn = connect_to_cdn("localbranding", "fadf29c3cfe25170ffabf4898d9de9e4")
    add_file_to_screenshot_container(filename, screenshot_location, cdn)
    db = connect_to_db(db_hostname, db_username, db_password, db_database)
    add_screenshot_to_db(filename, target, found_url, db)

#Function to load a set of cookie-drop pages
def load_cookie(b_window, path):
    for cookie in path:
        #b_window.get(cookie)
        print "GET DAT COOKIE: ", cookie
        manager = Manager()
        d = manager.list([False])
        site_load_process = Process(target=load_dat_cookie, args=(d, cookie))
        site_load_process.start()
        site_load_process.join(60)
        if not d[0]:
            print "JEESH"
        

#Method to determine if the current running process was order to terminate
def found_kill(kill_id):
    return os.path.exists("/home/adverify/public/screenshots/"+kill_id+".kill")

#Method to start a log entry in the database
def log_entry(connection, campaign_id):
    #campaign_id = re.sub("^0+", "", seek_string[9:len(seek_string)-3])
    query = "INSERT INTO ad_verify_records(campaign_id, start_time) VALUES ("+str(campaign_id)+",CURRENT_TIMESTAMP)"
    cur = connection.cursor()
    cur.execute(query)
    return cur.lastrowid
    
#Method to end and timestamp a log entry in the database
def log_exit(connection, file_name, hits):
    query = "UPDATE ad_verify_records SET end_time = CURRENT_TIMESTAMP, hits = "+str(hits)+" WHERE log_file = '"+file_name+"'"
    cur = connection.cursor()
    cur.execute(query)

def log_terminate(connection, row_id):
    query = "UPDATE ad_verify_records SET end_time = CURRENT_TIMESTAMP WHERE id = "+str(row_id)
    cur = connection.cursor()
    cur.execute(query)

def log_site_list(connection, row_id, site_count):
    query = "UPDATE ad_verify_records SET list_size = "+str(site_count)+" WHERE id = "+str(row_id)
    cur = connection.cursor()
    cur.execute(query)

def log_add_log_file(connection, row_id, log_file):
    query = "UPDATE ad_verify_records SET log_file = '"+log_file+"' WHERE id = "+str(row_id)
    cur = connection.cursor()
    cur.execute(query)


#Increments log entry's hit counter
def screenshot_hit(connection, hits, row_id):
    query = "UPDATE ad_verify_records SET hits = "+str(hits)+" WHERE id = "+str(row_id)
    cur = connection.cursor()
    cur.execute(query)

#Uh. Connects to the CDN?
def connect_to_cdn(username, api_key):
    conn = cloudfiles.get_connection(username, api_key)
    return conn

#Method to add the screenshot file to the CDN screenshot container
def add_file_to_screenshot_container(filename, file_path, connection):
   container = connection.get_container('ad_verify_screen_shots')
   temp_obj = container.create_object(filename)
   temp_obj.load_from_filename(file_path)

#Method returns connection object to connect to the database
def connect_to_db(hostname, username, password, database):
    conn = mdb.connect(hostname, username, password, database)
    conn.autocommit(True)
    return conn

#Query to add screenshot to the screenshot table in the database
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

def rebuke_site(target_site, cursor):
    update_page_query = "UPDATE ad_verify_page_data SET search_misses = search_misses+1, last_search_miss = CURRENT_TIMESTAMP WHERE url = '"+target_site+"'"
    cursor.execute(update_page_query)

def page_hit(target_site, cursor):
    update_page_query = "UPDATE ad_verify_page_data SET search_hits = search_hits+1, last_search_hit = CURRENT_TIMESTAMP WHERE url = '"+target_site+"'"
    cursor.execute(update_page_query)

def search_increment(target_site, cursor):
    update_page_query = "UPDATE ad_verify_page_data SET search_attempts = search_attempts+1, last_search_attempt = CURRENT_TIMESTAMP WHERE url = '"+target_site+"'"
    cursor.execute(update_page_query)

def get_campaign_name(campaign_id, cursor):
    get_campaign_details_query = "SELECT c.Name, a.Name FROM Campaigns c JOIN Advertisers a ON (c.business_id = a.id) WHERE c.id = "+str(campaign_id)
    cursor.execute(get_campaign_details_query)
    result = cur.fetchall()
    return result[0][1]+" - "+result[0][0]

def initialize_browser(b_window, cookies):
    file_write.write("Pre-loading cookie\n")
    load_cookie(b_window, cookies)
    time.sleep(60)
    file_write.write("Creating traffic...\n")
    load_cookie(b_window, ["http://www.mlbtraderumors.com"])
    load_cookie(b_window, cookies)
    time.sleep(480)

def write_to_common_log(msg):
    log_filepath = os.getcwd()+ "/av_common_log.txt"
    common_log_write = open(log_filepath, 'a+', 1)
    current_time = datetime.datetime.now()
    common_log_write.write(current_time.strftime("[%Y-%m-%d %H:%M:%S]: ")+msg+"\n")

def select_campaign():
    try:
        print "Querying..."
        cur = db.cursor()
        cur.execute("SELECT campaign_id FROM ad_verify_priority_queue WHERE was_run = 0 ORDER BY id DESC LIMIT 1")
        if(int(cur.rowcount > 0)):
            data = cur.fetchall()
            campaign = data[0][0]
        else:
            cur.execute("SELECT campaign_id from tags WHERE tag_type = 1 AND isActive = 1 AND campaign_id NOT IN (SELECT DISTINCT r.campaign_id FROM ad_verify_records r)") 
            if(int(cur.rowcount) > 0): 
                data = cur.fetchall()
                idx = random.randint(0, int(cur.rowcount)-1)
                campaign = data[idx][0]
            else:    
                print "A"
                cur.execute("SELECT campaign_id, MAX(start_time) FROM ad_verify_records WHERE campaign_id IN (SELECT id FROM Campaigns WHERE ignore_for_healthcheck = 0) AND campaign_id IN (SELECT campaign_id FROM tags WHERE isActive = 1 AND tag_type = 1) GROUP BY campaign_id ORDER BY MAX(start_time) ASC LIMIT 1 ")
                print "B"
                data = cur.fetchall()
                campaign = data[0][0]
    except mdb.Error:
        print "ERROR: Failed to grab a campaign to run on - DB Error"
        write_to_common_log("Failed to select a campaign - Database Error")
        sys.exit(0)
    except:
        print "ERROR: Failed to grab a campaign to run on - Other Error"
        write_to_common_log("Failed to select a campaign - "+ sys.exc_info()[0])
        sys.exit(0)
    return campaign

def clear_queue_of_campaign(connection, campaign_id, log_id):
    query = "UPDATE ad_verify_priority_queue SET was_run = 1, log_id = "+str(log_id)+" WHERE campaign_id = "+str(campaign_id)
    cur = connection.cursor()
    cur.execute(query)


#MAIN ==========================

if len(sys.argv) > 2:
    print "USAGE: python ad_verify.py [JSON DATA]"
    exit()

db = connect_to_db(db_hostname, db_username, db_password, db_database)
print "CONNECTED..."
find = ""
#sites_to_traverse = ["http://www.answers.com/T/Health", "http://www.answers.com/T/Health", "http://www.answers.com/T/Health", "http://www.answers.com/T/Health", "http://www.answers.com/T/entertainment_and_Arts", "http://www.answers.com/T/entertainment_and_Arts", "http://www.answers.com/T/entertainment_and_Arts", "http://www.answers.com/T/entertainment_and_Arts", "http://www.answers.com/T/Technology", "http://www.answers.com/T/Technology", "http://www.answers.com/T/Technology" ,"http://www.answers.com/T/Health", "http://www.answers.com/T/Health", "http://www.answers.com/T/Health", "http://www.answers.com/T/Health", "http://www.answers.com/T/entertainment_and_Arts", "http://www.answers.com/T/entertainment_and_Arts", "http://www.answers.com/T/entertainment_and_Arts", "http://www.answers.com/T/entertainment_and_Arts", "http://www.answers.com/T/Technology", "http://www.answers.com/T/Technology", "http://www.answers.com/T/Technology"]
#sites_two = sites_to_traverse
#sites_to_traverse += sites_two
#sites_to_traverse += sites_two
sites_to_traverse = []
cookie_path = ""
screenshot_path = "/home/adverify/public/adverify.vantagelocalstage.com/screenshots/"
max_runs = 5
max_frame_depth = 15
kill_id = "0"
cur = db.cursor()
encoded_json = ""
campaign = 0
was_specified = False
record_row = 0

if len(sys.argv) == 2:
    
    if len(sys.argv[1]) > 5:
        encoded_json = sys.argv[1]
        decoded_json = json.loads(encoded_json)
    
        find = decoded_json[0]
        sites_to_traverse = decoded_json[2]
        cookie_path = decoded_json[1]
        screenshot_path = decoded_json[3]
        max_runs = int(decoded_json[4])
        max_frame_depth = int(decoded_json[5])
        kill_id = decoded_json[6]
    else:
        campaign = int(sys.argv[1])
    was_specified = True

db = connect_to_db(db_hostname, db_username, db_password, db_database)



if not cookie_path:
    while len(sites_to_traverse) == 0:
        if(was_specified == False):
            campaign = select_campaign()
        record_row = log_entry(db, campaign)
        print "Finding Site For ", campaign
        find =  "engvlx4ie"+("%07d" % (campaign,))+"rlp"
        cookie_path = ["http://adverify.vantagelocalstage.com/cookie_monster.php?id="+str(campaign)]
        
        get_categories_query = "SELECT tag_id FROm ad_verify_campaign_categories WHERE campaign_id = "+str(campaign)
        cur.execute(get_categories_query)
        cat_data = cur.fetchall()
        categories = []
        for cat in cat_data:
            categories.append(int(cat[0]))
        
        print categories
        two_days_ago = date.today() - timedelta(30)
        get_base_sites_query = "SELECT b.Base_site AS Site FROM AdGroups a LEFT JOIN SiteRecords b ON (a.ID = b.AdGroupID) WHERE b.Base_site != 'All other sites' AND a.campaign_id = "+str(campaign)+" AND b.Date > '"+two_days_ago.strftime('%Y-%m-%d')+"' GROUP BY Site"
        cur.execute(get_base_sites_query)
        data = cur.fetchall()
        print "BASE SITES", data
        for site in data:
            print site[0]
            get_site_by_base_site = "SELECT url, id FROM ad_verify_page_data where base_url = '"+site[0]+"'"
            print get_site_by_base_site
            cur.execute(get_site_by_base_site)
            sites = cur.fetchall()
            per_site = 0
         
            for full_url in sites:
                if_category_campaign = "SELECT page_id FROM ad_verify_page_categories WHERE page_id = "+str(full_url[1])+" AND category_id IN (SELECT tag_id FROM ad_verify_campaign_categories WHERE campaign_id = "+str(campaign)+")"
                cur.execute(if_category_campaign)  
                if int(cur.rowcount) > 0:
                    if full_url[0] not in sites_to_traverse:
                        if per_site < 5:
                            sites_to_traverse.append(full_url[0])
                            per_site += 1
            
        print sites_to_traverse
      #  time.sleep(4000)
        
        print "E"
        get_cities_query = "SELECT r.City as city FROM CityRecords r LEFT JOIN AdGroups a ON (a.ID = r.AdGroupID) WHERE campaign_id = "+str(campaign)+" GROUP BY City ORDER BY SUM(Impressions)"
        cur.execute(get_cities_query)
        cities = cur.fetchall()
    #    for city in cities:
    #        print city[0]
    #        get_sites_query = "SELECT d.URL AS Site FROM ad_verify_page_data d LEFT JOIN ad_verify_city_pages c ON (d.id = c.page_id) WHERE c.city = '"+city[0]+"'"
    #        cur.execute(get_sites_query)
    #        city_urls = cur.fetchall()
    #        for url in city_urls:
    #            if url[0] not in sites_to_traverse:
    #                sites_to_traverse.append(url[0])
        
        print sites_to_traverse
    #    time.sleep(4000)
        print campaign

        print find
        print cookie_path
        print sites_to_traverse
        print screenshot_path
        print max_runs
        print "H"
        if len(sites_to_traverse) > 60:
            sites_to_traverse = random.sample(sites_to_traverse, 60)
        
        log_site_list(db,record_row, len(sites_to_traverse))
        
        if len(sites_to_traverse) == 0:
            print "No sites found..."
            if(was_specified):
                print "And that's okay."
                break
            else:
                clear_queue_of_campaign(db, campaign, record_row)
                log_terminate(db, record_row)
                print "And we don't want that. Re-searching"

#exit()


if kill_id == "0":
    kill_id =  str(random.randint(1, 200000)) 

now = datetime.datetime.now()
file_name = find + "_" + now.strftime("%m_%d_%H_%M") + ".txt"
file_write = open('/home/adverify/public/adverify.vantagelocalstage.com/screenshots/logs/'+file_name, 'w', 1)
log_add_log_file(db, record_row, file_name)
clear_queue_of_campaign(db, campaign, record_row)
file_write.write("====AD_VERIFY SCREENSHOT BOT====\n")
if len(encoded_json) > 0:
    file_write.write("MANUALLY FIRED\n")
file_write.write("Search String:"+ find +"\n")
if campaign:
    file_write.write(get_campaign_name(campaign, cur)+"\n")
else:
    file_write.write("")

file_write.write("Kill ID: "+ kill_id +  " ("+str(os.getpid())+")\n")
file_write.write("Screenshot Directory: "+screenshot_path+"\n")
file_write.write("Today, we're visiting \n")
for site in sites_to_traverse:
    file_write.write(site+"\n")
file_write.write("================START================\n\n\n")




vdisplay = Xvfb(width=1600, height=4000)
vdisplay.start()

chrome_options = Options()
chrome_options.add_extension("/home/vladmin/Stop-Load_v0.2.1.3.crx")
file_write.write("Loaded Stop-Load Extension - Starting Chrome\n")
start = get_all_pids()
try:
    browser = webdriver.Chrome("/home/vladmin/chromedriver")
except:
    print "NO CHROME"
    file_write.write("WELP")
time.sleep(1)
to_kill = diff_chrome_pids(get_chrome_pids(), start)
file_write.write("Chrome Initialized.\n")

if(len(sites_to_traverse) > 0):
    initialize_browser(browser, cookie_path)

hits = 0
#while start (max check or screenshot)

cycles = 0
while cycles < 1:
    cycles += 1
    file_write.write("---CYCLE---"+"\n")
    print "---CYCLE---"
    for seek_site in sites_to_traverse:
        print "LET'S TAKE A SWING AT ", seek_site
        time.sleep(2)
        runs = 0
        straws = 0
        while runs < max_runs:
            if seek_site == "":
                sites_to_traverse.remove(seek_site)
                continue
            print seek_site
            if found_kill(kill_id):
                file_write.write("KILL REQUEST FOUND: TERMINATING")
                browser.quit();
                vdisplay.stop();
                xvfb_get_out = get_xvfb_pids()
                kill_old_xvfb(xvfb_get_out)
                exit()
            runs = runs + 1
            load_cookie(browser, cookie_path)
            print "COOKIE LOADED"
            screenshot = False
            file_write.write("["+datetime.datetime.now().strftime("%H:%M:%S")+"] RUN: "+seek_site+" - "+str(runs)+"\n")
            print "ATTEMPTING TO LOAD TARGET SITE", os.getpid()
            #browser.get(seek_site)
            #did_not_timeout = False
            #site_load = site_load_thread(seek_site)
            #site_load.start()
            #time.sleep(15)
            #print did_not_timeout
            #if not did_not_timeout:
            if not browser or browser == "":
                file_write.write("We went in without a Chrome")
                
            manager = Manager()
            d = manager.list([False])
            site_load_process = Process(target=load_dat_site, args=(d,))
            site_load_process.start()
            site_load_process.join(30)
            if not d[0]:
                print "UH OH, WE TIMED OUT"
                file_write.write("ERROR: Timed out while loading page!\n")
                try:
                    straws += 1
                    print "terminating"
                    site_load_process.terminate()
                    print "terminated"
                    #del browser
                    print "getting pids"
                    
                    print "got pids"
                    
                    file_write.write("Restarting Chrome, (PROCESSES OPEN: "+str(len(get_all_pids()))+")\n")
                   # to_kill = diff_chrome_pids(get_chrome_pids(), start)
                    print to_kill
                   # time.sleep(40)
                    kill_old_chrome(to_kill)
                    kill_old_chrome(to_kill)
                    kill_old_chrome(to_kill)
                    kill_old_chrome(to_kill)
                    kill_old_chrome(to_kill)
                    kill_old_chrome(to_kill)
                    kill_old_chrome(to_kill)
                    kill_old_chrome(to_kill)
                    kill_old_chrome(to_kill)
                    kill_old_chrome(to_kill)

                    print "killed a bunch"
                  #  time.sleep(40)
                       # to_kill = diff_chrome_pids(get_chrome_pids(), start)
                    file_write.write("KILLED (PROCESSES OPEN: "+str(len(get_all_pids()))+")\n")
                  #  vdisplay.stop()
                  #  vdisplay = Xvfb(width=1600, height=4000)
                  #  vdisplay.start()

                    chrome_options = Options()
                    print "GONNA CHROME"
                    del browser
                    print "deleted old browser"
                    #b = manager.list([False])
                    #new_browser_process = Process(target=restart_browser, args=(b,))
                    #start = get_all_pids()
                    #new_browser_process.start()
                    #new_browser_process.join(10)
                    #if not b[0]:
                    #    print "DUDE IT'S AWFUL"
                    #    file_write("WHAT")
                    #    continue
#                    else:
                        #    browser = b[0]
                    chrome_options = Options()
                    chrome_options.add_extension("/home/vladmin/Stop-Load_v0.2.1.3.crx")
                    file_write.write("Loaded Stop-Load Extension - Starting Chrome\n")
                    start = get_all_pids()
                    try:
                        print "OPTIONS"
                        print "ABOUT TO LUNCH"
                        browser = ""
                        file_write("Starting a new Chrome\n")
                        browser = webdriver.Chrome("/home/vladmin/chromedriver")
                        file_write("Chrome online\n")
                        print "LUNCHED"
                        browser = new_browser
                    except:
                        print "UH OH"
                        del browser
                        browser = ""
                        new_browser = webdriver.Chrome("/home/vladmin/chromedriver")
                        browser = new_browser
                        
                    to_kill = diff_chrome_pids(get_chrome_pids(), start)
                    initialize_browser(browser, cookie_path)
                    #except:
                     #   continue
                
                    

                except:
                    #NO
                    file_write.write("ERROR: HAD SOME ISSUES STARTING A NEW CHROME\n")
                    time.sleep(3)
                    start = get_all_pids()
                    file_write.write("ATTEMPTING TO SAVE THIS THING...\n")
                    browser = webdriver.Chrome("/home/vladmin/chromedriver")
                    to_kill = diff_chrome_pids(get_chrome_pids(), start)
                    initialize_browser(browser, cookie_path)
                    print "THREAD COULD NOT BE TERMINATED"
                    time.sleep(5)
               
                if straws == 3:
                    print "THAT'S THE LAST STRAW! THIS SITE STINKS!"
                    file_write.write("SITE STINKS! GET OUT! GET OUT NOW! GET TO DA CHOPPAHAAAAHAHAH!")
                    runs = max_runs+1
                    #rebuke_site(seek_site, cur)
                    sites_to_traverse.remove(seek_site)
                    continue

            print "LOADED"
            search_increment(seek_site, cur)
            time.sleep(1)
            did_not_timeout = False
            print "GO TIME!"
            file_write.write("Retrieving Frames\n")
            try:
                get_frames = get_iframes_thread()
                get_frames.start()
            except:
                file_write("Failed to get frames\n")
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
            screenshot = screenshot or ad_check(browser, "engvlx4ie")
          #  screenshot = random.randint(0, 1)
            if screenshot:
                print "SCREENSHOTTING"
                file_write.write("FOUND AD ON: "+seek_site+"\n")
                file_write.write("WRITING SCREENSHOT TO"+screenshot_path+".")
                browser.switch_to_default_content()
                time.sleep(3)
                take_screenshot(browser, screenshot_path, seek_site, find)
                print "SCREENSHOTTED"
                sites_to_traverse.remove(seek_site)
                hits += 1
                screenshot_hit(db, hits, record_row)
                page_hit(seek_site, cur)
                break
            else:
                if not browser:
                    continue
                load_cookie(browser, cookie_path)
                file_write.write("AD NOT FOUND IN "+seek_site+"\n")
                rebuke_site(seek_site, cur)
                if runs == max_runs:
                    sites_to_traverse.remove(seek_site)
            print "WHILE END END END"
        print "FOR END END END"
                
                    

        file_write.write("=NEXT=\n")
        

log_terminate(db, record_row)
file_write.write("\n===WE'RE DONE HERE===");
file_write.close()
if browser:
    try:
        browser.quit()
    except:
        print "WELP"

vdisplay.stop()
xvfb_get_out = get_xvfb_pids()
kill_old_xvfb(xvfb_get_out)

exit()
