import { NextRequest, NextResponse } from "next/server";
import { getDb } from "@/lib/mongodb";
import { requireApiKey } from "@/lib/apiAuth";
import { ObjectId } from "mongodb";

type Params = { params: Promise<{ id: string }> };

export async function GET(req: NextRequest, { params }: Params) {
  const denied = await requireApiKey(req);
  if (denied) return denied;
  const { id } = await params;
  const db = await getDb();
  try {
    const doc = await db.collection("redirect_rules").findOne({ _id: new ObjectId(id) });
    if (!doc) return NextResponse.json({ error: "Not found" }, { status: 404 });
    return NextResponse.json(doc);
  } catch { return NextResponse.json({ error: "Invalid ID" }, { status: 400 }); }
}

export async function PUT(req: NextRequest, { params }: Params) {
  const denied = await requireApiKey(req);
  if (denied) return denied;
  const { id } = await params;
  let body: Record<string, unknown>;
  try { body = await req.json(); } catch { return NextResponse.json({ error: "Invalid JSON" }, { status: 400 }); }

  const update: Record<string, unknown> = { updated_at: new Date() };
  if ("rule_name" in body) update.rule_name = (body.rule_name as string)?.trim();
  if ("rule_type" in body) update.rule_type = body.rule_type;
  if ("offer_id" in body) update.offer_id = new ObjectId(body.offer_id as string);
  if ("domain_id" in body) update.domain_id = body.domain_id ? new ObjectId(body.domain_id as string) : null;
  if ("partner_id" in body) update.partner_id = body.partner_id ? new ObjectId(body.partner_id as string) : null;
  if ("is_paused" in body) update.is_paused = Boolean(body.is_paused);
  if ("priority" in body) update.priority = Number(body.priority);

  const db = await getDb();
  try {
    const result = await db.collection("redirect_rules").findOneAndUpdate(
      { _id: new ObjectId(id) },
      { $set: update },
      { returnDocument: "after" }
    );
    if (!result) return NextResponse.json({ error: "Not found" }, { status: 404 });
    return NextResponse.json(result);
  } catch { return NextResponse.json({ error: "Invalid ID" }, { status: 400 }); }
}

export async function DELETE(req: NextRequest, { params }: Params) {
  const denied = await requireApiKey(req);
  if (denied) return denied;
  const { id } = await params;
  const db = await getDb();
  try {
    const result = await db.collection("redirect_rules").deleteOne({ _id: new ObjectId(id) });
    if (result.deletedCount === 0) return NextResponse.json({ error: "Not found" }, { status: 404 });
    return NextResponse.json({ success: true });
  } catch { return NextResponse.json({ error: "Invalid ID" }, { status: 400 }); }
}
