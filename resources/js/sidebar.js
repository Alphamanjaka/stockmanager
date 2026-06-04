document.addEventListener("DOMContentLoaded", () => {
    const sidebarToggle = document.getElementById("toggleSidebar");
    const sidebar = document.querySelector(".sidebar");

    if (!sidebar || !sidebarToggle) {
        return;
    }

    const isDesktop = () => window.innerWidth > 768;

    const updateToggleIcon = (collapsed) => {
        const icon = sidebarToggle.querySelector("i");
        if (!icon) {
            return;
        }
        icon.classList.toggle("fa-angle-right", collapsed);
        icon.classList.toggle("fa-angle-left", !collapsed);
    };

    const setSidebarState = (collapsed) => {
        sidebar.classList.toggle("collapsed", collapsed);
        localStorage.setItem(
            "sidebar-state",
            collapsed ? "collapsed" : "expanded",
        );
        updateToggleIcon(collapsed);
    };

    const closeCollapsedSubmenus = (except = null) => {
        sidebar
            .querySelectorAll(".menu-group .collapse.show")
            .forEach((openSubmenu) => {
                if (openSubmenu === except) {
                    return;
                }
                const instance = bootstrap.Collapse.getOrCreateInstance(
                    openSubmenu,
                    {
                        toggle: false,
                    },
                );
                instance.hide();
            });
    };

    if (localStorage.getItem("sidebar-state") === "collapsed") {
        setSidebarState(true);
    }

    sidebarToggle.addEventListener("click", () => {
        if (isDesktop()) {
            setSidebarState(!sidebar.classList.contains("collapsed"));
            return;
        }

        sidebar.classList.toggle("show-mobile");
    });

    document.addEventListener("click", (event) => {
        if (
            !isDesktop() &&
            sidebar.classList.contains("show-mobile") &&
            !sidebar.contains(event.target) &&
            !sidebarToggle.contains(event.target)
        ) {
            sidebar.classList.remove("show-mobile");
        }
    });

    const positionSubmenu = (submenu, group) => {
        const rect = group.getBoundingClientRect();
        submenu.style.left = `${rect.right}px`;

        const submenuHeight = submenu.offsetHeight || 200;
        if (rect.top + submenuHeight > window.innerHeight) {
            submenu.style.top = "auto";
            submenu.style.bottom = "0";
        } else {
            submenu.style.top = `${rect.top}px`;
            submenu.style.bottom = "auto";
        }
    };

    sidebar.querySelectorAll(".menu-group").forEach((group) => {
        const toggleLink = group.querySelector(
            ".menu-group > a[data-bs-toggle='collapse']",
        );
        const submenu = group.querySelector(".collapse");

        if (!toggleLink || !submenu) {
            return;
        }

        toggleLink.addEventListener("click", (event) => {
            if (!sidebar.classList.contains("collapsed")) {
                return;
            }

            event.preventDefault();
            closeCollapsedSubmenus(submenu);
            positionSubmenu(submenu, group);
            const instance = bootstrap.Collapse.getOrCreateInstance(submenu, {
                toggle: false,
            });
            instance.toggle();
        });

        submenu.addEventListener("shown.bs.collapse", () => {
            if (sidebar.classList.contains("collapsed")) {
                sidebar.classList.add("submenu-open");
            }
        });

        submenu.addEventListener("hidden.bs.collapse", () => {
            if (!sidebar.querySelector(".menu-group .collapse.show")) {
                sidebar.classList.remove("submenu-open");
            }
        });

        group.addEventListener("mouseenter", () => {
            if (!sidebar.classList.contains("collapsed")) {
                return;
            }

            positionSubmenu(submenu, group);
        });
    });
});
