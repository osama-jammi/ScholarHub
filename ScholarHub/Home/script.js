// Dark Mode Toggle
const themeToggle = document.getElementById('theme-toggle');
themeToggle.addEventListener('click', () => {
    document.body.classList.toggle('dark-mode');
    themeToggle.querySelector('i').classList.toggle('fa-sun');
});

// Scroll Progress
window.addEventListener('scroll', () => {
    const progress = document.querySelector('.progress-fill');
    const windowHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
    const scrolled = (window.scrollY / windowHeight) * 100;
    progress.style.width = scrolled + '%';
});