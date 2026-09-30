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
     * Fotos ilustrativas (Unsplash, licença gratuita para uso comercial, sem atribuição obrigatória).
     * Usadas no visual e nos anúncios que não têm foto, sempre marcadas como "Imagem ilustrativa".
     * Para trocar, basta substituir o código da foto (parte depois de "photo-").
     */
    'fotos' => [
        'paisagem' => ['1732217164523-d147e04babaf', '1732217370219-f18445bc09b0', '1733057424920-7cf49b177b79'],
        'casas' => ['1706808849780-7a04fbac83ef', '1706164971299-cfa23ec76083', '1710046385385-a37df445b619', '1706164971302-e30c0640cc3b', '1649192154999-d924b3c9fb1b', '1672668802472-3410319459f0'],
        'aptos' => ['1738168279272-c08d6dd22002', '1682184805271-11671b7ecf4c', '1628592102751-ba83b0314276', '1603072845032-7b5bd641a82a', '1667584523543-d1d9cc828a15', '1758957701419-2c6e266f7988'],
        'terrenos' => ['1744658668911-8b9ec3a2de61', '1566296942542-b15c4da81c06', '1733057586164-6f9ab1062e7a', '1732216467230-42ce323767a8', '1598880457723-33f7c27d36fa'],
        'outros' => ['1647971447454-8093ed0f8e3d', '1598880457723-33f7c27d36fa', '1566296942542-b15c4da81c06'],
    ],
];
