<?php
defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/mod/lti/OAuth.php');

use moodle\mod\lti as lti;

$id = required_param('id', PARAM_INT);

if ($id == SITEID) {
    $course = get_site();
    $context = context_system::instance();
    require_login();
} else {
    $course = get_course($id);
    $context = context_course::instance($course->id);
    require_login($course);
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/myplugin/lti.php', ['id' => $id]));
$PAGE->set_pagelayout('embedded');

$launchurl = get_config('local_myplugin', 'externalurl');
$key = get_config('local_myplugin', 'apikey');
$secret = get_config('local_myplugin', 'secret');

if (!($launchurl && $key && $secret)) {
    echo $OUTPUT->header();
    echo html_writer::tag('p', 'Configure externalurl, apikey e secret nas configurações do plugin.');
    echo $OUTPUT->footer();
    exit;
}

// --- Export URL
$exporturl = $CFG->wwwroot . '/local/myplugin/export.php';

// --- Parâmetros LTI padrão
$requestparams = [
    'resource_link_id' => $course->id,
    'resource_link_title' => $course->fullname,
    'user_id' => $USER->id,
    'roles' => 'Instructor',
    'lis_person_name_given' => $USER->firstname,
    'lis_person_name_family' => $USER->lastname,
    'lis_person_contact_email_primary' => $USER->email,
    'lti_version' => 'LTI-1p0',
    'lti_message_type' => 'basic-lti-launch-request',
];

// --- OAuth HMAC-SHA1
$hmacmethod = new lti\OAuthSignatureMethod_HMAC_SHA1();
$consumer = new lti\OAuthConsumer($key, $secret, null);
$accreq = lti\OAuthRequest::from_consumer_and_token($consumer, '', 'POST', $launchurl, $requestparams);
$accreq->sign_request($hmacmethod, $consumer, '');
$parms = $accreq->get_parameters();

// --- Debug
echo $OUTPUT->header();
echo html_writer::tag('h2', 'LTI Debug — parâmetros enviados ao tool externo');
echo '<p>launchurl: ' . s($launchurl) . '</p>';
echo '<pre>';
print_r($parms);
echo '</pre>';

// --- Form POST automático
echo '<form action="'.htmlspecialchars($launchurl).'" method="POST" id="ltiLaunchForm">';
foreach ($parms as $k => $v) {
    if (is_array($v)) { $v = implode(',', $v); }
    echo '<input type="hidden" name="'.htmlspecialchars($k).'" value="'.htmlspecialchars($v).'">';
}
echo '</form>';

// Auto-submit após 1s
echo '<script>setTimeout(function(){ document.getElementById("ltiLaunchForm").submit(); }, 1000);</script>';
echo $OUTPUT->footer();
