<?php

namespace App\Service;

class ImportService
{
    private AIService $AIService;
    private NotionService $notionService;

    public function __construct(AIService $AIService, NotionService $notionService)
    {
        $this->AIService = $AIService;
        $this->notionService = $notionService;
    }

    public function import(string $data, bool $displayAI): void
    {
        $convertedData = $this->AIService->convertData($data);

        if ($displayAI) {
            dd($convertedData);
        }

        $lines = explode("\n", $convertedData);

        foreach ($lines as $line) {
            $line = explode(',', $line);
            $this->notionService->import($line);
        }
    }
}
