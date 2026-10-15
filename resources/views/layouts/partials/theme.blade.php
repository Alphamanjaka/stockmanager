{{-- Thème de l'application : réglage "theme_mode" (Administration › Paramètres › Interface & System).
     Appliqué avant le premier affichage (pas de flash) ; en mode automatique, themes.js suit ensuite le système. --}}
<script>
    (function (root) {
        root.dataset.themeMode = @json(settings('theme_mode', 'auto'));
        root.dataset.theme = root.dataset.themeMode === 'auto'
            ? (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
            : root.dataset.themeMode;
    })(document.documentElement);
</script>
