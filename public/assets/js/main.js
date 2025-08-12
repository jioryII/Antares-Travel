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
