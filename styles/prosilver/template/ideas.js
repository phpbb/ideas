(function($) { // Avoid conflicts with other libraries

	'use strict';

	var keymap = {
			TAB: 9,
			ENTER: 13,
			ESC: 27
		},
		$obj = {
			ideaTitle: $('.topic-title > a'),
			duplicateEdit: $('#duplicateedit'),
			duplicateEditInput: $('#duplicateeditinput'),
			duplicateLink: $('#duplicatelink'),
			duplicateToggle: $('.duplicatetoggle'),
			ticketEdit: $('#ticketedit'),
			ticketEditInput: $('#ticketeditinput'),
			ticketLink: $('#ticketlink'),
			rfcEdit: $('#rfcedit'),
			rfcEditInput: $('#rfceditinput'),
			rfcLink: $('#rfclink'),
			implementedEdit: $('#implementededit'),
			implementedEditInput: $('#implementededitinput'),
			implementedVersion: $('#implementedversion'),
			implementedToggle: $('.implementedtoggle'),
			removeVote: $('.removevote'),
			status: $('#status'),
			successVoted: $('.successvoted'),
			userVoted: $('.user-voted'),
			votes: $('.votes'),
			votesList: $('.voteslist'),
			voteDown: $('.minivotedown'),
			voteUp: $('.minivoteup'),
			voteRemove: $('#vote-remove')
		};

	function voteSuccess(result, $this) {
		if (typeof result === 'string') {
			phpbb.alert($this.attr('data-l-err'), $this.attr('data-l-msg') + ' ' + result);
		} else {
			$obj.voteUp.find('.vote-count').text(result.votes_up);
			$obj.voteDown.find('.vote-count').text(result.votes_down);
			$obj.votes.hide().text(function() {
				return result.points + ' ' + $(this).attr('data-l-msg');
			});
			$obj.successVoted.text(result.message)
				.css('display', 'inline-block')
				.delay(2000)
				.fadeOut(300, function() {
					$obj.votes.fadeIn(300);
				});
			displayVoters(result.voters);
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
			vote = $this.hasClass('minivoteup') ? 1 : 0;

		if ($this.hasClass('dead')) {
			return false;
		}

		$.get(url, {v: vote}, function(data) {
			voteSuccess(data, $this);
			resetVoteButtons($this);
			$obj.voteRemove.show();
		}).fail(voteFailure);
	});

	$obj.votes.on('click', function(e) {
		e.preventDefault();

		if ($obj.votesList.data('display')) {
			$obj.votesList.slideToggle();
		}
	});

	$obj.removeVote.on('click', function(e) {
		e.preventDefault();

		var $this = $(this),
			url = $this.attr('href');

		if ($this.hasClass('dead')) {
			return false;
		}

		$.get(url, function(data) {
			voteSuccess(data, $this);
			resetVoteButtons();
			$obj.voteRemove.hide();
		}).fail(voteFailure);
	});

	$obj.status.on('change', function() {
		var $this = $(this),
			data = {
				mode: 'status',
				status: $this.val()
			};

		if (!data.status) {
			return;
		}

		$.get($this.attr('data-url'), data, function(res) {
			if (res) {
				var anchor = $this.prev('a'),
					href = anchor.attr('href');

				href = href.replace(/status=\d/, 'status=' + data.status);

				anchor.attr('href', href)
					.text($this.find(':selected').text())
					.removeClass()
					.addClass('status-badge status-' + $this.find(':selected').val());

				$obj.duplicateToggle.toggle(idea_is_duplicate());
				$obj.implementedToggle.toggle(idea_is_implemented());
			}
		});
	});

	$obj.rfcEdit.on('click', function(e) {
		e.preventDefault();

		$obj.rfcEdit.add($obj.rfcLink).hide();
		$obj.rfcEditInput.show().trigger('focus');
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
					$obj.rfcLink.text(value)
						.attr('href', value);

					if (value) {
						$obj.rfcLink.show();
					}

					$this.hide();

					$obj.rfcEdit.toggleAddEdit(value);
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
		$obj.ticketEditInput.show().trigger('focus');
	});

	$obj.ticketEditInput.on('keydown', function(e) {
		if (e.keyCode === keymap.ENTER) {
			e.preventDefault();
			e.stopPropagation();

			var $this = $(this),
				url = $obj.ticketEdit.attr('href'),
				value = $this.val(),
				info;

			if (value && !(info = /^PHPBB3-(\d{1,6})$/.exec(value))) {
				phpbb.alert($this.attr('data-l-err'), $this.attr('data-l-msg'));
				return;
			}

			if (value) {
				value = 'PHPBB3-' + info[1];
			}

			$.get(url, {ticket: value && info[1]}, function(res) {
				if (res) {
					$obj.ticketLink.text(value)
						.attr('href', 'https://tracker.phpbb.com/browse/' + value);

					if (value) {
						$obj.ticketLink.show();
					}

					$this.hide();

					$obj.ticketEdit.toggleAddEdit(value);
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

	$obj.duplicateEdit.on('click', function(e) {
		e.preventDefault();

		$obj.duplicateEdit.add($obj.duplicateLink).hide();
		$obj.duplicateEditInput.show().trigger('focus');
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
						var link = $obj.duplicateLink.attr('data-link').replace(/^(.*\/)(\d+)$/, '$1');

						$obj.duplicateLink
							.text(msg + value)
							.attr('href', link + value)
							.show();
					} else {
						$obj.duplicateLink
							.text(value)
							.attr('href', value);
					}

					$this.hide();

					$obj.duplicateEdit.toggleAddEdit(value);
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

	$obj.implementedEdit.on('click', function(e) {
		e.preventDefault();

		$obj.implementedEdit.add($obj.implementedVersion).hide();
		$obj.implementedEditInput.show().trigger('focus');
	});

	$obj.implementedEditInput.on('keydown', function(e) {
		if (e.keyCode === keymap.ENTER) {
			e.preventDefault();
			e.stopPropagation();

			var $this = $(this),
				find = /^\d\.\d\.\d+(-\w+)?$/,
				url = $obj.implementedEdit.attr('href'),
				value = $this.val();

			if (value && !find.test(value)) {
				phpbb.alert($this.attr('data-l-err'), $this.attr('data-l-msg'));
				return;
			}

			$.get(url, {implemented: value}, function(res) {
				if (res) {
					$obj.implementedVersion.text(value);

					if (value) {
						$obj.implementedVersion.show();
					}

					$this.hide();

					$obj.implementedEdit.toggleAddEdit(value);
				}
			});
		} else if (e.keyCode === keymap.ESC) {
			e.preventDefault();

			$(this).hide();
			$obj.implementedEdit.show();

			if ($obj.implementedVersion.text()) {
				$obj.implementedVersion.show();
			}
		}
	});

	$.fn.toggleAddEdit = function(value) {
		$(this).text(function() {
			return value ? $(this).attr('data-l-edit') : $(this).attr('data-l-add');
		}).prepend($('<i class="fa fa-fw"></i>').addClass(function() {
			return value ? 'fa-pencil' : 'fa-plus-circle';
		})).show();
	};

	/**
	 * Returns true if idea is a duplicate. Bit hacky.
	 */
	function idea_is_duplicate() {
		var href = $obj.status.prev('a').attr('href');
		return href && href.indexOf('status=4') !== -1;
	}

	/**
	 * Returns true if idea is implemented. Bit hacky.
	 */
	function idea_is_implemented() {
		var href = $obj.status.prev('a').attr('href');
		return href && href.indexOf('status=3') !== -1;
	}

	function displayVoters(data) {

		var upVoters = [],
			downVoters = [];

		for (var i = 0; i < data.length; i++) {
			if (data[i].vote_value === '1') {
				upVoters.push(data[i].user);
			} else if (data[i].vote_value === '0') {
				downVoters.push(data[i].user);
			}
		}

		var hasUpVotes = upVoters.length > 0,
			hasDownVotes = downVoters.length > 0;

		$('#up-voters')
			.toggle(hasUpVotes)
			.find('span')
			.html(upVoters.join(', '));
		$('#down-voters')
			.toggle(hasDownVotes)
			.find('span')
			.html(downVoters.join(', '));

		$obj.votesList
			.attr('data-display', (hasUpVotes || hasDownVotes))
			.toggle(($obj.votesList.is(':visible') && (hasUpVotes || hasDownVotes)));
	}

	function resetVoteButtons($this) {
		$obj.voteUp.add($obj.voteDown).removeClass('dead');
		$obj.userVoted.hide();

		if ($this) {
			$this.addClass('dead').find($obj.userVoted).show();
		}
	}

})(jQuery); // Avoid conflicts with other libraries
