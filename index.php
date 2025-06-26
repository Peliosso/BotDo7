<?php
$bot_token = "7152860548:AAFTLPfNHBksGCudquJxNQlgWgGn2r-etUs";
$api_url = "https://api.telegram.org/bot$bot_token";

// Lê a mensagem do Telegram (JSON)
$update = json_decode(file_get_contents('php://input'), true);

if (!$update || !isset($update['message'])) {
    exit;
}

$message = $update['message'];
$chat_id = $message['chat']['id'];
$text = trim($message['text'] ?? '');

if (strpos($text, '/cpf ') === 0) {
    $cpf = preg_replace('/[^0-9]/', '', substr($text, 5));
    if (strlen($cpf) !== 11) {
        sendMessage($chat_id, "❌ CPF inválido. Envie assim: /cpf 12345678900");
        exit;
    }

    $url = "https://apiconsulta-y9mu.onrender.com/?cpf=$cpf&token=playboy";
    $response = json_decode(file_get_contents($url), true);

    if (!$response || !$response["status"]) {
        sendMessage($chat_id, "❌ CPF não encontrado.");
        exit;
    }

    $dados = $response["dados_formatados"];
    $msg = "🔎 *Consulta de CPF*\n\n" .
           "*Nome:* " . $dados["Nome Completo"] . "\n" .
           "*Mãe:* " . $dados["Mãe"] . "\n" .
           "*Nascimento:* " . $dados["Data de Nascimento"] . "\n" .
           "*CPF:* " . $dados["CPF"] . "\n" .
           "*Endereço:* " . $dados["Endereço"]["Logradouro"] . ", " . $dados["Endereço"]["Número"] . " - " . $dados["Endereço"]["Bairro"] . "\n" .
           "*Cidade:* " . $dados["Endereço"]["Município de Residência"] . "\n" .
           "*Telefone:* (" . $dados["Telefone"]["DDD"] . ") " . $dados["Telefone"]["Número"];

    sendMessage($chat_id, $msg, true);

} elseif (strpos($text, '/nome ') === 0) {
    $nome = urlencode(trim(substr($text, 6)));
    if (strlen($nome) < 3) {
        sendMessage($chat_id, "❌ Nome inválido. Envie assim: /nome fulano de tal");
        exit;
    }

    $url = "https://mdzapis.com/api/consultanew?base=nome_completo&query=$nome&apikey=Ribeiro7";
    $response = json_decode(file_get_contents($url), true);

    if (!$response || !isset($response["RESULTADOS"][0])) {
        sendMessage($chat_id, "❌ Nome não encontrado.");
        exit;
    }

    $r = $response["RESULTADOS"][0];
    $msg = "🔍 *Consulta por Nome*\n\n" .
           "*Nome:* " . trim($r["NOME"]) . "\n" .
           "*CPF:* " . $r["CPF"] . "\n" .
           "*Sexo:* " . $r["SEXO"] . "\n" .
           "*Mãe:* " . trim($r["NOME_MAE"]) . "\n" .
           "*Nascimento:* " . $r["NASC"];

    sendMessage($chat_id, $msg, true);

} else {
    sendMessage($chat_id, "👋 Envie:\n/cpf 12345678900\n/nome fulano de tal");
}

// === Função para enviar mensagem formatada
function sendMessage($chat_id, $text, $markdown = false) {
    global $api_url;
    $params = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => $markdown ? 'Markdown' : null
    ];
    file_get_contents($api_url . "/sendMessage?" . http_build_query($params));
}
?>
