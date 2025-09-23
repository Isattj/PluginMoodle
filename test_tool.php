<?php
echo '<h1>Ferramenta externa</h1>';
echo '<h2>Ferramenta LTI Recebeu os Dados!</h2>';

// mostra parâmetros POST
echo '<h3>Parâmetros POST:</h3><pre>';
print_r($_POST);
echo '</pre>';

// busca export_url enviado pelo Moodle
if (!empty($_POST['custom_plugin_export_url'])) {
    $url = $_POST['custom_plugin_export_url'];
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
    echo '<p>Campo custom_plugin_export_url não enviado.</p>';
}
