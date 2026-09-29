import type { Metadata } from "next";
import HomePage from "@/components/HomePage";
import { loadCatalogSnapshot } from "@/components/catalogSnapshot";

export const metadata: Metadata = {
  title: { absolute: "Línea Retail | Royal Beans Perú" },
  description: "Soluciones retail de productos peruanos para marcas, tiendas y canales de consumo final.",
  alternates: { canonical: "/productos/linea-retail/", languages: { "es-PE": "/productos/linea-retail/", en: "/en/products/retail/", "x-default": "/productos/linea-retail/" } },
};

export default async function RetailLinePage() {
  return <HomePage lang="es" page="productos" productLine="retail" initialProducts={await loadCatalogSnapshot("retail")} />;
}
