(() => {
  "use strict";
  const key = `royalbeans:admin-scroll:${location.pathname}${location.search}`;
  const navigation = performance.getEntriesByType("navigation")[0];
  let frame = 0;
  let hiddenAt = 0;
  let checkingSession = false;
  const prefetched = new Set();
  let loadingDepth = 0;
  const createLoading = () => {
    let overlay = document.querySelector("[data-admin-loading]");
    if (overlay) return overlay;
    overlay = document.createElement("div");
    overlay.className = "admin-loading-overlay";
    overlay.dataset.adminLoading = "true";
    overlay.innerHTML = '<div class="admin-loading-card" role="status" aria-live="polite"><span class="admin-loading-spinner"></span><strong>Aplicando cambios…</strong><small>Espera un momento</small></div>';
    document.body.append(overlay);
    return overlay;
  };
  const loading = {
    show(message = "Aplicando cambios…") {
      loadingDepth += 1;
      const overlay = createLoading();
      overlay.querySelector("strong").textContent = message;
      overlay.dataset.visible = "true";
      document.documentElement.dataset.adminBusy = "true";
    },
    hide() {
      loadingDepth = Math.max(0, loadingDepth - 1);
      if (loadingDepth) return;
      const overlay = document.querySelector("[data-admin-loading]");
      if (overlay) overlay.dataset.visible = "false";
      delete document.documentElement.dataset.adminBusy;
    },
    reset() { loadingDepth = 0; this.hide(); },
  };
  window.RoyalBeansAdminLoading = loading;
  const nativeFetch = window.fetch.bind(window);
  window.fetch = (...args) => {
    const input = args[0];
    const options = args[1] || {};
    const method = String(options.method || (input instanceof Request ? input.method : "GET")).toUpperCase();
    const url = new URL(input instanceof Request ? input.url : String(input), location.href);
    const tracked = method !== "GET" && url.origin === location.origin && url.pathname.startsWith("/admin/");
    if (tracked) loading.show("Aplicando cambios…");
    return nativeFetch(...args).then(response => { if (tracked && response.ok) announcePublicUpdate(); return response; }).finally(() => { if (tracked) loading.hide(); });
  };

  const announcePublicUpdate = () => {
    const detail = { scope: "all", at: Date.now() };
    try { localStorage.setItem("royalbeans:content-version", String(detail.at)); } catch (_) {}
    if ("BroadcastChannel" in window) {
      const channel = new BroadcastChannel("royalbeans-content");
      channel.postMessage(detail);
      channel.close();
    }
  };

  const persist = () => {
    try { sessionStorage.setItem(key, String(Math.max(0, scrollY))); } catch (_) {}
  };
  const save = () => {
    if (frame) return;
    frame = requestAnimationFrame(() => {
      frame = 0;
      persist();
    });
  };
  const restore = () => {
    if (!navigation || !["reload", "back_forward"].includes(navigation.type)) return;
    let top = 0;
    try { top = Number(sessionStorage.getItem(key) || 0); } catch (_) {}
    if (top > 0) requestAnimationFrame(() => requestAnimationFrame(() => scrollTo({ top, left: 0, behavior: "auto" })));
  };
  const checkSession = async () => {
    if (checkingSession || !navigator.onLine) return;
    checkingSession = true;
    try {
      const response = await fetch("/admin/session-ping.php", { credentials: "same-origin", cache: "no-store" });
      if (response.status === 401 || response.status === 403) {
        document.documentElement.dataset.adminSessionExpired = "true";
        dispatchEvent(new CustomEvent("royalbeans:admin-session-expired"));
        return;
      }
      delete document.documentElement.dataset.adminSessionExpired;
      if (response.ok) dispatchEvent(new CustomEvent("royalbeans:admin-resume"));
    } catch (_) {
      // La pantalla actual sigue operativa; se reintentará en la próxima reactivación.
    } finally {
      checkingSession = false;
    }
  };
  const prefetchAdminPage = target => {
    const link = target instanceof Element ? target.closest("a[href]") : null;
    if (!(link instanceof HTMLAnchorElement) || link.origin !== location.origin || !link.pathname.startsWith("/admin/")) return;
    const url = new URL(link.href);
    if (link.target || link.hasAttribute("download") || url.searchParams.get("view") === "logout") return;
    const href = link.href.split("#")[0];
    if (!href || href === location.href.split("#")[0] || prefetched.has(href)) return;
    prefetched.add(href);
    const preload = document.createElement("link");
    preload.rel = "prefetch";
    preload.as = "document";
    preload.href = href;
    document.head.append(preload);
  };
  const visibility = () => {
    document.documentElement.toggleAttribute("data-admin-hidden", document.hidden);
    if (document.hidden) {
      hiddenAt = Date.now();
      save();
    } else if (hiddenAt && Date.now() - hiddenAt > 20 * 60 * 1000) {
      checkSession();
    }
  };

  const initProductImagePreviews = () => {
    const mainInput = document.querySelector('input[type="file"][name="image"]');
    const mainPreview = mainInput?.closest(".editor-product-media");
    mainInput?.addEventListener("change", () => {
      const file = mainInput.files?.[0];
      if (!file || !mainPreview) return;
      let image = mainPreview.querySelector(":scope > img");
      if (!image) {
        image = document.createElement("img");
        mainPreview.querySelector(":scope > span")?.remove();
        mainPreview.prepend(image);
      }
      image.src = URL.createObjectURL(file);
      image.alt = file.name;
    });

    const galleryInput = document.querySelector('input[type="file"][name="gallery[]"]');
    const galleryEditor = document.querySelector(".gallery-editor");
    galleryInput?.addEventListener("change", () => {
      if (!galleryEditor) return;
      galleryEditor.querySelectorAll(".gallery-pending").forEach(item => item.remove());
      Array.from(galleryInput.files || []).forEach(file => {
        const card = document.createElement("label");
        const image = document.createElement("img");
        const status = document.createElement("span");
        card.className = "gallery-pending";
        image.src = URL.createObjectURL(file);
        image.alt = file.name;
        status.textContent = "Nueva imagen · se guardará con el producto";
        card.append(image, status);
        galleryEditor.append(card);
      });
    });
  };

  const initProductListFilters = () => {
    const table = document.querySelector(".table-wrap table");
    const toolbar = document.querySelector(".toolbar .filters");
    const tbody = table?.tBodies?.[0];
    if (!table || !toolbar || !tbody || table.dataset.filtersReady === "true") return;
    table.dataset.filtersReady = "true";
    const rows = Array.from(tbody.rows);
    const normalise = value => String(value || "").normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase().trim();
    const categories = [...new Set(rows.map(row => row.cells[3]?.textContent?.trim()).filter(Boolean))].sort((a,b) => a.localeCompare(b,"es"));
    const filters = document.createElement("section");
    filters.className = "product-admin-filters";
    filters.innerHTML = '<label><span>Producto</span><input type="search" placeholder="Buscar en español o inglés"></label><label><span>Categoría</span><select><option value="">Todas las categorías</option></select></label><label><span>Estado</span><select><option value="">Activos e inactivos</option><option value="active">Activos</option><option value="inactive">Inactivos</option></select></label>';
    const [search,category,status] = filters.querySelectorAll("input,select");
    categories.forEach(name => { const option=document.createElement("option");option.value=name;option.textContent=name;category.append(option); });
    const summary = document.createElement("footer");
    summary.className = "product-list-summary";
    summary.innerHTML = '<p></p><nav aria-label="Paginación de productos"></nav>';
    table.closest(".table-wrap").before(filters);
    table.closest(".table-wrap").after(summary);
    let page = 1;
    const render = () => {
      const term = normalise(search.value), selectedCategory=category.value, selectedStatus=status.value;
      const filtered = rows.filter(row => {
        const product=normalise(row.cells[1]?.textContent), rowCategory=row.cells[3]?.textContent?.trim() || "";
        const rowStatus=row.querySelector(".status")?.classList.contains("published") ? "active" : "inactive";
        return (!term || product.includes(term)) && (!selectedCategory || rowCategory===selectedCategory) && (!selectedStatus || rowStatus===selectedStatus);
      });
      const pages=Math.max(1,Math.ceil(filtered.length/10));page=Math.min(page,pages);const start=(page-1)*10,end=Math.min(start+10,filtered.length);
      rows.forEach(row => row.hidden=true);filtered.slice(start,end).forEach(row => row.hidden=false);
      const line=toolbar.querySelector("a.active")?.textContent?.trim() || "Productos";
      summary.querySelector("p").textContent=filtered.length ? `${line}: mostrando ${start+1}-${end} de ${filtered.length} productos` : `${line}: 0 productos encontrados`;
      const nav=summary.querySelector("nav");nav.innerHTML="";
      if(pages>1){for(let number=1;number<=pages;number++){const button=document.createElement("button");button.type="button";button.textContent=String(number);button.classList.toggle("active",number===page);button.addEventListener("click",()=>{page=number;render();table.scrollIntoView({behavior:"smooth",block:"start"})});nav.append(button)}}
    };
    [search,category,status].forEach(control => control.addEventListener(control===search?"input":"change",()=>{page=1;render()}));
    render();
  };

  const initRetailProductForm = () => {
    const form=document.querySelector('.product-studio-form input[name="line_slug"][value="retail"]')?.form;
    if(!form)return;
    form.classList.add("retail-product-form");
    const mainMedia=form.querySelector(".editor-product-media");
    const preservedPath=mainMedia?.querySelector('input[name="image_path"]');
    if(preservedPath)form.append(preservedPath);
    mainMedia?.remove();
    form.querySelector(".gallery-editor")?.closest("section")?.remove();
    const rename=(name,text)=>{const control=form.elements.namedItem(name);const label=control instanceof Element?control.closest("label"):null;if(!label)return;const node=Array.from(label.childNodes).find(item=>item.nodeType===Node.TEXT_NODE&&item.textContent.trim());if(node)node.textContent=text;};
    rename("short_es","Descripción");rename("short_en","Description");rename("description_es","Características del producto");rename("description_en","Product features");
    const packageEditor=form.querySelector("#package-editor");const section=packageEditor?.closest("section");if(section){const title=section.querySelector("h2");if(title)title.textContent="Presentaciones";const eyebrow=section.querySelector(".admin-eyebrow");if(eyebrow)eyebrow.textContent="Opciones disponibles";}
  };

  if ("scrollRestoration" in history) history.scrollRestoration = "manual";
  addEventListener("scroll", save, { passive: true });
  addEventListener("pagehide", persist);
  document.addEventListener("visibilitychange", visibility);
  document.addEventListener("pointerover", event => prefetchAdminPage(event.target), { passive: true });
  document.addEventListener("focusin", event => prefetchAdminPage(event.target));
  document.addEventListener("submit", event => {
    save();
    const form = event.target;
    if (form instanceof HTMLFormElement) setTimeout(() => { if (!event.defaultPrevented && form.checkValidity()) loading.show(form.enctype.includes("multipart") ? "Subiendo y aplicando cambios…" : "Aplicando cambios…"); }, 0);
  }, true);
  document.addEventListener("click", event => {
    if (!(event.target instanceof Element)) return;
    const link = event.target.closest("a[href]");
    if (!link) return;
    save();
    if (link instanceof HTMLAnchorElement && link.origin === location.origin && !link.target && !link.hasAttribute("download") && !link.href.includes("#")) setTimeout(() => { if (!event.defaultPrevented) loading.show("Cargando sección…"); }, 0);
  }, true);
  addEventListener("pageshow", () => loading.reset());
  visibility();
  const ready = () => {
    restore();
    initProductImagePreviews();
    initProductListFilters();
    initRetailProductForm();
    if (document.querySelector(".alert.success")) announcePublicUpdate();
  };
  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", ready, { once: true });
  else ready();
})();
