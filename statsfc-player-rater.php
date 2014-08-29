<?php
/*
Plugin Name: StatsFC Player Rater
Plugin URI: https://statsfc.com/docs/wordpress
Description: StatsFC Player Rater
Version: 1.2.2
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
	public $isShortcode = false;

	private static $defaults = array(
		'title'			=> '',
		'key'			=> '',
		'team'			=> '',
		'date'			=> '',
		'default_css'	=> true
	);

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
		$instance		= wp_parse_args((array) $instance, self::$defaults);
		$title			= strip_tags($instance['title']);
		$key			= strip_tags($instance['key']);
		$team			= strip_tags($instance['team']);
		$date			= strip_tags($instance['date']);
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
				<?php _e('Key', STATSFC_PLAYERRATER_ID); ?>:
				<input class="widefat" name="<?php echo $this->get_field_name('key'); ?>" type="text" value="<?php echo esc_attr($key); ?>">
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
				<?php _e('Date (YYYY-MM-DD)', STATSFC_PLAYERRATER_ID); ?>:
				<input class="widefat" name="<?php echo $this->get_field_name('date'); ?>" type="text" value="<?php echo esc_attr($date); ?>" placeholder="YYYY-MM-DD">
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
		$instance['key']			= strip_tags($new_instance['key']);
		$instance['team']			= strip_tags($new_instance['team']);
		$instance['date']			= strip_tags($new_instance['date']);
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
		$key			= $instance['key'];
		$team			= $instance['team'];
		$date			= $instance['date'];
		$default_css	= filter_var($instance['default_css'], FILTER_VALIDATE_BOOLEAN);

		$html  = $before_widget;
		$html .= $before_title . $title . $after_title;

		try {
			if (strlen($team) == 0) {
				throw new Exception('Please choose a team from the widget options');
			}

			$data = $this->_fetchData('https://api.statsfc.com/crowdscores/player-rater.php?key=' . urlencode($key) . '&team=' . urlencode($team) . '&date=' . urlencode($date));

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

			$key		= esc_attr($key);
			$match_id	= esc_attr($match->id);
			$team_id	= esc_attr($team->id);
			$teamName	= esc_attr($team->name);
			$against	= ($match->home == $team->name ? esc_attr($match->away) . ' (H)' : esc_attr($match->home) . ' (A)');

			$html .= <<< HTML
			<div class="statsfc_playerrater" data-api-key="{$key}" data-match-id="{$match_id}" data-team-id="{$team_id}">
				<table>
					<thead>
						<tr>
							<th colspan="5">{$teamName} vs {$against}</th>
						</tr>
					</thead>
					<tbody>
HTML;

			$cookie_id	= 'statsfc_playerrater_' . $key . '_' . $match_id . '_' . $team_id;
			$cookie		= (isset($_COOKIE[$cookie_id]) ? json_decode(stripslashes($_COOKIE[$cookie_id])) : null);

			foreach ($players as $player) {
				$player_id	= esc_attr($player->id);
				$position	= esc_attr($player->position);
				$number		= (! empty($player->number) ? esc_attr($player->number) . '.' : '');
				$name		= ($player->motm ? '<strong class="statsfc_motm">' . esc_attr($player->name) . '</strong>' : esc_attr($player->name));
				$rating		= '';
				$average	= ($player->rating ? esc_attr($player->rating) : '–');
				$submit		= '';

				if (is_null($cookie)) {
					$rating  = '<select data-player-id="' . $player_id . '">' . PHP_EOL;
					$rating .= '<option value="">--</option>' . PHP_EOL;

					for ($i = 1; $i <= 10; $i++) {
						$rating .= '<option value="' . $i . '">' . $i . '</option>' . PHP_EOL;
					}

					$rating .= '</select>' . PHP_EOL;

					$submit = '<p class="statsfc_submit"><input type="submit" value="Submit ratings"></p>' . PHP_EOL;
				} else {
					$rating = '<span>' . esc_attr($cookie->{$player->id}) . '</span>' . PHP_EOL;
				}

				$html .= <<< HTML
				<tr data-player-id="{$player_id}">
					<td class="statsfc_position">
						<small class="statsfc_{$position}">{$position}</small>
					</td>
					<td class="statsfc_numeric">{$number}</td>
					<td class="statsfc_player">{$name}</td>
					<td class="statsfc_numeric statsfc_rating">{$rating}</td>
					<td class="statsfc_numeric statsfc_average">
						<strong>{$average}</strong>
					</td>
				</tr>
HTML;
			}

			$html .= <<< HTML
					</tbody>
				</table>

				{$submit}
HTML;

			if ($customer->advert) {
				$html .= <<< HTML
				<p class="statsfc_footer"><small>Powered by StatsFC.com. Fan data via CrowdScores.com</small></p>
HTML;
			}

			$html .= <<< HTML
			</div>
HTML;
		} catch (Exception $e) {
			$html .= '<p style="text-align: center;">StatsFC.com – ' . esc_attr($e->getMessage()) . '</p>' . PHP_EOL;
		}

		$html .= $after_widget;

		if ($this->isShortcode) {
			return $html;
		} else {
			echo $html;
		}
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

	public static function shortcode($atts) {
		$args = shortcode_atts(self::$defaults, $atts);

		$widget					= new self;
		$widget->isShortcode	= true;

		return $widget->widget(array(), $args);
	}
}

// register StatsFC widget
add_action('widgets_init', create_function('', 'register_widget("' . STATSFC_PLAYERRATER_ID . '");'));
add_shortcode('statsfc-player-rater', STATSFC_PLAYERRATER_ID . '::shortcode');
