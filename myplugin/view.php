<?php
require_once(__DIR__ . '/../../config.php');

global $USER, $COURSE;

$context = context_course::instance($COURSE->id);


require_capability('local/myplugin:view', $context);

$PAGE->set_url(new moodle_url('/local/myplugin/view.php', ['id' => $COURSE->id]));
$PAGE->set_context($context);
$PAGE->set_title('Test MyPlugin');
$PAGE->set_heading('Test MyPlugin');

echo $OUTPUT->header();
echo $OUTPUT->heading('Testando integração da API');


$externalurl = get_config('local_myplugin', 'externalurl');
$apikey = get_config('local_myplugin', 'apikey');
$secret = get_config('local_myplugin', 'secret');

echo html_writer::tag('p', "External URL: $externalurl");
echo html_writer::tag('p', "API Key: $apikey");
echo html_writer::tag('p', "Secret: $secret");


$testurl = 'https://postman-echo.com/get';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $testurl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_USERAGENT, 'MoodleMyPluginTest/1.0');


$response = curl_exec($ch);

if ($response === false) {
    echo html_writer::tag('p', 'Erro ao conectar com a API! cURL: ' . curl_error($ch));
} else {
    echo html_writer::tag('p', 'Conexão com API pública OK!');

    echo html_writer::tag('pre', htmlspecialchars(substr($response,0,500)) . '...');
}

curl_close($ch);

echo $OUTPUT->footer();
