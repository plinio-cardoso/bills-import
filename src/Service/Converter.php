<?php

namespace App\Service;

use ImportStatus;

class Converter
{
    private ImportStatus $importStatus = ImportStatus::STOPPED;

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

    private function buildCsvLine($line): string
    {
        $items = [
            sprintf('%s/%s', $line[0], date('Y')),
            str_replace('/', '|', $line[1]),
            '-' . str_replace(',', '.', $line[3]),
            'Bradesco',
            'PREENCHER'
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
