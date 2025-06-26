<?php
$bot_token = "7152860548:AAFTLPfNHBksGCudquJxNQlgWgGn2r-etUs";
$api_url = "https://api.telegram.org/bot$bot_token";
$autorizados = [7926471341];

$update = json_decode(file_get_contents("php://input"), true);

function sendMessage($chat_id, $text, $buttons = null) {
    global $api_url;
    $params = ['chat_id' => $chat_id, 'text' => $text, 'parse_mode' => 'Markdown'];
    if ($buttons) $params['reply_markup'] = json_encode(['inline_keyboard' => $buttons]);
    file_get_contents($api_url . "/sendMessage?" . http_build_query($params));
}

function editMessage($chat_id, $msg_id, $text) {
    global $api_url;
    $params = [
        'chat_id' => $chat_id,
        'message_id' => $msg_id,
        'text' => $text,
        'parse_mode' => 'Markdown',
        'reply_markup' => json_encode(['inline_keyboard' => [[['text' => '🔗 Painel do 7', 'url' => 'https://paineldo7.rf.gd']]]])
    ];
    file_get_contents($api_url . "/editMessageText?" . http_build_query($params));
}

function startMenu() {
    return [
        [["text" => "🔍 Consultar CPF", "callback_data" => "cpf"], ["text" => "👤 Consultar Nome", "callback_data" => "nome"]],
        [["text" => "📞 Consultar Telefone", "callback_data" => "tel"], ["text" => "🚗 Consultar Placa", "callback_data" => "placa"]],
        [["text" => "💸 Planos", "callback_data" => "planos"]]
    ];
}

if (isset($update['callback_query'])) {
    $cb = $update['callback_query'];
    $msg = $cb['message'];
    $cid = $msg['chat']['id'];
    $data = $cb['data'];

    switch ($data) {
        case 'cpf':
            editMessage($cid, $msg['message_id'], "*Consulta CPF*\n\nEnvie:\n`/cpf 70192822616`");
            break;
        case 'nome':
            editMessage($cid, $msg['message_id'], "*Consulta Nome*\n\nEnvie:\n`/nome flaviane da silva`");
            break;
        case 'tel':
            editMessage($cid, $msg['message_id'], "*Consulta Telefone*\n\nEnvie:\n`/tel 31975037371`");
            break;
        case 'placa':
            editMessage($cid, $msg['message_id'], "🚗 Consulta de placa está em manutenção.");
            break;
        case 'planos':
            editMessage($cid, $msg['message_id'], "💸 *Plano vitalício:* R\$50,00\n\nAdquira com @RibeiroDo171");
            break;
    }
    exit;
}

if (!isset($update['message'])) exit;
$msg = $update['message'];
$text = $msg['text'] ?? '';
$chat_id = $msg['chat']['id'];
$from_id = $msg['from']['id'];

if (!in_array($from_id, $autorizados)) {
    sendMessage($chat_id, "🚫 *Acesso negado!*\n\nAdquira seu plano com @RibeiroDo171.");
    exit;
}

if ($text == "/start") {
    sendMessage($chat_id, "👋 *Bem-vindo!*\nSelecione a consulta abaixo:", startMenu());
    exit;
}

sendMessage($chat_id, "⏳ *Aguarde...*\nConsultando seus dados...");

// === CONSULTA CPF ===
if (strpos($text, "/cpf ") === 0) {
    $cpf = preg_replace("/\D/", "", substr($text, 5));
    $url = "https://mdzapis.com/api/consultanew?base=cpf_serasa_completo&query=$cpf&apikey=Ribeiro7";
    $resp = file_get_contents($url);
    $dados = json_decode($resp, true);

    if (!isset($dados['dados_pessoais'])) {
        sendMessage($chat_id, "❌ *CPF não encontrado.*");
        exit;
    }

    $p = $dados['dados_pessoais'];
    $score = $dados['score'] ?? [];
    $poder = $dados['poder_aquisitivo'] ?? [];
    $mosaic = $p['codigo_mosaic']['novo'] ?? [];
    $enderecos = $dados['enderecos'] ?? [];
    $parentes = $dados['parentes'] ?? [];
    $telefones = $dados['telefones'] ?? [];

    $idade = '---';
    if (!empty($p['data_nascimento'])) {
        $nasc = DateTime::createFromFormat('Y-m-d', substr($p['data_nascimento'], 0, 10));
        if ($nasc) {
            $hoje = new DateTime();
            $idade = $hoje->diff($nasc)->y . " anos";
        }
    }

    $msg = "*🔎 Consulta CPF: {$p['cpf']}*\n\n" .
        "*Nome:* {$p['nome']}\n" .
        "*Mãe:* {$p['nome_mae'] ?: '---'}\n" .
        "*Pai:* {$p['nome_pai'] ?: '---'}\n" .
        "*Nascimento:* {$p['data_nascimento']} ({$idade})\n" .
        "*Sexo:* {$p['sexo']}\n" .
        "*Nacionalidade:* {$p['nacionalidade']}\n" .
        "*Estado Civil:* {$p['estado_civil'] ?: '---'}\n" .
        "*RG:* {$p['rg'] ?: '---'}\n" .
        "*Órgão Emissor:* {$p['orgao_emissor']} - {$p['uf_emissao']}\n" .
        "*Título Eleitor:* {$p['titulo_eleitor'] ?: '---'}\n\n" .
        "*💰 Renda:* R\$ {$p['renda']}\n" .
        "*Faixa de Renda:* {$p['faixa_renda']['descricao']}\n" .
        "*Poder Aquisitivo:* {$poder['PODER_AQUISITIVO']} (R\$ {$poder['RENDA_PODER_AQUISITIVO']})\n" .
        "*Mosaic:* {$mosaic['descricao']}\n" .
        "*📊 Score:* " . ($score['CSBA'] ?? '---') . "\n\n";

    if (!empty($enderecos)) {
        $e = $enderecos[0];
        $msg .= "*📍 Endereço:*\n{$e['logradouro']}, N° {$e['numero']}, {$e['bairro']}\n{$e['cidade']} - {$e['uf']} | CEP: {$e['cep']}\n\n";
    }

    if (!empty($telefones)) {
        $msg .= "*📞 Telefones:*\n";
        foreach ($telefones as $t) {
            $msg .= "- ({$t['ddd']}) {$t['numero']}\n";
        }
        $msg .= "\n";
    }

    if (!empty($parentes)) {
        $msg .= "*👨‍👩‍👧 Parentes:*\n";
        foreach ($parentes as $parente) {
            $msg .= "- {$parente['nome']}\n";
        }
        $msg .= "\n";
    }

    $msg .= "_créditos: @ConsultasDo171_bot_";
    sendMessage($chat_id, $msg, [[['text' => '🔗 Painel do 7', 'url' => 'https://paineldo7.rf.gd']]]);
    exit;
}

// === CONSULTA NOME ===
if (strpos($text, "/nome ") === 0) {
    $nome = urlencode(substr($text, 6));
    $url = "https://mdzapis.com/api/consultanew?base=nome_completo&query=$nome&apikey=Ribeiro7";
    $resp = file_get_contents($url);
    $dados = json_decode($resp, true);
    $r = $dados['RESULTADOS'][0] ?? null;

    if (!$r) {
        sendMessage($chat_id, "❌ *Nome não encontrado.*");
        exit;
    }

    $msg = "*🔎 Consulta Nome*\n\n" .
        "*Nome:* {$r['NOME']}\n" .
        "*CPF:* {$r['CPF']}\n" .
        "*Sexo:* {$r['SEXO']}\n" .
        "*Mãe:* {$r['NOME_MAE']}\n" .
        "*Nascimento:* {$r['NASC']}\n\n" .
        "_créditos: @ConsultasDo171_bot_";

    sendMessage($chat_id, $msg, [[['text' => '🔗 Painel do 7', 'url' => 'https://paineldo7.rf.gd']]]);
    exit;
}

// === CONSULTA TELEFONE ===
if (strpos($text, "/tel ") === 0) {
    $tel = preg_replace("/\D/", "", substr($text, 5));
    $url = "https://mdzapis.com/api/consultanew?base=consulta_telefone&query=$tel&apikey=Ribeiro7";
    $resp = file_get_contents($url);
    $dados = json_decode($resp, true);
    $info = $dados['dados']['outrasDB']['OPERADORA'][0] ?? null;

    if (!$info) {
        sendMessage($chat_id, "❌ *Telefone não encontrado.*");
        exit;
    }

    $msg = "*🔎 Consulta Telefone*\n\n" .
        "*Nome:* {$info['nome']}\n" .
        "*Telefone:* ({$info['ddd']}) {$info['telefone']}\n" .
        "*Endereço:* {$info['tipo']} {$info['rua']}, {$info['numero']}\n" .
        "*Bairro:* {$info['bairro']}, {$info['cidade']} - {$info['uf']}\n" .
        "*CEP:* {$info['cep']}\n\n" .
        "_créditos: @ConsultasDo171_bot_";

    sendMessage($chat_id, $msg, [[['text' => '🔗 Painel do 7', 'url' => 'https://paineldo7.rf.gd']]]);
    exit;
}

sendMessage($chat_id, "❌ *Comando inválido.* Use /start para ver o menu.");
