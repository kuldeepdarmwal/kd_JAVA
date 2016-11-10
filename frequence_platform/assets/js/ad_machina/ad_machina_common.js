/*global window, document, $, mixpanel */

/*
 * @param string markup
 * @param string title optional (default '')
 * @param boolean closable optional (default true)
 */
function showFeedback(markup, title, closable)
{
	var modal, titleElement, contentElement;
	modal = $('.modal-feedback');
	if(!modal.length)
	{
		modal = $('<div class="modal-feedback"><div class="container"><div class="title"></div><div class="content"></div></div></div>').appendTo(document.body);
	}

	titleElement = modal.find('.title');
	titleElement.html(title || '');

	contentElement = modal.find('.content');
	contentElement.html(markup);

	modal.fadeIn();

	if(closable !== false)
	{
		modal.on('click', function(event) {
			// close if clicking on the modal BG, and not the container
			if(modal.is(event.target))
			{
				hideFeedback();
			}
		});
	}
}

function hideFeedback()
{
	$('.modal-feedback').fadeOut();
}

/*
 * @param string text
 * @param string title optional (default '')
 * @param boolean should_enable_subselection optional (default true)
 */
function showTextToCopy(text, title, should_enable_subselection)
{
	var instructions = '<p class="instructions"><span class="keyboard_key">Ctrl</span>+<span class="keyboard_key">C</span> to copy (<span class="keyboard_key">&#8984;</span>+<span class="keyboard_key">C</span> for Mac)</p>';
	showFeedback('<textarea class="copy_text_field">' + text + '</textarea>' + instructions, title);
	var text_field = $('.modal-feedback .copy_text_field');

	text_field.select();
	if(should_enable_subselection === false)
	{
		text_field.on('click', function() {
			this.select();
		})
		text_field.on('blur', hideFeedback);
	}
}

function handleAjaxResponse(raw_result, successHandler, errorHandler)
{
	var response;

	errorHandler = errorHandler || function() {
		showFeedback('There was an error processing your request. Please try again in a moment.');
		setTimeout(hideFeedback, 5000);
	};

	if(typeof raw_result === 'object')
	{
		response = raw_result
	}
	else
	{
		try {
			response = JSON.parse(raw_result);
		} catch(error) {
			response = {
				is_success: false,
				error: error
			};
		}
	}

	if(response.is_success)
	{
		successHandler(response);
	}
	else
	{
		if(response.message == 'Not authorized')
		{
			showFeedback('You are not authorized to perform this action. Redirecting you in a moment.');
			setTimeout(function() {
				window.location.reload();
			}, 3000);
		}
		else
		{
			errorHandler(response);
		}
	}
}

window.onload = function() {
	mixpanel && mixpanel.track("Page load");
};
