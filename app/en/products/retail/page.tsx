import type { Metadata } from "next";
import HomePage from "@/components/HomePage";
import { loadCatalogSnapshot } from "@/components/catalogSnapshot";

export const metadata: Metadata = {
  title: { absolute: "Retail Line | Royal Beans Perú" },
  description: "Retail solutions with Peruvian products for brands, stores and consumer channels.",
  alternates: { canonical: "/en/products/retail/", languages: { "es-PE": "/productos/linea-retail/", en: "/en/products/retail/", "x-default": "/productos/linea-retail/" } },
};

export default async function RetailLinePage() {
  return <HomePage lang="en" page="productos" productLine="retail" initialProducts={await loadCatalogSnapshot("retail")} />;
}
