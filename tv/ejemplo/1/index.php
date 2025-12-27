<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menú Digital - Pizzas y Pastelería J&Y</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;800&display=swap');

        html, body {
            margin: 0; padding: 0; width: 100%; height: 100%;
            overflow: hidden; font-family: 'Inter', sans-serif; background-color: #000000;
        }

        #carousel-container {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: 1;
        }

        .slide {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background-size: 100% 100%; background-position: center; background-repeat: no-repeat;
            display: none; opacity: 0; transition: opacity 1.5s ease-in-out;
        }

        .active { display: block; opacity: 1; }

        #video-slide { width: 100%; height: 100%; object-fit: fill; display: block; }

        #suggestion-box {
            position: fixed; bottom: 50px; right: 50px;
            background: rgba(255, 255, 255, 0.15); backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.3); border-radius: 20px;
            padding: 20px 35px; max-width: 450px; color: white;
            z-index: 100; display: none; transform: translateY(100px);
            transition: all 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
        }

        #suggestion-box.visible { display: block; transform: translateY(0); }

        #fullscreen-prompt {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.9); color: white; display: flex;
            flex-direction: column; align-items: center; justify-content: center;
            z-index: 9999; cursor: pointer; text-align: center; padding: 20px;
        }
    </style>
</head>
<body>

    <audio id="bg-music" loop>
        <source id="audio-source" src="" type="audio/mpeg">
    </audio>

    <div id="fullscreen-prompt">
        <div class="bg-white text-black p-8 rounded-full mb-6 animate-bounce">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" viewBox="0 0 16 16">
                <path d="M11.596 8.697l-6.363 3.692c-.54.313-1.233-.066-1.233-.697V4.308c0-.63.692-1.01 1.233-.696l6.363 3.692a.802.802 0 0 1 0 1.393z"/>
            </svg>
        </div>
        <h1 class="text-4xl font-black uppercase tracking-tighter">Toca para Iniciar Menú</h1>
        <p class="text-gray-400 mt-2">Pizzas y Pastelería J&Y</p>
    </div>

    <div id="carousel-container">
        <div class="slide active" style="background-image: url('0.png');"></div>
        <div class="slide" style="background-image: url('1.png');"></div>
        <div class="slide" style="background-image: url('2.png');"></div>
        <div class="slide" style="background-image: url('3.png');"></div>
        <div class="slide" style="background-image: url('4.png');"></div>
        <div class="slide" style="background-image: url('5.png');"></div>
        <div class="slide" style="background-image: url('6.png');"></div>
        <div class="slide" style="background-image: url('7.png');"></div>
        <div class="slide" style="background-image: url('8.png');"></div>
        <div class="slide" style="background-image: url('9.png');"></div>
        <div class="slide" style="background-image: url('10.png');"></div>

        <div class="slide" id="video-slide-container">
            <video id="video-slide" muted playsinline>
                <source src="2.mp4" type="video/mp4">
            </video>
        </div>
    </div>

    <div id="suggestion-box">
        <div class="flex items-center gap-4">
            <div class="bg-yellow-400 p-3 rounded-xl shadow-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-black" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                </svg>
            </div>
            <div>
                <p class="text-yellow-400 font-bold text-xs uppercase tracking-widest mb-1">Sugerencia del Chef</p>
                <p id="suggestion-text" class="text-xl font-extrabold leading-tight"></p>
            </div>
        </div>
    </div>

    <script>
        let CONFIG = {
            image_duration_ms: 12000,
            suggestion_probability: 0.40,
            chef_suggestions: [],
            background_audio: 'musica_fondo.mp3'
        };

        let currentSlide = 0;
        const slides = document.querySelectorAll('.slide');
        const videoSlide = document.getElementById('video-slide');
        const suggestionBox = document.getElementById('suggestion-box');
        const suggestionText = document.getElementById('suggestion-text');
        const bgMusic = document.getElementById('bg-music');
        const audioSource = document.getElementById('audio-source');

        async function loadConfig() {
            try {
                const response = await fetch('api.php');
                const result = await response.json();
                if (result.success) {
                    CONFIG = { ...CONFIG, ...result.data };
                    if (CONFIG.background_audio) {
                        audioSource.src = CONFIG.background_audio;
                        bgMusic.load();
                    }
                }
            } catch (e) { console.error("Error cargando config", e); }
            startCarousel();
        }

        function showNextSlide() {
            slides[currentSlide].classList.remove('active');
            currentSlide = (currentSlide + 1) % slides.length;
            const nextSlide = slides[currentSlide];
            nextSlide.classList.add('active');

            if (nextSlide.id === 'video-slide-container') {
                videoSlide.currentTime = 0;
                videoSlide.play();
                videoSlide.onended = showNextSlide;
            } else {
                setTimeout(showNextSlide, CONFIG.image_duration_ms);
                trySuggestion();
            }
        }

        function trySuggestion() {
            suggestionBox.classList.remove('visible');
            if (Math.random() < CONFIG.suggestion_probability && CONFIG.chef_suggestions.length > 0) {
                const randomMsg = CONFIG.chef_suggestions[Math.floor(Math.random() * CONFIG.chef_suggestions.length)];
                suggestionText.innerText = randomMsg;
                setTimeout(() => suggestionBox.classList.add('visible'), 1000);
                setTimeout(() => suggestionBox.classList.remove('visible'), 8000);
            }
        }

        function handleFullscreenActivation() {
            // Activar Pantalla Completa
            const docElm = document.documentElement;
            if (docElm.requestFullscreen) docElm.requestFullscreen();
            
            // Iniciar Música
            if (CONFIG.background_audio) {
                bgMusic.play().catch(e => console.log("Audio bloqueado", e));
            }

            document.getElementById('fullscreen-prompt').style.display = 'none';
            document.body.removeEventListener('click', handleFullscreenActivation);
        }

        function startCarousel() {
            setTimeout(showNextSlide, CONFIG.image_duration_ms);
        }

        document.addEventListener('DOMContentLoaded', loadConfig);
        document.body.addEventListener('click', handleFullscreenActivation);
    </script>
</body>
</html>