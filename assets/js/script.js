		////////////////////////////////////////////////////
		// 08. Smooth Scroll Js
	gsap.registerPlugin(
  ScrollTrigger,
  ScrollToPlugin
);

if (
  document.querySelector("#smooth-wrapper") &&
  document.querySelector("#smooth-content")
) {
  ScrollSmoother.create({
    wrapper: "#smooth-wrapper",
    content: "#smooth-content",
    smooth: 1.35,
    effects: true,
    smoothTouch: 0.1,
    ignoreMobileResize: true
  });

  // IMPORTANT for pin + smoother
  ScrollTrigger.defaults({
    scroller: "#smooth-wrapper"
  });
}
