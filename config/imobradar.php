<?php

return [
    // Token exigido na API de ingestão (Authorization: Bearer <token>).
    // Vazio = API de ingestão desligada.
    'ingest_token' => env('IMOBRADAR_INGEST_TOKEN', ''),

    // Máximo de anúncios aceitos por requisição de lote.
    'ingest_lote_max' => 500,

    // Dias em que o selo "Novo" aparece.
    'dias_novo' => 7,

    'uf' => 'PB',

    // Quem recebe os pedidos de cadastro e aparece como contato.
    'admin_email' => env('IMOBRADAR_ADMIN_EMAIL', 'contato@imobradar.com.br'),
    'whatsapp' => env('IMOBRADAR_WHATSAPP', '5583996158154'),
    'whatsapp_exibicao' => env('IMOBRADAR_WHATSAPP_EXIBICAO', '(83) 99615-8154'),

    // Validade do link de aprovação enviado por e-mail.
    'dias_link_aprovacao' => 7,

    /*
     * Busca sob demanda: quando alguém pesquisa uma cidade com dados velhos,
     * o site chama o webhook do n8n, que busca na web e devolve pela API de ingestão.
     */
    'busca' => [
        'n8n_webhook' => env('IMOBRADAR_N8N_WEBHOOK', ''),
        'n8n_segredo' => env('IMOBRADAR_N8N_SEGREDO', ''),
        // Depois de quantas horas uma cidade é buscada de novo.
        'horas_validade' => (int) env('IMOBRADAR_BUSCA_HORAS', 12),
        // Busca que passa disso sem resposta é considerada falha.
        'minutos_limite' => 8,
        // Controle de custo: buscas novas por usuário e no total, por dia.
        'limite_usuario_dia' => (int) env('IMOBRADAR_BUSCA_LIMITE_USUARIO', 15),
        'limite_total_dia' => (int) env('IMOBRADAR_BUSCA_LIMITE_TOTAL', 150),
    ],

    /*
     * Atualização semanal: rotinas do Claude Code gravam um JSON por cidade no ramo "dados"
     * do repositório no GitHub; o site importa esses arquivos (comando imobradar:importar-semanal).
     */
    'semanal' => [
        'base_url' => env('IMOBRADAR_DADOS_URL', 'https://raw.githubusercontent.com/JAILSONRCOACH/imobradar/dados'),
        // slug do município => praias/bairros que a rotina deve cobrir
        'cidades' => [
            'joao-pessoa' => 'Cabo Branco, Tambaú, Manaíra, Bessa, Altiplano, Bancários, Mangabeira, Centro',
            'cabedelo' => 'Intermares, Camboinha, Ponta de Campina, Poço, Formosa',
            'conde' => 'Jacumã, Carapibus, Tabatinga, Coqueirinho, Tambaba, Gurugi, Centro',
        ],
        // Anúncio não visto há mais que isso sai do ar (as rotinas não varrem o portal inteiro).
        'dias_expirar' => 21,
    ],
];
