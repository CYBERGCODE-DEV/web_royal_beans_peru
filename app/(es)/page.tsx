import HomePage from "@/components/HomePage";
import { pageMetadata } from "@/components/routes";
export const metadata = pageMetadata("inicio", "es");
export default function Page() { return <HomePage lang="es" />; }
