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

 // Toast Initialization
 const toast = new bootstrap.Toast(document.getElementById('liveToast'));
 
 document.querySelectorAll('form').forEach(form => {
     form.addEventListener('submit', (e) => {
         e.preventDefault();
         toast.show();
     });
 });

 // Skeleton Loading Simulation
 setTimeout(() => {
     document.querySelector('.skeleton-loader').style.display = 'none';
 }, 2000);