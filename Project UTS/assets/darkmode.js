// Dark Mode Toggle
const darkModeToggle = {
    init: function() {
        // Check saved preference
        const savedMode = localStorage.getItem('darkMode');
        if (savedMode === 'enabled') {
            document.body.classList.add('dark-mode');
        }
        
        // Add toggle button to navbar
        this.addToggleButton();
    },
    
    addToggleButton: function() {
        const navbar = document.querySelector('.nav-menu');
        if (navbar) {
            const toggleBtn = document.createElement('li');
            toggleBtn.innerHTML = `
                <a href="#" id="darkModeToggle" title="Toggle Dark Mode" style="color: inherit;">
                    <i class="fas fa-moon" id="darkModeIcon" style="color: #667eea; font-size: 18px;"></i>
                </a>
            `;
            navbar.appendChild(toggleBtn);
            
            document.getElementById('darkModeToggle').addEventListener('click', (e) => {
                e.preventDefault();
                this.toggle();
            });
        }
    },
    
    toggle: function() {
        document.body.classList.toggle('dark-mode');
        
        if (document.body.classList.contains('dark-mode')) {
            localStorage.setItem('darkMode', 'enabled');
            document.querySelector('#darkModeToggle i').classList.replace('fa-moon', 'fa-sun');
        } else {
            localStorage.setItem('darkMode', 'disabled');
            document.querySelector('#darkModeToggle i').classList.replace('fa-sun', 'fa-moon');
        }
    }
};

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    darkModeToggle.init();
});