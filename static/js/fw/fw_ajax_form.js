/*

 File: fw_ajax_form.js
 Copyright (c) 2014 Clemens K. (https://github.com/metacreature)
 
 MIT License
 
 Permission is hereby granted, free of charge, to any person obtaining a copy
 of this software and associated documentation files (the "Software"), to deal
 in the Software without restriction, including without limitation the rights
 to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 copies of the Software, and to permit persons to whom the Software is
 furnished to do so, subject to the following conditions:
 
 The above copyright notice and this permission notice shall be included in all
 copies or substantial portions of the Software.
 
 THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 SOFTWARE.

*/

$.fn.tinymce = function(options) {
	return this.each(function() {
		var settings = $.extend(true, {}, $.fn.tinymce.defaults, options);
		
		var form = $(this).closest('form');
		var button = settings.submit_btn ? $(settings.submit_btn) : form.find('.btn-ajax-submit').first();
	
		$(this).addClass('is_tinymce');
		
		var pressSave = function (event) {
			if (event.which == 83 && event.ctrlKey) {
				var target = $(event.target);
				if (target.hasClass('mce-content-body')) {
					button.click();
				}
				event.preventDefault();
				return false;
			}
			return true;
		}
		
		tinymce.init({
			target: $(this)[0],
			menubar: false,
			height: settings.height,
			plugins : 'link image code fullscreen',
			toolbar: 'code fullscreen | undo redo | removeformat | bold italic underline | alignleft aligncenter, alignright, alignjustify | bullist numlist link image',
			link_class_list: [
				{title: 'External Textlink', value: 'text_link external_link'},
			    {title: 'External Button', value: 'button_link external_link'},
			    {title: 'External', value: 'external_link'},
			    {title: 'Internal Textlink', value: 'text_link internal_link'},
			    {title: 'Internal Button', value: 'button_link internal_link'},
			    {title: 'Internal', value: 'internal_link'},
			  ],
		 	target_list: [
		    	{ text: 'New window', value: '_blank' },
		 		{ text: 'Current window', value: ''},
		  	],
		  	default_link_target: '_blank',
		  	link_title: false,
		  	convert_urls : false,
			content_css: '/static/css/bundle.min.css,' + ADMIN_WEB_ROOT +'/static/css/tinymce_content.css',
		}).then(function(editor){
			$('iframe').contents().keydown(pressSave);
			$('iframe').contents().keypress(pressSave);
		});
	});
}

$.fn.tinymce.defaults = {
	submit_btn: null,
	height: 300,
}

$.fn.ajax_form = function(options) {
	return this.each(function() {
		var settings = $.extend(true, {}, $.fn.ajax_form.defaults, options);
		
		var form = $(this);
		var button = settings.submit_btn ? $(settings.submit_btn) : form.find('.btn-ajax-submit').first();
		
		form.on('submit', function(event) {
			$.fn.ajax_form.submit_form(form, button, options);
			event.preventDefault();
			event.stopPropagation();
		})

		button.click(function(event) {
			event.preventDefault();
			event.stopPropagation();
			form.submit();
		});
		
		$.fn.ajax_form.init_press_save(form, button);

		
		var form_messages = sessionStorage.getItem('form_messages');
		if (form_messages) {
			form_messages = JSON.parse(form_messages);
			var key = form.data('name')+'__'+document.location.href;
			if (typeof form_messages[key] != 'undefined') {
				form.find(".ajax-form-response").html('<span class="ajax-form-success">'+form_messages[key]+'</span>').show();
			}
		}
	});
};

$.fn.ajax_form.init_press_save = function(form, button) {
	var pressSave = function (event) {
		if ($('body').hasClass('blockkeyactions')) {
            return;
        }
		if (event.which == 83 && event.ctrlKey) {
			var target = $(event.target);
			if (target.hasClass('form-control') && target.closest('form')[0] == form[0]) {
				button.click();
			}
			event.preventDefault();
			return false;
		}
		return true;
	}
	
	document.addEventListener('keydown', pressSave);
	document.addEventListener('keypress', pressSave);
}

$.fn.ajax_form.ajax_form_complete = function(form, button, options, data, status)	{
	var settings = $.extend(true, {}, $.fn.ajax_form.defaults, options);
	
	form.find(".ajax-form-response").hide().html("");
	form.find(".field-error-msg").remove();
	form.find('.line-error').removeClass('line-error');
	form.find('.field-error').removeClass('field-error');
	
	var ajax_form_scrolltop = function() {
		var top = form.find(".ajax-form-response").offset().top;
		$(document).scrollTop(top-30);
	}
	
	if (typeof data !== 'object' || is_empty(data)) {
		if (settings.error) {
			settings.error(form, button, null);
		}
		let error_message = 'Es ist ein Fehler aufgetreten! Bitte versuche es später noch einmal!'
		if (settings.default_error) {
			error_message = settings.default_error;
		}
		form.find(".field-error").remove();
		form.find(".field-line").removeClass('line-error');
		form.find(".ajax-form-response").html('<span class="ajax-form-error">'+error_message+'</span>').show();
		button.removeClass("btn-loading");
		
	} else if (data.success && data.redirect_url) {
		if(data.message) {
			var key = form.data('name')+'__'+data.redirect_url;
			var form_messages = {};
			form_messages[key] = data.message;
			sessionStorage.setItem('form_messages', JSON.stringify(form_messages));
		}
        document.location.replace(data.redirect_url);
    } else if (data.success) {
		if (settings.success) {
			settings.success(form, button, data);
		}
		if(data.message) {
			form.find(".ajax-form-response").html('<span class="ajax-form-success">'+data.message+'</span>').show();
			ajax_form_scrolltop();
		}
		form.find(".ajax-form-success-hide").hide();
	} else if (data.warning) {
		if (settings.warning) {
			settings.warning(form, button, data);
		}
		if(data.message) {
			form.find(".ajax-form-response").html('<span class="ajax-form-warning">'+data.message+'</span>').show();
			ajax_form_scrolltop();
		}
		form.find(".ajax-form-warning-hide").hide();
	} else {
		if (settings.error) {
			settings.error(form, button, data);
		}
		var i, field, error;
		var error_list = data.field_errors || data.errors;
		if (!is_empty(error_list)) {
			for ( i in error_list ) {
				field = form.find('[name="' + i + '"]').first();
				if (!field.attr('name')) {
					field = form.find('[name="' + i + '[]"]').first();
				}
				field.addClass("field-error").closest(".field-line").addClass("line-error");
				if (error_list[i] !== true) {
					error = typeof error_list[i] == 'string' ? error_list[i] : error_list[i][0]
					field.closest(".field-line").prepend('<span class="field-error-msg">'+error+'</span>');
				}
			}
			field = form.find('.line-error [name]');
			field.first().focus();
		}
		if (data.message) {
			form.find(".ajax-form-response").html('<span class="ajax-form-error">'+data.message+'</span>').show();
			if (!field || field.length == 0) {
				ajax_form_scrolltop();
			}
		}
	}
	
	button.removeClass("btn-loading");
	form.data('is_submitting', false);
}
		

$.fn.ajax_form.fullSerializeFormArray = function(form, add_disabled) {
	var
		rbracket = /\[\]$/,
		rCRLF = /\r?\n/g,
		rsubmitterTypes = /^(?:submit|button|image|reset)$/i,
		rsubmittable = /^(?:input|select|textarea|keygen)/i,
		rcheckableType = ( /^(?:checkbox|radio)$/i );

	var available_fields = form.map( function() {
			// Can add propHook for "elements" to filter or add form elements
			var elements = $.prop( this, "elements" );
			return elements ? $.makeArray( elements ) : this;
		} )
		.filter( function() {
			var type = this.type;
	
			// Use .is( ":disabled" ) so that fieldset[disabled] works
			return this.name && (!$( this ).is( ":disabled" ) || add_disabled) &&
				rsubmittable.test( this.nodeName ) && !rsubmitterTypes.test( type ) &&
				( this.checked || !rcheckableType.test( type ) );
		} ).get();
		
	var data = new FormData();
		
	for (i in available_fields) {
		var elem = $(available_fields[i]);
		var name = elem.attr('name');
		
		if (elem.attr('type') == 'file' && elem[0].files) {
			data.append(name, elem[0].files[0]);
			continue;
		} else if (elem.hasClass('is_tinymce')) {
			var val = tinymce.get(elem.attr('id')).getContent();
		} else {
			var val = elem.val();
		}
		
		if ( val == null ) {
			data.append(name, null);
		} else if ( Array.isArray( val ) ) {
			$.map( val, function( val ) {
				data.append(name, val.replace( rCRLF, "\r\n" ));
			});
		} else {
			data.append(name, val.replace( rCRLF, "\r\n" ));
		}

	};
	
	return data;
}

$.fn.ajax_form.submit_form = function(form, button, options) {
	if (!form.data('is_submitting')) {
		form.data('is_submitting', true);

		var settings = $.extend(true, {}, $.fn.ajax_form.defaults, options);
		if (settings.beforesubmit) {
			settings.beforesubmit(form, button);
		}
		
		var data = $.fn.ajax_form.fullSerializeFormArray(form);
		
		button.addClass("btn-loading");
		button.blur();
		
		$.ajax({
			type: "POST",
			dataType: "json",
			url: button.data('endpoint') ? button.data('endpoint') : document.location_href,
			data: data,
			contentType: false,
      		processData: false,
			complete: function (xhr) {
				$.fn.ajax_form.ajax_form_complete(form, button, options, xhr.responseJSON, xhr.status);
			},
		});
	}
}

$.fn.ajax_form.defaults = {
	default_error: null,
	submit_btn: null,
	beforesubmit: null,
	success: null,
	warning: null,
	error: null,
};

$(function(){
	sessionStorage.removeItem('form_messages');
})

