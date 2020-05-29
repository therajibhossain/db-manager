<?php

namespace DBManager;
trait Config
{
    private static $extensions = array(), $_menu_tabs, $option_name, $option_value = array(), $_db_config = array(),
        $_setting_url, $_location = array();
    public static $name = DBMANAGER_NAME;

    private function isExtensionLoaded($extension_name)
    {
        if (!isset(self::$extensions[$extension_name])) {
            self::$extensions[$extension_name] = extension_loaded($extension_name);
        }
        return self::$extensions[$extension_name];
    }

    public static function option_name()
    {
        if (!self::$option_name) {
            $prefix = 'dbmanager_';
            self::$option_name = array(
                $prefix . 'general_setting', $prefix . 'other_setting',
            );
        }
        return self::$option_name;
    }

    public static function option_tabs()
    {
        $subtitle = " ";
        if (!self::$_menu_tabs) {
            $tab_list = array(
                array(
                    'title' => 'DB Manager Dashboard', 'subtitle' => $subtitle, 'status' => 1, 'fields' => array(
                    array('name' => 'field1', 'title' => ' ', 'type' => 'text'),
                ),
                ),
                array(
                    'title' => '', 'subtitle' => '', 'status' => 0, 'fields' => array()
                ),
            );

            $list = array();
            foreach (self::option_name() as $key => $item) {
                $list[$item] = $tab_list[$key];
            }
            self::$_menu_tabs = $list;
        }
        return self::$_menu_tabs;
    }

    public static function boot_settings($option_name = '*', $status = 'active')
    {
        $options = self::option_name();
        if ($option_name == '*') {

        } elseif ($option_name === $options[0]) {
        } elseif ($option_name === $options[1]) {
        }
    }

    public static function get_comment($start = '/*', $end = '*/', $comment = 'last-modified: ', $date = 1)
    {
        $date = ($date != false) ? date('F d, Y h:i:sA') : '';
        return "$start " . $comment . " " . $date . " $end";
    }

    public static function option_value()
    {
        if (!self::$option_value) {
            foreach (self::option_name() as $item) {
                self::$option_value[$item] = get_option($item);
            }
        }
        return self::$option_value;
    }

    public static function log($message, $type = 'error')
    {
        $type = isset($type) ? $type . " :: " : $type;
        if (is_array($message)) {
            $message = json_encode($message);
        }
        $file = fopen(DBMANAGER_LOGS . DBMANAGER_NAME . '.txt', "a");
        echo fwrite($file, "[" . date('d-M-y h:i:s') . "] $type" . $message . "\n");
        fclose($file);
    }

    public static function setting_url()
    {
        if (!isset(self::$_setting_url)) {
            self::$_setting_url = esc_url(add_query_arg(
                'page',
                DBMANAGER_NAME,
                get_admin_url() . 'admin.php'
            ));
        }
        return self::$_setting_url;
    }

    /*sanitizing input values using sanitize_text_field()*/
    public static function sanitize_data($input)
    {
        if (is_array($input) && $input) {
            $output = array();
            foreach ($input as $key => $value) {
                $output[$key] = sanitize_text_field($value);
            }
        } else {
            $output = sanitize_text_field($input);
        }
        return $output;
    }

    public static function getSize($file)
    {
        $bytes = filesize($file);
        $s = array('b', 'Kb', 'Mb', 'Gb');
        $e = floor(log($bytes) / log(1024));
        return sprintf('%.2f ' . $s[$e], ($bytes / pow(1024, floor($e))));
    }

    //showing notice messages
    public static function notice_div()
    {
        if (isset($_GET)) {
            $input = self::sanitize_data($_GET);
            if (isset($input['type']) && isset($input['message'])) {
                return '<div class="notice ' . $input['type'] . ' is-dismissible" >
                    <p>' . $input['message'] . '</p>
                </div>';
            }
        }
    }

    //getting visitor  address
    public static function visitor_ip()
    {
        if (array_key_exists('HTTP_CLIENT_IP', $_SERVER) && $_SERVER['HTTP_CLIENT_IP'])
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        else if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER) && $_SERVER['HTTP_X_FORWARDED_FOR'])
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if (array_key_exists('HTTP_X_FORWARDED', $_SERVER) && $_SERVER['HTTP_X_FORWARDED'])
            $ip = $_SERVER['HTTP_X_FORWARDED'];
        else if (array_key_exists('HTTP_FORWARDED_FOR', $_SERVER) && $_SERVER['HTTP_FORWARDED_FOR'])
            $ip = $_SERVER['HTTP_FORWARDED_FOR'];
        else if (array_key_exists('HTTP_FORWARDED', $_SERVER) && $_SERVER['HTTP_FORWARDED'])
            $ip = $_SERVER['HTTP_FORWARDED'];
        else if (array_key_exists('REMOTE_ADDR', $_SERVER) && $_SERVER['REMOTE_ADDR'])
            $ip = $_SERVER['REMOTE_ADDR'];
        else
            $ip = '';

        if ($ip === '::1') {
            $ip = '103.133.206.230';
        }
        return $ip;
    }

    //getting location data from ipstack.com by ip
    public static function visitor_location()
    {
        if (!self::$_location) {
            $ip = self::visitor_ip();
            $option = self::$name . "_location_$ip";
            $location = get_option($option);
            if (!$location) {
                $option_name = self::option_name()[0];
                $apiKey = self::option_value()[$option_name]['ipstackapikey'];

//                Please note that, this plugin relies on ipstack.com to get visitors' IP location
                $url = "http://api.ipstack.com/$ip?access_key=$apiKey";
                $res = wp_remote_retrieve_body(wp_remote_get($url));
                if ($res)
                    update_option($option, json_decode($res, true));
            }
            self::$_location = $location;
        }
        return self::$_location;
    }

}