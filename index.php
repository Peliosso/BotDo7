<?php
$bot_token = "7152860548:AAFTLPfNHBksGCudquJxNQlgWgGn2r-etUs";
$api_url = "https://api.telegram.org/bot$bot_token";
$usuarios_autorizados = [123456789, -1001234567890]; // ← Substitua pelos IDs reais

$update = json_decode(file_get_contents('php://input'), true);

// === CALLBACK QUERY (botões abaixo da mensagem)
if (isset($update['callback_query'])) {
    $callback = $update['callback_query'];
    $chat_id = $callback['message']['chat']['id'];
    $data = $callback['data'];

    switch ($data) {
        case 'cpf':
            sendMessage($chat_id, "📌 Envie o CPF assim:\n`/cpf 70192822616`", null, true);
            break;
        case 'nome':
            sendMessage($chat_id, "📌 Envie o nome assim:\n`/nome jair messias bolsonaro`", null, true);
            break;
        case 'tel':
            sendMessage($chat_id, "📌 Envie o telefone assim:\n`/tel 31975037371`", null, true);
            break;
        case 'placa':
            sendMessage($chat_id, "🚗 Consulta de placa está em manutenção.");
            break;
        case 'planos':
            sendMessage($chat_id, "💰 Plano único vitalício:\n\n✔️ Acesso completo\n💵 R\$50,00\n\nFale com @RibeiroDo171");
            break;
    }
    exit;
}

if (!isset($update['message'])) exit;
$msg = $update['message'];
$chat_id = $msg['chat']['id'];
$text = trim($msg['text'] ?? '');
$autorizado = in_array($chat_id, $usuarios_autorizados);

// === /start com botões INLINE
if ($text === '/start') {
    $keyboard = [
        "inline_keyboard" => [
            [
                ["text" => "🔍 Consultar CPF", "callback_data" => "cpf"],
                ["text" => "👤 Consultar Nome", "callback_data" => "nome"]
            ],
            [
                ["text" => "📞 Consultar Telefone", "callback_data" => "tel"],
                ["text" => "🚗 Consultar Placa", "callback_data" => "placa"]
            ],
            [
                ["text" => "💸 Planos", "callback_data" => "planos"]
            ]
        ]
    ];
    sendMessage($chat_id, "👋 Olá! Selecione abaixo a consulta desejada:", $keyboard);
    exit;
}

// === Não autorizado
if (!$autorizado && !in_array($text, ['/start'])) {
    sendMessage($chat_id, "🚫 Você não tem acesso ao bot.\n\n💰 Para adquirir, fale com @RibeiroDo171\nPlano vitalício: R\$50,00");
    exit;
}

// === CPF ===
if (stripos($text, '/cpf ') === 0) {
    $cpf = preg_replace('/\D/', '', substr($text, 5));
    if (strlen($cpf) !== 11) {
        sendMessage($chat_id, "❌ CPF inválido. Envie assim:\n`/cpf 70192822616`", null, true);
        exit;
    }

    $url = "https://mdzapis.com/api/consultanew?base=cpf_serasa_completo&query=$cpf&apikey=Ribeiro7";
    $resp = json_decode(file_get_contents($url), true);
    $dados = $resp["dados_pessoais"] ?? null;

    if (!$dados) {
        sendMessage($chat_id, "❌ CPF não encontrado.");
        exit;
    }

    $msg = "🔎 *Consulta CPF*\n\n" .
        "*Nome:* {$dados['nome']}\n" .
        "*Nascimento:* {$dados['data_nascimento']}\n" .
        "*Mãe:* {$dados['nome_mae']}\n" .
        "*CPF:* {$dados['cpf']}\n" .
        "*Sexo:* {$dados['sexo']}\n" .
        "*Renda:* R\$" . ($dados["renda"] ?? "---") . "\n" .
        "*Classe:* " . ($resp["poder_aquisitivo"]["PODER_AQUISITIVO"] ?? "---");

    sendMessage($chat_id, $msg, null, true);
    exit;
}

// === Nome ===
if (stripos($text, '/nome ') === 0) {
    $nome = urlencode(trim(substr($text, 6)));
    $url = "https://mdzapis.com/api/consultanew?base=nome_completo&query=$nome&apikey=Ribeiro7";
    $response = json_decode(file_get_contents($url), true);

    if (!$response || !isset($response["RESULTADOS"][0])) {
        sendMessage($chat_id, "❌ Nome não encontrado.");
        exit;
    }

    $r = $response["RESULTADOS"][0];
    $msg = "👤 *Consulta Nome*\n\n" .
        "*Nome:* {$r['NOME']}\n" .
        "*CPF:* {$r['CPF']}\n" .
        "*Sexo:* {$r['SEXO']}\n" .
        "*Mãe:* " . trim($r["NOME_MAE"]) . "\n" .
        "*Nascimento:* {$r['NASC']}";

    sendMessage($chat_id, $msg, null, true);
    exit;
}

// === Telefone ===
if (stripos($text, '/tel ') === 0) {
    $tel = preg_replace('/\D/', '', substr($text, 5));
    $url = "https://mdzapis.com/api/consultanew?base=consulta_telefone&query=$tel&apikey=Ribeiro7";
    $resp = json_decode(file_get_contents($url), true);

    $info = $resp["dados"]["outrasDB"]["ASSECC"][0] ?? $resp["dados"]["outrasDB"]["OPERADORA"][0] ?? null;
    if (!$info) {
        sendMessage($chat_id, "❌ Telefone não encontrado.");
        exit;
    }

    $msg = "📞 *Consulta Telefone*\n\n" .
        "*Nome:* {$info['NOME']}\n" .
        "*CPF:* " . ($info['CPF'] ?? $info['doc'] ?? "---") . "\n" .
        "*Telefone:* ({$info['DDD']}) {$info['TELEFONE']}\n" .
        "*Endereço:* {$info['ENDERECO']}, {$info['NUMERO']} - {$info['BAIRRO']}\n" .
        "*Cidade:* {$info['CIDADE']} - {$info['UF']}";

    sendMessage($chat_id, $msg, null, true);
    exit;
}

// === Fallback
sendMessage($chat_id, "❌ Comando inválido. Use /start para ver o menu.");

// === Enviar mensagem
function sendMessage($chat_id, $text, $keyboard = null, $markdown = false) {
    global $api_url;
    $params = [
        "chat_id" => $chat_id,
        "text" => $text,
        "parse_mode" => $markdown ? "Markdown" : null
    ];
    if ($keyboard) {
        $params["reply_markup"] = json_encode($keyboard);
    }
    file_get_contents($api_url . "/sendMessage?" . http_build_query($params));
}
?>
