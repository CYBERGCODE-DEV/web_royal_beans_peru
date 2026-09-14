import type { Metadata } from "next";
import HomePage from "@/components/HomePage";

export const metadata: Metadata = {
  title: { absolute: "Conventional Line | Royal Beans Perú" },
  description: "Conventional catalogue of Peruvian pulses, grains, corn, seeds and spices for commercial operations.",
  alternates: { canonical: "/en/products/conventional-line/", languages: { "es-PE": "/productos/linea-convencional/", en: "/en/products/conventional-line/", "x-default": "/productos/linea-convencional/" } },
};

export default function ConventionalLinePage() {
  return <HomePage lang="en" page="productos" productLine="conventional" />;
}
