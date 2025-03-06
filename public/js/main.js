// Fonction pour basculer entre le mode clair et sombre
const themeToggle = document.getElementById('theme-toggle');
const body = document.body;

themeToggle.addEventListener('click', () => {
    body.classList.toggle('dark-mode');
    if (body.classList.contains('dark-mode')) {
        themeToggle.textContent = 'Mode Clair';
    } else {
        themeToggle.textContent = 'Mode Sombre';
    }
});

// Validation du formulaire de contact
const contactForm = document.getElementById('contact-form');

contactForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const name = document.getElementById('name').value;
    const email = document.getElementById('email').value;
    const message = document.getElementById('message').value;

    if (name && email && message) {
        alert('Message envoyé avec succès !');
        contactForm.reset();
    } else {
        alert('Veuillez remplir tous les champs.');
    }
});