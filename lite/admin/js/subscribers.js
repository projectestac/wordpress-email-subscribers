jQuery(document).ready(function ($) {
    "use strict"

    let importstatus = $('.step1 .import-status'),
		progress = $('#progress'),
		progressbar = progress.find('.bar'),
		importprogress = $('#importing-progress'),
		importprogressbar = importprogress.find('.bar'),
		import_percentage = importprogress.find('.import_percentage'),
		wpnonce = ig_es_subscribers_data.security,
		importerrors = 0,
		importstarttime,
        importidentifier,

	uploader_init = function () {
		let uploader = new plupload.Uploader(wpUploaderInit);

		uploader.bind('Init', function (up) {
			let uploaddiv = $('#plupload-upload-ui');

			if (up.features.dragdrop && !$(document.body).hasClass('mobile')) {
				uploaddiv.addClass('drag-drop');
				$('#drag-drop-area').bind('dragover.wp-uploader', function () { // dragenter doesn't fire right :(
					uploaddiv.addClass('drag-over');
				}).bind('dragleave.wp-uploader, drop.wp-uploader', function () {
					uploaddiv.removeClass('drag-over');
				});
			} else {
				uploaddiv.removeClass('drag-drop');
				$('#drag-drop-area').unbind('.wp-uploader');
			}

		});

		uploader.bind('FilesAdded', function (up, files) {
			$('#media-upload-error').html('');

			setTimeout(function () {
				up.refresh();
				up.start();
			}, 1);

		});

		uploader.bind('BeforeUpload', function (up, file) {
			progress.removeClass('finished error hidden');
			importstatus.show().removeClass('text-red-600');
			importstatus.html('Uploading');
		});

		uploader.bind('UploadFile', function (up, file) {});

		uploader.bind('UploadProgress', function (up, file) {
			importstatus.show().removeClass('text-red-600');
			importstatus.html(sprintf(ig_es_subscribers_data.i18n.uploading, file.percent + '%'));
			progressbar.stop().animate({
				'width': file.percent + '%'
			}, 100);
		});

		uploader.bind('Error', function (up, err) {
			importstatus.show().addClass('text-red-600 text-base font-medium');
			importstatus.html(err.message);
			progress.addClass('error');
			up.refresh();
		});

		uploader.bind('FileUploaded', function (up, file, response) {
			response = $.parseJSON(response.response);
			importidentifier = response.identifier;
			if (!response.success) {
				importstatus.html(response.message);
				progress.addClass('error');
				up.refresh();
				uploader.unbind('UploadComplete');
			}
		});

		uploader.bind('UploadComplete', function (up, files) {
			importstatus.removeClass('text-red-600').html(ig_es_subscribers_data.i18n.prepare_data);
			progress.addClass('finished');
			get_import_data();
		});

		uploader.init();
	}

	let get_import_data = function () {

		progress.removeClass('finished error');

		$.post( ajaxurl, {
			action: 'ig_es_get_import_data',
			identifier: importidentifier,
			security: wpnonce,
			dataType: 'json'
		}, function( response ) {
			progress.addClass('hidden');

			$('.step1').slideUp();
			$('.step2-body').html(response.html).parent().show();
			$('.step2-status,.step2-list').show();

			importstatus.html('');
		});
	}

	let start_import = function(e) {
		
		e.preventDefault();

		let is_email_field_set = false;
		let mapping_order = [];
		$('select[name="mapping_order[]"').each(function(){
			let mapped_field = $(this).val();
			mapping_order.push(mapped_field);
			if ( 'email' === mapped_field ) {
				is_email_field_set = true;
			}
		});
		

		if ( ! is_email_field_set ) {
			alert(ig_es_subscribers_data.i18n.select_emailcolumn);
			return false;
		}

		let status = $('#es_email_status').val();
		if ( '' === status || '0' === status ) {
			alert(ig_es_subscribers_data.i18n.select_status);
			return false;
		}

		let list_id = $('#list_id').val();

		if ( ! confirm(ig_es_subscribers_data.i18n.confirm_import) ) {
			return false;
		}

		let _this = $('.start-import').prop('disabled', true),
			loader = $('#import-ajax-loading').css({
				'display': 'inline-block'
			}),
			identifier = $('#identifier').val(),
			performance = $('#performance').is(':checked') ? 'yes' : 'no';

		progress.removeClass('hidden');
		progressbar.stop().width(0);
		$('.step1').slideUp();
		$('.step2-body').html('<br><br>').parent().show();
		$('.step2-status,.step2-list, .es-import-processing ').hide();

		importstarttime = new Date();

		do_import(0, {
			identifier: identifier,
			mapping_order: mapping_order,
			list_id: list_id,
			status: status,
			performance: performance
		});

		importstatus = $('.step2 .import-status');

		importstatus.html(ig_es_subscribers_data.i18n.prepare_import);

		window.onbeforeunload = function () {
			return ig_es_subscribers_data.i18n.onbeforeunloadimport;
		};
	}

	let do_import = function(id, options) {
		let percentage = 0;
		importprogress.removeClass('hidden');
		$.ajax({
			type: 'POST',
			url: ajaxurl,
			dataType: 'json',
			data: {
				action: 'ig_es_do_import',
				id: id,
				options: options,
				security: wpnonce,
			},
			success: function( response ) {
				percentage = (Math.min(1, (response.imported + response.errors) / response.total) * 100);

				$('.step2-body').html('<p class="pt-3 pb-2 text-sm text-gray-600">' + get_stats(response.f_imported, response.f_errors, response.f_total, percentage, response.memoryusage) + '</p>');
				$('.step2-status,.step2-list, .es-import-processing').hide();
				importerrors = 0;
				let finished = percentage >= 100;

				if (response.success) {

					if (!finished) do_import(id + 1, options);

					importprogressbar.animate({
						'width': (percentage) + '%'
					}, {
						duration: 1000,
						easing: 'swing',
						queue: false,
						step: function (percentage) {
							importstatus.show().addClass('text-lg').html( sprintf(ig_es_subscribers_data.i18n.import_contacts, '' ) );
							$('.import-instruction').html( ig_es_subscribers_data.i18n.no_windowclose );
							import_percentage.html( Math.ceil(percentage) + '%' );
						},
						complete: function () {
							if (finished) {
								window.onbeforeunload = null;
								$('.import-instruction').hide();
								importprogress.addClass('finished');
								$('.step2-body').html(response.html).slideDown();
								$('.step2-status,.step2-list,.es-import-processing').hide();
								importstatus.addClass('text-xl');
								importstatus.html(sprintf(ig_es_subscribers_data.i18n.import_complete,'<svg class=" w-6 h-6 inline-block text-indigo-600" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>'));
							}
						}
					});
				} else {
					import_error_handler(percentage, id, options);
				}
			},
			error: function(jqXHR, textStatus, errorThrown) {
				import_error_handler(percentage, id, options);
			}
		});
	}

	let get_stats = function(imported, errors, total, percentage, memoryusage) {
		let timepast = new Date().getTime() - importstarttime.getTime(),
			timeleft = Math.ceil(((100 - percentage) * (timepast / percentage)) / 60000);

		return sprintf(ig_es_subscribers_data.i18n.current_stats, '<span class="font-medium">' + imported + '</span>', '<span class="font-medium">' + total + '</span>', '<span class="font-medium">' + errors + '</span>', '<span class="font-medium">' + memoryusage + '</span>') + '<br>' +
			sprintf(ig_es_subscribers_data.i18n.estimate_time, timeleft);
	}

	let import_error_handler = function(percentage, id, options) {
		importerrors++;
		if (importerrors >= 5) {

			alert(ig_es_subscribers_data.i18n.error_importing);
			importstatus.html(sprintf(ig_es_subscribers_data.i18n.import_failed, '<svg class=" w-6 h-6 inline-block text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'));
			window.onbeforeunload = null;
			return;
		}

		let i = importerrors * 5,
			str = '',
			errorint = setInterval(function () {
				if (i <= 0) {
					clearInterval(errorint);
					progress.removeClass('paused');
					do_import(id, options);
				} else {
					progress.addClass('paused');
					str = '<span class="error">' + sprintf(ig_es_subscribers_data.i18n.continues_in, (i--)) + '</span>';
				}
				importstatus.html(sprintf(ig_es_subscribers_data.i18n.import_contacts, str));
			}, 1000);
	}

	let sprintf = function() {
		let a = Array.prototype.slice.call(arguments),
			str = a.shift(),
			total = a.length,
			reg;
		for (let i = 0; i < total; i++) {
			reg = new RegExp('%(' + (i + 1) + '\\$)?(s|d|f)');
			str = str.replace(reg, a[i]);
		}
		return str;
	}

	if ( 'object' === typeof (wpUploaderInit) ) {
		uploader_init();
	}

	$('#form_import_subscribers').on('submit', start_import );
});