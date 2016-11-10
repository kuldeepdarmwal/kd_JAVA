 $(document).ready(function(){
        $('a.setting').click(function(){
               //alert("hello");
                if($("div#user-pop-up").css('top') == '47px'){
                         $("div#user-pop-up").css("z-index", "0");
                         $("div#user-pop-up").animate({ top: "-100px" });
                } else {
                 $("div#header").css("z-index", "1000");
                 $("div#user-pop-up").css("z-index", "100");
                 $("div#user-pop-up").animate({ top: "47px" });
               }
       });
         $('a#close_button').click(function(){
               $("div#header").css("z-index", "1000");
                 $("div#user-pop-up").css("z-index", "100");
                 $("div#user-pop-up").animate({ top: "-100px" });
       });
});
 
