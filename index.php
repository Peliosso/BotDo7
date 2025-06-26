<?php
$bot_token = "7152860548:AAFTLPfNHBksGCudquJxNQlgWgGn2r-etUs";
$api_url = "https://api.telegram.org/bot$bot_token";
$autorizados = [7926471341];
$update = json_decode(file_get_contents("php://input"), true);

// Funções utilitárias
function sendMessage($id, $txt, $buttons = null, $md = true) {
    global $api_url;
    $data = [
        'chat_id' => $id,
        'text' => $txt,
        'parse_mode' => $md ? 'Markdown' : null
    ];
    if ($buttons) $data['reply_markup'] = json_encode(['inline_keyboard' => $buttons]);
    return json_decode(file_get_contents($api_url . "/sendMessage?" . http_build_query($data)), true);
}
function editMessage($chat_id, $msg_id, $txt, $buttons = null, $md = true) {
    global $api_url;
    $data = [
        'chat_id' => $chat_id,
        'message_id' => $msg_id,
        'text' => $txt,
        'parse_mode' => $md ? 'Markdown' : null
    ];
    if ($buttons) $data['reply_markup'] = json_encode(['inline_keyboard' => $buttons]);
    file_get_contents($api_url . "/editMessageText?" . http_build_query($data));
}
function deleteMessage($id, $msg_id) {
    global $api_url;
    file_get_contents($api_url . "/deleteMessage?chat_id=$id&message_id=$msg_id");
}

// Botões padrão
$botao_voltar = [[["text" => "⬅️ Voltar", "callback_data" => "voltar"]]];
$botao_painel = [[["text" => "🔗 Painel do 7", "url" => "https://paineldo7.rf.gd"]], [["text" => "🗑 Apagar", "callback_data" => "apagar"]]];
$menu = [
    [["text" => "🔍 Consultar CPF", "callback_data" => "cpf"], ["text" => "👤 Consultar Nome", "callback_data" => "nome"]],
    [["text" => "📞 Consultar Telefone", "callback_data" => "tel"], ["text" => "🚗 Consultar Placa", "callback_data" => "placa"]],
    [["text" => "💸 Planos", "callback_data" => "planos"]]
];

// === CALLBACK ===
if (isset($update['callback_query'])) {
    $cb = $update['callback_query'];
    $id = $cb['message']['chat']['id'];
    $mid = $cb['message']['message_id'];
    $d = $cb['data'];

    switch ($d) {
        case "cpf": editMessage($id, $mid, "📌 *Consulta CPF*\n\nEnvie assim:\n`/cpf 28536726890`", $botao_voltar); break;
        case "nome": editMessage($id, $mid, "📌 *Consulta Nome*\n\nEnvie assim:\n`/nome jair messias bolsonaro`", $botao_voltar); break;
        case "tel": editMessage($id, $mid, "📌 *Consulta Telefone*\n\nEnvie assim:\n`/tel 31975037371`", $botao_voltar); break;
        case "placa": editMessage($id, $mid, "🚧 Consulta de placa está em manutenção", $botao_voltar); break;
        case "planos": editMessage($id, $mid, "💸 *Plano Vitalício*\n\n✔️ Acesso total\n💵 R\$50,00\n📩 Fale com @RibeiroDo171", $botao_voltar); break;
        case "voltar": editMessage($id, $mid, "👋 *Bem-vindo ao Consultas do 7*\n\nSelecione abaixo:", $menu); break;
        case "apagar": deleteMessage($id, $mid); break;
    }
    exit;
}

// === MENSAGEM ===
if (!isset($update['message'])) exit;
$msg = $update['message'];
$texto = $msg['text'] ?? '';
$cid = $msg['chat']['id'];
$uid = $msg['from']['id'];

if ($texto === "/start") {
    sendMessage($cid, "👋 *Bem-vindo ao Consultas do 7*\n\nEscolha uma opção abaixo:", $menu);
    exit;
}

// === AUTORIZAÇÃO ===
if (!in_array($uid, $autorizados)) {
    sendMessage($cid, "🚫 *Você não tem acesso a este bot.*\n\n💰 Plano vitalício R\$50\n📩 Fale com @RibeiroDo171", null, true);
    exit;
}

// === CONSULTA CPF ===
if (stripos($texto, "/cpf ") === 0) {
    $cpf = preg_replace("/\D/", "", substr($texto, 5));
    if (strlen($cpf) !== 11) {
        sendMessage($cid, "❌ CPF inválido.");
        exit;
    }

    $aguarde = sendMessage($cid, "⏳ *Consultando CPF... Aguarde alguns segundos.*");
    sleep(4);

    $res = json_decode(file_get_contents("https://mdzapis.com/api/consultanew?base=cpf_serasa_completo&query=$cpf&apikey=Ribeiro7"), true);
    $d = $res['dados_pessoais'] ?? null;
    if (!$d) {
        editMessage($cid, $aguarde['result']['message_id'], "❌ CPF não encontrado.");
        exit;
    }

    $telefones = $res['telefones'] ?? [];
    $enderecos = $res['enderecos'] ?? [];
    $emails = implode(", ", $res['emails'] ?? []);
    $parentes = $res['parentes'][0]['NOME_VINCULO'] ?? "---";
    $pis = $res['pis']['PIS'] ?? "---";
    $score = $res['score']['CSBA'] ?? "---";
    $poder = $res['poder_aquisitivo']['PODER_AQUISITIVO'] ?? "---";

    $txt = "*🔍 Resultado da Consulta CPF:*\n\n" .
           "*👤 Nome:* {$d['nome']}\n" .
           "*📅 Nascimento:* {$d['data_nascimento']}\n" .
           "*📄 CPF:* {$d['cpf']}\n" .
           "*🧬 Sexo:* {$d['sexo']}\n" .
           "*👩 Mãe:* {$d['nome_mae']}\n" .
           "*🆔 RG:* {$d['rg']} ({$d['orgao_emissor']})\n" .
           "*🗳 Título Eleitor:* {$d['titulo_eleitor']}\n" .
           "*📈 Score:* {$score}\n" .
           "*💸 Renda:* R\$ {$d['renda']}\n" .
           "*🏷 Classe:* {$poder}\n" .
           "*📧 Emails:* {$emails}\n" .
           "*📞 Telefones:* " . implode(", ", array_map(fn($t) => "({$t['DDD']}) {$t['TELEFONE']}", $telefones)) . "\n" .
           "*🏠 Endereços:* " . implode(", ", array_map(fn($e) => "{$e['LOGR_NOME']} {$e['LOGR_NUMERO']} - {$e['BAIRRO']}", $enderecos)) . "\n\n" .
           "_créditos: @ConsultasDo171_bot_";

    editMessage($cid, $aguarde['result']['message_id'], $txt, $botao_painel);
    exit;
}

// === CONSULTA NOME ===
if (stripos($texto, "/nome ") === 0) {
    $q = urlencode(substr($texto, 6));
    $aguarde = sendMessage($cid, "⏳ *Consultando Nome...*");

    $res = json_decode(file_get_contents("https://mdzapis.com/api/consultanew?base=nome_completo&query=$q&apikey=Ribeiro7"), true);
    $r = $res["RESULTADOS"][0] ?? null;
    if (!$r) {
        editMessage($cid, $aguarde['result']['message_id'], "❌ Nome não encontrado.");
        exit;
    }

    $txt = "*👤 Resultado da Consulta Nome:*\n\n" .
           "*Nome:* {$r['NOME']}\n*CPF:* {$r['CPF']}\n*Mãe:* {$r['NOME_MAE']}\n*Nascimento:* {$r['NASC']}\n\n_créditos: @ConsultasDo171_bot_";

    editMessage($cid, $aguarde['result']['message_id'], $txt, $botao_painel);
    exit;
}

// === CONSULTA TELEFONE ===
if (stripos($texto, "/tel ") === 0) {
    $tel = preg_replace('/\D/', '', substr($texto, 5));
    $aguarde = sendMessage($cid, "⏳ *Consultando Telefone...*");

    $res = json_decode(file_get_contents("https://mdzapis.com/api/consultanew?base=consulta_telefone&query=$tel&apikey=Ribeiro7"), true);
    $info = $res["dados"]["outrasDB"]["ASSECC"][0] ?? $res["dados"]["outrasDB"]["OPERADORA"][0] ?? null;
    if (!$info) {
        editMessage($cid, $aguarde['result']['message_id'], "❌ Telefone não encontrado.");
        exit;
    }

    $txt = "*📞 Resultado da Consulta Telefone:*\n\n" .
           "*Nome:* {$info['NOME']}\n*CPF:* " . ($info['CPF'] ?? $info['doc']) . "\n*Telefone:* ({$info['DDD']}) {$info['TELEFONE']}\n" .
           "*Endereço:* {$info['ENDERECO']}, {$info['NUMERO']} - {$info['BAIRRO']} - {$info['CIDADE']}/{$info['UF']}\n\n" .
           "_créditos: @ConsultasDo171_bot_";

    editMessage($cid, $aguarde['result']['message_id'], $txt, $botao_painel);
    exit;
}

sendMessage($cid, "❌ *Comando inválido.*\nUse /start para abrir o menu.", null, true);
?>
