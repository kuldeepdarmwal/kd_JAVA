(function($){
    
/**
 * Submits Campaign Creation form using AJAX
 * 
 */
  
    $(document).ready(function(){
        
       $.fn.ajaxify = function(){
           var form = $(this) ;
           var _do = true ;
           var _with = 'ajax' ;
           
           
           // Field Validation Object
           var is = {
               filled : function(_elm){
                        if($.trim(_elm.val()) == ''){
                            return false ;
                        }
                        return true ;
               },
               num : function(_elm){
                        if(!$.isNumeric(_elm.val())){
                            return false ;
                        }
                        return true ;
               },
			   int : function(_elm){
						var p = new RegExp(/^([0-9])+$/);
                        if(p.test(_elm.val())){
                              return true ;	
                        }
                        return false ;
               },
               of_length : function(_elm,len){
                          if(_elm.val().length > len){
                              return false ;
                          }
                          return true ;
               },
                in_range : function(_elm,max){
						  if(_elm.val() > max){
                              return false ;
                          }
                          return true ;
		},
                email : function(_elm){
                        // customised mail pattern
			var p = new RegExp(/^([\w-_ ]+)\<([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?\>$/);
                        // var p = new RegExp(/^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/);
			if(p.test(_elm.val())){
                              return true ;
                          }
                       return false ;
		},
		test : function(_elm,cond){
                           
		           if(_elm.val() == cond.when){
                               
                                   if(cond.min){
                                   // checks for minimum number of checkbox needed to be selected
                                        var checked_num = $('".'+cond.elm+':checked"').length;
                                        if(checked_num < cond.min){
                                            return {'passed':false ,'type':'min'} ;
                                        }  
                                   }
                                   if(cond.either_or){
                                     // to enforce a either or condition in a checkbox list
                                       var checked =  $('".'+cond.elm+':checked"') ;
                                       var either_or = cond.either_or.split('|') ;
                                       var _occurence = 0 ;
                                       $.each(checked,function(i,v){
                                           if(cond.either_or.indexOf($(this).val()) != -1 ){
                                               _occurence += 1 ;
                                           }
                                       });
                                       if(_occurence>1){ return {'passed':false ,'type':'either_or'} ;}
                                   }   
			   }
                           return {'passed':true} ;
		}
                
           }
           
           // Holds all fields that are to be validated
           // make parameterized later
           var hints = ['sm name <some1@someplace.com>'] ;
           var check_for = {
               reqd : ['campaign-name','business-name','coupon-title','coupon-desc','coupon-discount','coupon-validity','coupon-max','coupon-mail-from','coupon-mail-msg'],
               mail : ['coupon-mail-from'] ,
               num : [],
               length : {
                   'coupon-validity':3
                   ,'coupon-max':7
               },
		   max : {
			       //'coupon-discount':100
		   },
		   int : ['coupon-validity','coupon-max'] ,
		   dependency : {
		        'coupon-types':{
				         'when' : '1',
					 'elm' : 'action-check',
					 'min' : 1 ,
                                         'either_or' : '1|4'
				},
		   }
               // is_ignore : true // determines whether it's a include or ignore type
           };
           
           var _msg = Array();  // for error messages 
           
           $('#' + form.attr('id') + ' input:submit').click(function(){
              
                form.children().each(function(){
                   var _elm =     $(this) ; 
                   var _elmName = _elm.attr('name') ; 
                   if(typeof _elmName != 'undefined'){
                   if(_elm.hasClass('error-field')){_elm.removeClass('error-field'); }    
                      var _clnName = _elm.prev('label').text() ; // here we know that it's a label 
                      
                   // hint cleaning
                  if(_elm.val() == 'name <mail@domain.com>'){_elm.val('');} 

                   if($.inArray(_elmName,check_for.reqd) != -1 || check_for.dependency[_elmName]){
                       
                       if(!is.filled(_elm)){
                          // 
                           _elm.addClass('error-field') ;
                          _do = false ;
                          _msg.push('Please fill in '+_clnName) ;
                       }

                      if(is.filled(_elm) && $.inArray(_elmName,check_for.num) != -1){
                               if(!is.num(_elm)){
                                        _elm.addClass('error-field') ;
                                        _do = false ;
                                        _msg.push(_clnName + ' is not a number' ) ;
                               }       
                                   
                      }
                      if(is.filled(_elm) && $.inArray(_elmName,check_for.int) != -1){
                               if(!is.int(_elm)){
                                        _elm.addClass('error-field') ;
                                        _do = false ;
                                        _msg.push(_clnName + ' is not a integer number' ) ;
                               }       
                                   
                      }
                     if(is.filled(_elm) && $.inArray(_elmName,check_for.mail) != -1){
                               if(!is.email(_elm)){
                                        _elm.addClass('error-field') ;
                                        _do = false ;
                                        _msg.push(_clnName + ' is not a valid mail') ;
                               }       
                                   
                      }
                      if(is.filled(_elm) && check_for.length[_elmName]){
                               var size = check_for.length[_elmName] ;
                               if(!is.of_length(_elm,size)){
                                         _elm.addClass('error-field') ;
                                        _do = false ;
                                        _msg.push(_clnName + ' should have ' +size + ' digit max' ) ;
                               }                    
                      }
                      if(is.filled(_elm) && check_for.max[_elmName]){
                               var max = check_for.max[_elmName] ;
                               if(!is.in_range(_elm,max)){
                                         _elm.addClass('error-field') ;
                                        _do = false ;
                                        _msg.push(_clnName + ' should have maximum ' + max + ' as value' ) ;
                               }                    
                      }
                      
                      // a little complicated and custom tailored method 
                      if(check_for.dependency[_elmName]){
                               var cond = check_for.dependency[_elmName] ;
                               if(!is.test(_elm,cond).passed){
                                   var test_type = is.test(_elm,cond).type ;
                                         _elm.addClass('error-field') ;
                                        _do = false ;
                                        if(test_type == 'min'){
                                             _msg.push(_clnName + ' should have minimum ' + cond.min + ' action(s)' ) ;
                                        }
                                        if(test_type == 'either_or'){
                                             _msg.push('Choose either FB Share / Like as a claim method') ;
                                        }
                                       
                               }                    
                      }
                   } 
                }
                   
                });
                
               // Data Submission
               // If there's no error ....
               if(_do){
                   // or a ajax request
                   if(_with == 'ajax'){
                       var data = form.serialize(); // short-cut ;)
                       $.post(CI.base_url+'admin/campaign_create',
                                        data,
                                        function(data){
                                            
                                            //action to take on data availability
                                             $("#campaign-list").trigger("reloadGrid",[{current:true}]);
                                             if(data.usecode_csv != null){
                                                 // reset form's state'
                                                 form[0].reset() ; 
                                                 $('input:file').val(''); 
                                                 $('#form-errors').html('');  
                                                 $('#coupon-mail-from').
                                                     val('name <mail@domain.com>').
                                                     css('color','silver').focus(function(){$(this).css('color','black')});//reset hint
                                                 $('#cnt-coupon-actions').show() ;
                                                  $('#file-error').html('').css('color','grey') ;
                                                  $('form#file_upload_form #progress').css("background-color","grey");
                                                
                                                var download_win = window.open(data.usecode_csv,'_blank','width=600,height=400,scrollbars=1');
                                                 $('html, body').animate({scrollTop:0}, 'slow');
                                                 }
                                        },'json');
                       return false ;
                   }else{
                       form.submit() ;
                   }
               }else{
                   // display errors depending upon error style ( inline , yet to be built )
                   // 
                   // iterate error array
                   if($('#form-errors').length == 0){
                       form.prepend('<ul id="form-errors"></ul>') ;
                   }else{
                      $('#form-errors').html('');
                   }
                   
                   $('#form-errors').css({
                       'list-style-type' : 'none',
                       'margin' : '0',
                       'padding' : '0',
                       'text-transform': 'normal !impo',
                       'font-size' : '9px',
                       'font-style' : 'italic',
                       'color' : 'red'
                       
                   });
                   $.each(_msg,function(index,value){
                       $('#form-errors').append('<li>'+value+'</li>') ;
                   });
                   _msg = Array() ;
                   _do = true ;
                   return false ;
               }
           });
           
       }
        
    });  
})(jQuery);
