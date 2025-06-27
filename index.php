<?php

$input = file_get_contents('php://input');
$update = json_decode($input);

$message = $update->message ?? null;
$data = $update->callback_query->data ?? null;

$chat_id = $message->chat->id ?? null;
$message_id = $message->message_id ?? null;
$texto = $message->text ?? null;
$nome = $message->from->first_name ?? '';
$query_id = $update->callback_query->id ?? '';
$query_chat_id = $update->callback_query->message->chat->id ?? null;
$query_message_id = $update->callback_query->message->message_id ?? null;
$query_nome = $update->callback_query->message->chat->first_name ?? '';

function bot($method, $parameters) {
    $token = "SEU_TOKEN_AQUI"; // Coloque seu token aqui
    $options = [
        'http' => [
            'method'  => 'POST',
            'content' => json_encode($parameters),
            'header'  => "Content-Type: application/json\r\n"
        ]
    ];
    $context = stream_context_create($options);
    return file_get_contents("https://api.telegram.org/bot$token/$method", false, $context);
}

function start($dados) {
    $chat_id = $dados['chat_id'];
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

    $txt = "*☆ | COMANDOS BOT Consultas do 7 | ☆*\n\n*● | STATUS 》 ONLINE*\n\n*• [CPF (1)] •*\n🟢 *CPF1:* /cpf 28536726890\n\n*• [NOME] •*\n🔵 *Nome:* /nome Ewerton da Silva Ribeiro\n\n⚡️ Use os comandos em Grupos e no Privado do Robô\n👤 *Suporte: @RibeiroDo171*";

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

function nf($dado) {
    return empty($dado) ? "_Não encontrado._" : $dado;
}

if (isset($texto) && strpos($texto, "/start") === 0) {
    start([
        "chat_id" => $chat_id,
        "nome" => $nome
    ]);
}

if (isset($texto) && strpos($texto, "/cpf") === 0) {
    $partes = explode(" ", $texto);
    if (isset($partes[1])) {
        $cpf = preg_replace("/[^0-9]/", "", $partes[1]);

        $aguarde = bot("sendMessage", [
            "chat_id" => $chat_id,
            "text" => "⏳ Aguarde, consultando CPF `$cpf`...",
            "parse_mode" => "Markdown"
        ]);
        $aguarde = json_decode($aguarde, true);
        $msg_id_aguarde = $aguarde['result']['message_id'];

        $apiUrl = "https://mdzapis.com/api/consultanew?base=cpf_serasa_completo&query={$cpf}&apikey=Ribeiro7";
        $resposta = file_get_contents($apiUrl);
        $dados = json_decode($resposta, true);

        if (isset($dados["dados_pessoais"]["nome"])) {
            $info = $dados["dados_pessoais"];

            $txt = "*🔍 Resultado para CPF:* `$cpf`\n\n";
            $txt .= "👤 *Nome:* " . nf($info["nome"] ?? "") . "\n";
            $txt .= "👩‍👧 *Mãe:* " . nf($info["nome_mae"] ?? "") . "\n";
            $txt .= "📅 *Nascimento:* " . nf($info["data_nascimento"] ?? "") . "\n";
            $txt .= "⚧️ *Sexo:* " . nf($info["sexo"] ?? "") . "\n";
            $txt .= "🪪 *RG:* " . nf($info["rg"] ?? "") . "\n";
            $txt .= "🗳️ *Título Eleitor:* " . nf($info["titulo_eleitor"] ?? "") . "\n";
            $txt .= "🇧🇷 *Nacionalidade:* " . nf($info["nacionalidade"] ?? "") . "\n";
            $txt .= "💸 *Renda:* R$ " . nf($info["renda"] ?? "") . "\n\n";

            // Endereços
            $enderecos = "";
            if (!empty($dados["enderecos"])) {
                foreach ($dados["enderecos"] as $end) {
                    $enderecos .= "🏠 {$end["LOGR_TIPO"]} {$end["LOGR_NOME"]}, {$end["LOGR_NUMERO"]} - {$end["BAIRRO"]}, {$end["CIDADE"]} - {$end["UF"]}\n";
                }
            } else {
                $enderecos = "_Nenhum endereço encontrado._";
            }

            // Emails
            $emails_array = $dados["emails"] ?? [];
            $emails = count($emails_array) > 0 ? implode(", ", $emails_array) : "_Nenhum email encontrado._";

            // Telefones
            $telefones = "";
            if (!empty($dados["telefones"])) {
                foreach ($dados["telefones"] as $tel) {
                    $telefones .= "📞 ({$tel["DDD"]}) {$tel["TELEFONE"]}\n";
                }
            } else {
                $telefones = "_Nenhum telefone encontrado._";
            }

            $txt .= "📬 *Endereços:*\n$enderecos\n";
            $txt .= "📧 *Emails:*\n$emails\n\n";
            $txt .= "📱 *Telefones:*\n$telefones";

            $botoes['inline_keyboard'] = [
                [
                    ['text' => '❌ Apagar', 'callback_data' => 'apagar'],
                    ['text' => 'Painel do 7', 'url' => 'https://paineldo7.rf.gd']
                ]
            ];

            bot("editMessageText", [
                "chat_id" => $chat_id,
                "message_id" => $msg_id_aguarde,
                "text" => $txt,
                "reply_markup" => $botoes,
                "parse_mode" => "Markdown"
            ]);
        } else {
            bot("editMessageText", [
                "chat_id" => $chat_id,
                "message_id" => $msg_id_aguarde,
                "text" => "❌ Nenhum dado encontrado para o CPF informado.",
                "parse_mode" => "Markdown"
            ]);
        }
    } else {
        bot("sendMessage", [
            "chat_id" => $chat_id,
            "text" => "⚠️ Use corretamente: /cpf 00000000000",
            "parse_mode" => "Markdown"
        ]);
    }
}

if (isset($texto) && strpos($texto, "/nome") === 0) {
    $partes = explode(" ", $texto, 2);
    if (isset($partes[1])) {
        $nomeBusca = urlencode($partes[1]);

        $aguarde = bot("sendMessage", [
            "chat_id" => $chat_id,
            "text" => "⏳ Consultando o nome `{$partes[1]}`...",
            "parse_mode" => "Markdown"
        ]);
        $aguarde = json_decode($aguarde, true);
        $msg_id_aguarde = $aguarde['result']['message_id'];

        $apiUrl = "https://mdzapis.com/api/consultanew?base=nome_abreviado&query={$nomeBusca}&apikey=Ribeiro7";
        $resposta = file_get_contents($apiUrl);
        $dados = json_decode($resposta, true);

        if (!empty($dados["RESULTADOS"])) {
            $txt = "*🔍 Resultados encontrados para:* `{$partes[1]}`\n\n";

            foreach ($dados["RESULTADOS"] as $pessoa) {
                $txt .= "👤 *Nome:* " . nf($pessoa["NOME"]) . "\n";
                $txt .= "🪪 *CPF:* " . nf($pessoa["CPF"]) . "\n";
                $txt .= "👩‍👧 *Mãe:* " . nf($pessoa["NOME_MAE"]) . "\n";
                $txt .= "📅 *Nascimento:* " . nf(substr($pessoa["NASC"], 0, 10)) . "\n";
                $txt .= "⚧️ *Sexo:* " . nf($pessoa["SEXO"]) . "\n";
                $txt .= str_repeat("━", 30) . "\n";
            }

            $botoes['inline_keyboard'] = [
                [
                    ['text' => '❌ Apagar', 'callback_data' => 'apagar'],
                    ['text' => 'Painel do 7', 'url' => 'https://paineldo7.rf.gd']
                ]
            ];

            bot("editMessageText", [
                "chat_id" => $chat_id,
                "message_id" => $msg_id_aguarde,
                "text" => $txt,
                "reply_markup" => $botoes,
                "parse_mode" => "Markdown"
            ]);
        } else {
            bot("editMessageText", [
                "chat_id" => $chat_id,
                "message_id" => $msg_id_aguarde,
                "text" => "❌ Nenhum resultado encontrado para o nome informado.",
                "parse_mode" => "Markdown"
            ]);
        }
    } else {
        bot("sendMessage", [
            "chat_id" => $chat_id,
            "text" => "⚠️ Use corretamente: /nome Nome Completo",
            "parse_mode" => "Markdown"
        ]);
    }
}

if (isset($data)) {
    if ($data == "apagar") {
        bot("deleteMessage", [
            "chat_id" => $query_chat_id,
            "message_id" => $query_message_id
        ]);
        exit;
    }

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
