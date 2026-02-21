import { NextRequest, NextResponse } from "next/server";
import { ObjectId } from "mongodb";
import { getDb } from "@/lib/mongodb";
import { hashString, getClientIP } from "@/lib/utils";

export const dynamic = "force-dynamic";

const CORS_HEADERS = {
  "Access-Control-Allow-Origin": "*",
  "Access-Control-Allow-Methods": "POST, OPTIONS",
  "Access-Control-Allow-Headers": "Content-Type, Accept",
};

export async function OPTIONS() {
  return new NextResponse(null, { status: 204, headers: CORS_HEADERS });
}

export async function POST(request: NextRequest) {
  // Accept both JSON and form-encoded bodies
  let partner_pub = "";
  let creative_id_raw = "";

  const contentType = request.headers.get("content-type") ?? "";

  if (contentType.includes("application/json")) {
    try {
      const body = await request.json();
      partner_pub = body.partner ?? "";
      creative_id_raw = body.creative_id ?? "";
    } catch {
      return NextResponse.json(
        { success: false, message: "Invalid JSON body" },
        { status: 400, headers: CORS_HEADERS }
      );
    }
  } else {
    // Handle form-encoded (URLSearchParams from sendBeacon)
    try {
      const text = await request.text();
      const params = new URLSearchParams(text);
      partner_pub = params.get("partner") ?? "";
      creative_id_raw = params.get("creative_id") ?? "";
    } catch {
      return NextResponse.json(
        { success: false, message: "Invalid request body" },
        { status: 400, headers: CORS_HEADERS }
      );
    }
  }

  if (!partner_pub) {
    return NextResponse.json(
      { success: false, message: "Missing partner" },
      { status: 400, headers: CORS_HEADERS }
    );
  }

  const db = await getDb();

  // ── Validate partner ──
  const partnerDoc = await db.collection("partners_new").findOne({
    partner_id_public: partner_pub,
    status: "approved",
  });

  if (!partnerDoc) {
    return NextResponse.json(
      { success: false, message: "Partner not found" },
      { status: 404, headers: CORS_HEADERS }
    );
  }

  // ── Build impression document ──
  const ip = getClientIP(request);
  const ua = request.headers.get("user-agent") ?? "";
  const ip_hash = ip ? hashString(ip) : "";
  const ua_hash = ua ? hashString(ua) : "";

  let creative_id: ObjectId | null = null;
  if (creative_id_raw) {
    try {
      creative_id = new ObjectId(String(creative_id_raw));
    } catch {
      // Not a valid ObjectId — store as null
    }
  }

  try {
    await db.collection("impressions").insertOne({
      partner_id: partnerDoc._id,
      creative_id: creative_id,
      ip_hash: ip_hash,
      ua_hash: ua_hash,
      ts: new Date(),
    });
  } catch {
    return NextResponse.json(
      { success: false, message: "DB insert failed" },
      { status: 500, headers: CORS_HEADERS }
    );
  }

  return NextResponse.json(
    { success: true },
    { status: 201, headers: CORS_HEADERS }
  );
}
