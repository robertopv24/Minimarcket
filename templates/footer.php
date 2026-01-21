<?php
// footer.php - Pie de página




?>



<!-- Footer Start -->
<div class="container-fluid sticky-down pt-4 px-4">
  <footer>
    <nav class="navbar justify-content-center navbar-expand bg-secondary navbar-dark sticky-down px-10 py-10  ">

      &copy; <?php echo date("Y"); ?> <?php echo htmlspecialchars($config->get('site_name')); ?>

    </nav>
    <nav class="navbar justify-content-center navbar-expand bg-secondary navbar-dark sticky-down px-2 py-2  ">






    </nav>
  </footer>
</div>
<!-- Footer End -->
</div>
<!-- Content End -->


<!-- Back to Top -->
<a href="#" class="btn btn-lg btn-primary btn-lg-square back-to-top"><i class="bi bi-arrow-up"></i></a>
</div>

<!-- Toast Container Global -->
<div id="global-toast-container" class="position-fixed bottom-0 end-0 p-3" style="z-index: 1100;"></div>

<!-- Flash Messages Logic -->
<?php if (class_exists('SessionHelper') && SessionHelper::hasFlashes()): ?>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      <?php foreach (SessionHelper::getFlashes() as $flash): ?>
        window.showToast('<?php echo $flash['type']; ?>', '<?php echo addslashes($flash['message']); ?>');
      <?php endforeach; ?>
    });
  </script>
<?php endif; ?>

<!-- JavaScript Libraries -->
<!-- Core libs moved to header.php -->
<script src="../lib/chart/chart.min.js"></script>
<script src="../lib/easing/easing.min.js"></script>
<script src="../lib/waypoints/waypoints.min.js"></script>
<script src="../lib/owlcarousel/owl.carousel.min.js"></script>
<script src="../lib/tempusdominus/js/moment.min.js"></script>
<script src="../lib/tempusdominus/js/moment-timezone.min.js"></script>
<script src="../lib/tempusdominus/js/tempusdominus-bootstrap-4.min.js"></script>

<!-- Template Javascript -->
<script>
  // Live Search Global (Optimizado para Tablas y Tarjetas)
  document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
      searchInput.addEventListener('keyup', function () {
        const value = this.value.toLowerCase().trim();

        // 1. Filtrado para Tablas (Prioridad 1)
        const tableBody = document.querySelector('.table tbody');
        let foundTable = false;
        if (tableBody) {
          const rows = tableBody.getElementsByTagName('tr');
          if (rows.length > 0) {
            foundTable = true;
            for (let i = 0; i < rows.length; i++) {
              // Ignorar filas de "Sin resultados"
              if (rows[i].cells.length < 2) continue;

              const text = rows[i].textContent.toLowerCase();
              if (text.indexOf(value) > -1) {
                rows[i].style.display = "";
              } else {
                rows[i].style.display = "none";
              }
            }
          }
        }

        // 2. Filtrado para Tarjetas/Grids (Si no es tabla o es mixto)
        // Buscamos contenedores típicos de cards (col-md-*) dentro de .row pero EXCLUYENDO los marcados con data-no-search
        const cards = document.querySelectorAll(
          '.col-md-6:not([data-no-search="true"]) .card, .col-md-6:not([data-no-search]) .card, ' +
          '.col-md-4:not([data-no-search="true"]) .card, .col-md-4:not([data-no-search]) .card, ' +
          '.col-md-3:not([data-no-search="true"]) .card, .col-md-3:not([data-no-search]) .card'
        );

        if (cards.length > 0 && !foundTable) {
          cards.forEach(card => {
            const col = card.closest('[class*="col-"]');
            if (col) {
              const text = card.textContent.toLowerCase();
              if (text.indexOf(value) > -1) {
                col.style.display = "";
              } else {
                col.style.display = "none";
              }
            }
          });
        }
      });
    }
  });
</script>
<script src="../js/main.js?v=<?= time() + 1; ?>"></script>
</body>

</html>