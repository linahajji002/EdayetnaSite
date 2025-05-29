(function($) {
    'use strict';
    $(document).ready(function() {
        var sidebar = $('.sidebar');

        function setActiveClass() {
            var currentPath = window.location.pathname;

            // Supprimer toutes les classes actives existantes
            $('.nav-item').removeClass('active');
            $('.sub-menu .collapse').removeClass('show');
            $('.nav-item a').removeClass('active');

            // Parcourir tous les liens du sidebar
            $('.nav li a', sidebar).each(function() {
                var $this = $(this);
                var linkPath = $this.attr('href');

                if (linkPath === currentPath) {
                    // Activer le lien actuel
                    $this.addClass('active');

                    // Activer le parent <li class="nav-item">
                    var parentNavItem = $this.closest('.nav-item');
                    parentNavItem.addClass('active');

                    // Si c'est un sous-menu, l'ouvrir
                    if ($this.parents('.sub-menu').length) {
                        var parentCollapse = $this.closest('.collapse');
                        parentCollapse.addClass('show');
                        parentNavItem.addClass('active');
                    }
                }
            });
        }

        // Appliquer l'état actif au chargement
        setActiveClass();

        // Gestion du toggle des sous-menus pour éviter d'en ouvrir plusieurs en même temps
        sidebar.on('show.bs.collapse', '.collapse', function() {
            sidebar.find('.collapse.show').not(this).collapse('hide');
        });

        // Minimise/Maximise Sidebar
        $('[data-toggle="minimize"]').on("click", function() {
            var body = $('body');
            if (body.hasClass('sidebar-toggle-display') || body.hasClass('sidebar-absolute')) {
                body.toggleClass('sidebar-hidden');
            } else {
                body.toggleClass('sidebar-icon-only');
            }
        });

        // Activation des popovers et tooltips
        var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        var popoverList = popoverTriggerList.map(function(popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });

        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Gestion du focus sur l'input de recherche
        $('#navbar-search-icon').click(function() {
            $("#navbar-search-input").focus();
        });
    });

})(jQuery);

function toggleDetailsAdmin(element) {
  var modal = document.getElementById("infoModal");
  
  // Extraire les données depuis les attributs data-
  var titre = element.getAttribute("data-titre");
  var category = element.getAttribute("data-category");
  var description = element.getAttribute("data-description");
  var formateur = element.getAttribute("data-formateur");
  var date = element.getAttribute("data-date");
  var prix = element.getAttribute("data-prix");
  var lien = element.getAttribute("data-lien");
  var duree = element.getAttribute("data-duree");
  var inscrits = element.getAttribute("data-inscrits"); // Liste des inscrits
  
  // Remplir les informations dans le modal
  document.getElementById("modalTitre").innerText = titre;
  document.getElementById("modalCategory").innerText = category;
  document.getElementById("modalDescription").innerText = description;
  document.getElementById("modalFormateur").innerText = formateur;
  document.getElementById("modalDate").innerText = date;
  document.getElementById("modalPrix").innerText = prix;
  document.getElementById("modalLien").innerText = lien;
  document.getElementById("modalDuree").innerText = duree;

  // Afficher la liste des inscrits
  var inscritsList = document.getElementById("modalInscrits");
  inscritsList.innerHTML = ""; // Effacer la liste précédente
  var inscritsArray = inscrits.split(", "); // Convertir la chaîne en tableau
  inscritsArray.forEach(function(inscrit) {
    var li = document.createElement("li");
    li.textContent = inscrit;
    inscritsList.appendChild(li);
  });

  // Afficher le modal
  modal.style.display = "block";
}

// Fermer le modal lorsque l'on clique sur la croix
document.querySelector(".close").onclick = function() {
  var modal = document.getElementById("infoModal");
  modal.style.display = "none";
}

