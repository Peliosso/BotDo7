<?php

$input = file_get_contents('php://input');
$update = json_decode($input);

$message = $update->message ?? null;
$data = $update->callback_query->data ?? null;

$chat_id = $message->chat->id ?? null;
$message_id = $message->message_id ?? null;
$thread_id = $message->message_thread_id ?? null;
$texto = $message->text ?? null;
$nome = $message->from->first_name ?? '';
$query_id = $update->callback_query->id ?? '';
$query_chat_id = $update->callback_query->message->chat->id ?? null;
$query_message_id = $update->callback_query->message->message_id ?? null;
$query_nome = $update->callback_query->message->chat->first_name ?? '';

function bot($method, $parameters) {
    global $thread_id;

    if ($thread_id !== null && in_array($method, ['sendMessage', 'editMessageText'])) {
        $parameters['message_thread_id'] = $thread_id;
    }

    $token = "7152860548:AAFTLPfNHBksGCudquJxNQlgWgGn2r-etUs"; // Seu token
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

$usuarios_autorizados = [7926471341, -1002323697211, -1002552180485]; // Substitua pelos IDs reais

function autorizado($chat_id) {
    global $usuarios_autorizados;
    return in_array($chat_id, $usuarios_autorizados);
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

    $txt  = "✨ *Consultas disponíveis no Painel do 7*\n\n";
    $txt .= "📡 *Status:* `ONLINE`\n";
    $txt .= "📅 *Atualizado:* " . date("d/m/Y") . "\n\n";
    
    $txt .= "📂 *COMANDOS:*\n";
    $txt .= "━━━━━━━━━━━━━━━━━━━\n";
    $txt .= "🧠 *CPF (Serasa 1)*\n";
    $txt .= "🔹 Ex: `/cpf 28536726890`\n\n";

    $txt .= "🧾 *Nome Completo*\n";
    $txt .= "🔹 Ex: `/nome Ana Luiza Silva`\n\n";

    $txt .= "📱 *Telefone com DDD*\n";
    $txt .= "🔹 Ex: `/tel 11999999999`\n\n";

    $txt .= "🚗 *Placa Veicular*\n";
    $txt .= "🔹 Ex: `/placa ABC1234`\n";
    $txt .= "━━━━━━━━━━━━━━━━━━━\n\n";

    $txt .= "💬 *Use os comandos no privado ou grupo.*\n";
    $txt .= "👨‍💻 *Suporte:* @RibeiroDo171";

    $menu['inline_keyboard'] = [
        [['text'=>"🔙 Voltar ao Início", "callback_data" => "start"]],
    ];

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

    $txt = "*🕵️ PLANO INDIVIDUAL*\n\n*💰 PREÇOS:*\n*1 SEMANA = R$10,00*\n\n⚠ *Também ganha no seu grupo!*";

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

function tentarConsultarAPI($url, $tentativas = 10, $intervalo = 1) {
    for ($i = 0; $i < $tentativas; $i++) {
        $resposta = @file_get_contents($url);
        if (!empty($resposta)) {
            $dados = json_decode($resposta, true);
            if (!empty($dados)) {
                return $dados;
            }
        }
        sleep($intervalo);
    }
    return null;
}

if (isset($texto) && strpos($texto, "/start") === 0) {
    start([
        "chat_id" => $chat_id,
        "nome" => $nome
    ]);
}

if (isset($texto) && strpos($texto, "/cpf") === 0) {
if (!autorizado($chat_id)) {
    bot("sendMessage", [
        "chat_id" => $chat_id,
        "text" => "🚫 *Acesso negado!*\n\nEste bot é exclusivo para usuários autorizados.\nEntre em contato com o suporte: @RibeiroDo171",
        "parse_mode" => "Markdown"
    ]);
    exit;
}
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

            bot("deleteMessage", [
    "chat_id" => $chat_id,
    "message_id" => $msg_id_aguarde
]);

bot("sendMessage", [
    "chat_id" => $chat_id,
    "text" => "✅ *Consulta encontrada para:* `$cpf`\n\nClique no botão abaixo para visualizar os dados completos no MiniApp:",
    "reply_markup" => [
        "inline_keyboard" => [
            [
                ['text' => '✅ • Ver Consulta', 'web_app' => ['url' => "https://botdo7.onrender.com/miniapp/cpf.html?cpf={$cpf}"]]
            ]
        ]
    ],
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

if (isset($texto) && strpos($texto, "/placa") === 0) {
if (!autorizado($chat_id)) {
    bot("sendMessage", [
        "chat_id" => $chat_id,
        "text" => "🚫 *Acesso negado!*\n\nEste bot é exclusivo para usuários autorizados.\nEntre em contato com o suporte: @RibeiroDo171",
        "parse_mode" => "Markdown"
    ]);
    exit;
}
    $partes = explode(" ", $texto);
    if (isset($partes[1])) {
        $placa = strtoupper(preg_replace("/[^A-Z0-9]/", "", $partes[1]));

        $aguarde = bot("sendMessage", [
            "chat_id" => $chat_id,
            "text" => "⏳ Consultando placa `$placa`...",
            "parse_mode" => "Markdown"
        ]);
        $aguarde = json_decode($aguarde, true);
        $msg_id_aguarde = $aguarde['result']['message_id'];

        $apiUrl = "https://patronhost.online/apis/placa.php?placa={$placa}";
        $resposta = file_get_contents($apiUrl);
        $dados = json_decode($resposta, true);

        if ($dados["sucesso"] ?? false) {
            $d = $dados["dados"];

            $txt = "*🚗 Resultado para Placa:* `$placa`\n\n";
            $txt .= "📍 *UF da Placa:* " . nf($d["uf_placa"] ?? "") . "\n";
            $txt .= "🏙️ *Município:* " . nf($d["municipio"] ?? "") . "\n";
            $txt .= "🏷️ *Marca:* " . nf($d["marca"] ?? "") . "\n";
            $txt .= "🚘 *Modelo:* " . nf($d["modelo"] ?? "") . "\n";
            $txt .= "🎨 *Cor:* " . nf($d["cor_veiculo"] ?? "") . "\n";
            $txt .= "🛢️ *Combustível:* " . nf($d["combustivel"] ?? "") . "\n";
            $txt .= "🗓️ *Ano Fab:* " . nf($d["ano_fabricacao"] ?? "") . "\n";
            $txt .= "🗓️ *Ano Mod:* " . nf($d["ano_modelo"] ?? "") . "\n";
            $txt .= "🆔 *Chassi:* " . nf($d["chassi"] ?? "") . "\n";
            $txt .= "⚙️ *Motor:* " . nf($d["motor"] ?? "") . "\n";
            $txt .= "⚖️ *Situação Chassi:* " . nf($d["situacao_chassi"] ?? "") . "\n";
            $txt .= "📌 *Situação Veículo:* " . nf($d["situacao_veiculo"] ?? "") . "\n\n";
            $txt .= "🚫 *Restrições:*\n";
            $txt .= "1️⃣ " . nf($d["restricao_1"] ?? "") . "\n";
            $txt .= "2️⃣ " . nf($d["restricao_2"] ?? "") . "\n";
            $txt .= "3️⃣ " . nf($d["restricao_3"] ?? "") . "\n";
            $txt .= "4️⃣ " . nf($d["restricao_4"] ?? "") . "\n";

            $botoes['inline_keyboard'] = [
                [
                    ['text' => '❌ Apagar', 'callback_data' => 'apagar']
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
                "text" => "❌ Nenhum dado encontrado para a placa informada.",
                "parse_mode" => "Markdown"
            ]);
        }
    } else {
        bot("sendMessage", [
            "chat_id" => $chat_id,
            "text" => "⚠️ Use corretamente: /placa ABC1234",
            "parse_mode" => "Markdown"
        ]);
    }
}

if (isset($texto) && strpos($texto, "/tel") === 0) {
if (!autorizado($chat_id)) {
    bot("sendMessage", [
        "chat_id" => $chat_id,
        "text" => "🚫 *Acesso negado!*\n\nEste bot é exclusivo para usuários autorizados.\nEntre em contato com o suporte: @RibeiroDo171",
        "parse_mode" => "Markdown"
    ]);
    exit;
}
    $partes = explode(" ", $texto);
    if (isset($partes[1])) {
        $numero = preg_replace("/[^0-9]/", "", $partes[1]);

        $aguarde = bot("sendMessage", [
            "chat_id" => $chat_id,
            "text" => "⏳ Consultando o telefone `$numero`...",
            "parse_mode" => "Markdown"
        ]);
        $aguarde = json_decode($aguarde, true);
        $msg_id_aguarde = $aguarde['result']['message_id'];

        $apiUrl = "https://mdzapis.com/api/consultanew?base=consulta_telefone2&query={$numero}&apikey=Ribeiro7";
        $resposta = file_get_contents($apiUrl);
        $dados = json_decode($resposta, true);

        $txt = "*📞 Resultado para:* `$numero`\n\n";
        $resultados = [];

        if (isset($dados["dados"]["serasa"]) && is_array($dados["dados"]["serasa"])) {
            foreach ($dados["dados"]["serasa"] as $pessoa) {
                $info = $pessoa["DADOSCPF"] ?? [];
                $end = $pessoa["DROP"] ?? [];

                $txt .= "👤 *Nome:* " . nf($info["NOME"] ?? "") . "\n";
                $txt .= "👩‍👧 *Mãe:* " . nf($info["NOME_MAE"] ?? "") . "\n";
                $txt .= "🪪 *CPF:* " . nf($info["CPF"] ?? "") . "\n";
                $txt .= "📅 *Nascimento:* " . nf(substr($info["NASC"] ?? "", 0, 10)) . "\n";
                $txt .= "⚧️ *Sexo:* " . nf($info["SEXO"] ?? "") . "\n";
                $txt .= "📈 *Renda:* R$ " . nf($info["RENDA"] ?? "") . "\n";
                $txt .= "🗳️ *Título Eleitor:* " . nf($info["TITULO_ELEITOR"] ?? "") . "\n";

                $endereco = nf(($end["LOGR_TIPO"] ?? "R") . " " . ($end["LOGR_NOME"] ?? "") . ", " . ($end["LOGR_NUMERO"] ?? "") . " - " . ($end["BAIRRO"] ?? "") . ", " . ($end["CIDADE"] ?? "") . " - " . ($end["UF"] ?? ""));
                $txt .= "🏠 *Endereço:* {$endereco}\n";
                $txt .= str_repeat("━", 30) . "\n";
            }
        } elseif (isset($dados["dados"]["outrasDB"])) {
            foreach ($dados["dados"]["outrasDB"] as $base => $entradas) {
                foreach ($entradas as $item) {
                    if (isset($item["NOME"])) {
                        $txt .= "👤 *Nome:* " . nf($item["NOME"] ?? "") . "\n";
                        $txt .= "🪪 *CPF:* " . nf($item["CPF"] ?? $item["CPF_CNPJ"] ?? "") . "\n";
                        $txt .= "📅 *Nascimento:* " . nf($item["DT_NASCIMENTO"] ?? $item["BVS_DT_NASC"] ?? "") . "\n";
                        $txt .= "📧 *Email:* " . nf($item["EMAIL"] ?? "") . "\n";
                        $txt .= "🏠 *Endereço:* " . nf($item["ENDERECO"] ?? $item["rua"] ?? "") . ", " . nf($item["NUMERO"] ?? "") . " - " . nf($item["BAIRRO"] ?? "") . ", " . nf($item["CIDADE"] ?? "") . " - " . nf($item["UF"] ?? "") . "\n";
                        $txt .= str_repeat("━", 30) . "\n";
                    }
                }
            }
        }

        if ($txt === "*📞 Resultado para:* `$numero`\n\n") {
            $txt .= "❌ Nenhuma informação encontrada para o número.";
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
        bot("sendMessage", [
            "chat_id" => $chat_id,
            "text" => "⚠️ Use corretamente: /tel 11999999999",
            "parse_mode" => "Markdown"
        ]);
    }
}

if (isset($texto) && strpos($texto, "/nome") === 0) {
if (!autorizado($chat_id)) {
    bot("sendMessage", [
        "chat_id" => $chat_id,
        "text" => "🚫 *Acesso negado!*\n\nEste bot é exclusivo para usuários autorizados.\nEntre em contato com o suporte: @RibeiroDo171",
        "parse_mode" => "Markdown"
    ]);
    exit;
}
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
