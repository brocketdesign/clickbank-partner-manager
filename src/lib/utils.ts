import { v4 as uuidv4 } from "uuid";
import crypto from "crypto";

export function generateUUID(): string {
  return uuidv4();
}

export function generateAffId(): string {
  return crypto.randomBytes(8).toString("hex");
}

export function hashString(input: string): string {
  return crypto.createHash("sha256").update(input).digest("hex");
}

export function sanitize(data: string): string {
  return data
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#x27;")
    .trim();
}

export function getClientIP(request: Request): string {
  const forwarded = request.headers.get("x-forwarded-for");
  if (forwarded) {
    return forwarded.split(",")[0].trim();
  }
  const realIp = request.headers.get("x-real-ip");
  if (realIp) return realIp;
  return "127.0.0.1";
}

// Disposable email detection
const DISPOSABLE_DOMAINS = [
  "tempmail.com", "temp-mail.org", "guerrillamail.com", "mailinator.com",
  "10minutemail.com", "maildrop.cc", "yopmail.com", "trash-mail.com",
  "throwaway.email", "mytrashmail.com", "sharklasers.com", "spam4.me",
  "temp-mail.io", "temporary-mail.net", "fakeinbox.com", "mail.tm",
];

export function isDisposableEmail(email: string): boolean {
  const domain = email.split("@")[1]?.toLowerCase() ?? "";
  if (DISPOSABLE_DOMAINS.includes(domain)) return true;
  if (/(temp|trash|fake|disposable|spam|drop|guerrilla)/i.test(domain)) return true;
  return false;
}

// Verify domain is reachable
export async function verifyDomainReachable(url: string): Promise<boolean> {
  try {
    let normalizedUrl = url.trim();
    if (!/^https?:\/\//i.test(normalizedUrl)) {
      normalizedUrl = "https://" + normalizedUrl;
    }
    const res = await fetch(normalizedUrl, {
      method: "HEAD",
      redirect: "follow",
      signal: AbortSignal.timeout(5000),
    });
    return [200, 301, 302].includes(res.status);
  } catch {
    return false;
  }
}

// Format date for display
export function formatDate(date: Date | string): string {
  const d = new Date(date);
  return d.toLocaleDateString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
}

export function formatDateTime(date: Date | string): string {
  const d = new Date(date);
  return d.toLocaleString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

export function timeAgo(date: Date | string): string {
  const now = new Date();
  const d = new Date(date);
  const seconds = Math.floor((now.getTime() - d.getTime()) / 1000);

  if (seconds < 60) return "just now";
  if (seconds < 3600) return `${Math.floor(seconds / 60)}m ago`;
  if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ago`;
  if (seconds < 2592000) return `${Math.floor(seconds / 86400)}d ago`;
  return formatDate(date);
}
