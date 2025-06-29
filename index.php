<?php

define("BOT_TOKEN", "7152860548:AAFTLPfNHBksGCudquJxNQlgWgGn2r-etUs");

function bot($method, $datas = [])
{
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/" . $method;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $datas);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true);
}

function consultarCPF($cpf)
{
    $url = "https://api.thugapplications.xyz/api/cpf?cpf=$cpf";
    $resposta = file_get_contents($url);
    return json_decode($resposta, true);
}

$update = json_decode(file_get_contents("php://input"), true);

if (isset($update["message"])) {
    $message = $update["message"];
    $text = $message["text"] ?? '';
    $chat_id = $message["chat"]["id"];
    $msg_id = $message["message_id"];

    if (preg_match('/^\d{11}$/', $text)) {
        $cpf = $text;
        $dados = consultarCPF($cpf);

        if (!$dados || empty($dados["dados_pessoais"])) {
            bot("sendMessage", [
                "chat_id" => $chat_id,
                "text" => "❌ CPF inválido ou sem dados encontrados.",
            ]);
            exit;
        }

        $p = $dados["dados_pessoais"];
        $nome = $p["nome"] ?? "Não encontrado";
        $sexo = $p["sexo"] ?? "Não encontrado";
        $nasc = $p["data_nascimento"] ?? "Não encontrado";
        $mae = $p["nome_mae"] ?? "Não encontrado";
        $nacionalidade = $p["nacionalidade"] ?? "Não encontrado";

        $score = $dados["score"]["CSBA"] ?? "Desconhecido";
        $faixa = $dados["score"]["CSBA_FAIXA"] ?? "Indefinida";

        $endereco = "Não encontrado";
        if (!empty($dados["enderecos"])) {
            $e = $dados["enderecos"][0];
            $endereco = "{$e['LOGR_TIPO']} {$e['LOGR_NOME']}, {$e['LOGR_NUMERO']} - {$e['BAIRRO']}, {$e['CIDADE']}/{$e['UF']} - CEP: {$e['CEP']}";
        }

        $txt = "🔍 *Consulta de CPF*\n\n";
        $txt .= "👤 *Nome:* $nome\n";
        $txt .= "📅 *Nascimento:* $nasc\n";
        $txt .= "🧬 *Sexo:* $sexo\n";
        $txt .= "👩 *Mãe:* $mae\n";
        $txt .= "🌎 *Nacionalidade:* $nacionalidade\n";
        $txt .= "📈 *Score:* $score ($faixa)\n";
        $txt .= "🏠 *Endereço:* $endereco\n\n";
        $txt .= "🧩 Dados adicionais podem estar disponíveis.\nClique nos botões abaixo 👇";

        $botoes = [
            [["text" => "📞 Ver Telefones", "callback_data" => "ver_telefones:$cpf"]],
            [["text" => "📧 Ver Emails", "callback_data" => "ver_emails:$cpf"]],
        ];

        bot("sendMessage", [
            "chat_id" => $chat_id,
            "text" => $txt,
            "parse_mode" => "Markdown",
            "reply_markup" => json_encode(["inline_keyboard" => $botoes])
        ]);
    }
}

if (isset($update["callback_query"])) {
    $data = $update["callback_query"]["data"];
    $query_id = $update["callback_query"]["id"];
    $query_chat_id = $update["callback_query"]["message"]["chat"]["id"];
    $msg_id = $update["callback_query"]["message"]["message_id"];

    // Função para montar o menu inicial
    function menu_inicial($cpf) {
        $p = consultarCPF($cpf)["dados_pessoais"];
        $nome = $p["nome"] ?? "Não encontrado";
        $sexo = $p["sexo"] ?? "Não encontrado";
        $nasc = $p["data_nascimento"] ?? "Não encontrado";
        $mae = $p["nome_mae"] ?? "Não encontrado";
        $nacionalidade = $p["nacionalidade"] ?? "Não encontrado";

        $dados = consultarCPF($cpf);
        $score = $dados["score"]["CSBA"] ?? "Desconhecido";
        $faixa = $dados["score"]["CSBA_FAIXA"] ?? "Indefinida";

        $endereco = "Não encontrado";
        if (!empty($dados["enderecos"])) {
            $e = $dados["enderecos"][0];
            $endereco = "{$e['LOGR_TIPO']} {$e['LOGR_NOME']}, {$e['LOGR_NUMERO']} - {$e['BAIRRO']}, {$e['CIDADE']}/{$e['UF']} - CEP: {$e['CEP']}";
        }

        $txt = "🔍 *Consulta de CPF*\n\n";
        $txt .= "👤 *Nome:* $nome\n";
        $txt .= "📅 *Nascimento:* $nasc\n";
        $txt .= "🧬 *Sexo:* $sexo\n";
        $txt .= "👩 *Mãe:* $mae\n";
        $txt .= "🌎 *Nacionalidade:* $nacionalidade\n";
        $txt .= "📈 *Score:* $score ($faixa)\n";
        $txt .= "🏠 *Endereço:* $endereco\n\n";
        $txt .= "🧩 Dados adicionais podem estar disponíveis.\nClique nos botões abaixo 👇";

        $botoes = [
            [["text" => "📞 Ver Telefones", "callback_data" => "ver_telefones:$cpf"]],
            [["text" => "📧 Ver Emails", "callback_data" => "ver_emails:$cpf"]],
        ];

        return ["text" => $txt, "reply_markup" => json_encode(["inline_keyboard" => $botoes])];
    }

    if (strpos($data, "ver_telefones:") === 0) {
        $cpf = str_replace("ver_telefones:", "", $data);
        $dados = consultarCPF($cpf);

        $telefones = $dados["telefones"] ?? [];
        $texto = "📞 *Telefones encontrados:*\n\n";
        if ($telefones) {
            foreach ($telefones as $tel) {
                $ddd = $tel["DDD"] ?? "";
                $num = $tel["TELEFONE"] ?? "";
                $texto .= "📱 ($ddd) $num\n";
            }
        } else {
            $texto .= "Nenhum telefone encontrado.";
        }

        $botoes = [
            [["text" => "🔙 Voltar", "callback_data" => "voltar:$cpf"]]
        ];

        bot("answerCallbackQuery", [
            "callback_query_id" => $query_id,
        ]);

        bot("editMessageText", [
            "chat_id" => $query_chat_id,
            "message_id" => $msg_id,
            "text" => $texto,
            "parse_mode" => "Markdown",
            "reply_markup" => json_encode(["inline_keyboard" => $botoes])
        ]);
    }

    if (strpos($data, "ver_emails:") === 0) {
        $cpf = str_replace("ver_emails:", "", $data);
        $dados = consultarCPF($cpf);

        $emails = $dados["emails"] ?? [];
        $texto = "📧 *Emails encontrados:*\n\n";
        if ($emails) {
            foreach ($emails as $email) {
                $texto .= "✉️ $email\n";
            }
        } else {
            $texto .= "Nenhum email encontrado.";
        }

        $botoes = [
            [["text" => "🔙 Voltar", "callback_data" => "voltar:$cpf"]]
        ];

        bot("answerCallbackQuery", [
            "callback_query_id" => $query_id,
        ]);

        bot("editMessageText", [
            "chat_id" => $query_chat_id,
            "message_id" => $msg_id,
            "text" => $texto,
            "parse_mode" => "Markdown",
            "reply_markup" => json_encode(["inline_keyboard" => $botoes])
        ]);
    }

    if (strpos($data, "voltar:") === 0) {
        $cpf = str_replace("voltar:", "", $data);
        $menu = menu_inicial($cpf);

        bot("answerCallbackQuery", [
            "callback_query_id" => $query_id,
        ]);

        bot("editMessageText", [
            "chat_id" => $query_chat_id,
            "message_id" => $msg_id,
            "text" => $menu["text"],
            "parse_mode" => "Markdown",
            "reply_markup" => $menu["reply_markup"]
        ]);
    }
}
