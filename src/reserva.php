<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservar Tour - Antares Travel</title>
    <link rel="icon" type="image/png" href="../../imagenes/antares_logozz3.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
    <style>
        :root {
            --primary-bg: #FFFAF0;
            --primary-color: #A27741;
            --primary-dark: #8B6332;
            --primary-light: #B8926A;
            --secondary-color: #5B797C;
            --text-dark: #2c2c2c;
            --text-light: #666;
            --white: #ffffff;
            --transition: all 0.3s ease;
            --shadow: 0 8px 24px rgba(162, 119, 65, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: var(--text-dark);
            background: var(--primary-bg);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(255, 250, 240, 0.95);
            backdrop-filter: blur(10px);
            z-index: 1000;
            padding: 1rem 0;
            border-bottom: 1px solid rgba(162, 119, 65, 0.1);
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 2rem;
        }

        .logo {
            display: flex;
            align-items: center;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            text-decoration: none;
            gap: 10px;
        }

        .auth-buttons {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .lang-switch {
            display: flex;
            border: 2px solid var(--primary-color);
            border-radius: 25px;
            overflow: hidden;
        }

        .lang-btn {
            padding: 0.5rem 1rem;
            border: none;
            background: transparent;
            color: var(--primary-color);
            cursor: pointer;
            transition: var(--transition);
            font-weight: 600;
        }

        .lang-btn.active {
            background: var(--primary-color);
            color: var(--white);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.7rem 1.5rem;
            border: none;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            cursor: pointer;
        }

        .btn-primary {
            background: var(--primary-color);
            color: var(--white);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .section {
            padding: 80px 0;
        }

        .section-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .section-title {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: var(--primary-light);
        }

        .section-subtitle {
            font-size: 1.1rem;
            color: var(--text-light);
            max-width: 600px;
            margin: 0 auto;
        }

        .reservation-form {
            background: var(--white);
            padding: 2rem;
            border-radius: 10px;
            box-shadow: var(--shadow);
            max-width: 800px;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-weight: 500;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--primary-light);
            border-radius: 5px;
            font-size: 1rem;
            transition: var(--transition);
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 5px rgba(162, 119, 65, 0.3);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .passenger-section {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--primary-light);
        }

        .passenger-section h3 {
            font-size: 1.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .passenger-group {
            background: var(--primary-bg);
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .section-title {
                font-size: 2rem;
            }

            .reservation-form {
                padding: 1.5rem;
            }
        }

        /* Google Translate Styling */
        .goog-te-gadget {
            font-size: 0;
        }

        .goog-te-combo {
            display: none !important;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="../../index.html" class="logo">
                <img src="../../imagenes/antares_logozz2.png" alt="Antares Travel Logo" height="50" loading="lazy">
                ANTARES TRAVEL
            </a>
            <div class="auth-buttons">
                <div class="lang-switch">
                    <button class="lang-btn active" data-lang="es">ES</button>
                    <button class="lang-btn" data-lang="en">EN</button>
                </div>
            </div>
        </div>
    </nav>

    <section class="section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Reservar Tour: Ejemplo Tour</h2>
                <p class="section-subtitle">Complete los detalles para reservar su experiencia con Antares Travel</p>
            </div>

            <div class="reservation-form">
                <form id="reservationForm">
                    <div class="form-group">
                        <label for="fecha_tour">Fecha del Tour</label>
                        <input type="date" id="fecha_tour" name="fecha_tour" required 
                               min="2025-09-02">
                    </div>

                    <div class="form-group">
                        <label for="num_adultos">Número de Adultos</label>
                        <input type="number" id="num_adultos" name="num_adultos" min="1" value="1" required>
                    </div>

                    <div class="form-group">
                        <label for="num_ninos">Número de Niños</label>
                        <input type="number" id="num_ninos" name="num_ninos" min="0" value="0">
                    </div>

                    <div class="passenger-section" id="passengerSection">
                        <h3>Información de Pasajeros</h3>
                        <div id="passengerForms"></div>
                    </div>

                    <div class="form-group">
                        <label for="observaciones">Observaciones</label>
                        <textarea id="observaciones" name="observaciones" placeholder="Alguna solicitud especial o información adicional"></textarea>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-calendar-check"></i> Confirmar Reserva
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <script>
        // Google Translate Initialization
        function googleTranslateElementInit() {
            new google.translate.TranslateElement({
                pageLanguage: 'es',
                includedLanguages: 'es,en',
                layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
                autoDisplay: false
            }, 'google_translate_element');
            
            document.querySelector('.goog-te-combo').style.display = 'none';
        }

        // Language switch
        function initializeLanguageSwitch() {
            const langButtons = document.querySelectorAll('.lang-btn');
            
            langButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const lang = button.dataset.lang;
                    switchLanguage(lang);
                    langButtons.forEach(btn => btn.classList.remove('active'));
                    button.classList.add('active');
                });
            });
        }

        function switchLanguage(lang) {
            const translateSelect = document.querySelector('.goog-te-combo');
            if (translateSelect) {
                translateSelect.value = lang;
                translateSelect.dispatchEvent(new Event('change'));
            }
        }

        // Dynamic passenger form fields
        function updatePassengerForms() {
            const numAdultos = parseInt(document.getElementById('num_adultos').value) || 0;
            const numNinos = parseInt(document.getElementById('num_ninos').value) || 0;
            const passengerForms = document.getElementById('passengerForms');
            passengerForms.innerHTML = '';

            for (let i = 0; i < numAdultos + numNinos; i++) {
                const type = i < numAdultos ? 'Adulto' : 'Niño';
                const formHtml = `
                    <div class="passenger-group">
                        <h4>Pasajero ${i + 1} (${type})</h4>
                        <div class="form-group">
                            <label>Nombre</label>
                            <input type="text" name="nombre[]" required>
                        </div>
                        <div class="form-group">
                            <label>Apellido</label>
                            <input type="text" name="apellido[]" required>
                        </div>
                        <div class="form-group">
                            <label>DNI/Pasaporte</label>
                            <input type="text" name="dni_pasaporte[]" required>
                        </div>
                        <div class="form-group">
                            <label>Nacionalidad</label>
                            <input type="text" name="nacionalidad[]" required>
                        </div>
                    </div>
                `;
                passengerForms.insertAdjacentHTML('beforeend', formHtml);
            }
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            initializeLanguageSwitch();
            updatePassengerForms();
            document.getElementById('num_adultos').addEventListener('input', updatePassengerForms);
            document.getElementById('num_ninos').addEventListener('input', updatePassengerForms);
        });
    </script>

    <div id="google_translate_element" style="display: none;"></div>
</body>
</html>
