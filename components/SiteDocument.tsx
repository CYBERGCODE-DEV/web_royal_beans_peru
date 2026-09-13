import type { Metadata } from "next";
import { Bricolage_Grotesque, DM_Sans } from "next/font/google";
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

export const metadata: Metadata = {
  metadataBase: new URL("https://royalbeansperu.com"),
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
  robots: { index: true, follow: true },
  icons: { icon: "/favicon.svg" },
};

export default function SiteDocument({ children, lang }: Readonly<{ children: React.ReactNode; lang: "es" | "en" }>) {
  return (
    <html lang={lang} className={`${display.variable} ${body.variable}`}>
      <body>{children}</body>
    </html>
  );
}
