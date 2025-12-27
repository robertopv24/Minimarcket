<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Menú Digital</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap');
        body, html { margin:0; padding:0; height:100%; overflow:hidden; background:#000; font-family: 'Inter', sans-serif; }
        
        #carousel { width:100%; height:100%; position:relative; }
        
        .slide { position:absolute; inset:0; display:none; opacity:0; transition: opacity 1s ease; background-size: cover; background-position: center; }
        .slide.active { display: flex !important; opacity:1; }
        
        .overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.40); z-index: 1; }
        .content-layer { position: relative; z-index: 2; width: 100%; height: 100%; display: flex; flex-direction: column; justify-content: center; padding: 0 10%; }
        
        /* Suggestion Box Animation (Glassmorphism) */
        #suggestion-box {
            position: absolute; bottom: 40px; right: 40px; left: auto; transform: translateX(120%);
            width: auto; min-width: 300px; max-width: 400px;
            background: rgba(255, 255, 255, 0.1); 
            backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-left: 4px solid #fbbf24;
            border-radius: 12px;
            padding: 1rem 1.5rem; 
            text-align: left;
            opacity: 0; 
            transition: all 0.8s cubic-bezier(0.16, 1, 0.3, 1);
            z-index: 10; pointer-events: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        #suggestion-box.visible { 
            opacity: 1; 
            transform: translateX(0); 
        }
        #suggestion-box h3 { margin-bottom: 0.25rem; text-transform: uppercase; letter-spacing: 0.1em; font-size: 0.9em; }

        @keyframes bounce-slow {
            0%, 100% { transform: translateY(-5%) rotate(-5deg); animation-timing-function: cubic-bezier(0.8, 0, 1, 1); }
            50% { transform: translateY(0) rotate(-5deg); animation-timing-function: cubic-bezier(0, 0, 0.2, 1); }
        }
        .animate-bounce-slow { animation: bounce-slow 3s infinite; }

        #fs-prompt { position:fixed; inset:0; z-index:1000; background:#000; color:white; display:flex; flex-direction:column; justify-content:center; align-items:center; cursor:pointer; }
    </style>
</head>
<body>
    <audio id="bg-music" loop><source id="audio-src" src="" type="audio/mpeg"></audio>
    
    <div id="fs-prompt" onclick="startApp()">
        <h1 class="text-4xl md:text-6xl font-black animate-pulse text-center px-4">TOCA PARA INICIAR</h1>
    </div>

    <div id="carousel"></div>

    <!-- Sugerencia Flotante -->
    <div id="suggestion-box">
        <h3 class="text-yellow-400 font-bold text-xl mb-1">✨ Sugerencia del Chef</h3>
        <p id="suggestion-text" class="text-white text-lg italic"></p>
    </div>

    <script>
        let CONFIG = {}; 
        let current = 0; 
        let slides = []; 
        let timer;
        let offersData = []; // To store full data objects

        async function init() {
            try {
                const res = await fetch('api.php');
                const r = await res.json();
                if(!r.success) return console.error(r.message);
                
                CONFIG = r.data;
                offersData = r.ofertas; // Store
                const container = document.getElementById('carousel');
                container.innerHTML = '';

                // Prepend Initial Video if exists
                if (CONFIG.initial_video) {
                    const videoObj = {
                        titulo: '',
                        titulo_size: 5,
                        descripcion: '',
                        precio: '',
                        imagen_producto: CONFIG.initial_video,
                        es_video: true,
                        imagen_fondo: 'default_bg.jpg', 
                        duration_ms: 0,
                        show_suggestion: false
                    };
                    // Check if offersData was empty fallback
                    if (offersData.length === 1 && offersData[0].titulo === 'Menú Digital' && offersData[0].imagen_producto === 'default.png') {
                         offersData = [videoObj];
                    } else {
                         offersData.unshift(videoObj);
                    }
                }

                if (offersData.length > 0) {
                     offersData.forEach((o, index) => {
                        const div = document.createElement('div');
                        div.className = index === 0 ? 'slide active' : 'slide';
                        div.style.backgroundImage = `url('${o.imagen_fondo}')`;
                        
                        let bgContent = '';
                        let mainContent = '';

                        if (o.es_video) {
                            // Fullscreen Video Background, No Overlay
                            bgContent = `<video src="${o.imagen_producto}" class="absolute inset-0 w-full h-full object-cover z-0" muted playsinline></video>`;
                        } else {
                            // Standard Image Slide with Overlay
                            bgContent = `<div class="overlay"></div>`;
                            if (o.imagen_producto) {
                                mainContent = `<img src="${o.imagen_producto}" class="h-[40vh] md:h-[550px] object-contain drop-shadow-2xl animate-fade-in-up">`;
                            }
                        }

                        div.innerHTML = `
                            ${bgContent}
                            <div class="content-layer p-0 flex flex-col justify-between items-center h-full w-full relative pt-8 pb-12">
                                <!-- Logo (Absolute) -->
                                <img src="${CONFIG.logo_url}" class="w-28 md:w-40 absolute top-8 left-8 z-30 opacity-90 drop-shadow-md">
                                
                                <!-- 1. Title (Top Center) -->
                                <div class="w-full text-center z-20 mt-4 md:mt-8">
                                    <h1 style="font-size: ${o.titulo_size}rem" class="font-black text-white uppercase leading-none tracking-tight drop-shadow-xl inline-block px-8 py-2">
                                        ${o.titulo}
                                    </h1>
                                </div>

                                <!-- 2. Main Content Row (Flex Grow) -->
                                <div class="flex w-full flex-grow px-8 gap-4 items-center h-0 min-h-0">
                                    
                                    <!-- Description Col (40%) - No Scroll -->
                                    <div class="w-[40%] flex flex-col justify-center h-full z-20 pl-4">
                                        <div class="text-2xl md:text-2xl text-gray-100 font-medium leading-tight text-shadow-md text-left w-full">
                                            ${(() => {
                                                let desc = o.descripcion || '';
                                                let items = [];
                                                if (desc.includes('*')) items = desc.split('*');
                                                else if (desc.includes('\n')) items = desc.split('\n');
                                                else if (desc.includes('+')) items = desc.split('+');
                                                else items = desc.split(/(?=\b\d+\s)/);
                                                
                                                items = items.map(x => x.trim()).filter(x => x.length > 0);
                                                
                                                if (items.length > 1) {
                                                    return `<ul class="w-full flex flex-col justify-center space-y-4">
                                                        ${items.map(i => `<li class="flex items-start w-full"><span class="mr-3 text-yellow-400 mt-2 text-[0.6em] flex-shrink-0">➤</span><span>${i}</span></li>`).join('')}
                                                    </ul>`;
                                                } else if (items.length === 1) {
                                                    return items[0];
                                                }
                                                return '';
                                            })()}
                                        </div>
                                    </div>

                                    <!-- Image Col (60%) -->
                                    <div class="w-[60%] relative flex items-center justify-center h-full z-10">
                                        <!-- Product Image -->
                                        ${o.es_video ? '' : (o.imagen_producto ? `<img src="${o.imagen_producto}" class="w-full h-full object-contain drop-shadow-2xl animate-fade-in-up transform scale-105 origin-center">` : '')}
                                        
                                        <!-- Price Badge (Top-Left, Smaller -30%, Stretched Horizontally) -->
                                        ${o.precio ? `
                                        <div class="absolute top-[2%] left-[2%] w-[32%] md:w-[28%] aspect-[1.6] flex items-center justify-center animate-bounce-slow z-30 transform rotate-[-8deg] hover:scale-110 transition-transform duration-300">
                                            <!-- Starburst SVG Background (Stretched) -->
                                            <svg viewBox="0 0 200 200" preserveAspectRatio="none" class="absolute inset-0 w-full h-full text-yellow-500 fill-current drop-shadow-2xl" style="filter: drop-shadow(0 10px 15px rgba(0,0,0,0.6));">
                                                <polygon points="100,10 130,40 180,30 160,65 195,80 160,110 180,145 130,140 115,175 90,145 40,160 60,120 15,100 55,75 25,35 75,45" 
                                                      stroke="#fbbf24" stroke-width="4" stroke-linejoin="round" />
                                            </svg>
                                            
                                            <!-- Price Text -->
                                            <div class="relative z-10 text-center transform scale-x-90"> <!-- compressed text width slightly to fit stretched star -->
                                                <div class="font-extrabold text-red-600 text-lg md:text-2xl uppercase tracking-widest mb-0" style="font-family: 'Inter', sans-serif; text-shadow: 1px 1px 0 #fff; -webkit-text-stroke: 1px white; white-space: nowrap;">
                                                    ¡Por solo!
                                                </div>
                                                <div class="font-black text-red-600 text-5xl md:text-7xl leading-none tracking-tighter" style="filter: drop-shadow(3px 3px 0px #fff);">
                                                    ${o.precio}
                                                </div>
                                            </div>
                                        </div>` : ''}
                                    </div>

                                </div>
                            </div>
                        `;
                        container.appendChild(div);
                    });
                } else {
                    container.innerHTML = '<div class="text-white flex items-center justify-center h-full text-2xl">Sin Contenido</div>';
                }

                slides = document.querySelectorAll('.slide');
                
                if(CONFIG.background_audio) {
                    document.getElementById('audio-src').src = CONFIG.background_audio;
                    document.getElementById('bg-music').load();
                }

                // Initial Slide Logic
                handleSlideChange(0);

            } catch (e) { console.error(e); }
        }

        function showNext() {
            if (slides.length < 1) return;

            clearTimeout(timer);
            slides[current].classList.remove('active');
            
            current = (current + 1) % slides.length;
            
            const next = slides[current];
            next.classList.add('active');

            handleSlideChange(current);
        }

        function handleSlideChange(index) {
            const data = offersData[index];
            if (!data) return;

            // 1. Video Logic
            const vid = slides[index].querySelector('video');
            if(vid) {
                vid.currentTime = 0;
                vid.play();
                vid.onended = showNext;
            } else {
                // 2. Duration Logic
                const duration = parseInt(data.duration_ms) || CONFIG.default_duration_ms || 10000;
                timer = setTimeout(showNext, duration);
            }

            // 3. Suggestion Logic
            const box = document.getElementById('suggestion-box');
            box.classList.remove('visible'); // Hide initially

            if (data.show_suggestion) {
                // Use specific text OR random from pool
                let text = data.suggestion_text;
                if (!text && CONFIG.chef_suggestions_pool && CONFIG.chef_suggestions_pool.length > 0) {
                    if (Math.random() < (CONFIG.global_suggestion_prob || 0.4)) {
                         text = CONFIG.chef_suggestions_pool[Math.floor(Math.random() * CONFIG.chef_suggestions_pool.length)];
                    }
                }

                if (text) {
                    document.getElementById('suggestion-text').textContent = text;
                    // Show 1 second after slide appears
                    setTimeout(() => box.classList.add('visible'), 1000);
                }
            }
        }

        function startApp() {
            const elem = document.documentElement;
            if (elem.requestFullscreen) elem.requestFullscreen().catch(err => console.log(err));
            document.getElementById('fs-prompt').style.display = 'none';
            document.getElementById('bg-music').play().catch(e=>console.log(e));
            
            // Start cycle logic implicitly handled by initial load, but we ensure video plays if 1st slide is video
            const first = slides[0];
            if(first && first.querySelector('video')) first.querySelector('video').play();
        }
        
        window.onload = init;
    </script>
</body>
</html>
