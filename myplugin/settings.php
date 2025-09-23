<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_myplugin', get_string('pluginname', 'local_myplugin'));
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configtext('local_myplugin/externalurl',
        get_string('externalurl', 'local_myplugin'), '', '', PARAM_URL));

    $settings->add(new admin_setting_configtext('local_myplugin/apikey',
        get_string('apikey', 'local_myplugin'), '', '', PARAM_TEXT ));

    $settings->add(new admin_setting_configpasswordunmask('local_myplugin/secret',
        get_string('secret', 'local_myplugin'), '', '' ));
}