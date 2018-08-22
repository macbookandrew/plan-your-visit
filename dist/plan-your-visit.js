/**
 * JS features
 *
 * @package PlanYourVisit
 */

/* global ajaxurl, jQuery */

'use strict';

(function($) {

	/**
	 * Save key to database.
	 *
	 * @since  1.0.0
	 *
	 * @param {string} key Authorization key.
	 *
	 * @returns {bool}     Whether key was saved succesfully or not.
	 */
	function saveKey(key) {
		var $message = $('.message');

		$.ajax({
			method: 'POST',
			url: ajaxurl,
			data: {
				action: 'plan_your_visit_save_key',
				key: key,
				email: $('[name="email"]').val(),
				nonce: $('[name="pyv_nonce"]').val()
			}
		}).success(function(data, status) {
			if ('success' === status && '1' === data) {
				$message.addClass('success').text('Installation was successful!');
			} else {
				$message.addClass('error').text('Something went wrong saving your data. Please try again.');
			}
		});
	}

	$('#church-hero-login').on('submit', function(e) {
		e.preventDefault();

		var $message = $('.message'),
			url = $(this).attr('action'),
			email = $(this).find('[name="email"]').val(),
			password = $(this).find('[name="password"]').val();

		$message.removeClass('success error').text('Loading…');

		$.ajax({
			method: 'POST',
			url: url,
			data: {
				email: email,
				password: password,
			}
		}).success(function(data, status) {
			if ('success' === status) {
				saveKey(data);
			} else {
				$message.addClass('error').text('Please check your login credentials.');
			}
		}).error(function(jqXHR, textStatus) {
			if ('error' === textStatus) {
				$message.addClass('error').text('Please check your login credentials.');
			}
		});

	});
}(jQuery));
