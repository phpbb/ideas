$('.rating').each(function() {
	var $this = $(this),
		url = $this.data('vote'),
		width = parseFloat($this.text()) * 25;

	$this.html('<ul class="star-rating' + (url ? ' active' : '') + '"><li class="current-rating" style="width: ' + width + 'px"></li><li><a class="one-star">1</a></li><li><a class="two-stars">2</a></li><li><a class="three-stars">3</a></li><li><a class="four-stars">4</a></li><li><a class="five-stars">5</a></li></ul>');
	$this.find('a').click(function(e) {
		if (url)
		{
			var vote = $(this).text();
			$.get(url + '&v=' + vote, function(message) {
				alert(message); // phpBB 3.1 please! :O
			});
		}
		e.preventDefault();
	});
});