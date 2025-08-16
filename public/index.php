<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Antares</title>
    <meta
      name="description"
      content="AntaresTravels — Agencia de viajes boutique. Experiencias, aventuras y escapadas diseñadas con cariño y precisión."
    />
    <meta name="author" content="AntaresTravels" />
    <!-- Open Graph -->
    <meta property="og:title" content="AntaresTravels — Viaja con la luz del sur" />
    <meta
      property="og:description"
      content="Experiencias, aventuras y escapadas diseñadas con cariño y precisión."
    />
    <meta
      property="og:image"
      content="https://images.unsplash.com/photo-1502784444185-4e32a1b8d5a6?w=1200&q=80&auto=format&fit=crop"
    />
    <meta property="og:type" content="website" />

    <!-- Tailwind (CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      // Tailwind config (colores y tipografías personalizados)
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              antares: '#FF7A3D',
              night: '#0f172a',
              sand: '#F6E7D7',
            },
            fontFamily: {
              display: ['Inter', 'ui-sans-serif', 'system-ui'],
              accent: ['Merriweather', 'serif'],
            },
            animation: {
              'fade-in': 'fadeIn 0.8s ease-out forwards',
              'slide-up': 'slideUp 0.6s ease-out forwards',
              'bounce-gentle': 'bounceGentle 2s ease-in-out infinite',
              'pulse-glow': 'pulseGlow 2s ease-in-out infinite',
              float: 'float 3s ease-in-out infinite',
              wiggle: 'wiggle 1s ease-in-out infinite',
            },
          },
        },
      }
    </script>

    <!-- Librerías: Alpine (reactividad ligera), Swiper (carrusel), AOS (animaciones) -->
    <link rel="stylesheet" href="https://unpkg.com/swiper@9/swiper-bundle.min.css" />
    <link rel="stylesheet" href="https://unpkg.com/aos@2.3.4/dist/aos.css" />
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://unpkg.com/swiper@9/swiper-bundle.min.js"></script>
    <script defer src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>

    <!-- Iconos -->
    <script src="https://kit.fontawesome.com/yourkitid.js" crossorigin="anonymous"></script>

    <style>
      /* Animaciones personalizadas */
      @keyframes fadeIn {
        from {
          opacity: 0;
          transform: translateY(30px);
        }
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }

      @keyframes slideUp {
        from {
          opacity: 0;
          transform: translateY(50px);
        }
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }

      @keyframes bounceGentle {
        0%,
        100% {
          transform: translateY(0px);
        }
        50% {
          transform: translateY(-10px);
        }
      }

      @keyframes pulseGlow {
        0%,
        100% {
          box-shadow: 0 0 20px rgba(255, 107, 107, 0.4);
        }
        50% {
          box-shadow: 0 0 40px rgba(255, 107, 107, 0.8), 0 0 60px rgba(255, 107, 107, 0.4);
        }
      }

      @keyframes float {
        0%,
        100% {
          transform: translateY(0px);
        }
        50% {
          transform: translateY(-15px);
        }
      }

      @keyframes wiggle {
        0%,
        100% {
          transform: rotate(-3deg);
        }
        50% {
          transform: rotate(3deg);
        }
      }

      /* Efectos visuales mejorados */
      .hero-mask {
        background: linear-gradient(180deg, rgba(15, 23, 42, 0.6), rgba(15, 23, 42, 0.3));
      }

      .glass {
        background: rgba(255, 255, 255, 0.08);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.1);
      }

      .glass-card {
        background: linear-gradient(145deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
        backdrop-filter: blur(15px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        box-shadow: 0 25px 45px rgba(0, 0, 0, 0.1);
      }

      .hover-lift {
        transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
      }

      .hover-lift:hover {
        transform: translateY(-10px) scale(1.02);
        box-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
      }

      .btn-glow {
        box-shadow: 0 0 20px rgba(255, 107, 107, 0.3);
        transition: all 0.3s ease;
      }

      .btn-glow:hover {
        box-shadow: 0 0 30px rgba(255, 107, 107, 0.5), 0 0 40px rgba(255, 107, 107, 0.2);
        transform: translateY(-2px);
      }

      .text-glow {
        text-shadow: 0 0 20px rgba(255, 107, 107, 0.5);
      }

      .parallax-bg {
        background-attachment: fixed;
        background-position: center;
        background-repeat: no-repeat;
        background-size: cover;
      }

      /* Efectos de navbar */
      .navbar-scroll {
        background: rgba(15, 23, 42, 0.95);
        backdrop-filter: blur(20px);
        transition: all 0.3s ease;
      }

      /* Efectos de hover para imágenes */
      .image-hover-effect {
        overflow: hidden;
        position: relative;
      }

      .image-hover-effect::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(45deg, rgba(255, 107, 107, 0.1), rgba(255, 107, 107, 0.3));
        opacity: 0;
        transition: opacity 0.3s ease;
        z-index: 1;
      }

      .image-hover-effect:hover::before {
        opacity: 1;
      }

      .image-hover-effect img {
        transition: transform 0.5s cubic-bezier(0.165, 0.84, 0.44, 1);
      }

      .image-hover-effect:hover img {
        transform: scale(1.1);
      }

      /* Micro interacciones */
      .micro-bounce {
        animation: bounceGentle 2s ease-in-out infinite;
      }

      .pulse-on-hover:hover {
        animation: pulseGlow 1s ease-in-out infinite;
      }

      /* Responsive navigation */
      .nav-mobile {
        transform: translateX(-100%);
        transition: transform 0.3s ease;
      }

      .nav-mobile.open {
        transform: translateX(0);
      }

      /* Custom scrollbar */
      ::-webkit-scrollbar {
        width: 8px;
      }

      ::-webkit-scrollbar-track {
        background: rgba(15, 23, 42, 0.5);
      }

      ::-webkit-scrollbar-thumb {
        background: linear-gradient(45deg, #ff6b6b, #ff8a8a);
        border-radius: 4px;
      }

      ::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(45deg, #ff5252, #ff6b6b);
      }

      /* Loading states */
      .loading-shimmer {
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
        background-size: 200% 100%;
        animation: shimmer 2s infinite;
      }

      @keyframes shimmer {
        0% {
          background-position: -200% 0;
        }
        100% {
          background-position: 200% 0;
        }
      }
    </style>

    <!-- JSON-LD (SEO) -->
    <script type="application/ld+json">
      {
        "@context": "https://schema.org",
        "@type": "TravelAgency",
        "name": "AntaresTravels",
        "url": "https://antares-travels.example",
        "logo": "https://images.unsplash.com/photo-1502784444185-4e32a1b8d5a6?w=600&q=80&auto=format&fit=crop",
        "description": "Agencia boutique de viajes y experiencias.",
        "address": {
          "@type": "PostalAddress",
          "addressLocality": "Lima",
          "addressCountry": "PE"
        }
      }
    </script>
  </head>
  <body class="bg-night text-sand font-display leading-relaxed antialiased">
    <!-- NAVBAR -->
    <header class="fixed w-full z-50 transition-all duration-300" id="navbar">
      <nav class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
        <a href="#hero" class="flex items-center gap-3 text-white no-underline hover-lift">
          <svg
            class="w-10 h-10 rounded-full p-2 bg-antares shadow-md pulse-on-hover"
            viewBox="0 0 24 24"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
          >
            <path d="M12 2L15 9H9L12 2Z" fill="white" />
            <circle cx="12" cy="15" r="6" fill="white" opacity="0.15" />
          </svg>
          <div>
            <h1 class="text-lg font-semibold text-glow">AntaresTravels</h1>
            <p class="text-xs opacity-80 -mt-1">Viaja con la luz del sur</p>
          </div>
        </a>

        <div class="hidden md:flex items-center gap-6 text-sm">
          <a
            href="#destinos"
            class="hover:text-antares transition-all duration-300 hover:scale-105 relative group"
          >
            Destinos
            <span
              class="absolute bottom-0 left-0 w-0 h-0.5 bg-antares transition-all duration-300 group-hover:w-full"
            ></span>
          </a>
          <a
            href="#paquetes"
            class="hover:text-antares transition-all duration-300 hover:scale-105 relative group"
          >
            Paquetes
            <span
              class="absolute bottom-0 left-0 w-0 h-0.5 bg-antares transition-all duration-300 group-hover:w-full"
            ></span>
          </a>
          <a
            href="#testimonios"
            class="hover:text-antares transition-all duration-300 hover:scale-105 relative group"
          >
            Testimonios
            <span
              class="absolute bottom-0 left-0 w-0 h-0.5 bg-antares transition-all duration-300 group-hover:w-full"
            ></span>
          </a>
          <a
            href="#contacto"
            class="hover:text-antares transition-all duration-300 hover:scale-105 relative group"
          >
            Contacto
            <span
              class="absolute bottom-0 left-0 w-0 h-0.5 bg-antares transition-all duration-300 group-hover:w-full"
            ></span>
          </a>
          <button
            @click="$dispatch('open-booking')"
            class="ml-2 px-4 py-2 rounded-full bg-antares text-white font-semibold shadow-lg btn-glow"
          >
            Reservar
          </button>
        </div>

        <!-- mobile -->
        <div class="md:hidden">
          <button
            @click="mobileMenuOpen = !mobileMenuOpen"
            x-data="{mobileMenuOpen:false}"
            x-bind:aria-expanded="mobileMenuOpen"
            class="p-2 rounded-md glass hover-lift"
          >
            <svg
              x-show="!mobileMenuOpen"
              xmlns="http://www.w3.org/2000/svg"
              class="h-6 w-6 text-white transition-transform duration-300"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M4 6h16M4 12h16M4 18h16"
              />
            </svg>
            <svg
              x-show="mobileMenuOpen"
              xmlns="http://www.w3.org/2000/svg"
              class="h-6 w-6 text-white transition-transform duration-300 rotate-180"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
          </button>
        </div>
      </nav>
    </header>

    <!-- HERO -->
    <section id="hero" class="relative pt-24 min-h-screen flex items-center">
      <div class="absolute inset-0 overflow-hidden">
        <img
          src="https://images.unsplash.com/photo-1501785888041-af3ef285b470?w=1600&q=80&auto=format&fit=crop"
          alt="paisaje"
          class="w-full h-full object-cover brightness-75 parallax-bg"
        />
        <div class="absolute inset-0 hero-mask"></div>
      </div>

      <div class="relative max-w-7xl mx-auto px-6 py-28">
        <div class="grid md:grid-cols-2 gap-10 items-center">
          <div data-aos="fade-right" class="space-y-6">
            <h2
              class="text-4xl md:text-6xl font-extrabold text-white leading-tight text-glow micro-bounce"
            >
              AntaresTravels
            </h2>
            <p class="mt-4 text-lg md:text-xl text-sand/90 max-w-xl animate-fade-in">
              Creemos que cada viaje es una historia. Diseñamos experiencias que parecen poemas —
              intensidad, calma y sorpresa en la misma frase.
            </p>

            <div class="mt-8 flex gap-3">
              <a
                href="#paquetes"
                class="inline-flex items-center gap-3 px-6 py-3 rounded-full bg-antares text-white font-semibold shadow-lg btn-glow hover-lift"
                >Explorar paquetes</a
              >
              <button
                @click="$dispatch('open-booking')"
                class="inline-flex items-center gap-3 px-6 py-3 rounded-full border border-white/20 text-white glass hover-lift"
              >
                Cotizar ahora
              </button>
            </div>

            <div
              class="mt-8 grid grid-cols-3 gap-3 text-xs text-white/80"
              data-aos="fade-up"
              data-aos-delay="200"
            >
              <div class="flex flex-col hover-lift cursor-pointer">
                <span class="font-bold text-2xl text-antares">150+</span>
                <span>Destinos</span>
              </div>
              <div class="flex flex-col hover-lift cursor-pointer">
                <span class="font-bold text-2xl text-antares">8.9</span>
                <span>Puntuación media</span>
              </div>
              <div class="flex flex-col hover-lift cursor-pointer">
                <span class="font-bold text-2xl text-antares">10k+</span>
                <span>Viajeros felices</span>
              </div>
            </div>
          </div>

          <!-- Tarjeta destacada -->
          <div data-aos="fade-left" class="relative">
            <div
              class="rounded-3xl overflow-hidden shadow-2xl glass-card border border-white/10 hover-lift"
            >
              <div class="p-6">
                <h3 class="text-2xl font-semibold">Aventura Andina — 7 días</h3>
                <p class="mt-2 text-sm text-sand/90">
                  Ruta exclusiva: Lima — Paracas — Huacachina — Arequipa. Guías locales, transporte
                  premium y alojamientos boutique.
                </p>

                <ul class="mt-4 grid grid-cols-2 gap-2 text-xs text-sand/90">
                  <li class="flex items-center gap-1">
                    <span class="w-2 h-2 bg-antares rounded-full"></span>
                    Guía experto
                  </li>
                  <li class="flex items-center gap-1">
                    <span class="w-2 h-2 bg-antares rounded-full"></span>
                    Desayunos incluidos
                  </li>
                  <li class="flex items-center gap-1">
                    <span class="w-2 h-2 bg-antares rounded-full"></span>
                    Transporte privado
                  </li>
                  <li class="flex items-center gap-1">
                    <span class="w-2 h-2 bg-antares rounded-full"></span>
                    Soporte 24/7
                  </li>
                </ul>

                <div class="mt-6 flex items-center justify-between">
                  <div>
                    <span class="text-3xl font-bold text-antares">USD 1,490</span>
                    <div class="text-xs text-sand/70">por persona</div>
                  </div>
                  <div>
                    <button
                      @click="$dispatch('open-booking',{package: 'Aventura Andina - 7 días'})"
                      class="px-4 py-2 rounded-full bg-antares text-white font-semibold btn-glow hover-lift"
                    >
                      Reservar
                    </button>
                  </div>
                </div>
              </div>

              <div
                class="h-44 bg-[url('https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?w=1200&q=80&auto=format&fit=crop')] bg-cover bg-center image-hover-effect"
              ></div>
            </div>

            <!-- badge -->
            <div
              class="absolute -top-4 right-6 bg-white text-night px-3 py-1 rounded-full text-xs font-semibold shadow animate-wiggle"
            >
              Oferta limitada
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- DESTINOS (Swiper) -->
    <section id="destinos" class="py-20 bg-gradient-to-b from-night/50 to-night/80">
      <div class="max-w-7xl mx-auto px-6">
        <div class="flex items-center justify-between mb-8" data-aos="fade-up">
          <h3 class="text-3xl text-white font-bold">Destinos destacados</h3>
          <p class="text-sand/80">Viajes hechos con propósito — cultura, sabor y paisaje.</p>
        </div>

        <!-- Swiper -->
        <div
          class="swiper mySwiper rounded-2xl overflow-hidden shadow-xl hover-lift"
          data-aos="zoom-in"
        >
          <div class="swiper-wrapper">
            <!-- slide 1 -->
            <div class="swiper-slide relative image-hover-effect cursor-pointer">
              <img
                src="https://i.pinimg.com/736x/44/e6/3f/44e63fd319fe4ca864502c5819b45a6e.jpg"
                alt="Machu Picchu"
                class="w-full h-80 object-cover"
              />
              <div
                class="absolute bottom-4 left-4 bg-black/60 px-4 py-2 rounded-lg glass backdrop-blur-sm"
              >
                <h4 class="font-semibold text-white">Machu Picchu</h4>
                <p class="text-xs text-sand/90">Historia que respira en cada piedra.</p>
              </div>
              <div
                class="absolute top-4 right-4 w-8 h-8 bg-antares/80 rounded-full flex items-center justify-center text-white text-sm font-bold animate-bounce"
              >
                ★
              </div>
            </div>

            <!-- slide 2 -->
            <div class="swiper-slide relative image-hover-effect cursor-pointer">
              <img
                src="https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=1200&q=80&auto=format&fit=crop"
                alt="Lima"
                class="w-full h-80 object-cover"
              />
              <div
                class="absolute bottom-4 left-4 bg-black/60 px-4 py-2 rounded-lg glass backdrop-blur-sm"
              >
                <h4 class="font-semibold text-white">Lima Gastronómica</h4>
                <p class="text-xs text-sand/90">Sabores que cuentan historias.</p>
              </div>
              <div
                class="absolute top-4 right-4 w-8 h-8 bg-antares/80 rounded-full flex items-center justify-center text-white text-sm font-bold animate-bounce"
              >
                ★
              </div>
            </div>

            <!-- slide 3 -->
            <div class="swiper-slide relative image-hover-effect cursor-pointer">
              <img
                src="https://i.pinimg.com/1200x/88/28/51/882851a17239571370fff28983f2d736.jpg"
                alt="Paracas"
                class="w-full h-80 object-cover"
              />
              <div
                class="absolute bottom-4 left-4 bg-black/60 px-4 py-2 rounded-lg glass backdrop-blur-sm"
              >
                <h4 class="font-semibold text-white">Paracas</h4>
                <p class="text-xs text-sand/90">Mar y viento, para el alma.</p>
              </div>
              <div
                class="absolute top-4 right-4 w-8 h-8 bg-antares/80 rounded-full flex items-center justify-center text-white text-sm font-bold animate-bounce"
              >
                ★
              </div>
            </div>
          </div>
          <!-- navigation -->
          <div
            class="swiper-button-prev text-white hover:text-antares transition-colors duration-300"
          ></div>
          <div
            class="swiper-button-next text-white hover:text-antares transition-colors duration-300"
          ></div>
          <div class="swiper-pagination"></div>
        </div>
      </div>
    </section>

    <!-- PAQUETES -->
    <section id="paquetes" class="py-20 bg-gradient-to-b from-night/80 to-night/60">
      <div class="max-w-7xl mx-auto px-6">
        <h3 class="text-3xl text-white font-bold mb-6" data-aos="fade-up">Paquetes populares</h3>
        <div class="grid md:grid-cols-3 gap-6">
          <!-- card paquete -->
          <article
            class="rounded-2xl overflow-hidden glass-card border border-white/5 shadow-lg hover-lift cursor-pointer group"
            data-aos="fade-up"
          >
            <div class="image-hover-effect">
              <img
                src="https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?w=1200&q=80&auto=format&fit=crop"
                alt="paquete1"
                class="w-full h-44 object-cover"
              />
            </div>
            <div class="p-5">
              <h4
                class="text-xl font-semibold group-hover:text-antares transition-colors duration-300"
              >
                Norte Místico — 5 días
              </h4>
              <p class="mt-2 text-sm text-sand/90">
                Playas secretas, ruinas y gastronomía norteña.
              </p>
              <div class="mt-4 flex items-center justify-between">
                <div>
                  <span class="text-2xl font-bold text-antares">USD 980</span>
                  <div class="text-xs text-sand/70">por persona</div>
                </div>
                <button
                  @click="$dispatch('open-booking',{package:'Norte Místico - 5 días'})"
                  class="px-4 py-2 rounded-full bg-antares text-white btn-glow hover-lift"
                >
                  Reservar
                </button>
              </div>
            </div>
          </article>

          <!-- card paquete 2 -->
          <article
            class="rounded-2xl overflow-hidden glass-card border border-white/5 shadow-lg hover-lift cursor-pointer group"
            data-aos="fade-up"
            data-aos-delay="100"
          >
            <div class="image-hover-effect">
              <img
                src="https://images.unsplash.com/photo-1501785888041-af3ef285b470?w=1200&q=80&auto=format&fit=crop"
                alt="paquete2"
                class="w-full h-44 object-cover"
              />
            </div>
            <div class="p-5">
              <h4
                class="text-xl font-semibold group-hover:text-antares transition-colors duration-300"
              >
                Amazonía Selecta — 6 días
              </h4>
              <p class="mt-2 text-sm text-sand/90">
                Selva profunda, lodges sostenibles y rutas privadas.
              </p>
              <div class="mt-4 flex items-center justify-between">
                <div>
                  <span class="text-2xl font-bold text-antares">USD 1,290</span>
                  <div class="text-xs text-sand/70">por persona</div>
                </div>
                <button
                  @click="$dispatch('open-booking',{package:'Amazonía Selecta - 6 días'})"
                  class="px-4 py-2 rounded-full bg-antares text-white btn-glow hover-lift"
                >
                  Reservar
                </button>
              </div>
            </div>
          </article>

          <!-- card paquete 3 -->
          <article
            class="rounded-2xl overflow-hidden glass-card border border-white/5 shadow-lg hover-lift cursor-pointer group"
            data-aos="fade-up"
            data-aos-delay="200"
          >
            <div class="image-hover-effect">
              <img
                src="https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=1200&q=80&auto=format&fit=crop"
                alt="paquete3"
                class="w-full h-44 object-cover"
              />
            </div>
            <div class="p-5">
              <h4
                class="text-xl font-semibold group-hover:text-antares transition-colors duration-300"
              >
                Islas Encantadas — 4 días
              </h4>
              <p class="mt-2 text-sm text-sand/90">Playas, snorkel y descanso absoluto.</p>
              <div class="mt-4 flex items-center justify-between">
                <div>
                  <span class="text-2xl font-bold text-antares">USD 720</span>
                  <div class="text-xs text-sand/70">por persona</div>
                </div>
                <button
                  @click="$dispatch('open-booking',{package:'Islas Encantadas - 4 días'})"
                  class="px-4 py-2 rounded-full bg-antares text-white btn-glow hover-lift"
                >
                  Reservar
                </button>
              </div>
            </div>
          </article>
        </div>
      </div>
    </section>

    <!-- TESTIMONIOS -->
    <section id="testimonios" class="py-20">
      <div class="max-w-4xl mx-auto px-6 text-center">
        <h3 class="text-3xl text-white font-bold" data-aos="fade-up">
          Lo que dicen nuestros viajeros
        </h3>
        <p class="mt-4 text-sand/80" data-aos="fade-up" data-aos-delay="100">
          Palabras que nos sostienen — y que nos impulsan a seguir creando.
        </p>

        <div class="mt-10 grid md:grid-cols-3 gap-6">
          <blockquote
            class="p-6 glass-card rounded-2xl text-left shadow hover-lift cursor-pointer group"
            data-aos="flip-left"
          >
            <div class="text-4xl text-antares mb-2 group-hover:animate-bounce">"</div>
            <p class="group-hover:text-white transition-colors duration-300">
              "Viajar con Antares fue como entrar a un libro: cada página mejor que la anterior."
            </p>
            <footer class="mt-4 text-xs text-sand/70 flex items-center gap-2">
              <div
                class="w-8 h-8 bg-gradient-to-r from-antares to-pink-500 rounded-full flex items-center justify-center text-white text-xs font-bold"
              >
                M
              </div>
              — Mariana R., Lima
            </footer>
          </blockquote>

          <blockquote
            class="p-6 glass-card rounded-2xl text-left shadow hover-lift cursor-pointer group"
            data-aos="flip-left"
            data-aos-delay="100"
          >
            <div class="text-4xl text-antares mb-2 group-hover:animate-bounce">"</div>
            <p class="group-hover:text-white transition-colors duration-300">
              "Atención impecable y rutas llenas de sorpresas. Repetiré el próximo año."
            </p>
            <footer class="mt-4 text-xs text-sand/70 flex items-center gap-2">
              <div
                class="w-8 h-8 bg-gradient-to-r from-blue-500 to-antares rounded-full flex items-center justify-center text-white text-xs font-bold"
              >
                J
              </div>
              — Jorge C., Arequipa
            </footer>
          </blockquote>

          <blockquote
            class="p-6 glass-card rounded-2xl text-left shadow hover-lift cursor-pointer group"
            data-aos="flip-left"
            data-aos-delay="200"
          >
            <div class="text-4xl text-antares mb-2 group-hover:animate-bounce">"</div>
            <p class="group-hover:text-white transition-colors duration-300">
              "Un viaje íntimo y profundo. Me trajeron de vuelta más que fotos."
            </p>
            <footer class="mt-4 text-xs text-sand/70 flex items-center gap-2">
              <div
                class="w-8 h-8 bg-gradient-to-r from-purple-500 to-antares rounded-full flex items-center justify-center text-white text-xs font-bold"
              >
                L
              </div>
              — Lucía M., Cusco
            </footer>
          </blockquote>
        </div>
      </div>
    </section>

    <!-- GALERÍA DE IMÁGENES -->
    <section class="py-20 bg-night/90">
      <div class="max-w-7xl mx-auto px-6">
        <h3 class="text-3xl text-white font-bold" data-aos="fade-up">Galería</h3>
        <p class="text-sand/80 mt-2" data-aos="fade-up" data-aos-delay="100">
          Fotos tomadas por nuestros viajeros y colaboradores.
        </p>

        <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-4">
          <div
            class="image-hover-effect rounded-xl overflow-hidden cursor-pointer hover-lift"
            data-aos="zoom-in"
          >
            <img
              src="https://images.unsplash.com/photo-1500534314209-a25ddb2bd429?w=800&q=60&auto=format&fit=crop"
              alt="gal1"
              class="w-full h-48 object-cover"
            />
          </div>
          <div
            class="image-hover-effect rounded-xl overflow-hidden cursor-pointer hover-lift"
            data-aos="zoom-in"
            data-aos-delay="100"
          >
            <img
              src="https://images.unsplash.com/photo-1501785888041-af3ef285b470?w=800&q=60&auto=format&fit=crop"
              alt="gal2"
              class="w-full h-48 object-cover"
            />
          </div>
          <div
            class="image-hover-effect rounded-xl overflow-hidden cursor-pointer hover-lift"
            data-aos="zoom-in"
            data-aos-delay="200"
          >
            <img
              src="https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=800&q=60&auto=format&fit=crop"
              alt="gal3"
              class="w-full h-48 object-cover"
            />
          </div>
          <div
            class="image-hover-effect rounded-xl overflow-hidden cursor-pointer hover-lift"
            data-aos="zoom-in"
            data-aos-delay="300"
          >
            <img
              src="https://images.unsplash.com/photo-1501594907352-04cda38ebc29?w=800&q=60&auto=format&fit=crop"
              alt="gal4"
              class="w-full h-48 object-cover"
            />
          </div>
        </div>
      </div>
    </section>

    <!-- CONTACTO + FORMULARIO -->
    <section id="contacto" class="py-20">
      <div class="max-w-5xl mx-auto px-6 grid md:grid-cols-2 gap-8 items-center">
        <div data-aos="fade-right">
          <h3 class="text-3xl text-white font-bold">Hablemos de tu próximo viaje</h3>
          <p class="mt-4 text-sand/80">
            Cuéntanos tu idea, fecha y cuántos serán. Nosotros nos ocupamos del resto — y te
            convertimos la espera en emoción.
          </p>

          <ul class="mt-6 text-sand/80 text-sm space-y-3">
            <li
              class="flex items-center gap-3 hover:text-white transition-colors duration-300 cursor-pointer"
            >
              <div class="w-8 h-8 bg-antares rounded-full flex items-center justify-center">📞</div>
              <strong>Tel:</strong> +51 123 456 789
            </li>
            <li
              class="flex items-center gap-3 hover:text-white transition-colors duration-300 cursor-pointer"
            >
              <div class="w-8 h-8 bg-antares rounded-full flex items-center justify-center">✉️</div>
              <strong>Email:</strong> hola@antares-travels.example
            </li>
            <li
              class="flex items-center gap-3 hover:text-white transition-colors duration-300 cursor-pointer"
            >
              <div class="w-8 h-8 bg-antares rounded-full flex items-center justify-center">📍</div>
              <strong>Oficina:</strong> Av. Ejemplo 123, Lima, Perú
            </li>
          </ul>
        </div>

        <form
          id="contactForm"
          class="glass-card p-6 rounded-2xl border border-white/5 shadow hover-lift"
          data-aos="fade-left"
          @submit.prevent="submitContact()"
          x-data="contactForm()"
        >
          <div class="space-y-4">
            <div>
              <label class="block text-xs text-sand/70 mb-1">Nombre</label>
              <input
                x-model="name"
                required
                class="w-full px-3 py-3 rounded-lg bg-transparent border border-white/20 focus:outline-none focus:ring-2 focus:ring-antares focus:border-transparent transition-all duration-300 hover:border-white/40"
                placeholder="Tu nombre"
              />
            </div>

            <div>
              <label class="block text-xs text-sand/70 mb-1">Email</label>
              <input
                x-model="email"
                type="email"
                required
                class="w-full px-3 py-3 rounded-lg bg-transparent border border-white/20 focus:outline-none focus:ring-2 focus:ring-antares focus:border-transparent transition-all duration-300 hover:border-white/40"
                placeholder="correo@ejemplo.com"
              />
            </div>

            <div>
              <label class="block text-xs text-sand/70 mb-1">Mensaje</label>
              <textarea
                x-model="message"
                rows="4"
                required
                class="w-full px-3 py-3 rounded-lg bg-transparent border border-white/20 focus:outline-none focus:ring-2 focus:ring-antares focus:border-transparent transition-all duration-300 hover:border-white/40 resize-none"
                placeholder="Cuéntanos tu idea..."
              ></textarea>
            </div>
          </div>

          <div class="mt-6 flex items-center justify-between">
            <div class="text-sm text-sand/70 flex items-center gap-2">
              <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse" x-show="!sending"></div>
              <div class="w-2 h-2 rounded-full bg-yellow-500 animate-bounce" x-show="sending"></div>
              Estado: <span x-text="sending ? 'Enviando...' : 'Listo'"></span>
            </div>
            <button
              type="submit"
              class="px-6 py-3 rounded-full bg-antares text-white font-semibold btn-glow hover-lift"
              :class="{ 'opacity-50 cursor-not-allowed': sending }"
              :disabled="sending"
            >
              <span x-show="!sending">Enviar mensaje</span>
              <span x-show="sending" class="flex items-center gap-2">
                <div
                  class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"
                ></div>
                Enviando...
              </span>
            </button>
          </div>
        </form>
      </div>
    </section>

    <!-- FOOTER -->
    <footer class="py-12 bg-night/95 border-t border-white/10">
      <div
        class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row items-center justify-between gap-4"
      >
        <div class="flex items-center gap-4">
          <div
            class="w-10 h-10 bg-antares rounded-full flex items-center justify-center animate-pulse-glow"
          >
            <span class="text-white font-bold text-sm">AT</span>
          </div>
          <div class="text-sand/80 text-sm">
            © <span id="year"></span> AntaresTravels. Todos los derechos reservados.
          </div>
        </div>
        <div class="flex gap-6">
          <a
            href="#"
            class="text-sand/80 hover:text-antares transition-all duration-300 hover:scale-110"
            >Política de privacidad</a
          >
          <a
            href="#"
            class="text-sand/80 hover:text-antares transition-all duration-300 hover:scale-110"
            >Términos</a
          >
          <div class="flex gap-3">
            <a
              href="#"
              class="w-8 h-8 bg-white/10 rounded-full flex items-center justify-center hover:bg-antares transition-all duration-300 hover:scale-110"
            >
              📘
            </a>
            <a
              href="#"
              class="w-8 h-8 bg-white/10 rounded-full flex items-center justify-center hover:bg-antares transition-all duration-300 hover:scale-110"
            >
              📷
            </a>
            <a
              href="#"
              class="w-8 h-8 bg-white/10 rounded-full flex items-center justify-center hover:bg-antares transition-all duration-300 hover:scale-110"
            >
              🐦
            </a>
          </div>
        </div>
      </div>
    </footer>

    <!-- BOOKING MODAL (alpine) -->
    <div
      x-data="bookingModal()"
      x-show="open"
      x-cloak
      class="fixed inset-0 z-50 flex items-center justify-center p-6"
      x-transition:enter="transition ease-out duration-300"
      x-transition:enter-start="opacity-0"
      x-transition:enter-end="opacity-100"
      x-transition:leave="transition ease-in duration-200"
      x-transition:leave-start="opacity-100"
      x-transition:leave-end="opacity-0"
    >
      <div @click="open=false" class="absolute inset-0 bg-black/70 backdrop-blur-sm"></div>
      <div
        class="relative max-w-xl w-full glass-card rounded-2xl p-6 border border-white/10 shadow-2xl hover-lift"
        @keydown.escape.window="open=false"
        x-transition:enter="transition ease-out duration-300 delay-150"
        x-transition:enter-start="opacity-0 scale-75"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-75"
      >
        <header class="flex items-start justify-between gap-4">
          <div>
            <h4 class="text-xl font-bold text-white">Reservar paquete</h4>
            <p class="text-sand/80 text-sm">Dinos quién eres y cuándo quieres viajar.</p>
          </div>
          <button
            @click="open=false"
            class="text-sand/70 hover:text-antares transition-colors duration-300 text-2xl hover:rotate-90 transition-transform"
          >
            ✕
          </button>
        </header>

        <form class="mt-6 space-y-4" @submit.prevent="submitBooking()">
          <div class="grid md:grid-cols-2 gap-4">
            <input
              x-model="form.name"
              required
              placeholder="Nombre completo"
              class="w-full px-4 py-3 rounded-lg bg-transparent border border-white/20 focus:outline-none focus:ring-2 focus:ring-antares focus:border-transparent transition-all duration-300 hover:border-white/40"
            />
            <input
              x-model="form.email"
              required
              type="email"
              placeholder="Email"
              class="w-full px-4 py-3 rounded-lg bg-transparent border border-white/20 focus:outline-none focus:ring-2 focus:ring-antares focus:border-transparent transition-all duration-300 hover:border-white/40"
            />
          </div>

          <input
            x-model="form.package"
            placeholder="Paquete seleccionado"
            class="w-full px-4 py-3 rounded-lg bg-transparent border border-white/20 focus:outline-none focus:ring-2 focus:ring-antares focus:border-transparent transition-all duration-300 hover:border-white/40"
            readonly
          />

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs text-sand/70 mb-1">Fecha de viaje</label>
              <input
                x-model="form.date"
                type="date"
                class="w-full px-4 py-3 rounded-lg bg-transparent border border-white/20 focus:outline-none focus:ring-2 focus:ring-antares focus:border-transparent transition-all duration-300 hover:border-white/40"
              />
            </div>
            <div>
              <label class="block text-xs text-sand/70 mb-1">Personas</label>
              <input
                x-model="form.people"
                type="number"
                min="1"
                max="20"
                value="1"
                class="w-full px-4 py-3 rounded-lg bg-transparent border border-white/20 focus:outline-none focus:ring-2 focus:ring-antares focus:border-transparent transition-all duration-300 hover:border-white/40"
                @input="calcEstimate()"
              />
            </div>
          </div>

          <div class="mt-6 p-4 bg-white/5 rounded-lg border border-white/10">
            <div class="flex items-center justify-between">
              <div class="text-sand/80 text-sm">Total estimado:</div>
              <div class="text-xl font-bold text-antares" x-text="estimate"></div>
            </div>
            <div class="text-xs text-sand/60 mt-1">*Precio aproximado, sujeto a confirmación</div>
          </div>

          <div class="flex items-center justify-between pt-4">
            <button
              type="button"
              @click="open=false"
              class="px-4 py-2 rounded-lg border border-white/20 text-sand/80 hover:text-white hover:border-white/40 transition-all duration-300"
            >
              Cancelar
            </button>
            <button
              type="submit"
              class="px-6 py-3 rounded-full bg-antares text-white font-semibold btn-glow hover-lift"
              :class="{ 'opacity-50 cursor-not-allowed': submitting }"
              :disabled="submitting"
            >
              <span x-show="!submitting">Solicitar cotización</span>
              <span x-show="submitting" class="flex items-center gap-2">
                <div
                  class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"
                ></div>
                Procesando...
              </span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Scroll to top button -->
    <button
      id="scrollToTop"
      class="fixed bottom-6 right-6 w-12 h-12 bg-antares rounded-full shadow-lg btn-glow hover-lift hidden z-40 flex items-center justify-center text-white"
      onclick="scrollToTop()"
    >
      ↑
    </button>

    <!-- SCRIPTS: Inicialización y lógica -->
    <script>
      // Iniciadores de UI
      document.addEventListener('DOMContentLoaded', function () {
        AOS.init({
          duration: 800,
          once: true,
          offset: 100,
          easing: 'ease-out-cubic',
        })

        // Swiper init con efectos mejorados
        const swiper = new Swiper('.mySwiper', {
          slidesPerView: 1.2,
          spaceBetween: 16,
          centeredSlides: true,
          loop: true,
          autoplay: {
            delay: 4000,
            disableOnInteraction: false,
          },
          effect: 'coverflow',
          coverflowEffect: {
            rotate: 30,
            stretch: 0,
            depth: 100,
            modifier: 1,
            slideShadows: true,
          },
          pagination: {
            el: '.swiper-pagination',
            clickable: true,
            dynamicBullets: true,
          },
          navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
          },
          breakpoints: {
            768: { slidesPerView: 2.2 },
            1024: { slidesPerView: 3 },
          },
        })

        // Navbar scroll effect
        const navbar = document.getElementById('navbar')
        const scrollToTopBtn = document.getElementById('scrollToTop')

        window.addEventListener('scroll', () => {
          if (window.scrollY > 100) {
            navbar.classList.add('navbar-scroll')
            scrollToTopBtn.classList.remove('hidden')
          } else {
            navbar.classList.remove('navbar-scroll')
            scrollToTopBtn.classList.add('hidden')
          }
        })

        // year
        document.getElementById('year').textContent = new Date().getFullYear()

        // Smooth scrolling para enlaces internos
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
          anchor.addEventListener('click', function (e) {
            e.preventDefault()
            const target = document.querySelector(this.getAttribute('href'))
            if (target) {
              target.scrollIntoView({
                behavior: 'smooth',
                block: 'start',
              })
            }
          })
        })

        // Parallax effect
        window.addEventListener('scroll', () => {
          const scrolled = window.pageYOffset
          const parallaxElements = document.querySelectorAll('.parallax-bg')

          parallaxElements.forEach(element => {
            const speed = 0.5
            element.style.transform = `translateY(${scrolled * speed}px)`
          })
        })

        // Loading states
        const images = document.querySelectorAll('img')
        images.forEach(img => {
          img.addEventListener('load', function () {
            this.classList.add('loaded')
          })
        })
      })

      // Scroll to top function
      function scrollToTop() {
        window.scrollTo({
          top: 0,
          behavior: 'smooth',
        })
      }

      // Enhanced booking modal
      function bookingModal() {
        return {
          open: false,
          submitting: false,
          form: {
            name: '',
            email: '',
            package: '',
            date: '',
            people: 1,
          },
          estimate: 'USD 720',

          calcEstimate() {
            const packages = {
              'Aventura Andina - 7 días': 1490,
              'Norte Místico - 5 días': 980,
              'Amazonía Selecta - 6 días': 1290,
              'Islas Encantadas - 4 días': 720,
            }

            const basePrice = packages[this.form.package] || 720
            const people = Number(this.form.people) || 1
            const total = basePrice * people

            this.estimate = 'USD ' + total.toLocaleString()
          },

          async submitBooking() {
            if (!this.form.name || !this.form.email) {
              this.showNotification('Por favor completa todos los campos requeridos', 'error')
              return
            }

            this.submitting = true

            // Simulación de envío con delay realista
            await new Promise(resolve => setTimeout(resolve, 2000))

            this.submitting = false
            this.showNotification(
              `¡Gracias ${this.form.name}! Hemos recibido tu solicitud de cotización. Te contactaremos pronto al ${this.form.email}`,
              'success'
            )

            // Reset form
            this.form = { name: '', email: '', package: '', date: '', people: 1 }
            this.calcEstimate()
            this.open = false
          },

          showNotification(message, type) {
            // Simple notification system
            const notification = document.createElement('div')
            notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-md transform transition-all duration-300 ${
              type === 'success' ? 'bg-green-600' : 'bg-red-600'
            } text-white`
            notification.textContent = message

            document.body.appendChild(notification)

            // Animate in
            setTimeout(() => notification.classList.add('translate-x-0'), 100)

            // Remove after delay
            setTimeout(() => {
              notification.classList.add('translate-x-full', 'opacity-0')
              setTimeout(() => notification.remove(), 300)
            }, 5000)
          },

          init() {
            // Listen for booking events
            document.addEventListener('open-booking', e => {
              this.open = true
              if (e.detail && e.detail.package) {
                this.form.package = e.detail.package
              }
              this.calcEstimate()
            })

            // Set minimum date to today
            const today = new Date().toISOString().split('T')[0]
            document.addEventListener('DOMContentLoaded', () => {
              const dateInput = document.querySelector('input[type="date"]')
              if (dateInput) dateInput.min = today
            })
          },
        }
      }

      // Enhanced contact form
      function contactForm() {
        return {
          name: '',
          email: '',
          message: '',
          sending: false,

          async submitContact() {
            if (!this.name || !this.email || !this.message) {
              this.showNotification('Por favor completa todos los campos', 'error')
              return
            }

            if (!this.isValidEmail(this.email)) {
              this.showNotification('Por favor ingresa un email válido', 'error')
              return
            }

            this.sending = true

            // Simulación de envío
            await new Promise(resolve => setTimeout(resolve, 1500))

            this.sending = false
            this.showNotification(
              `¡Gracias ${this.name}! Tu mensaje ha sido enviado correctamente.`,
              'success'
            )

            // Reset form
            this.name = ''
            this.email = ''
            this.message = ''
          },

          isValidEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
            return re.test(email)
          },

          showNotification(message, type) {
            const notification = document.createElement('div')
            notification.className = `fixed top-4 left-1/2 transform -translate-x-1/2 z-50 p-4 rounded-lg shadow-lg ${
              type === 'success' ? 'bg-green-600' : 'bg-red-600'
            } text-white max-w-md transition-all duration-300`
            notification.textContent = message

            document.body.appendChild(notification)

            setTimeout(() => {
              notification.classList.add('opacity-0', '-translate-y-2')
              setTimeout(() => notification.remove(), 300)
            }, 4000)
          },
        }
      }

      // Declaramos los hooks globalmente
      window.bookingModal = bookingModal
      window.contactForm = contactForm
    </script>

    <!-- Accessibility: focus outline + skip link -->
    <a
      href="#hero"
      class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-white text-night px-3 py-2 rounded z-50"
      >Saltar al contenido</a
    >
  </body>
</html>
