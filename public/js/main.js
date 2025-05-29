
(function() {
  "use strict";

  /**
   * Apply .scrolled class to the body as the page is scrolled down
   */
  function toggleScrolled() {
    const selectBody = document.querySelector('body');
    const selectHeader = document.querySelector('#header');
    if (!selectHeader.classList.contains('scroll-up-sticky') && !selectHeader.classList.contains('sticky-top') && !selectHeader.classList.contains('fixed-top')) return;
    window.scrollY > 100 ? selectBody.classList.add('scrolled') : selectBody.classList.remove('scrolled');
  }

  document.addEventListener('scroll', toggleScrolled);
  window.addEventListener('load', toggleScrolled);

  /**
   * Mobile nav toggle
   */
  const mobileNavToggleBtn = document.querySelector('.mobile-nav-toggle');
   
  function mobileNavToogle() {
    document.querySelector('body').classList.toggle('mobile-nav-active');
    mobileNavToggleBtn.classList.toggle('bi-list');
    mobileNavToggleBtn.classList.toggle('bi-x');
  }
  mobileNavToggleBtn.addEventListener('click', mobileNavToogle);

  /**
   * Hide mobile nav on same-page/hash links
   */
  document.querySelectorAll('#navmenu a').forEach(navmenu => {
    navmenu.addEventListener('click', () => {
      if (document.querySelector('.mobile-nav-active')) {
        mobileNavToogle();
      }
    });

  });
  
  /**
   * Preloader
   */
  const preloader = document.querySelector('#preloader');
  if (preloader) {
    window.addEventListener('load', () => {
      preloader.remove();
    });
  }

  /**
   * Scroll top button
   */
  let scrollTop = document.querySelector('.scroll-top');

  function toggleScrollTop() {
    if (scrollTop) {
      window.scrollY > 100 ? scrollTop.classList.add('active') : scrollTop.classList.remove('active');
    }
  }
  scrollTop.addEventListener('click', (e) => {
    e.preventDefault();
    window.scrollTo({
      top: 0,
      behavior: 'smooth'
    });
  });

  window.addEventListener('load', toggleScrollTop);
  document.addEventListener('scroll', toggleScrollTop);

  /**
   * Animation on scroll function and init
   */
  function aosInit() {
    AOS.init({
      duration: 1000,
      easing: 'ease-in-out',
      once: true,
      mirror: false,
      offset: 100
    });
  }
  window.addEventListener('load', aosInit);

  /**
   * Initiate glightbox
   */
  const glightbox = GLightbox({
    selector: '.glightbox'
  });

  /**
   * Init swiper sliders
   */
  function initSwiper() {
    document.querySelectorAll(".init-swiper").forEach(function(swiperElement) {
      let config = JSON.parse(
        swiperElement.querySelector(".swiper-config").innerHTML.trim()
      );

      if (swiperElement.classList.contains("swiper-tab")) {
        initSwiperWithCustomPagination(swiperElement, config);
      } else {
        new Swiper(swiperElement, config);
      }
    });
  }

  window.addEventListener("load", initSwiper);
  /**
   * Correct scrolling position upon page load for URLs containing hash links.
   */
  window.addEventListener('load', function(e) {
    if (window.location.hash) {
      if (document.querySelector(window.location.hash)) {
        setTimeout(() => {
          let section = document.querySelector(window.location.hash);
          let scrollMarginTop = getComputedStyle(section).scrollMarginTop;
          window.scrollTo({
            top: section.offsetTop - parseInt(scrollMarginTop),
            behavior: 'smooth'
          });
        }, 100);
      }
    }
  });

  /**
   * Navmenu Scrollspy
   */
  let navmenulinks = document.querySelectorAll('.navmenu a');

  function navmenuScrollspy() {
    navmenulinks.forEach(navmenulink => {
      if (!navmenulink.hash) return;
      let section = document.querySelector(navmenulink.hash);
      if (!section) return;
      let position = window.scrollY + 200;
      if (position >= section.offsetTop && position <= (section.offsetTop + section.offsetHeight)) {
        document.querySelectorAll('.navmenu a.active').forEach(link => link.classList.remove('active'));
        navmenulink.classList.add('active');
      } else {
        navmenulink.classList.remove('active');
      }
    })
  }
  window.addEventListener('load', navmenuScrollspy);
  document.addEventListener('scroll', navmenuScrollspy);

});

// Initialisation de Swiper
const swiper = new Swiper('.init-swiper', {
  loop: true,
  speed: 600,
  autoplay: {
    delay: 5000
  },
  slidesPerView: "auto",
  pagination: {
    el: ".swiper-pagination",
    type: "bullets",
    clickable: true
  },
  breakpoints: {
    320: {
      slidesPerView: 2,
      spaceBetween: 40
    },
    480: {
      slidesPerView: 3,
      spaceBetween: 60
    },
    640: {
      slidesPerView: 4,
      spaceBetween: 80
    },
    992: {
      slidesPerView: 5,
      spaceBetween: 120
    },
    1200: {
      slidesPerView: 6,
      spaceBetween: 120
    }
  }
});
document.addEventListener("DOMContentLoaded", function() {
  console.log("Script chargé"); // Vérifie si le script est bien exécuté

  const openModalButton = document.getElementById("openModalButton");
  const closeModalButton = document.getElementById("closeModalButton");
  const modalBox = document.getElementById("modalBox");

  // Vérification de l'état initial du modal (devrait être "none")
  console.log("Initial display state:", window.getComputedStyle(modalBox).display); // Affiche "none"

  // Ouvrir le modal au clic sur le bouton
  openModalButton.addEventListener("click", function(event) {
      event.preventDefault(); // Empêche le comportement par défaut du lien

      // Affiche le modal
      modalBox.style.display = "flex"; // Change le style en "flex" pour le rendre visible
      console.log("Modal display after click:", window.getComputedStyle(modalBox).display); // Affiche "flex"
  });

  // Fermer le modal lorsqu'on clique sur la croix
  closeModalButton.addEventListener("click", function() {
      modalBox.style.display = "none"; // Masque le modal
  });

  // Fermer le modal si on clique en dehors de la boîte
  window.addEventListener("click", function(event) {
      // Si l'utilisateur clique en dehors du modal, fermer la boîte
      if (event.target === modalBox) {
          modalBox.style.display = "none"; // Masque le modal
      }
  });
});

window.addEventListener('scroll', function() {
  AOS.refresh(); // Rafraîchit AOS lors du scroll
});


function showCategory(category) {
  // Cacher les deux catégories (produits et matériaux)
  let produits = document.getElementById('produits');
  let materiaux = document.getElementById('materiaux');
  
  // Boutons des catégories
  let produitsBtn = document.getElementById('btn-produits');
  let materiauxBtn = document.getElementById('btn-materiaux');
  
  // Masquer les deux catégories
  produits.style.display = 'none';
  materiaux.style.display = 'none';
  
  // Réinitialiser les classes actives et inactives
  produitsBtn.classList.remove('active');
  produitsBtn.classList.add('inactive');
  materiauxBtn.classList.remove('active');
  materiauxBtn.classList.add('inactive');
  
  // Si on clique sur "Produits"
  if (category === 'produits') {
    produits.style.display = 'flex';  
    produitsBtn.classList.add('active');  // Appliquer la classe active au bouton Produits
    produitsBtn.classList.remove('inactive');  // Retirer la classe inactive du bouton Produits
  } 
  // Si on clique sur "Matériaux"
  else if (category === 'materiaux') {
    console.log("materiaux")
    materiaux.style.display = 'flex';
    materiauxBtn.classList.add('active');  // Appliquer la classe active au bouton Matériaux
    materiauxBtn.classList.remove('inactive');  // Retirer la classe inactive du bouton Matériaux
  }
}

// Afficher les produits par défaut au chargement
document.addEventListener("DOMContentLoaded", function() {
  showCategory('produits');  // Afficher Produits par défaut
});


        





