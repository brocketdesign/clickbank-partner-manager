import { NextRequest, NextResponse } from "next/server";
import { getDb } from "@/lib/mongodb";
import { requireApiKey } from "@/lib/apiAuth";
import { ObjectId } from "mongodb";

export async function GET(req: NextRequest) {
  const denied = await requireApiKey(req);
  if (denied) return denied;

  const { searchParams } = req.nextUrl;
  const filter: Record<string, unknown> = {};
  if (searchParams.get("rule_type")) filter.rule_type = searchParams.get("rule_type");
  if (searchParams.get("is_paused") !== null && searchParams.get("is_paused") !== "")
    filter.is_paused = searchParams.get("is_paused") === "true";

  const db = await getDb();
  const items = await db.collection("redirect_rules").find(filter).sort({ priority: 1, created_at: -1 }).toArray();
  return NextResponse.json({ data: items, total: items.length });
}

export async function POST(req: NextRequest) {
  const denied = await requireApiKey(req);
  if (denied) return denied;

  let body: Record<string, unknown>;
  try { body = await req.json(); } catch { return NextResponse.json({ error: "Invalid JSON" }, { status: 400 }); }

  const rule_name = (body.rule_name as string)?.trim();
  const rule_type = body.rule_type as string;
  const offer_id = body.offer_id as string;
  if (!rule_name) return NextResponse.json({ error: "rule_name is required" }, { status: 400 });
  if (!["global", "domain", "partner"].includes(rule_type))
    return NextResponse.json({ error: "rule_type must be global, domain, or partner" }, { status: 400 });
  if (!offer_id) return NextResponse.json({ error: "offer_id is required" }, { status: 400 });

  const db = await getDb();
  const now = new Date();
  const doc: Record<string, unknown> = {
    rule_name,
    rule_type,
    offer_id: new ObjectId(offer_id),
    is_paused: Boolean(body.is_paused ?? false),
    priority: Number(body.priority ?? 0),
    created_at: now,
    updated_at: now,
  };
  if (body.domain_id) doc.domain_id = new ObjectId(body.domain_id as string);
  if (body.partner_id) doc.partner_id = new ObjectId(body.partner_id as string);

  const result = await db.collection("redirect_rules").insertOne(doc);
  const created = await db.collection("redirect_rules").findOne({ _id: result.insertedId });
  return NextResponse.json(created, { status: 201 });
}
