<?php
echo '<h1>Ferramenta externa</h1>';
echo '<h2>Ferramenta LTI Recebeu os Dados!</h2>';

echo '<h3>Parâmetros POST:</h3><pre>';
print_r($_POST);
echo '</pre>';

$courseid = isset($_POST['context_id']) ? (int)$_POST['context_id'] : 0;

if ($courseid) {
    $url = "http://127.0.0.1/moodle/local/myplugin/export.php?courseid=$courseid";
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
} else {
    echo '<p>Não foi possível identificar o courseid.</p>';
}

