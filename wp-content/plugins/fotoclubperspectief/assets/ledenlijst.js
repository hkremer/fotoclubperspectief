/**
 * Ledenlijst: kolom-weergave en rij-filter (frontend).
 */
(function () {
	'use strict';

	/**
	 * @param {string} col
	 * @returns {'address'|'phone'|'email'|'lidnr'|''}
	 */
	function colGroup(col) {
		if (col === 'adres' || col === 'postcode' || col === 'plaats') {
			return 'address';
		}
		if (col === 'telefoon') {
			return 'phone';
		}
		if (col === 'email') {
			return 'email';
		}
		if (col === 'lidnr_fotobond') {
			return 'lidnr';
		}
		return '';
	}

	/**
	 * @param {HTMLElement} root
	 */
	function init(root) {
		var table = root.querySelector('table.fcp-ledenlijst');
		if (!table) {
			return;
		}
		var tbody = table.querySelector('tbody');
		if (!tbody) {
			return;
		}
		var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr'));
		var toggleAddr = root.querySelector('.fcp-ledenlijst-toggle-address');
		var togglePhone = root.querySelector('.fcp-ledenlijst-toggle-phone');
		var toggleEmail = root.querySelector('.fcp-ledenlijst-toggle-email');
		var toggleLidnr = root.querySelector('.fcp-ledenlijst-toggle-lidnr');

		function applyColumnVisibility() {
			var showAddr = !toggleAddr || toggleAddr.checked;
			var showPhone = !togglePhone || togglePhone.checked;
			var showEmail = !toggleEmail || toggleEmail.checked;
			var showLidnr = !toggleLidnr || toggleLidnr.checked;
			var cells = table.querySelectorAll('[data-fcp-col]');
			for (var i = 0; i < cells.length; i++) {
				var el = cells[i];
				var key = el.getAttribute('data-fcp-col') || '';
				var g = colGroup(key);
				if (g === 'address') {
					el.hidden = !showAddr;
				} else if (g === 'phone') {
					el.hidden = !showPhone;
				} else if (g === 'email') {
					el.hidden = !showEmail;
				} else if (g === 'lidnr') {
					el.hidden = !showLidnr;
				} else {
					el.hidden = false;
				}
			}
		}

		function applyRowStriping() {
			var n = 0;
			for (var r = 0; r < rows.length; r++) {
				var tr = rows[r];
				tr.classList.remove('fcp-ledenlijst-tr--alt');
				if (tr.hidden) {
					continue;
				}
				if (n % 2 === 1) {
					tr.classList.add('fcp-ledenlijst-tr--alt');
				}
				n++;
			}
		}

		function applyRowFilter() {
			var selected = root.querySelector('.fcp-ledenlijst-filter-role:checked');
			var role = selected && selected.value ? selected.value : '';
			for (var r = 0; r < rows.length; r++) {
				var tr = rows[r];
				if (role === '') {
					tr.hidden = false;
					continue;
				}
				var show = tr.getAttribute('data-fcp-bool-' + role) === '1';
				tr.hidden = !show;
			}
			applyRowStriping();
		}

		function refresh() {
			applyColumnVisibility();
			applyRowFilter();
		}

		if (toggleAddr) {
			toggleAddr.addEventListener('change', refresh);
		}
		if (togglePhone) {
			togglePhone.addEventListener('change', refresh);
		}
		if (toggleEmail) {
			toggleEmail.addEventListener('change', refresh);
		}
		if (toggleLidnr) {
			toggleLidnr.addEventListener('change', refresh);
		}
		var roleFilters = root.querySelectorAll('.fcp-ledenlijst-filter-role');
		for (var f = 0; f < roleFilters.length; f++) {
			roleFilters[f].addEventListener('change', applyRowFilter);
		}

		refresh();
	}

	var roots = document.querySelectorAll('[data-fcp-ledenlijst-root]');
	for (var k = 0; k < roots.length; k++) {
		init(roots[k]);
	}
})();
