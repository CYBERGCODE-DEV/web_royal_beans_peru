import HomePage from "@/components/HomePage";
import { loadCatalogSnapshot } from "@/components/catalogSnapshot";
import { pageMetadata } from "@/components/routes";
export const metadata = pageMetadata("inicio", "en");
export default async function Page() { return <HomePage lang="en" initialProducts={await loadCatalogSnapshot("conventional", true)} />; }
