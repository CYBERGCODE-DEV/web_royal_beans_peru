"use client";

import { usePathname } from "next/navigation";
import { useEffect, useLayoutEffect, useRef, useState } from "react";
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

const curtainPaths = new Set([
  ...(["es", "en"] as const).flatMap(language => [
    ...(Object.keys(pages) as PageKey[]).map(page => pagePath(page, language)),
    productLinePath("conventional", language),
    productLinePath("retail", language),
  ]),
]);

function waitForImage(image: HTMLImageElement | null): Promise<void> {
  if (!image) return Promise.resolve();
  const decode = () => image.decode?.().then(() => undefined).catch(() => undefined) ?? Promise.resolve();
  if (image.complete) return decode();
  return new Promise(resolve => {
    image.addEventListener("load", () => { void decode().then(resolve); }, { once: true });
    image.addEventListener("error", () => resolve(), { once: true });
  });
}

function PublicRouteLoader({ pathname, lang }: { pathname: string; lang: Lang }) {
  const initialPhase = curtainPaths.has(pathname) ? "active" : "idle";
  const [phase, setPhase] = useState<"active" | "leaving" | "idle">(initialPhase);
  const phaseRef = useRef<"active" | "leaving" | "idle">(initialPhase);
  const targetPathRef = useRef(pathname);
  const safetyTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const exitTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const readyRef = useRef(0);
  const changePhase = (next: "active" | "leaving" | "idle") => { phaseRef.current = next; setPhase(next); };

  useLayoutEffect(() => {
    const isProductPreview = new URLSearchParams(window.location.search).has("producto") || new URLSearchParams(window.location.search).has("product");
    document.documentElement.toggleAttribute("data-product-preview", isProductPreview);
    if (targetPathRef.current !== pathname) {
      targetPathRef.current = pathname;
      if (!isProductPreview) changePhase(curtainPaths.has(pathname) ? "active" : "idle");
    }
    if (isProductPreview || !curtainPaths.has(pathname)) changePhase("idle");
    const clearTimers = () => {
      if (safetyTimerRef.current) clearTimeout(safetyTimerRef.current);
      if (exitTimerRef.current) clearTimeout(exitTimerRef.current);
    };
    const leave = () => {
      clearTimers();
      if (phaseRef.current !== "active") return;
      changePhase("leaving");
      exitTimerRef.current = setTimeout(() => changePhase("idle"), 680);
    };
    const waitForVisuals = async (request: number) => {
      const images = [
        document.querySelector<HTMLImageElement>(".public-page-stage [data-hero] img[data-cms='hero.image']"),
        document.querySelector<HTMLImageElement>(".shared-site-header .brand-mark img"),
        document.querySelector<HTMLImageElement>(".shared-site-footer .brand-mark img"),
        document.querySelector<HTMLImageElement>(".whatsapp-mascot img"),
      ];
      await Promise.all([
        document.fonts?.ready.catch(() => undefined),
        ...images.map(waitForImage),
      ]);
      if (request === readyRef.current) leave();
    };
    const cmsReady = () => {
      if (phaseRef.current !== "active" || window.location.pathname !== targetPathRef.current) return;
      void waitForVisuals(++readyRef.current);
    };
    window.addEventListener("royalbeans:cms-ready", cmsReady);
    const stage = document.querySelector<HTMLElement>(".public-page-stage");
    if (!isProductPreview && curtainPaths.has(pathname)) {
      if (stage?.dataset.cmsState === "ready" && (window as Window & { __ROYALBEANS_SERVER_CMS_PATH__?: string }).__ROYALBEANS_SERVER_CMS_PATH__ === pathname) cmsReady();
      safetyTimerRef.current = setTimeout(leave, 10000);
    }
    return () => { window.removeEventListener("royalbeans:cms-ready", cmsReady); clearTimers(); readyRef.current++; };
  }, [pathname]);

  useEffect(() => {
    const startLoading = (event: MouseEvent) => {
      if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
      const link = (event.target as Element | null)?.closest<HTMLAnchorElement>("a[href]");
      if (!link || link.target === "_blank" || link.hasAttribute("download")) return;
      if (link.hasAttribute("data-product-preview-link")) return;
      const target = new URL(link.href, window.location.href);
      if (target.origin !== window.location.origin) return;
      const current = new URL(window.location.href);
      if (target.pathname === current.pathname || !curtainPaths.has(target.pathname) || target.searchParams.has("producto") || target.searchParams.has("product")) return;
      document.documentElement.removeAttribute("data-product-preview");
      targetPathRef.current = target.pathname;
      readyRef.current++;
      if (exitTimerRef.current) clearTimeout(exitTimerRef.current);
      changePhase("active");
    };
    document.addEventListener("click", startLoading, true);
    return () => document.removeEventListener("click", startLoading, true);
  }, []);

  return <div className="public-route-loader" data-phase={phase} role="status" aria-label={lang === "es" ? "Abriendo página" : "Opening page"} aria-hidden={phase === "idle"}>
    <span className="public-route-loader-backdrop" aria-hidden="true" />
    <span className="public-route-loader-panel public-route-loader-panel-left" aria-hidden="true" />
    <span className="public-route-loader-panel public-route-loader-panel-right" aria-hidden="true" />
    <div className="public-route-loader-logo"><img src="/images/logo.webp" alt="" width="86" height="100" /></div>
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
    <div className="public-page-stage" data-cms-state="ready">{children}</div>
    <SharedFooter lang={lang} />
    <WhatsappMascot lang={lang} />
    <PublicRouteLoader pathname={pathname} lang={lang} />
  </>;
}
