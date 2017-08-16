<?php

/**
 * Class Plugin
 *
 * This is a utility class for debugging inside of your hook and plugin code.
 *
 *
 * SETUP:
 * 1) Install this file somewhere on your server.  In my case I'm putting it in
 *
 *      plugins/utility/Plugin.php
 *
 * 2) Let's force REDCap to load this class each time it runs - this can be done by appending one line to your
 * database.php file.  So, at the end of your database.php file, add:
 *
 *      include_once("plugins/utility/Plugin.php");
 *
 * 3) You can customize the behavior of the logging by editing the 'default_' options at the beginning of the class
 * or by doing per-project 'override' options inside of the override array in this file.
 *
 *      For example, if you can just tell it to use the default php error log file.
 *
 *      If you run the same code on different servers, you can set a log file location in the redcap_config table
 *      so your code will pick a log file location that is custom to that server.  To do this, insert the following
 *      sql statement into your REDCap database:
 *
 *      insert into redcap_config ('plugin_log_file', '/var/log/plugin.log');
 *
 * USE:
 * Anywhere in your code, you can add a call to Plugin::log("message", "type", (optional) "prefix"
 *
 * The 'message' can be a String/Array/Object/Boolean/ or basically any variable type - no need to convert it.
 *
 * Here are some samples:
 *      Plugin::log($Proj, "DEBUG", "Proj Object");
 *
 *      Plugin::log($username, "DEBUG", "Username at Step 1");
 *
 *      Plugin::log("Something went wrong!", "ERROR");
 *
 *
 *
 * You can set the log file location by writing to the global variable $plugin_log_file
 *
 *
 * You can debug by using the php error log by setting the default_use_error_log = 2.
 *
 * If you are in the context of REDCap - then you can make this a global by adding it to the redcap_config table
 *
 *
 */
class Plugin {
    public static function log($message, $type = 'DEBUG', $prefix = '', $full_backtrace = false) {
        global $project_id, $plugin_log_file;

        // SET DEFAULTS OPTIONS
        $default_debug_level    = 2;               // 2 = ALL, 1= INFO+ERROR, 0 = ERROR ONLY
        $default_use_error_log  = true;
        $default_log_file       = $plugin_log_file;

        // YOU CAN OVERRIDE THESE ON A PER PROJECT BASIS
        $override = array(
            '1234 (example custom log file for project)' => array('debug_level' => 2, 'use_error_log' => false, 'log_file' => '/var/log/pid123.log'),
        );

        // SET CURRENT DEBUG OPTIONS
        $log_file       = !empty($override[$project_id]['log_file'])      ? $override[$project_id]['log_file']        : $default_log_file;
        $debug_level    = isset($override[$project_id]['debug_level'])    ? $override[$project_id]['debug_level']     : $default_debug_level;
        $use_error_log  = isset($override[$project_id]['use_error_log'])  ? $override[$project_id]['use_error_log']   : $default_use_error_log;

        $loggable = ($type == 'ERROR') || ($debug_level == 1 && $type == 'INFO') || ($debug_level > 1);

        if ($loggable) {
            // Get calling file using php backtrace to help label where the log entry is coming from
            $bt = debug_backtrace();
            $calling_file = $bt[0]['file'];
            $calling_line = $bt[0]['line'];
            $calling_function = $bt[3]['function'];
            if (empty($calling_function)) $calling_function = $bt[2]['function'];
            if (empty($calling_function)) $calling_function = $bt[1]['function'];
            // if (empty($calling_function)) $calling_function = $bt[0]['function'];

            // Convert arrays/objects into string for logging
            if (is_array($message)) {
                $msg = "(array): " . print_r($message,true);
            } elseif (is_object($message)) {
                $msg = "(object): " . print_r($message,true);
            } elseif (is_string($message) || is_numeric($message)) {
                $msg = $message;
            } elseif (is_bool($message)) {
                $msg = "(boolean): " . ($message ? "true" : "false");
            } else {
                $msg = "(unknown): " . print_r($message,true);
            }

            // Prepend prefix
            if ($prefix) $msg = "[$prefix] " . $msg;

            // Build log array
            $output = array(
                "TS" => date( 'Y-m-d H:i:s' ),
                "PID" => empty($project_id) ? "-" : $project_id,
                "File" => basename($calling_file, '.php'),
                "Line" => $calling_line,
                "Function" => $calling_function,
                "Type" => $type,
                "Message" => $msg
            );

            // Add Backtrace
            if ($full_backtrace) $output['Trace'] = json_encode($bt);

            // Output to plugin log if defined, else use error_log
            if (!empty($log_file)) {
                $result = file_put_contents(
                    $log_file,
                    implode("\t",$output) . "\n",
                    FILE_APPEND
                );
                if ($result === false) {
                    // Invalid permissions to write to file
                    $msg = __CLASS__ . " in " . __FILE__ . " unable to write to log file $log_file - check permissions!";
                    error_log($msg);
                    throw new exception($msg);
                }
            }

            // Output to error_log
            if ($use_error_log) {
                // Output to error log
                // Drop the date
                array_shift($output);
                error_log(implode(" \t",$output));
            }

            // Output to screen
            if ($debug_level == 3) {
                print "<pre style='background: #eee; border: 1px solid #ccc; padding: 5px;'>PLUGIN LOG";
                foreach ($output as $k => $v) print "\n$k: $v";
                print "</pre>";
            }
        }

        //TODO: Maybe
    }


    // A utility for inserting a tab into the page
    public static function injectPluginTabs($tab_href, $tab_name, $image_name = 'gear.png') {
        $msg = '<script>
		jQuery("#sub-nav ul li:last-child").before(\'<li class="active"><a style="font-size:13px;color:#393733;padding:4px 9px 7px 10px;" href="'.$tab_href.'"><img src="' . APP_PATH_IMAGES . $image_name . '" class="imgfix" style="height:16px;width:16px;"> ' . $tab_name . '</a></li>\');
		</script>';
        echo $msg;
    }

}

