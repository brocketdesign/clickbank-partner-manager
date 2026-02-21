import { NextRequest, NextResponse } from "next/server";
import { getDb } from "@/lib/mongodb";
import { requireApiKey } from "@/lib/apiAuth";
import { ObjectId } from "mongodb";

export async function GET(req: NextRequest) {
  const denied = await requireApiKey(req);
  if (denied) return denied;

  const { searchParams } = req.nextUrl;
  const page = Math.max(1, parseInt(searchParams.get("page") ?? "1", 10));
  const limit = Math.min(200, Math.max(1, parseInt(searchParams.get("limit") ?? "50", 10)));
  const skip = (page - 1) * limit;

  // Filters
  const filter: Record<string, unknown> = {};
  if (searchParams.get("domain_id"))
    filter.domain_id = new ObjectId(searchParams.get("domain_id")!);
  if (searchParams.get("partner_id"))
    filter.partner_id = new ObjectId(searchParams.get("partner_id")!);
  if (searchParams.get("offer_id"))
    filter.offer_id = new ObjectId(searchParams.get("offer_id")!);
  if (searchParams.get("ip_address"))
    filter.ip_address = searchParams.get("ip_address");
  if (searchParams.get("from") || searchParams.get("to")) {
    const dateFilter: Record<string, Date> = {};
    if (searchParams.get("from")) dateFilter.$gte = new Date(searchParams.get("from")!);
    if (searchParams.get("to")) dateFilter.$lte = new Date(searchParams.get("to")!);
    filter.clicked_at = dateFilter;
  }

  const db = await getDb();
  const [items, total] = await Promise.all([
    db.collection("click_logs").find(filter).sort({ clicked_at: -1 }).skip(skip).limit(limit).toArray(),
    db.collection("click_logs").countDocuments(filter),
  ]);

  return NextResponse.json({ data: items, total, page, limit, pages: Math.ceil(total / limit) });
}
