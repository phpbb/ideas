(function($) { // Avoid conflicts with other libraries

	'use strict';

	// define a couple constants for keydown functions.
	var keymap = {
		TAB: 9,
		ENTER: 13,
		ESC: 27
	};

	function voteSuccess(message) {
		if (typeof message === 'string') {
			phpbb.alert('Error', 'An error occurred: ' + message); // Error
		} else {
			$('.voteup:first').html('<span>' + message.votes_up + '</span>');
			$('.votedown:first').html('<span>' + message.votes_down + '</span>');
			$('.votes').hide().text(function() {
				return $(this).attr('data-l-msg').replace('%s', message.points);
			});
			$('.successvoted').text(message.message)
				.show()
				.delay(2000)
				.fadeOut(300, function() {
					$('.votes').fadeIn(300);
				});
		}
	}

	function voteFailure() {
		$('.votes').hide();
		$('.successvoted').text(function(){
			return $(this).attr('data-l-err');
		})
			.show()
			.delay(2000)
			.fadeOut(300, function() {
				$('.votes').fadeIn(300);
			});
	}

	$('.voteup, .votedown').click(function(e) {
		e.preventDefault();

		var $this = $(this),
			url = $this.attr('href'),
			vote = $this.is('.voteup') ? 1 : 0;

		if ($this.is('.dead')) {
			return false;
		}

		$.get(url, {v: vote}, voteSuccess).fail(voteFailure);
	});

	$('a.dead').attr('href', '#');

	$('.votes').click(function(e) {
		e.preventDefault();

		$('.voteslist').slideToggle();
	});

	$('.removevote').click(function(e) {
		e.preventDefault();

		var $this = $(this),
			url = $this.attr('href');

		if ($this.is('.dead')) {
			return false;
		}

		$.get(url, voteSuccess).fail(voteFailure);
	});

	$('.confirm').click(function(e) {
		return confirm($(this).attr('data-l-msg')); // EVERYTHING IS BLEEDING
	});

	$('#status').change(function() {
		var $this = $(this),
			data = {
				mode: 'status',
				status: $this.val()
			};

		if (data.status === '-') {
			return;
		}

		$.get($this.attr('data-url'), data, function() {
			var anchor = $this.prev('a'),
				href = anchor.attr('href');

			href = href.replace(/status=\d/, 'status=' + data.status);

			anchor.attr('href', href)
				.text($this.find(':selected').text());

			if (idea_is_duplicate()) {
				$('.duplicatetoggle').show();
			} else {
				$('.duplicatetoggle').hide();
			}
		});
	});

	$('#rfcedit').click(function(e) {
		e.preventDefault();

		$('#rfcedit, #rfclink').hide();
		$('#rfceditinput').show().focus();
	});

	$('#rfceditinput').keydown(function(e) {
		if (e.keyCode === keymap.ENTER) {
			e.preventDefault();
			e.stopPropagation();

			var $this = $(this),
				find = /^https?:\/\/area51\.phpbb\.com\/phpBB\/viewtopic\.php/,
				url = $('#rfcedit').attr('href'),
				value = $this.val();

			if (value && !find.test(value)) {
				phpbb.alert($this.attr('data-l-err'), $this.attr('data-l-msg'));
				return;
			}

			$.get(url, {rfc: value}, function() {
				$('#rfclink').text(value)
					.attr('href', value)
					.show();

				$this.hide();

				$('#rfcedit').text(function() {
					return value ? $(this).attr('data-l-edit') : $(this).attr('data-l-add');
				}).show();
			});
		} else if (e.keyCode === keymap.ESC) {
			e.preventDefault();

			var $link = $('#rfclink');

			$(this).hide();
			$('#rfcedit').show();

			if ($link.html()) {
				$link.show();
			}
		}
	});

	$('#ticketedit').click(function(e) {
		e.preventDefault();

		$('#ticketedit, #ticketlink').hide();
		$('#ticketeditinput').show().focus();
	});

	$('#ticketeditinput').keydown(function(e) {
		if (e.keyCode === keymap.ENTER) {
			e.preventDefault();
			e.stopPropagation();

			var $this = $(this),
				url = $('#ticketedit').attr('href'),
				value = $this.val(),
				info;

			if (value && !(info = /^PHPBB3\-(\d{1,6})$/.exec(value))) {
				phpbb.alert($this.attr('data-l-err'), $this.attr('data-l-msg'));
				return;
			}

			if (value) {
				value = 'PHPBB3-' + info[1];
			}

			$.get(url, {ticket: value && info[1]}, function() {
				$('#ticketlink').text(value)
					.attr('href', 'http://tracker.phpbb.com/browse/' + value)
					.show();

				$this.hide();

				$('#ticketedit').text(function() {
					return value ? $(this).attr('data-l-edit') : $(this).attr('data-l-add');
				}).show();
			});
		} else if (e.keyCode === keymap.ESC) {
			e.preventDefault();

			var $link = $('#ticketlink');

			$(this).hide();
			$('#ticketedit').show();

			if ($link.html()) {
				$link.show();
			}
		}
	});

	// Hide duplicate column if status is not duplicate
	if (!idea_is_duplicate()) {
		$('.duplicatetoggle').hide();
	}

	$('#duplicateedit').click(function(e) {
		e.preventDefault();

		$('#duplicateedit, #duplicatelink').hide();
		$('#duplicateeditinput').show().focus();
	});

	$('#duplicateeditinput').keydown(function(e) {
		if (e.keyCode === keymap.ENTER) {
			e.preventDefault();
			e.stopPropagation();

			var $this = $(this),
				url = $('#duplicateedit').attr('href'),
				value = $this.val();

			if (value && isNaN(Number(value))) {
				phpbb.alert($this.attr('data-l-err'), $this.attr('data-l-msg'));
				return;
			}

			$.get(url, {duplicate: Number(value)}, function() {
				if (value) {
					$('#duplicatelink').text('idea.php?id=' + value)
						.attr('href', 'idea.php?id=' + value)
						.show();
				}

				$this.hide();

				$('#duplicateedit').show();
			});
		} else if (e.keyCode === keymap.ESC) {
			e.preventDefault();

			var $link = $('#duplicatelink');

			$(this).hide();
			$('#duplicateedit').show();

			if ($link.html()) {
				$link.show();
			}
		}
	});

	$('#titleedit').click(function(e) {
		e.preventDefault();

		$('#ideatitle').hide();
		$('#titleeditinput').show().focus();
	});

	$('#titleeditinput').keydown(function(e) {
		if (e.keyCode === keymap.ENTER) {
			e.preventDefault();
			e.stopPropagation();

			var $this = $(this),
				url = $('#titleedit').attr('href'),
				value = $this.val();

			if (value.length < 6 || value.length > 64) {
				phpbb.alert($this.attr('data-l-err'), $this.attr('data-l-msg'));
				return;
			}

			$.get(url, {title: value}, function() {
				$('#ideatitle').text(value).show();
				$this.hide();
			});
		} else if (e.keyCode === keymap.ESC) {
			e.preventDefault();

			$('#ideatitle').show();
			$(this).hide();
		}
	});

	/**
	 * Returns true if idea is a duplicate. Bit hacky.
	 */
	function idea_is_duplicate() {

		var href = $('#status').prev('a').attr('href');
		return href && href.indexOf('status=4') !== -1;
	}

})(jQuery); // Avoid conflicts with other libraries
