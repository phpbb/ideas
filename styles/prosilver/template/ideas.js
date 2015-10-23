(function($) { // Avoid conflicts with other libraries

	'use strict';

	var keymap = {
			TAB: 9,
			ENTER: 13,
			ESC: 27
		},
		$obj = {
			ideaTitle: $('#ideatitle'),
			duplicateEdit: $('#duplicateedit'),
			duplicateEditInput: $('#duplicateeditinput'),
			duplicateLink: $('#duplicatelink'),
			duplicateToggle: $('.duplicatetoggle'),
			ticketEdit: $('#ticketedit'),
			ticketEditInput: $('#ticketeditinput'),
			ticketLink: $('#ticketlink'),
			titleEdit: $('#titleedit'),
			titleEditInput: $('#titleeditinput'),
			rfcEdit: $('#rfcedit'),
			rfcEditInput: $('#rfceditinput'),
			rfcLink: $('#rfclink'),
			removeVote: $('.removevote'),
			status: $('#status'),
			successVoted: $('.successvoted'),
			votes: $('.votes'),
			votesList: $('.voteslist'),
			voteDown: $('.votedown'),
			voteUp: $('.voteup')
		};

	function voteSuccess(message, $this) {
		if (typeof message === 'string') {
			phpbb.alert($this.attr('data-l-err'), $this.attr('data-l-msg') + ' ' + message);
		} else {
			$obj.voteUp.first().html('<span>' + message.votes_up + '</span>');
			$obj.voteDown.first().html('<span>' + message.votes_down + '</span>');
			$obj.votes.hide().text(function() {
				return $(this).attr('data-l-msg').replace('%s', message.points);
			});
			$obj.successVoted.text(message.message)
				.show()
				.delay(2000)
				.fadeOut(300, function() {
					$obj.votes.fadeIn(300);
				});
		}
	}

	function voteFailure() {
		$obj.votes.hide();
		$obj.successVoted.text(function(){
			return $(this).attr('data-l-err');
		})
			.show()
			.delay(2000)
			.fadeOut(300, function() {
				$obj.votes.fadeIn(300);
			});
	}

	$obj.voteUp.add($obj.voteDown).on('click', function(e) {
		e.preventDefault();

		var $this = $(this),
			url = $this.attr('href'),
			vote = $this.is('.voteup') ? 1 : 0;

		if ($this.is('.dead')) {
			return false;
		}

		$.get(url, {v: vote}, function(data) {
			voteSuccess(data, $this);
		}).fail(voteFailure);
	});

	$obj.votes.on('click', function(e) {
		e.preventDefault();

		$obj.votesList.slideToggle();
	});

	$obj.removeVote.on('click', function(e) {
		e.preventDefault();

		var $this = $(this),
			url = $this.attr('href');

		if ($this.is('.dead')) {
			return false;
		}

		$.get(url, function(data) {
			voteSuccess(data, $this);
		}).fail(voteFailure);

	});

	$obj.status.change(function() {
		var $this = $(this),
			data = {
				mode: 'status',
				status: $this.val()
			};

		if (data.status === '-') {
			return;
		}

		$.get($this.attr('data-url'), data, function(res) {
			if (res) {
				var anchor = $this.prev('a'),
					href = anchor.attr('href');

				href = href.replace(/status=\d/, 'status=' + data.status);

				anchor.attr('href', href)
					.text($this.find(':selected').text());

				if (idea_is_duplicate()) {
					$obj.duplicateToggle.show();
				} else {
					$obj.duplicateToggle.hide();
				}
			}
		});
	});

	$obj.rfcEdit.on('click', function(e) {
		e.preventDefault();

		$obj.rfcEdit.add($obj.rfcLink).hide();
		$obj.rfcEditInput.show().focus();
	});

	$obj.rfcEditInput.on('keydown', function(e) {
		if (e.keyCode === keymap.ENTER) {
			e.preventDefault();
			e.stopPropagation();

			var $this = $(this),
				find = /^https?:\/\/area51\.phpbb\.com\/phpBB\/viewtopic\.php/,
				url = $obj.rfcEdit.attr('href'),
				value = $this.val();

			if (value && !find.test(value)) {
				phpbb.alert($this.attr('data-l-err'), $this.attr('data-l-msg'));
				return;
			}

			$.get(url, {rfc: value}, function(res) {
				if (res) {
					if (value) {
						$obj.rfcLink.text(value)
							.attr('href', value)
							.show();
					}

					$this.hide();

					$obj.rfcEdit.text(function() {
						return value ? $(this).attr('data-l-edit') : $(this).attr('data-l-add');
					}).show();
				}
			});
		} else if (e.keyCode === keymap.ESC) {
			e.preventDefault();

			var $link = $obj.rfcLink;

			$(this).hide();
			$obj.rfcEdit.show();

			if ($link.html()) {
				$link.show();
			}
		}
	});

	$obj.ticketEdit.on('click', function(e) {
		e.preventDefault();

		$obj.ticketEdit.add($obj.ticketLink).hide();
		$obj.ticketEditInput.show().focus();
	});

	$obj.ticketEditInput.on('keydown', function(e) {
		if (e.keyCode === keymap.ENTER) {
			e.preventDefault();
			e.stopPropagation();

			var $this = $(this),
				url = $obj.ticketEdit.attr('href'),
				value = $this.val(),
				info;

			if (value && !(info = /^PHPBB3\-(\d{1,6})$/.exec(value))) {
				phpbb.alert($this.attr('data-l-err'), $this.attr('data-l-msg'));
				return;
			}

			if (value) {
				value = 'PHPBB3-' + info[1];
			}

			$.get(url, {ticket: value && info[1]}, function(res) {
				if (res) {
					if (value) {
						$obj.ticketLink.text(value)
							.attr('href', 'https://tracker.phpbb.com/browse/' + value)
							.show();
					}

					$this.hide();

					$obj.ticketEdit.text(function() {
						return value ? $(this).attr('data-l-edit') : $(this).attr('data-l-add');
					}).show();
				}

			});
		} else if (e.keyCode === keymap.ESC) {
			e.preventDefault();

			var $link = $obj.ticketLink;

			$(this).hide();
			$obj.ticketEdit.show();

			if ($link.html()) {
				$link.show();
			}
		}
	});

	// Hide duplicate column if status is not duplicate
	if (!idea_is_duplicate()) {
		$obj.duplicateToggle.hide();
	}

	$obj.duplicateEdit.on('click', function(e) {
		e.preventDefault();

		$obj.duplicateEdit.add($obj.duplicateLink).hide();
		$obj.duplicateEditInput.show().focus();
	});

	$obj.duplicateEditInput.on('keydown', function(e) {
		if (e.keyCode === keymap.ENTER) {
			e.preventDefault();
			e.stopPropagation();

			var $this = $(this),
				url = $obj.duplicateEdit.attr('href'),
				value = $this.val();

			if (value && isNaN(Number(value))) {
				phpbb.alert($this.attr('data-l-err'), $this.attr('data-l-msg'));
				return;
			}

			$.get(url, {duplicate: Number(value)}, function(res) {
				if (res) {
					if (value) {
						var msg = $obj.duplicateLink.attr('data-l-msg');
						var link = $obj.duplicateLink.attr('data-link').replace(/(^.*\/)(\d)$/, '$1');

						$obj.duplicateLink
							.text(msg)
							.attr('href', link + value)
							.show();
					}

					$this.hide();

					$obj.duplicateEdit.show();
				}
			});
		} else if (e.keyCode === keymap.ESC) {
			e.preventDefault();

			var $link = $obj.duplicateLink;

			$(this).hide();
			$obj.duplicateEdit.show();

			if ($link.html()) {
				$link.show();
			}
		}
	});

	$obj.titleEdit.on('click', function(e) {
		e.preventDefault();

		$obj.ideaTitle.hide();
		$obj.titleEditInput.show().focus();
	});

	$obj.titleEditInput.on('keydown', function(e) {
		if (e.keyCode === keymap.ENTER) {
			e.preventDefault();
			e.stopPropagation();

			var $this = $(this),
				url = $obj.titleEdit.attr('href'),
				value = $this.val();

			if (value.length < 6 || value.length > 64) {
				phpbb.alert($this.attr('data-l-err'), $this.attr('data-l-msg'));
				return;
			}

			$.get(url, {title: value}, function(res) {
				if (res) {
					$obj.ideaTitle.text(value).show();
					$this.hide();
				}
			});
		} else if (e.keyCode === keymap.ESC) {
			e.preventDefault();

			$obj.ideaTitle.show();
			$(this).hide();
		}
	});

	/**
	 * Returns true if idea is a duplicate. Bit hacky.
	 */
	function idea_is_duplicate() {

		var href = $obj.status.prev('a').attr('href');
		return href && href.indexOf('status=4') !== -1;
	}

})(jQuery); // Avoid conflicts with other libraries
