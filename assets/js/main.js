/*----------theme js-----------------*/

/*====================================
01. Mobile Menu js
02. Header Search js
03. sticky
04. Loder 
05. counterUp
06. Hero Home one Slides
07. brand list Active
08. brand list2
09. Testi list 
10. Testi list2
11. Testi list3
12. Testi list inner
13. course program
14. Venubox
15. Team icon active js
16. menu button cart sidebar
17. Cart Plus Minus Button
18. Shipping Form Toggle
19. accordion js
20. barfiller script
21. WOW active js 
22. Sidebar
23. scroll btn
24. preloader js
25. Portfolio Isotope 
26. Custom Tab
27. count down timer
=====================================*/


(function ($) {
    'use strict';
    var $window = $(window);
    var $body = $('body');

    if ($('.preloader').length) {
        $('.preloader').delay(200).fadeOut(500);
    }

    // Mobile Menu js
    if ($.fn.meanmenu) {
        $('.mobile-menu nav').meanmenu({
            meanScreenWidth: "991",
            meanMenuContainer: ".mobile-menu",
            meanMenuOpen: "<span></span> <span></span> <span></span>",
            onePage: false,
        });
    }

    //Header Search js
    if ($('.search-box-outer').length) {
        $('.search-box-outer').on('click', function () {
            $('body').addClass('search-active');
        });
        $('.close-search').on('click', function () {
            $('body').removeClass('search-active');
        });
    }

    // sticky
    var wind = $(window);
    var sticky = $('#sticky-header');
    wind.on('scroll', function () {
        var scroll = wind.scrollTop();
        if (scroll < 100) {
            sticky.removeClass('sticky');
        } else {
            sticky.addClass('sticky');
        }
    });

    // counterUp
    $('.counter').counterUp({
        delay: 10,
        time: 1000
    });

    // Data backgrond image
    $("[data-background]").each(function () {
        $(this).css("background-image", "url(" + $(this).attr("data-background") + ")");
    });


    jQuery(document).ready(function ($) {
        "use strict";

        // =======< accordion js >========
        $(".accordion > li:eq(0) a").addClass("active").next().slideDown();
        $('.accordion a').on('click', function (j) {
            var dropDown = $(this).closest("li").find("p");

            $(this).closest(".accordion").find("p").not(dropDown).slideUp();

            if ($(this).hasClass("active")) {
                $(this).removeClass("active");
            } else {
                $(this).closest(".accordion").find("a.active").removeClass("active");
                $(this).addClass("active");
            }

            dropDown.stop(false, true).slideToggle();

            j.preventDefault();
        });


        //=====< barfiller script >====
        $('#bar1').barfiller({
            duration: 7000
        });
        $('#bar2').barfiller({
            duration: 7000
        });
        $('#bar3').barfiller({
            duration: 7000
        });
        $('#bar4').barfiller({
            duration: 7000
        });
        $('#bar5').barfiller({
            duration: 7000
        });

    });

    /*---------------------
    WOW active js 
    --------------------- */
    new WOW().init();

    // Sidebar
    jQuery(document).ready(function (o) {
        0 < o(".offset-side-bar").length &&
            o(".offset-side-bar").on("click", function (e) {
                e.preventDefault(), e.stopPropagation(), o(".cart-group").addClass("isActive");
            }),
            0 < o(".close-side-widget").length &&
            o(".close-side-widget").on("click", function (e) {
                e.preventDefault(), o(".cart-group").removeClass("isActive");
            }),
            0 < o(".navSidebar-button").length &&
            o(".navSidebar-button").on("click", function (e) {
                e.preventDefault(), e.stopPropagation(), o(".info-group").addClass("isActive");
            }),
            0 < o(".close-side-widget").length &&
            o(".close-side-widget").on("click", function (e) {
                e.preventDefault(), o(".info-group").removeClass("isActive");
            }),
            o("body").on("click", function (e) {
                o(".info-group").removeClass("isActive"), o(".cart-group").removeClass("isActive");
            }),
            o(".xs-sidebar-widget").on("click", function (e) {
                e.stopPropagation();
            }),
            0 < o(".xs-modal-popup").length &&
            o(".xs-modal-popup").magnificPopup({
                type: "inline",
                fixedContentPos: !2,
                fixedBgPos: !0,
                overflowY: "auto",
                closeBtnInside: !2,
                callbacks: {
                    beforeOpen: function () {
                        this.st.mainClass = "my-mfp-slide-bottom xs-promo-popup";
                    },
                },
            });
    });

    // scroll btn
    if ($('.prgoress_indicator path').length) {
        var progressPath = document.querySelector('.prgoress_indicator path');
        var pathLength = progressPath.getTotalLength();
        progressPath.style.transition = progressPath.style.WebkitTransition = 'none';
        progressPath.style.strokeDasharray = pathLength + ' ' + pathLength;
        progressPath.style.strokeDashoffset = pathLength;
        progressPath.getBoundingClientRect();
        progressPath.style.transition = progressPath.style.WebkitTransition = 'stroke-dashoffset 10ms linear';
        var updateProgress = function () {
            var scroll = $(window).scrollTop();
            var height = $(document).height() - $(window).height();
            var progress = pathLength - (scroll * pathLength / height);
            progressPath.style.strokeDashoffset = progress;
        }
        updateProgress();
        $(window).on('scroll', updateProgress);
        var offset = 250;
        var duration = 550;
        jQuery(window).on('scroll', function () {
            if (jQuery(this).scrollTop() > offset) {
                jQuery('.prgoress_indicator').addClass('active-progress');
            } else {
                jQuery('.prgoress_indicator').removeClass('active-progress');
            }
        });
        jQuery('.prgoress_indicator').on('click', function (event) {
            event.preventDefault();
            jQuery('html, body').animate({ scrollTop: 0 }, duration);
            return false;
        });
    }


    // Home-1 destination__swiper js
    var slider = new Swiper(".destination__swiper", {
        speed: 1500,
        slidesPerView: 3,
        spaceBetween: 30,
        loop: true,
        autoplay: false,
        breakpoints: {
            1920: {
                slidesPerView: 4,
            },
            1400: {
                slidesPerView: 4,
            },
            1200: {
                slidesPerView: 4,
            },
            992: {
                slidesPerView: 3,
            },
            768: {
                slidesPerView: 2,
            },
            576: {
                slidesPerView: 1,
            },
            0: {
                slidesPerView: 1,
            },
        },
        // Navigation arrows
        navigation: {
            nextEl: ".slider-prev",
            prevEl: ".slider-next",
        },
    });

    // Home-1 Brand Active js
    var slider = new Swiper(".band-active", {
        speed: 1500,
        slidesPerView: 5,
        spaceBetween: 30,
        loop: true,
        autoplay: false,
        breakpoints: {
            1920: {
                slidesPerView: 5,
            },
            1400: {
                slidesPerView: 5,
            },
            1200: {
                slidesPerView: 5,
            },
            992: {
                slidesPerView: 4,
            },
            768: {
                slidesPerView: 2,
            },
            576: {
                slidesPerView: 2,
            },
            0: {
                slidesPerView: 1,
            },
        },
    });

    // Home-1 popular__slider__active js
    var slider = new Swiper(".popular__slider__active", {
        speed: 1500,
        slidesPerView: 4,
        spaceBetween: 30,
        loop: true,
        autoplay: false,
        breakpoints: {
            1920: {
                slidesPerView: 4,
            },
            1400: {
                slidesPerView: 4,
            },
            1200: {
                slidesPerView: 3,
            },
            992: {
                slidesPerView: 3,
            },
            768: {
                slidesPerView: 2,
            },
            576: {
                slidesPerView: 1,
            },
            0: {
                slidesPerView: 1,
            },
        },
        // Navigation arrows
        navigation: {
            nextEl: ".slider-prev",
            prevEl: ".slider-next",
        },
    });

    // Home-1 Classic popular__slider__active 2 js
    var slider = new Swiper(".popular__slider__active_2", {
        speed: 1500,
        slidesPerView: 4,
        spaceBetween: 30,
        loop: true,
        autoplay: false,
        breakpoints: {
            1920: {
                slidesPerView: 3,
            },
            1400: {
                slidesPerView: 3,
            },
            1200: {
                slidesPerView: 3,
            },
            992: {
                slidesPerView: 3,
            },
            768: {
                slidesPerView: 2,
            },
            576: {
                slidesPerView: 1,
            },
            0: {
                slidesPerView: 1,
            },
        },
        // Navigation arrows
        navigation: {
            nextEl: ".slider-prev",
            prevEl: ".slider-next",
        },
    });

    // Home-1 Classic testimonial-active js
    var slider = new Swiper(".testimonial-active", {
        speed: 1500,
        slidesPerView: 3,
        spaceBetween: 30,
        loop: true,
        autoplay: false,
        breakpoints: {
            1920: {
                slidesPerView: 2,
            },
            1400: {
                slidesPerView: 2,
            },
            1200: {
                slidesPerView: 2,
            },
            992: {
                slidesPerView: 2,
            },
            768: {
                slidesPerView: 1,
            },
            576: {
                slidesPerView: 1,
            },
            0: {
                slidesPerView: 1,
            },
        },
        // Navigation arrows
        navigation: {
            nextEl: ".slider-prev",
            prevEl: ".slider-next",
        },
    });

    // Home-1 classic gallary-active js
    var slider = new Swiper(".gallary-active", {
        speed: 1500,
        slidesPerView: 5,
        spaceBetween: 25,
        slidesPerView: 1.5,
        centeredSlides: true,
        loop: true,
        autoplay: false,
        breakpoints: {
            1920: {
                slidesPerView: 5,
            },
            1400: {
                slidesPerView: 5,
            },
            1200: {
                slidesPerView: 4,
            },
            992: {
                slidesPerView: 3,
            },
            768: {
                slidesPerView: 2,
            },
            576: {
                slidesPerView: 1,
            },
            0: {
                slidesPerView: 1,
            },
        },
        // Navigation arrows
        navigation: {
            nextEl: ".slider-prev",
            prevEl: ".slider-next",
        },
    });

    // Home-2 testimonial-active js
    var slider = new Swiper(".testimonial__active2", {
        speed: 1500,
        slidesPerView: 3,
        spaceBetween: 30,
        loop: true,
        autoplay: false,
        breakpoints: {
            1920: {
                slidesPerView: 1,
            },
            1400: {
                slidesPerView: 1,
            },
            1200: {
                slidesPerView: 1,
            },
            992: {
                slidesPerView: 1,
            },
            768: {
                slidesPerView: 1,
            },
            576: {
                slidesPerView: 1,
            },
            0: {
                slidesPerView: 1,
            },
        },
        // Navigation arrows
        navigation: {
            nextEl: ".slider-prev",
            prevEl: ".slider-next",
        },
    });

    // Home-3 slider__active js
    var slider = new Swiper(".slider__active", {
        loop: true,
        autoplay: {
            speed: 3000,
            delay: 10000,
            disableOnInteraction: false,
            autoplay: false,
        },
        effect: "fade",
        navigation: {
            nextEl: '.slider-prev',
            prevEl: '.slider-next',
        },
        // Navigation arrows
        navigation: {
            nextEl: ".slider-prev",
            prevEl: ".slider-next",
        },
        pagination: {
            el: '.travel-slider-dots',
            clickable: true,
        },
    });

    // Home-3 testimonial-active js
    var slider = new Swiper(".testimonial__active_3", {
        speed: 1500,
        slidesPerView: 3,
        spaceBetween: 30,
        loop: true,
        autoplay: false,
        breakpoints: {
            1920: {
                slidesPerView: 3,
            },
            1400: {
                slidesPerView: 3,
            },
            1200: {
                slidesPerView: 3,
            },
            992: {
                slidesPerView: 3,
            },
            768: {
                slidesPerView: 2,
            },
            576: {
                slidesPerView: 1,
            },
            0: {
                slidesPerView: 1,
            },
        },
        // Navigation arrows
        navigation: {
            nextEl: ".slider-prev",
            prevEl: ".slider-next",
        },
    });

    // Home-3 testimonial-active js
    var slider = new Swiper(".facilities__swiper", {
        speed: 1500,
        slidesPerView: 3,
        spaceBetween: 30,
        loop: true,
        autoplay: false,
        breakpoints: {
            1920: {
                slidesPerView: 2,
            },
            1400: {
                slidesPerView: 2,
            },
            1200: {
                slidesPerView: 2,
            },
            992: {
                slidesPerView: 1,
            },
            768: {
                slidesPerView: 2,
            },
            576: {
                slidesPerView: 1,
            },
            0: {
                slidesPerView: 1,
            },
        },
        // Navigation arrows
        navigation: {
            nextEl: ".slider-prev",
            prevEl: ".slider-next",
        },
    });

    // Home-3 testimonial-active js
    var slider = new Swiper(".destination__swiper__inner", {
        speed: 1500,
        slidesPerView: 3,
        spaceBetween: 30,
        loop: true,
        autoplay: false,
        breakpoints: {
            1920: {
                slidesPerView: 1,
            },
            1400: {
                slidesPerView: 1,
            },
            1200: {
                slidesPerView: 1,
            },
            992: {
                slidesPerView: 1,
            },
            768: {
                slidesPerView: 2,
            },
            576: {
                slidesPerView: 1,
            },
            0: {
                slidesPerView: 1,
            },
        },
        // Navigation arrows
        navigation: {
            nextEl: ".slider-prev",
            prevEl: ".slider-next",
        },
    });



    //magnificPopup Js
    $(".popup-image").magnificPopup({
        type: "image",
        gallery: {
            enabled: true,
        },
    });

    // type animation text
    const text = "kuya ely";
    const typingEl = document.querySelector(".typing");
    if (typingEl) {
        function typingEffect() {
            typingEl.textContent = "";
            let i = 0;
            function typeChar() {
                typingEl.textContent = text.substring(0, i + 1);
                i++;

                if (i < text.length) {
                    setTimeout(typeChar, 200);
                } else {
                    setTimeout(typingEffect, 1000);
                }
            }
            typeChar();
        }
        typingEffect();
    }

    /* Image Reveal Animation */
    if ($('.reveal').length) {
        gsap.registerPlugin(ScrollTrigger);
        let revealContainers = document.querySelectorAll(".reveal");
        revealContainers.forEach((container) => {
            let image = container.querySelector("img");
            let tl = gsap.timeline({
                scrollTrigger: {
                    trigger: container,
                    toggleActions: "play none none none"
                }
            });
            tl.set(container, {
                autoAlpha: 1
            });
            tl.from(container, 1, {
                xPercent: -100,
                ease: Power2.out
            });
            tl.from(image, 1, {
                xPercent: 100,
                scale: 1,
                delay: -1,
                ease: Power2.out
            });
        });
    }

    // 1. panel pin section //
	let pr = gsap.matchMedia();

	pr.add("(min-width: 1199px)", () => {
		let tl = gsap.timeline();
		let panels = document.querySelectorAll('.tp-panel-pin');

		panels.forEach((section) => {

			// default value
			let defaultStart = "top 10%";
			let defaultEnd   = "bottom 99%";

			// if attribute exists â†’ override
			let startVal = section.dataset.start || defaultStart;
			let endVal   = section.dataset.end   || defaultEnd;

			tl.to(section, {
				scrollTrigger: {
					trigger: section,
					pin: section,
					scrub: 1,
					start: startVal,
					end: endVal,
					endTrigger: '.tp-panel-pin-area',
					pinSpacing: false,
					markers: false,
				}
			});

		});
	});


    //Video Modal cursor
    var v_cursor = document.getElementById("video_cursor");
    var video_icon = document.querySelector(".video__area a");

    if (video_icon) {
        video_icon.addEventListener("mousemove", (e) => {
            let x = e.clientX;
            let y = e.clientY;
            v_cursor.style.top = y + "px";
            v_cursor.style.left = x + "px";
            v_cursor.style.transform = "translate(-50%, -50%) scale(1)";
        });

        video_icon.addEventListener("mouseout", (e) => {
            v_cursor.style.transform = "translate(-50%, -50%) scale(0)";
        });
    }


    /* Text Effect Animation */
    function initHeadingAnimation() {

        if ($('.text-effect').length) {
            var textheading = $(".text-effect");

            if (textheading.length === 0) return; gsap.registerPlugin(SplitText); textheading.each(function (index, el) {

                el.split = new SplitText(el, {
                    type: "lines,words,chars",
                    linesClass: "split-line"
                });

                if ($(el).hasClass('text-effect')) {
                    gsap.set(el.split.chars, {
                        opacity: .3,
                        x: "-7",
                    });
                }
                el.anim = gsap.to(el.split.chars, {
                    scrollTrigger: {
                        trigger: el,
                        start: "top 92%",
                        end: "top 60%",
                        markers: false,
                        scrub: 1,
                    },

                    x: "0",
                    y: "0",
                    opacity: 1,
                    duration: .7,
                    stagger: 0.2,
                });

            });
        }

        if ($('.text-anime-1').length) {
            let staggerAmount = 0.05,
                translateXValue = 0,
                delayValue = 0.5,
                animatedTextElements = document.querySelectorAll('.text-anime-1');

            animatedTextElements.forEach((element) => {
                let animationSplitText = new SplitText(element, { type: "chars, words" });
                gsap.from(animationSplitText.words, {
                    duration: 1,
                    delay: delayValue,
                    x: 20,
                    autoAlpha: 0,
                    stagger: staggerAmount,
                    scrollTrigger: { trigger: element, start: "top 85%" },
                });
            });
        }

        if ($('.text-anime-2').length) {
            let staggerAmount = 0.03,
                translateXValue = 20,
                delayValue = 0.1,
                easeType = "power2.out",
                animatedTextElements = document.querySelectorAll('.text-anime-2');

            animatedTextElements.forEach((element) => {
                let animationSplitText = new SplitText(element, { type: "chars, words" });
                gsap.from(animationSplitText.chars, {
                    duration: 1,
                    delay: delayValue,
                    x: translateXValue,
                    autoAlpha: 0,
                    stagger: staggerAmount,
                    ease: easeType,
                    scrollTrigger: { trigger: element, start: "top 85%" },
                });
            });
        }

        if ($('.text-anime-3').length) {
            let animatedTextElements = document.querySelectorAll('.text-anime-3');

            animatedTextElements.forEach((element) => {
                //Reset if needed
                if (element.animation) {
                    element.animation.progress(1).kill();
                    element.split.revert();
                }

                element.split = new SplitText(element, {
                    type: "lines,words,chars",
                    linesClass: "split-line",
                });
                gsap.set(element, { perspective: 400 });

                gsap.set(element.split.chars, {
                    opacity: 0,
                    x: "50",
                });

                element.animation = gsap.to(element.split.chars, {
                    scrollTrigger: { trigger: element, start: "top 90%" },
                    x: "0",
                    y: "0",
                    rotateX: "0",
                    opacity: 1,
                    duration: 1,
                    ease: Back.easeOut,
                    stagger: 0.02,
                });
            });
        }
    }

    if (document.fonts && document.fonts.ready) {
        document.fonts.ready.then(() => {
            initHeadingAnimation();
        });
    } else {
        window.addEventListener("load", initHeadingAnimation);
    }

    /* Parallaxie js */
    var $parallaxie = $('.parallaxie');
    if ($parallaxie.length && ($window.width() > 991)) {
        if ($window.width() > 768) {
            $parallaxie.parallaxie({
                speed: 0.55,
                offset: 0,
            });
        }
    }

    /* Popup Video */
    if ($('.popup-video').length) {
        $('.popup-video').magnificPopup({
            type: 'iframe',
            mainClass: 'mfp-fade',
            removalDelay: 160,
            preloader: false,
            fixedContentPos: true
        });
    }

    // section border animation
    gsap.registerPlugin(ScrollTrigger);

    gsap.fromTo(".section-border",
        { scaleX: 0, transformOrigin: "left center" },
        {
            scaleX: 1,
            duration: 1.2,
            ease: "power2.out",
            scrollTrigger: {
                trigger: ".section-border",
                start: "top 80%",
                toggleActions: "play none none reverse",
            }
        }
    );

    ScrollTrigger.create({
        start: "top 80%",
        end: "bottom 60%",
        onLeaveBack: () => {
            gsap.to(".section-border", {
                scaleX: 0,
                transformOrigin: "right center",
                duration: 1,
                ease: "power2.inOut"
            });
        }
    });


    /* Image Hover Effect Start */
    const dataItemHover = () => {
        const initHoverEffect = (container, images) => {
            const hoverInstance = new hoverEffect({
                parent: container.get(0),
                intensity: container.data("intensity") || undefined,
                speedIn: container.data("speedin") || undefined,
                speedOut: container.data("speedout") || undefined,
                easing: container.data("easing") || undefined,
                hover: container.data("hover") || undefined,
                image1: images.eq(0).attr("src"),
                image2: images.eq(0).attr("src"),
                displacementImage: "assets/images/demo-image/image-effect.jpg",
                imagesRatio: images[0].width / images[0].height,
                hover: false
            });

            container.closest(".data-item-hover")
                .on("mouseenter", () => hoverInstance.next())
                .on("mouseleave", () => hoverInstance.previous());
        };

        const setupHoverAnimations = () => {
            $(".data-img-hover").each(function () {
                const currentContainer = $(this);
                const imageElements = currentContainer.find("img");
                const firstImage = imageElements.eq(0);

                if (firstImage[0].complete) {
                    initHoverEffect(currentContainer, imageElements);
                } else {
                    firstImage.on("load", () => {
                        initHoverEffect(currentContainer, imageElements);
                    });
                }
            });
        };

        setupHoverAnimations();
    }

    // Call this function when page loads
    document.addEventListener("DOMContentLoaded", () => {
        dataItemHover();
    });

    $(document).on('click', '.nav-link[data-bs-toggle="tab"]', function (e) {
        e.preventDefault();
        var target = $(this).attr('href');

        $('.nav-link').removeClass('active');
        $('.tab-pane').removeClass('active show');

        $(this).addClass('active');
        $(target).addClass('active show');
    });




})(jQuery);




// testimonal opacity
gsap.registerPlugin(ScrollTrigger);

const width = window.innerWidth;
const boxes = gsap.utils.toArray(".single-testi-box");
const container = document.getElementById("scroll-container");
const stickyBox = document.getElementById("sticky-box");

// Sticky right box
if (width >= 1200) {
    ScrollTrigger.create({
        trigger: stickyBox,
        start: "top 9%",
        endTrigger: container,
        end:
            width >= 1700
                ? "bottom 103.2%"
                : width >= 1400
                    ? "bottom 180%"
                    : "bottom 160%",
        pin: true,
        pinSpacing: false,
        scrub: false,
    });
}


// Bokking Tab

function changeTab(btn) {
    document.querySelectorAll(".tab").forEach(t => t.classList.remove("active"));
    btn.classList.add("active");
    let tabID = btn.getAttribute("data-tab");

    document.querySelectorAll(".add-bg").forEach(box => {
        box.classList.remove("active");
    });

    document.getElementById(tabID).classList.add("active");
}



// type animation text
const text = "ADVENTURE";
const typingEl = document.querySelector(".typings");
if (typingEl) {
    function typingEffect() {
        typingEl.textContent = "";
        let i = 0;
        function typeChar() {
            typingEl.textContent = text.substring(0, i + 1);
            i++;

            if (i < text.length) {
                setTimeout(typeChar, 200);
            }
        }
        typeChar();
    }
    typingEffect();
}
