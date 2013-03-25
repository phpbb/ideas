$('.voteup, .votedown').click(function (e) {
	"use strict";
	e.preventDefault();

	var $this = $(this),
		url = $this.attr('href'),
		vote = $this.is('.voteup') ? 1 : 0;

	$.get(url, {v: vote}, function (message) {
		if (typeof message === 'string') {
			alert('An error occurred: ' + message); // Error
		} else {
			$('.voteup:first').html('+' + message.votes_up);
			$('.votedown').html('-' + message.votes_down + ' ');
			$('.votes').text('(' + message.points + ')');

			setTimeout(function () {
				alert(message.message);
			}, 1);
		}
	});
});

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

