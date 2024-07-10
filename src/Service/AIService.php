<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;

class AIService
{
    public function convertData(string $data): string
    {
        $client = HttpClient::create();
        $apiKey = $_ENV['OPENAI_API_KEY'];
        $promptTemplate = '
            Converta para um CSV sem cabeçalho, simplesmente o resultado, sem explicação e sem formatação de código. Texto simples com quebra de linha: data,nome,valor,tipo,pago (true ou false),tipo_pagamento (cartao ou debito),banco (itau ou nubank)
            Tipo deve ser Despesa para valores positivos e Receita para valores negativos
            Datas devem ser no formato ano-mês-dia somente em números, sendo o ano o ano atual
            Nomes devem permanecer os mesmos
            Valores devem ser somente números separados por pontos - valores negativos como - R$ -0,010,01 na verdade é R$ 0,01; siga sempre esse padrão para números negativos, convertendo o negativo para positivo
            Banco deve ter valor padrao de "itau" se nao for especificado nenhum banco
            Tipo de Pagamento deve ter valor padrao de "cartao" se nao for especificado nenhum cartao, sem acentuação
            Pago deve ter valor padrao de "true" se nao for especificado
            Obs: quando ditado alguma regra ela deve valer para todas linhas sem excessão, as regras serao ditas na primeira linha dos dados, as regras podem ser somente banco, pagas ou nao, credito ou debito,
            podem conter outros inputs mais humanos como recorrencia, etc.
            Obs 2: Remover vírgulas do nome e substituir por espaço
            Obs 3: Ignorar dados se nos itens existir dados que não foram especificados nas regras/colunas
            
            Dados:
             %s
        ';

        $response = $client->request('POST', 'https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'gpt-4-turbo',
                'messages' => [
                    ['role' => 'user', 'content' => sprintf($promptTemplate, $data)],
                ]
            ],
        ]);

        $content = $response->toArray();

        return $content['choices'][0]['message']['content'];
    }
}
