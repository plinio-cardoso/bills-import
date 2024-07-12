<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;

class NotionService
{
    const DEFAULT_CATEGORY_ID = '8995e5c0611c4e92b08384352d1fc187';
    const ICON_TEMPLATE = 'https://www.notion.so/icons/%s.svg';

    private array $categoryMapping;

    public function __construct()
    {
        $this->categoryMapping = $this->getCategoryMapping();
    }

    /**
     * Import the given item into the Notion database.
     *
     * @param array $item
     */
    public function import(array $item): void
    {
        $paymentDetails = $this->getPaymentDetails($item);
        $icon = $this->getIcon($item);
        $categoryId = $this->mapCategoryName($item[1]);

        $this->sendRequestToNotion($item, $paymentDetails, $icon, $categoryId);
    }

    /**
     * Get the payment details based on the item data.
     *
     * @param array $item
     * @return array
     */
    private function getPaymentDetails(array $item): array
    {
        $paymentNameMapping = $this->getPaymentNameMapping();
        $debitIdMapping = $this->getDebitIdMapping();
        $creditIdMapping = $this->getCreditIdMapping();

        $paymentType = $item[5];
        $bank = $item[6];

        $paymentName = $paymentNameMapping[$paymentType];
        $paymentId = $paymentType == 'debito' ? $debitIdMapping[$bank] : $creditIdMapping[$bank];

        return [$paymentName, $paymentId];
    }

    /**
     * Get the icon based on the item data.
     *
     * @param array $item
     * @return string
     */
    private function getIcon(array $item): string
    {
        return $item[3] == 'Receita' ? 'add_green' : 'circle-remove_red';
    }

    /**
     * Send a request to the Notion API to import the item.
     *
     * @param array $item
     * @param array $paymentDetails
     * @param string $icon
     * @param string $categoryId
     */
    private function sendRequestToNotion(array $item, array $paymentDetails, string $icon, string $categoryId): void
    {
        [$paymentName, $paymentId] = $paymentDetails;

        $client = HttpClient::create();
        $client->request('POST', 'https://api.notion.com/v1/pages', [
            'headers' => $this->getHeaders(),
            'json' => $this->getJson($item, $paymentName, $paymentId, $icon, $categoryId),
        ]);
    }

    /**
     * Get the headers for the Notion API request.
     *
     * @return array
     */
    private function getHeaders(): array
    {
        return [
            'Notion-Version' => '2022-06-28',
            'Authorization' => 'Bearer ' . $_ENV['NOTION_API_KEY'],
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Get the JSON body for the Notion API request.
     *
     * @param array $item
     * @param string $paymentName
     * @param string $paymentId
     * @param string $icon
     * @param string $categoryId
     * @return array
     */
    private function getJson(array $item, string $paymentName, string $paymentId, string $icon, string $categoryId): array
    {
        return [
            'parent' => ['database_id' => $_ENV['NOTION_DATABASE_ID']],
            'properties' => $this->getProperties($item, $paymentName, $paymentId, $categoryId),
            'icon' => [
                'type' => 'external',
                'external' => [
                    'url' => sprintf(self::ICON_TEMPLATE, $icon)
                ]
            ]
        ];
    }

    /**
     * Get the properties for the Notion API request.
     *
     * @param array $item
     * @param string $paymentName
     * @param string $paymentId
     * @param string $categoryId
     * @return array
     */
    private function getProperties(array $item, string $paymentName, string $paymentId, string $categoryId): array
    {
        $paid = filter_var($item[4], FILTER_VALIDATE_BOOLEAN);

        $properties = [
            'Nome' => [
                'title' => [
                    [
                        'text' => [
                            'content' => $item[1],
                        ],
                    ],
                ],
            ],
            'Data Vencimento' => [
                'date' => [
                    'start' => $item[0]
                ]
            ],
            'Data Pagamento' => [
                'date' => [
                    'start' => date('Y-m-d', strtotime($_ENV['DEFAULT_PAYMENT_DAY']. '-' . date('m') . '-' . date('Y')))
                ]
            ],
            'Valor' => [
                'number' => (float) $item[2]
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
                    'name' => $item[3]
                ]
            ]
        ];

        if (!$paid) {
            unset($properties['Data Pagamento']);
        }

        return $properties;
    }

    /**
     * Map the given name to a category ID.
     *
     * @param string $name
     * @return string
     */
    private function mapCategoryName(string $name): string
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

    /**
     * Get the category mapping.
     *
     * @return array
     */
    private function getCategoryMapping(): array
    {
        return [
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
            // Casa
            'f64dfbeb6d55453fad3c9fac917ecde6' => [
                'Home Assistant',
                'Aluguel',
                'Sanepar',
                'Copel',
                'Edna',
                'Condomínio',
                'Vinho'
            ],
            // Entretenimento
            '94b87be263684caca26fa0cafba26194' => [
                'Youtube',
                'Spotify',
                'Netflix',
                'Home Assistant'
            ],
            // Trabalho Plinio
            '12b7dd6faeab42d39253a048d4ed5541' => [
                'Linkedin',
                'Github',
                'Contador'
            ],
            // Transporte
            '114a72acbeea4227824a7abc79ae4eaf' => [
                'Liberty',
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
            // Saúde
            'cc937996354b4e2e91768ba4167fbc5d' => [
                'Unimed',
                'FARMACIA',
                'DROGASIL',
                'DROGARIA',
                'RAIA',
                'Dental',
            ],
            // Lazer
            'c8cab024cdb6476dba12b30396c54ba8' => [
                'Futebol',
                'Hotel'
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
    }

    /**
     * Get the payment name mapping.
     *
     * @return array
     */
    private function getPaymentNameMapping(): array
    {
        return [
            'cartao' => 'Cartão de Crédito',
            'debito' => 'Conta'
        ];
    }

    /**
     * Get the debit ID mapping.
     *
     * @return array
     */
    private function getDebitIdMapping(): array
    {
        return [
            'nubank' => $_ENV['NUBANK_DEBIT_ID'],
            'itau' => $_ENV['ITAU_DEBIT_ID']
        ];
    }

    /**
     * Get the credit ID mapping.
     *
     * @return array
     */
    private function getCreditIdMapping(): array
    {
        return [
            'nubank' => $_ENV['NUBANK_CREDIT_ID'],
            'itau' => $_ENV['ITAU_CREDIT_ID']
        ];
    }
}
