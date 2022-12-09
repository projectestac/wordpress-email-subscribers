(function ($) {
	'use strict';

	/**
	 * All of the code for your public-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

    $(document).ready(function () {

        /**
         * Extend jQuery to convert form into JSON object
         * @returns {{}}
         */
        $.fn.serializeObject = function () {
            var output = {};
            var formData = this.serializeArray();
            $.each(formData, function () {
                var fieldName = this.name;
                var fieldValue = this.value || '';
                var isArrayField = fieldName.slice(-2) === '[]';
                if (isArrayField) {
                    if (output[fieldName]) {
                        output[fieldName].push(fieldValue);
                    } else {
                        output[fieldName] = [fieldValue];
                    }
                } else {
                    output[fieldName] = fieldValue;
                }
            });
            return output;
        };

        /**
         * Handle subscription form submission
         */
        $('.es_ajax_subscription_form').on('submit', function (e) {
            var form = $(this);
            e.preventDefault();
            handleBindFunction(form);
        });

    });

    function handleResponse(response, form) {

        var status = response.status;

        var message_class = 'success';
        if (status === 'ERROR') {
            message_class = 'error';
        }

        var responseText = response['message_text'];
        var messageContainer = $(form).next('.es_subscription_message');
        messageContainer.attr('class', 'es_subscription_message ' + message_class);
        messageContainer.html(responseText);
        var esSuccessEvent = {
            detail: {
                es_response: message_class,
                msg: responseText
            },
            bubbles: true,
            cancelable: true
        };

        $(form).trigger('es_response', [esSuccessEvent]);
    }

    function handleBindFunction(form, is_ig = false) {
        form = $(form);
        var formData = form.serializeObject();
        formData['es'] = 'subscribe';
        formData['action'] = 'es_add_subscriber';
        $.ajax({
            type: 'POST',
            url: es_data.es_ajax_url,
            data: formData,
            dataType: 'json',
            beforeSend: function () {
                form.find('#spinner-image').show();
                form.find('.es_submit_button').attr('disabled', true);
            },
            success: function (response) {
                if (!is_ig) {
                    if (response && typeof response.status !== 'undefined' && response.status === "SUCCESS") {
                        form.slideUp('slow');
                        form.hide();
                    } else {
                        form.find('#spinner-image').hide();
                    }
                }
                form.find('.es_submit_button').attr('disabled', false);
                jQuery(window).trigger('es.send_response', [form, response]);
                handleResponse(response, form);
            },
            error: function (err) {
                form.find('#spinner-image').hide();
                form.find('.es_submit_button').attr('disabled', false);
                console.log(err, 'error');
            },
        });


		return false;
	}

	// Compatibility of ES with IG
	jQuery( window ).on( "init.icegram", function(e, ig) {
		if(typeof ig !== 'undefined' && typeof ig.messages !== 'undefined' ) {
			jQuery('.icegram .es_shortcode_form, .icegram form[data-source="ig-es"]').each(function(i, v){
				jQuery(v).bind('submit', function (e) {
					e.preventDefault();
					e.stopImmediatePropagation();
					var form = $(this);
					handleBindFunction(form, true);
				});
			});
		}
	});

})(jQuery);


