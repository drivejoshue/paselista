<?php

namespace App\Console\Commands;

use App\Services\Ai\DeepSeekClient;
use Illuminate\Console\Command;
use Throwable;

class TestSchoolPassAi extends Command
{
    protected $signature = 'schoolpass:ai-test {--deep : Usa deepseek-v4-pro}';
    protected $description = 'Comprueba la conexión de SchoolPass con DeepSeek.';

    public function handle(DeepSeekClient $client): int
    {
        $this->info('Probando conexión con DeepSeek...');

        try {
            $response = $client->analyze(
                messages: [
                    [
                        'role' => 'system',
                        'content' => 'Responde únicamente json válido.',
                    ],
                    [
                        'role' => 'user',
                        'content' => 'Devuelve este json exacto: {"ok":true,"module":"SchoolPass IA"}',
                    ],
                ],
                deepAnalysis: (bool) $this->option('deep')
            );

            $this->info('Conexión correcta.');
            $this->line('Modelo: '.$response['model']);
            $this->line(json_encode(
                $response['result'],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            ));

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }
    }
}
