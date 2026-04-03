(function ($) {
	'use strict';

	var medIndex = $('#fcp-mededelingen .fcp-mededeling-row').length;

	function bindRemove() {
		$('#fcp-mededelingen').off('click', '.fcp-remove-med').on('click', '.fcp-remove-med', function () {
			if ($('#fcp-mededelingen .fcp-mededeling-row').length < 2) {
				$(this).closest('.fcp-mededeling-row').find('input,textarea').val('');
				return;
			}
			$(this).closest('.fcp-mededeling-row').remove();
		});
	}

	$('#fcp-add-med').on('click', function () {
		var nameBase = 'fcp_home_options[mededelingen][' + medIndex + ']';
		var html =
			'<div class="fcp-mededeling-row" style="border:1px solid #ccd0d4;padding:12px;margin-bottom:12px;background:#fff;">' +
			'<p><label>Kop<br /><input type="text" class="large-text" name="' +
			nameBase +
			'[title]" value="" /></label></p>' +
			'<p><label>Tekst<br /><textarea class="large-text" rows="5" name="' +
			nameBase +
			'[content]"></textarea></label></p>' +
			'<p><button type="button" class="button fcp-remove-med">Verwijder dit blok</button></p>' +
			'</div>';
		$('#fcp-mededelingen').append(html);
		medIndex += 1;
		bindRemove();
	});

	bindRemove();

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
