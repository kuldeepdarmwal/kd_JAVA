<html>
<head>
<script type="text/javascript" src="/bootstrap/assets/js/bootstrap.min.js"></script>
<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
<title>Revenue Admin</title>

<style>
#rcorners3 {
    border-radius: 25px;
    border: 2px solid #8AC007;
    padding: 20px; 
    width: 1300px;
}
input[type='text'] {
    font-size:10px;
}
 
</style>
<script src="http://code.highcharts.com/highcharts.js"></script>
<script src="http://code.highcharts.com/modules/exporting.js"></script>
<body >
<div class="container container-body" style='font-size:10px'>
    <div id="tags_alert_div" class="alert"></div>
<div class="row"><div class="span11 offset1">
        <ul class="nav nav-pills">
          <li ><a href="/campaigns_main/revenue">Revenue</a></li>
          <li class="active"><a href="#">CPM</a></li>
        </ul>
</div>
<div class="row"><div class="span9 offset3">
        <ul class="nav nav-pills">
          <li><a href="/campaigns_main/revenue_cpm?cpm_mode=p">1. Partner CPM</a></li>
          <li><a href="/campaigns_main/revenue_cpm?cpm_mode=a">2. Advertiser CPM</a></li>
          <li><a href="/campaigns_main/revenue_cpm?cpm_mode=c">3. Campaign CPM</a></li>
        </ul>
</div>

    <div class="row-fluid"><div class="span12">
        <a id="save" onclick='save_cpm()' role="button" class="btn btn-success" data-toggle="modal">Save CPM data</a>
<?php
if ($cpm_mode == "p")
{
    echo "<h4>1. Partner CPM</h4>";
    echo "<table class='table table-condensed'><tr><td>Partner</td><td>CPM Display</td><td>CPM Preroll</td><td>Min Impressions</td><td>Display Name</td><td>Sales Term<br>(Net 60 or Net 30)</td><td>Billing Address 1</td><td>Billing Address 2</td><td>Billing City</td><td>Billing Zip</td><td>Billing State</td><td>RateCard?</td></tr>";
    foreach($cpm_data as $row) {
        $checked_flag="";
        if ($row['rate_card_flag'] == "1")
        {
            $checked_flag="checked";
        }
        echo "<tr><td><input type='hidden' id='".$row['id']."' value='".$row['id']."'>".$row['p_name']." (".$row['p_id'].")</td>";//0
        echo "<td><input class='input-mini' placeholder='Display' onchange='track_changed_rows(".$row['id'].")' type=text value='".$row['cpm_display']."' id='".$row['id']."_cpm_display'></td>";//1
        echo "<td><input class='input-mini' placeholder='Preroll' onchange='track_changed_rows(".$row['id'].")' type=text value='".$row['cpm_preroll']."' id='".$row['id']."_cpm_preroll'></td>";//2
        echo "<td><input class='input-mini' placeholder='Min Impr' onchange='track_changed_rows(".$row['id'].")' type=text value='".$row['min_impressions']."' id='".$row['id']."_min_impressions'></td>";//3

        echo "<td><input class='input-mini' placeholder='Display' onchange='track_changed_rows(".$row['id'].")' type=text value='".$row['display_name']."' id='".$row['id']."_display_name'></td>";//4
        echo "<td><input class='input-mini' placeholder='Term' onchange='track_changed_rows(".$row['id'].")' type=text value='".$row['sales_term']."' id='".$row['id']."_sales_term'></td>";//5
        echo "<td><input class='input-mini' placeholder='Addr 1' onchange='track_changed_rows(".$row['id'].")' type=text value='".$row['billing_address_1']."' id='".$row['id']."_billing_address_1'></td>";//6
        echo "<td><input class='input-mini' placeholder='Addr 2' onchange='track_changed_rows(".$row['id'].")' type=text value='".$row['billing_address_2']."' id='".$row['id']."_billing_address_2'></td>";//7
        echo "<td><input class='input-mini' placeholder='City' onchange='track_changed_rows(".$row['id'].")' type=text value='".$row['billing_city']."' id='".$row['id']."_billing_city'></td>";//8
        echo "<td><input class='input-mini' placeholder='Zip' onchange='track_changed_rows(".$row['id'].")' type=text value='".$row['billing_zip']."' id='".$row['id']."_billing_zip'></td>";//9
        echo "<td><input class='input-mini' placeholder='State' onchange='track_changed_rows(".$row['id'].")' type=text value='".$row['billing_state']."' id='".$row['id']."_billing_state'></td>";//10

        echo "<td><input onchange='track_changed_rows(".$row['id'].")' type=checkbox ".$checked_flag." id='".$row['id']."_rate_card_flag'</td></tr>";//11
    }
    echo "</table>";
} 
else if ($cpm_mode == "a")
{
    echo "<h4>2. Advertiser CPM</h4>";
    echo "<table class='table table-condensed'><tr><td>Partner</td><td>Advertiser</td><td>CPM Display</td><td>CPM Preroll</td><td>Min Impressions</td><td>Display Name</td><td>Sales Term<br>(Net 60 or Net 30)</td><td>Billing Address 1</td><td>Billing Address 2</td><td>Billing City</td><td>Billing Zip</td><td>Billing State</td><td>RateCard?</td></tr>";
    foreach($cpm_data as $row) {
        $checked_flag="";
        if ($row['rate_card_flag'] == "1")
        {
            $checked_flag="checked";
        }
        echo "<tr><td><input type='hidden' id='".$row['id']."' value='".$row['id']."'>".$row['p_name']." (".$row['p_id'].")</td>";//0
        echo "<td>".$row['a_name']." (".$row['a_id'].")</td>";
        echo "<td><input class='input-mini' placeholder='Display' onchange='track_changed_rows(".$row['id'].")' type=text value='".$row['cpm_display']."' id='".$row['id']."_cpm_display'></td>";//1
        echo "<td><input class='input-mini' placeholder='Preroll' onchange='track_changed_rows(".$row['id'].")' type=text value='".$row['cpm_preroll']."' id='".$row['id']."_cpm_preroll'></td>";//2
        echo "<td><input class='input-mini' placeholder='Min Impr' onchange='track_changed_rows(".$row['id'].")' type=text value='".$row['min_impressions']."' id='".$row['id']."_min_impressions'></td>";//3

        echo "<td><input class='input-mini' placeholder='Display' onchange='track_changed_rows(".$row['id'].")' type=text value='".$row['display_name']."' id='".$row['id']."_display_name'></td>";//4
        echo "<td><input class='input-mini' placeholder='Term' onchange='track_changed_rows(".$row['id'].")' type=text value='".$row['sales_term']."' id='".$row['id']."_sales_term'></td>";//5
        echo "<td><input class='input-mini' placeholder='Addr 1' onchange='track_changed_rows(".$row['id'].")' type=text value='".$row['billing_address_1']."' id='".$row['id']."_billing_address_1'></td>";//6
        echo "<td><input class='input-mini' placeholder='Addr 2' onchange='track_changed_rows(".$row['id'].")' type=text value='".$row['billing_address_2']."' id='".$row['id']."_billing_address_2'></td>";//7
        echo "<td><input class='input-mini' placeholder='City' onchange='track_changed_rows(".$row['id'].")' type=text value='".$row['billing_city']."' id='".$row['id']."_billing_city'></td>";//8
        echo "<td><input class='input-mini' placeholder='Zip' onchange='track_changed_rows(".$row['id'].")' type=text value='".$row['billing_zip']."' id='".$row['id']."_billing_zip'></td>";//9
        echo "<td><input class='input-mini' placeholder='State' onchange='track_changed_rows(".$row['id'].")' type=text value='".$row['billing_state']."' id='".$row['id']."_billing_state'></td>";//10

        echo "<td><input onchange='track_changed_rows(".$row['id'].")' type=checkbox ".$checked_flag." id='".$row['id']."_rate_card_flag'</td></tr>";//11
    }
    echo "</table>";
} 
else if ($cpm_mode == "c")
{
    echo "<h4>3. Campaign CPM</h4>";
    echo "<table class='table table-condensed'><tr><td>Partner</td><td>Advertiser</td><td>Campaign</td><td>CPM Display</td><td>CPM Preroll</td><td>Min Impressions</td><td>Display Name</td><td>Sales Term<br>(Net 60 or Net 30)</td><td>Billing Address 1</td><td>Billing Address 2</td><td>Billing City</td><td>Billing Zip</td><td>Billing State</td><td>RateCard?</td></tr>";
    foreach($cpm_data as $row) {
        $checked_flag="";
        if ($row['rate_card_flag'] == "1")
        {
            $checked_flag="checked";
        }
        echo "<tr><td><input type='hidden' id='".$row['id']."' value='".$row['id']."'>".$row['p_name']." (".$row['p_id'].")</td>";//0
        echo "<td>".$row['a_name']." (".$row['a_id'].")</td>";
        echo "<td>".$row['c_name']." (".$row['c_id'].")</td>";
        echo "<td><input class='input-mini' placeholder='Display' onchange='track_changed_rows(".$row['id'].")' type=text value='".$row['cpm_display']."' id='".$row['id']."_cpm_display'></td>";//1
        echo "<td><input class='input-mini' placeholder='Preroll' onchange='track_changed_rows(".$row['id'].")' type=text value='".$row['cpm_preroll']."' id='".$row['id']."_cpm_preroll'></td>";//2
        echo "<td><input class='input-mini' placeholder='Min Impr' onchange='track_changed_rows(".$row['id'].")' type=text value='".$row['min_impressions']."' id='".$row['id']."_min_impressions'></td>";//3

        echo "<td><input class='input-mini' placeholder='Display' onchange='track_changed_rows(".$row['id'].")' type=text value='".$row['display_name']."' id='".$row['id']."_display_name'></td>";//4
        echo "<td><input class='input-mini' placeholder='Term' onchange='track_changed_rows(".$row['id'].")' type=text value='".$row['sales_term']."' id='".$row['id']."_sales_term'></td>";//5
        echo "<td><input class='input-mini' placeholder='Addr 1' onchange='track_changed_rows(".$row['id'].")' type=text value='".$row['billing_address_1']."' id='".$row['id']."_billing_address_1'></td>";//6
        echo "<td><input class='input-mini' placeholder='Addr 2' onchange='track_changed_rows(".$row['id'].")' type=text value='".$row['billing_address_2']."' id='".$row['id']."_billing_address_2'></td>";//7
        echo "<td><input class='input-mini' placeholder='City' onchange='track_changed_rows(".$row['id'].")' type=text value='".$row['billing_city']."' id='".$row['id']."_billing_city'></td>";//8
        echo "<td><input class='input-mini' placeholder='Zip' onchange='track_changed_rows(".$row['id'].")' type=text value='".$row['billing_zip']."' id='".$row['id']."_billing_zip'></td>";//9
        echo "<td><input class='input-mini' placeholder='State' onchange='track_changed_rows(".$row['id'].")' type=text value='".$row['billing_state']."' id='".$row['id']."_billing_state'></td>";//10

        echo "<td><input onchange='track_changed_rows(".$row['id'].")' type=checkbox ".$checked_flag." id='".$row['id']."_rate_card_flag'</td></tr>";//11
    }
    echo "</table>";
}
?>
</div> 
</div> 
</div> 
</body>
<script type="text/javascript">

var changed_rows_ids=new Array();
function track_changed_rows(id)
{
    changed_rows_ids[changed_rows_ids.length]="^"+id+"^";
}

function showoverlay() 
{
    if (document.getElementById('overlay') == undefined) 
    {
        $("body").append("<div id='overlay' style='background-color:#F2F0F2; opacity: 0.8;position:absolute;top:0;left:0;height:400%;width:100%;z-index:999'>Processing...</div>");
    }
}

function hideoverlay() 
{
    if (document.getElementById('overlay') != undefined) 
    {
        $("#overlay").remove();
    }
}

$("#overlay").click(function() 
{
    return false;
});

function save_cpm()
{       
        var post_string="";

        var params = document.getElementsByTagName("input");
        var error_flag=0;
        for (var i=0; i < params.length-11; i=i+12)
        {
            var continue_flag=true;
            for (var j=0; j < changed_rows_ids.length; j++)
            {
                var param_id= "^" + params[i].id + "^";
                if (param_id == changed_rows_ids[j] )
                    continue_flag=false;
            }
            if (continue_flag)
                continue;
           
            if (params[i+11].checked)
                post_string+="1"+"##";
            else
                post_string+="0"+"##";
         
            post_string+=params[i].value+"##"+params[i+1].value+"##"+params[i+2].value+"##"+params[i+3].value+"##"+params[i+4].value+"##"+params[i+5].value+"##"+params[i+6].value+"##"
            +params[i+7].value+"##"+params[i+8].value+"##"+params[i+9].value+"##"+params[i+10].value+"##";
        }

        if (error_flag == 1)
            return;

showoverlay() ;

$.ajax({
            type: "POST",
            url: "/campaigns_main/save_cpm_grid/",
            async: true,
            data: 
            {
                cpm_data: post_string 
            },
            dataType: 'json',
            error: function(msg)
            {
                alert('error');
               
            },
            success: function(data_all)
            {
                show_tags_error("Saved");
                hideoverlay();
            }
            
        });   

}

function show_tags_error(error_msg)
{
    error_msg="<b>"+error_msg+"</b>";
     
    $("#tags_alert_div").html(error_msg);
    $("#tags_alert_div").show();
}

</script>
</html>    