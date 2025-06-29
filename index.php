<?php

// =======================
// BOT TELEGRAM CPF COM BOTÕES DE EMAIL E TELEFONE
// =======================

$token = "SEU_TOKEN_AQUI"; // 🔒 Substitua pelo seu token do bot

$update = json_decode(file_get_contents("php://input"), true);

$message = $update["message"] ?? null;
$data = $update["callback_query"]["data"] ?? null;
$cpf_command = $message["text"] ?? null;

$chat_id = $message["chat"]["id"] ?? $update["callback_query"]["message"]["chat"]["id"] ?? null;
$message_id = $update["callback_query"]["message"]["message_id"] ?? null;
$query_id = $update["callback_query"]["id"] ?? null;
$query_chat_id = $update["callback_query"]["message"]["chat"]["id"] ?? null;

// Função para enviar requisições para a API do Telegram
function bot($method, $data = [])
{
    global $token;
    $url = "https://api.telegram.org/bot$token/$method";

    $options = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type:application/json\r\n",
            'content' => json_encode($data)
        ]
    ];

    $context = stream_context_create($options);
    return file_get_contents($url, false, $context);
}

// Função para obter dados da API
function consultarCPF($cpf)
{
    $apiUrl = "https://mdzapis.com/api/consultanew?base=cpf_serasa_completo&query=$cpf&apikey=Ribeiro7";
    $resposta = @file_get_contents($apiUrl, false, stream_context_create(['http' => ['timeout' => 10]]));
    return json_decode($resposta, true);
}

// ==================
// COMANDO DE CONSULTA CPF
// ==================
if ($cpf_command && strpos($cpf_command, "/cpf") === 0) {
    $cpf = trim(str_replace("/cpf", "", $cpf_command));

    if (empty($cpf)) {
        bot("sendMessage", [
            "chat_id" => $chat_id,
            "text" => "❌ Informe um CPF para consultar. Exemplo:\n`/cpf 12345678900`",
            "parse_mode" => "Markdown"
        ]);
        exit;
    }

    bot("sendMessage", [
        "chat_id" => $chat_id,
        "text" => "🔍 Consultando CPF: `$cpf`...",
        "parse_mode" => "Markdown"
    ]);

    $dados = consultarCPF($cpf);

    if (!$dados || isset($dados["erro"])) {
        bot("sendMessage", [
            "chat_id" => $chat_id,
            "text" => "❌ Não foi possível consultar este CPF.",
            "parse_mode" => "Markdown"
        ]);
        exit;
    }

    $nome = $dados["nome"] ?? "Não encontrado";
    $nascimento = $dados["nascimento"] ?? "Não encontrado";
    $sexo = $dados["sexo"] ?? "Não encontrado";
    $mae = $dados["mae"] ?? "Não encontrado";
    $score = $dados["score"] ?? "Não encontrado";

    $endereco = $dados["endereco"] ?? [];
    $logradouro = $endereco["logradouro"] ?? "";
    $numero = $endereco["numero"] ?? "";
    $bairro = $endereco["bairro"] ?? "";
    $cidade = $endereco["cidade"] ?? "";
    $estado = $endereco["estado"] ?? "";
    $cep = $endereco["cep"] ?? "";

    $txt = "🔍 *CPF CONSULTADO*\n\n";
    $txt .= "👤 *Nome:* $nome\n";
    $txt .= "🎂 *Nascimento:* $nascimento\n";
    $txt .= "👩‍👧 *Mãe:* $mae\n";
    $txt .= "🧬 *Sexo:* $sexo\n";
    $txt .= "📊 *Score:* $score\n\n";
    $txt .= "📍 *Endereço:*\n";
    $txt .= "$logradouro, $numero\n$bairro\n$cidade - $estado\nCEP: $cep\n\n";
    $txt .= "📧 *Emails:* Oculto\n";
    $txt .= "📱 *Telefones:* Oculto";

    $botoes = [
        'inline_keyboard' => [
            [
                ['text' => '📧 Ver Emails', 'callback_data' => "ver_emails:$cpf"],
                ['text' => '📱 Ver Telefones', 'callback_data' => "ver_telefones:$cpf"]
            ],
            [
                ['text' => '❌ Apagar', 'callback_data' => 'apagar'],
                ['text' => '🔗 Painel do 7', 'url' => 'https://paineldo7.rf.gd']
            ]
        ]
    ];

    bot("sendMessage", [
        "chat_id" => $chat_id,
        "text" => $txt,
        "parse_mode" => "Markdown",
        "reply_markup" => json_encode($botoes)
    ]);
}

// ========================
// CALLBACK: VER EMAILS
// ========================
if ($data && strpos($data, "ver_emails:") === 0) {
    $cpf = str_replace("ver_emails:", "", $data);
    $dados = consultarCPF($cpf);

    $emails_array = $dados["emails"] ?? [];
    $emails = count($emails_array) > 0 ? implode("\n📧 ", $emails_array) : "_Nenhum email encontrado._";

    bot("answerCallbackQuery", [
        "callback_query_id" => $query_id,
        "text" => "Emails carregados",
        "show_alert" => false
    ]);

    bot("sendMessage", [
        "chat_id" => $query_chat_id,
        "text" => "*📧 Emails encontrados:*\n📧 $emails",
        "parse_mode" => "Markdown"
    ]);
}

// ========================
// CALLBACK: VER TELEFONES
// ========================
if ($data && strpos($data, "ver_telefones:") === 0) {
    $cpf = str_replace("ver_telefones:", "", $data);
    $dados = consultarCPF($cpf);

    $telefones = "";
    if (!empty($dados["telefones"])) {
        foreach ($dados["telefones"] as $tel) {
            $telefones .= "📞 ({$tel["DDD"]}) {$tel["TELEFONE"]}\n";
        }
    } else {
        $telefones = "_Nenhum telefone encontrado._";
    }

    bot("answerCallbackQuery", [
        "callback_query_id" => $query_id,
        "text" => "Telefones carregados",
        "show_alert" => false
    ]);

    bot("sendMessage", [
        "chat_id" => $query_chat_id,
        "text" => "*📱 Telefones encontrados:*\n$telefones",
        "parse_mode" => "Markdown"
    ]);
}

// ========================
// CALLBACK: APAGAR MENSAGEM
// ========================
if ($data == "apagar") {
    bot("deleteMessage", [
        "chat_id" => $query_chat_id,
        "message_id" => $message_id
    ]);
}
