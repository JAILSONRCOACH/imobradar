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
];
