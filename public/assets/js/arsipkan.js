(() => {
  const sidebarStorageKey = "arsipkan.sidebar";
  let lucideRenderQueued = false;

  const shouldRenderLucide = () => {
    const path = window.location.pathname || "";

    return !/\/admin\/master-data(?:\/|$)/.test(path);
  };

  const renderLucideIcons = () => {
    if (!window.lucide || lucideRenderQueued || !shouldRenderLucide()) {
      return;
    }

    lucideRenderQueued = true;

    window.setTimeout(() => {
      window.requestAnimationFrame(() => {
        try {
          window.lucide.createIcons();
        } finally {
          lucideRenderQueued = false;
        }
      });
    }, 0);
  };

  const setSidebarCollapsed = (collapsed) => {
    const sidebarCollapseToggle = document.getElementById("sidebarCollapseToggle");
    const sidebarCollapseIcon = document.querySelector("[data-sidebar-collapse-icon]");

    document.body.classList.toggle("sidebar-collapsed", collapsed);

    if (sidebarCollapseToggle) {
      sidebarCollapseToggle.setAttribute("aria-expanded", collapsed ? "false" : "true");
      sidebarCollapseToggle.setAttribute("aria-label", collapsed ? "Lebarkan sidebar" : "Sempitkan sidebar");
      sidebarCollapseToggle.setAttribute("title", collapsed ? "Lebarkan sidebar" : "Sempitkan sidebar");
    }

    if (sidebarCollapseIcon) {
      sidebarCollapseIcon.className = collapsed
        ? "bi bi-layout-sidebar"
        : "bi bi-layout-sidebar-inset";
    }

    renderLucideIcons();
  };

  const storedSidebarState = () => {
    try {
      return localStorage.getItem(sidebarStorageKey);
    } catch {
      return null;
    }
  };

  const storeSidebarState = (collapsed) => {
    try {
      localStorage.setItem(sidebarStorageKey, collapsed ? "collapsed" : "expanded");
    } catch {
      // Local storage can be unavailable in strict browser privacy modes.
    }
  };

  const initSidebar = () => {
    setSidebarCollapsed(storedSidebarState() === "collapsed");
    closeMobileSidebar();
    renderLucideIcons();
  };

  const setMobileSidebar = (open) => {
    const sidebar = document.getElementById("sidebar");
    const overlay = document.getElementById("sidebarOverlay");
    const mobileToggle = document.getElementById("mobileToggle");

    if (!sidebar || !overlay) {
      return;
    }

    sidebar.classList.toggle("show", open);
    overlay.classList.toggle("show", open);
    document.body.style.overflow = open ? "hidden" : "";

    if (mobileToggle) {
      mobileToggle.setAttribute("aria-expanded", open ? "true" : "false");
      mobileToggle.setAttribute("aria-label", open ? "Tutup sidebar" : "Buka sidebar");
    }
  };

  const toggleMobileSidebar = () => {
    const sidebar = document.getElementById("sidebar");

    setMobileSidebar(!sidebar?.classList.contains("show"));
  };

  const closeMobileSidebar = () => {
    setMobileSidebar(false);
  };

  document.addEventListener("click", (event) => {
    if (event.target.closest("#mobileToggle")) {
      toggleMobileSidebar();

      return;
    }

    if (event.target.closest("#closeSidebar, #sidebarOverlay")) {
      closeMobileSidebar();

      return;
    }

    if (event.target.closest("#sidebarCollapseToggle")) {
      const collapsed = !document.body.classList.contains("sidebar-collapsed");

      setSidebarCollapsed(collapsed);
      storeSidebarState(collapsed);
    }
  });

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", () => {
      initSidebar();
    });
  } else {
    initSidebar();
  }

  document.addEventListener("livewire:navigated", initSidebar);
  document.addEventListener("livewire:init", renderLucideIcons);
  document.addEventListener("livewire:update", renderLucideIcons);
})();
