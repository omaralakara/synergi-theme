(() => {
  const carousel = document.querySelector("[data-why-carousel]");
  if (!carousel) return;

  const cards = [...carousel.querySelectorAll("[data-why-card]")];
  const pagination = [...carousel.querySelectorAll("[data-why-go]")];
  const stage = carousel.querySelector("[data-why-stage]");
  const currentNumber = carousel.querySelector("[data-why-current]");
  const status = carousel.querySelector("[data-why-status]");
  const reducedMotionQuery = window.matchMedia("(prefers-reduced-motion: reduce)");
  const autoAdvanceDelay = 3500;
  let activeIndex = 0;
  let autoAdvanceTimer = null;
  let pointerStartX = null;
  let pointerId = null;
  let suppressCardClick = false;
  let stageHasPointerCapture = false;
  const TAP_SLOP_PX = 12;
  let carouselIsVisible = !("IntersectionObserver" in window);

  if (!cards.length || cards.length !== pagination.length) return;

  function normalizedIndex(index) {
    return (index + cards.length) % cards.length;
  }

  function showCard(index, announce = true) {
    activeIndex = normalizedIndex(index);

    cards.forEach((card, cardIndex) => {
      const position = normalizedIndex(cardIndex - activeIndex);
      const isActive = position === 0;

      card.dataset.whyPosition = String(position);
      card.classList.toggle("is-active", isActive);
      card.setAttribute("aria-hidden", String(!isActive));
    });

    pagination.forEach((button, buttonIndex) => {
      const isActive = buttonIndex === activeIndex;
      button.classList.toggle("is-active", isActive);
      button.setAttribute("aria-pressed", String(isActive));
    });

    if (currentNumber) {
      currentNumber.textContent = String(activeIndex + 1).padStart(2, "0");
    }

    if (announce && status) {
      const title = cards[activeIndex].querySelector("h3")?.textContent.trim();
      status.textContent = `Showing reason ${activeIndex + 1}: ${title}.`;
    }
  }

  function stopAutoAdvance() {
    window.clearInterval(autoAdvanceTimer);
    autoAdvanceTimer = null;
  }

  function startAutoAdvance() {
    stopAutoAdvance();
    if (reducedMotionQuery.matches || document.hidden || !carouselIsVisible) return;

    autoAdvanceTimer = window.setInterval(() => {
      showCard(activeIndex + 1, false);
    }, autoAdvanceDelay);
  }

  function selectCard(index) {
    showCard(index);
    startAutoAdvance();
  }

  pagination.forEach((button, index) => {
    button.addEventListener("click", () => selectCard(index));
  });

  cards.forEach((card, index) => {
    card.addEventListener("click", (event) => {
      if (suppressCardClick) {
        event.preventDefault();
        return;
      }
      if (index !== activeIndex) selectCard(index);
    });
  });

  stage?.addEventListener("keydown", (event) => {
    if (!["ArrowLeft", "ArrowRight", "Home", "End"].includes(event.key)) return;
    event.preventDefault();

    if (event.key === "ArrowLeft") selectCard(activeIndex - 1);
    if (event.key === "ArrowRight") selectCard(activeIndex + 1);
    if (event.key === "Home") selectCard(0);
    if (event.key === "End") selectCard(cards.length - 1);
  });

  stage?.addEventListener("pointerdown", (event) => {
    if (!event.isPrimary || (event.button !== undefined && event.button !== 0)) return;
    pointerStartX = event.clientX;
    pointerId = event.pointerId;
    stageHasPointerCapture = false;
    // Not capturing on pointerdown: capture retargets the following click to
    // the stage, which stops the cards from being tapped. See pointermove.
  });

  stage?.addEventListener("pointermove", (event) => {
    if (
      pointerStartX === null ||
      event.pointerId !== pointerId ||
      stageHasPointerCapture
    ) {
      return;
    }
    if (Math.abs(event.clientX - pointerStartX) > TAP_SLOP_PX) {
      stage.setPointerCapture?.(event.pointerId);
      stageHasPointerCapture = true;
    }
  });

  stage?.addEventListener("pointerup", (event) => {
    if (pointerStartX === null || event.pointerId !== pointerId) return;

    const distance = event.clientX - pointerStartX;
    if (stageHasPointerCapture) {
      stage.releasePointerCapture?.(event.pointerId);
      stageHasPointerCapture = false;
    }
    pointerStartX = null;
    pointerId = null;

    if (Math.abs(distance) >= 45) {
      suppressCardClick = true;
      window.setTimeout(() => {
        suppressCardClick = false;
      }, 0);
      selectCard(activeIndex + (distance < 0 ? 1 : -1));
    }
  });

  stage?.addEventListener("dragstart", (event) => event.preventDefault());

  stage?.addEventListener("pointercancel", () => {
    pointerStartX = null;
    pointerId = null;
  });

  stage?.addEventListener("mouseenter", stopAutoAdvance);
  stage?.addEventListener("mouseleave", startAutoAdvance);
  carousel.addEventListener("focusin", stopAutoAdvance);
  carousel.addEventListener("focusout", (event) => {
    if (!carousel.contains(event.relatedTarget)) startAutoAdvance();
  });

  document.addEventListener("visibilitychange", () => {
    if (document.hidden) stopAutoAdvance();
    else startAutoAdvance();
  });

  reducedMotionQuery.addEventListener("change", startAutoAdvance);

  if ("IntersectionObserver" in window) {
    new IntersectionObserver(
      ([entry]) => {
        carouselIsVisible = entry.isIntersecting;
        if (carouselIsVisible) startAutoAdvance();
        else stopAutoAdvance();
      },
      { rootMargin: "80px 0px", threshold: 0.12 },
    ).observe(carousel);
  }

  showCard(0, false);
  startAutoAdvance();
})();
