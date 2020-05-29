<?php

namespace WeatherInfo;

use WeatherInfo\Config;

// The widget class
class Widget extends \WP_Widget
{
    private $_instance = array();

    // Main constructor
    public function __construct()
    {
        parent::__construct(
            'weather_info_widget',
            __('Weather Info', 'text_domain'),
            array(
                'customize_selective_refresh' => true,
            )
        );
    }

    // The widget form (for the backend )
    public function form($instance)
    {
        // Set widget defaults
        $defaults = array(
            'widget_title' => 'Weather Info W',
            'show_forcast' => 1,
            'forcast_days' => 6,
            'icon_color' => 'cadetblue',
            'icon_border' => 1,
            'icon_align' => 'left',
        );

        // Parse current settings with defaults
        extract(wp_parse_args(( array )$instance, $defaults));

        $fields = '<p>';
        $fields .= $this->field(array('label', 'title', '', 'Widget Title'));
        $fields .= $this->field(array('text', 'title', $title));
        echo $fields .= '</p>';

        $fields = '<hr><p>';
        $fields .= $this->field(array('label', 'show_forcast', '', 'Show Forcast')) . " ";
        $fields .= $this->field(array('checkbox', 'show_forcast', $show_forcast));
        echo $fields .= '</p>';

        $forcast_days_options = array();
        for ($i = 1; $i < 7; $i++) {
            $forcast_days_options[$i] = $i;
        }
        $fields = '<p>';
        $fields .= $this->field(array('label', 'forcast_days', '', 'Forcast Days')) . " ";
        $fields .= $this->field(array('select', 'forcast_days', $forcast_days), $forcast_days_options);
        echo $fields .= '</p>';

        $fields = '<p>';
        $fields .= $this->field(array('label', 'icon_color', '', 'Icon Color'));
        $fields .= $this->field(array('text', 'icon_color', $icon_color));
        echo $fields .= '</p>';

        $fields = '<p>';
        $fields .= $this->field(array('label', 'icon_border', '', 'Icon Border')) . " ";
        $fields .= $this->field(array('checkbox', 'icon_border', $icon_border));
        echo $fields .= '</p>';


        $fields = '<p>';
        $fields .= $this->field(array('label', 'icon_align', '', 'Icon Align'));
        $fields .= $this->field(array('select', 'icon_align', $icon_align), array('left' => 'Left (default)', 'right' => 'Right'));
        echo $fields .= '</p>';
    }

    private function field($args = array(), $select_options = array())
    {
        //array(type, name, value, label)
        $args = Config::sanitize_data($args);
        $id = $this->get_field_id($args[1]);
        $value = $args[2];
        $name = ($this->get_field_name($args[1]));
        $label = isset($args[3]) ? $args[3] : $args[1];
        $field = '';
        $common_attr = "name='$name' id='$id' class='widefat'";
        switch ($args[0]) {
            case "label":
                $field = "<label for='$id'>$label</label>";
                break;
            case "text":
                $field = "<input type='text' value='$value' $common_attr/>";
                break;
            case "textarea":
                $field = "<textarea $common_attr>$value</textarea>";
                break;
            case "checkbox":
                $checked = isset($value) && $value == 1 ? 'checked' : '';
                $field = "<input type='checkbox' $common_attr value='1' $checked/>";
                break;
            case "select":
                $select_options = Config::sanitize_data($select_options);
                $field = "<select $common_attr>";

                // Loop through options and add each one to the select dropdown
                foreach ($select_options as $key => $name) {
                    $field .= '<option value="' . esc_attr($key) . '" id="' . esc_attr($key) . '" ' . selected($value, $key, false) . '>' . $name . '</option>';
                }
                $field .= "</select>";
            default:

        }
        return $field;
    }

    // Update widget settings
    public function update($new_instance, $old_instance)
    {
        $instance = $old_instance;
        $instance['title'] = isset($new_instance['title']) ? wp_strip_all_tags($new_instance['title']) : '';
        $instance['show_forcast'] = isset($new_instance['show_forcast']) ? wp_strip_all_tags($new_instance['show_forcast']) : '';
        $instance['forcast_days'] = isset($new_instance['forcast_days']) ? wp_kses_post($new_instance['forcast_days']) : '';
        $instance['icon_color'] = isset($new_instance['icon_color']) ? wp_kses_post($new_instance['icon_color']) : '';
        $instance['icon_border'] = isset($new_instance['icon_border']) ? wp_kses_post($new_instance['icon_border']) : '';
        $instance['icon_align'] = isset($new_instance['icon_align']) ? wp_strip_all_tags($new_instance['icon_align']) : '';
        return $instance;
    }

    // Display the widget
    public function widget($args, $instance)
    {
        wp_enqueue_style("openweathermap-widget-right", GWW_STYLES . "openweathermap-widget-right.min.css");
        wp_enqueue_style("openweathermap-widget-left", GWW_STYLES . "openweathermap-widget-left.min.css");
        wp_enqueue_style("owfont-regular", GWW_STYLES . "owfont-regular.min.css");

        extract($args);
        // Check the widget options
        $title = isset($instance['title']) ? apply_filters('widget_title', $instance['title']) : '';


        // WordPress core before_widget hook (always include )
        $widget = $before_widget;

        // Display the widget
        $widget .= '<div class="widget-text wp_widget_plugin_box">';

        // Display widget title if defined
        if ($title) {
            $widget .= $before_title . $title . $after_title;
        }

        $widget .= $this->weather_forcast($instance);
        $widget .= '</div>';

        // WordPress core after_widget hook (always include )
        $widget .= $after_widget;
        echo $widget;
    }

    private function weather_forcast($instance)
    {
        $instance = Config::sanitize_data($instance);
        $this->_instance = $instance;
        $show_forcast = isset($instance['show_forcast']) ? $instance['show_forcast'] : '';
        $forcast_days = isset($instance['forcast_days']) ? $instance['forcast_days'] : '';
        $icon_color = isset($instance['icon_color']) ? $instance['icon_color'] : '';

        $loc = Config::visitor_location();
        $region = Config::sanitize_data($loc['region_name'] . ", " . $loc['country_code']);
        $weather = new Front();
        $weather = $weather->index();
        $weather_current = $weather['current'];
        $weather_forcast = $weather['daily'];
        date_default_timezone_set($weather['timezone']);
        ob_start();
        ?>

        <div class="container-custom-card">
            <div id="openweathermap-widget-11" class="container-widget container-widget--11">
                <div id="container-openweathermap-widget-11">
                    <div class="widget-left">

                        <div style="float: left">
                            <div class="container-custom-card">
                                <div id="openweathermap-widget-15" class="container-widget container-widget--15">
                                    <div id="container-openweathermap-widget-15">
                                        <div class="widget-right weather-right--type1 widget-right--brown">
                                            <div class="widget-right__header widget-right__header--brown">
                                                <div class="widget-right__layout">
                                                    <div>
                                                        <h2 class="widget-right__title"><?php echo $region; ?></h2>
                                                        <p class="widget-right__description"><?php echo $weather_current['weather'][0]['description']; ?></p>
                                                    </div>
                                                </div>

                                                <div width="128" height="128"
                                                     class="weather-icon_current weather-right__icon weather-right__icon--type1">
                                                    <i class="owf owf-<?php echo $weather_current['weather'][0]['id']; ?> owf-4x"></i>
                                                </div>
                                            </div>
                                            <div class="weather-right weather-right--brown">
                                                <div class="weather-right__layout">
                                                    <div class="weather-right__temperature"><?php echo $weather_current['temp']; ?>
                                                        <span>°C</span></div>
                                                    <div class="weather-right__weather">
                                                        <div class="weather-right-card">
                                                            <table class="weather-right__table">
                                                                <tbody>
                                                                <tr class="weather-right__items">
                                                                    <th colspan="2" class="weather-right__item">
                                                                        Details
                                                                    </th>
                                                                </tr>
                                                                <tr class="weather-right__items">
                                                                    <td class="weather-right__item">Feels like</td>
                                                                    <td class="weather-right__item weather-right__feels"><?php echo $weather_current['feels_like']; ?>
                                                                        <span>°C</span></td>
                                                                </tr>
                                                                <tr class="weather-right__items">
                                                                    <td class="weather-right__item">Wind</td>
                                                                    <td class="weather-right__item weather-right__wind-speed"><?php echo $weather_current['wind_speed']; ?>
                                                                        m/s
                                                                    </td>
                                                                </tr>
                                                                <tr class="weather-right-card__items">
                                                                    <td class="weather-right__item">Humidity</td>
                                                                    <td class="weather-right__item weather-right__humidity"><?php echo $weather_current['humidity']; ?>
                                                                        %
                                                                    </td>
                                                                </tr>

                                                                <tr class="weather-right-card__items">
                                                                    <td class="weather-right__item">Pressure</td>
                                                                    <td class="weather-right__item weather-right__pressure"><?php echo $weather_current['pressure']; ?>
                                                                        hPa
                                                                    </td>
                                                                </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="widget-right__footer widget-right__footer--brown">
                                                <div class="widget-right__layout"><a href="#">
                                                        <div class="widget-right__logo_black_small"></div>
                                                    </a>
                                                    <div class="widget-right__date sunrise_sunset_left">
                                                        Sunrise: <?php echo date('h:i a', $weather_current['sunrise']); ?></div>
                                                    <div class="widget-right__date">
                                                        Sunset: <?php echo date('h:i a', $weather_current['sunset']); ?></div>
                                                    <div class="widget-right__date">
                                                        *<?php echo date('H:i F d'); ?></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <br><br>
                            <?php echo $this->forcast_content($weather_forcast[0]); ?>

                        </div>

                        <?php if ($show_forcast): ?>
                            <div class="forcast-bar" style="float: right">
                                <?php
                                if ($weather_forcast) {
                                    foreach ($weather_forcast as $key => $item) {
                                        if ($key === 0 || $key > $forcast_days) continue;
                                        echo $this->forcast_content($item, $key);
                                    }
                                }
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <style>
            .owf-3x, .owf-4x {
                color: <?php echo $icon_color; ?>;
            }
        </style>
        <?php
        return ob_get_clean();
    }

    private function forcast_content($item, $key = 0)
    {
        $instance = $this->_instance;
        $icon_border = ($instance['icon_border']) ? 'border' : ' ';
        $icon_align = isset($instance['icon_align']) ? $instance['icon_align'] : '';
        $class = 'forcast-content';
        $date = date('D, d F', $item['dt']);
        $day = explode(', ', $date);
        $br = "<br>";
        if ($key === 0) {
            $day[0] = "Today";
            $class = $class . $key;
            $br = "";
        }
        $weather = $item['weather'][0];
        $icon = $weather['id'];
        $temp = $item['temp'];
        $weather_desc = $weather['main'] . ' - ' . $weather['description'];
        $weather_min_max = "Min {$temp['min']}°C | Max {$temp['max']}°C";
        return "<div class='$class'>
                            <i class='owf owf-$icon owf-3x owf-pull-$icon_align owf-$icon_border'></i>
                            <strong>$day[0] | $day[1]</strong><br>
                           <i>$weather_desc</i><br>
                            $weather_min_max
                        </div>$br";
    }
}