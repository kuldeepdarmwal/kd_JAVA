import os, psutil, signal, sys, time, datetime, requests, socket

def kill_processes(pids):
    print "GONNA KILL: ", pids
    for pid in pids:
        try:
            print "KILLING ", pid
            os.kill(pid, signal.SIGKILL)
        except:
            print sys.exc_info()[0]
            print "FAILED KILL"

if len(sys.argv) > 2:
    print "USAGE python av_process_check.py [optional: number of hours threshold]"
    exit()



hours = 5
if len(sys.argv) == 2:
    if not sys.argv[1].isdigit():
        print "USAGE python av_process_check.py [optional: number of hours threshold (int >= 0)]"
        exit()
    hours = int(sys.argv[1])



threshold = 60*60*hours
print threshold
murder = []
kill_job_times = []
jobs_to_kill = 0
for proc in psutil.process_iter():
    if (proc.name == "python") and ("/home/adverify/bin/ad_verify_prod.py" in proc.cmdline) and (time.time() - proc.create_time > threshold):
        print proc.pid, ' - ', proc.name, ' @ ', proc.cmdline
        print proc.parent.pid, ' - ', proc.parent.name
        if proc.parent.name == 'sh':
            murder.append(proc.parent.pid)
        murder.append(proc.pid)
        if proc.parent.name != 'python':
            jobs_to_kill += 1
            kill_job_times.append(proc.create_time)
        for child_to_murder in proc.get_children(recursive=True):
            murder.append(child_to_murder.pid)



print murder
if len(murder) > 1:
    kill_processes(murder)
    subject_text = "Ad Verify: "+str(jobs_to_kill)+" Screenshot bot(s) killed on "+socket.gethostname()+" ("+time.strftime('%X %x %Z')+")"
    body_text =  "===AD VERIFY JOBS STOPPED===\n"
    body_text += " --TIME: "+time.strftime('%X %x %Z')+"---\n"
    body_text += "Server: "+socket.gethostname()+"\n\n"
    body_text += "Jobs stopped: "+ str(jobs_to_kill)+"\n"
    for job_killed in kill_job_times:
        body_text += "---- Job Started at "+str(datetime.datetime.fromtimestamp(job_killed).strftime("%m/%d %H:%M"))+"\n"
    body_text += "\nProcesses Killed: "+ str(len(murder))


    print requests.post(
    "https://api.mailgun.net/v2/mg.brandcdn.com/messages",
    auth=("api", "key-1bsoo8wav8mfihe11j30qj602snztfe4"),
    data={"from": "Ad Verify Bot-Killer  <tech-logs@vantagelocal.com>",
          "to": ["tech-logs@vantagelocal.com"],
          "subject": subject_text,
          "text": body_text})




