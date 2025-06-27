<?php

// Responde imediatamente ao Telegram
http_response_code(200);
ignore_user_abort(true);
flush();
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
}

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

function bot($method, $params) {
    $token = "SEU_TOKEN_AQUI"; // <-- Substitua pelo seu token
    $url = "https://api.telegram.org/bot{$token}/{$method}";
    $options = [
        'http' => [
            'method'  => 'POST',
            'content' => json_encode($params),
            'header'  => "Content-Type: application/json\r\n"
        ]
    ];
    return file_get_contents($url, false, stream_context_create($options));
}

function v($dado) {
    return empty($dado) ? "Não encontrado" : $dado;
}

if ($texto && strpos($texto, "/cpf") === 0) {
    $partes = explode(" ", $texto);
    if (isset($partes[1])) {
        $cpf = preg_replace("/[^0-9]/", "", $partes[1]);

        $aguarde = bot("sendMessage", [
            "chat_id" => $chat_id,
            "text" => "⏳ Aguarde, consultando CPF `$cpf`...",
            "parse_mode" => "Markdown"
        ]);
        $aguarde = json_decode($aguarde, true);
        $msg_id = $aguarde['result']['message_id'];

        $url = "https://mdzapis.com/api/consultanew?base=cpf_serasa_completo&query={$cpf}&apikey=Ribeiro7";
        $res = @file_get_contents($url);
        $dados = json_decode($res, true);

        if (isset($dados["dados_pessoais"]["nome"])) {
            $p = $dados["dados_pessoais"];

            $txt = "*🔍 Resultado para CPF:* `$cpf`\n\n";
            $txt .= "👤 *Nome:* " . v($p["nome"]) . "\n";
            $txt .= "👩‍👧 *Mãe:* " . v($p["nome_mae"]) . "\n";
            $txt .= "📅 *Nascimento:* " . v($p["data_nascimento"]) . "\n";
            $txt .= "⚧️ *Sexo:* " . v($p["sexo"]) . "\n";
            $txt .= "🪪 *RG:* " . v($p["rg"]) . "\n";
            $txt .= "🗳️ *Título Eleitor:* " . v($p["titulo_eleitor"]) . "\n";
            $txt .= "🇧🇷 *Nacionalidade:* " . v($p["nacionalidade"]) . "\n";
            $txt .= "💸 *Renda:* R$ " . v($p["renda"]) . "\n\n";

            $enderecos = "";
            if (!empty($dados["enderecos"])) {
                foreach ($dados["enderecos"] as $e) {
                    $enderecos .= "🏠 {$e["LOGR_TIPO"]} {$e["LOGR_NOME"]}, {$e["LOGR_NUMERO"]} - {$e["BAIRRO"]}, {$e["CIDADE"]} - {$e["UF"]}\n";
                }
            } else $enderecos = "Não encontrados";

            $emails = !empty($dados["emails"]) ? implode(", ", $dados["emails"]) : "Não encontrados";

            $telefones = "";
            if (!empty($dados["telefones"])) {
                foreach ($dados["telefones"] as $t) {
                    $telefones .= "📞 ({$t["DDD"]}) {$t["TELEFONE"]}\n";
                }
            } else $telefones = "Não encontrados";

            $txt .= "📬 *Endereços:*\n$enderecos\n";
            $txt .= "📧 *Emails:* $emails\n\n";
            $txt .= "📱 *Telefones:*\n$telefones";

        } else {
            $txt = "❌ CPF não encontrado.";
        }

        $botoes = [
            [
                ["text" => "🗑 Apagar", "callback_data" => "apagar"],
                ["text" => "🌐 Painel do 7", "url" => "https://paineldo7.rf.gd"]
            ]
        ];

        bot("editMessageText", [
            "chat_id" => $chat_id,
            "message_id" => $msg_id,
            "text" => $txt,
            "parse_mode" => "Markdown",
            "reply_markup" => ["inline_keyboard" => $botoes]
        ]);
    }
}

if ($texto && strpos($texto, "/nome") === 0) {
    $nomeBusca = trim(str_replace("/nome", "", $texto));
    if ($nomeBusca) {
        $aguarde = bot("sendMessage", [
            "chat_id" => $chat_id,
            "text" => "🔎 Buscando dados do nome *{$nomeBusca}*...",
            "parse_mode" => "Markdown"
        ]);
        $aguarde = json_decode($aguarde, true);
        $msg_id = $aguarde['result']['message_id'];

        $url = "https://mdzapis.com/api/consultanew?base=nome_abreviado&query=" . urlencode($nomeBusca) . "&apikey=Ribeiro7";
        $res = @file_get_contents($url);
        $dados = json_decode($res, true);

        if (!empty($dados["RESULTADOS"])) {
            $txt = "*🔍 Resultados encontrados:*\n\n";
            foreach ($dados["RESULTADOS"] as $r) {
                $txt .= "👤 *Nome:* " . v($r["NOME"]) . "\n";
                $txt .= "📄 *CPF:* " . v($r["CPF"]) . "\n";
                $txt .= "👩‍👧 *Mãe:* " . v($r["NOME_MAE"]) . "\n";
                $txt .= "📅 *Nascimento:* " . v($r["NASC"]) . "\n";
                $txt .= "⚧️ *Sexo:* " . v($r["SEXO"]) . "\n\n";
            }
        } else {
            $txt = "❌ Nenhum dado encontrado para o nome informado.";
        }

        $botoes = [
            [
                ["text" => "🗑 Apagar", "callback_data" => "apagar"],
                ["text" => "🌐 Painel do 7", "url" => "https://paineldo7.rf.gd"]
            ]
        ];

        bot("editMessageText", [
            "chat_id" => $chat_id,
            "message_id" => $msg_id,
            "text" => $txt,
            "parse_mode" => "Markdown",
            "reply_markup" => ["inline_keyboard" => $botoes]
        ]);
    }
}

if ($data == "apagar") {
    bot("deleteMessage", [
        "chat_id" => $query_chat_id,
        "message_id" => $query_message_id
    ]);
}
