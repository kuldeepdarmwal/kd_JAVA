;(function(window, document){
	"use strict";

	function EmbedIo(container, token, resolve, reject) {
		document.getElementById(container).innerHTML = '<iframe src="' + token + '" height="100%" width="100%" id="embed_io"></iframe>';
		this.waitForSubmit().then(
			function(res) {
				resolve(res);
			},
			function(err) {
				reject(err);
			}
		);
	}

	EmbedIo.prototype = {

		waitForSubmit: function() {
			return new Promise(function(resolve, reject) {
				window.addEventListener("message", function(e) {
					if (e.data.frequence)
					{
						if (e.data.success) {
							resolve(e.data.submission_id);
						}
						else
						{
							reject(e.data.error);
						}
					}
				});
			});
		}

	}

	window.embedIo = function(container, token, resolve, reject) {
		return new EmbedIo(container, token, resolve, reject);
	};

}(window, document));