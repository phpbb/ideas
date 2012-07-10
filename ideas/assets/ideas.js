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
				$this.find('.current-rating').css('width', message.rating * 25);
				alert(message.message);
			});
		}
	});
});

$('.votes').click(function (e) {
	e.preventDefault();

	$('.voteslist').slideToggle();
});

$('.confirm').click(function () {
	"use strict";

	return confirm('Really delete idea?'); // EVERYTHING IS BLEEDING
});