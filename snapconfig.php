<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package   tool_snapconfig
 * @category  tool
 * @author    Valery Fremaux <valery.fremaux@gmail.com>
 */

require('../../../config.php');

// Security.

require_login();
$systemcontext = context_system::instance();
require_capability('moodle/site:config', $systemcontext);

$PAGE->set_url(new moodle_url('/admin/tool/snapconfig/snapconfig.php'));
$PAGE->set_context($systemcontext);
$PAGE->set_heading(get_string('pluginname', 'tool_snapconfig'));
// $PAGE->navbar->add(get_string('pluginname', 'tool_snapconfig'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('currentconf', 'tool_snapconfig'));

if ($full = optional_param('full', '', PARAM_BOOL)) {

    $confs = array();

    if ($full) {
        $conf = $DB->get_records_menu('config', array(), 'name', 'name,value');
        foreach ($conf as $key => $value) {
            $confs['core'][$key] = $value;
        }

        $pluginconf = $DB->get_records('config_plugins', array(), 'plugin,name', 'id,plugin,name,value');
        foreach ($pluginconf as $key => $cf) {
            $confs[$cf->plugin][$cf->name] = $cf->value;
        }

        $file = '';
        foreach ($confs as $plugin => $pluginconf) {
            $plugin = empty($plugin) ? 'core' : $plugin;
            foreach ($pluginconf as $name => $value) {
                $str = "\$default['{$plugin}']['{$name}'] = '".str_replace("'", "\\'", $value)."';\n";
                $file .= $str;
            }
            $file .= "\n";
        }
        echo '<pre>';
        echo $file;
        echo '</pre>';

        if ($FILE = fopen($CFG->dirroot.'/local/defaults_static.php', 'w+')) {
            fputs($FILE, "<?php\n");
            fputs($FILE, $file);
            fclose($FILE);
        } else {
            echo $OUTPUT->notification(get_string('errorfilewritenotallowed', 'tool_snapconfig'));
        }
    }
} else if (optional_param('generate', '', PARAM_BOOL)) {

    $confs = array();

    $configchanges = $DB->get_records_select('config_log', " userid > 0 ", array(), 'timemodified');

    if ($configchanges) {
        foreach ($configchanges as $cf) {
            $confs[$cf->plugin][$cf->name] = $cf->value;
        }

        $file = '';

        echo '<pre>';
        foreach ($confs as $plugin => $pluginconf) {
            $plugin = empty($plugin) ? 'core' : $plugin;
            foreach ($pluginconf as $name => $value) {
                $str = "\$default['{$plugin}']['{$name}'] = '".str_replace("'", "\\'", $value)."';\n";
                $file .= $str;
            }
            $file .= "\n";
        }
        echo $file;
        echo '</pre>';

        if ($FILE = fopen($CFG->dirroot.'/local/defaults.php', 'w+')) {
            fputs($FILE, "<?php\n");
            fputs($FILE, $file);
            fclose($FILE);
        } else {
            echo $OUTPUT->notification(get_string('errorfilewritenotallowed', 'tool_snapconfig'));
        }
    }
} elseif (optional_param('reset', '', PARAM_TEXT)) {
    require($CFG->dirroot.'/local/defaults.php');
    if (!empty($defaults)) {

        echo $OUTPUT->heading(get_string('resettingsnapshot', 'tool_snapconfig'));

        $table = new html_table();
        $table->head = array($pluginstr, $namestr, $oldvaluestr, $valuestr);
        $table->width = '100%';
        $table->size = array('25%', '25%', '25%', '25%');
        foreach ($defaults as $plugin => $config) {
            $oldconfig = get_config($plugin);
            foreach ($config as $name => $value) {
                $table->data($plugin, $name, $oldvalue, $value);
                set_config($name, $value, $plugin);
            }
        }

        echo html_writer::table($table);
    }
}

echo $OUTPUT->single_button(new moodle_url('/admin/tool/snapconfig/snapconfig.php', array('generate' => 1)), get_string('snapshot', 'tool_snapconfig'));
echo $OUTPUT->single_button(new moodle_url('/admin/tool/snapconfig/snapconfig.php', array('full' => 1)), get_string('snapshotstatic', 'tool_snapconfig'));

if (file_exists($CFG->dirroot.'/local/defaults.php')) {
    echo $OUTPUT->single_button(new moodle_url('/admin/tool/snapconfig/snapconfig.php', array('reset' => 1)), get_string('reset', 'tool_snapconfig'));
}

echo $OUTPUT->footer();