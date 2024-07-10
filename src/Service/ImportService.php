<?php

namespace App\Service;

class ImportService
{
    private NotionService $notionService;

    public function __construct(NotionService $notionService)
    {
        $this->notionService = $notionService;
    }

    public function import(string $data): void
    {
        $lines = explode("\r\n", $data);

        foreach ($lines as $line) {
            $line = explode(',', $line);
            $this->notionService->import($line);
        }
    }
}
