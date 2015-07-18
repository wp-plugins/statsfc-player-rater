var $j = jQuery;

function StatsFC_PlayerRater(key) {
	this.domain      = 'https://api.statsfc.com';
	this.referer     = '';
	this.key         = key;
	this.team        = '';
	this.competition = '';
	this.date        = '';

	this.display = function(placeholder) {
		if (placeholder.length == 0) {
			return;
		}

		var $placeholder = $j('#' + placeholder);

		if ($placeholder.length == 0) {
			return;
		}

		if (this.referer.length == 0) {
			this.referer = window.location.hostname;
		}

		var $container = $j('<div>').addClass('sfc_playerrater').attr('data-api-key', this.key);

		// Store globals variables here so we can use it later.
		var domain = this.domain;
		var key    = this.key;
		var object = this;

		$j.getJSON(
			domain + '/crowdscores/player-rater.php?callback=?',
			{
				key:         this.key,
				domain:      this.referer,
				team:        this.team,
				competition: this.competition,
				date:        this.date
			},
			function(data) {
				if (data.error) {
					$container.append(
						$j('<p>').css('text-align', 'center').append(
							$j('<a>').attr({ href: 'https://statsfc.com', title: 'Football widgets', target: '_blank' }).text('StatsFC.com'),
							' – ',
							data.error
						)
					);

					return;
				}

				$container.attr('data-match-id', data.match.id);
				$container.attr('data-team-id', data.team.id);

				var cookie_id	= 'sfc_playerrater_' + key + '_' + data.match.id + '_' + data.team.id;
				var cookie		= sfc_getCookie(cookie_id);

				if (cookie !== null) {
					cookie = JSON.parse(cookie);
				}

				var $table = $j('<table>');
				var $thead = $j('<thead>');
				var $tbody = $j('<tbody>');

				$thead.append(
					$j('<tr>').append(
						$j('<th>').attr('colspan', 4).text(data.team.name + ' vs ' + (data.match.home == data.team.name ? data.match.away + ' (H)' : data.match.home + ' (A)')),
						$j('<th>').addClass('sfc_numeric').append(
							$j('<small>').text('Avg')
						)
					)
				);

				$j.each(data.players, function(key, player) {
					var $player  = $j('<td>').addClass('sfc_player');
					var name     = player.name;
					var position = player.position;

					if (player.on) {
						name += ' <small>(<span class="sfc_subOn">↑' + player.on + '\'</span>';

						if (player.off) {
							name += ', <span class="sfc_subOff">↓' + player.off + '\'</span>';
						}

						name += ')</small>';

						position = 'SB';
					} else if (player.off) {
						name += ' <small>(<span class="sfc_subOff">↓' + player.off + '\'</span>)</small>';
					}

					if (player.motm) {
						$player.append(
							$j('<strong>').addClass('sfc_motm').html(name)
						);
					} else {
						$player.html(name);
					}

					if (cookie !== null) {
						var $rating = $j('<span>').text(cookie[player.id]);
					} else {
						var $rating = $j('<select>').attr({ 'data-player-id': player.id });

						$rating.append(
							$j('<option>').val('').text('--'),
							$j('<option>').text('N/A')
						);

						for (var i = 1; i <= 10; i++) {
							$rating.append(
								$j('<option>').val(i).text(i)
							);
						}
					}

					var $row = $j('<tr>').attr('data-player-id', player.id).append(
						$j('<td>').addClass('sfc_position').append(
							$j('<small>').addClass('sfc_' + position).text(position)
						),
						$j('<td>').addClass('sfc_numeric').text(player.number + '.'),
						$player,
						$j('<td>').addClass('sfc_numeric sfc_rating').append($rating),
						$j('<td>').addClass('sfc_numeric sfc_average').append(
							$j('<strong>').text(player.rating ? player.rating : '–')
						)
					);

					$tbody.append($row);
				});

				$table.append($thead, $tbody);

				var $submit = null;

				if (cookie == null) {
					$submit = $j('<p>').addClass('sfc_submit').append(
						$j('<input>').attr('type', 'submit').val('Submit ratings').on('click', function(e) {
							e.preventDefault();
							object.rate($j(this));
						})
					);
				}

				$container.append($table, $submit);

				if (data.customer.attribution) {
					$container.append(
						$j('<div>').attr('class', 'sfc_footer').append(
							$j('<p>').append(
								$j('<small>').append('Powered by ').append(
									$j('<a>').attr({ href: 'https://statsfc.com', title: 'StatsFC – Football widgets', target: '_blank' }).text('StatsFC.com')
								).append('. Fan data via ').append(
									$j('<a>').attr({ href: 'https://crowdscores.com', title: 'CrowdScores', target: '_blank' }).text('CrowdScores.com')
								)
							)
						)
					);
				}
			}
		);

		$j('#' + placeholder).append($container);
	};

	this.rate = function(e) {
		var $parent	= e.parents('.sfc_playerrater');
		var ratings	= {};
		var error = false;

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
		var cookie_id	= 'sfc_playerrater_' + this.key + '_' + $parent.attr('data-match-id') + '_' + $parent.attr('data-team-id');
		var cookie		= sfc_getCookie(cookie_id);

		if (cookie !== null) {
			alert('You can only submit one lot of ratings per match');
			return;
		}

		// Submit the ratings to StatsFC.
		$j.getJSON(
			this.domain + '/crowdscores/player-rater.php?callback=?',
			{
				key:		this.key,
				domain:		window.location.hostname,
				match_id:	$parent.attr('data-match-id'),
				team_id:	$parent.attr('data-team-id'),
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
						name += ' <small>(<span class="sfc_subOn">↑' + player.on + '\'</span>';

						if (player.off) {
							name += ', <span class="sfc_subOff">↓' + player.off + '\'</span>';
						}

						name += ')</small>';
					} else if (player.off) {
						name += ' <small>(<span class="sfc_subOff">↓' + player.off + '\'</span>)</small>';
					}

					if (player.motm) {
						var $player = $j('<strong>').addClass('sfc_motm').html(name);
					} else {
						var $player = name;
					}

					$parent.find('tr[data-player-id="' + player.id + '"] .sfc_player').empty().append(
						$player
					);

					$parent.find('tr[data-player-id="' + player.id + '"] .sfc_rating').empty().append(
						$j('<span>').text(ratings[player.id])
					);

					$parent.find('tr[data-player-id="' + player.id + '"] .sfc_average strong').empty().text(player.rating);
				});

				$parent.find('.sfc_submit').remove();
			}
		);
	};
}

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
