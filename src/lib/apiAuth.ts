import { createHash } from "crypto";
import { NextRequest, NextResponse } from "next/server";
import { getDb } from "@/lib/mongodb";

/** Hash a raw API key with SHA-256 */
export function hashKey(raw: string): string {
  return createHash("sha256").update(raw).digest("hex");
}

/** Generate a secure random API key in format: cbpm_<32 random hex chars> */
export function generateApiKey(): string {
  const bytes = new Uint8Array(24);
  crypto.getRandomValues(bytes);
  const hex = Array.from(bytes)
    .map((b) => b.toString(16).padStart(2, "0"))
    .join("");
  return `cbpm_${hex}`;
}

/**
 * Validate the Bearer token in the Authorization header.
 * Returns null if valid (proceeds), or a NextResponse with 401 if invalid.
 */
export async function requireApiKey(
  req: NextRequest
): Promise<NextResponse | null> {
  const authHeader = req.headers.get("authorization") ?? "";
  const raw = authHeader.startsWith("Bearer ")
    ? authHeader.slice(7).trim()
    : "";

  if (!raw) {
    return NextResponse.json(
      { error: "Missing Authorization header. Use: Bearer <api_key>" },
      { status: 401 }
    );
  }

  const db = await getDb();
  const hash = hashKey(raw);
  const key = await db
    .collection("api_keys")
    .findOne({ key_hash: hash, is_active: true });

  if (!key) {
    return NextResponse.json({ error: "Invalid or revoked API key" }, { status: 401 });
  }

  // Update last_used_at asynchronously (fire and forget)
  db.collection("api_keys")
    .updateOne({ _id: key._id }, { $set: { last_used_at: new Date() } })
    .catch(() => {});

  return null; // valid
}
