document.documentElement.classList.add("js");

const header = document.querySelector("[data-header]");
const menuToggle = document.querySelector(".menu-toggle");
const navigation = document.querySelector(".primary-nav");
const submenuToggles = [...document.querySelectorAll(".submenu-toggle")];
const mobileNavigation = window.matchMedia("(max-width: 74rem)");
const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)");

function setHeaderState() {
  if (!header) return;
  header.classList.toggle("is-scrolled", window.scrollY > 24);
}

function closeSubmenus(except = null) {
  submenuToggles.forEach((toggle) => {
    if (toggle === except) return;
    toggle.setAttribute("aria-expanded", "false");
    toggle.closest(".has-submenu")?.classList.remove("is-open");
  });
}

function syncNavigationInteractivity() {
  if (!navigation || !menuToggle) return;
  navigation.inert =
    mobileNavigation.matches &&
    menuToggle.getAttribute("aria-expanded") !== "true";
}

function closeMenu() {
  if (!menuToggle || !navigation || !header) return;
  menuToggle.setAttribute("aria-expanded", "false");
  menuToggle.querySelector(".sr-only").textContent = "Open navigation";
  navigation.classList.remove("is-open");
  header.classList.remove("is-menu-open");
  document.body.classList.remove("menu-open");
  closeSubmenus();
  syncNavigationInteractivity();
}

if (menuToggle && navigation && header) {
  menuToggle.addEventListener("click", () => {
    const willOpen = menuToggle.getAttribute("aria-expanded") !== "true";
    menuToggle.setAttribute("aria-expanded", String(willOpen));
    menuToggle.querySelector(".sr-only").textContent = willOpen
      ? "Close navigation"
      : "Open navigation";
    navigation.classList.toggle("is-open", willOpen);
    header.classList.toggle("is-menu-open", willOpen);
    document.body.classList.toggle("menu-open", willOpen);
    syncNavigationInteractivity();

    if (willOpen) {
      const focusDelay = window.matchMedia("(prefers-reduced-motion: reduce)")
        .matches
        ? 0
        : 320;
      window.setTimeout(() => navigation.querySelector("a")?.focus(), focusDelay);
    }
  });

  navigation.addEventListener("click", (event) => {
    const link = event.target.closest("a");
    if (link && !link.classList.contains("header-cta")) closeMenu();
  });
}

submenuToggles.forEach((toggle) => {
  toggle.addEventListener("click", () => {
    const isOpen = toggle.getAttribute("aria-expanded") === "true";
    closeSubmenus(toggle);
    toggle.setAttribute("aria-expanded", String(!isOpen));
    toggle.closest(".has-submenu")?.classList.toggle("is-open", !isOpen);
  });
});

document.addEventListener("click", (event) => {
  if (!event.target.closest(".has-submenu")) closeSubmenus();
});

document.addEventListener("keydown", (event) => {
  const menuIsOpen = menuToggle?.getAttribute("aria-expanded") === "true";

  if (event.key === "Escape") {
    closeMenu();
    if (menuIsOpen) menuToggle?.focus();
    return;
  }

  if (event.key !== "Tab" || !menuIsOpen || !mobileNavigation.matches) return;

  const focusable = [menuToggle, ...navigation.querySelectorAll("a, button")]
    .filter((element) => element.getClientRects().length > 0 && !element.disabled);
  const first = focusable[0];
  const last = focusable.at(-1);

  if (event.shiftKey && document.activeElement === first) {
    event.preventDefault();
    last.focus();
  } else if (!event.shiftKey && document.activeElement === last) {
    event.preventDefault();
    first.focus();
  }
});

window.addEventListener("scroll", setHeaderState, { passive: true });
setHeaderState();
syncNavigationInteractivity();

const partnerMarquee = document.querySelector("[data-partner-marquee]");

if (partnerMarquee) {
  const partnerTrack = partnerMarquee.querySelector(".partner-marquee-track");
  const reducedPartnerMotion = window.matchMedia("(prefers-reduced-motion: reduce)");
  let partnerStrip = null;
  let partnerLogos = [];
  let partnerLoopWidth = 0;
  let partnerOffset = 0;
  let partnerPointerId = null;
  let partnerPointerStartX = 0;
  let partnerStartOffset = 0;
  let partnerHasDragged = false;
  let partnerIsDragging = false;
  let partnerIsFocused = false;
  let partnerPreviousFrame = null;
  let partnerFrameId = 0;
  let partnerSectionVisible = !("IntersectionObserver" in window);

  if (partnerTrack) {
    [...partnerTrack.querySelectorAll("[data-partner-logo]")].forEach(
      (logo, index) => {
        logo.dataset.partnerLogoId = String(index);
      },
    );

    partnerStrip = document.createElement("div");
    partnerStrip.className = "partner-marquee-strip";
    partnerTrack.before(partnerStrip);
    partnerStrip.append(partnerTrack);

    ["beforebegin", "afterend"].forEach((position) => {
      const duplicateTrack = partnerTrack.cloneNode(true);
      duplicateTrack.setAttribute("aria-hidden", "true");
      duplicateTrack.querySelectorAll("a").forEach((logo) => {
        logo.tabIndex = -1;
      });
      partnerTrack.insertAdjacentElement(position, duplicateTrack);
    });

    partnerLogos = [...partnerMarquee.querySelectorAll("[data-partner-logo]")];
  }

  function normalizePartnerOffset() {
    if (!partnerLoopWidth) return;

    while (partnerOffset <= -partnerLoopWidth * 2) {
      partnerOffset += partnerLoopWidth;
    }

    while (partnerOffset >= 0) {
      partnerOffset -= partnerLoopWidth;
    }
  }

  function renderPartnerOffset() {
    if (!partnerStrip) return;
    partnerStrip.style.transform = `translate3d(${partnerOffset}px, 0, 0)`;
  }

  function setPartnerOffset(nextOffset) {
    partnerOffset = nextOffset;
    normalizePartnerOffset();
    renderPartnerOffset();
  }

  function shouldMovePartnerMarquee() {
    return (
      partnerSectionVisible &&
      !document.hidden &&
      !reducedPartnerMotion.matches &&
      !partnerIsDragging &&
      !partnerIsFocused &&
      partnerLoopWidth > 0
    );
  }

  function stopPartnerMarquee() {
    if (partnerFrameId) {
      window.cancelAnimationFrame(partnerFrameId);
      partnerFrameId = 0;
    }
    partnerPreviousFrame = null;
  }

  function schedulePartnerMarquee() {
    if (partnerFrameId || !shouldMovePartnerMarquee()) return;
    partnerFrameId = window.requestAnimationFrame(movePartnerMarquee);
  }

  partnerMarquee.addEventListener("pointerdown", (event) => {
    if (event.button !== undefined && event.button !== 0) return;
    partnerPointerId = event.pointerId;
    partnerPointerStartX = event.clientX;
    partnerStartOffset = partnerOffset;
    partnerHasDragged = false;
    partnerIsDragging = true;
    partnerMarquee.classList.add("is-dragging");
    partnerMarquee.setPointerCapture?.(event.pointerId);
    stopPartnerMarquee();
  });

  partnerMarquee.addEventListener(
    "pointermove",
    (event) => {
      if (event.pointerId !== partnerPointerId) return;
      const distance = event.clientX - partnerPointerStartX;

      if (Math.abs(distance) > 4) {
        partnerHasDragged = true;
        setPartnerOffset(partnerStartOffset + distance);
        event.preventDefault();
      }
    },
    { passive: false },
  );

  function finishPartnerDrag(event) {
    if (event.pointerId !== partnerPointerId) return;
    partnerMarquee.releasePointerCapture?.(event.pointerId);
    partnerMarquee.classList.remove("is-dragging");
    partnerPointerId = null;
    partnerIsDragging = false;
    schedulePartnerMarquee();
  }

  partnerMarquee.addEventListener("pointerup", finishPartnerDrag);
  partnerMarquee.addEventListener("pointercancel", finishPartnerDrag);
  partnerMarquee.addEventListener("dragstart", (event) => event.preventDefault());
  partnerMarquee.addEventListener(
    "click",
    (event) => {
      if (!partnerHasDragged) return;
      event.preventDefault();
      event.stopPropagation();
      partnerHasDragged = false;
    },
    true,
  );

  function measurePartnerMarquee(resetPosition = false) {
    if (!partnerTrack) return;
    const nextLoopWidth = partnerTrack.getBoundingClientRect().width;
    if (!nextLoopWidth) return;

    const loopProgress = partnerLoopWidth
      ? (partnerOffset + partnerLoopWidth) / partnerLoopWidth
      : 0;
    partnerLoopWidth = nextLoopWidth;
    setPartnerOffset(
      resetPosition
        ? -partnerLoopWidth
        : -partnerLoopWidth + loopProgress * partnerLoopWidth,
    );
    schedulePartnerMarquee();
  }

  function movePartnerMarquee(timestamp) {
    partnerFrameId = 0;
    if (!shouldMovePartnerMarquee()) {
      partnerPreviousFrame = null;
      return;
    }

    if (partnerPreviousFrame === null) partnerPreviousFrame = timestamp;
    const elapsed = Math.min(timestamp - partnerPreviousFrame, 48);
    partnerPreviousFrame = timestamp;

    setPartnerOffset(partnerOffset - elapsed * 0.052);
    schedulePartnerMarquee();
  }

  partnerLogos.forEach((logo) => {
    logo.addEventListener("focus", () => {
      partnerIsFocused = true;
      stopPartnerMarquee();
    });

    logo.addEventListener("blur", () => {
      window.setTimeout(() => {
        partnerIsFocused = partnerMarquee.matches(":focus-within");
        schedulePartnerMarquee();
      }, 0);
    });
  });

  if ("ResizeObserver" in window) {
    new ResizeObserver(() => measurePartnerMarquee()).observe(partnerTrack);
  } else {
    window.addEventListener("resize", () => measurePartnerMarquee(), {
      passive: true,
    });
  }

  if ("IntersectionObserver" in window) {
    new IntersectionObserver(
      ([entry]) => {
        partnerSectionVisible = entry.isIntersecting;
        if (partnerSectionVisible) schedulePartnerMarquee();
        else stopPartnerMarquee();
      },
      { rootMargin: "100px 0px", threshold: 0.01 },
    ).observe(partnerMarquee);
  }

  document.addEventListener("visibilitychange", () => {
    if (document.hidden) stopPartnerMarquee();
    else schedulePartnerMarquee();
  });

  reducedPartnerMotion.addEventListener("change", () => {
    if (reducedPartnerMotion.matches) stopPartnerMarquee();
    else schedulePartnerMarquee();
  });

  measurePartnerMarquee(true);
  schedulePartnerMarquee();
}

const serviceCarousel = document.querySelector("[data-service-carousel]");

if (serviceCarousel) {
  const serviceCards = [...serviceCarousel.querySelectorAll("[data-service-card]")];
  const previousService = serviceCarousel.querySelector("[data-service-prev]");
  const nextService = serviceCarousel.querySelector("[data-service-next]");
  const serviceStatus = serviceCarousel.querySelector("[data-service-status]");
  const serviceViewport = serviceCarousel.querySelector(".service-viewport");
  let activeServiceIndex = 0;
  let pointerStartX = null;
  let pointerId = null;
  let dragDistance = 0;
  let suppressClick = false;
  let viewportHasPointerCapture = false;
  // A tap on a touchscreen almost always drifts a few pixels; anything under
  // this stays a tap so the links inside the card still work.
  const TAP_SLOP_PX = 12;

  function showServiceCard(index, announce = true) {
    activeServiceIndex = (index + serviceCards.length) % serviceCards.length;

    serviceCards.forEach((card, cardIndex) => {
      const active = cardIndex === activeServiceIndex;
      const deckPosition =
        (cardIndex - activeServiceIndex + serviceCards.length) %
        serviceCards.length;

      card.dataset.deckPosition = String(deckPosition);
      card.classList.toggle("is-active", active);
      card.setAttribute("aria-hidden", String(!active));
      card.inert = !active;
    });

    if (announce) {
      const serviceName = serviceCards[activeServiceIndex]
        .querySelector("h3")
        .textContent.trim();
      serviceStatus.textContent = `Showing ${serviceName}.`;
    }
  }

  previousService.addEventListener("click", () => {
    showServiceCard(activeServiceIndex - 1);
  });

  nextService.addEventListener("click", () => {
    showServiceCard(activeServiceIndex + 1);
  });

  serviceViewport.addEventListener("keydown", (event) => {
    if (!["ArrowLeft", "ArrowRight", "Home", "End"].includes(event.key)) return;
    event.preventDefault();

    if (event.key === "ArrowLeft") showServiceCard(activeServiceIndex - 1);
    if (event.key === "ArrowRight") showServiceCard(activeServiceIndex + 1);
    if (event.key === "Home") showServiceCard(0);
    if (event.key === "End") showServiceCard(serviceCards.length - 1);
  });

  serviceViewport.addEventListener(
    "pointerdown",
    (event) => {
      if (event.button !== undefined && event.button !== 0) return;
      pointerStartX = event.clientX;
      pointerId = event.pointerId;
      dragDistance = 0;
      suppressClick = false;
      viewportHasPointerCapture = false;
      serviceViewport.classList.add("is-dragging");
      // Deliberately NOT capturing the pointer here. Capturing on pointerdown
      // retargets the following pointerup AND click to this element, so a tap
      // on a link inside the card never reaches the link. Capture is taken in
      // pointermove instead, once the gesture is actually a drag.
    },
  );

  serviceViewport.addEventListener(
    "pointermove",
    (event) => {
      if (pointerStartX === null || event.pointerId !== pointerId) return;
      dragDistance = event.clientX - pointerStartX;
      if (Math.abs(dragDistance) > TAP_SLOP_PX) {
        if (!viewportHasPointerCapture) {
          serviceViewport.setPointerCapture?.(event.pointerId);
          viewportHasPointerCapture = true;
        }
        event.preventDefault();
      }
    },
    { passive: false },
  );

  function finishServicePointer(event) {
    if (pointerStartX === null || event.pointerId !== pointerId) return;
    const distance = dragDistance || event.clientX - pointerStartX;
    const shouldAdvance =
      event.type === "pointerup" && Math.abs(distance) >= 45;

    if (viewportHasPointerCapture) {
      serviceViewport.releasePointerCapture?.(event.pointerId);
      viewportHasPointerCapture = false;
    }
    serviceViewport.classList.remove("is-dragging");
    pointerStartX = null;
    pointerId = null;
    dragDistance = 0;
    suppressClick =
      event.type === "pointerup" && Math.abs(distance) >= TAP_SLOP_PX;

    if (shouldAdvance) {
      showServiceCard(activeServiceIndex + (distance < 0 ? 1 : -1));
    }
  }

  serviceViewport.addEventListener("pointerup", finishServicePointer);
  serviceViewport.addEventListener("pointercancel", finishServicePointer);
  // Cards are mostly links and images, so a swipe usually starts on one of
  // them. Without this the browser begins a native link/image drag, which
  // swallows both the swipe and the click that should follow a tap.
  serviceViewport.addEventListener("dragstart", (event) => event.preventDefault());
  serviceViewport.addEventListener(
    "click",
    (event) => {
      if (!suppressClick) return;
      event.preventDefault();
      event.stopPropagation();
      suppressClick = false;
    },
    true,
  );

  showServiceCard(0, false);
}

// The original "industry showcase" carousel was superseded by the industry
// queue below. Its markup exists in neither the plugin template nor the
// standalone concept, so the implementation was removed on 2026-08-17.

const industryQueue = document.querySelector("[data-industry-queue]");

if (industryQueue) {
  const industryQueueRail = industryQueue.querySelector(
    "[data-industry-queue-rail]",
  );
  const industryQueueCards = [
    ...industryQueue.querySelectorAll("[data-industry-queue-card]"),
  ];
  const industryQueueButtons = industryQueueCards.map((card) =>
    card.querySelector("[data-industry-queue-go]"),
  );
  const previousQueueIndustry = industryQueue.querySelector(
    "[data-industry-queue-prev]",
  );
  const nextQueueIndustry = industryQueue.querySelector(
    "[data-industry-queue-next]",
  );
  const industryQueueStatus = industryQueue.querySelector(
    "[data-industry-queue-status]",
  );
  const compactIndustryQueue = window.matchMedia("(max-width: 47.99rem)");
  let industryQueueOrder = [...industryQueueCards];
  let industryQueueMoving = false;
  let pendingIndustryQueueCard = null;
  let industryQueuePointerStartX = null;
  let industryQueuePointerId = null;
  let suppressIndustryQueueClick = false;

  function syncIndustryQueue() {
    industryQueueOrder.forEach((card, position) => {
      const active = position === 0;

      card.dataset.industryQueuePosition = String(position);
      card.classList.toggle("is-active", active);
      industryQueueButtons[industryQueueCards.indexOf(card)].setAttribute(
        "aria-pressed",
        String(active),
      );
    });
  }

  function moveIndustryQueueTo(card, announce = true, moveFocus = false) {
    if (!card || !industryQueueOrder.includes(card)) return;

    if (industryQueueMoving) {
      pendingIndustryQueueCard = card;
      return;
    }

    const selectedPosition = industryQueueOrder.indexOf(card);

    if (selectedPosition === 0) {
      if (moveFocus) {
        industryQueueButtons[industryQueueCards.indexOf(card)].focus({
          preventScroll: true,
        });
      }
      return;
    }

    industryQueueMoving = true;
    const transitionDuration = reducedMotion.matches ? 0 : 1100;
    const previousOrder = [...industryQueueOrder];
    const outgoingCards = previousOrder.slice(0, selectedPosition);
    const remainingCards = previousOrder.slice(selectedPosition);
    const nextOrder = [...remainingCards, ...outgoingCards];

    function finishIndustryQueueMove() {
      industryQueueMoving = false;
      const activeCard = industryQueueOrder[0];
      const activeTitle = activeCard.querySelector("h3").textContent.trim();

      if (announce && industryQueueStatus) {
        industryQueueStatus.textContent = `Showing ${activeTitle}.`;
      }

      if (moveFocus) {
        industryQueueButtons[industryQueueCards.indexOf(activeCard)].focus({
          preventScroll: true,
        });
      }

      if (pendingIndustryQueueCard) {
        const pendingCard = pendingIndustryQueueCard;
        pendingIndustryQueueCard = null;
        moveIndustryQueueTo(pendingCard, true, moveFocus);
      }
    }

    if (compactIndustryQueue.matches) {
      const mobileFadeDuration = reducedMotion.matches ? 0 : 140;

      industryQueueRail.classList.add("is-mobile-changing");

      window.setTimeout(() => {
        industryQueueOrder = nextOrder;
        industryQueueOrder.forEach((queueCard) =>
          industryQueueRail.append(queueCard),
        );
        syncIndustryQueue();

        window.requestAnimationFrame(() => {
          industryQueueRail.classList.remove("is-mobile-changing");
        });

        window.setTimeout(
          finishIndustryQueueMove,
          reducedMotion.matches ? 0 : 260,
        );
      }, mobileFadeDuration);
      return;
    }

    industryQueueCards.forEach((queueCard) => {
      queueCard.style.transition = "none";
    });

    industryQueueOrder = nextOrder;
    industryQueueOrder.forEach((queueCard) => industryQueueRail.append(queueCard));
    syncIndustryQueue();
    void industryQueueRail.offsetWidth;

    const tailWidths = outgoingCards.map(
      (queueCard) => queueCard.getBoundingClientRect().width,
    );

    industryQueueOrder = previousOrder;
    industryQueueOrder.forEach((queueCard) => industryQueueRail.append(queueCard));
    syncIndustryQueue();
    void industryQueueRail.offsetWidth;

    industryQueueCards.forEach((queueCard) => {
      queueCard.style.transition = "";
    });

    const tailPlaceholders = outgoingCards.map((queueCard) => {
      const ghost = document.createElement("span");
      ghost.className = "industry-queue-placeholder";
      ghost.setAttribute("aria-hidden", "true");
      const ghostImage = queueCard.querySelector(".industry-queue-image img");
      const ghostSource = ghostImage && (ghostImage.currentSrc || ghostImage.src);
      if (ghostSource) {
        ghost.style.backgroundImage = `url("${ghostSource}")`;
      }
      industryQueueRail.append(ghost);
      return ghost;
    });

    industryQueueRail.style.setProperty(
      "--industry-queue-step-duration",
      `${transitionDuration}ms`,
    );
    void industryQueueRail.offsetWidth;

    window.requestAnimationFrame(() => {
      outgoingCards.forEach((queueCard) => {
        queueCard.classList.add("is-exiting");
      });
      industryQueueOrder = nextOrder;
      syncIndustryQueue();
      tailPlaceholders.forEach((ghost, ghostIndex) => {
        ghost.style.flexBasis = `${tailWidths[ghostIndex]}px`;
      });

      // Schedule the end-of-move swap from the same frame that STARTS the
      // transitions (not from the synchronous call site), plus a small
      // buffer. On a busy main thread the old timer could fire while the
      // cards were still visibly moving, snapping them to their final
      // geometry - one source of the reported end-of-animation shake.
      window.setTimeout(finalizeIndustryQueueMove,
        transitionDuration === 0 ? 0 : transitionDuration + 100);
    });

    function finalizeIndustryQueueMove() {
      industryQueueCards.forEach((queueCard) => {
        queueCard.style.transition = "none";
      });
      tailPlaceholders.forEach((ghost) => {
        ghost.style.transition = "none";
      });

      industryQueueOrder.forEach((queueCard) => industryQueueRail.append(queueCard));
      outgoingCards.forEach((queueCard) => {
        queueCard.classList.remove("is-exiting");
      });
      tailPlaceholders.forEach((ghost) => ghost.remove());
      syncIndustryQueue();
      void industryQueueRail.offsetWidth;

      window.requestAnimationFrame(() => {
        industryQueueCards.forEach((queueCard) => {
          queueCard.style.transition = "";
        });
        finishIndustryQueueMove();
      });
    }
  }

  previousQueueIndustry.addEventListener("click", () => {
    moveIndustryQueueTo(
      industryQueueOrder[industryQueueOrder.length - 1],
      true,
      true,
    );
  });

  nextQueueIndustry.addEventListener("click", () => {
    moveIndustryQueueTo(industryQueueOrder[1], true, true);
  });

  industryQueueCards.forEach((card, cardIndex) => {
    industryQueueButtons[cardIndex].addEventListener("click", (event) => {
      if (suppressIndustryQueueClick) {
        event.preventDefault();
        return;
      }
      moveIndustryQueueTo(card);
    });
  });

  let industryQueueHasPointerCapture = false;

  industryQueueRail.addEventListener("pointerdown", (event) => {
    if (!compactIndustryQueue.matches || !event.isPrimary) return;

    industryQueuePointerStartX = event.clientX;
    industryQueuePointerId = event.pointerId;
    industryQueueHasPointerCapture = false;
    // Capture is taken in pointermove, not here - see the note on the services
    // viewport. Capturing on pointerdown would retarget the click and break the
    // industry card links.
  });

  industryQueueRail.addEventListener("pointermove", (event) => {
    if (
      industryQueuePointerStartX === null ||
      event.pointerId !== industryQueuePointerId ||
      industryQueueHasPointerCapture
    ) {
      return;
    }
    if (Math.abs(event.clientX - industryQueuePointerStartX) > 12) {
      industryQueueRail.setPointerCapture(event.pointerId);
      industryQueueHasPointerCapture = true;
    }
  });

  industryQueueRail.addEventListener("pointerup", (event) => {
    if (
      industryQueuePointerStartX === null ||
      event.pointerId !== industryQueuePointerId
    ) {
      return;
    }

    const swipeDistance = event.clientX - industryQueuePointerStartX;
    if (industryQueueHasPointerCapture) {
      industryQueueRail.releasePointerCapture?.(event.pointerId);
      industryQueueHasPointerCapture = false;
    }
    industryQueuePointerStartX = null;
    industryQueuePointerId = null;

    if (Math.abs(swipeDistance) < 44) return;

    suppressIndustryQueueClick = true;
    window.setTimeout(() => {
      suppressIndustryQueueClick = false;
    }, 0);

    if (swipeDistance < 0) {
      moveIndustryQueueTo(industryQueueOrder[1]);
    } else {
      moveIndustryQueueTo(
        industryQueueOrder[industryQueueOrder.length - 1],
      );
    }
  });

  industryQueueRail.addEventListener("dragstart", (event) => event.preventDefault());

  industryQueueRail.addEventListener("pointercancel", () => {
    industryQueuePointerStartX = null;
    industryQueuePointerId = null;
  });

  industryQueueRail.addEventListener("keydown", (event) => {
    if (!["ArrowLeft", "ArrowRight", "Home", "End"].includes(event.key)) {
      return;
    }

    event.preventDefault();

    if (event.key === "ArrowLeft") {
      moveIndustryQueueTo(
        industryQueueOrder[industryQueueOrder.length - 1],
        true,
        true,
      );
    }
    if (event.key === "ArrowRight") {
      moveIndustryQueueTo(industryQueueOrder[1], true, true);
    }
    if (event.key === "Home") {
      moveIndustryQueueTo(industryQueueCards[0], true, true);
    }
    if (event.key === "End") {
      moveIndustryQueueTo(
        industryQueueCards[industryQueueCards.length - 1],
        true,
        true,
      );
    }
  });

  syncIndustryQueue();
}

const heroTypeword = document.querySelector("[data-hero-typewords]");

if (heroTypeword) {
  const words = heroTypeword.dataset.heroTypewords
    .split(",")
    .map((word) => word.trim())
    .filter(Boolean);

  if (words.length > 1 && !reducedMotion.matches) {
    let wordIndex = 0;
    let charIndex = words[0].length;
    let deleting = true;

    function tickHeroType() {
      const currentWord = words[wordIndex];
      heroTypeword.textContent = currentWord.slice(0, charIndex);

      if (deleting) {
        if (charIndex > 0) {
          charIndex -= 1;
          window.setTimeout(tickHeroType, 46);
          return;
        }

        deleting = false;
        wordIndex = (wordIndex + 1) % words.length;
        window.setTimeout(tickHeroType, 220);
        return;
      }

      if (charIndex < words[wordIndex].length) {
        charIndex += 1;
        window.setTimeout(tickHeroType, 68);
        return;
      }

      deleting = true;
      window.setTimeout(tickHeroType, 1350);
    }

    window.setTimeout(tickHeroType, 1050);
  }
}

document.querySelectorAll("[data-scale-section]").forEach((scaleSection) => {
  const scaleCanvas = scaleSection.querySelector("[data-scale-canvas]");
  const scaleContext = scaleCanvas?.getContext("2d", { alpha: true });
  const compactScale = window.matchMedia("(max-width: 47.99rem)");

  if (scaleCanvas && scaleContext) {
    const FRAME_INTERVAL = 1000 / 30;
    const LINK_DISTANCE = 130;
    const LINK_DISTANCE_SQ = LINK_DISTANCE * LINK_DISTANCE;
    /* The brand-blue variant of this section has a much lighter
       background, so the same particle values wash out on it.
       Brighter, slightly more opaque tones there only. */
    const brandScale = scaleSection.classList.contains("scale-section-brand");
    const LINK_RGB = brandScale ? "224, 244, 255" : "96, 182, 232";
    const LINK_ALPHA = brandScale ? 0.3 : 0.14;
    const DOT_RGB = brandScale ? "238, 250, 255" : "158, 213, 244";
    const DOT_ALPHA_SCALE = brandScale ? 1.55 : 1;

    let canvasWidth = 1;
    let canvasHeight = 1;
    let particles = [];
    let frameId = 0;
    let lastStep = 0;
    let sectionVisible = !("IntersectionObserver" in window);

    function seededValue(index, salt = 0) {
      const value =
        Math.sin((index + 1) * (12.9898 + salt * 17.17)) * 43758.5453;
      return value - Math.floor(value);
    }

    function createParticles() {
      const count = compactScale.matches ? 28 : 52;

      particles = Array.from({ length: count }, (_, index) => ({
        x: seededValue(index, 1) * canvasWidth,
        y: seededValue(index, 2) * canvasHeight,
        driftX: (seededValue(index, 3) - 0.5) * 14,
        driftY: (seededValue(index, 4) - 0.5) * 10,
        radius: 1.05 + seededValue(index, 5) * 1.35,
        alpha: 0.28 + seededValue(index, 6) * 0.42,
        pulse: seededValue(index, 7) * Math.PI * 2,
      }));
    }

    function stepParticles(deltaSeconds) {
      const margin = 14;

      particles.forEach((particle) => {
        particle.x += particle.driftX * deltaSeconds;
        particle.y += particle.driftY * deltaSeconds;

        if (particle.x < -margin) particle.x = canvasWidth + margin;
        else if (particle.x > canvasWidth + margin) particle.x = -margin;
        if (particle.y < -margin) particle.y = canvasHeight + margin;
        else if (particle.y > canvasHeight + margin) particle.y = -margin;
      });
    }

    function drawParticles(now = performance.now()) {
      scaleContext.clearRect(0, 0, canvasWidth, canvasHeight);
      scaleContext.lineWidth = 1;

      for (let first = 0; first < particles.length; first += 1) {
        for (
          let second = first + 1;
          second < particles.length;
          second += 1
        ) {
          const deltaX = particles[first].x - particles[second].x;
          const deltaY = particles[first].y - particles[second].y;
          const distanceSquared = deltaX * deltaX + deltaY * deltaY;

          if (distanceSquared < LINK_DISTANCE_SQ) {
            const strength =
              1 - Math.sqrt(distanceSquared) / LINK_DISTANCE;
            scaleContext.strokeStyle =
              `rgba(${LINK_RGB}, ${(strength * LINK_ALPHA).toFixed(3)})`;
            scaleContext.beginPath();
            scaleContext.moveTo(
              particles[first].x,
              particles[first].y,
            );
            scaleContext.lineTo(
              particles[second].x,
              particles[second].y,
            );
            scaleContext.stroke();
          }
        }
      }

      particles.forEach((particle) => {
        const twinkle =
          0.82 + Math.sin(now * 0.0011 + particle.pulse) * 0.18;
        scaleContext.beginPath();
        scaleContext.arc(
          particle.x,
          particle.y,
          particle.radius,
          0,
          Math.PI * 2,
        );
        scaleContext.fillStyle =
          `rgba(${DOT_RGB}, ${Math.min(
            particle.alpha * twinkle * DOT_ALPHA_SCALE,
            1,
          ).toFixed(3)})`;
        scaleContext.fill();
      });
    }

    function shouldAnimateScale() {
      return sectionVisible && !reducedMotion.matches && !document.hidden;
    }

    function scheduleScaleFrame() {
      if (frameId || !shouldAnimateScale()) return;
      lastStep = lastStep || performance.now();
      frameId = window.requestAnimationFrame(runScaleFrame);
    }

    function runScaleFrame(now) {
      frameId = 0;
      if (!shouldAnimateScale()) return;

      const elapsed = now - lastStep;
      if (elapsed >= FRAME_INTERVAL) {
        lastStep = now - (elapsed % FRAME_INTERVAL);
        stepParticles(Math.min(elapsed, 120) / 1000);
        drawParticles(now);
      }

      scheduleScaleFrame();
    }

    function stopScaleFrame() {
      if (!frameId) return;
      window.cancelAnimationFrame(frameId);
      frameId = 0;
    }

    function resizeScaleCanvas() {
      const bounds = scaleSection.getBoundingClientRect();
      const density = Math.min(window.devicePixelRatio || 1, 1.5);
      const nextWidth = Math.max(1, Math.round(bounds.width));
      const nextHeight = Math.max(1, Math.round(bounds.height));
      const sizeChanged =
        Math.abs(nextWidth - canvasWidth) > 24 ||
        Math.abs(nextHeight - canvasHeight) > 24;

      canvasWidth = nextWidth;
      canvasHeight = nextHeight;
      scaleCanvas.width = Math.round(nextWidth * density);
      scaleCanvas.height = Math.round(nextHeight * density);
      scaleContext.setTransform(density, 0, 0, density, 0, 0);

      if (sizeChanged || !particles.length) createParticles();
      drawParticles();
    }

    if ("ResizeObserver" in window) {
      new ResizeObserver(resizeScaleCanvas).observe(scaleSection);
    } else {
      window.addEventListener("resize", resizeScaleCanvas, {
        passive: true,
      });
    }

    if ("IntersectionObserver" in window) {
      new IntersectionObserver(
        ([entry]) => {
          sectionVisible = entry.isIntersecting;
          if (sectionVisible) scheduleScaleFrame();
          else stopScaleFrame();
        },
        { rootMargin: "80px 0px", threshold: 0.01 },
      ).observe(scaleSection);
    }

    document.addEventListener("visibilitychange", () => {
      if (document.hidden) stopScaleFrame();
      else scheduleScaleFrame();
    });

    reducedMotion.addEventListener("change", () => {
      if (reducedMotion.matches) {
        stopScaleFrame();
        drawParticles();
      } else {
        scheduleScaleFrame();
      }
    });

    compactScale.addEventListener("change", () => {
      createParticles();
      drawParticles();
    });

    resizeScaleCanvas();
    scheduleScaleFrame();
  }
});

{
  const statNumbers = [
    ...document.querySelectorAll("[data-stat-number]"),
  ];

  if (
    statNumbers.length &&
    "IntersectionObserver" in window &&
    !reducedMotion.matches
  ) {
    const renderStat = (element, value) => {
      element.textContent =
        `${element.dataset.prefix || ""}${value}` +
        `${element.dataset.suffix || ""}`;
    };

    const animateStat = (element) => {
      const target = Number(element.dataset.target || 0);
      const started = performance.now();
      const duration = 950;

      const tick = (now) => {
        const progress = Math.min((now - started) / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        renderStat(element, Math.round(target * eased));
        if (progress < 1) window.requestAnimationFrame(tick);
      };

      window.requestAnimationFrame(tick);
    };

    const statObserver = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting) return;
          statObserver.unobserve(entry.target);
          animateStat(entry.target);
        });
      },
      { threshold: 0.4 },
    );

    statNumbers.forEach((element) => statObserver.observe(element));
  }
}

const revealItems = [...document.querySelectorAll(".reveal")];

if (reducedMotion.matches || !("IntersectionObserver" in window)) {
  revealItems.forEach((item) => item.classList.add("is-visible"));
} else {
  const pendingReveals = new Set(revealItems);

  function showReveal(item, instant) {
    if (!pendingReveals.has(item)) return;
    pendingReveals.delete(item);
    // If the element is already well inside the viewport by the time we
    // reach it, the fade would only ever be perceived as a blank gap, so
    // it is skipped and the content is simply there.
    if (instant) item.classList.add("is-instant");
    item.classList.add("is-visible");
    revealObserver.unobserve(item);
  }

  const revealObserver = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) showReveal(entry.target, false);
      });
    },
    { rootMargin: "0px 0px -5% 0px", threshold: 0 },
  );

  /*
   * Failsafe sweep. IntersectionObserver alone left two visible holes:
   *  1. A fast scroll (wheel fling, scrollbar drag, jump to an anchor)
   *     outruns its callbacks - measured 605ms before the class landed,
   *     so a whole section sat blank for over a second.
   *  2. Anything the page loads ALREADY scrolled past never intersects
   *     at all, so on a refresh with a restored scroll position those
   *     sections stayed invisible permanently.
   * Running the same geometry test on every scroll frame closes both:
   * the reveal now happens on the frame the element comes into view.
   */
  let revealFrame = 0;
  let lastRevealScrollY = window.scrollY;

  function sweepReveals() {
    revealFrame = 0;
    if (!pendingReveals.size) return;

    const viewport = window.innerHeight;
    // How far the page travelled since the last frame. Past roughly a
    // third of a screen the scroll is a fling: the fade would land
    // mid-screen and read as a blank block, so those reveal instantly.
    const travelled = Math.abs(window.scrollY - lastRevealScrollY);
    const flinging = travelled > viewport * 0.3;
    lastRevealScrollY = window.scrollY;

    // While flinging, reveal a screen and a half ahead: those elements
    // are shown instantly anyway, so there is nothing to see early - it
    // just removes the one-frame gap when a card lands mid-frame.
    const limit = viewport * (flinging ? 1.6 : 0.95);

    [...pendingReveals].forEach((item) => {
      const rect = item.getBoundingClientRect();
      if (rect.top >= limit) return;
      showReveal(item, flinging || rect.top < viewport * 0.6);
    });

    if (!pendingReveals.size) {
      window.removeEventListener("scroll", scheduleRevealSweep);
      window.removeEventListener("resize", scheduleRevealSweep);
    }
  }

  function scheduleRevealSweep() {
    if (revealFrame) return;
    revealFrame = window.requestAnimationFrame(sweepReveals);
  }

  revealItems.forEach((item) => revealObserver.observe(item));
  window.addEventListener("scroll", scheduleRevealSweep, { passive: true });
  window.addEventListener("resize", scheduleRevealSweep);
  scheduleRevealSweep();
}

mobileNavigation.addEventListener("change", (event) => {
  if (!event.matches) closeMenu();
  syncNavigationInteractivity();
});

const blogCarousel = document.querySelector("[data-blog-carousel]");

if (blogCarousel) {
  const blogViewport = blogCarousel.querySelector("[data-blog-viewport]");
  const blogTrack = blogCarousel.querySelector("[data-blog-track]");
  const blogPrev = blogCarousel.querySelector("[data-blog-prev]");
  const blogNext = blogCarousel.querySelector("[data-blog-next]");
  const blogStatus = blogCarousel.querySelector("[data-blog-status]");
  const blogCards = blogTrack ? [...blogTrack.children] : [];

  if (blogViewport && blogTrack && blogCards.length > 1) {
    const BLOG_EASE = "cubic-bezier(0.22, 1, 0.36, 1)";
    const BLOG_TAP_SLOP = 8;

    // `blogOffset` is the track's translateX in px. It is kept inside
    // (-step, 0] by blogNormalize(), which recycles cards between the
    // ends of the track - that is what makes an unbounded drag wrap
    // seamlessly instead of running out of cards.
    let blogOffset = 0;
    let blogPointerId = null;
    let blogDragStartX = null;
    let blogDragBaseOffset = 0;
    let blogDragging = false;
    let blogSuppressClick = false;
    let blogSettleTimer = 0;
    let blogPendingSteps = 0;
    let blogSamples = [];

    blogCards.forEach((card, index) => {
      card.dataset.blogIndex = String(index);
    });

    function blogStep() {
      const first = blogTrack.children[0].getBoundingClientRect();
      const second = blogTrack.children[1]
        ? blogTrack.children[1].getBoundingClientRect()
        : null;
      return second ? second.left - first.left : first.width;
    }

    function blogRender() {
      blogTrack.style.transform = `translateX(${blogOffset}px)`;
    }

    function blogNormalize() {
      const step = blogStep();
      if (!(step > 0)) return;
      let guard = 0;
      while (blogOffset <= -step && guard++ < 64) {
        blogTrack.append(blogTrack.children[0]);
        blogOffset += step;
      }
      while (blogOffset > 0 && guard++ < 64) {
        blogTrack.prepend(blogTrack.children[blogTrack.children.length - 1]);
        blogOffset -= step;
      }
    }

    function blogAnnounce() {
      if (!blogStatus) return;
      const heading = blogTrack.children[0].querySelector("h3");
      if (heading) {
        blogStatus.textContent = `Showing articles starting with "${heading.textContent.trim()}".`;
      }
    }

    function blogSettle() {
      blogSettleTimer = 0;
      blogTrack.style.transition = "none";
      blogNormalize();
      blogRender();
      void blogTrack.offsetWidth;
      blogTrack.style.transition = "";
      blogAnnounce();

      // A click that arrived mid-glide waits here rather than starting
      // from a half-way position, which would leave the track resting
      // between two cards.
      if (blogPendingSteps) {
        const queued = blogPendingSteps;
        blogPendingSteps = 0;
        blogMove(queued);
      }
    }

    function blogCurrentTranslate() {
      const value = getComputedStyle(blogTrack).transform;
      if (!value || value === "none") return 0;
      const numbers = value.match(/matrix.*\((.+)\)/);
      if (!numbers) return 0;
      const parts = numbers[1].split(",").map(Number);
      return parts.length > 6 ? parts[12] : parts[4];
    }

    // Freeze the track wherever it is on screen. Used when a new
    // interaction interrupts a running animation, so grabbing mid-glide
    // picks the cards up exactly where the eye last saw them.
    function blogStopAnimation() {
      if (blogSettleTimer) {
        window.clearTimeout(blogSettleTimer);
        blogSettleTimer = 0;
      }
      blogOffset = blogCurrentTranslate();
      blogTrack.style.transition = "none";
      blogRender();
    }

    function blogAnimateTo(target) {
      const distance = Math.abs(target - blogOffset);
      const duration = reducedMotion.matches
        ? 0
        : Math.min(560, Math.max(300, distance * 0.55));
      if (blogSettleTimer) {
        window.clearTimeout(blogSettleTimer);
        blogSettleTimer = 0;
      }
      blogOffset = target;
      if (duration === 0) {
        blogSettle();
        return;
      }
      blogTrack.style.transition = `transform ${duration}ms ${BLOG_EASE}`;
      blogRender();
      blogSettleTimer = window.setTimeout(blogSettle, duration + 60);
    }

    // How many cards fit in the viewport at the current breakpoint.
    function blogPerView() {
      const step = blogStep();
      if (!(step > 0)) return 1;
      return Math.max(
        1,
        Math.round(blogViewport.getBoundingClientRect().width / step),
      );
    }

    // With every post on screen there is nothing to page through, so the
    // arrows and dots would be lying about the content. Hide them.
    function blogSyncControls() {
      const pageable = blogCards.length > blogPerView();
      blogCarousel.classList.toggle("is-static", !pageable);
      [blogPrev, blogNext].forEach((button) => {
        if (button) button.disabled = !pageable;
      });
      return pageable;
    }

    function blogMove(steps) {
      if (!steps || !blogSyncControls()) return;

      const step = blogStep();
      const spare = blogCards.length - blogPerView();
      if (spare < 1 || !(step > 0)) return;

      // Every move starts from a card boundary. A request that arrives
      // while the track is still gliding is queued for the settle instead
      // of being applied from a fractional offset - starting mid-glide
      // both leaves the track resting between cards and needs more travel
      // room than the spare off-screen cards can cover.
      if (blogSettleTimer) {
        const queued = blogPendingSteps + steps;
        const cap = Math.min(spare, Math.floor(blogCards.length / 2));
        blogPendingSteps = Math.max(-cap, Math.min(cap, queued));
        return;
      }

      blogNormalize();
      blogRender();

      if (Math.abs(steps) > spare) {
        // Further than the off-screen cards can cover: animating would run
        // the track off its own end, so reposition without a transition.
        const forward = steps > 0 ? steps : blogCards.length + steps;
        for (let i = 0; i < forward; i += 1) {
          blogTrack.append(blogTrack.children[0]);
        }
        blogOffset = 0;
        blogSettle();
        return;
      }

      if (steps < 0) {
        /*
         * Going back, the incoming cards sit at the END of the track, so
         * they must be moved to the front BEFORE the animation, with the
         * offset compensated so nothing jumps. Without this the track
         * simply slid right off empty space: every backwards step showed
         * a card-sized blank block for the length of the animation, which
         * is what made the dots look broken.
         */
        const count = -steps;
        for (let i = 0; i < count; i += 1) {
          blogTrack.prepend(blogTrack.children[blogTrack.children.length - 1]);
          blogOffset -= step;
        }
        blogTrack.style.transition = "none";
        blogRender();
        void blogTrack.offsetWidth;
        blogAnimateTo(blogOffset + count * step);
        return;
      }

      blogAnimateTo(blogOffset - steps * step);
    }

    if (blogNext) blogNext.addEventListener("click", () => blogMove(1));
    if (blogPrev) blogPrev.addEventListener("click", () => blogMove(-1));

    blogSyncControls();

    // Continuous drag. Pointer capture is taken only once movement passes
    // the tap threshold - capturing on pointerdown would retarget the
    // click and break the card links (see the services viewport note).
    blogViewport.addEventListener("pointerdown", (event) => {
      if (!event.isPrimary) return;
      blogStopAnimation();
      blogPendingSteps = 0;
      blogNormalize();
      blogRender();
      blogPointerId = event.pointerId;
      blogDragStartX = event.clientX;
      blogDragBaseOffset = blogOffset;
      blogDragging = false;
      blogSamples = [{ x: event.clientX, t: event.timeStamp }];
    });

    blogViewport.addEventListener("pointermove", (event) => {
      if (blogDragStartX === null || event.pointerId !== blogPointerId) return;
      const dx = event.clientX - blogDragStartX;

      if (!blogDragging) {
        if (Math.abs(dx) <= BLOG_TAP_SLOP) return;
        blogViewport.setPointerCapture(event.pointerId);
        blogDragging = true;
        blogViewport.classList.add("is-dragging");
        blogTrack.style.transition = "none";
      }

      blogSamples.push({ x: event.clientX, t: event.timeStamp });
      if (blogSamples.length > 6) blogSamples.shift();

      blogOffset = blogDragBaseOffset + dx;
      blogNormalize();
      // Recycling cards shifts the offset by whole steps; rebase so the
      // track keeps tracking the pointer 1:1 across any number of cards.
      blogDragBaseOffset = blogOffset - dx;
      blogRender();
    });

    function blogEndDrag(event, cancelled) {
      if (blogDragStartX === null || event.pointerId !== blogPointerId) return;
      const wasDragging = blogDragging;

      if (wasDragging) {
        blogViewport.releasePointerCapture?.(event.pointerId);
        blogViewport.classList.remove("is-dragging");
      }

      blogDragStartX = null;
      blogPointerId = null;
      blogDragging = false;
      if (!wasDragging) return;

      blogSuppressClick = true;
      window.setTimeout(() => {
        blogSuppressClick = false;
      }, 0);

      const step = blogStep();
      let velocity = 0;

      if (!cancelled && blogSamples.length > 1) {
        const first = blogSamples[0];
        const last = blogSamples[blogSamples.length - 1];
        const elapsed = last.t - first.t;
        if (elapsed > 0) velocity = (last.x - first.x) / elapsed;
      }

      // Throw the track a little past the finger, then snap to whichever
      // card boundary that lands nearest - bounded by the cards actually
      // sitting off-screen, so the glide can never run onto empty track.
      const spare = Math.max(1, blogCards.length - blogPerView());
      const projected = blogOffset + velocity * 140;
      const glide = step * Math.min(2, spare);
      const target = Math.max(
        Math.max(blogOffset - glide, -spare * step),
        Math.min(blogOffset + glide, Math.min(0, Math.round(projected / step) * step)),
      );
      blogAnimateTo(target);
    }

    blogViewport.addEventListener("pointerup", (event) => blogEndDrag(event, false));
    blogViewport.addEventListener("pointercancel", (event) => blogEndDrag(event, true));
    blogViewport.addEventListener("dragstart", (event) => event.preventDefault());

    blogCarousel.addEventListener(
      "click",
      (event) => {
        if (blogSuppressClick) {
          event.preventDefault();
          event.stopPropagation();
          blogSuppressClick = false;
        }
      },
      true,
    );

    // A width change resizes every card, so the pixel offset no longer
    // maps to a card boundary - realign on the leading card.
    let blogResizeTimer = 0;
    window.addEventListener("resize", () => {
      window.clearTimeout(blogResizeTimer);
      blogResizeTimer = window.setTimeout(() => {
        blogStopAnimation();
        blogOffset = 0;
        blogTrack.style.transition = "none";
        blogRender();
        void blogTrack.offsetWidth;
        blogTrack.style.transition = "";
        blogSyncControls();
      }, 150);
    });
  }
}
