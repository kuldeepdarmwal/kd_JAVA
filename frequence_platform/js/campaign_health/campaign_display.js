// // Define a class like this
// function Person(name, gender){

//    // Add object properties like this
//    this.name = name;
//    this.gender = gender;
// }

// // Add methods like this.  All Person objects will be able to invoke this
// Person.prototype.speak = function(){
//     alert("Howdy, my name is" + this.name);
// }

// // Instantiate new objects with 'new'
// var person = new Person("Bob", "M");

// // Invoke methods like this
// person.speak(); // alerts "Howdy, my name is Bob"


//jQuery.parseJSON('[{\"id\":\"335\",\"partner\":\"Charter Media KMA West\",\"advertiser\":\"Anytime Fitness\",\"c_name\":\"Get Fit Now\",\"retargeting\":\"1\",\"target\":\"90\",\"end_date\":\"2013-03-31\",\"is_month_end_type\":\"0\"}
function Campaign(c_id, partner, advertiser, name, retargeting, target, end_date, is_month_end_type ){
	this.c_id = c_id;
	this.partner = partner;
	this.advertiser = advertiser;
	this.c_name = c_name;
	this.retargeting = retargeting;
	this.c_target = target;
	this.end_date = end_date;
	this.is_month_end_type;
}

Campaign.prototype.get_row = function(partner_vl,row_num){

	row =  '<tr id = "'+(row_num+1)+'" >';
    //Partner
    if(partner_vl){
        row += '<td>'+null_check(this.partner)+'</td>';
    }
    //Advertiser
    row += '<td>'+null_check(this.advertiser)+'</td>';
    //Campaign
    if(partner_vl){
        row += '<td><a <a target="_blank" href="/campaign_setup/'+this.c_id+'">'+null_check(this.c_name)+'</a></td>';
    }else{
        row += '<td>'+null_check(this.c_name)+'</td>';   
    }
    //RTG?
    row += '<td>'+((null_check(this.retargeting)==1)? 'TRUE' : 'FALSE')+'</td>';
    //Target Impressions ('000)
    row += '<td>'+add_commas(null_check(this.c_target))+'k </td>';
    //End Date (ie: hard end date for defined period campaigns)
    row += '<td  >'+null_check(this.end_date)+'</td>';
    
    ///11111111
    //Months Live (float)
    row += '<td>'+(Math.round(100*cycles_live)/100).toFixed(2)+'</td>';////
    //Start Date (the first day an impression showed up for this campaign)
    row += '<td>'+displ_start_date+'</td>';
    //Bill Date (the next monthly anniversary for the campaign or if a defined period campaign, it's the hard end date)
    row += '<td>'+displ_bill_date+'</td>';
    
    if(partner_vl){
        //Site Weight (the % that the heaviest site makes up in the recent month's impressions)'
        row += '<td>'+displ_single_site_weight+'</td>';
        //Total Impressions (Lifetime all impressions)
        row += '<td>'+displ_delivered_imprs+'</td>';
    }
    //lifetime OTI
    row += '<td>'+displ_lt_oti+'</td>';
    if(partner_vl){
        //Cycle Impressions (total impressions between last reset date and report date (if defined period campaign, equal to Total Impressions)
        row += '<td>'+displ_cycle_imprs+'</td>';
    
        //cycle OTI
        row += '<td>'+displ_cycle_oti+'</td>';
    
        //Cycle Target (daily kImpressions to get to target for this billing cycle)
        row += '<td>'+displ_cycle_target+'</td>';
        //Y'day Realized (Total campaign impressions for report_date)
        row += '<td >'+displ_yday_realized+'</td>';
        //LT Target (for normal recurring campaign, this is target impressions / 28, for defined period campaigns it's target/expected period
        row += '<td>'+displ_long_term_target+'</td>';
    }
    //this is the button to show the charts
    row += '<td><div class="btn-group">'; 
    //row +=  '<a class="btn btn-mini btn-success"  title="view reports" onclick="show_detail_id(\''+campaign.id+'\',\''+one_month_ago+'\',\''+report_date+'\',\''+displ_advertiser+'-'+displ_campaign_name+'\');"><i class="icon-signal icon-white"></i></a>';
    row +=  '<a class="btn btn-mini btn-success"   href="#myModal" data-toggle="modal" onclick="show_detail_modal(\''+campaign.id+'\',\''+one_month_ago+'\',\''+report_date+'\',\''+null_check(campaign.advertiserElem)+'-'+null_check(campaign.c_name)+'\');"><i class="icon-signal icon-white"></i></a>';
    if(partner_vl){
        row +=  '<a class="btn btn-mini btn-inverse" onclick="remove_from_healthcheck(\''+campaign.c_id+'\','+(row_num+1)+');" title="Remove"><i class="icon-trash icon-white"></i></a>';
    }
    row += '</div></td>';
    //row += '<td><div class="" onclick="show_detail_id(\''+campaign.id+'\',\''+one_month_ago+'\',\''+report_date+'\',\''+displ_advertiser+'-'+displ_campaign_name+'\');"><img class="resize" src="/images/campaign_health/chart.png" alt="Detail" ></div></td>';
    row += '</tr>';


}