import type { Metadata } from "next";
import { Bricolage_Grotesque, DM_Sans } from "next/font/google";
import { SITE_INDEXABLE, SITE_URL } from "./siteUrl";
import { ContentProtection } from "./InteractiveShell";
import "@/app/globals.css";
import "@/app/responsive.css";

const display = Bricolage_Grotesque({
  variable: "--font-display",
  subsets: ["latin"],
  display: "swap",
});

const body = DM_Sans({
  variable: "--font-body",
  subsets: ["latin"],
  display: "swap",
});

const scrollRestoreScript = `(()=>{try{const n=performance.getEntriesByType('navigation')[0];if(!n||!['reload','back_forward'].includes(n.type))return;const k='royalbeans:scroll:'+location.pathname+location.search;const y=Number(sessionStorage.getItem(k)||0);if(y<=0)return;if('scrollRestoration'in history)history.scrollRestoration='manual';const d=document.documentElement;d.setAttribute('data-scroll-restoring','');let stopped=false,t=0,stable=0;const finish=()=>{if(stopped)return;stopped=true;clearTimeout(t);d.removeAttribute('data-scroll-restoring')};const restore=()=>{if(stopped)return;const max=Math.max(0,d.scrollHeight-innerHeight);scrollTo(0,Math.min(y,max));stable=max>=y-2&&Math.abs(scrollY-y)<=2?stable+1:0;if(stable>=8){requestAnimationFrame(()=>requestAnimationFrame(finish));return}t=setTimeout(restore,60)};['wheel','touchstart','pointerdown','keydown'].forEach(e=>addEventListener(e,finish,{once:true,passive:true}));document.readyState==='loading'?addEventListener('DOMContentLoaded',restore,{once:true}):restore();addEventListener('load',restore,{once:true});setTimeout(finish,2200)}catch(e){document.documentElement.removeAttribute('data-scroll-restoring')}})()`;
const productPreviewScript = `(()=>{const q=new URLSearchParams(location.search);if(q.has('producto')||q.has('product'))document.documentElement.setAttribute('data-product-preview','')})()`;

export const metadata: Metadata = {
  metadataBase: new URL(SITE_URL),
  title: {
    default: "Royal Beans Perú | Granos, legumbres y especias",
    template: "%s | Royal Beans Perú",
  },
  description:
    "Empresa peruana dedicada al acopio, proceso, comercialización y exportación de granos andinos, legumbres, semillas y especias.",
  alternates: {
    canonical: "/",
    languages: { "es-PE": "/", "en": "/en/" },
  },
  openGraph: {
    title: "Royal Beans Perú",
    description: "Calidad peruana que conecta el campo con mercados globales.",
    type: "website",
    locale: "es_PE",
    alternateLocale: "en_US",
    siteName: "Royal Beans Perú",
  },
  robots: SITE_INDEXABLE
    ? { index: true, follow: true, googleBot: { index: true, follow: true, "max-image-preview": "large", "max-snippet": -1 } }
    : { index: false, follow: false, googleBot: { index: false, follow: false } },
  icons: { icon: [{ url: "/favicon-64.png", type: "image/png", sizes: "64x64" }], apple: [{ url: "/apple-touch-icon.png", sizes: "180x180" }] },
};

export default function SiteDocument({ children, lang }: Readonly<{ children: React.ReactNode; lang: "es" | "en" }>) {
  return (
    <html lang={lang} className={`${display.variable} ${body.variable}`}>
      <head><link rel="preload" as="image" href="/images/logo.webp" type="image/webp" /><link rel="preload" as="image" href="/images/mascotita-whatsapp.png" type="image/png" media="(min-width: 900px)" fetchPriority="low" /><link rel="stylesheet" href="/shared-chrome.css?v=20260918-5" /><script dangerouslySetInnerHTML={{ __html: productPreviewScript }} /><script dangerouslySetInnerHTML={{ __html: scrollRestoreScript }} /><noscript><style>{".public-route-loader{display:none}"}</style></noscript></head>
      <body>{children}<ContentProtection lang={lang} /></body>
    </html>
  );
}
