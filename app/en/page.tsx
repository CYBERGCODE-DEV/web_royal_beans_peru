import HomePage from "@/components/HomePage";
import { pageMetadata } from "@/components/routes";
export const metadata = pageMetadata("inicio", "en");
export default function Page() { return <HomePage lang="en" />; }
