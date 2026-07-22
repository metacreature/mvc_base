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