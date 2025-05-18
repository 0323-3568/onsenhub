document.addEventListener('DOMContentLoaded', function() {
    gsap.registerPlugin(ScrollTrigger);

    gsap.utils.toArray('section:not(#features)').forEach(section => {
        gsap.from(section, {
            opacity: 0,
            y: 50,
            duration: 0.8,
            ease: "power2.out",
            scrollTrigger: {
                trigger: section,
                start: "top 80%",
                toggleActions: "play none none none"
            }
        });
    });

    const featuresSection = document.querySelector('#features');
    if (featuresSection) {
        const featureHeaderContainer = featuresSection.querySelector('.text-center.mb-5');
        const featureCards = featuresSection.querySelectorAll('.row > .col-md-4.mb-4');
        
        if (featureHeaderContainer) {
            gsap.set(featureHeaderContainer, { opacity: 0, scale: 0.5 });
        }
        if (featureCards.length > 0) {
            gsap.set(featureCards, { opacity: 0, y: 50 });
        }
        
        const featuresTl = gsap.timeline({
            scrollTrigger: {
                trigger: featuresSection,
                start: "top 80%",
                toggleActions: "play none none none",
            }
        });
        
        featuresTl.from(featuresSection, {
            opacity: 0,
            y: 50,
            duration: 1.5,
            ease: "power2.out"
        });
        
        if (featureHeaderContainer) {
            featuresTl.to(featureHeaderContainer, {
                opacity: 1,
                scale: 1.03,
                duration: 1.2,
                ease: "back.out(1.7)"
            }, "-=0.7");
        }
        if (featureCards.length > 0) {
            featuresTl.to(featureCards, {
                opacity: 1,
                y: 0,
                duration: 0.8,
                stagger: 0.2,
                ease: "power2.out"
            }, "-=0.5");
        }
    }

    const contactSection = document.querySelector('#contact');
    if (contactSection) {
        const contactUsHeading = contactSection.querySelector('.contact-us-heading');
        const contactInfoDetails = contactSection.querySelector('.contact-info-details');
        
        if (contactUsHeading) {
            gsap.from(contactUsHeading, {
                opacity: 0,
                xPercent: -100,
                duration: 0.7,
                ease: "power2.out",
                scrollTrigger: {
                    trigger: contactSection,
                    start: "top 80%",
                    toggleActions: "play none none reverse"
                }
            });
        }
        
        if (contactInfoDetails) {
            gsap.from(contactInfoDetails, {
                opacity: 0,
                xPercent: 100,
                duration: 0.7,
                ease: "power2.out",
                scrollTrigger: {
                    trigger: contactSection,
                    start: "top 80%",
                    toggleActions: "play none none reverse"
                }
            });
        }
    }

    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                const navbarHeight = document.querySelector('.navbar') ? document.querySelector('.navbar').offsetHeight : 0;
                window.scrollTo({
                    top: targetElement.offsetTop - navbarHeight,
                    behavior: 'smooth'
                });
            }
        });
    });
});