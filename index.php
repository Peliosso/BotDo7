<?php
$bot_token = "7152860548:AAFTLPfNHBksGCudquJxNQlgWgGn2r-etUs";
$api_url = "https://api.telegram.org/bot$bot_token";

// IDs autorizados (pessoas ou grupos)
$usuarios_autorizados = [123456789, -1001234567890]; // coloque aqui IDs reais

// Lê a mensagem do Telegram (JSON)
$update = json_decode(file_get_contents('php://input'), true);
if (!$update || !isset($update['message'])) exit;

$msg = $update['message'];
$chat_id = $msg['chat']['id'];
$text = trim($msg['text'] ?? '');

// === Autorização ===
$autorizado = in_array($chat_id, $usuarios_autorizados);

// === /start ===
if ($text == '/start') {
    $keyboard = [
        "keyboard" => [
            [["text" => "🔍 Consultar CPF"], ["text" => "👤 Consultar Nome"]],
            [["text" => "📞 Consultar Telefone"], ["text" => "🚗 Consultar Placa"]],
            [["text" => "💸 Planos"]]
        ],
        "resize_keyboard" => true,
        "one_time_keyboard" => false
    ];
    sendMessage($chat_id, "👋 Olá! Selecione abaixo a consulta desejada:", $keyboard);
    exit;
}

// === Mensagem para não autorizados ===
if (!$autorizado && !in_array($text, ['/start', '💸 Planos'])) {
    sendMessage($chat_id, "🚫 Você não tem acesso a este bot.\n\n💰 Para adquirir, fale com @RibeiroDo171\nPlano: R\$50,00 (vitalício)");
    exit;
}

// === Planos ===
if ($text == '💸 Planos') {
    sendMessage($chat_id, "💰 Plano único vitalício disponível:\n\n✔️ Acesso total a todas as consultas\n💵 Valor: R\$50,00\n\nEntre em contato com @RibeiroDo171 para ativação.");
    exit;
}

// === Consultar CPF ===
if (stripos($text, '/cpf ') === 0 || $text == '🔍 Consultar CPF') {
    if ($text == '🔍 Consultar CPF') {
        sendMessage($chat_id, "📌 Envie o CPF no formato:\n`/cpf 70192822616`", null, true);
        exit;
    }

    $cpf = preg_replace('/\D/', '', substr($text, 5));
    if (strlen($cpf) !== 11) {
        sendMessage($chat_id, "❌ CPF inválido. Envie assim: `/cpf 70192822616`", null, true);
        exit;
    }

    $url = "https://mdzapis.com/api/consultanew?base=cpf_serasa_completo&query=$cpf&apikey=Ribeiro7";
    $resp = json_decode(file_get_contents($url), true);
    $dados = $resp["dados_pessoais"] ?? null;

    if (!$dados) {
        sendMessage($chat_id, "❌ CPF não encontrado ou resposta inválida.");
        exit;
    }

    $msg = "🔎 *Resultado CPF*\n\n" .
        "*Nome:* " . ($dados["nome"] ?? "---") . "\n" .
        "*Nascimento:* " . ($dados["data_nascimento"] ?? "---") . "\n" .
        "*Mãe:* " . ($dados["nome_mae"] ?? "---") . "\n" .
        "*CPF:* " . ($dados["cpf"] ?? "---") . "\n" .
        "*Sexo:* " . ($dados["sexo"] ?? "---") . "\n" .
        "*Nacionalidade:* " . ($dados["nacionalidade"] ?? "---") . "\n" .
        "*Renda:* R$" . ($dados["renda"] ?? "---") . "\n" .
        "*Classe:* " . ($resp["poder_aquisitivo"]["PODER_AQUISITIVO"] ?? "---");

    sendMessage($chat_id, $msg, null, true);
    exit;
}

// === Consultar Nome ===
if (stripos($text, '/nome ') === 0 || $text == '👤 Consultar Nome') {
    if ($text == '👤 Consultar Nome') {
        sendMessage($chat_id, "📌 Envie o nome no formato:\n`/nome jair messias bolsonaro`", null, true);
        exit;
    }

    $nome = urlencode(trim(substr($text, 6)));
    $url = "https://mdzapis.com/api/consultanew?base=nome_completo&query=$nome&apikey=Ribeiro7";
    $response = json_decode(file_get_contents($url), true);

    if (!$response || !isset($response["RESULTADOS"][0])) {
        sendMessage($chat_id, "❌ Nome não encontrado.");
        exit;
    }

    $r = $response["RESULTADOS"][0];
    $msg = "👤 *Consulta por Nome*\n\n" .
        "*Nome:* " . trim($r["NOME"]) . "\n" .
        "*CPF:* " . $r["CPF"] . "\n" .
        "*Sexo:* " . $r["SEXO"] . "\n" .
        "*Mãe:* " . trim($r["NOME_MAE"]) . "\n" .
        "*Nascimento:* " . $r["NASC"];

    sendMessage($chat_id, $msg, null, true);
    exit;
}

// === Consultar Telefone ===
if (stripos($text, '/tel ') === 0 || $text == '📞 Consultar Telefone') {
    if ($text == '📞 Consultar Telefone') {
        sendMessage($chat_id, "📌 Envie o telefone no formato:\n`/tel 31975037371`", null, true);
        exit;
    }

    $telefone = preg_replace('/\D/', '', substr($text, 5));
    $url = "https://mdzapis.com/api/consultanew?base=consulta_telefone&query=$telefone&apikey=Ribeiro7";
    $resp = json_decode(file_get_contents($url), true);

    $info = $resp["dados"]["outrasDB"]["ASSECC"][0] ?? null;
    if (!$info) $info = $resp["dados"]["outrasDB"]["OPERADORA"][0] ?? null;

    if (!$info) {
        sendMessage($chat_id, "❌ Telefone não encontrado.");
        exit;
    }

    $msg = "📞 *Resultado Telefone*\n\n" .
        "*Nome:* " . $info["NOME"] . "\n" .
        "*CPF:* " . ($info["CPF"] ?? $info["doc"] ?? "---") . "\n" .
        "*Telefone:* (" . $info["DDD"] . ") " . $info["TELEFONE"] . "\n" .
        "*Endereço:* " . $info["ENDERECO"] . ", " . $info["NUMERO"] . " - " . $info["BAIRRO"] . "\n" .
        "*Cidade:* " . $info["CIDADE"] . " - " . $info["UF"];

    sendMessage($chat_id, $msg, null, true);
    exit;
}

// === Placa (em manutenção) ===
if ($text == '🚗 Consultar Placa') {
    sendMessage($chat_id, "🚗 Consulta de placa está em manutenção no momento.");
    exit;
}

// === Fallback
sendMessage($chat_id, "❌ Comando não reconhecido. Use /start para ver o menu.");

// === Função para enviar mensagem
function sendMessage($chat_id, $text, $keyboard = null, $markdown = false) {
    global $api_url;
    $params = [
        "chat_id" => $chat_id,
        "text" => $text,
        "parse_mode" => $markdown ? "Markdown" : null
    ];
    if ($keyboard) $params["reply_markup"] = json_encode($keyboard);
    file_get_contents($api_url . "/sendMessage?" . http_build_query($params));
}
?>
