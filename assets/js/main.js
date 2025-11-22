// assets/js/main.js

document.addEventListener('DOMContentLoaded', function() {
    
    const menuBtn = document.getElementById('menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    
    // Crear el elemento Overlay (sombra de fondo) dinámicamente
    const overlay = document.createElement('div');
    overlay.className = 'overlay';
    document.body.appendChild(overlay);

    // Función para abrir/cerrar menú
    function toggleMenu() {
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
    }

    // Evento Click en el botón hamburguesa
    if(menuBtn) {
        menuBtn.addEventListener('click', toggleMenu);
    }

    // Evento Click en la sombra (para cerrar al tocar afuera)
    overlay.addEventListener('click', toggleMenu);
});