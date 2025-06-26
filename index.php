<?php
$bot_token = "7152860548:AAFTLPfNHBksGCudquJxNQlgWgGn2r-etUs";
$api_url = "https://api.telegram.org/bot$bot_token";
$autorizados = [7926471341];

$update = json_decode(file_get_contents("php://input"), true);

function sendMessage($chat_id, $text, $buttons = null, $markdown = false) {
    global $api_url;
    $params = ['chat_id' => $chat_id, 'text' => $text];
    if ($markdown) $params['parse_mode'] = "Markdown";
    if ($buttons) $params['reply_markup'] = json_encode(['inline_keyboard' => $buttons]);
    file_get_contents($api_url . "/sendMessage?" . http_build_query($params));
}

function sendDocument($chat_id, $filename, $jsonData) {
    global $api_url;
    $f = tmpfile();
    fwrite($f, $jsonData);
    $meta = stream_get_meta_data($f);
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "$api_url/sendDocument",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => [
            'chat_id' => $chat_id,
            'document' => new CURLFile($meta['uri'], 'application/json', $filename),
            'caption' => "📎 Veja sua consulta completa no arquivo.\n\n_Créditos: @ConsultasDo171_bot_",
            'parse_mode' => 'Markdown',
            'reply_markup' => json_encode([
                'inline_keyboard' => [[['text' => '🌐 Métodos do 7', 'url' => 'https://paineldo7.rf.gd']]]
            ])
        ]
    ]);
    curl_exec($curl);
    fclose($f);
}

function startMenu() {
    return [
        [["text" => "🔍 Consultar CPF", "callback_data" => "cpf"], ["text" => "👤 Consultar Nome", "callback_data" => "nome"]],
        [["text" => "📞 Consultar Telefone", "callback_data" => "tel"], ["text" => "🚗 Consultar Placa", "callback_data" => "placa"]],
        [["text" => "💸 Planos", "callback_data" => "planos"]]
    ];
}

function edit($text, $msg) {
    global $api_url;
    $params = [
        'chat_id' => $msg['chat']['id'],
        'message_id' => $msg['message_id'],
        'text' => $text,
        'parse_mode' => 'Markdown',
        'reply_markup' => json_encode(['inline_keyboard' => startMenu()])
    ];
    file_get_contents($api_url . "/editMessageText?" . http_build_query($params));
}

if (isset($update['callback_query'])) {
    $cb = $update['callback_query'];
    $chat_id = $cb['message']['chat']['id'];
    $data = $cb['data'];
    $msg = $cb['message'];

    switch ($data) {
        case 'cpf':
            edit("📌 *Consulta CPF*\n\nEnvie:\n`/cpf 70192822616`", $msg);
            break;
        case 'nome':
            edit("📌 *Consulta Nome*\n\nEnvie:\n`/nome joao da silva`", $msg);
            break;
        case 'tel':
            edit("📌 *Consulta Telefone*\n\nEnvie:\n`/tel 31975037371`", $msg);
            break;
        case 'placa':
            edit("🚗 Consulta de placa está em manutenção.", $msg);
            break;
        case 'planos':
            edit("💸 *Plano vitalício*: R\$50,00\n\nFale com @RibeiroDo171", $msg);
            break;
    }
    exit;
}

if (!isset($update['message'])) exit;
$msg = $update['message'];
$chat_id = $msg['chat']['id'];
$from_id = $msg['from']['id'];
$text = $msg['text'] ?? '';

if (!in_array($from_id, $autorizados)) {
    sendMessage($chat_id, "🚫 *Acesso negado!*\n\nAdquira seu plano com @RibeiroDo171", null, true);
    exit;
}

if ($text == "/start") {
    sendMessage($chat_id, "👋 *Bem-vindo ao Bot!*\n\nSelecione abaixo:", startMenu(), true);
    exit;
}

sendMessage($chat_id, "⏳ *Aguarde...*\nConsultando seus dados...", null, true);

// === CONSULTA CPF ===
if (strpos($text, "/cpf ") === 0) {
    $cpf = preg_replace("/\D/", "", substr($text, 5));
    $url = "https://mdzapis.com/api/consultanew?base=cpf_serasa_completo&query=$cpf&apikey=Ribeiro7";
    $resposta = file_get_contents($url);

    if (!$resposta) {
        sendMessage($chat_id, "❌ *Erro ao acessar a API.*", null, true);
        exit;
    }

    $dados = json_decode($resposta, true);
    if (!$dados || !isset($dados['dados_pessoais'])) {
        sendMessage($chat_id, "❌ *CPF não encontrado ou resposta inválida.*", null, true);
        exit;
    }

    $json = json_encode([
        "status" => true,
        "mensagem" => "Consulta de CPF realizada com sucesso.",
        "data_consulta" => date("d/m/Y"),
        "hora_consulta" => date("H:i:s"),
        "dados" => $dados,
        "créditos" => "@ConsultasDo171_bot"
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    sendDocument($chat_id, "cpf-$cpf.json", $json);
    exit;
}

// === CONSULTA NOME ===
if (strpos($text, "/nome ") === 0) {
    $nome = urlencode(trim(substr($text, 6)));
    $url = "https://mdzapis.com/api/consultanew?base=nome_completo&query=$nome&apikey=Ribeiro7";
    $resposta = file_get_contents($url);

    if (!$resposta) {
        sendMessage($chat_id, "❌ *Erro ao acessar a API.*", null, true);
        exit;
    }

    $dados = json_decode($resposta, true);
    if (!$dados || !isset($dados['RESULTADOS'])) {
        sendMessage($chat_id, "❌ *Nome não encontrado ou resposta inválida.*", null, true);
        exit;
    }

    $json = json_encode([
        "status" => true,
        "mensagem" => "Consulta de nome realizada com sucesso.",
        "data_consulta" => date("d/m/Y"),
        "hora_consulta" => date("H:i:s"),
        "dados" => $dados,
        "créditos" => "@ConsultasDo171_bot"
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    sendDocument($chat_id, "nome.json", $json);
    exit;
}

// === CONSULTA TELEFONE ===
if (strpos($text, "/tel ") === 0) {
    $tel = preg_replace("/\D/", "", substr($text, 5));
    $url = "https://mdzapis.com/api/consultanew?base=consulta_telefone&query=$tel&apikey=Ribeiro7";
    $resposta = file_get_contents($url);

    if (!$resposta) {
        sendMessage($chat_id, "❌ *Erro ao acessar a API.*", null, true);
        exit;
    }

    $dados = json_decode($resposta, true);
    if (!$dados || !isset($dados['dados'])) {
        sendMessage($chat_id, "❌ *Telefone não encontrado ou resposta inválida.*", null, true);
        exit;
    }

    $json = json_encode([
        "status" => true,
        "mensagem" => "Consulta de telefone realizada com sucesso.",
        "data_consulta" => date("d/m/Y"),
        "hora_consulta" => date("H:i:s"),
        "dados" => $dados,
        "créditos" => "@ConsultasDo171_bot"
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    sendDocument($chat_id, "tel-$tel.json", $json);
    exit;
}

sendMessage($chat_id, "❌ *Comando inválido.*\nUse /start para ver o menu.", null, true);
?>
