// Gestion du menu latéral
const menuToggle = document.getElementById('menuToggle');
const sideNav = document.getElementById('sideNav');
const body = document.body;

// Fonction pour basculer l'état du sidebar
function toggleSidebar() {
    const isCollapsing = !body.classList.contains('collapsed-sidebar');
    
    sideNav.style.transition = 'transform 0.3s ease, width 0.3s ease';
    body.style.transition = 'padding-left 0.3s ease';
    
    body.classList.toggle('collapsed-sidebar');
    localStorage.setItem('sidebarCollapsed', body.classList.contains('collapsed-sidebar'));
    
    document.dispatchEvent(new CustomEvent('sidebarToggle', { 
        detail: { collapsed: isCollapsing }
    }));
}

function initSidebar() {
    const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    
    sideNav.style.transition = 'none';
    body.style.transition = 'none';
    
    if (isCollapsed) body.classList.add('collapsed-sidebar');
    else body.classList.remove('collapsed-sidebar');
    
    void sideNav.offsetWidth; // Force reflow
    void body.offsetWidth;
    
    sideNav.style.transition = '';
    body.style.transition = '';
}

function handleResponsive() {
    if (window.innerWidth <= 992) {
        body.classList.remove('collapsed-sidebar');
        sideNav.style.transform = 'translateX(-100%)';
    } else {
        initSidebar();
    }
}

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    handleResponsive();
    window.addEventListener('resize', handleResponsive);
});

// Gestion des clics
menuToggle?.addEventListener('click', (e) => {
    e.stopPropagation();
    toggleSidebar();
});

document.addEventListener('click', (e) => {
    if (window.innerWidth <= 992 && 
        !sideNav.contains(e.target) && 
        e.target !== menuToggle) {
        sideNav.style.transform = 'translateX(-100%)';
    }
});

// Gestion des popups
function openModulePopup() {
    const role = window.AppConfig?.userRole;
    const popupId = role === 'student' ? 'join-module-popup' : 
                   role === 'prof' ? 'create-module-popup' : null;
    
    if (popupId) {
        const popup = document.getElementById(popupId);
        if (popup) {
            popup.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
    }
}

function closeAllPopups() {
    document.querySelectorAll('.module-popup').forEach(popup => {
        popup.style.display = 'none';
    });
    document.body.style.overflow = '';
}

// Écouteurs d'événements
document.getElementById('module-action-trigger')?.addEventListener('click', (e) => {
    e.stopPropagation();
    openModulePopup();
});

document.getElementById('addModuleCard')?.addEventListener('click', (e) => {
    e.stopPropagation();
    openModulePopup();
});

document.addEventListener('click', (e) => {
    if (!e.target.closest('.popup-content') && 
        e.target.closest('.module-popup')) {
        closeAllPopups();
    }
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeAllPopups();
});