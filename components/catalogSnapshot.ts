import type { CatalogProduct } from "./InteractiveShell";

const source = (process.env.RB_CATALOG_SNAPSHOT_URL || "https://royalbeansperu.com").replace(/\/$/, "");

export async function loadCatalogSnapshot(line: "conventional" | "retail", featured = false): Promise<CatalogProduct[]> {
  const url = `${source}/api/products.php?line=${line}${featured ? "&featured=1" : ""}`;
  const response = await fetch(url, { cache: "force-cache", headers: { Accept: "application/json" } });
  if (!response.ok) throw new Error(`Cannot build indexable product HTML: ${line} API returned ${response.status}`);
  const payload = await response.json();
  if (!Array.isArray(payload.products)) throw new Error(`Cannot build indexable product HTML: invalid ${line} API response`);
  return payload.products.filter((item: CatalogProduct) => item && typeof item.id === "number" && typeof item.es === "string" && typeof item.en === "string" && typeof item.image === "string");
}
