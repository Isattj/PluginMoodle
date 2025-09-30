<?php
echo '<h1>Ferramenta externa</h1>';
echo '<h2>Ferramenta LTI Recebeu os Dados!</h2>';

echo '<h3>Parâmetros POST:</h3><pre>';
print_r($_POST);
echo '</pre>';

$courseid = isset($_POST['context_id']) ? (int)$_POST['context_id'] : 0;
$userid = (int)$_POST['user_id'];

if ($courseid) {
    echo '<h3>Modo Curso</h3>';
    $url = "http://127.0.0.1/moodle/local/myplugin/exports/export_course.php?courseid=$courseid";
    echo '<h3>Buscando dados via export_url: '.$url.'</h3>';

    $json = @file_get_contents($url);

    if ($json === false) {
        echo "<p>Falha ao buscar $url</p>";
    } else {
        $data = json_decode($json, true);
        if ($data === null) {
            echo "<p>Erro: JSON inválido</p><pre>$json</pre>";
        } else {
            echo '<pre>';
            print_r($data);
            echo '</pre>';
        }
    }

    echo '<h3>Modo Usuário</h3>';
    $url2 = "http://127.0.0.1/moodle/local/myplugin/exports/export_user.php?userid=$userid";
    echo '<h3>Buscando dados via export_url: '.$url2.'</h3>';

    $json2 = @file_get_contents($url2);
    if ($json2 === false) {
        echo "<p>Falha ao buscar $url2</p>";
    } else {
        $data2 = json_decode($json2, true);
        if ($data2 === null) {
            echo "<p>Erro: JSON inválido</p><pre>$json</pre>";
        } else {
            echo '<pre>';
            print_r($data2);
            echo '</pre>';
        }
    }


    echo '<h3>Logs deste usuário no Moodle</h3>';
    $url3 = "http://127.0.0.1/moodle/local/myplugin/exports/export_logs.php?userid=$userid&courseid=$courseid";
    echo '<h3>Buscando dados via export_url: '.$url3.'</h3>';

    $json3 = @file_get_contents($url3);
    if ($json3 === false) {
        echo "<p>Falha ao buscar $url3</p>";
    } else {
        $data3 = json_decode($json3, true);
        if ($data3 === null) {
            echo "<p>Erro: JSON inválido</p><pre>$json</pre>";
        } else {
            echo '<pre>';
            print_r($data3);
            echo '</pre>';
        }
    }

} else {
    echo '<p>Não foi possível identificar o courseid.</p>';
}

