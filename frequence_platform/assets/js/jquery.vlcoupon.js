/**
 * VLCoupon.js
 * all site related javascript commands 
 */


$(document).ready(function(){

// Admin Dashboard : Campaign Creation Form ================================
// show/hide coupon action checkboxes depending upon "Coupon type" selection
// =========================================================================
$('select[name="coupon-types"]').change(function(){
	if($(this).val() != 1){
		$('#cnt-coupon-actions').fadeOut();
	}else if($('#cnt-coupon-actions').not(':visible')){
		$('#cnt-coupon-actions').fadeIn();
	}
});

// Admin Dashboard : Campaign Listing =====================================
// Completely AJAX based Campaign listing / Commands for jQgrid
// ========================================================================
        
  $("#campaign-list").jqGrid({
    url:CI.base_url+'admin/coupon_available',
    datatype: 'json',
    mtype: 'POST',
    colNames:['Business Name','Campaign Name', 'Creation Date','Actions'],
    colModel :[ 
      {name:'business_name', index:'business_name', width:150}, 
      {name:'campaign_name', index:'campaign_name', width:150}, 
      {name:'created', index:'created', width:120, align:'center'}, 
      {name:'actions', index:'actions', width:280, align:'center',sortable:false}
    ],
    pager: '#pager',
    rowNum:10,
    scrollOffset : 0 ,
    rowList:[5,10,15],
    sortname: 'created',
    sortorder: 'desc',
    viewrecords: true,
    gridview: true,
  });
  // set grid's height to available page area
  $("#campaign-list").setGridHeight(918,true);
  
  //jQuery based pagination for coupon generation page
  $('ul#coupon-list').easyPaginate({step:12});
  
  // A hint for Coupon mail from address
  $('#coupon-mail-from').val('name <mail@domain.com>')
                  .css('color','silver')
                  .focus(function(){
                     if( $.trim($(this).val().toLowerCase()) =='name <mail@domain.com>'){
                         $(this).val('').css('color','black');
                     } 
                  })
                  .blur(function(){
                     if($.trim($(this).val()) == ''){
                         $(this).val('name <mail@domain.com>').css('color','silver');
                     }
                  });   
  
  // AJAXify Campaign creation form
  $('form#campaign-create').ajaxify() ;
});


// Admin Dashboard : Action buttons =================================================
// Action confirmation dialog & pop-up ( dynamically bind as elements are created )
// ==================================================================================

$(document).delegate(
    '#campaign-list .jqgrow .action-buttons li a', 'click', 
    function () { 
       // determine clicked button
       var elm = $(this) ;
       var action = elm.attr('id').split('-')[1] ;
       var id = elm.attr('id').split('-')[2] ;
       
       switch(action){
           case 'delete': var confirmed = confirm('Are you sure to delete this campaign ?') ;
                          if(confirmed){
                              // disable row's display & reload grid
                               $.post(CI.base_url+'admin/campaign_delete',{'id' : id},function(data){
                                 elm.html('deleted') ;
                                 // reload jQgrid
                                $("#campaign-list").trigger("reloadGrid",[{current:true}]);
                              },'json');
                          }
                  break ;
           case 'pause':  var confirmed = confirm('Are you sure to '+ elm.text().toLowerCase() +' this campaign ?') ;
                          if(confirmed){
                              // Pause campaign & notify user
                              $.post(CI.base_url+'admin/campaign_toggle',{'id' : id},function(data){
                                 elm.html(data.next_op) ;
                              },'json');
                          }
                  break ;
           case 'view': 
				  return true ;
                  break ;
           case 'link': var campaign_view = window.open(elm.attr('href'),'_blank','width=600,height=400') ;
                  break ;
           case 'metrics': var campaign_view = window.open(elm.attr('href'),'_blank','width=1000,height=600') ;
                  break ;
       }
       

    return false ; 
});