<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }} - Miniapp</title>

    <!-- Telegram WebApp SDK -->
    <script src="https://telegram.org/js/telegram-web-app.js"></script>

    <!-- Telegram UI Library -->
    <link rel="stylesheet" href="https://unpkg.com/@telegram-apps/telegram-ui@2.1.8/dist/styles.css">
    <script src="https://unpkg.com/@telegram-apps/telegram-ui@2.1.8/dist/telegram-ui.umd.js"></script>

    <style>
        :root {
            --tg-theme-bg-color: #ffffff;
            --tg-theme-text-color: #000000;
            --tg-theme-hint-color: #999999;
            --tg-theme-link-color: #2481cc;
            --tg-theme-button-color: #2481cc;
            --tg-theme-button-text-color: #ffffff;
        }

        body {
            background-color: var(--tg-theme-bg-color);
            color: var(--tg-theme-text-color);
            margin: 0;
            padding: 0;
            font-family: Roboto, -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif;
        }

        .miniapp-header {
            position: sticky;
            top: 0;
            z-index: 1000;
            background-color: var(--tg-theme-secondary-bg-color, rgba(0, 0, 0, 0.05));
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 12px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .miniapp-header .user-name {
            font-weight: 600;
            font-size: 16px;
            color: var(--tg-theme-text-color);
        }

        .miniapp-header .currency-selector {
            cursor: pointer;
            padding: 6px 12px;
            border-radius: 6px;
            background-color: var(--tg-theme-button-color);
            color: var(--tg-theme-button-text-color);
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
            font-weight: 500;
            transition: opacity 0.2s;
        }

        .miniapp-header .currency-selector:hover {
            opacity: 0.8;
        }

        .currency-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }

        .currency-modal.active {
            display: flex;
        }

        .currency-modal-content {
            background-color: var(--tg-theme-bg-color);
            color: var(--tg-theme-text-color);
            border-radius: 12px;
            padding: 20px;
            max-width: 300px;
            width: 90%;
        }

        .currency-modal-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 16px;
            color: var(--tg-theme-text-color);
        }

        .currency-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .currency-item {
            padding: 12px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 16px;
            color: var(--tg-theme-text-color);
            transition: background-color 0.2s;
        }

        .currency-item:hover {
            background-color: var(--tg-theme-button-color);
            color: var(--tg-theme-button-text-color);
        }

        .currency-item.active {
            background-color: var(--tg-theme-button-color);
            color: var(--tg-theme-button-text-color);
        }

        .rating-emoji {
            font-size: 24px;
        }
    </style>

    @livewireStyles
</head>
<body class="tg-root">
    @if(Auth::check())
    <div class="miniapp-header">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div class="home-button" onclick="window.location.href='/miniapp'" style="cursor: pointer; font-size: 20px;">🏠</div>
            <div class="user-name">{{ Auth::user()->first_name }}</div>
        </div>
        <div class="currency-selector" onclick="openCurrencyModal()">
            <span id="currency-flag">{{ Auth::user()->currency?->flagEmoji() ?? '💱' }}</span>
            <span id="currency-code">{{ Auth::user()->currency?->title() ?? 'Выберите' }}</span>
        </div>
    </div>
    @endif

    @yield('content')

    <!-- Currency Modal -->
    <div id="currencyModal" class="currency-modal" onclick="closeCurrencyModal(event)">
        <div class="currency-modal-content" onclick="event.stopPropagation()">
            <div class="currency-modal-title">Выберите валюту</div>
            <div class="currency-list">
                <div class="currency-item" onclick="selectCurrency(1)">
                    <span>🇷🇺</span>
                    <span>RUB</span>
                </div>
                <div class="currency-item" onclick="selectCurrency(2)">
                    <span>🇺🇸</span>
                    <span>USD</span>
                </div>
                <div class="currency-item" onclick="selectCurrency(3)">
                    <span>🇪🇺</span>
                    <span>EUR</span>
                </div>
                <div class="currency-item" onclick="selectCurrency(4)">
                    <span>🇰🇿</span>
                    <span>KZT</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Telegram WebApp Init -->
    <script>
        // Инициализация Telegram WebApp
        const tg = window.Telegram.WebApp;
        tg.ready();
        tg.expand();

        // Применение темы Telegram
        document.documentElement.style.setProperty('--tg-theme-bg-color', tg.themeParams.bg_color || '#ffffff');
        document.documentElement.style.setProperty('--tg-theme-text-color', tg.themeParams.text_color || '#000000');
        document.documentElement.style.setProperty('--tg-theme-hint-color', tg.themeParams.hint_color || '#999999');
        document.documentElement.style.setProperty('--tg-theme-link-color', tg.themeParams.link_color || '#2481cc');
        document.documentElement.style.setProperty('--tg-theme-button-color', tg.themeParams.button_color || '#2481cc');
        document.documentElement.style.setProperty('--tg-theme-button-text-color', tg.themeParams.button_text_color || '#ffffff');

        // Если есть initData и пользователь не авторизован, отправляем на сервер
        if (tg.initData) {
            const url = new URL(window.location.href);
            if (!url.searchParams.has('initData')) {
                url.searchParams.set('initData', tg.initData);
                window.location.href = url.toString();
            }
        }

        // Currency modal functions
        function openCurrencyModal() {
            document.getElementById('currencyModal').classList.add('active');
        }

        function closeCurrencyModal(event) {
            if (event.target.id === 'currencyModal') {
                document.getElementById('currencyModal').classList.remove('active');
            }
        }

        async function selectCurrency(currencyId) {
            try {
                const response = await fetch('/api/miniapp/user/currency', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ currency: currencyId })
                });

                const data = await response.json();

                if (data.success) {
                    document.getElementById('currency-flag').textContent = data.currency_flag;
                    document.getElementById('currency-code').textContent = data.currency_title;
                    closeCurrencyModal({ target: { id: 'currencyModal' } });

                    // Перезагружаем список игр если функция существует
                    if (typeof loadGames === 'function') {
                        loadGames();
                    }
                }
            } catch (error) {
                console.error('Error updating currency:', error);
            }
        }
    </script>

    @livewireScripts
</body>
</html>
