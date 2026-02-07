@php
    use Illuminate\Support\Facades\Auth;
    use App\Enums\UserRatingEnum;
    $user = Auth::user();
    $initDataParam = request()->query('initData');

    $ratingEmojis = collect(UserRatingEnum::cases())->mapWithKeys(fn($case) => [$case->value => UserRatingEnum::from($case->value)->emoji()])->toArray();
@endphp

@extends('layouts.miniapp')

@section('content')
<style>
    .games-container {
        padding: 0;
    }

    .games-title {
        padding: 16px;
        margin: 0;
        font-size: 24px;
        font-weight: 700;
        text-align: center;
    }

    .loading {
        text-align: center;
        padding: 40px;
        color: var(--tg-theme-hint-color);
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
    }

    .empty-icon {
        font-size: 64px;
        margin-bottom: 16px;
    }

    .empty-title {
        font-size: 20px;
        font-weight: 600;
        margin-bottom: 8px;
    }

    .empty-subtitle {
        color: var(--tg-theme-hint-color);
    }

    .game-separator {
        border: none;
        border-top: 1px solid rgba(0, 0, 0, 0.1);
        margin: 0 16px;
    }

    .game-item {
        background-color: var(--tg-theme-secondary-bg-color, rgba(0, 0, 0, 0.03));
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        min-height: 68px;
        display: flex;
        align-items: center;
        cursor: pointer;
        transition: filter 0.2s;
    }

    .game-item:hover {
        filter: brightness(0.95);
    }

    .game-name {
        font-weight: 500;
        font-size: 15px;
        margin-bottom: 4px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .game-price {
        font-size: 14px;
        font-weight: 600;
        color: var(--tg-theme-text-color);
    }

    .game-price.discounted {
        color: #4CAF50;
    }

    .game-price-original {
        font-size: 12px;
        text-decoration: line-through;
        color: var(--tg-theme-hint-color);
        margin-left: 6px;
    }

    .game-price-discount {
        font-size: 11px;
        background-color: #4CAF50;
        color: white;
        padding: 2px 6px;
        border-radius: 4px;
        margin-left: 6px;
        font-weight: 600;
    }
</style>

<div class="games-container">
    @if($user)
        <h1 class="games-title">Рейтинг игр</h1>

        <div id="loading" class="loading">Загрузка...</div>

        <div id="gamesList" style="display: none;"></div>
        <div id="unratedGames" style="display: none;">
            <h2 class="games-title">Игры без оценок</h2>
            <div id="unratedGamesList"></div>
        </div>
    @else
        <div class="empty-state">
            <div class="empty-icon">🔒</div>
            <p class="empty-title">Пользователь не авторизован</p>
            <p class="empty-subtitle">Откройте это приложение через Telegram</p>
        </div>
    @endif
</div>

<script>
    // Загрузка списка игр
    async function loadGames() {
        try {
            const response = await fetch('/api/miniapp/games');
            const games = await response.json();

            const gamesList = document.getElementById('gamesList');
            const unratedGamesList = document.getElementById('unratedGamesList');
            gamesList.innerHTML = '';
            unratedGamesList.innerHTML = '';

            const ratingEmojis = @json($ratingEmojis);

            const ratedGames = games.filter(game => game.average_rating !== null);
            const unratedGames = games.filter(game => game.average_rating === null);

            // Функция создания карточки игры
            const createGameCell = (game, showRating = true) => {
                const userRatingEmoji = game.user_rating ? (ratingEmojis[game.user_rating] || '') : '';
                const avgRatingDisplay = game.average_rating ? game.average_rating.toFixed(1) : '—';

                // Формируем блок цены
                let priceHtml = '';
                if (game.price) {
                    const hasDiscount = game.price.discount_percent > 0;
                    const priceClass = hasDiscount ? 'game-price discounted' : 'game-price';

                    priceHtml = `
                        <div style="font-size: 13px; color: var(--tg-theme-hint-color); display: flex; align-items: center; flex-wrap: wrap;">
                            <span class="${priceClass}">${game.price.final_price} ${game.price.currency_symbol}</span>
                            ${hasDiscount ? `
                                <span class="game-price-original">${game.price.initial_price} ${game.price.currency_symbol}</span>
                                <span class="game-price-discount">-${game.price.discount_percent}%</span>
                            ` : ''}
                        </div>
                    `;
                }

                const cell = document.createElement('div');
                cell.className = 'game-item';
                cell.onclick = () => {
                    window.location.href = `/miniapp/game/${game.id}`;
                };

                cell.innerHTML = `
                    <div class="tgui-4ffd8a3f6b55d7b7" style="padding: 12px 16px; width: 100%; cursor: pointer;">
                        <div style="display: flex; align-items: center; gap: 12px; cursor: pointer;">
                            <div style="font-weight: 600; color: var(--tg-theme-hint-color); min-width: 30px;">
                                ${game.number}
                            </div>
                            <div style="flex: 1; min-width: 0;">
                                <div class="game-name">
                                    ${game.name}
                                </div>
                                ${priceHtml}
                                ${game.user_rating ? `<div style="font-size: 13px; color: var(--tg-theme-hint-color);">
                                    Ваша оценка: ${game.user_rating}
                                </div>` : ''}
                            </div>
                            ${showRating ? `<div style="font-size: 20px; font-weight: 600; color: var(--tg-theme-text-color);">
                                ${avgRatingDisplay}
                            </div>` : ''}
                        </div>
                    </div>
                `;

                return cell;
            };

            // Добавляем игры с рейтингом
            ratedGames.forEach((game, index) => {
                game.number = index + 1;
                gamesList.appendChild(createGameCell(game));
                if (index < ratedGames.length - 1) {
                    const separator = document.createElement('hr');
                    separator.className = 'game-separator';
                    gamesList.appendChild(separator);
                }
            });

            // Добавляем игры без рейтинга
            unratedGames.forEach((game, index) => {
                game.number = index + 1;
                unratedGamesList.appendChild(createGameCell(game, false));
                if (index < unratedGames.length - 1) {
                    const separator = document.createElement('hr');
                    separator.className = 'game-separator';
                    unratedGamesList.appendChild(separator);
                }
            });

            document.getElementById('loading').style.display = 'none';
            gamesList.style.display = 'block';

            if (unratedGames.length > 0) {
                document.getElementById('unratedGames').style.display = 'block';
            }
        } catch (error) {
            console.error('Error loading games:', error);
            document.getElementById('loading').textContent = 'Ошибка загрузки';
        }
    }

    // Загружаем игры при загрузке страницы
    if (document.getElementById('gamesList')) {
        loadGames();
    }
</script>
@endsection
