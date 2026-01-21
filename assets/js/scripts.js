document.addEventListener("DOMContentLoaded", function () {
    // Validar formularios antes de enviar
    const forms = document.querySelectorAll(".needs-validation");

    forms.forEach((form) => {
        form.addEventListener("submit", function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add("was-validated");
        });
    });

    // Mostrar/Ocultar contraseña en los formularios
    const togglePassword = document.querySelectorAll(".toggle-password");
    togglePassword.forEach((btn) => {
        btn.addEventListener("click", function () {
            let input = this.previousElementSibling;
            if (input.type === "password") {
                input.type = "text";
                this.innerHTML = '<i class="fa fa-eye-slash"></i>';
            } else {
                input.type = "password";
                this.innerHTML = '<i class="fa fa-eye"></i>';
            }
        });
    });

    // Confirmación antes de eliminar usuario
    const deleteButtons = document.querySelectorAll(".btn-delete");
    deleteButtons.forEach((btn) => {
        btn.addEventListener("click", function (event) {
            if (!confirm("¿Estás seguro de que deseas eliminar este usuario?")) {
                event.preventDefault();
            }
        });
    });

    // Previsualizar imagen de perfil antes de subirla
    const profilePicInput = document.querySelector("#profile-pic-input");
    const profilePicPreview = document.querySelector("#profile-pic-preview");

    if (profilePicInput) {
        profilePicInput.addEventListener("change", function () {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    profilePicPreview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    }
});
