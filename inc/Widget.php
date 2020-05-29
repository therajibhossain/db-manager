<?php

namespace DBManager;

// The widget class
class Widget extends \WP_Widget
{
    private $_instance = array();

    // Main constructor
    public function __construct()
    {
        parent::__construct(
            'dbmanager_widget',
            __('DB Manager', 'text_domain'),
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
            'widget_title' => 'DB Manager W',
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

        return $instance;
    }

    // Display the widget
    public function widget($args, $instance)
    {
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


        $widget .= '</div>';

        // WordPress core after_widget hook (always include )
        $widget .= $after_widget;
        echo $widget;
    }
}