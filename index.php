<?php
//Ativa a exibição de erros
ini_set('display_errors', 1);
error_reporting(E_ALL);

//Lista de endipoints do moodle
//chave -> valor
$endpoints = [
    'get_activities_by_course'          => 'local_myplugin_get_activities_by_course',
    'get_activities_by_user'            => 'local_myplugin_get_activities_by_user',
    'get_competencies_by_user'          => 'local_myplugin_get_competencies_by_user',
    'get_courses_informations_by_user'  => 'local_myplugin_get_courses_informations_by_user',
    'get_logs_users'                    => 'local_myplugin_get_logs_users',
    'get_quiz_questions'                => 'local_myplugin_get_quiz_questions',
    'get_students_informations'         => 'local_myplugin_get_students_informations',
];

//Recebimento de variáveis vindas do POST do LTI
$user_id = $_POST['user_id'] ?? null;
$course_id = $_POST['course_id'] 
    ?? $_POST['context_id'] 
    ?? $_POST['custom_course_id'] 
    ?? null;
$roles = $_POST['roles'] ?? '';
$lis_name = $_POST['lis_person_name_full'] ?? "Usuário desconhecido";
$email = $_POST['lis_person_contact_email_primary'] ?? '';

//Preparação das variáveis
$response = null;
$error = null;
$selected_endpoint = $_POST['endpoint'] ?? null;

//Tratamento ao enviar POST
//Se o formulário for enviado ele valida se falta o userid, se o endpoint selecionado existe e se exige o courseId.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['endpoint'])) {
    if (!$user_id) {
        $error = "Não foi possível identificar o usuário.";
    } elseif (!$selected_endpoint || !isset($endpoints[$selected_endpoint])) {
        $error = "Selecione um endpoint válido.";
    } elseif (in_array($selected_endpoint, ['get_students_informations', 'get_activities_by_course', 'get_quiz_questions']) && !$course_id) {
        $error = "Este endpoint precisa de um course_id.";
    } else {

        //Configuração da chamada ao Moodle. Configura a URL do servidor Moodle, token do WebService e a função a ser chamada.
        $moodle_url = 'http://127.0.0.1/moodle/webservice/rest/server.php';
        $token      = '53f6c6e36617a9dd47853f767baea388';
        $function   = $endpoints[$selected_endpoint];

        //Montagem dos parâmetros para o Moodle
        $postfields = [
            'wstoken' => $token,
            'wsfunction' => $function,
            'moodlewsrestformat' => 'json',
        ];

        //Parâmetros específicos por endpoint
        switch ($selected_endpoint) {
            case 'get_activities_by_user':
                $postfields['userid'] = $user_id;
                $postfields['realuserid'] = $user_id;
                break;
                
            case 'get_students_informations':
            case 'get_activities_by_course':
                $postfields['courseid'] = $course_id;
                $postfields['realuserid'] = $user_id;
                break;
            case 'get_competencies_by_user':
            case 'get_courses_informations_by_user':
            case 'get_logs_users':
                $postfields['userid'] = $user_id;
                break;
            case 'get_quiz_questions':
                $postfields['courseid'] = $course_id;
                break;
            default:
                $error = "Endpoint não configurado corretamente.";
                break;
        }

        //Envio da requisição cURL ao Moodle
        if(!$error){
            //Inicializa a sessão cURL
            $ch = curl_init();

            //Define a url para onde será mandada a requisição
            curl_setopt($ch, CURLOPT_URL, $moodle_url);

            //Diz para cURL retornar a resposta em vez de abrir ela
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            //Informa que será um POST
            curl_setopt($ch, CURLOPT_POST, true);

            //Envia os parâmetros da requisição
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
    
            //excuta a requisição
            $raw_response = curl_exec($ch);
            if ($raw_response === false) {
                $error = 'Erro CURL: ' . curl_error($ch);
            } else {
                //Se não der erro ele decodifica o JSON
                $response = json_decode($raw_response, true);
            }
            //Fecha a conexão cURL
            curl_close($ch);
        }
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
