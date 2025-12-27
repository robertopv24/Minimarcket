<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Admin J&Y - Editor de Menú</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 p-6">
    <div class="max-w-5xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-6">
        
        <div class="bg-white p-6 rounded-xl shadow-lg h-fit sticky top-6">
            <h2 id="form-title" class="text-xl font-black mb-4 uppercase text-green-600">Nueva Oferta</h2>
            <input type="hidden" id="edit_id" value="">
            <div class="space-y-3">
                <input id="t" type="text" placeholder="Título" class="w-full border p-2 rounded">
                <div class="flex items-center gap-2 border p-2 rounded">
                    <span class="text-xs font-bold">Tamaño Título:</span>
                    <input id="ts" type="number" value="8" step="0.5" class="w-full outline-none">
                </div>
                <input id="p" type="text" placeholder="Precio" class="w-full border p-2 rounded">
                <textarea id="d" placeholder="Descripción" class="w-full border p-2 rounded h-24"></textarea>
                <input id="if" type="text" placeholder="Imagen Fondo (ej: f1.jpg)" class="w-full border p-2 rounded">
                <input id="ip" type="text" placeholder="Imagen Producto (ej: p1.png)" class="w-full border p-2 rounded">
                
                <div class="flex gap-2">
                    <button onclick="save()" class="flex-1 bg-green-600 text-white p-3 rounded font-bold uppercase shadow">Guardar</button>
                    <button onclick="resetForm()" class="bg-gray-400 text-white p-3 rounded font-bold uppercase shadow">Limpiar</button>
                </div>
            </div>

            <hr class="my-6">
            <h2 class="font-bold text-blue-600 mb-2 uppercase text-sm">Ajustes de Pantalla</h2>
            <input id="logo_url" type="text" placeholder="URL del Logo" class="w-full border p-2 rounded mb-2 text-sm">
            <button onclick="saveLogo()" class="w-full bg-blue-600 text-white p-2 rounded text-xs font-bold uppercase">Actualizar Logo</button>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-lg">
            <h2 class="text-xl font-black mb-4 uppercase text-gray-700">Productos Registrados</h2>
            <div id="list" class="divide-y space-y-2"></div>
        </div>
    </div>

    <script>
        let allOfertas = [];

        async function load() {
            const r = await (await fetch('api.php')).json();
            allOfertas = r.ofertas;
            document.getElementById('logo_url').value = r.data.logo_url || '';

            document.getElementById('list').innerHTML = allOfertas.map(o => `
                <div class="py-3 flex justify-between items-center">
                    <div>
                        <div class="font-bold uppercase text-sm">${o.titulo}</div>
                        <div class="text-xs text-gray-500">${o.precio} | Tamaño: ${o.titulo_size}</div>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="edit(${o.id})" class="bg-blue-100 text-blue-600 px-3 py-1 rounded text-xs font-bold">Editar</button>
                        <button onclick="del(${o.id})" class="bg-red-100 text-red-600 px-3 py-1 rounded text-xs font-bold">X</button>
                    </div>
                </div>
            `).join('');
        }

        function edit(id) {
            const item = allOfertas.find(o => o.id == id);
            document.getElementById('edit_id').value = item.id;
            document.getElementById('t').value = item.titulo;
            document.getElementById('ts').value = item.titulo_size;
            document.getElementById('p').value = item.precio;
            document.getElementById('d').value = item.descripcion;
            document.getElementById('if').value = item.imagen_fondo;
            document.getElementById('ip').value = item.imagen_producto;
            document.getElementById('form-title').innerText = "Editando Oferta";
            document.getElementById('form-title').classList.replace('text-green-600', 'text-blue-600');
        }

        function resetForm() {
            document.getElementById('edit_id').value = "";
            document.querySelectorAll('input, textarea').forEach(i => { if(i.id != 'logo_url') i.value = (i.id == 'ts' ? 8 : ""); });
            document.getElementById('form-title').innerText = "Nueva Oferta";
            document.getElementById('form-title').classList.replace('text-blue-600', 'text-green-600');
        }

        async function save() {
            const id = document.getElementById('edit_id').value;
            const data = {
                action: id ? 'update_oferta' : 'add_oferta',
                id: id,
                titulo: document.getElementById('t').value,
                titulo_size: document.getElementById('ts').value,
                precio: document.getElementById('p').value,
                descripcion: document.getElementById('d').value,
                imagen_fondo: document.getElementById('if').value,
                imagen_producto: document.getElementById('ip').value
            };
            await fetch('api.php', { method: 'POST', body: JSON.stringify(data) });
            resetForm();
            load();
        }

        async function saveLogo() {
            await fetch('api.php', { method: 'POST', body: JSON.stringify({ action: 'save_setting', key: 'logo_url', value: document.getElementById('logo_url').value }) });
            alert("Logo actualizado");
        }

        async function del(id) {
            if(confirm('¿Borrar oferta?')) {
                await fetch('api.php', { method: 'POST', body: JSON.stringify({action:'delete_oferta', id:id}) });
                load();
            }
        }
        window.onload = load;
    </script>
</body>
</html>