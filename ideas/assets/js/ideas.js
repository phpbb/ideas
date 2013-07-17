$('.voteup, .votedown').click(function (e) {
	"use strict";
	e.preventDefault();

	var $this = $(this),
		url = $this.attr('href'),
		vote = $this.is('.voteup') ? 1 : 0;

	if ($this.is('.dead')) {
		return false;
	}

	$.get(url, {v: vote}, function (message) {
		if (typeof message === 'string') {
			alert('An error occurred: ' + message); // Error
		} else {
			$('.voteup:first').html('<span>' + message.votes_up + '</span>');
			$('.votedown:first').html('<span>' + message.votes_down + '</span>');
			$('.votes').hide()
				.text('(' + message.points + ' points. Click to view votes)');
			$('.successvoted').text('   ' + message.message)
				.show()
				.delay(2000)
					.fadeOut(300, function () {
						$('.votes').fadeIn(300);
					});
		}
	});
});

$('.voteup-button, .minivoteup').click(function () {
	$('.voteup-button a').addClass("voted");
	$('.votedown-button a').removeClass("voted");
});

$('.votedown-button, .minivotedown').click(function () {
	$('.votedown-button a').addClass("voted");
	$('.voteup-button a').removeClass("voted");
});

$('a.dead').attr('href', '#');

$('.votes').click(function (e) {
	"use strict";
	e.preventDefault();

	$('.voteslist').slideToggle();
});

$('.confirm').click(function () {
	"use strict";

	return confirm('Really delete idea?'); // EVERYTHING IS BLEEDING
});

$('#status').change(function () {
	"use strict";

	var $this = $(this),
		data = {
			mode: 'status',
			status: $this.val()
		};

	if (data.status === '-') {
		return;
	}

	$.get($this.attr('data-url'), data, function () {
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

$('#rfcedit').click(function (e) {
	"use strict";
	e.preventDefault();

	$('#rfcedit, #rfclink').hide();
	$('#rfceditinput').show().focus();
});

$('#rfceditinput').keydown(function (e) {
	"use strict";

	if (e.keyCode === 13) {
		e.preventDefault();

		var $this = $(this),
			find = /^https?:\/\/area51\.phpbb\.com\/phpBB\/viewtopic\.php/,
			url = $('#rfcedit').attr('href'),
			value = $this.val();

		if (value && !find.test(value)) {
			alert('Error: RFC must be a topic on Area51.');
			return;
		}

		$.get(url, {rfc: value}, function () {
			$('#rfclink').text(value)
				.attr('href', value)
				.show();

			$this.hide();

			$('#rfcedit').text(value ? 'Edit' : 'Add').show();
		});
	} else if (e.keyCode === 27) {
		e.preventDefault();

		var $link = $('#rfclink');

		$(this).hide();
		$('#rfcedit').show();

		if ($link.html()) {
			$link.show();
		}
	}
});

$('#ticketedit').click(function (e) {
	"use strict";
	e.preventDefault();

	$('#ticketedit, #ticketlink').hide();
	$('#ticketeditinput').show().focus();
});

$('#ticketeditinput').keydown(function (e) {
	"use strict";

	if (e.keyCode === 13) {
		e.preventDefault();

		var $this = $(this),
			url = $('#ticketedit').attr('href'),
			value = $this.val(),
			info;

		if (value && !(info = /^PHPBB3\-(\d{1,6})$/.exec(value))) {
			alert('Error: Ticket ID must be of the format "PHPBB3-#####".');
			return;
		}

		if (value) {
			value = 'PHPBB3-' + info[1];
		}

		$.get(url, {ticket: value && info[1]}, function () {
			$('#ticketlink').text(value)
				.attr('href', 'http://tracker.phpbb.com/browse/' + value)
				.show();

			$this.hide();

			$('#ticketedit').text(value ? 'Edit' : 'Add').show();
		});
	} else if (e.keyCode === 27) {
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

$('#duplicateedit').click(function (e) {
	"use strict";
	e.preventDefault();

	$('#duplicateedit, #duplicatelink').hide();
	$('#duplicateeditinput').show().focus();
});

$('#duplicateeditinput').keydown(function (e) {
	"use strict";

	if (e.keyCode === 13) {
		e.preventDefault();

		var $this = $(this),
			url = $('#duplicateedit').attr('href'),
			value = $this.val();

		if (value && isNaN(Number(value))) {
			alert('Error: Please post the ID of the ticket.');
			return;
		}

		$.get(url, {duplicate: Number(value)}, function () {
			if (value) {
				$('#duplicatelink').text('idea.php?id=' + value)
					.attr('href', 'idea.php?id=' + value)
					.show();
			}

			$this.hide();

			$('#duplicateedit').show();
		});
	} else if (e.keyCode === 27) {
		e.preventDefault();

		var $link = $('#duplicatelink');

		$(this).hide();
		$('#duplicateedit').show();

		if ($link.html()) {
			$link.show();
		}
	}
});

$('#titleedit').click(function (e) {
	"use strict";
	e.preventDefault();

	$('#ideatitle').hide();
	$('#titleeditinput').show().focus();
});

$('#titleeditinput').keydown(function (e) {
	"use strict";

	if (e.keyCode === 13) {
		e.preventDefault();

		var $this = $(this),
			url = $('#titleedit').attr('href'),
			value = $this.val();

		if (value.length < 6 || value.length > 64) {
			alert('Error: Title must be between 6 and 64 characters.');
			return;
		}

		$.get(url, {title: value}, function () {
			$('#ideatitle').text(value).show();
			$this.hide();
		});
	} else if (e.keyCode === 27) {
		e.preventDefault();

		$('#ideatitle').show();
		$(this).hide();
	}
});

/**
 * Returns true if idea is a duplicate. Bit hacky.
 */
function idea_is_duplicate() {
	"use strict";

	var href = $('#status').prev('a').attr('href');
	return href.indexOf('status=4') !== -1;
}

/**
 * Set display of page element
 * s[-1,0,1] = hide,toggle display,show
 * type = string: inline, block, inline-block or other CSS "display" type
 *
 * WHY DOES THIS FUNCTION EVEN EXIST
 */
function dE(n, s, type)
{
	if (!type)
	{
		type = 'block';
	}

	var e = document.getElementById(n);
	if (!s)
	{
		s = (e.style.display == '' || e.style.display == type) ? -1 : 1;
	}
	e.style.display = (s == 1) ? type : 'none';
}
