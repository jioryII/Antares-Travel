// Inicializar AOS
AOS.init({
  duration: 800,
  offset: 100,
  once: true,
})

// Manejar navegación suave
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
  anchor.addEventListener('click', function (e) {
    e.preventDefault()
    document.querySelector(this.getAttribute('href')).scrollIntoView({
      behavior: 'smooth',
    })
  })
})

// Animación del navbar al hacer scroll
window.addEventListener('scroll', function () {
  const nav = document.querySelector('nav')
  if (window.scrollY > 50) {
    nav.classList.add('shadow-lg')
  } else {
    nav.classList.remove('shadow-lg')
  }
})


const langButtons = document.querySelectorAll('.lang-btn');
const langElements = document.querySelectorAll('[data-es][data-en]');
let currentLang = localStorage.getItem('language') || 'es';

function updateLanguage(lang) {
    langElements.forEach(element => {
        const text = element.getAttribute(`data-${lang}`);
        if (text) {
            const icon = element.querySelector('i');
            const span = element.querySelector('span');

            if (span) {
                span.textContent = text;
            } else if (!icon) {
                element.textContent = text;
            } else {
                element.innerHTML = `${icon.outerHTML} <span>${text}</span>`;
            }
        }
    });
    document.documentElement.lang = lang;
    currentLang = lang;
    localStorage.setItem('language', lang);

    langButtons.forEach(btn => {
        btn.classList.toggle('active', btn.getAttribute('data-lang') === lang);
    });
}

langButtons.forEach(btn => {
    btn.addEventListener('click', () => {
        const lang = btn.getAttribute('data-lang');
        updateLanguage(lang);
    });
});

updateLanguage(currentLang);