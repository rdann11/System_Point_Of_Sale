// /assets/js/script.js

document.addEventListener('DOMContentLoaded', function() {
    
    const menuToggle = document.getElementById('menu-toggle');
    const wrapper = document.getElementById('wrapper');

    if (menuToggle && wrapper) {
        // Cek localStorage untuk state sidebar saat halaman dimuat
        if (localStorage.getItem('sidebarToggledPosWebsite') === 'true') {
            wrapper.classList.add('toggled');
        }

        menuToggle.addEventListener('click', function(e) {
            e.preventDefault(); 
            wrapper.classList.toggle('toggled');
            
            if (wrapper.classList.contains('toggled')) {
                localStorage.setItem('sidebarToggledPosWebsite', 'true');
            } else {
                localStorage.setItem('sidebarToggledPosWebsite', 'false');
            }
        });
    }

    // Pastikan tidak ada kode JavaScript lain yang terkait SweetAlert di sini
    // untuk saat ini.

});