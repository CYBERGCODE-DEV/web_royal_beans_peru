"use client";
import { FormEvent, ReactNode, useEffect, useLayoutEffect, useRef, useState } from "react";
import Link from "next/link";
import { createPortal } from "react-dom";
import { usePathname } from "next/navigation";
import { ArrowRight, ArrowUpRight, CalendarCheck, ChevronDown, ChevronLeft, ChevronRight, Leaf, Link2, Mail, MapPin, Menu, MessageCircle, Phone, Play, Send, X, Globe2, ZoomIn, ZoomOut } from "lucide-react";
import { pagePath, productLinePath, productMenuLinks, type Lang, type PageKey } from "./routes";
type NavItem = { href: string; label: string };
export type CatalogProduct = { id?: number; es: string; en: string; slug_es?: string; slug_en?: string; description_es?: string; description_en?: string; alt_es?: string; alt_en?: string; category: string; category_name_es?: string; category_name_en?: string; image: string; image_320?: string; image_640?: string; line?: "conventional" | "retail" };
type ProductDetailPayload = {
  product: { id: number; name: string; image: string; image_original?: string; line: string; category: string; short_description: string; description: string; seo_title: string; seo_description: string; scientific_name: string; tariff_code: string; caliber: string; destinations: string; technical_sheet_path: string };
  gallery: Array<{ media_type: string; media_path: string; thumbnail_path?: string; original_path?: string; alt_text: string }>;
  packages: Array<{ weight_primary: string; weight_secondary: string; material: string; package_type: string; image_path: string; original_path?: string }>;
  harvest: Record<string, string>;
  certifications: string[];
  whatsapp_url: string;
};
type EventYear = "2024" | "2025";
export type CmsCollection = { id: number; entry_type: "event" | "presence" | "standard"; title: string; description: string; event_date: string | null; location: string; logo_path: string; cover_path: string; media: Array<{ path: string; caption: string }> };
export type ContactChannel = { id: number; channel_type: string; label: string; value_text: string; link_url: string };
type CatalogLoadState = { products: CatalogProduct[] | null; failed: boolean; retry: () => void };
type RichTextRun = { text: string; color?: string };
declare global { interface Window { dataLayer?: Array<Record<string, unknown>> } }

function usePublicRevision(scopes: string[]) {
  const [revision, setRevision] = useState(0);
  useEffect(() => {
    const refresh = (event: Event) => {
      const scope = (event as CustomEvent<{ scope?: string }>).detail?.scope || "all";
      if (scope === "all" || scopes.includes(scope)) setRevision(value => value + 1);
    };
    window.addEventListener("royalbeans:content-change", refresh);
    return () => window.removeEventListener("royalbeans:content-change", refresh);
  }, [scopes.join("|")]);
  return revision;
}

function useCatalogProducts(line: "conventional" | "retail", featured = false, initialProducts: CatalogProduct[] = []): CatalogLoadState {
  const [items, setItems] = useState<CatalogProduct[] | null>(initialProducts.length ? initialProducts : null);
  const [failed, setFailed] = useState(false);
  const [requestVersion, setRequestVersion] = useState(0);
  const publicRevision = usePublicRevision(["products", "media"]);
  useLayoutEffect(() => {
    if (featured) return;
    try {
      const selected = JSON.parse(document.getElementById("selected-product-data")?.textContent || "null") as { product?: CatalogProduct } | null;
      const product = selected?.product;
      if (!product?.id || product.line !== line) return;
      setItems(current => {
        const existing = current ?? [];
        const index = existing.findIndex(item => item.id === product.id);
        if (index < 0) return [...existing, product];
        const updated = [...existing];
        updated[index] = { ...updated[index], ...product };
        return updated;
      });
    } catch { /* A normal catalogue URL has no server-selected product. */ }
  }, [featured, line]);
  useEffect(() => {
    if (line !== "retail") return;
    const refreshRetailCatalog = () => setRequestVersion(value => value + 1);
    window.addEventListener("focus", refreshRetailCatalog);
    return () => window.removeEventListener("focus", refreshRetailCatalog);
  }, [line]);
  useEffect(() => {
    let active = true;
    let controller: AbortController | null = null;
    setFailed(false);
    const load = async () => {
      for (let attempt = 0; attempt < 2 && active; attempt++) {
        controller = new AbortController();
        const timeout = window.setTimeout(() => controller?.abort(), 4000);
        try {
          const freshness = line === "retail" ? `&t=${Date.now()}` : "";
          const response = await fetch(`/api/products.php?line=${line}${featured ? "&featured=1" : ""}${freshness}`, { headers: { Accept: "application/json" }, cache: "no-store", signal: controller.signal });
          if (!response.ok) throw new Error("Catalogue unavailable");
          const payload = await response.json();
          const products = Array.isArray(payload.products) ? payload.products : [];
          if (!active) return;
          setItems(products);
          return;
        } catch {
          if (attempt === 0 && active) await new Promise(resolve => window.setTimeout(resolve, 350));
        } finally {
          window.clearTimeout(timeout);
        }
      }
      if (active) { setItems(current => current ?? []); setFailed(true); }
    };
    void load();
    return () => { active = false; controller?.abort(); };
  }, [line, featured, requestVersion, publicRevision]);
  return { products: items, failed, retry: () => { setItems(null); setRequestVersion(current => current + 1); } };
}
export function usePublicCollections(module: "presentation" | "impact" | "standards", lang: Lang) {
  const [items, setItems] = useState<CmsCollection[] | null>(null);
  const publicRevision = usePublicRevision([module, "sections", "media"]);
  useEffect(() => {
    const controller = new AbortController();
    fetch(`/api/sections.php?module=${module}&lang=${lang}`, { headers: { Accept: "application/json" }, cache: "no-store", signal: controller.signal })
      .then(response => response.ok ? response.json() : Promise.reject(new Error("Section unavailable")))
      .then(payload => setItems(Array.isArray(payload.collections) ? payload.collections : []))
      .catch(() => { if (!controller.signal.aborted) setItems([]); });
    return () => controller.abort();
  }, [module, lang, publicRevision]);
  return items;
}
export function QualityStandards({ lang, titles, descriptions }: { lang: Lang; titles: string[]; descriptions: string[] }) {
  const standards = usePublicCollections("standards", lang);
  const items = standards ?? titles.map((title, index) => ({
    id: -(index + 1), title, description: descriptions[index] || "",
    logo_path: ["/images/standards/fda.png", "/images/standards/senasa.png", "/images/standards/haccp.png"][index],
  }));
  return <>{items.map(standard => <li key={standard.id}><span className="standard-logo"><img src={standard.logo_path} alt={standard.title} loading="lazy" /></span><div><h3>{standard.title}</h3><p>{standard.description}</p></div></li>)}</>;
}
export function useContactChannels(lang: Lang) {
  const [channels, setChannels] = useState<ContactChannel[] | null>(null);
  const publicRevision = usePublicRevision(["contacts"]);
  useEffect(() => {
    const controller = new AbortController();
    fetch(`/api/sections.php?module=contacts&lang=${lang}`, { headers: { Accept: "application/json" }, cache: "no-store", signal: controller.signal })
      .then(response => response.ok ? response.json() : Promise.reject(new Error("Contacts unavailable")))
      .then(payload => setChannels(Array.isArray(payload.contacts) ? payload.contacts : []))
      .catch(() => { if (!controller.signal.aborted) setChannels([]); });
    return () => controller.abort();
  }, [lang, publicRevision]);
  return channels;
}
export function ContactChannels({ lang }: { lang: Lang }) {
  const channels = useContactChannels(lang);
  const directChannels = (channels ?? []).filter(channel => !["facebook", "instagram", "youtube", "linkedin", "tiktok"].includes(channel.channel_type));
  const address = directChannels.find(channel => channel.channel_type === "address");
  const icon: Record<string, typeof MessageCircle> = { address: MapPin, email: Mail, phone: Phone };
  const safeLink = (value: string) => /^(https?:|mailto:|tel:)/i.test(value) ? value : "";
  return <div className="contact-details" data-cms-managed>{directChannels.map(channel => { const Icon=icon[channel.channel_type] ?? MessageCircle; const href=safeLink(channel.link_url); return <article key={channel.id}><span>{channel.channel_type === "whatsapp" ? <WhatsappIcon size={22} /> : <Icon size={22} />}</span><div><h3>{channel.label}</h3>{href ? <a href={href} target={href.startsWith("http") ? "_blank" : undefined} rel={href.startsWith("http") ? "noreferrer" : undefined}>{channel.value_text}</a> : <p>{channel.value_text}</p>}</div></article>; })}{address && <div className="contact-map"><iframe src={`https://www.google.com/maps?q=${encodeURIComponent(address.value_text)}&output=embed`} title={lang === "es" ? `Mapa de ${address.value_text}` : `Map of ${address.value_text}`} loading="lazy" referrerPolicy="no-referrer-when-downgrade" /></div>}</div>;
}
function trackConversion(event: string, details: Record<string, unknown> = {}) {
  const payload = { event, ...details };
  window.dataLayer = window.dataLayer || [];
  window.dataLayer.push(payload);
  window.dispatchEvent(new CustomEvent("royalbeans:conversion", { detail: payload }));
}

export function ConversionTracker() {
  const pathname = usePathname();
  useEffect(() => {
    if ("scrollRestoration" in history) history.scrollRestoration = "manual";
    document.documentElement.style.overflow = "";
    document.body.style.overflow = "";
    document.body.style.position = "";
    document.body.style.top = "";
    document.body.style.width = "";
    const scrollKey = `royalbeans:scroll:${pathname}${location.search}`;
    let scrollFrame = 0;
    let hiddenAt = 0;
    const persistScroll = () => {
      try { sessionStorage.setItem(scrollKey, String(Math.max(0, window.scrollY))); } catch {}
    };
    const saveScroll = () => {
      if (scrollFrame) return;
      scrollFrame = requestAnimationFrame(() => {
        scrollFrame = 0;
        persistScroll();
      });
    };
    window.addEventListener("scroll", saveScroll, { passive: true });
    window.addEventListener("pagehide", persistScroll);
    const syncPageVisibility = () => {
      document.documentElement.toggleAttribute("data-page-hidden", document.hidden);
      if (document.hidden) {
        hiddenAt = Date.now();
        saveScroll();
        return;
      }
      if (hiddenAt && Date.now() - hiddenAt > 30 * 60 * 1000) {
        window.dispatchEvent(new CustomEvent("royalbeans:resume", { detail: { inactiveMs: Date.now() - hiddenAt } }));
      }
    };
    syncPageVisibility();
    document.addEventListener("visibilitychange", syncPageVisibility);
    const trackClick = (event: MouseEvent) => {
      const link = (event.target as Element | null)?.closest<HTMLAnchorElement>("a[href]");
      if (!link) return;
      const href = link.href;
      const target = new URL(href, window.location.href);
      if (target.origin === window.location.origin && (target.pathname !== location.pathname || target.search !== location.search)) persistScroll();
      const label = link.textContent?.trim().replace(/\s+/g, " ").slice(0, 100) || link.getAttribute("aria-label") || "";
      if (href.includes("wa.me/")) trackConversion("click_whatsapp", { label, location: window.location.pathname });
      else if (href.startsWith("mailto:")) trackConversion("click_email", { label, location: window.location.pathname });
      else if (href.includes("?product=")) trackConversion("product_enquiry", { product: new URL(href).searchParams.get("product"), location: window.location.pathname });
      else if (/\/contacto\/?$|\/en\/contact\/?$/.test(new URL(href).pathname)) trackConversion("contact_cta_click", { label, location: window.location.pathname });
    };
    document.addEventListener("click", trackClick);
    return () => {
      if (location.pathname === pathname) persistScroll();
      document.removeEventListener("click", trackClick);
      document.removeEventListener("visibilitychange", syncPageVisibility);
      window.removeEventListener("scroll", saveScroll);
      window.removeEventListener("pagehide", persistScroll);
      if (scrollFrame) cancelAnimationFrame(scrollFrame);
    };
  }, [pathname]);
  return null;
}

export function ContentProtection({ lang }: { lang: Lang }) {
  const [visible, setVisible] = useState(false);
  const hideTimer = useRef<ReturnType<typeof setTimeout> | null>(null);
  useEffect(() => {
    if (new URLSearchParams(location.search).has("cms_preview")) return;
    document.body.dataset.contentProtected = "true";
    const notify = () => {
      setVisible(true);
      if (hideTimer.current) clearTimeout(hideTimer.current);
      hideTimer.current = setTimeout(() => setVisible(false), 1200);
    };
    const blockContext = (event: MouseEvent) => { event.preventDefault(); notify(); };
    const blockImageDrag = (event: DragEvent) => {
      if ((event.target as Element | null)?.closest("img,picture")) { event.preventDefault(); notify(); }
    };
    const blockSelection = (event: Event) => {
      const target = event.target as Element | null;
      if (target?.closest("input,textarea,select,option,[contenteditable='true']")) return;
      event.preventDefault();
    };
    const blockShortcut = (event: KeyboardEvent) => {
      const key = event.key.toLowerCase();
      const command = event.ctrlKey || event.metaKey;
      const inspect = event.key === "F12" || (command && event.shiftKey && ["i", "j", "c", "k"].includes(key)) || (command && ["u", "s"].includes(key)) || (event.metaKey && event.altKey && ["i", "j", "c"].includes(key));
      if (!inspect) return;
      event.preventDefault();
      event.stopImmediatePropagation();
      notify();
    };
    document.addEventListener("contextmenu", blockContext);
    document.addEventListener("dragstart", blockImageDrag);
    document.addEventListener("selectstart", blockSelection);
    window.addEventListener("keydown", blockShortcut, true);
    return () => {
      delete document.body.dataset.contentProtected;
      document.removeEventListener("contextmenu", blockContext);
      document.removeEventListener("dragstart", blockImageDrag);
      document.removeEventListener("selectstart", blockSelection);
      window.removeEventListener("keydown", blockShortcut, true);
      if (hideTimer.current) clearTimeout(hideTimer.current);
    };
  }, []);
  return <div className="content-protected-notice" data-visible={visible ? "true" : "false"} role="status" aria-live="polite">{lang === "es" ? "Contenido protegido" : "Protected content"}</div>;
}

function renderCmsText(element: HTMLElement, value: string, runs?: RichTextRun[]) {
  const preserveChildren = Boolean(element.querySelector(":scope > svg, :scope > img, :scope > span:not([data-cms-run])"));
  const validRuns = Array.isArray(runs)
    && runs.length > 0
    && runs.every(run => run && typeof run.text === "string" && (!run.color || /^#[0-9a-f]{6}$/i.test(run.color)))
    && runs.map(run => run.text).join("") === value;
  if (preserveChildren) {
    const textNode = Array.from(element.childNodes).find(node => node.nodeType === Node.TEXT_NODE && node.textContent?.trim());
    if (textNode) textNode.textContent = value;
    else element.prepend(document.createTextNode(value));
    return;
  }
  if (!validRuns) {
    element.textContent = value;
    return;
  }
  element.replaceChildren(...runs.map(run => {
    if (!run.color) return document.createTextNode(run.text);
    const span = document.createElement("span");
    span.dataset.cmsRun = "color";
    span.style.color = run.color;
    span.textContent = run.text;
    return span;
  }));
}

export function ContentHydrator({ page, lang }: { page: string; lang: Lang }) {
  const publicRevision = usePublicRevision(["content", "media"]);
  useLayoutEffect(() => {
    const stateRoot = document.querySelector<HTMLElement>(".public-page-stage");
    const serverRendered = publicRevision === 0 && (window as Window & { __ROYALBEANS_SERVER_CMS_PATH__?: string }).__ROYALBEANS_SERVER_CMS_PATH__ === window.location.pathname;
    if (stateRoot && !serverRendered) stateRoot.dataset.cmsState = "loading";
    const forcedBreakElements = Array.from(document.querySelectorAll<HTMLElement>("[data-cms-break-after]"));
    const enforceTitleBreak = (element: HTMLElement) => {
      if (element.querySelector("br")) return;
      const text = element.textContent ?? "";
      const words = Array.from(text.matchAll(/\S+/g));
      const breakAfter = Math.max(1, Math.min(words.length - 1, Number(element.dataset.cmsBreakAfter || 1)));
      if (words.length < 2 || breakAfter >= words.length) return;
      const offset = (words[breakAfter - 1].index ?? 0) + words[breakAfter - 1][0].length;
      const walker = document.createTreeWalker(element, NodeFilter.SHOW_TEXT);
      let consumed = 0;
      let node = walker.nextNode() as Text | null;
      while (node) {
        const next = consumed + node.data.length;
        if (offset <= next) {
          const tail = node.splitText(Math.max(0, offset - consumed));
          tail.parentNode?.insertBefore(document.createElement("br"), tail);
          return;
        }
        consumed = next;
        node = walker.nextNode() as Text | null;
      }
    };
    forcedBreakElements.forEach(enforceTitleBreak);
    const forcedBreakObserver = new MutationObserver(records => records.forEach(record => {
      const element = record.target instanceof Element
        ? record.target.closest<HTMLElement>("[data-cms-break-after]")
        : record.target.parentElement?.closest<HTMLElement>("[data-cms-break-after]");
      if (element) enforceTitleBreak(element);
    }));
    forcedBreakElements.forEach(element => forcedBreakObserver.observe(element, { childList: true, characterData: true, subtree: true }));
    const roots = Array.from(document.querySelectorAll<HTMLElement>("main"));
    const used = new Set(Array.from(document.querySelectorAll<HTMLElement>("[data-cms]")).map(element => element.dataset.cms || ""));
    const safe = (value: string) => value.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "").replace(/[^a-z0-9]+/g, "-").replace(/^-|-$/g, "").slice(0, 46);
    roots.forEach((root, rootIndex) => {
      const directSections = Array.from(root.querySelectorAll<HTMLElement>(":scope > section"));
      const sections = directSections.length ? directSections : [root];
      sections.forEach((section, sectionIndex) => {
        if (section.matches("[data-cms-managed]")) return;
        const sectionName = safe(section.id || Array.from(section.classList).find(name => !["section-pad","container"].includes(name)) || `${root.tagName.toLowerCase()}-${rootIndex}-${sectionIndex}`) || `section-${sectionIndex}`;
        section.dataset.cmsSection = sectionName;
        const elements = Array.from(section.querySelectorAll<HTMLElement>('h1,h2,h3,h4,h5,h6,p,li,blockquote,figcaption,dt,dd,a,button,span,strong,small,label,img:not([alt=""])')).filter(element => element instanceof HTMLImageElement || Array.from(element.childNodes).some(node => node.nodeType === Node.TEXT_NODE && node.textContent?.trim()));
        const counters: Record<string, number> = {};
        elements.forEach(element => {
          if (element.dataset.cms || element.closest("[data-product-id],[data-cms-managed]")) return;
          const kind = element instanceof HTMLImageElement ? "image" : safe(element.tagName.toLowerCase() + "-" + (element.className || "text"));
          counters[kind] = (counters[kind] || 0) + 1;
          let key = `${sectionName}.${kind}-${counters[kind]}`;
          while (used.has(key)) { counters[kind] += 1; key = `${sectionName}.${kind}-${counters[kind]}`; }
          used.add(key); element.dataset.cms = key;
        });
      });
    });
    if (serverRendered) {
      window.dispatchEvent(new CustomEvent("royalbeans:cms-ready"));
      return () => forcedBreakObserver.disconnect();
    }
    const controller = new AbortController();
    const contentRequest = new URL("/api/content.php", window.location.origin);
    contentRequest.searchParams.set("page", page);
    contentRequest.searchParams.set("lang", lang);
    contentRequest.searchParams.set("revision", Date.now().toString());
    fetch(contentRequest, { cache: "no-store", headers: { "Cache-Control": "no-cache", Pragma: "no-cache" }, signal: controller.signal })
      .then(response => response.ok ? response.json() : Promise.reject(new Error("Content unavailable")))
      .then(payload => {
        const fields = payload.fields ?? {};
        // Los campos nuevos conservan su texto estático hasta que la migración CMS los registre.
        // Así una publicación parcial nunca deja la página bloqueada o sin contenido.
        Object.entries(payload.fields ?? {}).forEach(([key, field]) => {
          const data = field as { type?: string; value?: string; style?: { font?: string; color?: string; size?: number; heroHeight?: number; heroOverlay?: number; heroShowText?: boolean; richText?: Partial<Record<Lang, RichTextRun[]>> } };
          document.querySelectorAll<HTMLElement>(`[data-cms="${CSS.escape(key)}"]`).forEach(element => {
            if (typeof data.value === "string" && data.type === "image" && element instanceof HTMLImageElement) {
              const imageValue = data.value.trim();
              element.hidden = imageValue === "";
              element.toggleAttribute("data-cms-image-empty", imageValue === "");
              if (imageValue !== "") element.src = imageValue;
              else element.removeAttribute("src");
              element.closest("picture")?.querySelectorAll("source").forEach(source => {
                if (imageValue !== "") source.srcset = imageValue;
                else source.removeAttribute("srcset");
              });
            }
            else if (data.type === "image") return;
            else if (typeof data.value === "string" && data.type === "url" && element instanceof HTMLAnchorElement) element.href = data.value;
            else if (typeof data.value === "string") renderCmsText(element, data.value, data.style?.richText?.[lang]);
            const fonts: Record<string, string> = { display: "var(--font-display-active,var(--font-display))", body: "var(--font-body-active,var(--font-body))", editorial: "Georgia, 'Times New Roman', serif" };
            if (data.style?.font && fonts[data.style.font]) element.style.fontFamily = fonts[data.style.font];
            if (data.style?.color && /^#[0-9a-f]{6}$/i.test(data.style.color)) element.style.color = data.style.color;
            if (data.style?.size && data.style.size >= 10 && data.style.size <= 120) element.style.fontSize = `${data.style.size}px`;
            if (key === "hero.image") {
              const hero = element.closest<HTMLElement>("[data-hero]");
              if (!hero) return;
              if (data.style?.heroHeight && data.style.heroHeight >= 360 && data.style.heroHeight <= 950) hero.style.setProperty("--hero-height", `${data.style.heroHeight}px`);
              if (typeof data.style?.heroOverlay === "number") {
                const overlay = `${Math.max(0, Math.min(90, data.style.heroOverlay)) / 100}`;
                hero.style.setProperty("--hero-overlay", overlay);
                document.documentElement.style.setProperty("--hero-overlay", overlay);
              }
              hero.toggleAttribute("data-hero-text-hidden", data.style?.heroShowText === false);
            }
          });
        });
        const palettes: Record<string, Record<string, string>> = {
          royal: { "--forest": "#052f26", "--green": "#0a493c", "--lime": "#c5e59b", "--paper": "#f4f5ec" },
          harvest: { "--forest": "#263c2c", "--green": "#496044", "--lime": "#d8e7a5", "--paper": "#f7f3e8" },
          pacific: { "--forest": "#123b3a", "--green": "#1f5d58", "--lime": "#b9e2bd", "--paper": "#f1f5f0" },
        };
        const backgrounds: Record<string, string> = { paper: "#f4f5ec", white: "#ffffff", mist: "#edf3e9" };
        const fonts: Record<string, [string, string]> = {
          "bricolage-dm": ["var(--font-display)", "var(--font-body)"],
          editorial: ["Georgia, 'Times New Roman', serif", "var(--font-body)"],
          modern: ["var(--font-body)", "var(--font-body)"],
        };
        const theme = payload.theme ?? {};
        const themeRoot = document.querySelector<HTMLElement>(".public-page-stage");
        if (!themeRoot) return;
        Object.entries(palettes[theme.theme_palette] ?? palettes.royal).forEach(([key, value]) => themeRoot.style.setProperty(key, value));
        themeRoot.style.setProperty("--paper", backgrounds[theme.theme_background] ?? backgrounds.paper);
        const selectedFonts = fonts[theme.theme_font] ?? fonts["bricolage-dm"];
        themeRoot.style.setProperty("--font-display-active", selectedFonts[0]);
        themeRoot.style.setProperty("--font-body-active", selectedFonts[1]);
        themeRoot.dataset.cmsTheme = theme.theme_palette ?? "royal";
        themeRoot.dataset.cmsState = "ready";
        window.dispatchEvent(new CustomEvent("royalbeans:cms-ready"));
      })
      .catch(() => {
        if (controller.signal.aborted) return;
        if (stateRoot) stateRoot.dataset.cmsState = "error";
      });
    return () => { forcedBreakObserver.disconnect(); controller.abort(); };
  }, [page, lang, publicRevision]);
  return null;
}
type HomeAnnouncement = { id: number; image_path: string; alt_text: string };
export function HomeAnnouncements({ lang }: { lang: Lang }) {
  const [items,setItems]=useState<HomeAnnouncement[]>([]),[open,setOpen]=useState(false),[index,setIndex]=useState(0);const closeRef=useRef<HTMLButtonElement>(null);
  const [imageSize,setImageSize]=useState<{path:string;width:number;height:number}|null>(null);
  const [viewport,setViewport]=useState({width:0,height:0});
  const publicRevision=usePublicRevision(["announcements","media"]);
  useEffect(()=>{const controller=new AbortController();fetch(`/api/announcements.php?lang=${lang}`,{cache:"no-store",signal:controller.signal}).then(response=>response.ok?response.json():Promise.reject()).then(payload=>{const next=Array.isArray(payload.items)?payload.items:[];setItems(next);if(next.length){setIndex(0);setOpen(true);}}).catch(()=>{});return()=>controller.abort();},[lang,publicRevision]);
  useEffect(()=>{if(!open)return;const previous=document.body.style.overflow;document.body.style.overflow="hidden";closeRef.current?.focus();const keyboard=(event:KeyboardEvent)=>{if(event.key==="Escape")setOpen(false);if(items.length>1&&event.key==="ArrowRight")setIndex(value=>(value+1)%items.length);if(items.length>1&&event.key==="ArrowLeft")setIndex(value=>(value-1+items.length)%items.length);};window.addEventListener("keydown",keyboard);return()=>{document.body.style.overflow=previous;window.removeEventListener("keydown",keyboard);};},[open,items.length]);
  useEffect(()=>{if(!open)return;const update=()=>setViewport({width:window.innerWidth,height:window.innerHeight});update();window.addEventListener("resize",update);return()=>window.removeEventListener("resize",update);},[open]);
  if(!open||!items.length)return null;const current=items[index];const move=(step:number)=>setIndex(value=>(value+step+items.length)%items.length);
  const gutter=viewport.width<600?10:Math.min(48,Math.max(16,viewport.width*.04));
  const maxHeight=Math.min(viewport.height*.74,760,viewport.height-gutter*2);
  const width=imageSize?.path===current.image_path&&viewport.width>0
    ?Math.min(imageSize.width,860,viewport.width-gutter*2,maxHeight*imageSize.width/imageSize.height)
    :undefined;
  return createPortal(<div className="home-announcement-overlay" role="presentation" onMouseDown={event=>{if(event.target===event.currentTarget)setOpen(false)}}><section className="home-announcement-dialog" style={width?{width:`${Math.max(1,width)}px`}:undefined} role="dialog" aria-modal="true" aria-label={lang==="es"?"Anuncios":"Announcements"}><button ref={closeRef} className="home-announcement-close" type="button" onClick={()=>setOpen(false)} aria-label={lang==="es"?"Cerrar anuncio":"Close announcement"}><X size={25}/></button><img key={current.id} src={current.image_path} alt={current.alt_text||""} onLoad={event=>{const image=event.currentTarget;if(image.naturalWidth&&image.naturalHeight)setImageSize({path:current.image_path,width:image.naturalWidth,height:image.naturalHeight});}}/>{items.length>1&&<><button className="home-announcement-arrow previous" type="button" onClick={()=>move(-1)} aria-label={lang==="es"?"Anuncio anterior":"Previous announcement"}><ChevronLeft size={30}/></button><button className="home-announcement-arrow next" type="button" onClick={()=>move(1)} aria-label={lang==="es"?"Siguiente anuncio":"Next announcement"}><ChevronRight size={30}/></button><div className="home-announcement-position" aria-live="polite">{index+1} / {items.length}</div></>}</section></div>,document.body);
}
export function Brand() { return <><span className="brand-mark"><img src="/images/logo.webp" alt="" width="52" height="60" /></span><span className="brand-copy"><strong>ROYAL BEANS PERÚ</strong></span></>; }

export function AboutVideo({ lang }: { lang: Lang }) {
  const [playing, setPlaying] = useState(false);
  const [videoUrl, setVideoUrl] = useState("");
  const publicRevision = usePublicRevision(["content", "media"]);
  useEffect(() => {
    const controller = new AbortController();
    setPlaying(false);
    setVideoUrl("");
    fetch(`/api/content.php?page=nosotros&lang=${lang}`, { cache: "no-store", signal: controller.signal })
      .then(response => response.ok ? response.json() : Promise.reject(new Error("Video unavailable")))
      .then(payload => {
        const source = payload.fields?.["about-video.source"]?.value;
        if (typeof source === "string" && /^https:\/\//i.test(source)) setVideoUrl(source);
      })
      .catch(() => {});
    return () => controller.abort();
  }, [lang, publicRevision]);
  if (playing && videoUrl) return <video key={videoUrl} src={videoUrl} controls autoPlay playsInline preload="none" aria-label={lang === "es" ? "Video institucional en español" : "Corporate video in English"} />;
  if (playing && lang === "es") return <iframe src="https://www.youtube-nocookie.com/embed/PaMhqpNjbPE?autoplay=1&rel=0" title="Video institucional de Royal Beans Perú" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowFullScreen />;
  if (!videoUrl && lang === "en") return null;
  return <button className="about-video-poster" type="button" onClick={() => setPlaying(true)} aria-label={lang === "es" ? "Reproducir video institucional" : "Play corporate video"}>
    <img src={lang === "es" ? "/images/about-video-poster.jpg" : "/images/expo-connection.webp"} alt="" width="1280" height="720" loading="lazy" />
    <span><Play size={22} fill="currentColor" />{lang === "es" ? "Reproducir video" : "Play video"}</span>
  </button>;
}

export function EnglishAboutVideoSection({ kicker, title, description }: { kicker: string; title: string; description: string }) {
  const [available, setAvailable] = useState(false);
  const publicRevision = usePublicRevision(["content", "media"]);
  useEffect(() => {
    const controller = new AbortController();
    setAvailable(false);
    fetch("/api/content.php?page=nosotros&lang=en", { cache: "no-store", signal: controller.signal })
      .then(response => response.ok ? response.json() : Promise.reject(new Error("Video unavailable")))
      .then(payload => setAvailable(typeof payload.fields?.["about-video.source"]?.value === "string" && /^https:\/\//i.test(payload.fields["about-video.source"].value)))
      .catch(() => setAvailable(false));
    return () => controller.abort();
  }, [publicRevision]);
  if (!available) return null;
  return <div className="about-video-grid">
    <div className="about-video-copy"><p className="eyebrow"><span />{kicker}</p><h3>{title}</h3><p>{description}</p></div>
    <div className="about-video-frame"><AboutVideo lang="en" /></div>
  </div>;
}

export function HorizontalRail({ children, label, previousLabel, nextLabel }: { children: ReactNode; label: string; previousLabel: string; nextLabel: string }) {
  const viewportRef = useRef<HTMLDivElement>(null);
  const move = (direction: -1 | 1) => {
    const viewport = viewportRef.current;
    if (!viewport) return;
    const max = viewport.scrollWidth - viewport.clientWidth;
    const atStart = viewport.scrollLeft <= 2;
    const atEnd = viewport.scrollLeft >= max - 2;
    const left = direction === 1 && atEnd ? 0 : direction === -1 && atStart ? max : viewport.scrollLeft + direction * Math.min(viewport.clientWidth * .72, 560);
    viewport.scrollTo({ left, behavior: window.matchMedia("(prefers-reduced-motion: reduce)").matches ? "auto" : "smooth" });
  };
  return <div className="horizontal-rail"><div className="rail-controls" aria-label={label}><button type="button" onClick={() => move(-1)} aria-label={previousLabel}><ChevronLeft size={20} /></button><button type="button" onClick={() => move(1)} aria-label={nextLabel}><ChevronRight size={20} /></button></div><div ref={viewportRef} className="trace-viewport" tabIndex={0} aria-label={label}>{children}</div></div>;
}

export function WhatsappIcon({ size = 22 }: { size?: number }) {
  return <svg width={size} height={size} viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.04 2a9.84 9.84 0 0 0-8.46 14.85L2 22l5.28-1.53A9.92 9.92 0 1 0 12.04 2Zm0 17.93a8.06 8.06 0 0 1-4.1-1.12l-.29-.17-3.13.91.92-3.05-.19-.31a8.09 8.09 0 1 1 6.79 3.74Zm4.44-6.06c-.24-.12-1.44-.71-1.66-.79-.22-.08-.39-.12-.55.12-.16.24-.63.79-.77.95-.14.16-.28.18-.52.06-.24-.12-1.03-.38-1.96-1.21a7.35 7.35 0 0 1-1.36-1.69c-.14-.24-.01-.37.11-.49.11-.11.24-.28.36-.42.12-.14.16-.24.24-.4.08-.16.04-.3-.02-.42-.06-.12-.55-1.33-.75-1.82-.2-.48-.4-.41-.55-.42h-.47c-.16 0-.42.06-.65.3-.22.24-.85.83-.85 2.02 0 1.19.87 2.34.99 2.5.12.16 1.71 2.61 4.14 3.66.58.25 1.03.4 1.38.51.58.18 1.11.16 1.53.1.47-.07 1.44-.59 1.64-1.16.2-.57.2-1.07.14-1.17-.06-.1-.22-.16-.47-.28Z" /></svg>;
}

export function WhatsappMascot({ lang }: { lang: Lang }) {
  const messages = lang === "es"
    ? ["¿Buscas un producto?", "Cotiza por WhatsApp", "¿Hablamos?"]
    : ["Looking for a product?", "Get a WhatsApp quote", "Let’s chat"];
  const [messageIndex, setMessageIndex] = useState(0);
  const channels = useContactChannels(lang);
  const whatsapp = channels?.find(channel => channel.channel_type === "whatsapp");
  const href = whatsapp?.link_url || (whatsapp?.value_text ? `https://wa.me/${whatsapp.value_text.replace(/\D/g, "")}` : "https://wa.me/51961804500");
  useEffect(() => {
    const timer = window.setInterval(() => setMessageIndex(current => (current + 1) % messages.length), 6000);
    return () => window.clearInterval(timer);
  }, [messages.length]);
  if (!href) return null;
  return <a className="whatsapp-float whatsapp-mascot" href={href} target="_blank" rel="noreferrer" aria-label={lang === "es" ? "Conversar por WhatsApp" : "Chat on WhatsApp"}>
    <span key={messageIndex} className="whatsapp-mascot-label" aria-hidden="true">{messages[messageIndex]}</span>
    <img src="/images/mascotita-whatsapp.png" alt="" width="640" height="563" />
  </a>;
}

export function InstagramIcon({ size = 22 }: { size?: number }) {
  return <svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5" /><circle cx="12" cy="12" r="4" /><circle cx="17.4" cy="6.6" r="1" fill="currentColor" stroke="none" /></svg>;
}

export function FacebookIcon({ size = 22 }: { size?: number }) {
  return <svg width={size} height={size} viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M13.65 21v-8.2h2.76l.41-3.2h-3.17V7.56c0-.93.26-1.56 1.59-1.56h1.7V3.14A22.7 22.7 0 0 0 14.47 3c-2.45 0-4.12 1.49-4.12 4.24V9.6H7.58v3.2h2.77V21h3.3Z" /></svg>;
}

export function YoutubeIcon({ size = 22 }: { size?: number }) {
  return <svg width={size} height={size} viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M21.58 7.19a2.86 2.86 0 0 0-2.01-2.02C17.79 4.69 12 4.69 12 4.69s-5.79 0-7.57.48a2.86 2.86 0 0 0-2.01 2.02A29.8 29.8 0 0 0 1.94 12c0 1.61.16 3.22.48 4.81a2.86 2.86 0 0 0 2.01 2.02c1.78.48 7.57.48 7.57.48s5.79 0 7.57-.48a2.86 2.86 0 0 0 2.01-2.02c.32-1.59.48-3.2.48-4.81s-.16-3.22-.48-4.81ZM9.94 15.12V8.88L15.33 12l-5.39 3.12Z" /></svg>;
}

export function AnimatedCounter({ target, suffix = "" }: { target: number; suffix?: string }) {
  const [value, setValue] = useState(target);
  const counterRef = useRef<HTMLSpanElement>(null);
  useEffect(() => {
    const element = counterRef.current;
    if (!element) return;
    if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) { setValue(target); return; }
    let frame = 0;
    const observer = new IntersectionObserver(([entry]) => {
      if (!entry.isIntersecting) return;
      setValue(0);
      const startedAt = performance.now();
      const animate = (now: number) => {
        const progress = Math.min((now - startedAt) / 1200, 1);
        setValue(Math.round(target * (1 - Math.pow(1 - progress, 3))));
        if (progress < 1) frame = requestAnimationFrame(animate);
      };
      frame = requestAnimationFrame(animate);
      observer.disconnect();
    }, { threshold: 0.45 });
    observer.observe(element);
    return () => { observer.disconnect(); cancelAnimationFrame(frame); };
  }, [target]);
  return <span ref={counterRef}>{value}{suffix}</span>;
}
export function Header({ nav, lang, page, languagePaths }: { nav: NavItem[]; lang: Lang; page: PageKey; languagePaths?: { es: string; en: string } }) {
  const [open, setOpen] = useState(false);
  const [productsOpen, setProductsOpen] = useState(false);
  const [scrolled, setScrolled] = useState(false);
  const [productLanguagePaths, setProductLanguagePaths] = useState<{ es: string; en: string } | null>(() => {
    if (typeof document === "undefined") return null;
    try {
      const selected = JSON.parse(document.getElementById("selected-product-data")?.textContent || "null") as { languagePaths?: { es: string; en: string } } | null;
      return selected?.languagePaths?.es && selected.languagePaths.en ? selected.languagePaths : null;
    } catch { return null; }
  });
  const selectedLanguagePaths = productLanguagePaths ?? languagePaths;
  const active = pagePath(page, lang);
  const productsHref = pagePath("productos", lang);
  const productLinks = productMenuLinks(lang);
  const menuRef = useRef<HTMLButtonElement>(null);
  const headerRef = useRef<HTMLElement>(null);
  useEffect(() => {
    const update = (event: Event) => setProductLanguagePaths((event as CustomEvent<{ es: string; en: string } | null>).detail);
    window.addEventListener("royalbeans:product-language-paths", update);
    const selected = document.getElementById("selected-product-data");
    if (selected?.textContent) {
      try {
        const product = JSON.parse(selected.textContent) as { languagePaths?: { es: string; en: string } };
        if (product.languagePaths?.es && product.languagePaths?.en) setProductLanguagePaths(product.languagePaths);
      } catch { /* The catalogue remains usable without the optional server selection. */ }
    }
    return () => window.removeEventListener("royalbeans:product-language-paths", update);
  }, []);
  useEffect(() => {
    document.querySelectorAll<HTMLElement>('[data-menu-inert="true"]').forEach(element => {
      element.inert = false;
      delete element.dataset.menuInert;
    });
    const update = () => setScrolled(window.scrollY > 28);
    update(); window.addEventListener("scroll", update, { passive: true });
    const reveal = new IntersectionObserver(entries => { entries.forEach(entry => { if (entry.isIntersecting) { entry.target.classList.add("reveal-in"); reveal.unobserve(entry.target); } }); }, { threshold: 0.12 });
    if (!window.matchMedia("(prefers-reduced-motion: reduce)").matches) document.querySelectorAll(".section-heading,.content-heading,.content-traits article,.impact-copy,.impact-media,.impact-feature-grid article,.image-reveal,.event-grid figure,.home-about-media,.home-value-list article,.home-origin-content,.product-card,.about-pillar-grid article,.trace-steps li,.standards-grid article").forEach(el => reveal.observe(el));
    return () => { window.removeEventListener("scroll", update); reveal.disconnect(); };
  }, [page]);
  useEffect(() => { setOpen(false); setProductsOpen(false); }, [page, lang, languagePaths?.es, languagePaths?.en]);
  useEffect(() => {
    const desktop = window.matchMedia("(min-width: 1280px)");
    const closeOnDesktop = () => { if (desktop.matches) setOpen(false); };
    desktop.addEventListener("change", closeOnDesktop);
    return () => desktop.removeEventListener("change", closeOnDesktop);
  }, []);
  useEffect(() => {
    if (!open) return;
    const previousOverflow = document.body.style.overflow;
    document.body.classList.add("menu-open");
    document.body.style.overflow = "hidden";
    const background = Array.from(document.querySelectorAll<HTMLElement>("main, footer, .whatsapp-float"));
    background.forEach(element => {
      if (!element.inert) {
        element.inert = true;
        element.dataset.menuInert = "true";
      }
    });
    headerRef.current?.querySelector<HTMLAnchorElement>("#mobile-menu .mobile-menu-links a")?.focus();
    const close = (e: KeyboardEvent) => {
      if (e.key === "Escape") { e.preventDefault(); setOpen(false); menuRef.current?.focus(); }
      if (e.key === "Tab") {
        const elements = Array.from(headerRef.current?.querySelectorAll<HTMLElement>("a[href], button") ?? []).filter(element => element.getClientRects().length > 0);
        const first = elements[0]; const last = elements[elements.length - 1];
        if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last?.focus(); }
        else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first?.focus(); }
      }
    };
    window.addEventListener("keydown", close);
    return () => {
      window.removeEventListener("keydown", close);
      document.body.classList.remove("menu-open");
      document.body.style.overflow = previousOverflow;
      background.forEach(element => {
        if (element.dataset.menuInert === "true") {
          element.inert = false;
          delete element.dataset.menuInert;
        }
      });
    };
  }, [open]);
  useEffect(() => {
    if (!productsOpen) return;
    const close = (event: MouseEvent | KeyboardEvent) => {
      if (event instanceof KeyboardEvent && event.key === "Escape") setProductsOpen(false);
      if (event instanceof MouseEvent && !(event.target as Element).closest(".nav-products, .mobile-products")) setProductsOpen(false);
    };
    document.addEventListener("mousedown", close);
    document.addEventListener("keydown", close);
    return () => { document.removeEventListener("mousedown", close); document.removeEventListener("keydown", close); };
  }, [productsOpen]);
  return <header ref={headerRef} data-cms-managed className={`site-header shared-site-header ${page !== "inicio" ? "interior-header" : ""} ${scrolled || open ? "is-scrolled" : ""} ${open ? "is-menu-open" : ""}`}>
    <Link className="brand" href={pagePath("inicio", lang)} prefetch={false} aria-label={lang === "es" ? "Royal Beans Perú — Inicio" : "Royal Beans Perú — Home"}><Brand /></Link>
    <nav className="desktop-nav gooey-nav" aria-label={lang === "es" ? "Navegación principal" : "Main navigation"}>{nav.map((item, index) => item.href === productsHref ? <div className="nav-products" key={item.href}><button type="button" aria-current={page === "productos" ? "page" : undefined} aria-expanded={productsOpen} aria-controls="desktop-products-menu" className="gooey-nav-item nav-products-trigger" onClick={() => setProductsOpen(value => !value)}><span className="nav-label">{item.label}</span><ChevronDown size={15} aria-hidden="true" /></button><div id="desktop-products-menu" className="product-submenu" hidden={!productsOpen}>{productLinks.map(link => <a key={link.href} href={link.href} onClick={() => setProductsOpen(false)}>{link.label}<ArrowUpRight size={16} /></a>)}</div></div> : <Link key={item.href} aria-current={active === item.href ? "page" : undefined} className={`gooey-nav-item${index === nav.length - 1 ? " nav-contact" : ""}`} href={item.href} prefetch={false}><span className="nav-label">{item.label}</span>{index === nav.length - 1 && <ArrowUpRight size={16} aria-hidden="true" />}</Link>)}</nav>
    <div className="header-actions"><Globe2 size={16} aria-hidden="true" /><div className="languages"><a suppressHydrationWarning href={selectedLanguagePaths?.es ?? pagePath(page, "es")} lang="es" aria-current={lang === "es" ? "page" : undefined}>ES</a><span>/</span><a suppressHydrationWarning href={selectedLanguagePaths?.en ?? pagePath(page, "en")} lang="en" aria-current={lang === "en" ? "page" : undefined}>EN</a></div><button ref={menuRef} className="menu-toggle" onClick={() => { setOpen(value => !value); setProductsOpen(false); }} aria-expanded={open} aria-controls="mobile-menu" aria-label={lang === "es" ? (open ? "Cerrar menú" : "Abrir menú") : (open ? "Close menu" : "Open menu")}>
      {open ? <X size={24} /> : <Menu size={24} />}</button></div>
    <nav id="mobile-menu" className="mobile-menu" hidden={!open} aria-label={lang === "es" ? "Navegación móvil" : "Mobile navigation"}>
      <Link className="mobile-menu-brand" href={pagePath("inicio", lang)} prefetch={false} aria-label={lang === "es" ? "Royal Beans Perú — Inicio" : "Royal Beans Perú — Home"}><Brand /></Link>
      <div className="mobile-menu-links">{nav.map((item, index) => item.href === productsHref ? <div className="mobile-products" key={item.href}><button type="button" className="mobile-products-trigger" aria-current={page === "productos" ? "page" : undefined} aria-expanded={productsOpen} aria-controls="mobile-products-menu" onClick={() => setProductsOpen(value => !value)}>{item.label}<ChevronDown size={18} aria-hidden="true" /></button><div id="mobile-products-menu" className="mobile-products-menu" hidden={!productsOpen}>{productLinks.map(link => <a key={link.href} href={link.href} onClick={() => { setOpen(false); setProductsOpen(false); }}>{link.label}<ArrowUpRight size={18} /></a>)}</div></div> : <Link key={item.href} className={index === nav.length - 1 ? "mobile-contact" : undefined} href={item.href} prefetch={false} aria-current={active === item.href ? "page" : undefined} onClick={() => setOpen(false)}>{item.label}{index === nav.length - 1 && <ArrowUpRight size={18} aria-hidden="true" />}</Link>)}</div>
      <div className="mobile-menu-languages"><Globe2 size={22} aria-hidden="true" /><a suppressHydrationWarning href={selectedLanguagePaths?.es ?? pagePath(page, "es")} lang="es" aria-current={lang === "es" ? "page" : undefined}>ES</a><span>/</span><a suppressHydrationWarning href={selectedLanguagePaths?.en ?? pagePath(page, "en")} lang="en" aria-current={lang === "en" ? "page" : undefined}>EN</a></div>
      <p className="mobile-menu-signature">{lang === "es" ? <>Del Perú<br />para el mundo</> : <>From Peru<br />to the world</>}</p>
    </nav>
  </header>;
}

function storedProductSlug(product: CatalogProduct, lang: Lang) { return product[lang === "es" ? "slug_es" : "slug_en"] || ""; }
function catalogPath(line: "conventional" | "retail", lang: Lang) {
  return productLinePath(line, lang);
}
function productPublicPath(product: CatalogProduct, lang: Lang) {
  const stored = storedProductSlug(product, lang);
  const path = catalogPath(product.line === "retail" ? "retail" : "conventional", lang);
  return stored ? `${path}?producto=${encodeURIComponent(stored)}` : path;
}
function setPageMeta(selector: string, content: string) {
  const element = document.head.querySelector<HTMLMetaElement>(selector);
  if (element) element.content = content;
}
function requestedProductSlug(line: "conventional" | "retail", lang: Lang) {
  const url = new URL(window.location.href);
  if (url.pathname !== catalogPath(line, lang)) return "";
  return url.searchParams.get("producto") || url.searchParams.get("product") || "";
}
function initialServerProduct(line: "conventional" | "retail", lang: Lang): CatalogProduct | null {
  if (typeof document === "undefined" || !requestedProductSlug(line, lang)) return null;
  try {
    const product = (JSON.parse(document.getElementById("selected-product-data")?.textContent || "null") as { product?: CatalogProduct } | null)?.product;
    return product?.line === line && storedProductSlug(product, lang) === requestedProductSlug(line, lang) ? product : null;
  } catch { return null; }
}
function productImageAlt(product: CatalogProduct, lang: Lang) {
  return product[lang === "es" ? "alt_es" : "alt_en"]?.trim() || `${product[lang]} - Royal Beans Perú`;
}
function initialServerProductDetail(id: number, lang: Lang): ProductDetailPayload | null {
  if (typeof document === "undefined") return null;
  try {
    const detail = JSON.parse(document.getElementById("selected-product-detail-data")?.textContent || "null") as ProductDetailPayload | null;
    return detail?.product.id === id && (lang === "en" || lang === "es") ? detail : null;
  } catch { return null; }
}
function setLanguageAlternates(paths: { es: string; en: string }) {
  const targets: Record<string, string> = { "es-PE": paths.es, en: paths.en, "x-default": paths.es };
  document.querySelectorAll<HTMLLinkElement>('link[rel="alternate"][hreflang]').forEach(link => {
    const path = targets[link.hreflang];
    if (path) link.href = new URL(path,window.location.origin).href;
  });
  window.dispatchEvent(new CustomEvent("royalbeans:product-language-paths", { detail: paths }));
}
function showProductInUrl(product: CatalogProduct, lang: Lang) {
  const url = new URL(window.location.href);
  const slug = storedProductSlug(product, lang);
  if (!slug) return;
  url.searchParams.delete("product");
  url.searchParams.set("producto", slug);
  history.pushState({ ...history.state, productModal: product.id }, "", `${url.pathname}${url.search}${url.hash}`);
  setLanguageAlternates({ es: productPublicPath(product,"es"), en: productPublicPath(product,"en") });
  const productUrl = new URL(productPublicPath(product,lang),url.origin).href;
  document.title = `${product[lang]} | Royal Beans Perú`;
  document.head.querySelector<HTMLLinkElement>('link[rel="canonical"]')?.setAttribute("href",productUrl);
  setPageMeta('meta[property="og:title"]',document.title);
  setPageMeta('meta[property="og:url"]',productUrl);
  setPageMeta('meta[name="twitter:title"]',document.title);
}
function removeProductFromUrl(returnPath: string) {
  const url = new URL(window.location.href);
  url.pathname = returnPath;
  url.searchParams.delete("producto");
  url.searchParams.delete("product");
  history.replaceState(history.state, "", `${url.pathname}${url.search}${url.hash}`);
  const conventional = returnPath.includes("linea-a-granel") || returnPath.includes("bulk-line");
  const isHome = returnPath === "/" || returnPath === "/en/";
  setLanguageAlternates(isHome ? { es: "/", en: "/en/" } : { es: catalogPath(conventional ? "conventional" : "retail","es"), en: catalogPath(conventional ? "conventional" : "retail","en") });
  document.title = isHome
    ? (returnPath === "/en/" ? "Royal Beans Perú | Peruvian Agricultural Exporter" : "Royal Beans Perú | Agroexportadora Peruana")
    : langFromPath(returnPath) === "en"
      ? (conventional ? "Bulk Line for Export | Royal Beans Perú" : "Peruvian retail products | Royal Beans Perú")
      : (conventional ? "Línea a Granel para Exportación | Royal Beans Perú" : "Productos retail peruanos | Royal Beans Perú");
  const canonical = new URL(returnPath,window.location.origin).href;
  const description = isHome
    ? (langFromPath(returnPath) === "en" ? "Peruvian company specializing in grains, pulses, seeds and spices." : "Empresa peruana dedicada a granos, legumbres, semillas y especias.")
    : (langFromPath(returnPath) === "en" ? "Catalogue of selected Peruvian products for commercial and export operations." : "Catálogo de productos peruanos seleccionados para operaciones comerciales y exportación.");
  document.head.querySelector<HTMLLinkElement>('link[rel="canonical"]')?.setAttribute("href",canonical);
  setPageMeta('meta[name="description"]',description);
  setPageMeta('meta[property="og:title"]',document.title);
  setPageMeta('meta[property="og:description"]',description);
  setPageMeta('meta[property="og:url"]',canonical);
  setPageMeta('meta[name="twitter:title"]',document.title);
  setPageMeta('meta[name="twitter:description"]',description);
}
function langFromPath(path: string): Lang { return path.startsWith("/en/") ? "en" : "es"; }
export function ProductCatalog({ lang, line = "conventional", initialProducts = [] }: { lang: Lang; line?: "conventional" | "retail"; initialProducts?: CatalogProduct[] }) {
  const categoryGroup = (value: string) => value === "corn" ? "grains" : value;
  const [category, setCategory] = useState(categoryGroup(initialProducts[0]?.category || "")); const [expanded, setExpanded] = useState(false);
  const [previewIndex, setPreviewIndex] = useState<number | null>(null);
  const [serverSelectedProduct, setServerSelectedProduct] = useState<CatalogProduct | null>(() => initialServerProduct(line, lang));
  const { products: catalogProducts, failed, retry } = useCatalogProducts(line, false, initialProducts);
  const filters = Object.fromEntries((catalogProducts || []).map(product => {
    const group = categoryGroup(product.category);
    const label = group === "grains" ? (lang === "es" ? "Granos y semillas" : "Grains & seeds") : product[lang === "es" ? "category_name_es" : "category_name_en"] || product.category;
    return [group, label];
  }));
  const filtered = catalogProducts?.filter(product => !category || category === "all" || categoryGroup(product.category) === category) ?? [];
  useEffect(() => { if (catalogProducts?.length && !category) setCategory(categoryGroup(catalogProducts[0].category)); }, [catalogProducts, category]);
  useEffect(() => {
    const preventPreviewNavigation = (event: MouseEvent) => {
      const link = (event.target as Element | null)?.closest<HTMLAnchorElement>("a[data-product-preview-link]");
      if (link) event.preventDefault();
    };
    const loader = document.querySelector<HTMLElement>(".public-route-loader");
    const previousPosition = loader?.style.position ?? "";
    const previousInset = loader?.style.inset ?? "";
    if (loader) { loader.style.position = "absolute"; loader.style.inset = "0"; }
    window.addEventListener("click",preventPreviewNavigation,true);
    return () => {
      window.removeEventListener("click",preventPreviewNavigation,true);
      if (loader) { loader.style.position = previousPosition; loader.style.inset = previousInset; }
    };
  }, []);
  useLayoutEffect(() => {
    if (!catalogProducts) return;
    const syncLocation = () => {
      const requested = requestedProductSlug(line,lang);
      if (!requested) {
        setPreviewIndex(null);
        setServerSelectedProduct(null);
        const requestedCategory = new URL(window.location.href).searchParams.get("categoria");
        if (requestedCategory && catalogProducts.some(item => categoryGroup(item.category) === requestedCategory)) setCategory(requestedCategory);
        return;
      }
      let selectedId = 0;
      try { selectedId = Number(JSON.parse(document.getElementById("selected-product-data")?.textContent || "null")?.productId) || 0; } catch { /* The URL slug remains the source of truth. */ }
      const item = catalogProducts.find(candidate => storedProductSlug(candidate,lang) === requested || (selectedId > 0 && candidate.id === selectedId));
      if (!item) { setPreviewIndex(null); return; }
      setCategory(categoryGroup(item.category));
      setExpanded(true);
      setPreviewIndex(catalogProducts.filter(candidate => categoryGroup(candidate.category) === categoryGroup(item.category)).findIndex(candidate => candidate.id === item.id));
      setLanguageAlternates({ es: productPublicPath(item,"es"), en: productPublicPath(item,"en") });
      if (item.id) prefetchProductDetail(item.id,lang);
    };
    syncLocation();
    window.addEventListener("popstate",syncLocation);
    return () => window.removeEventListener("popstate",syncLocation);
  }, [catalogProducts, lang, line]);
  const openPreview = (index: number) => { const item=filtered[index]; if (item?.id) prefetchProductDetail(item.id,lang); if (item) showProductInUrl(item,lang); setServerSelectedProduct(null); setPreviewIndex(index); };
  const warmPreview = (_index: number) => {};
  return <><div className="catalog-toolbar" data-cms-managed><div className="product-filters" role="group" aria-label={lang === "es" ? "Filtrar productos" : "Filter products"}>{Object.entries(filters).map(([key, label]) => <button type="button" key={key} aria-pressed={category === key} onClick={() => { setCategory(key); setExpanded(false); }}>{label}</button>)}</div></div>
    {catalogProducts === null ? <CatalogLoadingState lang={lang} /> : failed && catalogProducts.length === 0 ? <CatalogErrorState lang={lang} retry={retry} /> : filtered.length ? <div className={`product-grid ${expanded ? "is-expanded" : ""}`}>{filtered.map((product, index) => <article className="product-card" data-product-id={product.id} key={product.id ?? product.es}>
      <a href={productPublicPath(product,lang)} data-product-preview-link className="product-image" onPointerEnter={() => warmPreview(index)} onFocus={() => warmPreview(index)} onClick={event => { event.preventDefault(); openPreview(index); }} aria-label={`${lang === "es" ? "Ver detalles de" : "View details for"} ${product[lang]}`}><span className="product-index">{String(index + 1).padStart(2, "0")}</span><img src={product.image} srcSet={product.image_320 && product.image_640 ? `${product.image_320} 320w, ${product.image_640} 640w` : undefined} sizes="(max-width: 640px) 45vw, (max-width: 1024px) 30vw, 260px" alt={productImageAlt(product,lang)} loading="lazy" decoding="async" width="640" height="640" /><span className="product-arrow"><ArrowUpRight size={20} /></span></a><div className="product-meta"><p>{filters[categoryGroup(product.category)]}</p><h3><a href={productPublicPath(product,lang)} data-product-preview-link className="product-title-button" onPointerEnter={() => warmPreview(index)} onFocus={() => warmPreview(index)} onClick={event => { event.preventDefault(); openPreview(index); }}>{product[lang]}</a></h3><div className="home-product-actions product-card-actions"><a className="button button-forest" href={productPublicPath(product,lang)} data-product-preview-link onPointerEnter={() => warmPreview(index)} onFocus={() => warmPreview(index)} onClick={event => { event.preventDefault(); openPreview(index); }}>{lang === "es" ? "Ver detalles" : "View details"}<ArrowUpRight size={15} aria-hidden="true" /></a></div></div>
    </article>)}</div> : <div className="catalog-empty"><span><PackageIcon /></span><h3>{lang === "es" ? "Catálogo en preparación" : "Catalogue in preparation"}</h3><p>{lang === "es" ? "Añade los productos Retail desde el panel administrativo para publicarlos aquí." : "Add Retail products from the administration panel to publish them here."}</p></div>}{filtered.length > 4 && <button className="catalog-more text-link" aria-expanded={expanded} onClick={() => setExpanded(!expanded)}>{expanded ? (lang === "es" ? "Ver menos" : "Show less") : (lang === "es" ? `Explorar los ${filtered.length} productos` : `Explore all ${filtered.length} products`)}<ArrowRight size={18} /></button>}
    <ProductSheetDialog product={previewIndex === null ? serverSelectedProduct : filtered[previewIndex]} lang={lang} onClose={() => { const selectedId=(previewIndex === null ? serverSelectedProduct : filtered[previewIndex])?.id; removeProductFromUrl(catalogPath(line,lang)); setServerSelectedProduct(null); setPreviewIndex(null); if(selectedId) requestAnimationFrame(() => document.querySelector<HTMLElement>(`.product-card[data-product-id="${selectedId}"] a[data-product-preview-link]`)?.focus()); }} />
  </>;
}

function PackageIcon() { return <svg viewBox="0 0 24 24" width="27" height="27" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><path d="m21 8-9-5-9 5 9 5 9-5Z"/><path d="m3 8 9 5 9-5M3 8v8l9 5 9-5V8M12 13v8"/></svg>; }

function CatalogLoadingState({ lang }: { lang: Lang }) {
  return <div className="catalog-loading-skeleton" data-cms-managed role="status"><span className="sr-only">{lang === "es" ? "Cargando productos" : "Loading products"}</span>{[0,1,2,3].map(index => <i aria-hidden="true" key={index} />)}</div>;
}

function CatalogErrorState({ lang, retry }: { lang: Lang; retry: () => void }) {
  return <div className="catalog-empty catalog-load-error" data-cms-managed role="alert"><span><PackageIcon /></span><h3>{lang === "es" ? "No pudimos cargar los productos" : "We could not load the products"}</h3><p>{lang === "es" ? "Comprueba tu conexión e inténtalo nuevamente." : "Check your connection and try again."}</p><button className="button button-forest" type="button" onClick={retry}>{lang === "es" ? "Reintentar" : "Try again"}</button></div>;
}

function HarvestCalendar({ harvest, lang }: { harvest: Record<string,string>; lang: Lang }) {
  const months = lang === "es" ? ["Ene","Feb","Mar","Abr","May","Jun","Jul","Ago","Set","Oct","Nov","Dic"] : ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];
  return <section className="native-product-section native-harvest"><img className="native-harvest-leaf native-harvest-leaf-left" src="/images/bean-leaf.png" alt="" aria-hidden="true" /><img className="native-harvest-leaf native-harvest-leaf-right" src="/images/bean-leaf-mirrored.png" alt="" aria-hidden="true" /><div className="native-harvest-heading"><div className="native-harvest-title"><h3>{lang === "es" ? "Calendario agrícola" : "Agricultural calendar"}</h3><p>{lang === "es" ? "Ciclos que hacen crecer el campo" : "Cycles that make the fields grow"}</p></div><div className="native-harvest-legend" aria-label={lang === "es" ? "Leyenda del calendario" : "Calendar legend"}><span className="planting"><i />{lang === "es" ? "Siembra" : "Planting"}</span><span className="harvest"><i />{lang === "es" ? "Cosecha" : "Harvest"}</span></div></div><div className="native-harvest-scroll"><div className="native-harvest-arc"><div className="native-harvest-curve" aria-hidden="true" />{months.map((month,index) => {const rawStatus=harvest[String(index + 1)] ?? "none";const status=rawStatus === "available" ? "harvest" : rawStatus;const label=status === "planting" ? (lang === "es" ? "Siembra" : "Planting") : status === "harvest" ? (lang === "es" ? "Cosecha" : "Harvest") : "";return <div className={`native-harvest-month month-${index + 1} ${status}`} style={{"--month-index":index} as React.CSSProperties} key={month}><span>{month}</span><i />{label && <strong>{label}</strong>}</div>;})}<div className="native-harvest-center"><img src="/images/agricultural-calendar-field.png" alt="" /><div className="native-harvest-motto"><i /><p>{lang === "es" ? <>Buenas temporadas,<br />mejores cosechas</> : <>Good seasons,<br />better harvests</>}</p><i /></div></div></div></div></section>;
}

const productDetailMemory = new Map<string,{ payload: ProductDetailPayload; savedAt: number }>();
const productDetailRequests = new Map<string,Promise<ProductDetailPayload>>();
const productDetailTtl = 10 * 60 * 1000;
function productDetailKey(id: number, lang: Lang) { return `royalbeans:product-detail:${id}:${lang}`; }
function getCachedProductDetail(id: number, lang: Lang): ProductDetailPayload | null {
  const key = productDetailKey(id,lang);
  const memory = productDetailMemory.get(key);
  if (memory && Date.now() - memory.savedAt < productDetailTtl) return memory.payload;
  try {
    const stored = JSON.parse(sessionStorage.getItem(key) || "null") as { payload?: ProductDetailPayload; savedAt?: number } | null;
    if (stored?.payload && stored.savedAt && Date.now() - stored.savedAt < productDetailTtl) {
      productDetailMemory.set(key,{payload:stored.payload,savedAt:stored.savedAt});
      return stored.payload;
    }
  } catch {}
  return null;
}
function loadProductDetail(id: number, lang: Lang, fresh = false): Promise<ProductDetailPayload> {
  const cached = fresh ? null : getCachedProductDetail(id,lang);
  if (cached) return Promise.resolve(cached);
  const key = productDetailKey(id,lang);
  const pending = fresh ? null : productDetailRequests.get(key);
  if (pending) return pending;
  const request = fetch(`/api/products.php?id=${id}&lang=${lang}&t=${Date.now()}`, { headers: { Accept: "application/json" }, cache: "no-store" })
    .then(response => response.ok ? response.json() : Promise.reject(new Error("Product unavailable")))
    .then((payload: ProductDetailPayload) => {
      const savedAt=Date.now();
      productDetailMemory.set(key,{payload,savedAt});
      try { sessionStorage.setItem(key,JSON.stringify({payload,savedAt})); } catch {}
      return payload;
    })
    .finally(() => productDetailRequests.delete(key));
  productDetailRequests.set(key,request);
  return request;
}

function prefetchProductDetail(id: number, lang: Lang) {
  void loadProductDetail(id,lang).catch(() => undefined);
}

function ProductSheetDialog({ product, lang, onClose }: { product: CatalogProduct | null; lang: Lang; onClose: () => void }) {
  const publicRevision = usePublicRevision(["products", "media"]);
  const dialogRef = useRef<HTMLDialogElement>(null);
  const contentRef = useRef<HTMLDivElement>(null);
  const calendarCloseRef = useRef<HTMLButtonElement>(null);
  const packageCloseRef = useRef<HTMLButtonElement>(null);
  const [detail, setDetail] = useState<ProductDetailPayload | null>(null);
  const [loading, setLoading] = useState(false);
  const [activeImage, setActiveImage] = useState(0);
  const [imageZoomed, setImageZoomed] = useState(false);
  const [calendarOpen, setCalendarOpen] = useState(false);
  const [packagesOpen, setPackagesOpen] = useState(false);
  const [copied, setCopied] = useState(false);
  const [activePackage, setActivePackage] = useState(0);
  const [quoteQuantity, setQuoteQuantity] = useState(1);
  const [serverPreviewActive, setServerPreviewActive] = useState(() => typeof document !== "undefined" && !!document.getElementById("selected-product-data"));
  useEffect(() => {
    document.querySelectorAll<HTMLElement>('[data-dialog-inert="true"]').forEach(element => {
      element.inert = false;
      delete element.dataset.dialogInert;
    });
  }, []);
  useLayoutEffect(() => {
    const dialog = dialogRef.current;
    if (!dialog) return;
    if (!product) { if (dialog.open) dialog.close(); return; }
    if (!dialog.open) dialog.showModal();

    const rootOverflow = document.documentElement.style.overflow;
    const bodyStyles = { overflow: document.body.style.overflow, paddingRight: document.body.style.paddingRight };
    const scrollbarWidth = Math.max(0, window.innerWidth - document.documentElement.clientWidth);
    const inertElements = new Set<HTMLElement>();
    let activeBranch: HTMLElement = dialog;
    while (activeBranch.parentElement) {
      const parent = activeBranch.parentElement;
      Array.from(parent.children).forEach(sibling => {
        if (sibling !== activeBranch && sibling instanceof HTMLElement) {
          if (!sibling.inert) {
            sibling.inert = true;
            sibling.dataset.dialogInert = "true";
            inertElements.add(sibling);
          }
        }
      });
      if (parent === document.body) break;
      activeBranch = parent;
    }
    document.documentElement.style.overflow = "hidden";
    document.body.style.overflow = "hidden";
    if (scrollbarWidth > 0) document.body.style.paddingRight = `${scrollbarWidth}px`;
    return () => {
      document.documentElement.style.overflow = rootOverflow;
      Object.assign(document.body.style, bodyStyles);
      inertElements.forEach(element => {
        if (element.dataset.dialogInert === "true") {
          element.inert = false;
          delete element.dataset.dialogInert;
        }
      });
    };
  }, [product]);
  useEffect(() => {
    if (!product?.id) { setDetail(null); setServerPreviewActive(false); setCalendarOpen(false); setPackagesOpen(false); return; }
    const serverDetail = initialServerProductDetail(product.id,lang);
    if (serverDetail) {
      setDetail(serverDetail); setLoading(false); setServerPreviewActive(false);
      setActiveImage(0); setActivePackage(0); setQuoteQuantity(1); setImageZoomed(false);
      setCalendarOpen(false); setPackagesOpen(false);
      return;
    }
    let active=true;
    const requiresFreshRetailDetail = product.line === "retail";
    const cached = requiresFreshRetailDetail ? null : getCachedProductDetail(product.id,lang);
    setDetail(cached); setLoading(!cached); setActiveImage(0); setActivePackage(0); setQuoteQuantity(1); setImageZoomed(false); setCalendarOpen(false); setPackagesOpen(false);
    void loadProductDetail(product.id,lang,requiresFreshRetailDetail)
      .then(payload => { if (active) setDetail(payload); })
      .catch(() => undefined)
      .finally(() => { if (active) { setLoading(false); setServerPreviewActive(false); } });
    return () => { active=false; };
  }, [product?.id, lang]);
  useEffect(() => {
    if (!product?.id || publicRevision === 0) return;
    let active=true;
    void loadProductDetail(product.id,lang,true).then(payload => { if (active) setDetail(payload); }).catch(() => undefined);
    return () => { active=false; };
  }, [product?.id, lang, publicRevision]);
  useEffect(() => {
    if (!product || !detail) return;
    const title = detail.product.seo_title?.trim() || `${detail.product.name} | Royal Beans Perú`;
    const description = detail.product.seo_description?.trim() || detail.product.short_description?.trim() || detail.product.description?.trim();
    document.title = title;
    setPageMeta('meta[property="og:title"]',title);
    setPageMeta('meta[name="twitter:title"]',title);
    if (description) {
      setPageMeta('meta[name="description"]',description);
      setPageMeta('meta[property="og:description"]',description);
      setPageMeta('meta[name="twitter:description"]',description);
    }
    const currentUrl = new URL(productPublicPath(product,lang),window.location.origin).href;
    document.head.querySelector<HTMLLinkElement>('link[rel="canonical"]')?.setAttribute("href",currentUrl);
    setPageMeta('meta[property="og:url"]',currentUrl);
    if (detail.product.image) setPageMeta('meta[property="og:image"]',new URL(detail.product.image,window.location.origin).href);
  }, [product,detail]);
  useEffect(() => {
    if (!calendarOpen) return;
    calendarCloseRef.current?.focus();
  }, [calendarOpen]);
  useEffect(() => {
    if (!packagesOpen) return;
    packageCloseRef.current?.focus();
  }, [packagesOpen]);
  useEffect(() => {
    if (!product) return;
    const frame = requestAnimationFrame(() => contentRef.current?.scrollTo({ top: 0, left: 0, behavior: "auto" }));
    return () => cancelAnimationFrame(frame);
  }, [product, detail]);
  const images = detail?.gallery.filter(item => item.media_type === "image").map(item => item.media_path) ?? (product ? [product.image] : []);
  const imageThumbnails = detail?.gallery.filter(item => item.media_type === "image").map(item => item.thumbnail_path || item.media_path) ?? [];
  const imageOriginals = detail?.gallery.filter(item => item.media_type === "image").map(item => item.original_path || item.media_path) ?? [];
  const imageAlts = detail?.gallery.filter(item => item.media_type === "image").map(item => item.alt_text) ?? [];
  const retailPackages = detail?.product.line === "retail" ? detail.packages : [];
  const selectedPackage = retailPackages[activePackage] ?? retailPackages[0];
  const retailImage = selectedPackage?.image_path || "";
  const retailCategoryLabels: Record<string,string> = lang === "es"
    ? { pulses: "Legumbres", grains: "Granos y semillas", corn: "Maíces", spices: "Especias" }
    : { pulses: "Pulses", grains: "Grains & seeds", corn: "Corn", spices: "Spices" };
  const retailCategory = retailCategoryLabels[detail?.product.category ?? product?.category ?? ""];
  const consult = lang === "es" ? "Consultar con nuestro equipo" : "Ask our team";
  const shareTitle = detail?.product.name ?? product?.[lang] ?? "Royal Beans Perú";
  const shareUrl = () => product ? new URL(productPublicPath(product,lang),window.location.origin).href : window.location.href;
  const copyShareLink = async () => {
    const url=shareUrl();
    try { await navigator.clipboard.writeText(url); }
    catch { const field=document.createElement("textarea");field.value=url;field.style.position="fixed";field.style.opacity="0";document.body.append(field);field.select();document.execCommand("copy");field.remove(); }
    setCopied(true); window.setTimeout(() => setCopied(false),1800);
  };
  const shareToInstagram = async () => {
    const data={title:shareTitle,text:lang === "es" ? `Conoce ${shareTitle} de Royal Beans Perú` : `Discover ${shareTitle} from Royal Beans Perú`,url:shareUrl()};
    if (navigator.share) { try { await navigator.share(data); return; } catch (error) { if ((error as DOMException).name === "AbortError") return; } }
    await copyShareLink(); window.open("https://www.instagram.com/","_blank","noopener,noreferrer");
  };
  const requestRetailQuote = () => {
    if (!detail || detail.product.line !== "retail") return;
    const presentation = selectedPackage?.weight_primary || (lang === "es" ? "Por confirmar" : "To be confirmed");
    const text = lang === "es"
      ? `Hola, deseo solicitar una cotización.\nProducto: ${detail.product.name}\nPresentación: ${presentation}\nCantidad: ${quoteQuantity}\nEnlace: ${shareUrl()}`
      : `Hello, I would like to request a quote.\nProduct: ${detail.product.name}\nPresentation: ${presentation}\nQuantity: ${quoteQuantity}\nLink: ${shareUrl()}`;
    const base = detail.whatsapp_url || "https://wa.me/51961804500";
    window.open(`${base.split("?")[0]}?text=${encodeURIComponent(text)}`, "_blank", "noopener,noreferrer");
  };
  if (product && serverPreviewActive && !detail) return <dialog ref={dialogRef} className="product-sheet-dialog" data-cms-managed role="dialog" aria-modal="true" aria-labelledby="product-sheet-title" onCancel={event => { event.preventDefault(); onClose(); }} onClose={onClose}>
    <button className="product-sheet-close" type="button" onClick={onClose} aria-label={lang === "es" ? "Cerrar ficha" : "Close product sheet"}>×</button>
    <a className="product-sheet-language" href={productPublicPath(product,lang === "es" ? "en" : "es")} lang={lang === "es" ? "en" : "es"} aria-label={lang === "es" ? "Ver este producto en inglés" : "View this product in Spanish"}>{lang === "es" ? "EN" : "ES"}</a>
    <div className="product-sheet-dialog-inner"><article className={`native-product-sheet${product.line === "retail" ? " retail-product-sheet" : ""}`} aria-busy="true"><section className="native-product-hero">
      <div className="native-product-gallery"><button className="native-product-zoom" type="button" tabIndex={-1}><img src={product.image} alt={productImageAlt(product,lang)} /></button></div>
      <div className="native-product-copy"><p className="eyebrow">{product.line === "retail" ? (lang === "es" ? "Línea retail" : "Retail line") : (lang === "es" ? "Línea a granel" : "Bulk line")}</p><h2 id="product-sheet-title">{product[lang]}</h2><p className="native-product-lead">{product[lang === "es" ? "description_es" : "description_en"] || (lang === "es" ? "Producto peruano seleccionado por su calidad, trazabilidad y origen." : "Peruvian product selected for its quality, traceability and origin.")}</p></div>
    </section></article></div>
  </dialog>;
  return <dialog ref={dialogRef} className="product-sheet-dialog" data-cms-managed role="dialog" aria-modal="true" aria-labelledby="product-sheet-title" onCancel={event => { event.preventDefault(); if (packagesOpen) setPackagesOpen(false); else if (calendarOpen) setCalendarOpen(false); else onClose(); }} onClose={onClose}>
    {product && <><button className="product-sheet-close" type="button" onClick={onClose} aria-label={lang === "es" ? "Cerrar ficha" : "Close product sheet"}><X size={22} /></button><a className="product-sheet-language" href={productPublicPath(product,lang === "es" ? "en" : "es")} lang={lang === "es" ? "en" : "es"} aria-label={lang === "es" ? "Ver este producto en inglés" : "View this product in Spanish"}>{lang === "es" ? "EN" : "ES"}</a><div ref={contentRef} className="product-sheet-dialog-inner">
      <article className={`native-product-sheet${(detail?.product.line ?? product.line) === "retail" ? " retail-product-sheet" : ""}`} aria-busy={loading}>
        <section className="native-product-hero">
          <div className="native-product-gallery"><button type="button" className={`native-product-zoom${imageZoomed ? " is-zoomed" : ""}`} aria-pressed={imageZoomed} aria-label={imageZoomed ? (lang === "es" ? "Reducir imagen" : "Zoom out") : (lang === "es" ? "Ampliar imagen" : "Zoom in")} onClick={() => setImageZoomed(value => !value)} onPointerMove={event => { if (!imageZoomed) return; const bounds=event.currentTarget.getBoundingClientRect(); event.currentTarget.style.setProperty("--zoom-x",`${Math.max(0,Math.min(100,(event.clientX-bounds.left)/bounds.width*100))}%`); event.currentTarget.style.setProperty("--zoom-y",`${Math.max(0,Math.min(100,(event.clientY-bounds.top)/bounds.height*100))}%`); }}><img src={imageZoomed ? (selectedPackage?.original_path || imageOriginals[activeImage] || detail?.product.image_original || retailImage || images[activeImage] || product.image) : (retailImage || images[activeImage] || product.image)} alt={imageAlts[activeImage]?.trim() || productImageAlt(product,lang)} width="1200" height="1200" decoding="async" /><span>{imageZoomed ? <ZoomOut size={18} /> : <ZoomIn size={18} />}{imageZoomed ? (lang === "es" ? "Reducir" : "Zoom out") : (lang === "es" ? "Ampliar" : "Zoom")}</span></button>{detail?.product.line !== "retail" && images.length > 1 && <div>{images.map((image,index) => <button type="button" className={index === activeImage ? "is-active" : ""} onClick={() => { setActiveImage(index); setImageZoomed(false); }} key={image}><img src={imageThumbnails[index] || image} alt="" loading="lazy" decoding="async" width="96" height="96" /></button>)}</div>}</div>
          <div className="native-product-copy">
            <div className="product-kicker-row"><p className="eyebrow">{(detail?.product.line ?? product.line) === "retail" ? (lang === "es" ? "Línea retail" : "Retail line") : (lang === "es" ? "Línea a granel" : "Bulk line")}</p>{(detail?.product.line ?? product.line) === "retail" && retailCategory && <span className="retail-product-category"><Leaf size={15} aria-hidden="true" />{retailCategory}</span>}</div>
            <h2 id="product-sheet-title">{detail?.product.name ?? product[lang]}</h2>
            <p className="native-product-lead">{detail?.product.short_description || product[lang === "es" ? "description_es" : "description_en"] || (lang === "es" ? "Producto peruano seleccionado por su calidad, trazabilidad y origen." : "Peruvian product selected for its quality, traceability and origin.")}</p>
            {detail?.product.description && detail.product.line !== "retail" && <div className="native-product-characteristics"><p className="native-product-description">{detail.product.description}</p></div>}
            {loading ? <div className="native-product-loading"><span />{lang === "es" ? "Cargando ficha..." : "Loading product sheet..."}</div> : detail ? <>
              {detail.product.line === "conventional" && <dl>{[[lang === "es" ? "Partida arancelaria" : "Tariff code",detail.product.tariff_code],[lang === "es" ? "Nombre científico" : "Scientific name",detail.product.scientific_name],[lang === "es" ? "Destinos principales" : "Main destinations",detail.product.destinations],[lang === "es" ? "Calibre" : "Caliber",detail.product.caliber]].map(([label,value]) => <div key={label}><dt>{label}</dt><dd>{value || consult}</dd></div>)}</dl>}
              {detail.product.line === "retail" && <section className="retail-order-box">
                <div><span>{lang === "es" ? "Presentación" : "Presentation"}</span><div className="retail-presentation-options">{retailPackages.map((item,index) => <button type="button" className={index === activePackage ? "is-active" : ""} aria-pressed={index === activePackage} onClick={() => { setActivePackage(index); setImageZoomed(false); }} key={`${item.weight_primary}-${index}`}>{item.weight_primary}</button>)}</div></div>
                {selectedPackage?.material && <p>{selectedPackage.material}</p>}
                <div className="retail-order-actions"><label><span>{lang === "es" ? "Cantidad" : "Quantity"}</span><input type="number" min="1" step="1" value={quoteQuantity} onChange={event => setQuoteQuantity(Math.max(1,Number.parseInt(event.target.value,10) || 1))} /></label><button className="retail-whatsapp-quote" type="button" onClick={requestRetailQuote}><WhatsappIcon size={19} />{lang === "es" ? "Solicitar cotización" : "Request a quote"}</button></div>
              </section>}
              <div className="native-product-share" aria-label={lang === "es" ? "Compartir ficha del producto" : "Share product sheet"}><span>{lang === "es" ? "Compartir ficha" : "Share product"}</span><a href={`https://wa.me/?text=${encodeURIComponent(`${shareTitle} — ${typeof window === "undefined" ? "" : shareUrl()}`)}`} target="_blank" rel="noreferrer" aria-label={lang === "es" ? "Compartir por WhatsApp" : "Share on WhatsApp"} title="WhatsApp"><WhatsappIcon size={19} /></a><a href={`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(typeof window === "undefined" ? "" : shareUrl())}`} target="_blank" rel="noreferrer" aria-label={lang === "es" ? "Compartir en Facebook" : "Share on Facebook"} title="Facebook"><FacebookIcon size={19} /></a><button type="button" onClick={shareToInstagram} aria-label={lang === "es" ? "Compartir en Instagram" : "Share on Instagram"} title="Instagram"><InstagramIcon size={19} /></button><button type="button" onClick={copyShareLink} aria-label={lang === "es" ? "Copiar enlace" : "Copy link"} title={lang === "es" ? "Copiar enlace" : "Copy link"}><Link2 size={19} /></button><small role="status" aria-live="polite">{copied ? (lang === "es" ? "Enlace copiado" : "Link copied") : ""}</small></div>
              {detail.product.line === "retail" && detail.product.description && <details className="retail-product-characteristics"><summary>{lang === "es" ? "Características del producto" : "Product features"}<ChevronDown size={18} aria-hidden="true" /></summary><p className="native-product-description">{detail.product.description}</p></details>}
              {detail.product.line === "conventional" && <div className="native-product-actions"><Link href={`${pagePath("contacto",lang)}?product=${encodeURIComponent(detail.product.name)}`} prefetch={false}>{lang === "es" ? "Solicitar cotización" : "Request a quote"}<ArrowRight size={17} /></Link>{detail.product.technical_sheet_path && <a href={detail.product.technical_sheet_path} target="_blank" rel="noreferrer">{lang === "es" ? "Ficha técnica" : "Technical sheet"}<ArrowUpRight size={16} /></a>}</div>}
            </> : <p className="native-product-error">{lang === "es" ? "No se pudo cargar la ficha." : "The product sheet could not be loaded."}</p>}
          </div>
        </section>
        {detail?.product.line === "conventional" && (detail.packages.length > 0 || Object.keys(detail.harvest).length > 0) && <section className="native-product-details">{detail.packages.length > 0 && <section className="native-product-section native-package-launch"><div><p>{lang === "es" ? "Presentaciones disponibles" : "Available presentations"}</p><h3>{lang === "es" ? "Empaques" : "Packaging"}</h3><span>{lang === "es" ? `${detail.packages.length} ${detail.packages.length === 1 ? "presentación disponible" : "presentaciones disponibles"} para este producto.` : `${detail.packages.length} ${detail.packages.length === 1 ? "presentation" : "presentations"} available for this product.`}</span></div><button type="button" onClick={() => setPackagesOpen(true)}><PackageIcon />{lang === "es" ? "Ver empaques" : "View packaging"}</button></section>}{Object.keys(detail.harvest).length > 0 && <section className="native-product-section native-harvest-launch"><div><p>{lang === "es" ? "Ciclo agrícola del producto" : "Product agricultural cycle"}</p><h3>{lang === "es" ? "Calendario agrícola" : "Agricultural calendar"}</h3><span>{lang === "es" ? "Consulta claramente los meses de siembra y cosecha." : "Clearly review planting and harvest months."}</span></div><button type="button" onClick={() => setCalendarOpen(true)}><CalendarCheck size={21} />{lang === "es" ? "Abrir calendario" : "Open calendar"}</button></section>}</section>}
      </article>
    </div>{packagesOpen && detail && <div className="package-modal" role="dialog" aria-modal="true" aria-label={lang === "es" ? "Empaques disponibles" : "Available packaging"} onMouseDown={event => { if (event.target === event.currentTarget) setPackagesOpen(false); }}><div className="package-modal-window"><button ref={packageCloseRef} className="package-modal-close" type="button" onClick={() => setPackagesOpen(false)} aria-label={lang === "es" ? "Cerrar empaques" : "Close packaging"}><X size={23} /></button><section className="package-showcase"><header><div><p>{lang === "es" ? "Presentaciones disponibles" : "Available presentations"}</p><h3>{lang === "es" ? "El empaque ideal para cada operación" : "The right packaging for every operation"}</h3><span>{lang === "es" ? "Formatos preparados para conservar la calidad del producto durante su almacenamiento y exportación." : "Formats designed to preserve product quality during storage and export."}</span></div></header><div className="package-showcase-grid">{detail.packages.slice(0,4).map((item,index) => <article key={`${item.weight_primary}-${index}`}><div className={`package-showcase-media${item.image_path ? " has-image" : ""}`}>{item.image_path ? <img src={item.image_path} alt={`${lang === "es" ? "Empaque" : "Package"} ${item.weight_primary}`} loading="lazy" decoding="async" /> : <div><PackageIcon /><small>{lang === "es" ? "Presentación" : "Presentation"}</small></div>}</div><div className="package-showcase-copy"><h4>{item.weight_primary}</h4>{item.weight_secondary && <strong>{item.weight_secondary}</strong>}<p>{item.material}</p></div></article>)}</div></section></div></div>}{calendarOpen && detail && <div className="harvest-modal" role="dialog" aria-modal="true" aria-label={lang === "es" ? "Calendario agrícola" : "Agricultural calendar"} onMouseDown={event => { if (event.target === event.currentTarget) setCalendarOpen(false); }}><div className="harvest-modal-window"><button ref={calendarCloseRef} className="harvest-modal-close" type="button" onClick={() => setCalendarOpen(false)} aria-label={lang === "es" ? "Cerrar calendario" : "Close calendar"}><X size={23} /></button><HarvestCalendar harvest={detail.harvest} lang={lang} /></div></div>}</>}
  </dialog>;
}

export function HomeProductCarousel({ lang, initialProducts = [] }: { lang: Lang; initialProducts?: CatalogProduct[] }) {
  const animationTimer = useRef<ReturnType<typeof setTimeout> | null>(null);
  const animationLocked = useRef(false);
  const pointerStart = useRef<number | null>(null);
  const [startIndex, setStartIndex] = useState(0);
  const [motion, setMotion] = useState<-1 | 1 | null>(null);
  const [visibleCount, setVisibleCount] = useState(4);
  const [carouselAnnouncement, setCarouselAnnouncement] = useState("");
  const [previewIndex, setPreviewIndex] = useState<number | null>(null);
  const { products: catalogProducts, failed, retry } = useCatalogProducts("conventional", true, initialProducts);
  const openPreview = (index: number) => { const item=catalogProducts?.[index]; if (item?.id) prefetchProductDetail(item.id,lang); if (item) showProductInUrl(item,lang); setPreviewIndex(index); };
  const warmPreview = (_index: number) => {};

  useEffect(() => {
    const syncLocation = () => {
      const slug = new URL(window.location.href).searchParams.get("producto");
      if (!slug || !catalogProducts) { setPreviewIndex(null); return; }
      const index = catalogProducts.findIndex(item => storedProductSlug(item,lang) === slug);
      setPreviewIndex(index >= 0 ? index : null);
    };
    window.addEventListener("popstate",syncLocation);
    return () => window.removeEventListener("popstate",syncLocation);
  }, [catalogProducts,lang]);

  useEffect(() => {
    const updateVisibleCount = () => setVisibleCount(window.innerWidth >= 1100 ? 4 : window.innerWidth >= 760 ? 3 : window.innerWidth >= 560 ? 2 : 1);
    updateVisibleCount();
    window.addEventListener("resize", updateVisibleCount, { passive: true });
    return () => {
      window.removeEventListener("resize", updateVisibleCount);
      if (animationTimer.current) clearTimeout(animationTimer.current);
    };
  }, []);

  useEffect(() => {
    if (startIndex >= (catalogProducts?.length ?? 0)) setStartIndex(0);
  }, [catalogProducts?.length, startIndex]);

  const move = (direction: -1 | 1) => {
    if (animationLocked.current || !catalogProducts?.length) return;
    const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    animationLocked.current = true;
    setMotion(direction);
    animationTimer.current = setTimeout(() => {
      const next = (startIndex + direction + catalogProducts.length) % catalogProducts.length;
      const last = (next + Math.min(visibleCount, catalogProducts.length) - 1) % catalogProducts.length;
      setStartIndex(next);
      const shown = last < next ? `${next + 1}, 1 ${lang === "es" ? "a" : "to"} ${last + 1}` : `${next + 1} ${lang === "es" ? "a" : "to"} ${last + 1}`;
      setCarouselAnnouncement(lang === "es" ? `Mostrando productos ${shown} de ${catalogProducts.length}` : `Showing products ${shown} of ${catalogProducts.length}`);
      setMotion(null);
      animationLocked.current = false;
    }, reducedMotion ? 40 : 700);
  };

  const visibleProducts = catalogProducts?.length ? Array.from({ length: visibleCount + 2 }, (_, offset) => {
    const index = (startIndex - 1 + offset + catalogProducts.length) % catalogProducts.length;
    return { product: catalogProducts[index], index, key: `${startIndex}-${offset}-${catalogProducts[index]?.id ?? catalogProducts[index]?.es}` };
  }) : [];
  return <section className="home-products" aria-labelledby="home-products-title">
    <div className="container">
      <div className="home-products-heading">
        <h2 id="home-products-title" className="home-products-title"><span />{lang === "es" ? "Nuestros productos" : "Our products"}</h2>
        <div className="home-products-tools">
          <a className="text-link" href={catalogPath("conventional",lang)}>{lang === "es" ? "Ver Línea a Granel" : "View Bulk Line"}<ArrowRight size={18} /></a>
        </div>
      </div>
      <nav className="home-category-links" aria-label={lang === "es" ? "Categorías de productos" : "Product categories"}>
        {(lang === "es" ? [["Legumbres", "pulses"], ["Granos y semillas", "grains"], ["Especias", "spices"]] : [["Pulses", "pulses"], ["Grains & seeds", "grains"], ["Spices", "spices"]]).map(([label, category]) => <a href={`${catalogPath("conventional",lang)}?categoria=${category}`} key={category}>{label}<ArrowUpRight size={15} /></a>)}
      </nav>
      {catalogProducts === null ? <CatalogLoadingState lang={lang} /> : failed && catalogProducts.length === 0 ? <CatalogErrorState lang={lang} retry={retry} /> : catalogProducts.length === 0 ? <div className="catalog-empty" data-cms-managed><span><PackageIcon /></span><h3>{lang === "es" ? "No hay productos publicados" : "No published products"}</h3></div> : <div className="product-carousel-shell">
        <button className="carousel-image-arrow carousel-image-arrow-previous" type="button" onClick={() => move(-1)} aria-label={lang === "es" ? "Ver productos anteriores" : "View previous products"}><ChevronLeft size={24} /></button>
        <div className="product-carousel-viewport" aria-label={lang === "es" ? "Catálogo circular de productos" : "Circular product catalogue"} onPointerDown={event => { pointerStart.current = event.clientX; }} onPointerUp={event => { if (pointerStart.current === null) return; const distance = event.clientX - pointerStart.current; pointerStart.current = null; if (Math.abs(distance) > 45) move(distance < 0 ? 1 : -1); }}>
        <p className="sr-only" role="status" aria-live="polite" aria-atomic="true">{carouselAnnouncement}</p>
        <div className={`product-carousel-track ${motion === 1 ? "is-moving-next" : motion === -1 ? "is-moving-previous" : ""}`} data-visible={visibleCount}>
          {visibleProducts.map(({ product, index, key }, offset) => {
            const active = motion === 1 ? offset >= 2 && offset <= visibleCount + 1 : motion === -1 ? offset <= visibleCount - 1 : offset >= 1 && offset <= visibleCount;
            const exiting = motion === 1 ? offset === 1 : motion === -1 ? offset === visibleCount : false;
            const incoming = motion === 1 ? offset === visibleCount + 1 : motion === -1 ? offset === 0 : false;
            const accessible = motion === 1 ? offset >= 2 && offset <= visibleCount : motion === -1 ? offset >= 1 && offset <= visibleCount - 1 : active;
            return <article className={`home-product-card ${active ? "is-active" : ""} ${exiting ? "is-exiting" : ""} ${incoming ? "is-incoming" : ""}`} data-product-id={product.id} key={key} aria-hidden={!accessible || undefined} inert={!accessible}>
              {(active || exiting || incoming) && <>
                <a href={productPublicPath(product,lang)} data-product-preview-link onClick={event => { event.preventDefault(); openPreview(index); }} tabIndex={accessible ? undefined : -1}><img src={product.image} srcSet={product.image_320 && product.image_640 ? `${product.image_320} 320w, ${product.image_640} 640w` : undefined} sizes="(max-width: 640px) 85vw, 360px" alt={productImageAlt(product,lang)} loading="lazy" decoding="async" fetchPriority="low" width="720" height="460" /></a>
                <h3>{product[lang]}</h3>
                <div className="home-product-actions">
                  <a className="button button-forest" href={productPublicPath(product,lang)} data-product-preview-link onPointerEnter={() => warmPreview(index)} onFocus={() => warmPreview(index)} onClick={event => { event.preventDefault(); openPreview(index); }} tabIndex={accessible ? undefined : -1}>{lang === "es" ? "Ver producto" : "View product"}</a>
                </div>
              </>}
          </article>;})}
        </div>
        </div>
        <button className="carousel-image-arrow carousel-image-arrow-next" type="button" onClick={() => move(1)} aria-label={lang === "es" ? "Ver productos siguientes" : "View next products"}><ChevronRight size={24} /></button>
      </div>}
    </div>
    <ProductSheetDialog product={previewIndex === null ? null : catalogProducts?.[previewIndex] ?? null} lang={lang} onClose={() => { removeProductFromUrl(pagePath("inicio",lang)); setPreviewIndex(null); }} />
  </section>;
}

export function EventParticipationArchive({ lang }: { lang: Lang }) {
  const dialogRef = useRef<HTMLDialogElement>(null);
  const contentRefreshLanguage = useRef<Lang | null>(null);
  const [selectedId, setSelectedId] = useState<number | null>(null);
  const [activeIndex, setActiveIndex] = useState(0);
  const managedEntries = usePublicCollections("presentation", lang);
  const entryPhotos = (entry: CmsCollection) => Array.from(new Set([entry.cover_path, ...entry.media.map(media => media.path)].filter(Boolean)));
  const managedPresence = managedEntries?.filter(entry => entry.entry_type === "presence") ?? null;
  const entries = managedPresence?.filter(entry => entryPhotos(entry).length) ?? [];
  const selectedEntry = entries.find(entry => entry.id === selectedId) ?? null;
  const selectedPhotos = selectedEntry ? entryPhotos(selectedEntry) : [];
  const allPhotos = entries.flatMap(entry => entryPhotos(entry).map(src => ({ src, entry })));

  useEffect(() => {
    if (!entries.length || contentRefreshLanguage.current === lang) return;
    contentRefreshLanguage.current = lang;
    window.dispatchEvent(new CustomEvent("royalbeans:content-change", { detail: { scope: "content" } }));
  }, [entries.length, lang]);

  useEffect(() => {
    const dialog = dialogRef.current;
    if (!dialog) return;
    if (!selectedEntry) { if (dialog.open) dialog.close(); return; }
    if (!dialog.open) dialog.showModal();
    const previousOverflow = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    return () => { document.body.style.overflow = previousOverflow; };
  }, [selectedEntry]);

  const openGallery = (entry: CmsCollection) => { setActiveIndex(0); setSelectedId(entry.id); };
  const closeGallery = () => setSelectedId(null);
  const move = (direction: -1 | 1) => setActiveIndex(current => (current + direction + selectedPhotos.length) % selectedPhotos.length);

  if (!entries.length) return null;

  return <>
    <section className="event-history section-pad" data-cms-managed aria-labelledby="event-history-title"><div className="container"><div className="event-section-heading"><div><p className="eyebrow" data-cms="presence-heading.kicker"><span />{lang === "es" ? "Nuestra trayectoria" : "Our journey"}</p><h2 id="event-history-title" data-cms="presence-heading.title">{lang === "es" ? "Próximos eventos y presencia" : "Upcoming events and presence"}</h2></div><p data-cms="presence-heading.note">{lang === "es" ? "Cada encuentro nos impulsa" : "Every meeting moves us forward"}</p></div><div className="event-history-grid">{entries.map(entry => { const photos=entryPhotos(entry); const year=entry.event_date?.slice(0,4); return <article key={entry.id}><button className="event-history-cover" type="button" onClick={() => openGallery(entry)} aria-label={`${lang === "es" ? "Abrir galería de" : "Open gallery for"} ${entry.title}`}><img src={photos[0]} alt={entry.title} loading="lazy" />{year && <span className="event-year">{year}</span>}</button><div><div className="event-card-heading"><h3>{entry.title}</h3>{entry.logo_path && <img className="event-card-logo" src={entry.logo_path} alt={entry.title} />}</div>{entry.location && <small>{entry.location}</small>}<p>{entry.description}</p><button className="event-gallery-link" type="button" onClick={() => openGallery(entry)}>{lang === "es" ? "Ver galería" : "View gallery"}<ArrowRight size={16} /></button></div></article>; })}</div></div></section>
    <section className="event-gallery-section section-pad" data-cms-managed aria-labelledby="event-gallery-title"><div className="container"><div className="event-section-heading"><div><p className="eyebrow" data-cms="presence-gallery.kicker"><span />{lang === "es" ? "Momentos que nos inspiran" : "Moments that inspire us"}</p><h2 id="event-gallery-title" data-cms="presence-gallery.title">{lang === "es" ? "Galería" : "Gallery"}</h2></div><p data-cms="presence-gallery.note">{lang === "es" ? "Personas, cultivos y oportunidades" : "People, crops and opportunities"}</p></div></div><div className="event-photo-viewport" role="region" aria-label={lang === "es" ? "Galería de participación" : "Participation gallery"}><div className="event-photo-track">{[false,true].map(duplicate => <div className="event-photo-sequence" aria-hidden={duplicate || undefined} key={duplicate ? "duplicate" : "original"}>{allPhotos.map((photo,index) => <figure key={`${duplicate ? "duplicate" : "original"}-${photo.entry.id}-${photo.src}`}><button type="button" tabIndex={duplicate ? -1 : 0} onClick={() => { if (!duplicate) { setSelectedId(photo.entry.id); setActiveIndex(entryPhotos(photo.entry).indexOf(photo.src)); } }} aria-label={`${lang === "es" ? "Ampliar fotografía" : "Enlarge photo"} ${index + 1}: ${photo.entry.title}`}><img src={photo.src} alt={duplicate ? "" : photo.entry.title} loading="eager" decoding="async" fetchPriority="low" /></button><figcaption>{photo.entry.title}</figcaption></figure>)}</div>)}</div></div></section>
    <dialog ref={dialogRef} className="event-gallery-dialog" onClick={event => { if (event.target === event.currentTarget) closeGallery(); }} onKeyDown={event => { if (event.key === "Escape") { event.preventDefault(); closeGallery(); } else if (event.key === "ArrowLeft") move(-1); else if (event.key === "ArrowRight") move(1); }} onCancel={event => { event.preventDefault(); closeGallery(); }} onClose={closeGallery} aria-labelledby="event-gallery-dialog-title">{selectedEntry && <div className="event-gallery-dialog-inner"><header>{selectedEntry.logo_path && <img src={selectedEntry.logo_path} alt="" />}<div><p>{lang === "es" ? "Galería oficial" : "Official gallery"}</p><h2 id="event-gallery-dialog-title">{selectedEntry.title}</h2></div><button className="event-dialog-close" type="button" onClick={closeGallery} aria-label={lang === "es" ? "Cerrar galería" : "Close gallery"}><X size={23} /></button></header><div className="event-dialog-stage"><button type="button" onClick={() => move(-1)} aria-label={lang === "es" ? "Fotografía anterior" : "Previous photo"}><ChevronLeft size={26} /></button><img src={selectedPhotos[activeIndex]} alt={`${selectedEntry.title}: ${activeIndex + 1}`} /><button type="button" onClick={() => move(1)} aria-label={lang === "es" ? "Fotografía siguiente" : "Next photo"}><ChevronRight size={26} /></button></div><footer><span>{String(activeIndex + 1).padStart(2,"0")} / {String(selectedPhotos.length).padStart(2,"0")}</span><div>{selectedPhotos.map((src,index) => <button type="button" className={index === activeIndex ? "is-active" : ""} onClick={() => setActiveIndex(index)} aria-label={lang === "es" ? `Ver fotografía ${index + 1}` : `View photo ${index + 1}`} aria-current={index === activeIndex ? "true" : undefined} key={src}><img src={src} alt="" /></button>)}</div></footer></div>}</dialog>
  </>;
}

export function UpcomingEvent({ lang }: { lang: Lang }) {
  const managedEntries = usePublicCollections("presentation", lang);
  const event = managedEntries?.find(entry => entry.entry_type === "event");
  if (!event) return null;
  const dateLabel = event.description || (event.event_date ? new Intl.DateTimeFormat(lang === "es" ? "es-PE" : "en-US", { day: "numeric", month: "long", year: "numeric", timeZone: "UTC" }).format(new Date(`${event.event_date}T00:00:00Z`)) : "");

  return <section className="event-upcoming section-pad" data-cms-managed aria-labelledby="upcoming-event-title"><div className="container"><div className="event-section-heading"><div><p className="eyebrow" data-cms="upcoming-heading.kicker"><span />{lang === "es" ? "Nuestros próximos pasos" : "Our next steps"}</p><h2 id="upcoming-event-title" data-cms="upcoming-heading.title">{lang === "es" ? "Próximo evento" : "Next event"}</h2></div><p data-cms="upcoming-heading.note">{lang === "es" ? "El talento del Perú en el mundo" : "Peruvian talent around the world"}</p></div><article className="upcoming-event-card"><div className="upcoming-event-mark">{event.logo_path && <img src={event.logo_path} alt={event.title} />}</div><div className="upcoming-event-info"><h3>{event.title}</h3>{event.location && <p><MapPin size={16} />{event.location}</p>}{dateLabel && <p><CalendarCheck size={16} />{dateLabel}</p>}<Link className="button button-forest" data-cms="upcoming-heading.action" href={pagePath("contacto",lang)} prefetch={false}>{lang === "es" ? "Coordinar reunión" : "Schedule a meeting"}<ArrowRight size={17} /></Link></div>{event.cover_path && <img src={event.cover_path} alt={event.title} loading="lazy" />}</article></div></section>;
}

export function ImpactGallery({ lang }: { lang: Lang }) {
  const dialogRef = useRef<HTMLDialogElement>(null);
  const [activeIndex, setActiveIndex] = useState<number | null>(null);
  const collections = usePublicCollections("impact", lang);
  const managedPhotos = collections?.flatMap(item => [item.cover_path, ...item.media.map(media => media.path)]).filter(Boolean) ?? null;
  const photos = managedPhotos === null ? [] : Array.from(new Set(managedPhotos));

  useEffect(() => {
    const dialog = dialogRef.current;
    if (!dialog) return;
    if (activeIndex === null) { if (dialog.open) dialog.close(); return; }
    if (!dialog.open) dialog.showModal();
    const previousOverflow = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    return () => { document.body.style.overflow = previousOverflow; };
  }, [activeIndex]);

  const closeGallery = () => setActiveIndex(null);
  const move = (direction: -1 | 1) => setActiveIndex(current => current === null ? 0 : (current + direction + photos.length) % photos.length);

  if (!photos.length) return null;

  return <>
    <section className="impact-gallery section-pad" data-cms-managed aria-labelledby="impact-gallery-title">
      <div className="container impact-section-heading"><div><p data-cms="impact-gallery.kicker">{lang === "es" ? "Personas y oportunidades" : "People and opportunities"}</p><h2 id="impact-gallery-title" data-cms="impact-gallery.title">{lang === "es" ? "Nuestro impacto en imágenes" : "Our impact in pictures"}</h2></div><span data-cms="impact-gallery.note">{lang === "es" ? "Historias que se construyen juntas." : "Stories built together."}</span></div>
      <div className="event-photo-viewport impact-photo-viewport" role="region" aria-label={lang === "es" ? "Galería automática de impacto" : "Automatic impact gallery"}><div className="event-photo-track">{[false,true].map(duplicate => <div className="event-photo-sequence" aria-hidden={duplicate || undefined} key={duplicate ? "duplicate" : "original"}>{photos.map((src,index) => <figure key={`${duplicate ? "duplicate" : "original"}-${src}`}><button type="button" tabIndex={duplicate ? -1 : 0} onClick={() => { if (!duplicate) setActiveIndex(index); }} aria-label={lang === "es" ? `Ampliar fotografía ${index + 1}` : `Enlarge photo ${index + 1}`}><img src={src} alt={duplicate ? "" : (lang === "es" ? `Historia de impacto ${index + 1}` : `Impact story ${index + 1}`)} loading="lazy" decoding="async" /></button></figure>)}</div>)}</div></div>
    </section>
    <dialog ref={dialogRef} className="event-gallery-dialog impact-gallery-dialog" onClick={event => { if (event.target === event.currentTarget) closeGallery(); }} onKeyDown={event => { if (event.key === "Escape") { event.preventDefault(); closeGallery(); } else if (event.key === "ArrowLeft") move(-1); else if (event.key === "ArrowRight") move(1); }} onCancel={event => { event.preventDefault(); closeGallery(); }} onClose={closeGallery} aria-labelledby="impact-gallery-dialog-title">{activeIndex !== null && <div className="event-gallery-dialog-inner"><header><div><p>{lang === "es" ? "Galería de impacto" : "Impact gallery"}</p><h2 id="impact-gallery-dialog-title">{lang === "es" ? "Oportunidades que transforman" : "Opportunities that transform"}</h2></div><button className="event-dialog-close" type="button" onClick={closeGallery} aria-label={lang === "es" ? "Cerrar galería" : "Close gallery"}><X size={23} /></button></header><div className="event-dialog-stage"><button type="button" onClick={() => move(-1)} aria-label={lang === "es" ? "Fotografía anterior" : "Previous photo"}><ChevronLeft size={26} /></button><img src={photos[activeIndex]} alt={lang === "es" ? `Fotografía ${activeIndex + 1} de impacto` : `Impact photo ${activeIndex + 1}`} /><button type="button" onClick={() => move(1)} aria-label={lang === "es" ? "Fotografía siguiente" : "Next photo"}><ChevronRight size={26} /></button></div><footer><span>{String(activeIndex + 1).padStart(2,"0")} / {String(photos.length).padStart(2,"0")}</span><div>{photos.map((src,index) => <button type="button" className={index === activeIndex ? "is-active" : ""} onClick={() => setActiveIndex(index)} aria-label={lang === "es" ? `Ver fotografía ${index + 1}` : `View photo ${index + 1}`} aria-current={index === activeIndex ? "true" : undefined} key={src}><img src={src} alt="" /></button>)}</div></footer></div>}</dialog>
  </>;
}
export function ContactForm({ lang }: { lang: Lang }) {
  const [message, setMessage] = useState(""); const [status, setStatus] = useState<"saved" | "error" | "select_product" | null>(null); const [loading, setLoading] = useState(false);
  const [whatsappMessage, setWhatsappMessage] = useState("");
  const submitLockRef = useRef(false);
  const formStartedRef = useRef(false);
  const [line, setLine] = useState<"conventional" | "retail">("conventional");
  const [selectedProducts, setSelectedProducts] = useState<string[]>([]);
  const conventionalCatalog = useCatalogProducts("conventional");
  const retailCatalog = useCatalogProducts("retail");
  const conventionalProducts = conventionalCatalog.products;
  const retailProducts = retailCatalog.products;
  const availableProducts = line === "conventional" ? conventionalProducts : retailProducts;
  const availableProductsFailed = line === "conventional" ? conventionalCatalog.failed : retailCatalog.failed;
  const retryProducts = line === "conventional" ? conventionalCatalog.retry : retailCatalog.retry;
  const categoryLabels: Record<string, string> = lang === "es" ? { pulses: "Legumbres", grains: "Granos y semillas", corn: "Maíces", spices: "Especias" } : { pulses: "Pulses", grains: "Grains & seeds", corn: "Corn", spices: "Spices" };
  const productGroups = Object.entries((availableProducts ?? []).reduce<Record<string, CatalogProduct[]>>((groups, product) => { (groups[product.category] ||= []).push(product); return groups; }, {}));
  const channels = useContactChannels(lang);
  const whatsapp = channels?.find(channel => channel.channel_type === "whatsapp");
  const whatsappHref = whatsapp?.link_url || (whatsapp?.value_text ? `https://wa.me/${whatsapp.value_text.replace(/\D/g, "")}` : "");
  useEffect(() => {
    const requested = new URLSearchParams(window.location.search).get("product");
    const product = [...(conventionalProducts ?? []), ...(retailProducts ?? [])].find(item => item.es === requested || item.en === requested);
    if (requested) {
      setMessage(`${lang === "es" ? "Me interesa" : "I am interested in"} ${product?.[lang] ?? requested}. `);
      setSelectedProducts([product?.[lang] ?? requested]);
      if (product?.line === "retail") setLine("retail");
    }
  }, [lang, conventionalProducts, retailProducts]);
  const toggleProduct = (name: string) => setSelectedProducts(current => current.includes(name) ? current.filter(item => item !== name) : [...current, name]);
  const submit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (submitLockRef.current) return;
    if (!selectedProducts.length) { setStatus("select_product"); return; }
    const form = new FormData(event.currentTarget);
    const details = {
      participant_type: form.get("participant_type"), name: form.get("name"), company: form.get("company"),
      position: form.get("position"), country: form.get("country"), email: form.get("email"), phone: form.get("phone"),
      product_line: line, products: selectedProducts, volume: form.get("volume"),
      presentation: form.get("presentation"), destination: form.get("destination"), message, locale: lang,
      website: form.get("website"),
    };
    const text = `${lang === "es" ? "Hola, solicito una cotización para" : "Hello, I would like a quote for"} ${selectedProducts.join(", ")}.\n${lang === "es" ? "Empresa" : "Company"}: ${form.get("company")}\n${lang === "es" ? "País" : "Country"}: ${form.get("country")}\n${lang === "es" ? "Volumen" : "Volume"}: ${form.get("volume")}\n${lang === "es" ? "Presentación" : "Presentation"}: ${form.get("presentation") || "—"}\n${lang === "es" ? "Destino" : "Destination"}: ${form.get("destination")}\n${message}`;
    submitLockRef.current = true;
    setLoading(true); setStatus(null);
    try {
      const response = await fetch("/api/inquiries.php", { method: "POST", headers: { "Content-Type": "application/json", Accept: "application/json" }, body: JSON.stringify(details) });
      if (!response.ok) throw new Error("Unable to save enquiry");
      setWhatsappMessage(text);
      setStatus("saved");
      trackConversion("quote_submit", { location: window.location.pathname, line });
    } catch {
      setStatus("error");
      trackConversion("quote_error", { location: window.location.pathname, line });
    } finally {
      submitLockRef.current = false;
      setLoading(false);
    }
  };
  return <form className="contact-form" data-cms-managed onSubmit={submit} onFocusCapture={() => { if (!formStartedRef.current) { formStartedRef.current = true; trackConversion("quote_start", { location: window.location.pathname }); } }} aria-busy={loading}>
    <div className="honeypot" hidden aria-hidden="true" inert><input type="hidden" name="website" tabIndex={-1} autoComplete="off" aria-hidden="true" /></div>
    <h3 className="full-field" data-cms="contact-form.title">{lang === "es" ? "Cultivemos una nueva conexión." : "Let’s grow a new connection."}</h3>
    <label className="contact-choice full-field"><span data-cms="contact-form.participant-label">{lang === "es" ? "Tipo de empresa" : "Business type"} *</span><span className="contact-select-control"><select name="participant_type" required defaultValue="importer"><option value="importer">{lang === "es" ? "Importadora" : "Importer"}</option><option value="distributor">{lang === "es" ? "Distribuidora" : "Distributor"}</option><option value="public_institution">{lang === "es" ? "Institución pública" : "Public institution"}</option><option value="other">{lang === "es" ? "Otra" : "Other"}</option></select><ChevronDown size={20} aria-hidden="true" /></span></label>
    <label><span data-cms="contact-form.name-label">{lang === "es" ? "Nombres" : "Full name"} *</span><input name="name" autoComplete="name" required maxLength={120} placeholder={lang === "es" ? "Tu nombre completo" : "Your full name"} /></label>
    <label><span data-cms="contact-form.company-label">{lang === "es" ? "Empresa" : "Company"} *</span><input name="company" autoComplete="organization" required maxLength={180} placeholder={lang === "es" ? "Tu empresa" : "Your company"} /></label>
    <label><span>{lang === "es" ? "Cargo" : "Job title"}</span><input name="position" autoComplete="organization-title" maxLength={120} placeholder={lang === "es" ? "Tu cargo" : "Your role"} /></label>
    <label><span data-cms="contact-form.country-label">{lang === "es" ? "País" : "Country"} *</span><input name="country" autoComplete="country-name" required maxLength={100} placeholder={lang === "es" ? "País de origen" : "Country of origin"} /></label>
    <label><span data-cms="contact-form.email-label">{lang === "es" ? "Correo electrónico" : "Email"} *</span><input name="email" type="email" autoComplete="email" required maxLength={190} placeholder={lang === "es" ? "correo@empresa.com" : "name@company.com"} /></label>
    <label><span data-cms="contact-form.phone-label">{lang === "es" ? "Teléfono / WhatsApp" : "Phone / WhatsApp"} *</span><input name="phone" type="tel" autoComplete="tel" required minLength={7} maxLength={80} placeholder="+51 999 999 999" /></label>
    <label className="contact-choice full-field"><span data-cms="contact-form.line-label">{lang === "es" ? "Línea de productos" : "Product line"} *</span><span className="contact-select-control"><select name="product_line" value={line} onChange={event => { setLine(event.target.value as "conventional" | "retail"); setSelectedProducts([]); }}><option value="conventional">{lang === "es" ? "Línea a Granel" : "Bulk Line"}</option><option value="retail">{lang === "es" ? "Línea Retail" : "Retail Line"}</option></select><ChevronDown size={20} aria-hidden="true" /></span></label>
    <fieldset className="contact-products full-field"><legend data-cms="contact-form.products-label">{lang === "es" ? "Productos de interés" : "Products of interest"} *</legend>{availableProducts === null ? <div className="contact-products-loading" aria-label={lang === "es" ? "Cargando productos" : "Loading products"}>{[0,1,2].map(index => <i aria-hidden="true" key={index} />)}</div> : availableProductsFailed ? <div className="contact-products-error"><p>{lang === "es" ? "No pudimos cargar esta línea." : "We could not load this line."}</p><button type="button" onClick={retryProducts}>{lang === "es" ? "Reintentar" : "Try again"}</button></div> : availableProducts.length ? <div className="contact-product-groups">{productGroups.map(([category, categoryProducts]) => <section key={category}><h4>{categoryLabels[category] ?? category}</h4><div>{categoryProducts.map(product => { const name=product[lang]; return <label key={product.id ?? `${line}-${name}`}><input type="checkbox" checked={selectedProducts.includes(name)} onChange={() => { toggleProduct(name); setStatus(null); }} /><span>{name}</span></label>; })}</div></section>)}</div> : <p>{lang === "es" ? "No hay productos publicados en esta línea." : "There are no published products in this line."}</p>}</fieldset>
    <label><span>{lang === "es" ? "Volumen aproximado" : "Approximate volume"} *</span><input name="volume" required maxLength={120} placeholder={lang === "es" ? "Ej.: 20 toneladas" : "E.g. 20 tonnes"} /></label>
    <label><span>{lang === "es" ? "Presentación" : "Presentation"}</span><input name="presentation" maxLength={120} placeholder={lang === "es" ? "Ej.: sacos de 25 kg" : "E.g. 25 kg bags"} /></label>
    <label className="full-field"><span>{lang === "es" ? "País o puerto destino" : "Destination country or port"} *</span><input name="destination" required maxLength={180} placeholder={lang === "es" ? "Ej.: España, puerto de Valencia" : "E.g. Spain, Port of Valencia"} /></label>
    <label className="contact-message full-field"><span data-cms="contact-form.message-label">{lang === "es" ? "Cuéntanos qué necesitas" : "Tell us what you need"} *</span><textarea name="message" required rows={4} maxLength={2000} value={message} onChange={e => { setMessage(e.target.value); setStatus(null); }} placeholder={lang === "es" ? "Ej.: Frijol canario, 20 toneladas, destino España" : "E.g. Canary beans, 20 tonnes, destination Spain"} /><small data-cms="contact-form.message-help">{lang === "es" ? "Incluye producto, volumen aproximado y país de destino." : "Include product, approximate volume and destination country."}</small></label>
    <p className="form-note full-field" data-cms="contact-form.note">{lang === "es" ? "Tu consulta quedará registrada para que nuestro equipo pueda responderte." : "Your enquiry will be saved so our team can respond."}</p>
    <button type="submit" className="button button-lime full-field" data-cms="contact-form.action" disabled={loading}>{loading ? <span className="loading-ring" aria-hidden="true" /> : <Send size={17} />}{lang === "es" ? "Enviar consulta" : "Send enquiry"}</button>
    {status && <p role="status" className="full-field form-note">{status === "saved" ? (lang === "es" ? "Consulta registrada. Nuestro equipo podrá responderte." : "Enquiry received. Our team will be able to respond.") : status === "select_product" ? (lang === "es" ? "Selecciona al menos un producto de interés." : "Select at least one product of interest.") : (lang === "es" ? "No pudimos registrar la consulta. Inténtalo nuevamente." : "We could not save your enquiry. Please try again.")}</p>}
    {status === "saved" && whatsappHref && <a className="button button-forest full-field" href={`${whatsappHref.split("?")[0]}?text=${encodeURIComponent(whatsappMessage)}`} target="_blank" rel="noreferrer"><WhatsappIcon size={18} />{lang === "es" ? "Continuar por WhatsApp" : "Continue on WhatsApp"}</a>}
  </form>;
}
export function TextLink({ href, children }: { href: string; children: React.ReactNode }) { return <Link className="text-link" href={href} prefetch={false}>{children}<ArrowRight size={17} /></Link>; }
