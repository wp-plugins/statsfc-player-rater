<?php
/*
Plugin Name: StatsFC Player Rater
Plugin URI: https://statsfc.com/docs/wordpress
Description: StatsFC Player Rater
Version: 1.0.2
Author: Will Woodward
Author URI: http://willjw.co.uk
License: GPL2
*/

/*  Copyright 2013  Will Woodward  (email : will@willjw.co.uk)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define('STATSFC_PLAYERRATER_ID',	'StatsFC_PlayerRater');
define('STATSFC_PLAYERRATER_NAME',	'StatsFC Player Rater');

/**
 * Adds StatsFC widget.
 */
class StatsFC_PlayerRater extends WP_Widget {
	/**
	 * Register widget with WordPress.
	 */
	public function __construct() {
		parent::__construct(STATSFC_PLAYERRATER_ID, STATSFC_PLAYERRATER_NAME, array('description' => 'StatsFC Player Rater'));
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form($instance) {
		$defaults = array(
			'title'			=> __('Player Rater', STATSFC_PLAYERRATER_ID),
			'api_key'		=> __('', STATSFC_PLAYERRATER_ID),
			'team'			=> __('', STATSFC_PLAYERRATER_ID),
			'default_css'	=> __('', STATSFC_PLAYERRATER_ID)
		);

		$instance		= wp_parse_args((array) $instance, $defaults);
		$title			= strip_tags($instance['title']);
		$api_key		= strip_tags($instance['api_key']);
		$team			= strip_tags($instance['team']);
		$default_css	= strip_tags($instance['default_css']);
		?>
		<p>
			<label>
				<?php _e('Title', STATSFC_PLAYERRATER_ID); ?>:
				<input class="widefat" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>">
			</label>
		</p>
		<p>
			<label>
				<?php _e('API key', STATSFC_PLAYERRATER_ID); ?>:
				<input class="widefat" name="<?php echo $this->get_field_name('api_key'); ?>" type="text" value="<?php echo esc_attr($api_key); ?>">
			</label>
		</p>
		<p>
			<label>
				<?php _e('Team', STATSFC_PLAYERRATER_ID); ?>:
				<input class="widefat" name="<?php echo $this->get_field_name('team'); ?>" type="text" value="<?php echo esc_attr($team); ?>">
			</label>
		</p>
		<p>
			<label>
				<?php _e('Use default CSS?', STATSFC_PLAYERRATER_ID); ?>
				<input type="checkbox" name="<?php echo $this->get_field_name('default_css'); ?>"<?php echo ($default_css == 'on' ? ' checked' : ''); ?>>
			</label>
		</p>
	<?php
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update($new_instance, $old_instance) {
		$instance					= $old_instance;
		$instance['title']			= strip_tags($new_instance['title']);
		$instance['api_key']		= strip_tags($new_instance['api_key']);
		$instance['team']			= strip_tags($new_instance['team']);
		$instance['default_css']	= strip_tags($new_instance['default_css']);

		return $instance;
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget($args, $instance) {
		extract($args);

		$title			= apply_filters('widget_title', $instance['title']);
		$api_key		= $instance['api_key'];
		$team			= $instance['team'];
		$default_css	= $instance['default_css'];

		echo $before_widget;
		echo $before_title . $title . $after_title;

		try {
			if (strlen($team) == 0) {
				throw new Exception('Please choose a team from the widget options');
			}

			$data = $this->_fetchData('https://api.statsfc.com/crowdscores/player-rater.php?key=' . urlencode($api_key) . '&team=' . urlencode($team));

			if (empty($data)) {
				throw new Exception('There was an error connecting to the StatsFC API');
			}

			$json = json_decode($data);

			if (isset($json->error)) {
				throw new Exception($json->error);
			}

			$match		= $json->match;
			$team		= $json->team;
			$players	= $json->players;
			$customer	= $json->customer;

			if ($default_css) {
				wp_register_style(STATSFC_PLAYERRATER_ID . '-css', plugins_url('all.css', __FILE__));
				wp_enqueue_style(STATSFC_PLAYERRATER_ID . '-css');
			}

			wp_register_script(STATSFC_PLAYERRATER_ID . '-js', plugins_url('script.js', __FILE__), array('jquery'));
			wp_enqueue_script(STATSFC_PLAYERRATER_ID . '-js');
			?>
			<div class="statsfc_playerrater" data-api-key="<?php echo esc_attr($api_key); ?>" data-match-id="<?php echo esc_attr($match->id); ?>" data-team-id="<?php echo esc_attr($team->id); ?>">
				<table>
					<thead>
						<tr>
							<th colspan="5"><?php echo esc_attr($team->name); ?> vs <?php echo ($match->home == $team->name ? esc_attr($match->away) . ' (H)' : esc_attr($match->home) . ' (A)'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						$cookie_id	= 'statsfc_playerrater_' . $api_key . '_' . $match->id . '_' . $team->id;
						$cookie		= (isset($_COOKIE[$cookie_id]) ? json_decode(stripslashes($_COOKIE[$cookie_id])) : null);

						foreach ($players as $player) {
						?>
							<tr data-player-id="<?php echo esc_attr($player->id); ?>">
								<td class="statsfc_position">
									<small class="statsfc_<?php echo esc_attr($player->position); ?>"><?php echo esc_attr($player->position); ?></small>
								</td>
								<td class="statsfc_numeric"><?php echo esc_attr($player->number); ?>.</td>
								<td class="statsfc_player">
									<?php
									if ($player->motm) {
										echo '<strong class="statsfc_motm">' . esc_attr($player->name) . '</strong>';
									} else {
										echo esc_attr($player->name);
									}
									?>
								</td>
								<td class="statsfc_numeric statsfc_rating">
									<?php
									if (! is_null($cookie)) {
									?>
										<span><?php echo esc_attr($cookie->{$player->id}); ?></span>
									<?php
									} else {
									?>
										<select data-player-id="<?php echo esc_attr($player->id); ?>">
											<option value="">--</option>
											<?php
											for ($i = 1; $i <= 10; $i++) {
												echo '<option value="' . $i . '">' . $i . '</option>' . PHP_EOL;
											}
											?>
										</select>
									<?php
									}
									?>
								</td>
								<td class="statsfc_numeric statsfc_average">
									<strong><?php echo ($player->rating ? esc_attr($player->rating) : '–'); ?></strong>
								</td>
							</tr>
						<?php
						}
						?>
					</tbody>
				</table>

				<?php
				if (is_null($cookie)) {
				?>
					<p class="statsfc_submit">
						<input type="submit" value="Submit ratings">
					</p>
				<?php
				}
				?>

				<p class="statsfc_footer"><small>Powered by StatsFC.com. Fan data via CrowdScores.com</small></p>
			</div>
		<?php
		} catch (Exception $e) {
			echo '<p style="text-align: center;">StatsFC.com – ' . esc_attr($e->getMessage()) .'</p>' . PHP_EOL;
		}

		echo $after_widget;
	}

	private function _fetchData($url) {
		if (function_exists('curl_exec')) {
			return $this->_curlRequest($url);
		} else {
			return $this->_fopenRequest($url);
		}
	}

	private function _curlRequest($url) {
		$ch = curl_init();

		curl_setopt_array($ch, array(
			CURLOPT_AUTOREFERER		=> true,
			CURLOPT_HEADER			=> false,
			CURLOPT_RETURNTRANSFER	=> true,
			CURLOPT_TIMEOUT			=> 5,
			CURLOPT_URL				=> $url
		));

		$data = curl_exec($ch);
		if (empty($data)) {
			$data = $this->_fopenRequest($url);
		}

		curl_close($ch);

		return $data;
	}

	private function _fopenRequest($url) {
		return file_get_contents($url);
	}
}

// register StatsFC widget
add_action('widgets_init', create_function('', 'register_widget("' . STATSFC_PLAYERRATER_ID . '");'));
