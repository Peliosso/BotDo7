<?php

// === CONFIGURAÇÕES DO BOT ===
$token = '7152860548:AAFTLPfNHBksGCudquJxNQlgWgGn2r-etUs';
define('API_URL', "https://api.telegram.org/bot$token/");
$input = file_get_contents("php://input");
$update = json_decode($input, true);

$chat_id = $update['message']['chat']['id'] ?? $update['callback_query']['message']['chat']['id'] ?? null;
$message_id = $update['message']['message_id'] ?? $update['callback_query']['message']['message_id'] ?? null;
$text = $update['message']['text'] ?? $update['callback_query']['data'] ?? null;
$name = $update['message']['from']['first_name'] ?? $update['callback_query']['from']['first_name'] ?? null;

function sendMessage($chat_id, $text, $buttons = null, $parse = 'Markdown') {
    $data = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => $parse,
    ];
    if ($buttons) $data['reply_markup'] = json_encode(['inline_keyboard' => $buttons]);
    file_get_contents(API_URL . "sendMessage?" . http_build_query($data));
}

function editMessage($chat_id, $message_id, $text, $buttons = null, $parse = 'Markdown') {
    $data = [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $text,
        'parse_mode' => $parse,
    ];
    if ($buttons) $data['reply_markup'] = json_encode(['inline_keyboard' => $buttons]);
    file_get_contents(API_URL . "editMessageText?" . http_build_query($data));
}

// === COMANDOS ===
if ($text === "/start") {
    sendMessage($chat_id, "👋 Olá *$name*, seja bem-vindo ao *Consultas do 7*!\n\nUse /menu para ver os recursos disponíveis.");
}

elseif ($text === "/menu" || $text === "menu") {
    $msg = "📋 *Menu de Consultas*\n\nEscolha uma das opções abaixo:";
    $botoes = [
        [['text' => '🔍 Como usar /cpf', 'callback_data' => 'como_cpf']],
        [['text' => '👤 Como usar /nome', 'callback_data' => 'como_nome']],
        [['text' => '💳 Planos de acesso', 'callback_data' => 'planos']]
    ];
    sendMessage($chat_id, $msg, $botoes);
}

elseif ($text === "como_cpf") {
    editMessage($chat_id, $message_id, "🔍 *Como usar o /cpf*\n\nDigite assim:\n`/cpf 28536726890`\n\nVocê receberá nome, nascimento, endereço, telefones, parentes, score e muito mais.");
}

elseif ($text === "como_nome") {
    editMessage($chat_id, $message_id, "👤 *Como usar o /nome*\n\nDigite assim:\n`/nome ana luiza santos`\n\nVocê verá todos os CPFs vinculados a esse nome.");
}

elseif ($text === "planos") {
    $txt = "💳 *Planos de acesso*\n\n✅ Diário: *R$ 5*\n✅ Semanal: *R$ 15*\n✅ Mensal: *R$ 30*\n\n📲 Comprar com: @RibeiroDo171";
    editMessage($chat_id, $message_id, $txt);
}

elseif (preg_match("/\/cpf (\d{11})/", $text, $m)) {
    $cpf = $m[1];
    $msgAguarde = sendMessage($chat_id, "⏳ *Consultando CPF... aguarde*");

    $resposta = json_decode(file_get_contents("https://mdzapis.com/api/consultanew?base=cpf_serasa_completo&query=$cpf&apikey=Ribeiro7"), true);
    $dados = $resposta['dados_pessoais'];

    $nome = $dados['nome'] ?? 'Desconhecido';
    $mae = $dados['nome_mae'] ?? '-';
    $sexo = $dados['sexo'] ?? '-';
    $nasc = $dados['data_nascimento'] ?? '-';
    $renda = $dados['renda'] ?? '-';
    $score = $resposta['score']['CSBA'] ?? '-';
    $faixa = $resposta['score']['CSBA_FAIXA'] ?? '-';

    $telefones = $resposta['telefones'] ?? [];
    $listaTels = '';
    foreach ($telefones as $tel) {
        $listaTels .= "📞 {$tel['DDD']}-{$tel['TELEFONE']}\n";
    }

    $enderecos = $resposta['enderecos'] ?? [];
    $listaEnds = '';
    foreach ($enderecos as $end) {
        $listaEnds .= "🏠 {$end['LOGR_NOME']}, {$end['LOGR_NUMERO']} - {$end['BAIRRO']}, {$end['CIDADE']}/{$end['UF']}\n";
    }

    $parentes = $resposta['parentes'][0]['NOME_VINCULO'] ?? '-';

    $mensagem = "*🔍 Resultado da Consulta CPF*\n\n" .
                "*👤 Nome:* `$nome`\n" .
                "*👩 Mãe:* `$mae`\n" .
                "*📆 Nasc:* `$nasc`\n" .
                "*📈 Score:* `$score` ($faixa)\n" .
                "*💰 Renda:* R$ $renda\n" .
                "*👪 Parente:* $parentes\n\n" .
                "*📍 Endereços:*\n$listaEnds\n" .
                "*📞 Telefones:*\n$listaTels\n\n" .
                "_Créditos: @ConsultasDo171_bot_";

    editMessage($chat_id, $msgAguarde['result']['message_id'], $mensagem, [
        [['text' => '🌐 Painel do 7', 'url' => 'https://paineldo7.rf.gd']],
        [['text' => '🗑 Apagar', 'callback_data' => 'apagar']]
    ]);
}

elseif (preg_match("/\/nome (.+)/", $text, $m)) {
    $nome = urlencode($m[1]);
    $msgAguarde = sendMessage($chat_id, "⏳ *Consultando nome... aguarde*");

    $resposta = json_decode(file_get_contents("https://mdzapis.com/api/consultanew?base=nome_abreviado&query=$nome&apikey=Ribeiro7"), true);
    $lista = $resposta['RESULTADOS'] ?? [];

    if (count($lista) === 0) {
        editMessage($chat_id, $msgAguarde['result']['message_id'], "❌ Nenhum resultado encontrado para esse nome.");
        exit;
    }

    $textinho = "*🔍 Resultados encontrados:*\n\n";
    foreach ($lista as $pessoa) {
        $n = $pessoa['NOME'] ?? '-';
        $cpf = $pessoa['CPF'] ?? '-';
        $nasc = $pessoa['NASC'] ?? '-';
        $textinho .= "*👤 Nome:* `$n`\n*🔢 CPF:* `$cpf`\n📅 *Nascimento:* `$nasc`\n\n";
    }

    editMessage($chat_id, $msgAguarde['result']['message_id'], $textinho, [
        [['text' => '🌐 Painel do 7', 'url' => 'https://paineldo7.rf.gd']],
        [['text' => '🗑 Apagar', 'callback_data' => 'apagar']]
    ]);
}

elseif ($text === "apagar") {
    file_get_contents(API_URL . "deleteMessage?chat_id=$chat_id&message_id=$message_id");
}
