import { NextRequest, NextResponse } from "next/server";
import { ObjectId } from "mongodb";
import { getDb } from "@/lib/mongodb";
import { generateUUID, hashString, getClientIP } from "@/lib/utils";

export const dynamic = "force-dynamic";

export async function GET(request: NextRequest) {
  const db = await getDb();

  // ── Parse query params ──
  const { searchParams } = request.nextUrl;
  const aff_id = searchParams.get("aff_id") ?? "";
  const uParam = searchParams.get("u") ?? "";
  const cParam = searchParams.get("c") ?? "";

  // ── Request info ──
  const host = request.headers.get("host") ?? "";
  const domain = host.split(":")[0]; // strip port if present
  const ip_address = getClientIP(request);
  const user_agent = request.headers.get("user-agent") ?? "";
  const referer = request.headers.get("referer") ?? "";

  // Tracking variables
  let domain_id: ObjectId | null = null;
  let partner_id: ObjectId | null = null;
  let offer_id: ObjectId | null = null;
  let rule_id: ObjectId | null = null;
  let redirect_url: string | null = null;

  // ── Step 1: Find domain ──
  if (domain) {
    const domainDoc = await db.collection("domains").findOne({
      domain_name: domain,
      is_active: true,
    });
    if (domainDoc) {
      domain_id = domainDoc._id;
    }
  }

  // ── Step 2: Find partner ──
  if (aff_id) {
    const partnerDoc = await db.collection("partners").findOne({
      aff_id: aff_id,
      is_active: true,
    });
    if (partnerDoc) {
      partner_id = partnerDoc._id;
    }
  }

  // ── Step 3: Direct URL override ──
  if (uParam) {
    const trimmed = uParam.trim();
    if (/^https:\/\//i.test(trimmed)) {
      redirect_url = trimmed;
    }
  }

  // ── Step 4: Resolve creative/offer by `c` param ──
  let creative_id_param: ObjectId | null = null;
  if (cParam) {
    try {
      creative_id_param = new ObjectId(cParam);
    } catch {
      // Not a valid ObjectId — ignore
    }
  }

  if (creative_id_param && !redirect_url) {
    // Try creatives table first (where partner_id matches and active)
    if (partner_id) {
      const creativeDoc = await db.collection("creatives").findOne({
        _id: creative_id_param,
        partner_id: partner_id,
        active: true,
      });
      if (creativeDoc?.destination_hoplink) {
        redirect_url = creativeDoc.destination_hoplink;
      }
    }

    // Fallback: try offers table directly
    if (!redirect_url) {
      const offerDoc = await db.collection("offers").findOne({
        _id: creative_id_param,
        is_active: true,
      });
      if (offerDoc?.clickbank_hoplink) {
        redirect_url = offerDoc.clickbank_hoplink;
        offer_id = offerDoc._id;
      }
    }
  }

  // ── Step 5: Rule matching (priority order) ──
  // 5a. Partner-specific rules
  if (!redirect_url && partner_id) {
    const partnerRules = await db
      .collection("redirect_rules")
      .aggregate([
        {
          $match: {
            rule_type: "partner",
            partner_id: partner_id,
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
        { $limit: 1 },
      ])
      .toArray();

    if (partnerRules.length > 0) {
      const rule = partnerRules[0];
      rule_id = rule._id;
      offer_id = rule.offer_id;
      redirect_url = rule.offer.clickbank_hoplink;
    }
  }

  // 5b. Domain-specific rules
  if (!redirect_url && domain_id) {
    const domainRules = await db
      .collection("redirect_rules")
      .aggregate([
        {
          $match: {
            rule_type: "domain",
            domain_id: domain_id,
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
        { $limit: 1 },
      ])
      .toArray();

    if (domainRules.length > 0) {
      const rule = domainRules[0];
      rule_id = rule._id;
      offer_id = rule.offer_id;
      redirect_url = rule.offer.clickbank_hoplink;
    }
  }

  // 5c. Global rules
  if (!redirect_url) {
    const globalRules = await db
      .collection("redirect_rules")
      .aggregate([
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
        { $limit: 1 },
      ])
      .toArray();

    if (globalRules.length > 0) {
      const rule = globalRules[0];
      rule_id = rule._id;
      offer_id = rule.offer_id;
      redirect_url = rule.offer.clickbank_hoplink;
    }
  }

  // ── Step 6: Log click (attribution) ──
  const click_id = generateUUID();
  const ip_hash = ip_address ? hashString(ip_address) : "";
  const ua_hash = user_agent ? hashString(user_agent) : "";

  // Validate creative_id for the clicks table (must exist in creatives collection)
  let validCreativeId: ObjectId | null = null;
  if (creative_id_param) {
    try {
      const exists = await db
        .collection("creatives")
        .findOne({ _id: creative_id_param }, { projection: { _id: 1 } });
      if (exists) {
        validCreativeId = creative_id_param;
      }
    } catch {
      // Invalid ObjectId or query failure — skip
    }
  }

  try {
    await db.collection("clicks").insertOne({
      partner_id: partner_id,
      creative_id: validCreativeId,
      click_id: click_id,
      ip_hash: ip_hash,
      ua_hash: ua_hash,
      referrer: referer,
      ts: new Date(),
    });
  } catch {
    // Non-blocking: don't fail the redirect if click logging fails
  }

  // ── Step 7: Log to click_logs (raw) ──
  try {
    await db.collection("click_logs").insertOne({
      domain_id: domain_id,
      partner_id: partner_id,
      offer_id: offer_id,
      rule_id: rule_id,
      ip_address: ip_address,
      user_agent: user_agent,
      referer: referer,
      redirect_url: redirect_url ?? "",
      clicked_at: new Date(),
    });
  } catch {
    // Non-blocking
  }

  // ── Step 8: No redirect URL → 404 ──
  if (!redirect_url) {
    return new NextResponse("No redirect rule configured", { status: 404 });
  }

  // ── Step 9: Set cb_attribution cookie (30 days) ──
  const cookieValue = JSON.stringify({ click_id, aff_id });
  const thirtyDays = 30 * 24 * 60 * 60;

  // ── Step 10: Append tid & cb_click_id to redirect URL ──
  try {
    const url = new URL(redirect_url);
    if (aff_id) url.searchParams.set("tid", aff_id);
    if (click_id) url.searchParams.set("cb_click_id", click_id);
    redirect_url = url.toString();
  } catch {
    // If URL parsing fails, append manually
    const sep = redirect_url.includes("?") ? "&" : "?";
    const params: string[] = [];
    if (aff_id) params.push(`tid=${encodeURIComponent(aff_id)}`);
    if (click_id) params.push(`cb_click_id=${encodeURIComponent(click_id)}`);
    if (params.length > 0) {
      redirect_url += sep + params.join("&");
    }
  }

  // ── Step 11: Return 302 redirect ──
  const response = NextResponse.redirect(redirect_url, 302);

  response.cookies.set("cb_attribution", cookieValue, {
    path: "/",
    maxAge: thirtyDays,
    sameSite: "lax",
    secure: true,
    httpOnly: false, // accessible to client-side JS for attribution
  });

  return response;
}
