					<?php
					foreach($videos as $video)
					{
						echo "<li class=\"spot_video\""
							. "data-video-id=\"{$video['video_creative_id']}\""
							. "data-mp4=\"{$video['link_mp4']}\""
							. "data-webm=\"{$video['link_webm']}\">"
								. "<img src=\"{$video['link_thumb']}\">"
								. "<label class=\"\">{$video['name']}</label>"
							. "</li>\n";
					}
					?>
