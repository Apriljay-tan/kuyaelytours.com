(function () {
    if (!document.body.classList.contains("story-page")) {
        return;
    }

    var track = document.querySelector(".story-advocacy-track");
    if (track) {
        var group = track.querySelector(".story-advocacy-group");
        if (group && track.querySelectorAll(".story-advocacy-group").length === 1) {
            var clone = group.cloneNode(true);
            clone.setAttribute("aria-hidden", "true");
            track.appendChild(clone);
        }
    }

    var reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    var portrait = document.querySelector(".story-hero-portrait img");

    if (reduce || typeof gsap === "undefined") {
        if (portrait) {
            portrait.classList.add("is-floating");
        }
        return;
    }

    if (typeof ScrollTrigger !== "undefined") {
        gsap.registerPlugin(ScrollTrigger);
    }

    var heroBits = document.querySelectorAll(".story-hero-copy > *");
    if (heroBits.length) {
        gsap.from(heroBits, {
            y: 28,
            duration: 0.9,
            stagger: 0.1,
            ease: "power3.out",
            delay: 0.12
        });
    }

    if (portrait) {
        gsap.from(portrait, {
            y: 48,
            opacity: 0,
            duration: 1.15,
            ease: "power3.out",
            delay: 0.18,
            onComplete: function () {
                portrait.classList.add("is-floating");
            }
        });
    }

    var glow = document.querySelector(".story-hero-glow");
    if (glow) {
        gsap.from(glow, {
            opacity: 0,
            duration: 1.3,
            ease: "power2.out"
        });
    }

    var watermark = document.querySelector(".story-hero-watermark");
    if (watermark) {
        gsap.from(watermark, {
            opacity: 0,
            x: 32,
            duration: 1.2,
            delay: 0.3,
            ease: "power2.out"
        });
    }

    gsap.utils.toArray(".story-reveal").forEach(function (el) {
        gsap.from(el, {
            y: 36,
            opacity: 0,
            duration: 0.85,
            ease: "power2.out",
            scrollTrigger: {
                trigger: el,
                start: "top 86%"
            }
        });
    });

    var items = gsap.utils.toArray(".story-timeline-item");
    if (items.length) {
        gsap.from(items, {
            y: 22,
            opacity: 0,
            duration: 0.55,
            stagger: 0.08,
            ease: "power2.out",
            scrollTrigger: {
                trigger: ".story-timeline",
                start: "top 80%"
            }
        });
    }

    var quoteImg = document.querySelector(".story-quote-portrait img");
    if (quoteImg) {
        gsap.from(quoteImg, {
            y: 40,
            opacity: 0,
            duration: 1,
            ease: "power3.out",
            scrollTrigger: {
                trigger: ".story-founder-quote",
                start: "top 80%"
            }
        });
    }
})();
