<?php //print"<pre>";print_r($encoded_data['rows']); 
?>
<!--<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">-->
<!--<html xmlns="http://www.w3.org/1999/xhtml">
        <head>
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
                <title>New Web Project</title>
-->
                <style>
                        .campaignHeader{ height:140px; overflow-y:scroll;width:100%; }
                        table tr td table{ border:1px solid #666; table-layout:fixed;}
                        table tr td table tr th{color:#fff;font-size:10px; table-layout:fixed;}
                        table tr td table tr td{border-bottom:1px solid #666;font-size:8px; color: #000;word-wrap: break-word;
white-space: normal; table-layout:fixed;}
                        .blue{background-color:blue;}
                        .red{background-color:red;}
                        .orange{background-color:orange;}
                        .campaignHeader table td{font-size:10px;max-width:12px;word-wrap:break-word;}
                        .campaignHeader table th{font-size:9px;max-width:12px;}
                        .noborder{border:0px;width:3px;}
                        .width15{}
                </style>
   <!--     </head>
        <body> -->
				<div class="campaignHeader">
					
				<table width="650" border="1" cellspacing="0" cellpadding="0">
                                <tr>
                                               <th colspan="3" border="0"></th>
                                                <th class="noborder"></th>

                                                <th  colspan="3">Monthly</th>
                                                <th class="noborder"></th>
                                                 <th  colspan="3">Forthnightly</th>
                                                <th class="noborder"></th>
                                                <th  colspan="3">Weekly</th>
                                                <th class="noborder"></th>
                                                 <th  colspan="3">Daily</th>
                                                <th class="noborder"></th>
                                        </tr>

                        <tr>
                                        
                                                <th bgcolor="#666" class="width15" >Advertiser</th>
                                                <th bgcolor="#666" class="width15">Campaign</th>
                                                <th bgcolor="#666">Rtg?</th>
                                                <th class="noborder"></th>

                                                <th bgcolor="#666">TARGET</th>
                                                <th bgcolor="#666">ACTUAL</th>
                                                <th bgcolor="#666">eCPM</th>
                                                <th class="noborder"></th>
                                                 <th bgcolor="#666">TARGET</th>
                                                <th bgcolor="#666">ACTUAL</th>
                                                <th bgcolor="#666">eCPM</th>
                                                <th class="noborder"></th>
                                        
                                                <th bgcolor="#666">TARGET</th>
                                                <th bgcolor="#666">ACTUAL</th>
                                                <th bgcolor="#666">eCPM</th>
                                                <th class="noborder"></th>
                                                <th bgcolor="#666">TARGET</th>
                                                <th bgcolor="#666">ACTUAL</th>
                                                <th bgcolor="#666">eCPM</th>
                                        </tr>
                                       

                                        
                                        <?php foreach($encoded_data['rows'] as $key=>$val){?>
                                        <tr id="<?php print $val['cell']['1']."::".$val['cell']['0'];?>" onclick="campaign(this.id);">

                                                <td class="width15"><?php print $val['cell']['1'];?></td>
                                                <td class="width15"><?php print $val['cell']['0'];?></td>
                                                <?php if($val['cell']['3']==0){$rtgclass="red";}
                                                      if($val['cell']['3']>0){$rtgclass="orange";}
                                                      if($val['cell']['3']>99){$rtgclass="";}?>
                                                <td  class="<?php print $rtgclass;?>"><?php print $val['cell']['2'];?></td>
                                                <td class="noborder"></td>

                                                <td ><?php print $val['monthly']['0'];?></td>
                                                <?php if($val['monthly']['0']>$val['monthly']['1'])
                                                      {$actual="red";}
                                                       elseif($val['monthly']['1']>1.5*$val['monthly']['0'])
                                                      {$actual="blue";}
                                                       else{$actual="";}
                                                       ?>
                                                <td  class="<?php print $actual;?>"><?php print $val['monthly']['1'];?></td>
                                                <td  class="<?php print $val['monthly']['3'];?>"><?php print $val['monthly']['2'];?></td>
                                                <td class="noborder"></td>
                                                <td ><?php print $val['forthnightly']['0'];?></td>
                                                <?php if($val['forthnightly']['0']>$val['forthnightly']['1'])
                                                      {$actual="red";}
                                                       elseif($val['forthnightly']['1']>1.5*$val['forthnightly']['0'])
                                                      {$actual="blue";}
                                                      else{$actual="";}
                                                      ?>
                                                <td  class="<?php print $actual;?>"><?php print $val['forthnightly']['1'];?></td>
                                                <td class="<?php print $val['forthnightly']['3'];?>"><?php print $val['forthnightly']['2'];?></td>
                                                <td class="noborder"></td>
                                                <td ><?php print $val['weekly']['0'];?></td>
                                                <?php if($val['weekly']['0']>$val['weekly']['1'])
                                                      {$actual="red";}
                                                       elseif($val['weekly']['1']>1.5*$val['weekly']['0'])
                                                      {$actual="blue";}
                                                       else{$actual="";}?>
                                                <td  class="<?php print $actual;?>"><?php print $val['weekly']['1'];?></td>
                                                <td  class="<?php print $val['weekly']['3'];?>"><?php print $val['weekly']['2'];?></td>
                                       
                                                <td class="noborder"></td>
                                                <td ><?php print $val['daily']['0'];?></td>
                                                 <?php if($val['daily']['0']>$val['daily']['1'])
                                                      {$actual="red";}
                                                       elseif($val['daily']['1']>1.5*$val['daily']['0'])
                                                      {$actual="blue";}
                                                      else{$actual="";}?>
                                                <td  class="<?php print $actual;?>"><?php print $val['daily']['1'];?></td>
                                                <td  class="<?php print $val['daily']['3'];?>"><?php print $val['daily']['2'];?></td>
                                        </tr>
                                        <?php } ?>

                </table>
                </div>
                <?php
                $date = date("Y-m-d",strtotime('-2 day'));
                $oneMonthAgo = date("Y-m-d",strtotime ( '-1 month' , strtotime ( $date ) )) ;

                ?>
                <div style="margin-top:20px;">
                     <div style="float:left; margin-left:20px;">DATES BETWEEN<input type="text" value="<?php print $oneMonthAgo;?>" id="date1" name="date1"></input></div>
                     <div style="float:left;margin-left:20px;">AND<input type="text" value="<?php print $date;?>" id="date2" name="date2"></input></div>
                     <div style="float:left;margin-left:20px;"><input type="button" value="Generate" onClick="detailstable();"></input></div>
                </div>
                <div id="campaignname" style="display:none;"></div>
                <div style="clear:both;"></div>
                <div id="detailstable" style="margin-top:10px;"></div>
                <div id="detailsgraph" style="margin-top:10px;"></div>
                <div id="top5" style="margin-top:10px;"></div>
                <div id="top5cities" style="margin-top:10px;"></div>
<!--        </body>
</html>-->

