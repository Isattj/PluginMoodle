<?php
echo '<h2>Ferramenta LTI Recebeu os Dados!</h2>';

// 1) mostra todos os parâmetros POST
echo '<h3>Parâmetros POST:</h3><pre>';
print_r($_POST);
echo '</pre>';

// 2) busca export_url enviado pelo Moodle
if (!empty($_POST['custom_plugin_export_url'])) {
    $url = $_POST['custom_plugin_export_url'];
    echo '<h3>Buscando dados via export_url: '.$url.'</h3>';

    $json = @file_get_contents($url);
    if ($json === false) {
        echo "<p>Falha ao buscar $url</p>";
    } else {
        $data = json_decode($json, true);
        echo '<pre>';
        print_r($data);
        echo '</pre>';
    }
} else {
    echo '<p>Campo custom_plugin_export_url não enviado.</p>';
}
?>
