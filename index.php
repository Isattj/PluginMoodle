<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$endpoints = [
    'core_grades_get_course_grades'     => 'local_myplugin_core_grades_get_course_grades',
    'get_courses_informations_by_user'  => 'local_myplugin_get_courses_informations_by_user',
    'get_competencies_by_user'          => 'local_myplugin_get_competencies_by_user',
    'get_quiz_questions'                => 'local_myplugin_get_quiz_questions',
    'get_students_informations'         => 'local_myplugin_get_students_informations',
    'get_users_roles'                   => 'local_myplugin_get_users_roles',
    'get_activities_by_user'            => 'local_myplugin_get_activities_by_user',
    'get_activities_by_course'          => 'local_myplugin_get_activities_by_course'
];

$response = null;
$error = null;

$user_id   = $_POST['user_id'] ?? null;
$course_id = $_POST['course_id'] ?? ($_POST['context_id'] ?? null);
$selected_endpoint = $_POST['endpoint'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$user_id) {
        $error = "Não foi possível identificar o usuário.";
    } elseif (!$selected_endpoint || !isset($endpoints[$selected_endpoint])) {
        $error = "Selecione um endpoint válido.";
    } elseif (($selected_endpoint !== 'get_courses_by_user') && !$course_id) {
        $error = "O endpoint não recebeu as informações necessárias";
    }
    
    else {
        $moodle_url = 'http://127.0.0.1/moodle/webservice/rest/server.php';
        $token      = '10b829c5fbaa7d7379007b48c087996a';
        $function   = $endpoints[$selected_endpoint];

        $postfields = [
            'wstoken' => $token,
            'wsfunction' => $function,
            'moodlewsrestformat' => 'json',
        ];

        switch ($selected_endpoint) {
            case 'get_courses_informations_by_user':
            case 'get_competencies_by_user':
            case 'get_activities_by_user':
                $postfields['userid'] = $user_id;
                break;
                
            case 'core_grades_get_course_grades':
            case 'get_quiz_questions':
            case 'get_students_informations':
            case 'get_activities_by_course':
                $postfields['courseid'] = $course_id;
                break;

            case 'get_users_roles':
                $postfields['courseid'] = $course_id;
                break;

            default:
                $error = "Endpoint não configurado corretamente.";
                break;
}

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $moodle_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);

        $raw_response = curl_exec($ch);
        if ($raw_response === false) {
            $error = 'Erro CURL: ' . curl_error($ch);
        } else {
            $response = json_decode($raw_response, true);
        }
        curl_close($ch);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Ferramenta Externa</title>
</head>
<body>

<h2>Ferramenta Externa</h2>


<?php if ($user_id): ?>
    <p><strong>Usuário logado:</strong> <?= htmlspecialchars($user_id) ?></p>
<?php endif; ?>

<form method="post">
    <input type="hidden" name="user_id" value="<?= htmlspecialchars($user_id) ?>">
    <?php if ($course_id): ?>
        <input type="hidden" name="course_id" value="<?= htmlspecialchars($course_id) ?>">
    <?php endif; ?>

    <label>Escolha o endpoint:</label><br>
    <select name="endpoint">
        <option value="">  Selecione  </option>
        <?php foreach ($endpoints as $key => $func): ?>
            <option value="<?= $key ?>" <?= (($selected_endpoint ?? '') === $key) ? 'selected' : '' ?>>
                <?= $func ?>
            </option>
        <?php endforeach; ?>
    </select><br><br>

    <button type="submit">Consultar</button>
</form>


<?php if ($error): ?>
    <p style="color:red"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<?php if ($response !== null): ?>
    <pre><?= json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></pre>
<?php endif; ?>

</body>
