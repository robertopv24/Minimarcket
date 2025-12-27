<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Menú Vivo J&Y</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@900&display=swap');
        body, html { margin:0; padding:0; height:100%; overflow:hidden; background:#000; font-family: 'Inter', sans-serif; }
        #carousel { width:100%; height:100%; position:relative; }
        .slide { position:absolute; inset:0; display:none; opacity:0; transition: opacity 1s ease; background-size: cover; background-position: center; }
        .active { display: flex !important; opacity:1; }
        .overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.65); z-index: 1; }
        .content-layer { position: relative; z-index: 2; width: 100%; height: 100%; display: flex; flex-direction: column; justify-content: center; padding: 0 10%; }
        #fs-prompt { position:fixed; inset:0; z-index:1000; background:#000; color:white; display:flex; flex-direction:column; justify-content:center; align-items:center; cursor:pointer; }
    </style>
</head>
<body>
    <audio id="bg-music" loop><source id="audio-src" src="" type="audio/mpeg"></audio>
    
    <div id="fs-prompt" onclick="startApp()">
        <h1 class="text-5xl font-black animate-pulse">INICIAR MENÚ DIGITAL</h1>
    </div>

    <div id="carousel"></div>

    <script>
        let CONFIG = {}; let current = 0; let slides = []; let timer;

        async function init() {
            const res = await fetch('api.php');
            const r = await res.json();
            if(!r.success) return;
            CONFIG = r.data;
            const container = document.getElementById('carousel');
            container.innerHTML = '';

            // 1. VIDEO INICIAL (Si existe video.mp4)
            const vDiv = document.createElement('div');
            vDiv.className = 'slide active';
            vDiv.innerHTML = `<video id="m-video" class="w-full h-full object-cover" muted><source src="video.mp4" type="video/mp4"></video>`;
            container.appendChild(vDiv);

            // 2. DIAPOSITIVAS DE LA DB
            r.ofertas.forEach((o) => {
                const div = document.createElement('div');
                div.className = 'slide';
                div.style.backgroundImage = `url('${o.imagen_fondo}')`;
                div.innerHTML = `
                    <div class="overlay"></div>
                    <div class="content-layer">
                        <img src="${CONFIG.logo_url}" class="w-48 absolute top-10 left-10">
                        
                        <h1 style="font-size: ${o.titulo_size}rem" class="font-black text-white uppercase leading-[0.85] tracking-tighter">
                            ${o.titulo}
                        </h1>
                        <div class="flex items-center gap-12 mt-6">
                            <p class="text-5xl text-gray-200 font-medium max-w-xl leading-tight">
                                ${o.descripcion}
                            </p>
                            ${o.imagen_producto ? `<img src="${o.imagen_producto}" class="h-[450px] object-contain drop-shadow-2xl">` : ''}
                        </div>
                        <div class="absolute bottom-16 left-20 bg-yellow-400 text-black text-9xl font-black px-12 py-4 rounded-3xl shadow-2xl">
                            ${o.precio}
                        </div>
                    </div>
                `;
                container.appendChild(div);
            });

            slides = document.querySelectorAll('.slide');
            if(CONFIG.background_audio) {
                document.getElementById('audio-src').src = CONFIG.background_audio;
                document.getElementById('bg-music').load();
            }
        }

        function showNext() {
            clearTimeout(timer);
            slides[current].classList.remove('active');
            
            // EL CONTEO AUTOMÁTICO REINICIA EL CICLO
            current = (current + 1) % slides.length;
            
            const next = slides[current];
            next.classList.add('active');

            const vid = next.querySelector('video');
            if(vid) {
                vid.play();
                vid.onended = showNext;
            } else {
                timer = setTimeout(showNext, parseInt(CONFIG.image_duration_ms || 10000));
            }
        }

        function startApp() {
            document.documentElement.requestFullscreen();
            document.getElementById('fs-prompt').style.display = 'none';
            document.getElementById('bg-music').play();
            
            // Si la primera es video, darle play, sino iniciar timer
            const firstVid = slides[0].querySelector('video');
            if(firstVid) { firstVid.play(); firstVid.onended = showNext; } 
            else { timer = setTimeout(showNext, parseInt(CONFIG.image_duration_ms || 10000)); }
        }
        window.onload = init;
    </script>
</body>
</html>