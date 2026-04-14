// Homepage animations and interactions

document.addEventListener('DOMContentLoaded', function() {
    // Intersection Observer for scroll animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animated');
                
                // Trigger counter animation for stats
                if (entry.target.classList.contains('stat-card')) {
                    const counter = entry.target.querySelector('.stat-number');
                    if (counter && !counter.classList.contains('counted')) {
                        animateCounter(counter);
                        counter.classList.add('counted');
                    }
                }
            }
        });
    }, observerOptions);

    // Observe all animate-on-scroll elements
    document.querySelectorAll('.animate-on-scroll').forEach(el => {
        observer.observe(el);
    });

    // Counter animation function
    function animateCounter(element) {
        const target = parseInt(element.getAttribute('data-target'));
        const duration = 2000; // 2 seconds
        const step = target / (duration / 16); // 60fps
        let current = 0;

        const updateCounter = () => {
            current += step;
            if (current < target) {
                element.textContent = Math.floor(current).toLocaleString('id-ID');
                requestAnimationFrame(updateCounter);
            } else {
                element.textContent = target.toLocaleString('id-ID');
                // Add % sign for satisfaction rate
                if (element.getAttribute('data-target') === '98') {
                    element.textContent += '%';
                }
            }
        };

        updateCounter();
    }

    // Floating Action Button
    const fab = document.querySelector('.fab');
    const fabMenu = document.querySelector('.fab-menu');
    
    if (fab && fabMenu) {
        fab.addEventListener('click', function() {
            fabMenu.classList.toggle('show');
            
            // Rotate the FAB icon
            const icon = this.querySelector('i');
            if (icon) {
                icon.style.transform = fabMenu.classList.contains('show') ? 'rotate(45deg)' : 'rotate(0)';
            }
        });

        // Close FAB menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!fab.contains(e.target) && !fabMenu.contains(e.target)) {
                fabMenu.classList.remove('show');
                const icon = fab.querySelector('i');
                if (icon) {
                    icon.style.transform = 'rotate(0)';
                }
            }
        });
    }

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Parallax effect for hero section
    const heroSection = document.querySelector('.hero-section');
    if (heroSection) {
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const parallax = heroSection.querySelector('.hero-image');
            if (parallax) {
                parallax.style.transform = `translateY(${scrolled * 0.5}px)`;
            }
        });
    }

    // Add hover effect to event cards
    document.querySelectorAll('.event-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.zIndex = '10';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.zIndex = '';
        });
    });

    // Dynamic typing effect for hero title (optional)
    const heroTitle = document.querySelector('.hero-section h1');
    if (heroTitle) {
        const originalText = heroTitle.innerHTML;
        heroTitle.style.opacity = '0';
        
        setTimeout(() => {
            heroTitle.style.transition = 'opacity 0.8s ease';
            heroTitle.style.opacity = '1';
        }, 300);
    }
});

// FAB button removed as requested
