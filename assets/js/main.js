// assets/js/main.js

document.addEventListener('DOMContentLoaded', function() {
    
    const menuBtn = document.getElementById('menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    

    const overlay = document.createElement('div');
    overlay.className = 'overlay';
    document.body.appendChild(overlay);

    function toggleMenu() {
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
    }

    if(menuBtn) {
        menuBtn.addEventListener('click', toggleMenu);
    }

    overlay.addEventListener('click', toggleMenu);
});