<?php

namespace App\Console\Commands;

use App\Models\Game;
use App\Services\SteamApiGameInfoService;
use Illuminate\Console\Command;

class UpdateGamePreviewImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'game:update-preview-images';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Обновить preview_image_url для всех игр из Steam API';

    public function __construct(
        private readonly SteamApiGameInfoService $steamApiService
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $games = Game::all();

        if ($games->isEmpty()) {
            $this->info('Нет игр для обновления.');
            return;
        }

        $this->info("Найдено игр без preview_image_url: {$games->count()}");

        $progressBar = $this->output->createProgressBar($games->count());
        $progressBar->start();

        $successCount = 0;
        $errorCount = 0;

        foreach ($games as $game) {
            try {
                // Получаем preview_image_url из Steam API
                $gameDetails = $this->steamApiService->getGameDetails($game->steam_id);

                if ($gameDetails === null || empty($gameDetails['preview_image_url'])) {
                    $this->newLine();
                    $this->warn("Не удалось получить preview_image_url для игры {$game->name}");
                    $errorCount++;
                    $progressBar->advance();
                    continue;
                }

                // Обновляем URL в базе данных
                $game->update(['preview_image_url' => $gameDetails['preview_image_url']]);

                $successCount++;
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("Ошибка обработки игры {$game->name}: {$e->getMessage()}");
                $errorCount++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("Обработка завершена!");
        $this->info("Успешно: {$successCount}");
        if ($errorCount > 0) {
            $this->warn("Ошибок: {$errorCount}");
        }
    }
}
