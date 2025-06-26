<?php
$bot_token = "7152860548:AAFTLPfNHBksGCudquJxNQlgWgGn2r-etUs";
$api_url = "https://api.telegram.org/bot$bot_token";
$autorizados = [7926471341];

$update = json_decode(file_get_contents('php://input'), true);

// === Função enviar mensagem
function sendMessage($chat_id, $text, $buttons = null, $markdown = false) {
    global $api_url;
    $params = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => $markdown ? 'Markdown' : null
    ];
    if ($buttons) {
        $params['reply_markup'] = json_encode(['inline_keyboard' => $buttons]);
    }
    file_get_contents($api_url . "/sendMessage?" . http_build_query($params));
}

// === Função editar mensagem
function editMessage($message, $text, $buttons = null, $markdown = false) {
    global $api_url;
    $params = [
        'chat_id' => $message['chat']['id'],
        'message_id' => $message['message_id'],
        'text' => $text,
        'parse_mode' => $markdown ? 'Markdown' : null
    ];
    if ($buttons) {
        $params['reply_markup'] = json_encode(['inline_keyboard' => $buttons]);
    }
    file_get_contents($api_url . "/editMessageText?" . http_build_query($params));
}

// === CALLBACK
if (isset($update['callback_query'])) {
    $cb = $update['callback_query'];
    $chat_id = $cb['message']['chat']['id'];
    $data = $cb['data'];

    switch ($data) {
        case 'cpf':
            editMessage($cb['message'], "📌 *Consulta CPF*\n\nEnvie assim:\n`/cpf 70192822616`", [[["text" => "⬅️ Voltar", "callback_data" => "voltar"]]], true);
            break;
        case 'nome':
            editMessage($cb['message'], "📌 *Consulta Nome*\n\nEnvie assim:\n`/nome jair messias bolsonaro`", [[["text" => "⬅️ Voltar", "callback_data" => "voltar"]]], true);
            break;
        case 'tel':
            editMessage($cb['message'], "📌 *Consulta Telefone*\n\nEnvie assim:\n`/tel 31975037371`", [[["text" => "⬅️ Voltar", "callback_data" => "voltar"]]], true);
            break;
        case 'placa':
            editMessage($cb['message'], "🚗 Consulta de placa está em manutenção.", [[["text" => "⬅️ Voltar", "callback_data" => "voltar"]]]);
            break;
        case 'planos':
            editMessage($cb['message'], "💰 *Plano Vitalício*\n\n✔️ Acesso total\n💵 R\$50,00\n📩 Fale com @RibeiroDo171", [[["text" => "⬅️ Voltar", "callback_data" => "voltar"]]], true);
            break;
        case 'voltar':
            $menu = [
                [["text" => "🔍 Consultar CPF", "callback_data" => "cpf"], ["text" => "👤 Consultar Nome", "callback_data" => "nome"]],
                [["text" => "📞 Consultar Telefone", "callback_data" => "tel"], ["text" => "🚗 Consultar Placa", "callback_data" => "placa"]],
                [["text" => "💸 Planos", "callback_data" => "planos"]]
            ];
            editMessage($cb['message'], "👋 Olá! Selecione abaixo:", $menu);
            break;
    }
    exit;
}

// === MENSAGEM DE TEXTO
if (!isset($update['message'])) exit;
$msg = $update['message'];
$chat_id = $msg['chat']['id'];
$text = $msg['text'] ?? '';
$from_id = $msg['from']['id'];

if ($text === '/start') {
    $menu = [
        [["text" => "🔍 Consultar CPF", "callback_data" => "cpf"], ["text" => "👤 Consultar Nome", "callback_data" => "nome"]],
        [["text" => "📞 Consultar Telefone", "callback_data" => "tel"], ["text" => "🚗 Consultar Placa", "callback_data" => "placa"]],
        [["text" => "💸 Planos", "callback_data" => "planos"]]
    ];
    sendMessage($chat_id, "👋 *Bem-vindo!*\n\nSelecione uma opção abaixo:", $menu, true);
    exit;
}

// === NÃO AUTORIZADO
if (!in_array($from_id, $autorizados)) {
    sendMessage($chat_id, "🚫 Você não tem acesso.\n💰 Compre acesso vitalício por R\$50 com @RibeiroDo171");
    exit;
}

// === CONSULTA CPF
if (stripos($text, '/cpf ') === 0) {
    $cpf = preg_replace('/\D/', '', substr($text, 5));
    if (strlen($cpf) !== 11) {
        sendMessage($chat_id, "❌ CPF inválido.");
        exit;
    }

    $aguarde = sendMessage($chat_id, "⏳ *Aguarde, consultando dados...*", null, true);
    sleep(3);

    $api = "https://mdzapis.com/api/consultanew?base=cpf_serasa_completo&query=$cpf&apikey=Ribeiro7";
    $resp = json_decode(file_get_contents($api), true);
    $d = $resp["dados_pessoais"] ?? null;

    if (!$d) {
        sendMessage($chat_id, "❌ CPF não encontrado.");
        exit;
    }

    $msg = "*🔍 Resultado CPF*\n\n" .
        "*Nome:* {$d['nome']}\n" .
        "*Nascimento:* {$d['data_nascimento']}\n" .
        "*Sexo:* {$d['sexo']}\n" .
        "*Mãe:* {$d['nome_mae']}\n" .
        "*CPF:* {$d['cpf']}\n" .
        "*Renda:* R\$" . ($d['renda'] ?? "---") . "\n" .
        "*Classe:* " . ($resp["poder_aquisitivo"]["PODER_AQUISITIVO"] ?? "---") . "\n" .
        "*Faixa:* " . ($resp["poder_aquisitivo"]["FX_PODER_AQUISITIVO"] ?? "---") . "\n\n" .
        "🔗 [Painel do 7](https://paineldo7.rf.gd)\n`@ConsultasDo171_bot`";

    sendMessage($chat_id, $msg, null, true);
    exit;
}

// === CONSULTA NOME
if (stripos($text, '/nome ') === 0) {
    $q = urlencode(trim(substr($text, 6)));
    $api = "https://mdzapis.com/api/consultanew?base=nome_completo&query=$q&apikey=Ribeiro7";
    $res = json_decode(file_get_contents($api), true);
    $r = $res["RESULTADOS"][0] ?? null;

    if (!$r) {
        sendMessage($chat_id, "❌ Nome não encontrado.");
        exit;
    }

    $msg = "*👤 Resultado Nome*\n\n" .
        "*Nome:* {$r['NOME']}\n" .
        "*CPF:* {$r['CPF']}\n" .
        "*Nascimento:* {$r['NASC']}\n" .
        "*Mãe:* {$r['NOME_MAE']}\n" .
        "*Sexo:* {$r['SEXO']}\n\n" .
        "🔗 [Painel do 7](https://paineldo7.rf.gd)\n`@ConsultasDo171_bot`";

    sendMessage($chat_id, $msg, null, true);
    exit;
}

// === CONSULTA TELEFONE
if (stripos($text, '/tel ') === 0) {
    $q = preg_replace('/\D/', '', substr($text, 5));
    $api = "https://mdzapis.com/api/consultanew?base=consulta_telefone&query=$q&apikey=Ribeiro7";
    $res = json_decode(file_get_contents($api), true);
    $info = $res["dados"]["outrasDB"]["ASSECC"][0] ?? $res["dados"]["outrasDB"]["OPERADORA"][0] ?? null;

    if (!$info) {
        sendMessage($chat_id, "❌ Telefone não encontrado.");
        exit;
    }

    $msg = "*📞 Resultado Telefone*\n\n" .
        "*Nome:* {$info['NOME']}\n" .
        "*CPF:* " . ($info['CPF'] ?? $info['doc'] ?? "---") . "\n" .
        "*Telefone:* ({$info['DDD']}) {$info['TELEFONE']}\n" .
        "*Endereço:* {$info['ENDERECO']}, {$info['NUMERO']} - {$info['BAIRRO']}\n" .
        "*Cidade:* {$info['CIDADE']} - {$info['UF']}\n\n" .
        "🔗 [Painel do 7](https://paineldo7.rf.gd)\n`@ConsultasDo171_bot`";

    sendMessage($chat_id, $msg, null, true);
    exit;
}

// === Comando inválido
sendMessage($chat_id, "❌ Comando inválido. Use /start para o menu.");
?>
