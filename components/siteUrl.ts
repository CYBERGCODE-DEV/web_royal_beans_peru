export type DeploymentEnvironment = "staging" | "production";

const configuredEnvironment = process.env.NEXT_PUBLIC_DEPLOYMENT_ENV?.trim().toLowerCase();
const configuredSiteUrl = process.env.NEXT_PUBLIC_SITE_URL?.trim();

export const DEPLOYMENT_ENV: DeploymentEnvironment = configuredEnvironment === "staging"
  ? "staging"
  : configuredEnvironment === "production"
    ? "production"
    : configuredSiteUrl && !/^https?:\/\/(?:www\.)?royalbeansperu\.com\/?$/i.test(configuredSiteUrl)
      ? "staging"
      : process.env.NODE_ENV === "production" ? "production" : "staging";
export const SITE_URL = (
  configuredSiteUrl
  || (DEPLOYMENT_ENV === "production" ? "https://royalbeansperu.com" : "http://localhost:3000")
).replace(/\/$/, "");
export const SITE_INDEXABLE = DEPLOYMENT_ENV === "production"
  && process.env.NEXT_PUBLIC_INDEXABLE?.trim().toLowerCase() !== "false";
