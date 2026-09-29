import { notFound } from "next/navigation";
import HomePage from "@/components/HomePage";
import { pages, pageFromSlug, pageMetadata, type PageKey } from "@/components/routes";
export const dynamicParams = false;
export function generateStaticParams() { return (Object.keys(pages) as PageKey[]).filter(key => key !== "inicio" && key !== "productos").map(key => ({ section: pages[key].en })); }
export async function generateMetadata({ params }: { params: Promise<{ section: string }> }) { const { section } = await params; const page = pageFromSlug(section, "en"); if (!page) notFound(); return pageMetadata(page, "en"); }
export default async function Page({ params }: { params: Promise<{ section: string }> }) { const { section } = await params; const page = pageFromSlug(section, "en"); if (!page) notFound(); return <HomePage lang="en" page={page} />; }
