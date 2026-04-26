<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'TrainTrack') : config('app.name', 'TrainTrack') }}
</title>

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet" />
@if(app()->getLocale() === 'ar')
<style>
    html[dir="rtl"] { font-family: 'Cairo', sans-serif; }
    html[dir="rtl"] * { font-family: inherit; }
</style>
@endif

@vite(['resources/css/app.css', 'resources/js/app.js'])

{{-- Global theme management script - runs on every page load --}}
<script>
    // Apply theme from localStorage
    function applyTheme() {
        const theme = localStorage.getItem('theme') || 'dark';
        if (theme === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    }

    // Initialize theme immediately to prevent flash
    applyTheme();

    // Re-apply theme on Livewire navigation (SPA-style page changes)
    document.addEventListener('livewire:navigated', function() {
        applyTheme();
    });

    // Global theme toggle function
    window.toggleTheme = function() {
        const currentTheme = localStorage.getItem('theme') || 'dark';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

        localStorage.setItem('theme', newTheme);

        if (newTheme === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }

        // Dispatch custom event for any listeners
        window.dispatchEvent(new CustomEvent('theme-changed', { detail: { theme: newTheme } }));
    };
</script>
