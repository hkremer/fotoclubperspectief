(function ($) {
	'use strict';

	$(document).on('click', '.fcp-select-image', function (e) {
		e.preventDefault();
		var target = $(this).data('target');
		var $field = $(this).closest('.fcp-image-field');
		var frame = wp.media({
			title: 'Afbeelding kiezen',
			button: { text: 'Gebruiken' },
			multiple: false,
		});
		frame.on('select', function () {
			var att = frame.state().get('selection').first().toJSON();
			$('#' + target + '_id').val(att.id);
			var url = att.sizes && att.sizes.medium ? att.sizes.medium.url : att.url;
			$field.find('.fcp-image-preview').html('<img src="' + url + '" alt="" style="max-width:240px;height:auto;" />');
		});
		frame.open();
	});

	$(document).on('click', '.fcp-clear-image', function (e) {
		e.preventDefault();
		var target = $(this).data('target');
		$('#' + target + '_id').val('');
		$(this).closest('.fcp-image-field').find('.fcp-image-preview').empty();
	});
})(jQuery);
