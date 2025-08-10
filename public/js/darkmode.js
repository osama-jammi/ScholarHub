document.addEventListener('DOMContentLoaded', function() {
    const darkModeToggle = document.getElementById('darkModeToggle');
    const body = document.body;
    
    // Vérifier le mode préféré
    const prefersDarkMode = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const savedMode = localStorage.getItem('darkMode');
    const sessionMode = document.body.classList.contains('dark-mode');
    
    // Appliquer le mode initial
    if (sessionMode || savedMode === 'enabled' || (!savedMode && prefersDarkMode)) {
        enableDarkMode();
    }
    
    // Gérer le clic
    if (darkModeToggle) {
        darkModeToggle.addEventListener('click', function() {
            if (body.classList.contains('dark-mode')) {
                disableDarkMode();
            } else {
                enableDarkMode();
            }
            
            // Sauvegarder dans la session via une requête AJAX
            saveModeToSession(body.classList.contains('dark-mode'));
        });
    }
    
    function enableDarkMode() {
        body.classList.add('dark-mode');
        localStorage.setItem('darkMode', 'enabled');
        updateToggleButton(true);
    }
    
    function disableDarkMode() {
        body.classList.remove('dark-mode');
        localStorage.setItem('darkMode', 'disabled');
        updateToggleButton(false);
    }
    
    function updateToggleButton(isDark) {
        if (!darkModeToggle) return;
        
        const icon = darkModeToggle.querySelector('i');
        const text = darkModeToggle.querySelector('.mode-text');
        
        if (isDark) {
            icon.classList.replace('fa-moon', 'fa-sun');
            text.textContent = ' Mode Clair';
        } else {
            icon.classList.replace('fa-sun', 'fa-moon');
            text.textContent = ' Mode Sombre';
        }
    }
    
    function saveModeToSession(isDark) {
        // Envoyer l'état au serveur via AJAX
        fetch('update_mode.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ darkMode: isDark })
        });
    }
});