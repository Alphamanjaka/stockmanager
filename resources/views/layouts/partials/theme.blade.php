{{-- Thème de l'application : réglage "theme_mode" (Administration › Paramètres › Interface & System).
     Appliqué avant le premier affichage (pas de flash) ; en mode automatique, themes.js suit ensuite le système.
     data-bs-theme est recopié pour que les composants Bootstrap suivent le même thème. --}}
<script>
    (function (root) {
        root.dataset.themeMode = @json(settings('theme_mode', 'auto'));
        root.dataset.theme = root.dataset.themeMode === 'auto'
            ? (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
            : root.dataset.themeMode;
        root.dataset.bsTheme = root.dataset.theme;
    })(document.documentElement);
</script>
