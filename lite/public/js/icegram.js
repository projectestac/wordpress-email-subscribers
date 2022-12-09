/**
 * Icegram JS class
 * Initialize, run and manage all messages
 * Public interface
 **/
function Icegram() { 
	// var data, defaults, message_data, messages, map_id_to_index, map_type_to_index, timer, message_template_cache, mode;
	// var tracking_data, powered_by;
}

Icegram.prototype.init = function ( data ) {
	if (data == undefined) {
		return;
	}
	// Pre-init - can allow others to modify message data
	jQuery( window ).trigger( 'preinit.icegram', [ data ] );

	this.data = data;
	this.defaults = jQuery.extend( {}, data.defaults );
	this.message_data = data.messages;
	this.messages, this.tracking_data = [];
	this.message_template_cache = {};
	this.map_id_to_index = {};
	this.map_type_to_index = {};
	this.mode = (window.ig_mode == undefined) ? 'local' : window.ig_mode;
	this.powered_by = { link: 'https://www.icegram.com/?utm_source=inapp&utm_campaign=poweredby&utm_medium=' };
	//this.timer = setInterval( this.timer_tick, 1000 );

	// Add powered by properties
	this.powered_by.text = this.defaults.powered_by_text;
	this.powered_by.logo = this.defaults.powered_by_logo;

	// Add container div for Icegram
	jQuery('body').append('<div id="icegram_messages_container"></div>');
	// Loop over message data and create messages & maps
	var i = 0;
	this.messages = [];
	var self = this;
	if (this.message_data.length > 0) {
		jQuery.each( this.message_data, function ( i, v ) {
			
			try {
				// dont check cookies in preview mode
				// if(window.location.href.indexOf("campaign_preview_id") == -1){
				// 	//check cookies in js 
				// 	if(v['retargeting'] == 'yes' && jQuery.cookie('icegram_campaign_shown_'+v['campaign_id']) == 1){
				// 		return;
				// 	} 
				// 	if(v['retargeting_clicked'] == 'yes' && jQuery.cookie('icegram_campaign_clicked_'+v['campaign_id']) == 1){
				// 		return;
				// 	} 
				// }
				//set delay time of mobile popup as -1
				if(v['ig_mobile_popup'] !== 'undefined' && v['ig_mobile_popup'] == true){
					v['delay_time'] = -1;
				}
				var m = null;
				var classname_suffix = v['type'].split('-').join(' ').ucwords().split(' ').join('_');
		
				if (typeof (window['Icegram_Message_Type_' + classname_suffix]) === 'function') {
					m = new window['Icegram_Message_Type_' + classname_suffix]( v );
				} else {
					m = new Icegram_Message_Type( v );
				}

				self.messages.push( m );
				self.map_id_to_index['_'+v['id'] ] = i;
				self.map_type_to_index[ v['type'] ] = jQuery.isArray(self.map_type_to_index[ v['type'] ]) ? self.map_type_to_index[ v['type'] ] : new Array();
				self.map_type_to_index[ v['type'] ].push(i);
			
			} catch(err) {
				console.log(err);
			}
		});
	}

	// Submit event data on unload and at every 5 seconds...
	jQuery( window ).on('unload', function() {
		if (typeof(window.icegram.submit_tracking_data) === 'function') {
			window.icegram.submit_tracking_data( false );
		}
	} );
	setInterval( function() { 
		if (typeof(window.icegram.submit_tracking_data) === 'function') { 
			window.icegram.submit_tracking_data( true );
		} } , 5 * 1000 );


	// Trigger event for others!
	jQuery( window ).trigger( 'init.icegram', [ this ] );
};


Icegram.prototype.timer_tick = function (  ) {
	
};

// Message template cache - get / set
Icegram.prototype.get_template_fn = function ( type ) {
	return this.message_template_cache[ type ];
};
Icegram.prototype.set_template_fn = function ( type, fn ) {
	this.message_template_cache[ type ] = fn;
};

// Utility functions to get message instances
Icegram.prototype.get_message = function ( index ) {
	if (this.messages.length > index) {
		return this.messages[ index ];
	}
	return undefined;
};

Icegram.prototype.get_message_by_id = function ( id ) {
	if ( this.map_id_to_index.hasOwnProperty( '_'+id )) {
		var index = this.map_id_to_index[ '_'+id ];
		return this.get_message( index );
	}  
	return undefined;
};
Icegram.prototype.get_message_by_campaign_id = function ( ids ) {
	ids = String(ids);
	var campaign_messages = [];
	var ig_messages = this.messages;
	//spilt ids string
	if(ids.indexOf(" ")){
		ids = ids.split(" ");
	}
	jQuery.each(ids,function(i,id){
		jQuery.each( ig_messages ,function(k,v){
			if(id == v.data.campaign_id){
				campaign_messages.push(v);
			}
		});
	});
	return campaign_messages;
};

Icegram.prototype.get_messages_by_type = function ( type ) {
	if ( this.map_type_to_index.hasOwnProperty( type )) {
		var indices = this.map_type_to_index[ type ];
		var matches = [];
		if (jQuery.isArray( indices )) {
			var self = this;
			jQuery.each( indices, function ( i, v ) {
				matches.push( self.get_message( v ) );
			} );
		}
		return matches;
	}  
	return undefined;
};

//Get powered by link
Icegram.prototype.get_powered_by = function ( type ) {
	var res = jQuery.extend( {}, this.powered_by );
	res.link = res.link + (type || '');
	return res;
}


//Event tracking
Icegram.prototype.track = function ( ev, params ) {
	if (typeof(params) === 'object' && params.hasOwnProperty('message_id') && params.hasOwnProperty('campaign_id') && params.message_id.indexOf('_00') == -1 ) {
		jQuery( window ).trigger( 'track.icegram', [ ev, params ] );
		this.tracking_data.push( { 'type': ev, 'params': params} );
	}
}
Icegram.prototype.submit_tracking_data = function ( async ) {
	var protocol = window.location.protocol.split(':');
	var a = protocol[0];
	var protocol1 = this.data.ajax_url.split('://');
	var b = protocol1[0];
	
	if (this.tracking_data.length > 0 && window.location.href.indexOf("campaign_preview_id") == -1 && !this.is_bot()) {
		var params = {
			type: 'POST',
			url: this.data.ajax_url,
			async: (async || false),
			data: {
				action: 'icegram_event_track',
				event_data: JSON.parse(JSON.stringify(this.tracking_data)),
				ig_remote_url: (this.mode == 'remote') ? window.location.href : undefined,
			},
			success: function(data, status, xhr) {
			},
			error: function(data, status, xhr) {
			}
		};
		if (this.mode == 'remote') {
			params['xhrFields'] = { withCredentials: true };
			params['crossDomain'] = true;
			params['async'] = true;
		} else {
			if( a != b ){
				params['xhrFields'] = { withCredentials: true };
				jQuery.extend(params.data, {ig_local_url_cs:  window.location.href });
			}
		}
		
		jQuery.ajax(params);
		this.tracking_data = [];
	}
}

Icegram.prototype.is_bot = function ( ) {
	var bot_list = /bot|spider|crawl|sucker|ia_archiver|alexa|spade|slurp|webbug|ZyBorg|Feedfetcher-Google|Mediapartners-Google|aolserver|seamonkey|binlar|casper|comodo|feedfinder|jakarta|java|larbin|libwww|pycurl|linkwalker|steeler|nutch|turnit|zmeu/i;
    if ( bot_list.test( navigator.userAgent ) ){
    	return true;
    } else {
    	return false;
    }
}

// hide all messages
Icegram.prototype.hide_all_messages = function ( ) {
	if (this.messages.length > 0) {
		jQuery.each(this.messages , function( i, v ){
			v.hide();
		});
	}
}

/**
 * Icegram Message Type - Base class
 **/
function Icegram_Message_Type( data ) {
	
	var data, template, dom_id, el, type, root_container;

	this.root_container = "#icegram_messages_container";
	this.data = data;
	this.type = data.type;
	this.data.delay_time = parseInt(this.data.delay_time);
	//add http:// to link
	if (typeof(this.data.link) === 'string' && this.data.link != '' && !/^tel:/i.test(this.data.link)) {
	    if (!/^https?:\/\//i.test(this.data.link)) {
	    	this.data.link = "http://" + this.data.link;
	    }
    }
	this.set_template( this.get_template_default() );

	jQuery( window ).trigger( 'msg_preinit.icegram', [ this ] );
	this.init();
}

Icegram_Message_Type.prototype.init = function ( ) {
	// Render HTML
	this.render();
	// Add handlers
	this.add_event_handlers();	
};

Icegram_Message_Type.prototype.add_event_handlers = function ( ) {
	this.el.on('click', {self: this}, this.on_click);
	jQuery( window ).on('resize', {self: this}, this.on_resize);
}
// TODO:: add JS animation in this if necessary
Icegram_Message_Type.prototype.animations = {
};

Icegram_Message_Type.prototype.embed_form = function ( ) {
	if(jQuery.inArray(this.data.type, ['toast', 'badge', 'ribbon', 'exit-redirect']) === -1 ){
	    var form_text = null;
		var	form_layout = this.data.form_layout;
		var	form_has_label = this.data.form_has_label;
		var	form_style = this.data.form_style || 'none';
		var	form_button_text = this.data.label || undefined;
		form_style = form_style.toLowerCase().replace(' ', '_');

		if(this.data.use_form != undefined && this.data.use_form == 'yes') {
	    	form_text = this.data.form_html;
		}

		//Check for old form code in message_body
		//If found, process that form
	    if(this.el.find('form.ig_embed_form').length > 0){
	    	var form_old = this.el.find('form.ig_embed_form');
	    	form_layout = 'inline';
	    	// Change form layout handling for messagetype having bottom and inline options only
	    	if(jQuery.inArray(this.data.type, ['messenger', 'tab', 'sidebar', 'interstitial', 'sticky']) === -1 ){
				if(form_old.hasClass('ig_left') ){
			    	form_layout = 'left';
				}else if(form_old.hasClass('ig_right')){
			    	form_layout = 'right';
				}
	    	}
	    	if(form_layout == 'inline'){
	    		this.el.find('form.ig_embed_form').replaceWith('<div class="ig_form_container layout_inline"></div>');
	    	}
	    	form_has_label = (form_old.find('.ig_form_el_group label').length > 0) ? 'yes' : undefined;
			if(form_old.find('.ig_button').length == 0 && form_old.find('button[type=submit]').length == 0){
				form_button_text = form_button_text || 'Submit';
				form_old.append('<input class="ig_button" type="submit" value="' + form_button_text +'">');
			}
	    	form_text = jQuery('<div/>').append(form_old).html();
	    }
		if(form_text == null) {
	        this.el.find('.ig_form_container').remove();
	    }else{
		    var form_code = window.icegram.formProcess(this, form_text);
		    // TODO :: test this properly
			// if(form_code){
			if(form_code && this.el.find('.ig_form_container form').length == 0){
				var form_header = (this.data.form_header != undefined && this.data.form_header != '' ) ? ('<div class="ig_form_header">'+this.data.form_header+'</div>') : '';
				var form_footer = (this.data.form_footer != undefined && this.data.form_footer != '' ) ? ('<div class="ig_form_footer">'+this.data.form_footer+'</div>') : '';
				var form_original_data = jQuery('<div/>').html(form_text);
			   	this.el.find('.ig_form_container')
			   		  	.append(form_header)
			   		  	.append(form_code)
			   		  	.append(form_footer);

	   		  	//Add rainmaker class, form_type class and append success msg to the processed form container
				if(this.data.rainmaker_form_code && this.data.rainmaker_form_code != ''){
					if(!this.data.cta || this.data.cta === 'form' || !this.data.show_response || !this.data.response_text || (this.data.response_text && this.data.response_text == '') ){
						this.data.response_text = form_original_data.find('.rm_form_message').html() || '';
					}
				   	this.el.find('.ig_form_container')
				   		   .addClass(form_original_data.find('.rm_form_container').data('type'))
				   		   .data('form-id', form_original_data.find('.rm_form_container').data('form-id'))
				   		   .addClass('rainmaker_form')
					var rm_script = form_original_data.find('#rm_script');
					var rm_style = form_original_data.find('#rm_style');
					this.el.find('.ig_form_container').prepend(rm_style).append(rm_script);
					
					var rm_captch_div = form_original_data.find('.rm_captcha');
					var rm_error_div = form_original_data.find('.rm_form_error_message');
					// this.el.find('.ig_form_container').prepend(rm_style).append(rm_script).after(rm_captcha);
					
					this.el.find('.ig_form_container form').prepend(rm_error_div).append(rm_captch_div);
				}
				// es form support shortcode
				if(this.el.find('[data-es_form_id = "es_shortcode_form" ]').length > 0){
					this.el.find('[data-es_form_id = "es_shortcode_form" ]').addClass('es_shortcode_form');
					var es_captcha_div = jQuery(form_text).find('.es_captcha');
					this.el.find('.es_shortcode_form').parent().append(es_captcha_div);
					this.el.find('.es_shortcode_form').parent().addClass('es_form_container');
				}
				//es form support direct
				if(this.el.find('form[data-source="ig-es"]').length > 0){
					var es_captcha_div = jQuery(form_text).find('.es_captcha');
					this.el.find('form[data-source="ig-es"] .ig_button').parent().before(es_captcha_div);
					this.el.find('form[data-source="ig-es"] .es_captcha').addClass('ig_form_els');
					this.el.find('form[data-source="ig-es"]').addClass('es_form_container');
				}
				
				//es form remote site support
				var url = new URL(this.el.find('form').attr('action'), window.location.href); //Passing second parameter as the current page url to avoid TypeError in case URL in action attribute is not valid
				var actionparam = url.searchParams.get('action');
				
				if( 'ig_es_external_subscription_form_submission' === actionparam ){
					this.el.find('form').addClass('ig-es-embeded-from es_form_container');
				}
				

				if(form_has_label == undefined ){ 
					this.el.find('.ig_el_label').not('span.ig_el_label').remove();
				}else{
					this.el.find('input, textarea').removeAttr('placeholder');
					this.el.find('select option.ig_el_placeholder').remove();
				}	
			   	if(this.el.find('.ig_form_container .ig_button').length > 0){
			        this.el.find('.ig_button').not('.ig_form_container .ig_button').hide();
					form_button_text = (form_button_text || this.el.find('.ig_button').val()) || 'Submit'; //TODO :: test this
			        this.el.find('.ig_button').val(form_button_text);
		        }
		    	this.el.addClass('ig_form_'+ form_layout);
		    	
		    	var inline_style = '';
		    	if(this.data.form_bg_color != undefined && this.data.form_bg_color != '') {
			    	this.el.find('.ig_form_container').css('background-color', this.data.form_bg_color);
		    		inline_style += '.ig_form_'+ form_layout + '.ig_form_' + form_style + ' .ig_form_container:before{ background-color:' + this.data.form_bg_color + '; border-color:' + this.data.form_bg_color + ';}';
			    }else{
			    	inline_style += '.ig_form_'+ form_layout + '.ig_form_' + form_style + ' .ig_form_container:before{ display:none;}';
			    }
				if(this.data.form_text_color != undefined && this.data.form_text_color != '') {
			    	this.el.find('.ig_form_container').css('color', this.data.form_text_color);
			    }
		    	this.el.addClass('ig_form_'+ form_style).find('.ig_form_container').prepend('<style type="text/css">'+ inline_style +'</style>');
	    		// this.el.find('.ig_form_container.layout_' + form_layout + ' .ig_form_els')
	    		// 	   .first().addClass('ig_form_els_first').end()
	    		// 	   .last().addClass('ig_form_els_last');

		   		jQuery.each( this.el.find('.ig_form_container') || [], function(i, el){
			   		jQuery(el).find('.ig_form_els')
			   			.first().addClass('ig_form_els_first').end()
				   		.last().addClass('ig_form_els_last');
			   });
			}
	    }
	
	}

}


Icegram_Message_Type.prototype.render = function ( ) {

	this.pre_render();

	var html = this.render_template();

	// Add html to DOM, Setup dom_id, el etc.
	try {
		jQuery(this.root_container).append(html);
	} catch ( e ) {}
	
	this.dom_id = 'icegram_message_'+this.data.id;
	this.el = jQuery('#'+this.dom_id);
	this.set_position();

	var pb = window.icegram.get_powered_by( this.type );
	if ( pb.hasOwnProperty('link') && pb.hasOwnProperty('text') && pb.text != '' ) {
		this.add_powered_by( pb );
	}

	// Hide elements if insufficient data...
	if(this.data.headline == undefined || this.data.headline == '') {
        this.el.find('.ig_headline').hide();
    }
    if(this.data.icon == undefined || this.data.icon == '') {
        this.el.addClass('ig_no_icon').find('.ig_icon').remove();
    }
    if(this.data.message == undefined || this.data.message == '') {
        this.el.find('.ig_message').hide();
    }
    if(this.data.label == undefined || this.data.label == '') {
        this.el.find('.ig_button').hide();
    }

    // Form Embed
	this.embed_form();
   
    // Apply colors, if theme defaults in not checked
    if(this.data.use_theme_defaults == undefined || this.data.use_theme_defaults != 'yes'){
        if (this.data.text_color != undefined && this.data.text_color != '') {
        	this.el.css('color', this.data.text_color);
        	this.el.find('.ig_container').css('color', this.data.text_color);
        }
        if (this.data.bg_color != undefined && this.data.bg_color != '') {
        	this.el.css('background-color', this.data.bg_color);
        	this.el.find('.ig_container').css('background-color', this.data.bg_color);
    	}

        if (this.data.cta_bg_color != undefined && this.data.cta_bg_color != '') {
            this.el.find('.ig_button, form input[type="submit"]').css('background-color', this.data.cta_bg_color);
            var bgColorHSL = window.icegram.hexToHsl(this.data.cta_bg_color); // border color based on bg_color
            this.el.find('.ig_button, form input[type="submit"]').css('border-color', "hsl(" + bgColorHSL.h + "," + (bgColorHSL.s-5)  + "%," + (bgColorHSL.l-8) + "%)" );
        }
        if (this.data.cta_text_color != undefined && this.data.cta_text_color != '') {
            this.el.find('.ig_button, form input[type="submit"]').css('color', this.data.cta_text_color);
        }
    }
    
    // Add Custom CSS and js here.
    if(this.data.use_custom_code){
	    if(this.data.custom_css){
	    	// this.el.prepend('<style id="ig_custom_css_'+this.data.id+'" type="text/css">' + this.data.custom_css.replace(/#ig_this_message/g, '#'+this.dom_id).replace(/\.ig_this_message_root/g, '.ig_this_message_root_'+this.data.id) + '</style>');
	    	this.el.prepend('<style id="ig_custom_css_'+this.data.id+'" type="text/css">' + this.data.custom_css.replace(/#ig_this_message/g, '#'+this.dom_id)+ '</style>');
	    }
	    //TODO:: Add provision to replace #ig_this_message with dom_id and/or message_id 
	    // if(this.data.custom_js){
	    // 	jQuery('body').append(this.data.custom_js);
	    // }
	}

	// Hint clickability for buttons / ctas
	if (typeof(this.data.link) === 'string' && this.data.link != '') {
		this.el.parent().find('.ig_cta, .ig_button').css('cursor', 'pointer');
	}
	
	this.post_render();
	// Hide the message by default
	this.hide( {}, true );

	// Set up message display trigger
	this.set_up_show_trigger();
}

//form process on
Icegram.prototype.formProcess = function(self, form_text) {
	var form_tags = jQuery('<div/>')
					.html(form_text)
					.find('input[name="fake_text"]').data('required_field', true).end() // Arforms
					.find('input.rm_required_feild').data('required_field', true).end() // detect Rainmaker Forms honeypot fields here
					.find('.gform_validation_container input').data('required_field', true).end() // detect Gravity Forms honeypot fields here
					.find('.rm_captcha_input').data('keep_class', true).end() // 
					.find('.es_captcha_input').data('keep_class', true).end() // 
					.find('.gdpr-label').data('keep_class', true).end() // keep GDPR class
					.find('.required_field').data('required_field', true).end() // detect Form Craft honeypot fields here
					.find('input, label, select, textarea, button')// Get only these tags from the form
					.not('.rm_captcha_input, .rm_captcha_verify')
					.not('.es_captcha_input, .es_captcha_verify ')
					.not('br');

	if(form_tags.length > 0){
		var form_container = jQuery('<div class="ig_embed_form_container ig_clear_fix"></div>');
		var form_object = jQuery('<div/>')
						.html(form_text)
						.find('form')
						.removeAttr('class')
						.removeAttr('style')
						.addClass('ig_clear_fix')
						.empty();
		
		var label_text = '.';
		var el_count = 0;
		jQuery.each(form_tags, function(i, form_el){
			var el_obj = jQuery(form_el);
			var el_group = jQuery('<div class="ig_form_els"></div>');
				el_obj.removeAttr('style');
				if(!el_obj.data('keep_class')){
					el_obj.removeAttr('class');
				}

			// add all the cases for bot here 
			if(	el_obj.attr('tabindex') == -1 
				|| el_obj.is('*[name*="[abs]"]')
				|| el_obj.data('required_field')
				|| el_obj.attr('name') == 'data[email]'
				|| el_obj.attr('name') == '_mc4wp_required_but_not_really'){
				el_obj.addClass('ig_form_required_field').removeData('required_field');
				el_count--;
			}
			
			//hidden fields
			if( el_obj.attr('type') == 'hidden' || el_obj.prop('disabled') === true){
				el_obj.addClass('ig_form_hidden_field');
				el_count--;
			}

			if(el_obj.is('label')){ // get the label if found.
				//GDPR compatibility
				if(el_obj.attr('class') == 'gdpr-label' || el_obj.find('input[name="es_gdpr_consent"]').length > 0){
					el_obj.find('input[type=checkbox]').remove();
					label_text = el_obj.not('input, select, textarea, button, span, br').html().replace(/\s+/g, ' ') || '.';
				}else{
					label_text = el_obj.not('input, select, textarea, button, span, br').text().replace(/\s+/g, ' ') || '.';
				}
			}else if((el_obj.is('input') || el_obj.is('button') || el_obj.is('textarea') || el_obj.is('select')) && !el_obj.is('input[type=radio]' ) &&  !el_obj.is('input[type=checkbox]' ) ) {
				
				el_obj.removeAttr('id');

				if(el_obj.is('button')){ // if button found convert it to input type="submit"
					var button_text = el_obj.not('br, span, div').text().trim() || '';
					el_obj.remove();
					el_obj = jQuery('<input type="submit" value="' + button_text +'">');
				}

				if((el_obj.is('input[type=submit]') || el_obj.is('input[type=button]') ) && !el_obj.is('.ig_form_hidden_field, .ig_form_required_field')){ // TODO :: check do we need to handle input[type=button]
					el_obj.addClass('ig_button');
				}
				//TODO :: temp additon Test it, in all cases
				if(el_obj.is('input[type=text]') || el_obj.is('input[type=email]') ){
					el_obj.attr('size', 25);
				}
				label_class = 'ig_el_label ig_button_label';
				if(label_text != '.'){
					label_class = 'ig_el_label';
					if(el_obj.is('select')) {
						jQuery('<option class="ig_el_placeholder">' + label_text + '</option>').prependTo(el_obj);
					}else{
						el_obj.attr('placeholder', label_text);
					}
				}
				jQuery('<label class="'+label_class+ '">' + label_text + '</label>').appendTo(el_group);
				label_text = '.';	
				el_group.append(el_obj);
				form_container.append(el_group);

				el_count++;
			}else if(el_obj.is('input[type=radio]') || el_obj.is('input[type=checkbox]') ) {
				
				//For Bottom & Inline position, form fields does not look good so we are giving full width to the fields. 
				if( 'bottom' === self.data.form_layout || 'inline' === self.data.form_layout ){
					form_container.addClass('ig_form_full');
				}

				label_class = 'ig_el_label ig_button_label';
				if(label_text != '.'){
					label_class = 'ig_el_label';
				}
				jQuery('<label><span class="'+label_class+'">' + label_text + '</span></label>').prepend(el_obj).appendTo(el_group);
				label_text = '.';

				el_group.addClass('ig_form_el_radio');
				form_container.append(el_group);
				el_count++;
			}
		}); // loop End
		
		var elsClass = ['', 'ig_full', 'ig_half', 'ig_third', 'ig_quater'];	
		el_count = (el_count < 4 ) ? el_count : 4;

		//Add bot field if not detected
		if(form_container.find('.ig_form_required_field').length <= 0){
			form_container.append('<div class="ig_form_els"><input class="ig_form_required_field" type="text" tabindex="-1" value="" /></div>');
		}
		//Checking if full width needs to be given to form fields.
		if( form_container.hasClass('ig_form_full')){
			form_container.addClass('ig_full').removeClass('ig_form_full');
		} else{
			form_container.addClass(elsClass[el_count]);
		}
		form_container.find('.ig_form_required_field').parent().removeClass('ig_form_els').css({'position':'absolute' , 'left': '-5000px'}).end().end()
			.find('.ig_form_hidden_field').parent().removeClass('ig_form_els').css({'display':'none'});
		form_object.append(form_container);
		
		// TODO :: Qucik fix for Rainmaker form submission - success message
		// return jQuery('<div/>').append(form_object).append(rm_message).html();
		return jQuery('<div/>').append(form_object).html();
	}
	return null;
}


Icegram_Message_Type.prototype.render_template = function ( ) {
	if ( typeof(window.icegram.get_template_fn( this.type ) ) !== 'function') {
		// Adapted from John Resig's Simple JavaScript Templating
		window.icegram.set_template_fn( this.type, new Function("obj",
							"var p=[],print=function(){p.push.apply(p,arguments);};" +
						        "with(obj){p.push('" +
							this.template
							  .replace(/[\r\t\n]/g, " ")
							  .split("{{").join("\t")
							  .replace(/((^|\}\})[^\t]*)'/g, "$1\r")
							  .replace(/\t=(.*?)\}\}/g, "',$1,'")
							  .split("\t").join("');")
							  .split("}}").join("p.push('")
							  .split("\r").join("\\'")
							+ "');}return p.join('');") );
	}
	return window.icegram.get_template_fn( this.type )( this.data );
};

Icegram_Message_Type.prototype.pre_render = function ( ) {
};

Icegram_Message_Type.prototype.post_render = function ( ) {
}

Icegram_Message_Type.prototype.set_up_show_trigger = function ( ) {
	if (!isNaN(this.data.delay_time)) {
		if( this.data.delay_time >= 0 ) {	
			var self = this;
			this.timer = setTimeout( function() { self.show(); } , this.data.delay_time * 1000 );
		}
	} else {
		this.show();
	}
};

Icegram_Message_Type.prototype.set_template = function ( str ) {
	this.template = str;
};

Icegram_Message_Type.prototype.get_template_default = function () {
	return '<div id="icegram_message_{{=id}}" class="icegram">' + 
			'<div class="ig_headline">{{=headline}}</div>' +
			'</div>';
};

Icegram_Message_Type.prototype.show = function ( options, silent ) {
	if ( !this.is_visible() ) {
		this.animate('in');
		this.pre_show();
		this.el.show( options );
		this.el.addClass('ig_show').removeClass('ig_hide');
		silent !== true && this.track( 'shown' );
		this.post_show();
	}
	var self = this;
	window.icegram.adjustFormContainerHeight(self);
};

Icegram_Message_Type.prototype.hide = function ( options, silent ) {
	if ( this.is_visible() ) {
		var self = this;
		this.animate('out');
		this.pre_hide();
		setTimeout(function(){
			if(!self.el.hasClass('ig_no_hide')){
				self.el.hide( options );
			}
			self.el.addClass('ig_hide').removeClass('ig_show');
		},500);
		silent !== true && this.track( 'closed' );
		this.post_hide();
	}
};

Icegram_Message_Type.prototype.set_position = function ( ) {
};

Icegram_Message_Type.prototype.add_powered_by = function ( pb ) {
};

Icegram_Message_Type.prototype.pre_show = function (  ) {
};

Icegram_Message_Type.prototype.post_show = function (  ) {
};

Icegram_Message_Type.prototype.pre_hide = function (  ) {
};

Icegram_Message_Type.prototype.post_hide = function (  ) {
};

// Event tracking wrapper
Icegram_Message_Type.prototype.track = function ( e, params ) {
	if (typeof( window.icegram.track ) === 'function' ) {
		params = params || {};
		jQuery.extend( params, {message_id: this.data.id, campaign_id: this.data.campaign_id, expiry_time:this.data.expiry_time, expiry_time_clicked:this.data.expiry_time_clicked} );
		window.icegram.track( e, params);
	}
};

Icegram_Message_Type.prototype.is_visible = function ( ) {
	return this.el.hasClass('ig_show');
};

// Click and other event handlers
Icegram_Message_Type.prototype.toggle = function ( options ) {
	if ( this.is_visible() ) {
		this.hide( options );
	} else {
		this.show( options );
	};
};

Icegram_Message_Type.prototype.on_click = function ( e ) {

	e.data = e.data || { self: this };
	// Clicked on close button
	if (jQuery(e.target).filter('.ig_close').length
		|| jQuery(e.target).parents('.ig_close').length ) {
		e.data.self.hide();
		return;
	}
	var form = jQuery(e.target).closest('.icegram').find('form:visible').first();
	// Clicking on ig_button or any other link with a class ig_cta will trigger cta click
	if((jQuery(e.target).filter('.ig_button, .ig_cta, :submit').length
		|| jQuery(e.target).parents('button[type=submit]').length
		|| jQuery(e.target).filter('.es_submit_button').length // Email subscriber form in messagebody-cta compatiblity
		|| (jQuery(e.target).parents('.ig_button, .ig_cta').length && !(form.find('.ig_button, input[type=button], input[type=submit], button[type=submit]' ).length > 0) )) 
		&& jQuery(e.target).not('.donot-track').length){
        e.data.self.on_cta_click( e );
    }
};

Icegram_Message_Type.prototype.on_resize = function ( e ) {
};

Icegram_Message_Type.prototype.on_cta_click = function ( e ) {
	e.data = e.data || { self: this };
    var form = jQuery(e.target).closest('.icegram').find('form:visible').first();
    if(jQuery(form).length && jQuery(form).find('.ig_form_required_field').length 
    		&& jQuery(form).find('.ig_form_required_field').val() !== ''){
    	e.preventDefault();
    	e.data.self.hide();
    	return;
    }
    // Do not track clicks, if form submission has failed because of honeypot
	e.data.self.track( 'clicked' );

	if(jQuery(form).length){
		jQuery(form).submit(function(e){
			if( jQuery(form).hasClass('ig_form_init_done')){
				e.preventDefault();
			}
		});
	}else if (typeof(e.data.self.data.link) === 'string' && e.data.self.data.link != '') {
        window.location.href = e.data.self.data.link;
    	e.data.self.hide();
    }else if(e.data.self.data.hide !== false){
    	e.data.self.hide();
    }

};

Icegram_Message_Type.prototype.animate = function ( mode ) {
	if (typeof(this.data.animation) !== 'undefined') {
		var fn = this.data.animation;
		var self = this;
		if (mode == 'in') {
			if(typeof(this.animations[fn+'_in']) === 'function'){
				this.animations[fn+'_in'](self);
			} else if (!this.el.hasClass('ig_anim_'+fn+'_in') ) {
				this.el.removeClass('ig_anim_'+fn+'_out');
				setTimeout(function(){
					self.el.addClass('ig_anim_'+fn+'_in');
				} ,1);
			}
		}else if (mode == 'out') {
			if(typeof(this.animations[fn+'_out']) === 'function'){
				this.animations[fn+'_out'](self);
			} else {
				this.el.removeClass('ig_anim_'+fn+'_in');
				setTimeout(function(){
					self.el.addClass('ig_anim_'+fn+'_out');
				} ,1);
				
			}
		}
	}
}

/**
 * Utilities
 */
String.prototype.ucwords = function() {
	return this.toLowerCase().replace(/\b[a-z]/g, function(letter) {
    	return letter.toUpperCase();
	});
}
// Adjustment of Form container height.
Icegram.prototype.adjustFormContainerHeight = function(that) {
	var form_layout = '';
	if(that.el.hasClass('ig_form_left') || that.el.hasClass('ig_form_right')){
		form_layout = that.el.hasClass('ig_form_left') ? 'left' : 'right';
        var height = ( that.el.find('.ig_data').outerHeight() > that.el.find('.ig_form_container.layout_' + form_layout ).outerHeight())
                        ? that.el.find('.ig_data').outerHeight()
                        : that.el.find('.ig_form_container.layout_' + form_layout ).outerHeight();
        that.el.find('.ig_form_container').outerHeight(height);
    }

	// if(that.el.hasClass('ig_form_bottom') || that.el.hasClass('ig_form_inline')){
	// 	form_layout = that.el.hasClass('ig_form_bottom') ? 'bottom' : 'inline';
 //        var height = ( that.el.find('.ig_form_container.layout_' + form_layout +' .ig_button').innerHeight() > that.el.find('.ig_form_container input').first().outerHeight())
 //                        ? that.el.find('.ig_form_container.layout_' + form_layout +' .ig_button').innerHeight()
 //                        : that.el.find('.ig_form_container input').first().outerHeight();
 //        that.el.find('.ig_form_container input, .ig_form_container select, .ig_form_container textarea')
 //          .not('.ig_form_container.layout_' + form_layout +' .ig_button, .ig_form_el_radio input').outerHeight(height);
	// }
}
//Color conversion Functions.
Icegram.prototype.hexToRgb = function(hex) {
    var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result ? {
        r: parseInt(result[1], 16),
        g: parseInt(result[2], 16),
        b: parseInt(result[3], 16)
    } : null;
}
Icegram.prototype.rgbToHsl = function(r, g, b) {
    r /= 255, g /= 255, b /= 255;
    var max = Math.max(r, g, b), min = Math.min(r, g, b);

    var h, s, l = (max + min) / 2;
    if(max == min){
        h = s = 0; // achromatic
    }else{
        var d = max - min;
        s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
        switch(max){
            case r: h = (g - b) / d; break;
            case g: h = (b - r) / d + 2; break;
            case b: h = (r - g) / d + 4; break;
        }
        l = Math.floor(l * 100);
	    s = Math.floor(s * 100);
	    h = Math.floor(h * 60);
	    if(h<0){
	        h += 360;
	    }
    }
    return {h: h, s: s, l: l};
}
	
Icegram.prototype.hexToHsl = function (hex) {
	var rgb = window.icegram.hexToRgb(hex);
	return window.icegram.rgbToHsl(rgb.r, rgb.g, rgb.b);
}

if (typeof Object.create != 'function') {
    (function () {
        var F = function () {};
        Object.create = function (o) {
            if (arguments.length > 1) { 
              throw Error('Second argument not supported');
            }
            if (o === null) { 
              throw Error('Cannot set a null [[Prototype]]');
            }
            if (typeof o != 'object') { 
              throw TypeError('Argument must be an object');
            }
            F.prototype = o;
            return new F();
        };
    })();
}

//ES - success event handler
var es_responseHandler = function (e, data) {
	if( typeof icegram !== 'undefined'){
		var msg_id = ((jQuery(e.target).closest('[id^=icegram_message_]') || {}).attr('id') || '').split('_').pop() || 0 ;
		var msg = icegram.get_message_by_id(msg_id) || undefined;
	  	if(typeof msg !== 'undefined'){
			jQuery(e.target).find('.es_msg_ig').remove();
  			if(msg.data.cta === 'form_via_ajax' && data.detail.es_response === 'success'){
		  		if(msg.data.response_text === ''){
			  		msg.data.response_text = data.detail.msg;
		  		}
	  			msg.el.trigger('form_success.ig_cta', [msg]);
  			}else{
  				if(typeof msg.data.use_form !== 'undefined'){
		  			jQuery(e.target).append('<div class="es_msg es_msg_ig es_subscription_message '+data.detail.es_response+'">'+ (data.detail.msg) +'</div>');
		  		}
  			}
  		}
	}
}; //success.es


jQuery( document ).on( "es_response" ,es_responseHandler );

// jQuery Cookies
if(typeof(jQuery.cookie) !== undefined){
	(function(e){if(typeof define==="function"&&define.amd){define(["jquery"],e)}else if(typeof exports==="object"){e(require("jquery"))}else{e(jQuery)}})(function(e){function n(e){return u.raw?e:encodeURIComponent(e)}function r(e){return u.raw?e:decodeURIComponent(e)}function i(e){return n(u.json?JSON.stringify(e):String(e))}function s(e){if(e.indexOf('"')===0){e=e.slice(1,-1).replace(/\\"/g,'"').replace(/\\\\/g,"\\")}try{e=decodeURIComponent(e.replace(t," "));return u.json?JSON.parse(e):e}catch(n){}}function o(t,n){var r=u.raw?t:s(t);return e.isFunction(n)?n(r):r}var t=/\+/g;var u=e.cookie=function(t,s,a){if(s!==undefined&&!e.isFunction(s)){a=e.extend({},u.defaults,a);if(typeof a.expires==="number"){var f=a.expires,l=a.expires=new Date;l.setTime(+l+f*864e5)}return document.cookie=[n(t),"=",i(s),a.expires?"; expires="+a.expires.toUTCString():"",a.path?"; path="+a.path:"",a.domain?"; domain="+a.domain:"",a.secure?"; secure":""].join("")}var c=t?undefined:{};var h=document.cookie?document.cookie.split("; "):[];for(var p=0,d=h.length;p<d;p++){var v=h[p].split("=");var m=r(v.shift());var g=v.join("=");if(t&&t===m){c=o(g,s);break}if(!t&&(g=o(g))!==undefined){c[m]=g}}return c};u.defaults={};e.removeCookie=function(t,n){if(e.cookie(t)===undefined){return false}e.cookie(t,"",e.extend({},n,{expires:-1}));return!e.cookie(t)}});
}


/**
 * Icegram Message Type - Popup
 **/
 function Icegram_Message_Type_Popup( data ) {
    Icegram_Message_Type.apply(this, arguments);
}
Icegram_Message_Type_Popup.prototype = Object.create(Icegram_Message_Type.prototype);
Icegram_Message_Type_Popup.prototype.constructor = Icegram_Message_Type_Popup;

Icegram_Message_Type_Popup.prototype.get_template_default = function () {
    return  '<div id="icegram_message_{{=id}}" class="icegram ig_popup ig_{{=theme}} ig_container mfp-hide">'+
                '<div class="ig_close" id="popup_box_close_{{=id}}"></div>'+
                '<div class="ig_clear_fix" data={{=id}}>'+
                    '<div class="ig_bg_overlay"></div>'+
                    '<div class="ig_form_container layout_left"></div>'+
                    '<div class="ig_data ig_clear_fix">'+
                        '<div class="ig_headline">{{=headline}}</div>'+
                        '<div class="ig_content">'+
                            '<div class="ig_message ig_clear_fix">{{=message}}</div>'+
                        '</div>'+
                        '<div class="ig_button" >{{=label}}</div>'+
                    '</div>'+
                    '<div class="ig_form_container layout_right layout_bottom"></div>'+
                '</div>'+
            '</div>';
};

Icegram_Message_Type_Popup.prototype.post_render = function ( ) {
    //Calling parent post_render function
    Icegram_Message_Type.prototype.post_render.apply(this, arguments);
    
    if(this.data.use_theme_defaults == undefined || this.data.use_theme_defaults != 'yes'){
        if (this.data.bg_color != undefined && this.data.bg_color != '') {
            // TODO :: test all popup themes with bg overlay
            // this.el.find('.ig_bg_overlay').css('background-color', this.data.bg_color).css('border-color', this.data.bg_color);
            this.el.find('.ig_bg_overlay').css('border-color', this.data.bg_color);
        }
    }
};

Icegram_Message_Type_Popup.prototype.show = function ( options, silent ) {
    if ( this.is_visible() ) return;
    var self = this;
    var popup_id = '#icegram_message_'+this.data.id;
    this.animate('in');
    window.ig_popup = jQuery.magnificPopup;
    window.ig_popup.open({ 
        items: {
        src: popup_id,
        type: 'inline'
        },
        // mainClass: 'ig_this_message_root_'+this.data.id,
        showCloseBtn :false,
        callbacks: {
            close: function() {
              // Will fire when popup is closed
                silent !== true && self.track( 'closed' );
                self.el.removeClass('ig_show');
            },
            open: function(){
                window.icegram.adjustFormContainerHeight(self);
            }
        }
    });
    self.el.addClass('ig_show').removeClass('ig_hide');
    silent !== true && this.track( 'shown' );
};

Icegram_Message_Type_Popup.prototype.add_powered_by = function ( pb ) {        
    setTimeout( function() {
        jQuery('.mfp-wrap').append('<div class="ig_powered_by"><a href="'+pb.link+'" target="_blank">'+pb.text+'</a></div>');
    },1000 + this.data.delay_time * 1000);
};

Icegram_Message_Type_Popup.prototype.hide = function ( options, silent ) {
    if ( !this.is_visible() ) return;
    var popup_id = '#icegram_message_'+this.data.id;
    var self = this;
    this.animate('out');
    setTimeout( function() {
        self.el.addClass('ig_hide').removeClass('ig_show');
        jQuery.magnificPopup.close({ items: {
            src: popup_id,
            type: 'inline'
    }})} , 500);
};

// Magnific Popup v1.0.0 by Dmitry Semenov
// http://bit.ly/magnific-popup#build=inline+image+retina
if(typeof(window.ig_popup) === 'undefined'){
	(function(a){typeof define=="function"&&define.amd?define(["jquery"],a):typeof exports=="object"?a(require("jquery")):a(window.jQuery||window.Zepto)})(function(a){var b="Close",c="BeforeClose",d="AfterClose",e="BeforeAppend",f="MarkupParse",g="Open",h="Change",i="mfp",j="."+i,k="mfp-ready",l="mfp-removing",m="mfp-prevent-close",n,o=function(){},p=!!window.jQuery,q,r=a(window),s,t,u,v,w=function(a,b){n.ev.on(i+a+j,b)},x=function(b,c,d,e){var f=document.createElement("div");return f.className="mfp-"+b,d&&(f.innerHTML=d),e?c&&c.appendChild(f):(f=a(f),c&&f.appendTo(c)),f},y=function(b,c){n.ev.triggerHandler(i+b,c),n.st.callbacks&&(b=b.charAt(0).toLowerCase()+b.slice(1),n.st.callbacks[b]&&n.st.callbacks[b].apply(n,a.isArray(c)?c:[c]))},z=function(b){if(b!==v||!n.currTemplate.closeBtn)n.currTemplate.closeBtn=a(n.st.closeMarkup.replace("%title%",n.st.tClose)),v=b;return n.currTemplate.closeBtn},A=function(){a.magnificPopup.instance||(n=new o,n.init(),a.magnificPopup.instance=n)},B=function(){var a=document.createElement("p").style,b=["ms","O","Moz","Webkit"];if(a.transition!==undefined)return!0;while(b.length)if(b.pop()+"Transition"in a)return!0;return!1};o.prototype={constructor:o,init:function(){var b=navigator.appVersion;n.isIE7=b.indexOf("MSIE 7.")!==-1,n.isIE8=b.indexOf("MSIE 8.")!==-1,n.isLowIE=n.isIE7||n.isIE8,n.isAndroid=/android/gi.test(b),n.isIOS=/iphone|ipad|ipod/gi.test(b),n.supportsTransition=B(),n.probablyMobile=n.isAndroid||n.isIOS||/(Opera Mini)|Kindle|webOS|BlackBerry|(Opera Mobi)|(Windows Phone)|IEMobile/i.test(navigator.userAgent),s=a(document),n.popupsCache={}},open:function(b){var c;if(b.isObj===!1){n.items=b.items.toArray(),n.index=0;var d=b.items,e;for(c=0;c<d.length;c++){e=d[c],e.parsed&&(e=e.el[0]);if(e===b.el[0]){n.index=c;break}}}else n.items=a.isArray(b.items)?b.items:[b.items],n.index=b.index||0;if(n.isOpen){n.updateItemHTML();return}n.types=[],u="",b.mainEl&&b.mainEl.length?n.ev=b.mainEl.eq(0):n.ev=s,b.key?(n.popupsCache[b.key]||(n.popupsCache[b.key]={}),n.currTemplate=n.popupsCache[b.key]):n.currTemplate={},n.st=a.extend(!0,{},a.magnificPopup.defaults,b),n.fixedContentPos=n.st.fixedContentPos==="auto"?!n.probablyMobile:n.st.fixedContentPos,n.st.modal&&(n.st.closeOnContentClick=!1,n.st.closeOnBgClick=!1,n.st.showCloseBtn=!1,n.st.enableEscapeKey=!1),n.bgOverlay||(n.bgOverlay=x("bg").on("click"+j,function(){n.close()}),n.wrap=x("wrap").attr("tabindex",-1).on("click"+j,function(a){n._checkIfClose(a.target)&&n.close()}),n.container=x("container",n.wrap)),n.contentContainer=x("content"),n.st.preloader&&(n.preloader=x("preloader",n.container,n.st.tLoading));var h=a.magnificPopup.modules;for(c=0;c<h.length;c++){var i=h[c];i=i.charAt(0).toUpperCase()+i.slice(1),n["init"+i].call(n)}y("BeforeOpen"),n.st.showCloseBtn&&(n.st.closeBtnInside?(w(f,function(a,b,c,d){c.close_replaceWith=z(d.type)}),u+=" mfp-close-btn-in"):n.wrap.append(z())),n.st.alignTop&&(u+=" mfp-align-top"),n.fixedContentPos?n.wrap.css({overflow:n.st.overflowY,overflowX:"hidden",overflowY:n.st.overflowY}):n.wrap.css({top:r.scrollTop(),position:"absolute"}),(n.st.fixedBgPos===!1||n.st.fixedBgPos==="auto"&&!n.fixedContentPos)&&n.bgOverlay.css({height:s.height(),position:"absolute"}),n.st.enableEscapeKey&&s.on("keyup"+j,function(a){a.keyCode===27&&n.close()}),r.on("resize"+j,function(){n.updateSize()}),n.st.closeOnContentClick||(u+=" mfp-auto-cursor"),u&&n.wrap.addClass(u);var l=n.wH=r.height(),m={};if(n.fixedContentPos&&n._hasScrollBar(l)){var o=n._getScrollbarSize();o&&(m.marginRight=o)}n.fixedContentPos&&(n.isIE7?a("body, html").css("overflow","hidden"):m.overflow="hidden");var p=n.st.mainClass;return n.isIE7&&(p+=" mfp-ie7"),p&&n._addClassToMFP(p),n.updateItemHTML(),y("BuildControls"),a("html").css(m),n.bgOverlay.add(n.wrap).prependTo(n.st.prependTo||a(document.body)),n._lastFocusedEl=document.activeElement,setTimeout(function(){n.content?(n._addClassToMFP(k),n._setFocus()):n.bgOverlay.addClass(k),s.on("focusin"+j,n._onFocusIn)},16),n.isOpen=!0,n.updateSize(l),y(g),b},close:function(){if(!n.isOpen)return;y(c),n.isOpen=!1,n.st.removalDelay&&!n.isLowIE&&n.supportsTransition?(n._addClassToMFP(l),setTimeout(function(){n._close()},n.st.removalDelay)):n._close()},_close:function(){y(b);var c=l+" "+k+" ";n.bgOverlay.detach(),n.wrap.detach(),n.container.empty(),n.st.mainClass&&(c+=n.st.mainClass+" "),n._removeClassFromMFP(c);if(n.fixedContentPos){var e={marginRight:""};n.isIE7?a("body, html").css("overflow",""):e.overflow="",a("html").css(e)}s.off("keyup"+j+" focusin"+j),n.ev.off(j),n.wrap.attr("class","mfp-wrap").removeAttr("style"),n.bgOverlay.attr("class","mfp-bg"),n.container.attr("class","mfp-container"),n.st.showCloseBtn&&(!n.st.closeBtnInside||n.currTemplate[n.currItem.type]===!0)&&n.currTemplate.closeBtn&&n.currTemplate.closeBtn.detach(),n._lastFocusedEl&&a(n._lastFocusedEl).focus(),n.currItem=null,n.content=null,n.currTemplate=null,n.prevHeight=0,y(d)},updateSize:function(a){if(n.isIOS){var b=document.documentElement.clientWidth/window.innerWidth,c=window.innerHeight*b;n.wrap.css("height",c),n.wH=c}else n.wH=a||r.height();n.fixedContentPos||n.wrap.css("height",n.wH),y("Resize")},updateItemHTML:function(){var b=n.items[n.index];n.contentContainer.detach(),n.content&&n.content.detach(),b.parsed||(b=n.parseEl(n.index));var c=b.type;y("BeforeChange",[n.currItem?n.currItem.type:"",c]),n.currItem=b;if(!n.currTemplate[c]){var d=n.st[c]?n.st[c].markup:!1;y("FirstMarkupParse",d),d?n.currTemplate[c]=a(d):n.currTemplate[c]=!0}t&&t!==b.type&&n.container.removeClass("mfp-"+t+"-holder");var e=n["get"+c.charAt(0).toUpperCase()+c.slice(1)](b,n.currTemplate[c]);n.appendContent(e,c),b.preloaded=!0,y(h,b),t=b.type,n.container.prepend(n.contentContainer),y("AfterChange")},appendContent:function(a,b){n.content=a,a?n.st.showCloseBtn&&n.st.closeBtnInside&&n.currTemplate[b]===!0?n.content.find(".mfp-close").length||n.content.append(z()):n.content=a:n.content="",y(e),n.container.addClass("mfp-"+b+"-holder"),n.contentContainer.append(n.content)},parseEl:function(b){var c=n.items[b],d;c.tagName?c={el:a(c)}:(d=c.type,c={data:c,src:c.src});if(c.el){var e=n.types;for(var f=0;f<e.length;f++)if(c.el.hasClass("mfp-"+e[f])){d=e[f];break}c.src=c.el.attr("data-mfp-src"),c.src||(c.src=c.el.attr("href"))}return c.type=d||n.st.type||"inline",c.index=b,c.parsed=!0,n.items[b]=c,y("ElementParse",c),n.items[b]},addGroup:function(a,b){var c=function(c){c.mfpEl=this,n._openClick(c,a,b)};b||(b={});var d="click.magnificPopup";b.mainEl=a,b.items?(b.isObj=!0,a.off(d).on(d,c)):(b.isObj=!1,b.delegate?a.off(d).on(d,b.delegate,c):(b.items=a,a.off(d).on(d,c)))},_openClick:function(b,c,d){var e=d.midClick!==undefined?d.midClick:a.magnificPopup.defaults.midClick;if(!e&&(b.which===2||b.ctrlKey||b.metaKey))return;var f=d.disableOn!==undefined?d.disableOn:a.magnificPopup.defaults.disableOn;if(f)if(a.isFunction(f)){if(!f.call(n))return!0}else if(r.width()<f)return!0;b.type&&(b.preventDefault(),n.isOpen&&b.stopPropagation()),d.el=a(b.mfpEl),d.delegate&&(d.items=c.find(d.delegate)),n.open(d)},updateStatus:function(a,b){if(n.preloader){q!==a&&n.container.removeClass("mfp-s-"+q),!b&&a==="loading"&&(b=n.st.tLoading);var c={status:a,text:b};y("UpdateStatus",c),a=c.status,b=c.text,n.preloader.html(b),n.preloader.find("a").on("click",function(a){a.stopImmediatePropagation()}),n.container.addClass("mfp-s-"+a),q=a}},_checkIfClose:function(b){if(a(b).hasClass(m))return;var c=n.st.closeOnContentClick,d=n.st.closeOnBgClick;if(c&&d)return!0;if(!n.content||a(b).hasClass("mfp-close")||n.preloader&&b===n.preloader[0])return!0;if(b!==n.content[0]&&!a.contains(n.content[0],b)){if(d&&a.contains(document,b))return!0}else if(c)return!0;return!1},_addClassToMFP:function(a){n.bgOverlay.addClass(a),n.wrap.addClass(a)},_removeClassFromMFP:function(a){this.bgOverlay.removeClass(a),n.wrap.removeClass(a)},_hasScrollBar:function(a){return(n.isIE7?s.height():document.body.scrollHeight)>(a||r.height())},_setFocus:function(){(n.st.focus?n.content.find(n.st.focus).eq(0):n.wrap).focus()},_onFocusIn:function(b){if(b.target!==n.wrap[0]&&!a.contains(n.wrap[0],b.target))return n._setFocus(),!1},_parseMarkup:function(b,c,d){var e;d.data&&(c=a.extend(d.data,c)),y(f,[b,c,d]),a.each(c,function(a,c){if(c===undefined||c===!1)return!0;e=a.split("_");if(e.length>1){var d=b.find(j+"-"+e[0]);if(d.length>0){var f=e[1];f==="replaceWith"?d[0]!==c[0]&&d.replaceWith(c):f==="img"?d.is("img")?d.attr("src",c):d.replaceWith('<img src="'+c+'" class="'+d.attr("class")+'" />'):d.attr(e[1],c)}}else b.find(j+"-"+a).html(c)})},_getScrollbarSize:function(){if(n.scrollbarSize===undefined){var a=document.createElement("div");a.style.cssText="width: 99px; height: 99px; overflow: scroll; position: absolute; top: -9999px;",document.body.appendChild(a),n.scrollbarSize=a.offsetWidth-a.clientWidth,document.body.removeChild(a)}return n.scrollbarSize}},a.magnificPopup={instance:null,proto:o.prototype,modules:[],open:function(b,c){return A(),b?b=a.extend(!0,{},b):b={},b.isObj=!0,b.index=c||0,this.instance.open(b)},close:function(){return a.magnificPopup.instance&&a.magnificPopup.instance.close()},registerModule:function(b,c){c.options&&(a.magnificPopup.defaults[b]=c.options),a.extend(this.proto,c.proto),this.modules.push(b)},defaults:{disableOn:0,key:null,midClick:!1,mainClass:"",preloader:!0,focus:"",closeOnContentClick:!1,closeOnBgClick:!0,closeBtnInside:!0,showCloseBtn:!0,enableEscapeKey:!0,modal:!1,alignTop:!1,removalDelay:0,prependTo:null,fixedContentPos:"auto",fixedBgPos:"auto",overflowY:"auto",closeMarkup:'<button title="%title%" type="button" class="mfp-close">&times;</button>',tClose:"Close (Esc)",tLoading:"Loading..."}},a.fn.magnificPopup=function(b){A();var c=a(this);if(typeof b=="string")if(b==="open"){var d,e=p?c.data("magnificPopup"):c[0].magnificPopup,f=parseInt(arguments[1],10)||0;e.items?d=e.items[f]:(d=c,e.delegate&&(d=d.find(e.delegate)),d=d.eq(f)),n._openClick({mfpEl:d},c,e)}else n.isOpen&&n[b].apply(n,Array.prototype.slice.call(arguments,1));else b=a.extend(!0,{},b),p?c.data("magnificPopup",b):c[0].magnificPopup=b,n.addGroup(c,b);return c};var C="inline",D,E,F,G=function(){F&&(E.after(F.addClass(D)).detach(),F=null)};a.magnificPopup.registerModule(C,{options:{hiddenClass:"hide",markup:"",tNotFound:"Content not found"},proto:{initInline:function(){n.types.push(C),w(b+"."+C,function(){G()})},getInline:function(b,c){G();if(b.src){var d=n.st.inline,e=a(b.src);if(e.length){var f=e[0].parentNode;f&&f.tagName&&(E||(D=d.hiddenClass,E=x(D),D="mfp-"+D),F=e.after(E).detach().removeClass(D)),n.updateStatus("ready")}else n.updateStatus("error",d.tNotFound),e=a("<div>");return b.inlineElement=e,e}return n.updateStatus("ready"),n._parseMarkup(c,{},b),c}}});var H,I=function(b){if(b.data&&b.data.title!==undefined)return b.data.title;var c=n.st.image.titleSrc;if(c){if(a.isFunction(c))return c.call(n,b);if(b.el)return b.el.attr(c)||""}return""};a.magnificPopup.registerModule("image",{options:{markup:'<div class="mfp-figure"><div class="mfp-close"></div><figure><div class="mfp-img"></div><figcaption><div class="mfp-bottom-bar"><div class="mfp-title"></div><div class="mfp-counter"></div></div></figcaption></figure></div>',cursor:"mfp-zoom-out-cur",titleSrc:"title",verticalFit:!0,tError:'<a href="%url%">The image</a> could not be loaded.'},proto:{initImage:function(){var c=n.st.image,d=".image";n.types.push("image"),w(g+d,function(){n.currItem.type==="image"&&c.cursor&&a(document.body).addClass(c.cursor)}),w(b+d,function(){c.cursor&&a(document.body).removeClass(c.cursor),r.off("resize"+j)}),w("Resize"+d,n.resizeImage),n.isLowIE&&w("AfterChange",n.resizeImage)},resizeImage:function(){var a=n.currItem;if(!a||!a.img)return;if(n.st.image.verticalFit){var b=0;n.isLowIE&&(b=parseInt(a.img.css("padding-top"),10)+parseInt(a.img.css("padding-bottom"),10)),a.img.css("max-height",n.wH-b)}},_onImageHasSize:function(a){a.img&&(a.hasSize=!0,H&&clearInterval(H),a.isCheckingImgSize=!1,y("ImageHasSize",a),a.imgHidden&&(n.content&&n.content.removeClass("mfp-loading"),a.imgHidden=!1))},findImageSize:function(a){var b=0,c=a.img[0],d=function(e){H&&clearInterval(H),H=setInterval(function(){if(c.naturalWidth>0){n._onImageHasSize(a);return}b>200&&clearInterval(H),b++,b===3?d(10):b===40?d(50):b===100&&d(500)},e)};d(1)},getImage:function(b,c){var d=0,e=function(){b&&(b.img[0].complete?(b.img.off(".mfploader"),b===n.currItem&&(n._onImageHasSize(b),n.updateStatus("ready")),b.hasSize=!0,b.loaded=!0,y("ImageLoadComplete")):(d++,d<200?setTimeout(e,100):f()))},f=function(){b&&(b.img.off(".mfploader"),b===n.currItem&&(n._onImageHasSize(b),n.updateStatus("error",g.tError.replace("%url%",b.src))),b.hasSize=!0,b.loaded=!0,b.loadError=!0)},g=n.st.image,h=c.find(".mfp-img");if(h.length){var i=document.createElement("img");i.className="mfp-img",b.el&&b.el.find("img").length&&(i.alt=b.el.find("img").attr("alt")),b.img=a(i).on("load.mfploader",e).on("error.mfploader",f),i.src=b.src,h.is("img")&&(b.img=b.img.clone()),i=b.img[0],i.naturalWidth>0?b.hasSize=!0:i.width||(b.hasSize=!1)}return n._parseMarkup(c,{title:I(b),img_replaceWith:b.img},b),n.resizeImage(),b.hasSize?(H&&clearInterval(H),b.loadError?(c.addClass("mfp-loading"),n.updateStatus("error",g.tError.replace("%url%",b.src))):(c.removeClass("mfp-loading"),n.updateStatus("ready")),c):(n.updateStatus("loading"),b.loading=!0,b.hasSize||(b.imgHidden=!0,c.addClass("mfp-loading"),n.findImageSize(b)),c)}}});var J,K=function(){return J===undefined&&(J=document.createElement("p").style.MozTransform!==undefined),J};a.magnificPopup.registerModule("zoom",{options:{enabled:!1,easing:"ease-in-out",duration:300,opener:function(a){return a.is("img")?a:a.find("img")}},proto:{initZoom:function(){var a=n.st.zoom,d=".zoom",e;if(!a.enabled||!n.supportsTransition)return;var f=a.duration,g=function(b){var c=b.clone().removeAttr("style").removeAttr("class").addClass("mfp-animated-image"),d="all "+a.duration/1e3+"s "+a.easing,e={position:"fixed",zIndex:9999,left:0,top:0,"-webkit-backface-visibility":"hidden"},f="transition";return e["-webkit-"+f]=e["-moz-"+f]=e["-o-"+f]=e[f]=d,c.css(e),c},h=function(){n.content.css("visibility","visible")},i,j;w("BuildControls"+d,function(){if(n._allowZoom()){clearTimeout(i),n.content.css("visibility","hidden"),e=n._getItemToZoom();if(!e){h();return}j=g(e),j.css(n._getOffset()),n.wrap.append(j),i=setTimeout(function(){j.css(n._getOffset(!0)),i=setTimeout(function(){h(),setTimeout(function(){j.remove(),e=j=null,y("ZoomAnimationEnded")},16)},f)},16)}}),w(c+d,function(){if(n._allowZoom()){clearTimeout(i),n.st.removalDelay=f;if(!e){e=n._getItemToZoom();if(!e)return;j=g(e)}j.css(n._getOffset(!0)),n.wrap.append(j),n.content.css("visibility","hidden"),setTimeout(function(){j.css(n._getOffset())},16)}}),w(b+d,function(){n._allowZoom()&&(h(),j&&j.remove(),e=null)})},_allowZoom:function(){return n.currItem.type==="image"},_getItemToZoom:function(){return n.currItem.hasSize?n.currItem.img:!1},_getOffset:function(b){var c;b?c=n.currItem.img:c=n.st.zoom.opener(n.currItem.el||n.currItem);var d=c.offset(),e=parseInt(c.css("padding-top"),10),f=parseInt(c.css("padding-bottom"),10);d.top-=a(window).scrollTop()-e;var g={width:c.width(),height:(p?c.innerHeight():c[0].offsetHeight)-f-e};return K()?g["-moz-transform"]=g.transform="translate("+d.left+"px,"+d.top+"px)":(g.left=d.left,g.top=d.top),g}}});var L="retina";a.magnificPopup.registerModule(L,{options:{replaceSrc:function(a){return a.src.replace(/\.\w+$/,function(a){return"@2x"+a})},ratio:1},proto:{initRetina:function(){if(window.devicePixelRatio>1){var a=n.st.retina,b=a.ratio;b=isNaN(b)?b():b,b>1&&(w("ImageHasSize."+L,function(a,c){c.img.css({"max-width":c.img[0].naturalWidth/b,width:"100%"})}),w("ElementParse."+L,function(c,d){d.src=a.replaceSrc(d,b)}))}}}}),A()});
	
}