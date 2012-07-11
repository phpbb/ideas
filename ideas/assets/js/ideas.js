$('.rating').each(function() {
	"use strict";

	var $this = $(this),
		url = $this.data('vote'),
		width = $this.text() * 25;

	$this.html('<ul class="star-rating' + (url ? ' active' : '') + '"><li class="current-rating" style="width: ' + width + 'px"></li><li><a class="one-star">1</a></li><li><a class="two-stars">2</a></li><li><a class="three-stars">3</a></li><li><a class="four-stars">4</a></li><li><a class="five-stars">5</a></li></ul>');
	$this.find('a').click(function (e) {
		e.preventDefault();

		if (url) {
			var vote = $(this).text();
			$.get(url, {v: vote}, function (message) {
				if (typeof message === 'string') {
					alert(message); // Error
				} else {
					$this.find('.current-rating').css('width', message.rating * 25);
					alert(message.message);
				}
			});
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
		}

	if (data.status === '-') {
		return;
	}

	$.get($this.attr('data-url'), data, function (res) {
		var link = $this.prev('a'),
			newhref = link.attr('href')
				.replace(/status=\d/, 'status=' + data.status);

		link.attr('href', newhref)
			.text($this.find(':selected').text());
	});
});

$('#rfcedit').click(function (e) {
	"use strict";

	e.preventDefault();

	$(this).hide();
	$('#rfclink').hide();
	$('#rfceditinput').show().focus();
});

$('#rfceditinput').keydown(function (e) {
	"use strict";

	var $this = $(this),
		url = $('#rfcedit').attr('href'),
		value = $this.val();

	if (e.keyCode === 13) {
		if (value && !/^https?:\/\/area51\.phpbb\.com\/phpBB\/viewtopic\.php/.exec(value)) {
			alert('Error: RFC must be a topic on Area51.');
			return;
		}

		$.get(url, {rfc: value}, function (res) {
			$('#rfclink')
				.attr('href', value)
				.text(value)
				.show();

			$this.hide();

			$('#rfcedit').text(value ? 'Edit' : 'Add').show();
		});

		e.preventDefault();
	}
});

$('#ticketedit').click(function (e) {
	"use strict";

	e.preventDefault();

	$(this).hide();
	$('#ticketlink').hide();
	$('#ticketeditinput').show().focus();
});

$('#ticketeditinput').keydown(function (e) {
	"use strict";

	var $this = $(this),
		url = $('#ticketedit').attr('href'),
		value = $this.val(),
		info;

	if (e.keyCode === 13) {
		if (value && !(info = /PHPBB3\-(\d{1,6})$/.exec(value))) {
			alert('Error: Ticket ID must be of the format "PHPBB3-#####".');
			return;
		}

		if (value) {
			value = 'PHPBB3-' + info[1];
		}

		$.get(url, {ticket: value && info[1]}, function (res) {
			$('#ticketlink')
				.attr('href', 'http://tracker.phpbb.com/browse/' + value)
				.text(value)
				.show();

			$this.hide();

			$('#ticketedit').text(value ? 'Edit' : 'Add').show();
		});

		e.preventDefault();
	}
});

