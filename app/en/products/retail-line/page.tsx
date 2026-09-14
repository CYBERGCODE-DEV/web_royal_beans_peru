import type { Metadata } from "next";
import HomePage from "@/components/HomePage";

export const metadata: Metadata = {
  title: { absolute: "Retail Line | Royal Beans Perú" },
  description: "Retail solutions with Peruvian products for brands, stores and consumer channels.",
  alternates: { canonical: "/en/products/retail-line/", languages: { "es-PE": "/productos/linea-retail/", en: "/en/products/retail-line/", "x-default": "/productos/linea-retail/" } },
};

export default function RetailLinePage() {
  return <HomePage lang="en" page="productos" productLine="retail" />;
}
