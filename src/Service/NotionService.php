<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;

class NotionService
{
    const DEFAULT_CATEGORY_ID = '8995e5c0611c4e92b08384352d1fc187';

    private array $categoryMapping = [
        // Alimentação
        'b18d56a5fcdf4d83a1122391f5781c55' => [
            'IFD BR',
            'ESPETINHOS',
            'BODEGA',
            'GALPAO DA COSTELA',
            'iFood',
            'Panificadora',
            'Padoka',
            'CONVENIENCIA 855',
            'Carnes',
            'SORVETERIA',
            'PIZZARIA',
            'HAMBURGUERIA',
            'MR DOOGS',
            'QUE TEMPERO',
            'RESTAURANT',
            'ACOUGUE',
            'DOM ROCHA',
            'Flechazo',
            'LANCHONETE',
            'LUMBERJACK',
        ],
        // Transporte
        '114a72acbeea4227824a7abc79ae4eaf' => [
            'Posto',
            'PRADO MULTIMARCAS',
            'TIRESHOP',
            'RENATO AUTO SERVICE'
        ],
        // Compras Online
        'de8f6bb37f60423492418c10b6378c4d' => [
            'MercadoLivre',
            'AMAZON',
            'APPLE COM BILL',
            'Adidas',
            'Nike',
        ],
        // Beleza
        '4ef02c949996441a958bde40ef93fb11' => [
            'BARBEARIA',
        ],
        // Sauúde
        'cc937996354b4e2e91768ba4167fbc5d' => [
            'FARMACIA',
            'DROGASIL',
            'DROGARIA',
            'RAIA',
        ],
        // Lazer
        'c8cab024cdb6476dba12b30396c54ba8' => [
            'Netflix',
        ],
        // Mercado
        'b7e6b6d8a86f4768a90d377ac1247885' => [
            'MUFFATO',
            'SUPERMERCADO',
            'CANCAO LONDRINA'
        ],
        // Telefone / Internet
        '818de6069c884be7b73076c1f5c81bb2' => [
            'VIVO'
        ],
        // PET
        '9254f04703a948db9d9c03ee646d580f' => [
            'PetShop',
            'PetCenterComercio',
            'Petzaplicativo',
        ],
        // Diversos Plinio
        '20ac6d4b0a994aca976e415cbea797b7' => [
            'CASA DOS PARAFUSOS',
            'MACRIPAR',
            'DUQUE COMPONENTES',
            'ALELUZ',
            'GranaCapital',
            'Suno Research',
            'PetCenterComercio',
        ]
    ];

    public function import(array $item): void
    {
        $paymentNameMapping = [
            'cartao' => 'Cartão de Crédito',
            'debito' => 'Conta'
        ];

        $debitIdMapping = [
            'nubank' => $_ENV['NUBANK_DEBIT_ID'],
            'itau' => $_ENV['ITAU_DEBIT_ID']
        ];

        $creditIdMapping = [
            'nubank' => $_ENV['NUBANK_CREDIT_ID'],
            'itau' => $_ENV['ITAU_CREDIT_ID']
        ];

        $apiKey = $_ENV['NOTION_API_KEY'];
        $databaseId = $_ENV['NOTION_DATABASE_ID'];
        $defaultPaymentDay = $_ENV['DEFAULT_PAYMENT_DAY'];

        $paymentDate = date('Y-m-d', strtotime($defaultPaymentDay. '-' . date('m') . '-' . date('Y')));
        $date = $item[0];
        $name = $item[1];
        $price = $item[2];
        $type = $item[3];
        $paid = filter_var($item[4], FILTER_VALIDATE_BOOLEAN);
        $paymentType = $item[5];
        $bank = $item[6];
        $categoryId = $this->mapCategoryName($name);
        $paymentName = $paymentNameMapping[$paymentType];
        $paymentId = $creditIdMapping[$bank];

        if ($paymentType == 'debito') {
            $paymentId = $debitIdMapping[$bank];
        }

        $client = HttpClient::create();
        $client->request('POST', 'https://api.notion.com/v1/pages', [
            'headers' => [
                'Notion-Version' => '2022-06-28',
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'parent' => ['database_id' => $databaseId],
                'properties' => [
                    'Nome' => [
                        'title' => [
                            [
                                'text' => [
                                    'content' => $name,
                                ],
                            ],
                        ],
                    ],
                    'Data Vencimento' => [
                        'date' => [
                            'start' => $date
                        ]
                    ],
                    'Data Pagamento' => [
                        'date' => [
                            'start' => $paymentDate
                        ]
                    ],
                    'Valor' => [
                        'number' => (float) $price
                    ],
                    'Categoria' => [
                        'relation' => [
                            [
                                'id' => $categoryId
                            ]
                        ]
                    ],
                    $paymentName => [
                        'relation' => [
                            [
                                'id' => $paymentId
                            ]
                        ]
                    ],
                    'Pago' => [
                        'checkbox' => $paid
                    ],
                    'Tipo' => [
                        'select' => [
                            'name' => $type
                        ]
                    ]
                ],
            ],
        ]);
    }

    private function mapCategoryName($name): string
    {
        foreach ($this->categoryMapping as $categoryName => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains(strtolower($name), strtolower($keyword)) !== false) {
                    return $categoryName;
                }
            }
        }

        return self::DEFAULT_CATEGORY_ID;
    }
}
