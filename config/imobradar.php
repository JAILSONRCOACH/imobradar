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
];
