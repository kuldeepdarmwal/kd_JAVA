function site_open()
{
    if(typeof sitewindow!='undefined')
    {
	sitewindow.close();
    }
    var sitedrop = document.getElementById("siteselect");
    var site = sitedrop.options[sitedrop.selectedIndex].value;
    var xmlhttp = new XMLHttpRequest();
    var adset = document.getElementById("rtg-frame").src;
    if(site == 'nytimes.com')
    {
	site += '/pages/national/';
	
    }
    if(adset.indexOf("not_retargeted") != -1)
    {
	xmlhttp.open("GET", "/rtg_demo/dom/"+site+"/not_retargeted", false);
	xmlhttp.send();
    }
    else
    {
	xmlhttp.open("GET", "/rtg_demo/dom/"+site+"/"+adset.substring(adset.indexOf("/ads/")+5, adset.indexOf("/flash")), false);
	xmlhttp.send();
    }
    var response = xmlhttp.responseText;
    sitewindow = window.open("", "sitewindow", "resizable=1,scrollbars=1,width=1000,height=800");
    sitewindow.document.write(response);
}
function ad_open()
{
    if(typeof adwindow!='undefined')
    {
	adwindow.close();
    }
    var addrop = document.getElementById("adsiteselect");
    var ad = addrop.options[addrop.selectedIndex].value.split("|");
    adwindow = window.open(ad[1], "adwindow", "resizable=1,scrollbars=1,width=1000,height=800");
    document.getElementById("rtg-frame").src = "/ring_files/rtg_files/ads/"+ad[0]+"/flash_300x250.swf";    
    
}