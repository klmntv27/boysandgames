@php
    use Illuminate\Support\Facades\Auth;
    $user = Auth::user();
@endphp

@extends('layouts.miniapp')

@section('content')
<style>
    .game-detail-container {
        padding: 16px;
    }

    .game-title {
        font-size: 24px;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .game-meta {
        display: flex;
        gap: 16px;
        margin-bottom: 16px;
        color: var(--tg-theme-hint-color);
        font-size: 13px;
    }

    .game-description {
        margin-bottom: 16px;
        line-height: 1.6;
        white-space: pre-wrap;
    }

    .game-images {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 12px;
        margin-bottom: 16px;
    }

    .game-images img {
        width: 100%;
        border-radius: 12px;
        cursor: pointer;
    }

    .section-title {
        font-size: 17px;
        font-weight: 600;
        margin: 20px 0 12px 0;
    }

    .rating-buttons {
        display: flex;
        gap: 6px;
        justify-content: space-between;
        margin-bottom: 20px;
    }

    .rating-button {
        flex: 1;
        padding: 10px 4px;
        border: 2px solid var(--tg-theme-button-color);
        background-color: var(--tg-theme-secondary-bg-color, rgba(0, 0, 0, 0.03));
        border-radius: 12px;
        cursor: pointer;
        font-size: 24px;
        transition: all 0.2s;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
        color: var(--tg-theme-text-color);
    }

    .rating-button .rating-button-label {
        color: var(--tg-theme-text-color);
    }

    .rating-button.active {
        border-color: var(--tg-theme-button-color);
        background-color: var(--tg-theme-button-color);
        color: var(--tg-theme-button-text-color);
    }

    .rating-button.active .rating-button-label {
        color: var(--tg-theme-button-text-color);
    }

    .rating-button-label {
        font-size: 9px;
        text-align: center;
    }

    .prices-container {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
        margin-bottom: 20px;
    }

    .price-card {
        background-color: var(--tg-theme-secondary-bg-color, rgba(0, 0, 0, 0.03));
        border-radius: 12px;
        padding: 12px;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .price-currency {
        font-size: 18px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .price-value {
        font-size: 16px;
        font-weight: 600;
        color: var(--tg-theme-text-color);
    }

    .price-value.discounted {
        color: #4CAF50;
    }

    .price-original {
        font-size: 13px;
        text-decoration: line-through;
        color: var(--tg-theme-hint-color);
    }

    .price-discount {
        font-size: 12px;
        color: #4CAF50;
        font-weight: 500;
    }

    .loading {
        text-align: center;
        padding: 40px;
        color: var(--tg-theme-hint-color);
    }

    @media (max-width: 600px) {
        .game-title {
            font-size: 20px;
        }

        .rating-buttons {
            gap: 4px;
        }

        .rating-button {
            padding: 8px 2px;
            font-size: 20px;
        }

        .rating-button-label {
            font-size: 8px;
        }
    }
</style>

<div class="game-detail-container">
    <div id="loading" class="loading">Загрузка...</div>

    <div id="gameContent" style="display: none;">
        <h1 class="game-title" id="gameTitle"></h1>

        <div class="game-meta">
            <span id="gameSteamRating"></span>
            <span id="gameAddedAt"></span>
        </div>

        <div class="game-description" id="gameDescription"></div>

        <div class="game-images" id="gameImages"></div>

        <!-- Блок цен -->
        <div id="pricesSection" style="display: none;">
            <div class="section-title">Цены в разных регионах</div>
            <div class="prices-container" id="pricesContainer"></div>
        </div>

        <!-- Блок оценки игры -->
        <div class="section-title">Ваша оценка</div>
        <div class="rating-buttons" id="ratingButtons">
            <button class="rating-button" onclick="rateGame(1)" data-rating="1">
                <span>👎</span>
                <span class="rating-button-label">Точно нет</span>
            </button>
            <button class="rating-button" onclick="rateGame(2)" data-rating="2">
                <span>👀</span>
                <span class="rating-button-label">Скорее нет</span>
            </button>
            <button class="rating-button" onclick="rateGame(3)" data-rating="3">
                <span>🤷‍♂️</span>
                <span class="rating-button-label">Не знаю</span>
            </button>
            <button class="rating-button" onclick="rateGame(4)" data-rating="4">
                <span>🤞</span>
                <span class="rating-button-label">Скорее да</span>
            </button>
            <button class="rating-button" onclick="rateGame(5)" data-rating="5">
                <span>👍</span>
                <span class="rating-button-label">Точно да</span>
            </button>
        </div>

        <!-- Список оценок пользователей -->
        <div class="section-title">Оценки пользователей</div>
        <div id="ratingsList"></div>
    </div>
</div>

<script>
    const gameId = {{ $gameId }};
    let currentUserRating = null;

    const ratingEmojis = {
        1: '👎',
        2: '👀',
        3: '🤷‍♂️',
        4: '🤞',
        5: '👍'
    };

    const currencyData = {
        1: { flag: '🇷🇺', symbol: '₽', code: 'RUB' },
        2: { flag: '🇺🇸', symbol: '$', code: 'USD' },
        3: { flag: '🇪🇺', symbol: '€', code: 'EUR' },
        4: { flag: '🇰🇿', symbol: '₸', code: 'KZT' }
    };

    // Загрузка деталей игры
    async function loadGameDetail() {
        try {
            const [gameResponse, ratingsResponse] = await Promise.all([
                fetch(`/api/miniapp/games/${gameId}`),
                fetch(`/api/miniapp/games/${gameId}/ratings`)
            ]);

            const game = await gameResponse.json();
            const ratings = await ratingsResponse.json();

            // Заполняем информацию об игре
            document.getElementById('gameTitle').textContent = game.name;
            document.getElementById('gameSteamRating').textContent = `Steam: ${game.steam_rating}%`;
            document.getElementById('gameAddedAt').textContent = `Добавлена: ${new Date(game.added_at).toLocaleDateString('ru-RU')}`;
            document.getElementById('gameDescription').textContent = game.description;

            // Загружаем изображения
            const imagesContainer = document.getElementById('gameImages');
            if (game.images && game.images.length > 0) {
                game.images.forEach(image => {
                    const img = document.createElement('img');
                    img.src = image.url;
                    img.alt = game.name;
                    img.onclick = () => window.open(image.url, '_blank');
                    imagesContainer.appendChild(img);
                });
            }

            // Устанавливаем текущую оценку пользователя
            if (game.user_rating) {
                currentUserRating = game.user_rating;
                updateRatingButtons();
            }

            // Загружаем цены
            if (game.prices && Object.keys(game.prices).length > 0) {
                displayPrices(game.prices);
            }

            // Загружаем список оценок
            displayRatings(ratings);

            document.getElementById('loading').style.display = 'none';
            document.getElementById('gameContent').style.display = 'block';
        } catch (error) {
            console.error('Error loading game detail:', error);
            document.getElementById('loading').textContent = 'Ошибка загрузки';
        }
    }

    // Отображение цен
    function displayPrices(prices) {
        const pricesContainer = document.getElementById('pricesContainer');
        pricesContainer.innerHTML = '';

        Object.entries(prices).forEach(([currencyId, price]) => {
            const currency = currencyData[currencyId];
            if (!currency) return;

            const card = document.createElement('div');
            card.className = 'price-card';

            const hasDiscount = price.discount_percent > 0;
            const priceClass = hasDiscount ? 'price-value discounted' : 'price-value';

            card.innerHTML = `
                <div class="price-currency">
                    <span>${currency.flag}</span>
                    <span>${currency.code}</span>
                </div>
                <div class="${priceClass}">${price.final_price} ${currency.symbol}</div>
                ${hasDiscount ? `
                    <div class="price-original">${price.initial_price} ${currency.symbol}</div>
                    <div class="price-discount">-${price.discount_percent}%</div>
                ` : ''}
            `;

            pricesContainer.appendChild(card);
        });

        document.getElementById('pricesSection').style.display = 'block';
    }

    // Отображение списка оценок
    function displayRatings(ratings) {
        const listContainer = document.getElementById('ratingsList');
        listContainer.innerHTML = '';

        if (ratings.length === 0) {
            listContainer.innerHTML = '<div class="loading">Пока нет оценок</div>';
            return;
        }

        ratings.forEach(rating => {
            const cell = document.createElement('div');
            cell.className = 'tgui-d3f8ac20ca81c66f';
            cell.innerHTML = `
                <div class="tgui-4ffd8a3f6b55d7b7" style="padding: 12px 16px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: 500; margin-bottom: 4px;">${rating.user.name}</div>
                            <div style="font-size: 13px; color: var(--tg-theme-hint-color);">${rating.rated_at}</div>
                        </div>
                        <div style="font-size: 28px;">${rating.rating_emoji}</div>
                    </div>
                </div>
            `;
            listContainer.appendChild(cell);
        });
    }

    // Обновление визуального состояния кнопок оценки
    function updateRatingButtons() {
        document.querySelectorAll('.rating-button').forEach(button => {
            const rating = parseInt(button.dataset.rating);
            if (rating === currentUserRating) {
                button.classList.add('active');
            } else {
                button.classList.remove('active');
            }
        });
    }

    // Оценка игры
    async function rateGame(rating) {
        try {
            const response = await fetch(`/api/miniapp/games/${gameId}/rate`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ rating })
            });

            const data = await response.json();

            if (data.success) {
                currentUserRating = rating;
                updateRatingButtons();

                // Перезагружаем список оценок
                const ratingsResponse = await fetch(`/api/miniapp/games/${gameId}/ratings`);
                const ratings = await ratingsResponse.json();
                displayRatings(ratings);
            }
        } catch (error) {
            console.error('Error rating game:', error);
            alert('Ошибка при сохранении оценки');
        }
    }

    // Загружаем данные при загрузке страницы
    loadGameDetail();
</script>
@endsection
