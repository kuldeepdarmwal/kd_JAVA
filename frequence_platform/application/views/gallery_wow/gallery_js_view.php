<script src="/bootstrap/assets/js/bootstrap-tab.js"></script>
<script src="/libraries/external/isotope/jquery.isotope.min.js"></script>

	<script>

	function add_tile(existing_tiles,new_tile){

		return existing_tiles+ '<div class="item '+new_tile.feature_classes+'">\
								<a href="'+new_tile.ref+'" target="_blank">\
									<div class="thumb_wrapper">\
										<div class="view view-tenth">\
											<img id="'+new_tile.v_id+'" src="'+new_tile.open_uri+'"  />\
											<div class="mask">\
												<img class="viewcreative" src="//www.vantagelocal.com/tech/gallery_wow/vc_button.png" />\
											</div>\
										</div>\
										<div class="clrfix"></div>\
										<span class="ad_title">'+new_tile.name+'</span>\
									</div>\
								</a>\
								</div>';
	}
	
		$( document ).ready(function() {

			var $container = $('#gallery_div');
			$container.isotope({
				itemSelector: '.item'
			});


			var gallery_adsets = eval(<?php echo $tiles_blob;?>);
			//console.log(gallery_adsets);
			var this_tile = 0;
			var timer; 

			var tiles_per_load = 2;
			var this_load = '';

			function load_tile(){
				this_load = add_tile(this_load,gallery_adsets[this_tile]);
				if(this_tile+1==gallery_adsets.length){
					$container.isotope( 'insert', $(this_load) );
					$container.isotope( 'reLayout' );
					clearInterval(timer);
					//console.log('done',this_tile);
				}else{
					
					if((this_tile+1)%tiles_per_load==0){//
						$container.isotope( 'insert', $(this_load) );
						this_load = '';
					}
					
					this_tile++;
				}
				
			}

			timer = setInterval(function(){if(gallery_adsets !== null)load_tile();},100);

			//makes isotope filtering work
			$('#filters a').click(function(){
				var selector = $(this).attr('data-filter');
				$container.isotope({ filter: selector });
				return false;
			});

			//makes tab buttons work
			$('.feature_pills a').click(function (e) {
				e.preventDefault();
				$(this).tab('show');
			});

		
			

		});
</script>