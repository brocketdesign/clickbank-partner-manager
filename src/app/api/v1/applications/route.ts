import { NextRequest, NextResponse } from "next/server";
import { getDb } from "@/lib/mongodb";
import { requireApiKey } from "@/lib/apiAuth";

export async function GET(req: NextRequest) {
  const denied = await requireApiKey(req);
  if (denied) return denied;

  const { searchParams } = req.nextUrl;
  const page = Math.max(1, parseInt(searchParams.get("page") ?? "1", 10));
  const limit = Math.min(200, Math.max(1, parseInt(searchParams.get("limit") ?? "50", 10)));
  const skip = (page - 1) * limit;

  const filter: Record<string, unknown> = {};
  if (searchParams.get("status")) filter.status = searchParams.get("status");
  if (searchParams.get("email")) filter.email = { $regex: searchParams.get("email"), $options: "i" };
  if (searchParams.get("name")) filter.name = { $regex: searchParams.get("name"), $options: "i" };

  const db = await getDb();
  const [items, total] = await Promise.all([
    db.collection("partner_applications").find(filter).sort({ created_at: -1 }).skip(skip).limit(limit).toArray(),
    db.collection("partner_applications").countDocuments(filter),
  ]);

  return NextResponse.json({ data: items, total, page, limit, pages: Math.ceil(total / limit) });
}
