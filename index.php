<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$user_id = $_POST['user_id'] ?? null;

if (!$user_id) {
    die(json_encode(['error' => 'Parâmetros LTI incompletos. User ID ausente.']));
}

$moodle_url = 'http://127.0.0.1/moodle/webservice/rest/server.php';
$token      = 'cd2eaeec09ee16da91b9221d4d9f0259';
$function   = 'local_myplugin_get_courses_informations_by_user';

$postfields = [
    'wstoken' => $token,
    'wsfunction' => $function,
    'moodlewsrestformat' => 'json',
    'userid' => $user_id,
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $moodle_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);

$response = curl_exec($ch);
if ($response === false) {
    die(json_encode(['error' => 'Erro CURL: ' . curl_error($ch)]));
}
curl_close($ch);

$data = json_decode($response, true);

if (isset($data['exception'])) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'error' => true,
        'message' => $data['message'] ?? 'Erro desconhecido no web service',
        'debug' => $data,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($data)) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['message' => 'Nenhum curso encontrado para este usuário.'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
