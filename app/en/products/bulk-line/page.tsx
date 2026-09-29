import type { Metadata } from "next";
import HomePage from "@/components/HomePage";
import { loadCatalogSnapshot } from "@/components/catalogSnapshot";
import { SITE_URL } from "@/components/siteUrl";

export const metadata: Metadata = {
  title: { absolute: "Bulk Line for Export | Royal Beans Perú" },
  description: "Royal Beans Perú Bulk Line: Peruvian pulses, grains, seeds, corn and spices for commercial and export operations.",
  keywords: ["jumbo mung beans", "Peruvian mung beans", "Peruvian pulses", "Peruvian beans", "Peruvian grains", "Royal Beans Perú"],
  alternates: { canonical: "/en/products/bulk-line/", languages: { "es-PE": "/productos/linea-a-granel/", en: "/en/products/bulk-line/", "x-default": "/productos/linea-a-granel/" } },
  openGraph: { title: "Bulk Line for Export | Royal Beans Perú", description: "Selected Peruvian pulses and grains for commercial and export operations.", url: `${SITE_URL}/en/products/bulk-line/`, type: "website", locale: "en_US" },
};

export default async function ConventionalLinePage() {
  const schema = {
    "@context": "https://schema.org",
    "@type": "CollectionPage",
    name: "Bulk Line from Royal Beans Perú",
    description: "Catalogue of Peruvian pulses and grains for commercial and export operations.",
    url: `${SITE_URL}/en/products/bulk-line/`,
    about: ["Jumbo mung beans", "Peruvian mung beans", "Canary beans", "Black beans", "Peruvian pulses", "Peruvian grains"].map(name => ({ "@type": "Thing", name })),
  };
  return <><HomePage lang="en" page="productos" productLine="conventional" initialProducts={await loadCatalogSnapshot("conventional")} /><script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(schema) }} /></>;
}
