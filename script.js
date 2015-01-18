var $j = jQuery;

$j(function() {
	$j('.statsfc_playerrater .statsfc_submit input:submit').click(function(e) {
		e.preventDefault();

		var $parent	= $j(this).parents('.statsfc_playerrater');
		var ratings	= {};
		var error	= false;

		$parent.find('select[data-player-id]').each(function() {
			var id		= $j(this).attr('data-player-id');
			var rating	= $j(this).val();

			if (rating !== 'N/A' && (isNaN(rating) || rating < 1 || rating > 10)) {
				error = true;
				return;
			}

			ratings[id] = rating;
		});

		if (error) {
			alert('Please choose a rating for each player');
			return;
		}

		// Check that cookie doesn't exist for the current match.
		var api_key		= $parent.attr('data-api-key');
		var match_id	= $parent.attr('data-match-id');
		var team_id		= $parent.attr('data-team-id');
		var cookie_id	= 'statsfc_playerrater_' + api_key + '_' + match_id + '_' + team_id;
		var cookie		= sfc_getCookie(cookie_id);

		if (cookie !== null) {
			alert('You can only submit one lot of ratings per match');
			return;
		}

		// Submit the ratings to StatsFC.
		$j.getJSON(
			'https://api.statsfc.com/crowdscores/player-rater.php?callback=?',
			{
				key:		api_key,
				domain:		window.location.hostname,
				match_id:	match_id,
				team_id:	team_id,
				rating:		ratings
			},
			function(data) {
				if (data.error) {
					alert(data.error);
					return;
				}

				// Save cookie.
				sfc_setCookie(cookie_id, JSON.stringify(ratings));

				// Update average ratings.
				$j.each(data.players, function(key, player) {
					var name = player.name;

					if (player.on) {
						name += ' <small>(<span class="statsfc_subOn">↑' + player.on + '\'</span>';

						if (player.off) {
							name += ', <span class="statsfc_subOff">↓' + player.off + '\'</span>';
						}

						name += ')</small>';
					} else if (player.off) {
						name += ' <small>(<span class="statsfc_subOff">↓' + player.off + '\'</span>)</small>';
					}

					if (player.motm) {
						var $player = $j('<strong>').addClass('statsfc_motm').html(name);
					} else {
						var $player = name;
					}

					$parent.find('tr[data-player-id="' + player.id + '"] .statsfc_player').empty().append(
						$player
					);

					$parent.find('tr[data-player-id="' + player.id + '"] .statsfc_rating').empty().append(
						$j('<span>').text(ratings[player.id])
					);

					$parent.find('tr[data-player-id="' + player.id + '"] .statsfc_average strong').empty().text(player.rating);
				});

				$parent.find('.statsfc_submit').remove();
			}
		);
	});
});

function sfc_setCookie(name, value) {
	var date = new Date();
	date.setTime(date.getTime() + (28 * 24 * 60 * 60 * 1000));
	var expires = '; expires=' + date.toGMTString();
	document.cookie = escape(name) + '=' + escape(value) + expires + '; path=/';
}

function sfc_getCookie(name) {
	var nameEQ	= escape(name) + "=";
	var ca		= document.cookie.split(';');

	for (var i = 0; i < ca.length; i++) {
		var c = ca[i];
		while (c.charAt(0) == ' ') {
			c = c.substring(1, c.length);
		}

		if (c.indexOf(nameEQ) == 0) {
			return unescape(c.substring(nameEQ.length, c.length));
		}
	}

	return null;
}