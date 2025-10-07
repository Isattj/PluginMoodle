<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Dados enviados via LTI
$user_id = $_POST['user_id'] ?? null;

if (!$user_id) {
    die("Parâmetros LTI incompletos. User ID ausente.");
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
    die("Erro CURL: " . curl_error($ch));
}
curl_close($ch);

$data = json_decode($response, true);

if (isset($data['exception'])) {
    echo "<h2>Erro no Web Service</h2>";
    echo "<pre>" . print_r($data, true) . "</pre>";
    exit;
}

if (empty($data)) {
    echo "<p>Nenhum curso encontrado para este usuário.</p>";
    exit;
}

echo "<h1>Cursos em que o usuário está matriculado</h1>";

foreach ($data as $course) {
    echo "<h2>{$course['fullname']} ({$course['shortname']})</h2>";
    echo "<p><strong>ID:</strong> {$course['id']} | ";
    echo "<strong>Início:</strong> " . date('d/m/Y', $course['startdate']) . " | ";
    echo "<strong>Última modificação:</strong> " . date('d/m/Y H:i', $course['timemodified']) . "</p>";

    if (!empty($course['users'])) {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>User ID</th><th>Nome</th><th>Email</th><th>Funções</th></tr>";

        foreach ($course['users'] as $user) {
            $roles = array_map(function($r) {
                return $r['rolename'];
            }, $user['roles']);

            $roles_str = implode(', ', $roles);

            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['fullname']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$roles_str}</td>";
            echo "</tr>";
        }

        echo "</table>";
    } else {
        echo "<p>Nenhum usuário matriculado neste curso.</p>";
    }

    echo "<hr>";
}
?>
