<?php

namespace App\Telegram\Commands;

use Telegram\Bot\Commands\Command;

class AddGameCommand extends Command
{
    protected string $name = 'add';

    protected string $description = 'Добавить игру';

    public function handle(): void
    {

    }
}

