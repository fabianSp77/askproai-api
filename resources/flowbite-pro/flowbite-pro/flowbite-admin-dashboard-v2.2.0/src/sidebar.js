window.addEventListener("load", function () {
  let event = new Event("rerender-charts");
  const $toggleSidebarButton = document.getElementById("togglSidebarButton");
  const $sidebar = document.getElementById("sidebar");
  const $mainContent = document.getElementById("main-content");
  const $collapseIcon = document.querySelector("[data-sidebar-toggle-collapse-icon]");
  const $expandIcon = document.querySelector("[data-sidebar-toggle-expand-icon]");

  const collapseSidebar = (save = true, initial = false) => {
    document.querySelectorAll("[data-sidebar-collapse-hide]").forEach((el) => {
      el.classList.add("hidden");
      el.classList.add("opacity-0");
      if (initial) {
        el.classList.remove("opacity-0");
      } else {
        setTimeout(() => {
          el.classList.remove("opacity-0");
        }, 75);
      }
    });
    document.querySelectorAll("[data-sidebar-collapse-subitem]").forEach((el) => {
      el.classList.remove("pl-10");
    });
    $sidebar.classList.remove("w-64");
    $sidebar.classList.add("w-16");
    $mainContent.classList.remove("lg:ms-64");
    $mainContent.classList.add("lg:ml-16");
    $toggleSidebarButton.setAttribute("aria-expanded", "false");
    if (save) {
      $collapseIcon.classList.add("hidden");
      $expandIcon.classList.remove("hidden");
      localStorage.setItem("sidebarExpanded", "false"); // Save state
    }

    this.document.dispatchEvent(event);
  };

  const expandSidebar = (save = true, initial = false) => {
    document.querySelectorAll("[data-sidebar-collapse-hide]").forEach((el) => {
      el.classList.remove("hidden");
      el.classList.add("opacity-0");
      if (initial) {
        el.classList.remove("opacity-0");
      } else {
        setTimeout(() => {
          el.classList.remove("opacity-0");
        }, 75);
      }
    });
    document.querySelectorAll("[data-sidebar-collapse-subitem]").forEach((el) => {
      el.classList.add("pl-10");
    });
    $sidebar.classList.remove("w-16");
    $sidebar.classList.add("w-64");
    $mainContent.classList.remove("lg:ml-16");
    $mainContent.classList.add("lg:ms-64");

    $toggleSidebarButton.setAttribute("aria-expanded", "true");
    if (save) {
      $collapseIcon.classList.remove("hidden");
      $expandIcon.classList.add("hidden");
      localStorage.setItem("sidebarExpanded", "true"); // Save state
    }

    this.document.dispatchEvent(event);
  };

  const initializeSidebarState = () => {
    const sidebarState = localStorage.getItem("sidebarExpanded");
    const hoverState = sessionStorage.getItem("sidebarHoverState");

    if (hoverState === "true" && sidebarState === "false") {
      expandSidebar(false, true); // Temporarily expand on hover
    } else if (sidebarState === "true") {
      expandSidebar(false, true); // Restore permanent expanded state
    } else {
      collapseSidebar(false, true); // Restore permanent collapsed state
    }
  };

  if ($toggleSidebarButton && $sidebar) {
    // Initialize the sidebar state based on localStorage and sessionStorage
    initializeSidebarState();

    // Add click event to toggle button
    $toggleSidebarButton.addEventListener("click", () => {
      sessionStorage.setItem("sidebarHoverState", "false"); // Reset hover state on click
      if ($toggleSidebarButton.getAttribute("aria-expanded") === "true") {
        collapseSidebar(); // Save state permanently
      } else {
        expandSidebar(); // Save state permanently
      }
    });

    // Add hover functionality for temporary expansion
    let isTemporarilyExpanded = false;

    $sidebar.addEventListener("mouseenter", () => {
      if ($toggleSidebarButton.getAttribute("aria-expanded") === "false") {
        isTemporarilyExpanded = true;
        sessionStorage.setItem("sidebarHoverState", "true"); // Remember hover state
        expandSidebar(false); // Expand temporarily
      }
    });

    $sidebar.addEventListener("mouseleave", () => {
      if (isTemporarilyExpanded) {
        isTemporarilyExpanded = false;
        sessionStorage.setItem("sidebarHoverState", "false"); // Reset hover state
        collapseSidebar(false); // Collapse temporarily
      }
    });
  }
});
