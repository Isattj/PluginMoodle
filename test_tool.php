<?php
// test_tool.php fora do Moodle

echo "<h2>Ferramenta LTI Recebeu os Dados!</h2>";

if (!empty($_POST)) {
    echo "<h3>Parâmetros POST recebidos:</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
} else {
    echo "<p>Nenhum parâmetro recebido no POST.</p>";
}

$data = $_POST['custom_data'] ?? null;

if ($data) {
    echo "<h3>Conteúdo de custom_data (JSON decodificado):</h3>";
    $json = json_decode($data, true);
    if ($json) {
        echo '<pre>';
        print_r($json);
        echo '</pre>';
    } else {
        echo "<p>Erro ao decodificar JSON.</p>";
    }
} else {
    echo "<p><strong>custom_data não foi enviado.</strong></p>";
}

