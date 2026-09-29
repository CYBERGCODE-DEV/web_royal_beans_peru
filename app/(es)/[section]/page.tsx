import { notFound } from "next/navigation";
import HomePage from "@/components/HomePage";
import { pages, pageFromSlug, pageMetadata, type PageKey } from "@/components/routes";
export const dynamicParams = false;
export function generateStaticParams() { return (Object.keys(pages) as PageKey[]).filter(key => key !== "inicio" && key !== "productos").map(key => ({ section: pages[key].es })); }
export async function generateMetadata({ params }: { params: Promise<{ section: string }> }) { const { section } = await params; const page = pageFromSlug(section, "es"); if (!page) notFound(); return pageMetadata(page, "es"); }
export default async function Page({ params }: { params: Promise<{ section: string }> }) { const { section } = await params; const page = pageFromSlug(section, "es"); if (!page) notFound(); return <HomePage lang="es" page={page} />; }
