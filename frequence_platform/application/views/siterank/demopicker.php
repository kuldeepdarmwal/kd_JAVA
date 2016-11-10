<?php
function spit_out_checkbox_pod($label_copy,$array_name, $checkbox_name, $js){
    $string = "";
    for ($i = 0; $i<count($label_copy);$i++){
       $string .= form_checkbox( $array_name,$checkbox_name, TRUE, $js);
       $string .= $label_copy[$i]."<br>"; 
    }
    return $string;                                                 
}

?>


<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<title>siterank</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link rel="stylesheet" type="text/css" href="/css/siterank.css" />
</head>



<?php 
$this->load->library('javascript');
$this->load->helper('form');

$gender_labels= array("Male","Female");
$age_labels = array("Under 18","18-24","25-34","35-44","45-54","55-64","65 Plus");
$ethnic_labels = array("Cauc","Afr Amer","Asian","Hisp","Other");
$parent_labels = array("No Kids","Has Kids");
$income_labels = array("Less Than $50k","$50-100k","$100-150k","$150k Plus");
$education_labels = array("No College","College","Grad School");

$gender_tags = array("MALE","FEMALE");
$age_tags = array("UNDER_18","18_24","25_34","35_44","45_54","55_64","65_PLUS");
$ethnic_tags = array("CAUC","AFR_AMER","ASIAN","HISP","OTHER");
$parent_tags = array("NO_KIDS","HAS_KIDS");
$income_tags = array("LESS_THAN_50k","50_100k","100_150k","150k_PLUS");
$education_tags = array("NO_COLLEGE","COLLEGE","GRAD_SCHOOL");

$js = 'onClick="anyClickScript()"';
$slider_js = 'onchange="anyClickScript()"';

?>

<body>
	<div class="container">
		<form name="selectionGroup">
			<div class="pod-container">
                            <h3>Select Your Audience:</h3>
                            <div class="pod1">
                                <?php echo spit_out_checkbox_pod($gender_labels,'include', $gender_tags, $js); ?>

                                <?php echo spit_out_checkbox_pod($age_labels,'include', $age_tags, $js); ?>
                            </div>
                            <div class ="pod2">
                                <?php echo spit_out_checkbox_pod($income_labels,'include', $income_tags, $js); ?>

                                <?php echo spit_out_checkbox_pod($education_labels,'include', $education_tags, $js); ?>
                            </div>
                            <div class ="pod3">
                                <?php echo spit_out_checkbox_pod($parent_labels,'include', $parent_tags, $js); ?>

                                <?php echo spit_out_checkbox_pod($ethnic_labels,'include', $ethnic_tags, $js); ?>
                            </div>
                        </div>
               </form>
        </div>

<div class="container">
    <div class="container"><h3>Dial in Campaign Focus:   <form name="slider_form">Reach<input name="slider" type="range" value="2.5" min="0" max="5" step="0.000001"<?php echo $slider_js;?> />Frequency</form></h3>
    </div>
    
    <div id="siterankresult"></div>
	<script type="text/javascript">
            function findTickedElements(){
            var tickedElements = new Array;
            var selectionString = "";
                for (i=0; i<document.selectionGroup.include.length; i++){
                    if (document.selectionGroup.include[i].checked==true){
                        selectionString = selectionString+"1";
                    }	
                    else{
                        selectionString = selectionString+"0";
                    }
                    if (i!=document.selectionGroup.include.length-1){
                        selectionString = selectionString+"-";
                    }
                }
                return selectionString+"-"+document.slider_form.slider.value;
            }


            function anyClickScript(){
                var checkBoxResult = findTickedElements();
		var xmlhttp = new XMLHttpRequest();
		xmlhttp.open("GET", "siterank/"+checkBoxResult, false);
		xmlhttp.send();
		document.getElementById("siterankresult").innerHTML=xmlhttp.responseText;	
            }

            anyClickScript();
	</script>
    </div> 
</body>
</html>
