"use client";
import { FormEvent, ReactNode, useEffect, useRef, useState } from "react";
import { ArrowRight, ArrowUpRight, ChevronLeft, ChevronRight, Menu, Play, Send, X, Globe2 } from "lucide-react";
import products from "./products.json";
import { pagePath, type Lang, type PageKey } from "./routes";
type NavItem = { href: string; label: string };
declare global { interface Window { dataLayer?: Array<Record<string, unknown>> } }

function trackConversion(event: string, details: Record<string, unknown> = {}) {
  const payload = { event, ...details };
  window.dataLayer = window.dataLayer || [];
  window.dataLayer.push(payload);
  window.dispatchEvent(new CustomEvent("royalbeans:conversion", { detail: payload }));
}

export function ConversionTracker() {
  useEffect(() => {
    const trackClick = (event: MouseEvent) => {
      const link = (event.target as Element | null)?.closest<HTMLAnchorElement>("a[href]");
      if (!link) return;
      const href = link.href;
      const label = link.textContent?.trim().replace(/\s+/g, " ").slice(0, 100) || link.getAttribute("aria-label") || "";
      if (href.includes("wa.me/")) trackConversion("whatsapp_click", { label, location: window.location.pathname });
      else if (href.includes("?product=")) trackConversion("product_enquiry", { product: new URL(href).searchParams.get("product"), location: window.location.pathname });
      else if (/\/contacto\/?$|\/en\/contact\/?$/.test(new URL(href).pathname)) trackConversion("contact_cta_click", { label, location: window.location.pathname });
    };
    document.addEventListener("click", trackClick);
    return () => document.removeEventListener("click", trackClick);
  }, []);
  return null;
}
export function Brand() { return <><span className="brand-mark"><img src="/images/logo.webp" alt="" width="52" height="60" /></span><span className="brand-copy"><strong>ROYAL BEANS</strong><small>- PERÚ -</small></span></>; }

export function AboutVideo({ lang }: { lang: Lang }) {
  const [playing, setPlaying] = useState(false);
  if (playing) return <iframe src="https://www.youtube-nocookie.com/embed/PaMhqpNjbPE?autoplay=1&rel=0" title="Video institucional de Royal Beans Perú" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowFullScreen />;
  return <button className="about-video-poster" type="button" onClick={() => setPlaying(true)} aria-label={lang === "es" ? "Reproducir video institucional" : "Play corporate video"}>
    <img src="/images/about-video-poster.jpg" alt="" width="1280" height="720" />
    <span><Play size={22} fill="currentColor" />{lang === "es" ? "Reproducir video" : "Play video"}</span>
  </button>;
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

export function InstagramIcon({ size = 22 }: { size?: number }) {
  return <svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5" /><circle cx="12" cy="12" r="4" /><circle cx="17.4" cy="6.6" r="1" fill="currentColor" stroke="none" /></svg>;
}

export function FacebookIcon({ size = 22 }: { size?: number }) {
  return <svg width={size} height={size} viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M13.65 21v-8.2h2.76l.41-3.2h-3.17V7.56c0-.93.26-1.56 1.59-1.56h1.7V3.14A22.7 22.7 0 0 0 14.47 3c-2.45 0-4.12 1.49-4.12 4.24V9.6H7.58v3.2h2.77V21h3.3Z" /></svg>;
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
export function Header({ nav, lang, page }: { nav: NavItem[]; lang: Lang; page: PageKey }) {
  const [open, setOpen] = useState(false);
  const [scrolled, setScrolled] = useState(false);
  const active = pagePath(page, lang);
  const menuRef = useRef<HTMLButtonElement>(null);
  const headerRef = useRef<HTMLElement>(null);
  useEffect(() => {
    const update = () => setScrolled(window.scrollY > 28);
    update(); window.addEventListener("scroll", update, { passive: true });
    const reveal = new IntersectionObserver(entries => { entries.forEach(entry => { if (entry.isIntersecting) { entry.target.classList.add("reveal-in"); reveal.unobserve(entry.target); } }); }, { threshold: 0.12 });
    if (!window.matchMedia("(prefers-reduced-motion: reduce)").matches) document.querySelectorAll(".section-heading,.presence-intro,.impact-copy,.image-reveal,.event-grid figure,.home-about-media,.product-card,.about-pillar-grid article,.trace-steps li,.standards-grid article").forEach(el => reveal.observe(el));
    return () => { window.removeEventListener("scroll", update); reveal.disconnect(); };
  }, []);
  useEffect(() => {
    const desktop = window.matchMedia("(min-width: 1200px)");
    const closeOnDesktop = () => { if (desktop.matches) setOpen(false); };
    desktop.addEventListener("change", closeOnDesktop);
    return () => desktop.removeEventListener("change", closeOnDesktop);
  }, []);
  useEffect(() => {
    if (!open) return;
    const previousOverflow = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    const background = Array.from(document.querySelectorAll<HTMLElement>("main, footer, .whatsapp-float"));
    const previousInert = background.map(element => element.inert);
    background.forEach(element => { element.inert = true; });
    headerRef.current?.querySelector<HTMLAnchorElement>("#mobile-menu a")?.focus();
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
      document.body.style.overflow = previousOverflow;
      background.forEach((element, index) => { element.inert = previousInert[index]; });
    };
  }, [open]);
  return <header ref={headerRef} className={`site-header ${page !== "inicio" ? "interior-header" : ""} ${scrolled || open ? "is-scrolled" : ""}`}>
    <a className="brand" href={pagePath("inicio", lang)} aria-label={lang === "es" ? "Royal Beans Perú — Inicio" : "Royal Beans Perú — Home"}><Brand /></a>
    <nav className="desktop-nav gooey-nav" aria-label={lang === "es" ? "Navegación principal" : "Main navigation"}>{nav.map((item, index) => <a key={item.href} aria-current={active === item.href ? "page" : undefined} className={`gooey-nav-item${index === nav.length - 1 ? " nav-contact" : ""}`} href={item.href}><span className="nav-label">{item.label}</span></a>)}</nav>
    <div className="header-actions"><Globe2 size={16} aria-hidden="true" /><div className="languages"><a href={pagePath(page, "es")} lang="es" aria-current={lang === "es" ? "page" : undefined}>ES</a><span>/</span><a href={pagePath(page, "en")} lang="en" aria-current={lang === "en" ? "page" : undefined}>EN</a></div><button ref={menuRef} className="menu-toggle" onClick={() => setOpen(!open)} aria-expanded={open} aria-controls="mobile-menu" aria-label={lang === "es" ? (open ? "Cerrar menú" : "Abrir menú") : (open ? "Close menu" : "Open menu")}>
      {open ? <X size={24} /> : <Menu size={24} />}</button></div>
    <nav id="mobile-menu" className="mobile-menu" hidden={!open} aria-label={lang === "es" ? "Navegación móvil" : "Mobile navigation"}>{nav.map(item => <a key={item.href} href={item.href} aria-current={active === item.href ? "page" : undefined} onClick={() => setOpen(false)}>{item.label}<ArrowUpRight size={18} /></a>)}</nav>
  </header>;
}
export function ProductCatalog({ lang }: { lang: Lang }) {
  const [category, setCategory] = useState("all"); const [expanded, setExpanded] = useState(true);
  const categories: Record<string, string> = lang === "es" ? { all: "Todos", pulses: "Legumbres", grains: "Granos y semillas", corn: "Maíces", spices: "Especias" } : { all: "All products", pulses: "Pulses", grains: "Grains & seeds", corn: "Corn", spices: "Spices" };
  const filtered = products.filter(p => category === "all" || p.category === category);
  const contactHref = (product: string) => `${pagePath("contacto", lang)}?product=${encodeURIComponent(product)}`;
  return <><div className="catalog-toolbar"><div className="product-filters" role="group" aria-label={lang === "es" ? "Filtrar productos" : "Filter products"}>{Object.entries(categories).map(([key, label]) => <button key={key} aria-pressed={category === key} onClick={() => { setCategory(key); setExpanded(true); }}>{label}</button>)}</div><span className="product-count" aria-live="polite">{String(filtered.length).padStart(2, "0")} {lang === "es" ? "productos" : "products"}</span></div>
    <div className={`product-grid ${expanded ? "is-expanded" : ""}`}>{filtered.map((product, index) => <article className="product-card" key={product.es}>
      <a href={contactHref(product.es)} className="product-image" aria-label={`${lang === "es" ? "Consultar" : "Enquire about"} ${product[lang]}`}><span className="product-index">{String(index + 1).padStart(2, "0")}</span><img src={product.image} alt={product[lang]} loading="lazy" width="720" height="720" /><span className="product-arrow"><ArrowUpRight size={20} /></span></a><div className="product-meta"><p>{categories[product.category]}</p><h3><a href={contactHref(product.es)}>{product[lang]}</a></h3></div>
    </article>)}</div>{filtered.length > 4 && <button className="catalog-more text-link" aria-expanded={expanded} onClick={() => setExpanded(!expanded)}>{expanded ? (lang === "es" ? "Ver menos" : "Show less") : (lang === "es" ? `Explorar los ${filtered.length} productos` : `Explore all ${filtered.length} products`)}<ArrowRight size={18} /></button>}</>;
}

export function HomeProductCarousel({ lang }: { lang: Lang }) {
  const animationTimer = useRef<ReturnType<typeof setTimeout> | null>(null);
  const animationLocked = useRef(false);
  const pointerStart = useRef<number | null>(null);
  const [startIndex, setStartIndex] = useState(0);
  const [motion, setMotion] = useState<-1 | 1 | null>(null);
  const [visibleCount, setVisibleCount] = useState(3);
  const currentProduct = products[startIndex];

  useEffect(() => {
    const updateVisibleCount = () => setVisibleCount(window.innerWidth >= 900 ? 3 : window.innerWidth >= 600 ? 2 : 1);
    updateVisibleCount();
    window.addEventListener("resize", updateVisibleCount, { passive: true });
    return () => {
      window.removeEventListener("resize", updateVisibleCount);
      if (animationTimer.current) clearTimeout(animationTimer.current);
    };
  }, []);

  const move = (direction: -1 | 1) => {
    if (animationLocked.current) return;
    const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    animationLocked.current = true;
    setMotion(direction);
    animationTimer.current = setTimeout(() => {
      setStartIndex(current => (current + direction + products.length) % products.length);
      setMotion(null);
      animationLocked.current = false;
    }, reducedMotion ? 40 : 700);
  };

  const visibleProducts = Array.from({ length: visibleCount + 2 }, (_, offset) => {
    const index = (startIndex - 1 + offset + products.length) % products.length;
    return { product: products[index], key: `${startIndex}-${offset}` };
  });

  return <section className="home-products" aria-labelledby="home-products-title">
    <div className="container">
      <div className="home-products-heading">
        <div>
          <p className="eyebrow"><span />{lang === "es" ? "Nuestros productos" : "Our products"}</p>
          <h2 id="home-products-title">{lang === "es" ? "Una selección que habla de su origen." : "A selection shaped by its origin."}</h2>
        </div>
        <div className="carousel-controls" aria-label={lang === "es" ? "Controles del carrusel" : "Carousel controls"}>
          <button type="button" onClick={() => move(-1)} aria-label={lang === "es" ? "Ver productos anteriores" : "View previous products"}><ChevronLeft size={24} /></button>
          <button type="button" onClick={() => move(1)} aria-label={lang === "es" ? "Ver productos siguientes" : "View next products"}><ChevronRight size={24} /></button>
        </div>
      </div>
      <div className="product-carousel-viewport" aria-label={lang === "es" ? "Catálogo circular de productos" : "Circular product catalogue"} onPointerDown={event => { pointerStart.current = event.clientX; }} onPointerUp={event => { if (pointerStart.current === null) return; const distance = event.clientX - pointerStart.current; pointerStart.current = null; if (Math.abs(distance) > 45) move(distance < 0 ? 1 : -1); }}>
        <p className="sr-only" role="status" aria-live="polite" aria-atomic="true">{lang === "es" ? `Producto ${startIndex + 1} de ${products.length}: ${currentProduct.es}` : `Product ${startIndex + 1} of ${products.length}: ${currentProduct.en}`}</p>
        <div className={`product-carousel-track ${motion === 1 ? "is-moving-next" : motion === -1 ? "is-moving-previous" : ""}`} data-visible={visibleCount}>
          {visibleProducts.map(({ product, key }, offset) => {
            const active = motion === 1 ? offset >= 2 && offset <= visibleCount + 1 : motion === -1 ? offset <= visibleCount - 1 : offset >= 1 && offset <= visibleCount;
            const exiting = motion === 1 ? offset === 1 : motion === -1 ? offset === visibleCount : false;
            const incoming = motion === 1 ? offset === visibleCount + 1 : motion === -1 ? offset === 0 : false;
            return <article className={`home-product-card ${active ? "is-active" : ""} ${exiting ? "is-exiting" : ""} ${incoming ? "is-incoming" : ""}`} key={key} aria-hidden={!active || undefined}>
            <img src={product.image} alt={product[lang]} loading="lazy" decoding="async" fetchPriority="low" width="720" height="720" />
            <a className="home-product-panel" href={`${pagePath("contacto", lang)}?product=${encodeURIComponent(product.es)}`} tabIndex={active ? undefined : -1} aria-label={`${lang === "es" ? "Consultar" : "Enquire about"} ${product[lang]}`}>
              <strong>{product[lang]}</strong>
              <span aria-hidden="true"><ArrowUpRight size={17} /></span>
            </a>
          </article>;})}
        </div>
      </div>
      <div className="home-products-footer"><a className="text-link" href={pagePath("productos", lang)}>{lang === "es" ? "Ver catálogo completo" : "View full catalogue"}<ArrowRight size={18} /></a></div>
    </div>
  </section>;
}
export function ContactForm({ lang }: { lang: Lang }) {
  const [message, setMessage] = useState(""); const [status, setStatus] = useState(false); const [loading, setLoading] = useState(false);
  const timer = useRef<ReturnType<typeof setTimeout> | null>(null);
  useEffect(() => {
    const requested = new URLSearchParams(window.location.search).get("product");
    const product = products.find(p => p.es === requested || p.en === requested);
    if (product) setMessage(`${lang === "es" ? "Me interesa" : "I am interested in"} ${product[lang]}. `);
    return () => { if (timer.current) clearTimeout(timer.current); };
  }, [lang]);
  const submit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault(); const form = new FormData(event.currentTarget);
    const text = `${lang === "es" ? "Hola, soy" : "Hello, I am"} ${form.get("name")} (${form.get("company") || "—"}).\nEmail: ${form.get("email")}\n${lang === "es" ? "País" : "Country"}: ${form.get("country")}\n${message}`;
    setLoading(true); trackConversion("contact_form_submit", { channel: "whatsapp", location: window.location.pathname }); window.open(`https://wa.me/51961804500?text=${encodeURIComponent(text)}`, "_blank", "noopener,noreferrer"); setStatus(true); timer.current = setTimeout(() => setLoading(false), 400);
  };
  return <form className="contact-form" onSubmit={submit} aria-busy={loading}>
    <h3 className="full-field">{lang === "es" ? "Cultivemos una nueva conexión." : "Let’s grow a new connection."}</h3>
    <label><span>{lang === "es" ? "Nombre completo" : "Full name"} *</span><input name="name" autoComplete="name" required maxLength={120} placeholder={lang === "es" ? "Tu nombre" : "Your name"} /></label>
    <label><span>{lang === "es" ? "Empresa" : "Company"}</span><input name="company" autoComplete="organization" maxLength={160} placeholder={lang === "es" ? "Tu empresa" : "Your company"} /></label>
    <label><span>Email *</span><input name="email" type="email" autoComplete="email" required maxLength={200} placeholder={lang === "es" ? "nombre@empresa.com" : "name@company.com"} /></label>
    <label><span>{lang === "es" ? "País de destino" : "Destination country"} *</span><input name="country" autoComplete="country-name" required maxLength={100} placeholder={lang === "es" ? "¿A dónde llegaremos?" : "Where are we heading?"} /></label>
    <label className="full-field"><span>{lang === "es" ? "Cuéntanos qué necesitas" : "Tell us what you need"} *</span><textarea name="message" required rows={3} maxLength={2000} value={message} onChange={e => { setMessage(e.target.value); setStatus(false); }} placeholder={lang === "es" ? "Producto, volumen estimado y destino" : "Product, estimated volume and destination"} /></label>
    <p className="form-note full-field">{lang === "es" ? "Se abrirá WhatsApp con tu consulta preparada. Tú decides cuándo enviarla." : "WhatsApp will open with your enquiry ready. You choose when to send it."}</p>
    <button type="submit" className="button button-lime full-field" disabled={loading}>{loading ? <span className="loading-ring" aria-hidden="true" /> : <Send size={17} />}{lang === "es" ? "Conversar por WhatsApp" : "Chat on WhatsApp"}</button>
    {status && <p role="status" className="full-field form-note">{lang === "es" ? "Tu consulta está preparada. Si WhatsApp no se abrió, permite las ventanas emergentes o escríbenos al correo que aparece al lado." : "Your enquiry is ready. If WhatsApp did not open, allow pop-ups or email us using the address shown alongside."}</p>}
  </form>;
}
export function TextLink({ href, children }: { href: string; children: React.ReactNode }) { return <a className="text-link" href={href}>{children}<ArrowRight size={17} /></a>; }
