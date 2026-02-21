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
    const doc = await db.collection("partner_applications").findOne({ _id: new ObjectId(id) });
    if (!doc) return NextResponse.json({ error: "Not found" }, { status: 404 });
    return NextResponse.json(doc);
  } catch {
    return NextResponse.json({ error: "Invalid ID" }, { status: 400 });
  }
}

export async function PATCH(req: NextRequest, { params }: Params) {
  const denied = await requireApiKey(req);
  if (denied) return denied;
  const { id } = await params;
  const db = await getDb();
  let body: Record<string, unknown>;
  try {
    body = await req.json();
  } catch {
    return NextResponse.json({ error: "Invalid JSON body" }, { status: 400 });
  }

  const allowed = ["status", "notes", "domain_verification_status", "domain_verified"];
  const update: Record<string, unknown> = { updated_at: new Date() };
  for (const key of allowed) {
    if (key in body) update[key] = body[key];
  }

  try {
    const result = await db
      .collection("partner_applications")
      .findOneAndUpdate(
        { _id: new ObjectId(id) },
        { $set: update },
        { returnDocument: "after" }
      );
    if (!result) return NextResponse.json({ error: "Not found" }, { status: 404 });
    return NextResponse.json(result);
  } catch {
    return NextResponse.json({ error: "Invalid ID" }, { status: 400 });
  }
}
