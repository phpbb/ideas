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
			status: $('.change-status'),
			statusLink: $('#status-link'),
			successVoted: $('.successvoted'),
			userVoted: $('.user-voted'),
			votes: $('.votes'),
			votesList: $('.voteslist'),
			voteDown: $('.vote-down'),
			voteUp: $('.vote-up'),
			voteRemove: $('#vote-remove')
		}, $loadingIndicator;

	/**
	 * @param {Object} $this
	 * @param {Object} result
	 * @param {string} result.message
	 * @param {string} result.points
	 * @param {Object} result.voters
	 * @param {string} result.votes_up
	 * @param {string} result.votes_down
	 */
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
			vote = $this.is($obj.voteUp) ? 1 : 0;

		if ($this.hasClass('vote-disabled')) {
			return false;
		}

		showLoadingIndicator();
		$.get(url, {
			v: vote
		}, function(data) {
			voteSuccess(data, $this);
			resetVoteButtons($this);
			$obj.voteRemove.show();
		}).fail(voteFailure).always(hideLoadingIndicator);
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

		if ($this.hasClass('vote-disabled')) {
			return false;
		}

		showLoadingIndicator();
		$.get(url, function(data) {
			voteSuccess(data, $this);
			resetVoteButtons();
			$obj.voteRemove.hide();
		}).fail(voteFailure).always(hideLoadingIndicator);
	});

	$obj.status.on('click', function(e) {
		e.preventDefault();

		var $this = $(this),
			data = {
				status: $this.attr('data-status')
			};

		if (!data.status) {
			return;
		}

		showLoadingIndicator();
		$.get($this.attr('href'), data, function(res) {
			if (res) {
				var href = $obj.statusLink.attr('href').replace(/status=\d/, 'status=' + data.status);

				$obj.statusLink.attr('href', href)
					.html($this.html())
					.closest('span')
					.removeClass()
					.addClass('status-badge status-' + data.status);

				$obj.duplicateToggle.toggle(data.status === '4');
				$obj.implementedToggle.toggle(data.status === '3');
			}
		}).always([
			hideLoadingIndicator,
			hideStatusDropDown
		]);
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

			showLoadingIndicator();
			$.get(url, {
				rfc: value
			}, function(res) {
				if (res) {
					$obj.rfcLink.text(value)
						.attr('href', value);

					if (value) {
						$obj.rfcLink.show();
					}

					$this.hide();

					$obj.rfcEdit.toggleAddEdit(value);
				}
			}).always(hideLoadingIndicator);
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

			if (value && !(info = /^PHPBB3?-(\d{1,6})$/.exec(value))) {
				phpbb.alert($this.attr('data-l-err'), $this.attr('data-l-msg'));
				return;
			}

			if (value) {
				value = 'PHPBB-' + info[1];
			}

			showLoadingIndicator();
			$.get(url, {
				ticket: value && info[1]
			}, function(res) {
				if (res) {
					$obj.ticketLink.text(value)
						.attr('href', 'https://tracker.phpbb.com/browse/' + value);

					if (value) {
						$obj.ticketLink.show();
					}

					$this.hide();

					$obj.ticketEdit.toggleAddEdit(value);
				}

			}).always(hideLoadingIndicator);
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

	/**
	 * This callback handles live idea title searches for duplicate ideas.
	 */
	phpbb.addAjaxCallback('idea_search', function(res) {
		phpbb.search.handleResponse(res, $(this), false, phpbb.getFunctionByName('phpbb.search.setDuplicateOnEvent'));
	});

	/**
	 * This performs actions on each result from the live idea title search for duplicate ideas.
	 *
	 * @param {jQuery} $input		Search input|textarea.
	 * @param {object} value		Result object.
	 * @param {jQuery} $row			Result element.
	 * @param {jQuery} $container	jQuery object for the search container.
	 */
	phpbb.search.setDuplicateOnEvent = function($input, value, $row, $container) {
		$row.on('click', function() {
			setDuplicate($input, value);
			phpbb.search.closeResults($input, $container);
		});
	};

	/**
	 * Assign a duplicate idea identifier to a given idea.
	 *
	 * @param {jQuery} $input	Search input|textarea.
	 * @param {object} value	Result object.
	 */
	function setDuplicate($input, value) {
		if (value.result && isNaN(Number(value.result))) {
			phpbb.alert($input.attr('data-l-err'), $input.attr('data-l-msg'));
			return;
		}
		$input.val(value.clean_title);
		showLoadingIndicator();
		$.get($obj.duplicateEdit.attr('href'), {
			duplicate: Number(value.result)
		}, function(res) {
			if (res) {
				if (value.result) {
					$obj.duplicateLink
						.text(value.clean_title)
						.attr('href', $obj.duplicateLink.attr('data-link').replace(/^(.*\/)(\d+)$/, '$1') + value.result)
						.show();
				} else {
					$obj.duplicateLink
						.empty()
						.removeAttr('href');
				}
				$input.hide();
				$obj.duplicateEdit.toggleAddEdit(value.result);
			}
		}).always(hideLoadingIndicator);
	}

	/**
	 * Handling of the duplicate idea input field.
	 * ENTER: When the input field is empty clear any existing duplicate entry. Otherwise, just show an alert message.
	 * ESC: Will clear and close the input field (if it isn't cleared, live search may unexpectedly run).
	 */
	$obj.duplicateEditInput.on('keydown.duplicate', function(e) {
		var $this = $(this),
			key = e.keyCode || e.which;
		if (key === keymap.ESC) {
			$this.val('').hide();
			$obj.duplicateEdit.show();
			$obj.duplicateLink.toggle($obj.duplicateLink.html().length !== 0);
		} else if (key === keymap.ENTER) {
			if ($this.val().length === 0) {
				setDuplicate($this, {
					'result': '',
					'clean_title': ''
				});
			} else {
				e.stopPropagation();
				phpbb.alert($this.attr('data-l-err'), $this.attr('data-l-msg'));
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

			showLoadingIndicator();
			$.get(url, {
				implemented: value
			}, function(res) {
				if (res) {
					$obj.implementedVersion.text(value);

					if (value) {
						$obj.implementedVersion.show();
					}

					$this.hide();

					$obj.implementedEdit.toggleAddEdit(value);
				}
			}).always(hideLoadingIndicator);
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
		$(this)
			.find('.icon-edit').toggle(value)
			.end()
			.find('.icon-add').toggle(!value)
			.end()
			.show();
	};

	function hideStatusDropDown() {
		$('.status-dropdown').hide();
	}

	/**
	 * @param {Object} data
	 * @param {number} data.length
	 * @param {string} data.user
	 * @param {string} data.vote_value
	 */
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
		$obj.voteUp.add($obj.voteDown).removeClass('vote-disabled');
		$obj.userVoted.hide();

		if ($this) {
			$this.addClass('vote-disabled').find($obj.userVoted).show();
		}
	}

	function showLoadingIndicator() {
		$loadingIndicator = phpbb.loadingIndicator();
	}

	function hideLoadingIndicator() {
		if ($loadingIndicator && $loadingIndicator.is(':visible')) {
			$loadingIndicator.fadeOut(phpbb.alertTime);
		}
	}

})(jQuery); // Avoid conflicts with other libraries
