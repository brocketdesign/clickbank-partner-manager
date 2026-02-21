import { NextRequest, NextResponse } from "next/server";
import { getDb } from "@/lib/mongodb";
import { requireApiKey } from "@/lib/apiAuth";

export async function GET(req: NextRequest) {
  const denied = await requireApiKey(req);
  if (denied) return denied;

  const { searchParams } = req.nextUrl;
  const filter: Record<string, unknown> = {};
  if (searchParams.get("is_active") !== null && searchParams.get("is_active") !== "")
    filter.is_active = searchParams.get("is_active") === "true";

  const db = await getDb();
  const items = await db.collection("domains").find(filter).sort({ created_at: -1 }).toArray();
  return NextResponse.json({ data: items, total: items.length });
}

export async function POST(req: NextRequest) {
  const denied = await requireApiKey(req);
  if (denied) return denied;

  let body: Record<string, unknown>;
  try { body = await req.json(); } catch { return NextResponse.json({ error: "Invalid JSON" }, { status: 400 }); }

  const domain_name = (body.domain_name as string)?.trim();
  if (!domain_name) return NextResponse.json({ error: "domain_name is required" }, { status: 400 });

  const db = await getDb();
  const now = new Date();
  const result = await db.collection("domains").insertOne({
    domain_name,
    is_active: body.is_active !== undefined ? Boolean(body.is_active) : true,
    created_at: now,
    updated_at: now,
  });
  const created = await db.collection("domains").findOne({ _id: result.insertedId });
  return NextResponse.json(created, { status: 201 });
}
