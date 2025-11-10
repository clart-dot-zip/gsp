import '../css/app.css';
import './bootstrap';

import jQuery from 'jquery';

window.$ = window.jQuery = jQuery;

import 'admin-lte/plugins/fontawesome-free/css/all.min.css';
import 'overlayscrollbars/css/OverlayScrollbars.min.css';
import 'admin-lte/dist/css/adminlte.min.css';
import 'admin-lte/plugins/bootstrap/js/bootstrap.bundle.min.js';
import 'admin-lte/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js';
import 'admin-lte';

document.addEventListener('DOMContentLoaded', () => {
	document.querySelectorAll('[data-permission-accordion]').forEach((accordion) => {
		const panels = Array.from(accordion.querySelectorAll('.permission-collapse'));
		const toggles = Array.from(accordion.querySelectorAll('[data-permission-target]'));

		panels.forEach((panel) => {
			if (!panel.dataset.defaultOpen) {
				panel.classList.add('d-none');
			}
		});

		toggles.forEach((toggle) => {
			if (!toggle.hasAttribute('aria-expanded')) {
				toggle.setAttribute('aria-expanded', 'false');
			}
		});

		accordion.addEventListener('click', (event) => {
			const button = event.target instanceof HTMLElement ? event.target.closest('[data-permission-target]') : null;
			if (!button || !accordion.contains(button)) {
				return;
			}

			event.preventDefault();

			const targetSelector = button.getAttribute('data-permission-target');
			if (!targetSelector) {
				return;
			}

			const target = document.querySelector(targetSelector);
			if (!target) {
				return;
			}

			const isExpanded = button.getAttribute('aria-expanded') === 'true';

			if (isExpanded) {
				button.setAttribute('aria-expanded', 'false');
				target.classList.add('d-none');
				return;
			}

			toggles.forEach((toggle) => {
				const selector = toggle.getAttribute('data-permission-target');
				if (!selector) {
					return;
				}

				const panel = document.querySelector(selector);
				const shouldOpen = toggle === button;

				toggle.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');

				if (panel) {
					panel.classList.toggle('d-none', !shouldOpen);
				}
			});
		});
	});
});
