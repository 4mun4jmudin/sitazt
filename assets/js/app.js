document.addEventListener('DOMContentLoaded', function() {
    // 1. Initial Page Load Animations
    const animatedElements = document.querySelectorAll('.animate-on-load');
    animatedElements.forEach((el, index) => {
        el.classList.add('animate-slide-up');
        el.style.animationDelay = `${index * 0.1}s`;
    });

    // 2. Live Search Functionality for Tables
    const searchInput = document.getElementById('tableSearch');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const table = document.querySelector('.table-admin');
            
            if (table) {
                const tr = table.getElementsByTagName('tr');
                
                // Loop through all table rows, and hide those who don't match the search query
                for (let i = 1; i < tr.length; i++) {
                    let textValue = tr[i].textContent || tr[i].innerText;
                    if (textValue.toLowerCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                        tr[i].style.animation = "fadeIn 0.3s ease-in-out forwards";
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            }
        });
    }
});
