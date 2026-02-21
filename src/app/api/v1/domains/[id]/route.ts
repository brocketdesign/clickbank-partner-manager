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
    const doc = await db.collection("domains").findOne({ _id: new ObjectId(id) });
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
  if ("domain_name" in body) update.domain_name = (body.domain_name as string)?.trim();
  if ("is_active" in body) update.is_active = Boolean(body.is_active);

  const db = await getDb();
  try {
    const result = await db.collection("domains").findOneAndUpdate(
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
    const result = await db.collection("domains").deleteOne({ _id: new ObjectId(id) });
    if (result.deletedCount === 0) return NextResponse.json({ error: "Not found" }, { status: 404 });
    return NextResponse.json({ success: true });
  } catch { return NextResponse.json({ error: "Invalid ID" }, { status: 400 }); }
}
