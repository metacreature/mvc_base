/*

 File: fw_ajax_form_button.js
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

$.fn.ajax_form_button = function(ajax_endpoint, options) {

	return this.each(function() {
		var settings = $.extend(true, {}, $.fn.ajax_form_button.defaults, options);
		
		var form = $(this).closest('form');
			var button = $(this);
		
		$(this).click(function(event) {
			event.preventDefault();
			event.stopPropagation();
			submit_form();
		});
		
		
		
		var fullSerializeFormArray = function(add_disabled) {
			var
				rbracket = /\[\]$/,
				rCRLF = /\r?\n/g,
				rsubmitterTypes = /^(?:submit|button|image|reset)$/i,
				rsubmittable = /^(?:input|select|textarea|keygen)/i,
				rcheckableType = ( /^(?:checkbox|radio)$/i );
		
			return form.map( function() {
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
			} )
			.map( function( i, elem ) {
				var val = $( this ).val();
		
				if ( val == null ) {
					return { name: elem.name, value: null };
				}
		
				if ( Array.isArray( val ) ) {
					return $.map( val, function( val ) {
						return { name: elem.name, value: val.replace( rCRLF, "\r\n" ) };
					} );
				}
		
				return { name: elem.name, value: val.replace( rCRLF, "\r\n" ) };
			} ).get();
		}
		
		var ajax_form_scrolltop = function() {
			var top = form.find(".ajax-form-response").offset().top;
			$(document).scrollTop(top-30);
		}

		var ajax_form_complete = function(data, status)	{
			form.find(".ajax-form-response").hide().html("");
			form.find(".field-error-msg").remove();
			form.find(".field-line").removeClass('line-error');
		
			
			if (typeof data !== 'object' || is_empty(data)) {
				if (settings.error) {
					settings.error(form, button, null);
				}
				
				form.find(".field-error").remove();
				form.find(".field-line").removeClass('line-error');
				form.find(".ajax-form-response").html('<span class="ajax-form-error">Es ist ein Fehler aufgetreten! Bitte versuche es später noch einmal!</span>').show();
				button.removeClass("btn-loading");
				
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
						field.closest(".field-line").addClass("line-error");
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
		
		var submit_form = function() 
		{		
			if (!form.data('is_submitting')) {
				var data = fullSerializeFormArray();
		
				if (typeof ihandlevars == 'object') {
					var data2 = {}, i;
					for (i in data) {
						data2[data[i].name] = data[i].value;
					}
					var values = [], keys = array_keys(data2), i;
					keys.sort();
					for (i in keys) {
						values.push(data2[keys[i]].trim());
					}
					data.push({name: 'secure2', value: md5(values.join('data'))});
					
					for (i in ihandlevars) {
						data.push({name: i, value: ihandlevars[i]});
					}
				}
				
				button.addClass("btn-loading");
				button.blur();
				
				form.data('is_submitting', true);
				
				$.ajax({
					type: "POST",
					dataType: "json",
					url: ajax_endpoint,
					data: data,
					complete: function (xhr) {
						ajax_form_complete(xhr.responseJSON, xhr.status);
					},
				});
			}
		}
		
	});
};

$.fn.ajax_form_button.defaults = {
	success: null,
	warning: null,
	error: null,
};

