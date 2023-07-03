<?php

namespace App\Service;

use ImportStatus;

class Converter
{
    private ImportStatus $importStatus = ImportStatus::STOPPED;

    private array $categoryMapping = [
        'Refeicao' => [
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
        'Transporte' => [
            'Posto',
            'PRADO MULTIMARCAS',
            'TIRESHOP',
            'RENATO AUTO SERVICE'
        ],
        'Compras Online' => [
            'MercadoLivre',
            'AMAZON',
            'APPLE COM BILL',
            'Adidas',
            'Nike',
        ],
        'Beleza' => [
            'BARBEARIA',
        ],
        'Saude' => [
            'FARMACIA',
            'DROGASIL',
            'DROGARIA',
            'RAIA',
        ],
        'Esporte' => [
            'BT FO LONDRINA',
        ],
        'Lazer' => [
            'Netflix',
        ],
        'Supermercado' => [
            'MUFFATO',
            'SUPERMERCADO',
            'CANCAO LONDRINA'
        ],
        'Telefone / Internet' => [
            'VIVO'
        ],
        'PET' => [
            'PetShop',
            'PetCenterComercio',
            'Petzaplicativo',
        ],
        'Diversos Plinio' => [
            'CASA DOS PARAFUSOS',
            'MACRIPAR',
            'DUQUE COMPONENTES',
            'ALELUZ',
            'GranaCapital',
            'Suno Research',
            'PetCenterComercio',
        ]
    ];

    public function convertBradesco($path): string
    {
        $data = file_get_contents($path);
        $lines = explode("\r", $data);

        $csv = ['Data,Descrição,Valor,Conta,Categoria'];

        foreach ($lines as $line) {
            $line = explode(';', $line);

            if ($this->isFinishPoint($line)) {
                $this->importStatus = ImportStatus::STOPPED;
            }

            if ($this->importStatus == ImportStatus::PROCESSING && !$this->shouldIgnore($line)) {
                $csv[] = $this->buildCsvLine($line);
            }

            if ($this->isStartPoint($line)) {
                $this->importStatus = ImportStatus::PROCESSING;
            }
        }

        return implode("\n", $csv);
    }

    function mapCategoryName($name): string
    {
        foreach ($this->categoryMapping as $categoryName => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains(strtolower($name), strtolower($keyword)) !== false) {
                    return $categoryName;
                }
            }
        }

        return 'PREENCHER';
    }

    private function buildCsvLine($line): string
    {
        $name = trim(str_replace('/', '|', $line[1]));
        $items = [
            sprintf('%s/%s', $line[0], date('Y')),
            $name,
            '-' . str_replace(',', '.', $line[3]),
            'Bradesco',
            $this->mapCategoryName($name)
        ];

        return implode(',', $items);
    }

    private function shouldIgnore(array $line): bool
    {
        return str_contains($line[1], 'SALDO ANTERIOR')
            || str_contains($line[1], 'PAGTO. POR DEB EM');
    }

    private function isStartPoint(array $line): bool
    {
        return $line[0] === 'Data';
    }

    private function isFinishPoint(array $line): bool
    {
        return str_contains($line[0], 'Total para');
    }
}
