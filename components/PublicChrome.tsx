"use client";

import { usePathname } from "next/navigation";
import { useEffect, useRef, useState } from "react";
import { ConversionTracker, Header, WhatsappMascot } from "./InteractiveShell";
import SharedFooter from "./SharedFooter";
import { pages, pageFromSlug, pagePath, productLinePath, type Lang, type PageKey } from "./routes";

function PublicContentSync() {
  const revisionRef = useRef("");
  useEffect(() => {
    let active = true;
    let timer = 0;
    const notify = (detail: { revision?: string; scope?: string }) => window.dispatchEvent(new CustomEvent("royalbeans:content-change", { detail }));
    const check = async () => {
      if (!active || document.hidden || !navigator.onLine) return;
      try {
        const response = await fetch(`/api/content-version.php?t=${Date.now()}`, { cache: "no-store", headers: { Accept: "application/json" } });
        if (!response.ok) return;
        const payload = await response.json();
        const revision = String(payload.revision || "");
        if (!revision) return;
        if (revisionRef.current && revisionRef.current !== revision) notify(payload);
        revisionRef.current = revision;
      } catch {}
    };
    // BroadcastChannel, focus and storage events keep local/admin updates immediate.
    // The slower fallback avoids a permanent request every 2.5 seconds on public pages.
    const schedule = () => { window.clearInterval(timer); timer = window.setInterval(check, 30000); };
    const immediate = (event?: Event) => {
      if (event instanceof StorageEvent && event.key !== "royalbeans:content-version") return;
      void check();
    };
    let channel: BroadcastChannel | null = null;
    if ("BroadcastChannel" in window) {
      channel = new BroadcastChannel("royalbeans-content");
      channel.addEventListener("message", event => { notify(event.data || { scope: "all" }); void check(); });
    }
    window.addEventListener("focus", immediate);
    window.addEventListener("storage", immediate);
    document.addEventListener("visibilitychange", immediate);
    void check(); schedule();
    return () => {
      active = false; window.clearInterval(timer); channel?.close();
      window.removeEventListener("focus", immediate); window.removeEventListener("storage", immediate); document.removeEventListener("visibilitychange", immediate);
    };
  }, []);
  return null;
}

function routeState(pathname: string, lang: Lang): { page: PageKey; languagePaths?: { es: string; en: string } } {
  if (pathname.includes("/productos/linea-a-granel") || pathname.includes("/products/bulk-line")) {
    return { page: "productos", languagePaths: { es: productLinePath("conventional", "es"), en: productLinePath("conventional", "en") } };
  }
  if (pathname.includes("/productos/linea-retail") || pathname.includes("/products/retail")) {
    return { page: "productos", languagePaths: { es: productLinePath("retail", "es"), en: productLinePath("retail", "en") } };
  }
  const prefix = lang === "en" ? "/en/" : "/";
  const slug = pathname.replace(prefix, "").split("/").filter(Boolean)[0] ?? "";
  return { page: pageFromSlug(slug, lang) ?? "inicio" };
}

function PublicRouteLoader({ pathname, lang }: { pathname: string; lang: Lang }) {
  const [loading, setLoading] = useState(false);
  const activeRef = useRef(false);
  const safetyTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  useEffect(() => {
    const startLoading = (event: MouseEvent) => {
      if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
      const link = (event.target as Element | null)?.closest<HTMLAnchorElement>("a[href]");
      if (!link || link.target === "_blank" || link.hasAttribute("download")) return;
      if (link.hasAttribute("data-product-preview-link")) return;
      const target = new URL(link.href, window.location.href);
      if (target.origin !== window.location.origin) return;
      const current = new URL(window.location.href);
      if (target.pathname === current.pathname && target.search === current.search) return;
      activeRef.current = true;
      setLoading(true);
      if (safetyTimerRef.current) clearTimeout(safetyTimerRef.current);
      safetyTimerRef.current = setTimeout(() => { activeRef.current = false; setLoading(false); }, 10000);
    };
    document.addEventListener("click", startLoading, true);
    return () => document.removeEventListener("click", startLoading, true);
  }, []);

  useEffect(() => {
    if (!activeRef.current) return;
    activeRef.current = false;
    setLoading(false);
    if (safetyTimerRef.current) clearTimeout(safetyTimerRef.current);
  }, [pathname]);

  useEffect(() => () => {
    if (safetyTimerRef.current) clearTimeout(safetyTimerRef.current);
  }, []);

  return <div className={`public-route-loader${loading ? " is-active" : ""}`} role="status" aria-label={lang === "es" ? "Abriendo página" : "Opening page"} aria-hidden={!loading}>
    <div><img src="/images/logo.webp" alt="Royal Beans Perú" width="54" height="54" /><span aria-hidden="true" /></div>
  </div>;
}

export default function PublicChrome({ lang, children }: { lang: Lang; children: React.ReactNode }) {
  const pathname = usePathname();
  const route = routeState(pathname, lang);
  const nav = (Object.keys(pages) as PageKey[]).map(key => ({ href: pagePath(key, lang), label: pages[key].label[lang] }));

  return <>
    <PublicContentSync />
    <ConversionTracker />
    <Header nav={nav} lang={lang} page={route.page} languagePaths={route.languagePaths} />
    <div className="public-page-stage" data-cms-state="ready">{children}<PublicRouteLoader pathname={pathname} lang={lang} /></div>
    <SharedFooter lang={lang} />
    <WhatsappMascot lang={lang} />
  </>;
}
