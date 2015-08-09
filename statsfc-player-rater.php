<?php
/*
Plugin Name: StatsFC Player Rater
Plugin URI: https://statsfc.com/widgets/player-rater
Description: StatsFC Player Rater
Version: 1.5.2
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

define('STATSFC_PLAYERRATER_ID',      'StatsFC_PlayerRater');
define('STATSFC_PLAYERRATER_NAME',    'StatsFC Player Rater');
define('STATSFC_PLAYERRATER_VERSION', '1.5.2');

/**
 * Adds StatsFC widget.
 */
class StatsFC_PlayerRater extends WP_Widget
{
    public $isShortcode = false;

    protected static $count = 0;

    private static $defaults = array(
        'title'       => '',
        'key'         => '',
        'team'        => '',
        'competition' => '',
        'date'        => '',
        'default_css' => true
    );

    private static $whitelist = array(
        'team',
        'competition',
        'date',
        'season'
    );

    /**
     * Register widget with WordPress.
     */
    public function __construct()
    {
        parent::__construct(STATSFC_PLAYERRATER_ID, STATSFC_PLAYERRATER_NAME, array('description' => 'StatsFC Player Rater'));
    }

    /**
     * Back-end widget form.
     *
     * @see WP_Widget::form()
     *
     * @param array $instance Previously saved values from database.
     */
    public function form($instance)
    {
        $instance    = wp_parse_args((array) $instance, self::$defaults);
        $title       = strip_tags($instance['title']);
        $key         = strip_tags($instance['key']);
        $team        = strip_tags($instance['team']);
        $competition = strip_tags($instance['competition']);
        $date        = strip_tags($instance['date']);
        $default_css = strip_tags($instance['default_css']);
        ?>
        <p>
            <label>
                <?php _e('Title', STATSFC_PLAYERRATER_ID); ?>
                <input class="widefat" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>">
            </label>
        </p>
        <p>
            <label>
                <?php _e('Key', STATSFC_PLAYERRATER_ID); ?>
                <input class="widefat" name="<?php echo $this->get_field_name('key'); ?>" type="text" value="<?php echo esc_attr($key); ?>">
            </label>
        </p>
        <p>
            <label>
                <?php _e('Team', STATSFC_PLAYERRATER_ID); ?>
                <input class="widefat" name="<?php echo $this->get_field_name('team'); ?>" type="text" value="<?php echo esc_attr($team); ?>">
            </label>
        </p>
        <p>
            <label>
                <?php _e('Competition', STATSFC_PLAYERRATER_ID); ?>
                <input class="widefat" name="<?php echo $this->get_field_name('competition'); ?>" type="text" value="<?php echo esc_attr($competition); ?>">
            </label>
        </p>
        <p>
            <label>
                <?php _e('Date (YYYY-MM-DD)', STATSFC_PLAYERRATER_ID); ?>
                <input class="widefat" name="<?php echo $this->get_field_name('date'); ?>" type="text" value="<?php echo esc_attr($date); ?>" placeholder="YYYY-MM-DD">
            </label>
        </p>
        <p>
            <label>
                <?php _e('Use default styles?', STATSFC_PLAYERRATER_ID); ?>
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
    public function update($new_instance, $old_instance)
    {
        $instance                = $old_instance;
        $instance['title']       = strip_tags($new_instance['title']);
        $instance['key']         = strip_tags($new_instance['key']);
        $instance['team']        = strip_tags($new_instance['team']);
        $instance['competition'] = strip_tags($new_instance['competition']);
        $instance['date']        = strip_tags($new_instance['date']);
        $instance['default_css'] = strip_tags($new_instance['default_css']);

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
    public function widget($args, $instance)
    {
        extract($args);

        $title       = apply_filters('widget_title', $instance['title']);
        $unique_id   = ++static::$count;
        $key         = $instance['key'];
        $referer     = (array_key_exists('HTTP_HOST', $_SERVER) ? $_SERVER['HTTP_HOST'] : '');
        $default_css = filter_var($instance['default_css'], FILTER_VALIDATE_BOOLEAN);

        $options = array(
            'team'        => $instance['team'],
            'competition' => $instance['competition'],
            'date'        => $instance['date']
        );

        $html  = $before_widget;
        $html .= $before_title . $title . $after_title;
        $html .= '<div id="statsfc-player-rater-' . $unique_id . '"></div>' . PHP_EOL;
        $html .= $after_widget;

        // Enqueue CSS
        if ($default_css) {
            wp_register_style(STATSFC_PLAYERRATER_ID . '-css', plugins_url('all.css', __FILE__), null, STATSFC_PLAYERRATER_VERSION);
            wp_enqueue_style(STATSFC_PLAYERRATER_ID . '-css');
        }

        // Enqueue base JS
        wp_register_script(STATSFC_PLAYERRATER_ID . '-js', plugins_url('player-rater.js', __FILE__), array('jquery'), STATSFC_PLAYERRATER_VERSION, true);
        wp_enqueue_script(STATSFC_PLAYERRATER_ID . '-js');

        // Enqueue widget JS
        $object = 'statsfc_player_rater_' . $unique_id;

        $script  = '<script>' . PHP_EOL;
        $script .= 'var ' . $object . ' = new StatsFC_PlayerRater(' . json_encode($key) . ');' . PHP_EOL;
        $script .= $object . '.referer = ' . json_encode($referer) . ';' . PHP_EOL;

        foreach (static::$whitelist as $parameter) {
            if (! array_key_exists($parameter, $options)) {
                continue;
            }

            $script .= $object . '.' . $parameter . ' = ' . json_encode($options[$parameter]) . ';' . PHP_EOL;
        }

        $script .= $object . '.display("statsfc-player-rater-' . $unique_id . '");' . PHP_EOL;
        $script .= '</script>';

        add_action('wp_print_footer_scripts', function() use ($script)
        {
            echo $script;
        });

        if ($this->isShortcode) {
            return $html;
        } else {
            echo $html;
        }
    }

    public static function shortcode($atts)
    {
        $args = shortcode_atts(self::$defaults, $atts);

        $widget              = new self;
        $widget->isShortcode = true;

        return $widget->widget(array(), $args);
    }
}

// Register StatsFC widget
add_action('widgets_init', function()
{
    register_widget(STATSFC_PLAYERRATER_ID);
});

add_shortcode('statsfc-player-rater', STATSFC_PLAYERRATER_ID . '::shortcode');
