<?php

$input = file_get_contents('php://input');
$update = json_decode($input);

// Dados da mensagem
$message = $update->message ?? null;
$data = $update->callback_query->data ?? null;

// Identificadores
$chat_id = $message->chat->id ?? null;
$message_id = $message->message_id ?? null;
$texto = $message->text ?? null;
$nome = $message->from->first_name ?? '';
$query_id = $update->callback_query->id ?? '';
$query_chat_id = $update->callback_query->message->chat->id ?? null;
$query_message_id = $update->callback_query->message->message_id ?? null;
$query_nome = $update->callback_query->message->chat->first_name ?? '';

// Função principal para interagir com a API do Telegram
function bot($method, $parameters) {
    $token = "7152860548:AAFTLPfNHBksGCudquJxNQlgWgGn2r-etUs";
    $options = [
        'http' => [
            'method'  => 'POST',
            'content' => json_encode($parameters),
            'header'  => "Content-Type: application/json\r\nAccept: application/json\r\n"
        ]
    ];
    $context = stream_context_create($options);
    return file_get_contents("https://api.telegram.org/bot$token/$method", false, $context);
}

function start($dados) {
    $chat_id = $dados['chat_id'];
    $message_id = $dados['message_id'];
    $nome = $dados['nome'];

    $txt = "🔹 *Bem-vindo {$nome}*\n\n• [Grupo - Oficial](https://t.me/MetodosDo7Gratis)\n\n_Navegue pelo menu abaixo:_";

    $button[] = ['text' => "Consultas", 'callback_data' => "consultas"];
    $button[] = ['text' => "Tabela", 'callback_data' => "tabela"];
    $button[] = ['text' => "Suporte / Dev", 'url' => "https://t.me/RibeiroDo171"];

    $menu['inline_keyboard'] = array_chunk($button, 2);

    bot("sendMessage", [
        "chat_id" => $chat_id,
        "text" => $txt,
        "reply_markup" => $menu,
        "parse_mode" => 'Markdown'
    ]);
}

function consultas($dados) {
    $chat_id = $dados["chat_id"];
    $message_id = $dados["query_message_id"];

    $txt = "*☆ | COMANDOS BOT Consultas do 7 | ☆*\n\n*● | STATUS 》 ONLINE*\n\n*• [CPF (1)] •*\n🟢 *CPF1:* /cpf 28536726890\n\n⚡️ Use os comandos em Grupos e no Privado do Robô\n👤 *Suporte: @RibeiroDo171*";

    $button[] = ['text'=>"Voltar", "callback_data" => "start"];
    $menu['inline_keyboard'] = array_chunk($button, 2);

    bot("editMessageText", [
        "chat_id" => $chat_id,
        "text" => $txt,
        "message_id" => $message_id,
        "reply_markup" => $menu,
        "parse_mode" => 'Markdown'
    ]);
}

function tabela($dados) {
    $chat_id = $dados["chat_id"];
    $message_id = $dados["query_message_id"];

    $txt = "*🕵️ PLANO INDIVIDUAL*\n\n*💰 PREÇOS:*\n*1 SEMANA = R$100,00*\n\n⚠ *Apenas no privado com o bot!*";

    $button[] = ['text'=>"1 SEMANA", "callback_data" => "kkk"];
    $button[] = ['text'=>"Voltar", "callback_data" => "start"];
    $menu['inline_keyboard'] = array_chunk($button, 2);

    bot("editMessageText", [
        "chat_id" => $chat_id,
        "text" => $txt,
        "message_id" => $message_id,
        "reply_markup" => $menu,
        "parse_mode" => 'Markdown'
    ]);
}

// Resposta ao comando /start
if (isset($texto) && strpos($texto, "/start") === 0) {
    start([
        "chat_id" => $chat_id,
        "message_id" => $message_id,
        "nome" => $nome
    ]);
}

// Resposta ao comando /cpf
if (isset($texto) && strpos($texto, "/cpf") === 0) {
    $partes = explode(" ", $texto);
    if (isset($partes[1])) {
        $cpf = preg_replace("/[^0-9]/", "", $partes[1]);
        $apiUrl = "https://mdzapis.com/api/consultanew?base=cpf_serasa_completo&query={$cpf}&apikey=Ribeiro7";
        $resposta = file_get_contents($apiUrl);
        $dados = json_decode($resposta, true);

        if (isset($dados["dados_pessoais"]["nome"])) {
            $nome = $dados["dados_pessoais"]["nome"];
            $mae = $dados["dados_pessoais"]["nome_mae"];
            $nasc = $dados["dados_pessoais"]["data_nascimento"];
            $sexo = $dados["dados_pessoais"]["sexo"];
            $txt = "*🔍 Resultado para CPF:* `$cpf`\n\n👤 *Nome:* $nome\n👩‍👧 *Mãe:* $mae\n📅 *Nascimento:* $nasc\n⚧️ *Sexo:* $sexo";
        } else {
            $txt = "❌ CPF não encontrado.";
        }
    } else {
        $txt = "⚠️ Use corretamente: /cpf 00000000000";
    }

    bot("sendMessage", [
        "chat_id" => $chat_id,
        "text" => $txt,
        "parse_mode" => "Markdown"
    ]);
}

// Resposta a callback buttons
if (isset($data)) {
    $callback = explode("|", $data)[0];
    $dados = [
        "chat_id" => $query_chat_id,
        "query_message_id" => $query_message_id,
        "query_id" => $query_id,
        "nome" => $query_nome
    ];

    if (function_exists($callback)) {
        $callback($dados);
    } else {
        bot("answerCallbackQuery", [
            "callback_query_id" => $query_id,
            "text" => "⚠️ Em desenvolvimento...",
            "show_alert" => false
        ]);
    }
}
?>
