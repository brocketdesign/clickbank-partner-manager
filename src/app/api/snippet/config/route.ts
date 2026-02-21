import { NextRequest, NextResponse } from "next/server";
import { getDb } from "@/lib/mongodb";

export const dynamic = "force-dynamic";

const CORS_HEADERS = {
  "Access-Control-Allow-Origin": "*",
  "Access-Control-Allow-Methods": "GET, OPTIONS",
  "Access-Control-Allow-Headers": "Content-Type, Accept",
};

export async function OPTIONS() {
  return new NextResponse(null, { status: 204, headers: CORS_HEADERS });
}

export async function GET(request: NextRequest) {
  const { searchParams } = request.nextUrl;
  const partnerPublicId = searchParams.get("partner") ?? "";
  const domainParam = searchParams.get("domain") ?? "";

  if (!partnerPublicId) {
    return NextResponse.json(
      { success: false, message: "Missing partner id" },
      { status: 400, headers: CORS_HEADERS }
    );
  }

  if (!domainParam) {
    return NextResponse.json(
      { success: false, message: "Missing domain" },
      { status: 400, headers: CORS_HEADERS }
    );
  }

  const db = await getDb();

  // ── Validate domain ──
  const domainDoc = await db
    .collection("domains")
    .findOne({ domain_name: domainParam });

  if (domainDoc && !domainDoc.is_active) {
    return NextResponse.json(
      { success: false, message: "Domain is deactivated" },
      { status: 403, headers: CORS_HEADERS }
    );
  }

  // ── Validate partner (partners_new / snippet system) ──
  const partnerNew = await db.collection("partners_new").findOne({
    partner_id_public: partnerPublicId,
    status: "approved",
  });

  if (!partnerNew) {
    return NextResponse.json(
      { success: false, message: "Partner not found or not approved" },
      { status: 404, headers: CORS_HEADERS }
    );
  }

  // ── Look up internal partner from partners collection ──
  const internalPartner = await db.collection("partners").findOne({
    aff_id: partnerPublicId,
    is_active: true,
  });

  const internalPartnerId = internalPartner?._id ?? null;

  // ── Fetch offers via redirect_rules ──
  interface OfferRow {
    _id: unknown;
    offer_name: string;
    clickbank_hoplink: string;
    priority: number;
  }

  let offers: OfferRow[] = [];

  // Try partner-specific rules first
  if (internalPartnerId) {
    const partnerOffers = await db
      .collection("redirect_rules")
      .aggregate<OfferRow>([
        {
          $match: {
            rule_type: "partner",
            partner_id: internalPartnerId,
            is_paused: false,
          },
        },
        {
          $lookup: {
            from: "offers",
            localField: "offer_id",
            foreignField: "_id",
            as: "offer",
          },
        },
        { $unwind: "$offer" },
        { $match: { "offer.is_active": true } },
        { $sort: { priority: 1 } },
        {
          $project: {
            _id: "$offer._id",
            offer_name: "$offer.offer_name",
            clickbank_hoplink: "$offer.clickbank_hoplink",
            priority: "$priority",
          },
        },
      ])
      .toArray();

    offers = partnerOffers;
  }

  // Fallback to global rules if no partner-specific results
  if (offers.length === 0) {
    const globalOffers = await db
      .collection("redirect_rules")
      .aggregate<OfferRow>([
        {
          $match: {
            rule_type: "global",
            is_paused: false,
          },
        },
        {
          $lookup: {
            from: "offers",
            localField: "offer_id",
            foreignField: "_id",
            as: "offer",
          },
        },
        { $unwind: "$offer" },
        { $match: { "offer.is_active": true } },
        { $sort: { priority: 1 } },
        {
          $project: {
            _id: "$offer._id",
            offer_name: "$offer.offer_name",
            clickbank_hoplink: "$offer.clickbank_hoplink",
            priority: "$priority",
          },
        },
      ])
      .toArray();

    offers = globalOffers;
  }

  // ── Transform offers into creatives format ──
  const creatives = offers.map((offer) => ({
    id: String(offer._id),
    name: offer.offer_name,
    type: "native" as const,
    destination_hoplink: offer.clickbank_hoplink,
    weight: Math.max(1, 101 - (offer.priority ?? 1)),
  }));

  // ── Build response ──
  const response = {
    success: true,
    partner: {
      id: partnerNew.partner_id_public,
      name: partnerNew.name,
    },
    config: {
      selectors: ["article", ".post-content", ".entry-content", "main"],
      creatives,
      cache_ttl_seconds: 300,
    },
  };

  return NextResponse.json(response, { status: 200, headers: CORS_HEADERS });
}
