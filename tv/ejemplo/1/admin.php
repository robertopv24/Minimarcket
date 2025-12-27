<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración de Menú Digital</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: sans-serif; background-color: #f4f4f9; padding: 20px; }
        .setting-group { background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 20px; }
        input[type="text"], input[type="number"], textarea { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .button-save { background-color: #4CAF50; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; transition: background-color 0.3s; }
        .button-save:hover { background-color: #45a049; }
        .status-msg { margin-top: 10px; font-weight: bold; }
        .suggestion-item { display: flex; align-items: center; margin-bottom: 10px; }
        .button-remove { background-color: #f44336; color: white; border: none; padding: 5px 10px; border-radius: 4px; margin-left: 10px; cursor: pointer; }
    </style>
</head>
<body>

    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold mb-6 text-gray-800">Panel de Control - Menú Digital</h1>

        <div id="config-form" class="setting-group">
            <h2 class="text-xl font-semibold mb-3 border-b pb-2">Configuración General</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="image_duration_ms">Duración de cada imagen (ms):</label>
                    <input type="number" id="image_duration_ms" data-key="image_duration_ms" placeholder="Ej: 12000">
                </div>
                <div>
                    <label for="suggestion_probability">Probabilidad de Sugerencias (0.0 a 1.0):</label>
                    <input type="text" id="suggestion_probability" data-key="suggestion_probability" placeholder="Ej: 0.40">
                </div>
            </div>
            
            <div class="mt-4">
                <label for="background_audio">Archivo de Música de Fondo (ej: musica.mp3):</label>
                <input type="text" id="background_audio" data-key="background_audio" placeholder="Nombre del archivo alojado en el servidor">
            </div>
        </div>

        <div class="setting-group">
            <h2 class="text-xl font-semibold mb-3 border-b pb-2">Sugerencias del Chef (Rotación)</h2>
            <div id="suggestions-list">
                </div>
            <button id="add-suggestion-btn" class="mt-4 bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">+ Añadir Sugerencia</button>
        </div>

        <button id="save-all-btn" class="button-save w-full text-lg font-bold">GUARDAR TODA LA CONFIGURACIÓN</button>
        <div id="status-container" class="status-msg text-center mt-4"></div>
    </div>

    <script>
        let currentSettings = {};

        async function loadSettings() {
            try {
                const response = await fetch('api.php');
                const result = await response.json();
                if (result.success) {
                    currentSettings = result.data;
                    
                    // Cargar valores básicos
                    document.getElementById('image_duration_ms').value = currentSettings.image_duration_ms || '';
                    document.getElementById('suggestion_probability').value = currentSettings.suggestion_probability || '';
                    document.getElementById('background_audio').value = currentSettings.background_audio || '';

                    // Cargar sugerencias
                    const list = document.getElementById('suggestions-list');
                    list.innerHTML = '';
                    if (currentSettings.chef_suggestions && Array.isArray(currentSettings.chef_suggestions)) {
                        currentSettings.chef_suggestions.forEach(text => addSuggestionInput(text));
                    }
                }
            } catch (e) {
                showStatus("Error al cargar la configuración.", true);
            }
        }

        function addSuggestionInput(text = '') {
            const list = document.getElementById('suggestions-list');
            const div = document.createElement('div');
            div.className = 'suggestion-item';
            div.innerHTML = `
                <input type="text" class="suggestion-text" value="${text}" placeholder="Escribe una sugerencia...">
                <button class="button-remove" onclick="this.parentElement.remove()">Eliminar</button>
            `;
            list.appendChild(div);
        }

        function addSuggestion() {
            addSuggestionInput();
        }

        async function saveSingleSetting(key, value) {
            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ key, value })
                });
                return await response.json();
            } catch (e) {
                return { success: false };
            }
        }

        async function saveAllSettings() {
            showStatus("Guardando...");
            let allSuccess = true;

            // 1. Guardar ajustes básicos
            const duration_ms = document.getElementById('image_duration_ms').value;
            const probability = document.getElementById('suggestion_probability').value;
            const audio = document.getElementById('background_audio').value;

            if (!(await saveSingleSetting('image_duration_ms', duration_ms)).success) allSuccess = false;
            if (!(await saveSingleSetting('suggestion_probability', probability)).success) allSuccess = false;
            if (!(await saveSingleSetting('background_audio', bgAudio)).success) allSuccess = false;

            // 2. Guardar sugerencias
            const inputs = document.querySelectorAll('.suggestion-text');
            const suggestions = Array.from(inputs).map(i => i.value).filter(v => v.trim() !== '');
            if (!(await saveSingleSetting('chef_suggestions_json', JSON.stringify(suggestions))).success) allSuccess = false;

            if (allSuccess) {
                showStatus("✅ ¡Configuración guardada exitosamente!");
            } else {
                showStatus("⚠️ Error al guardar algunos ajustes.", true);
            }
        }

        function showStatus(msg, isError = false) {
            const container = document.getElementById('status-container');
            container.innerText = msg;
            container.style.color = isError ? 'red' : 'green';
        }

        document.addEventListener('DOMContentLoaded', () => {
            loadSettings();
            document.getElementById('save-all-btn').onclick = saveAllSettings;
            document.getElementById('add-suggestion-btn').onclick = addSuggestion;
        });
    </script>
</body>
</html>