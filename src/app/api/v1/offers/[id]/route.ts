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
    const doc = await db.collection("offers").findOne({ _id: new ObjectId(id) });
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
  if ("offer_name" in body) update.offer_name = (body.offer_name as string)?.trim();
  if ("clickbank_vendor" in body) update.clickbank_vendor = (body.clickbank_vendor as string)?.trim();
  if ("clickbank_hoplink" in body) update.clickbank_hoplink = (body.clickbank_hoplink as string)?.trim();
  if ("is_active" in body) update.is_active = Boolean(body.is_active);

  const db = await getDb();
  try {
    const result = await db.collection("offers").findOneAndUpdate(
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
    const result = await db.collection("offers").deleteOne({ _id: new ObjectId(id) });
    if (result.deletedCount === 0) return NextResponse.json({ error: "Not found" }, { status: 404 });
    return NextResponse.json({ success: true });
  } catch { return NextResponse.json({ error: "Invalid ID" }, { status: 400 }); }
}
