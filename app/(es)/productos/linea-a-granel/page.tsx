import type { Metadata } from "next";
import HomePage from "@/components/HomePage";
import { loadCatalogSnapshot } from "@/components/catalogSnapshot";
import { SITE_URL } from "@/components/siteUrl";

export const metadata: Metadata = {
  title: { absolute: "Línea a Granel para Exportación | Royal Beans Perú" },
  description: "Línea a Granel de Royal Beans Perú: legumbres, granos, semillas, maíces y especias peruanas para operaciones comerciales y exportación.",
  keywords: ["loctao", "loctao jumbo", "frejol peruano", "frijol peruano", "legumbres peruanas", "granos peruanos", "Royal Beans Perú"],
  alternates: { canonical: "/productos/linea-a-granel/", languages: { "es-PE": "/productos/linea-a-granel/", en: "/en/products/bulk-line/", "x-default": "/productos/linea-a-granel/" } },
  openGraph: { title: "Línea a Granel para Exportación | Royal Beans Perú", description: "Legumbres y granos peruanos seleccionados para operaciones comerciales y exportación.", url: `${SITE_URL}/productos/linea-a-granel/`, type: "website", locale: "es_PE" },
};

export default async function ConventionalLinePage() {
  const schema = {
    "@context": "https://schema.org",
    "@type": "CollectionPage",
    name: "Línea a Granel de Royal Beans Perú",
    description: "Catálogo de legumbres y granos peruanos para operaciones comerciales y exportación.",
    url: `${SITE_URL}/productos/linea-a-granel/`,
    about: ["Loctao jumbo", "Frejol peruano", "Frijol canario", "Frijol negro", "Legumbres peruanas", "Granos peruanos"].map(name => ({ "@type": "Thing", name })),
  };
  return <><HomePage lang="es" page="productos" productLine="conventional" initialProducts={await loadCatalogSnapshot("conventional")} /><script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(schema) }} /></>;
}
