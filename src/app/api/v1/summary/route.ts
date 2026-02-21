import { NextRequest, NextResponse } from "next/server";
import { getDb } from "@/lib/mongodb";
import { requireApiKey } from "@/lib/apiAuth";

export async function GET(req: NextRequest) {
  const denied = await requireApiKey(req);
  if (denied) return denied;

  const db = await getDb();

  const [
    totalClicks,
    totalPartners,
    activePartners,
    totalOffers,
    activeOffers,
    totalDomains,
    activeDomains,
    totalRules,
    activeRules,
    pendingApplications,
    totalApplications,
    recentClicks,
  ] = await Promise.all([
    db.collection("click_logs").countDocuments(),
    db.collection("partners").countDocuments(),
    db.collection("partners").countDocuments({ is_active: true }),
    db.collection("offers").countDocuments(),
    db.collection("offers").countDocuments({ is_active: true }),
    db.collection("domains").countDocuments(),
    db.collection("domains").countDocuments({ is_active: true }),
    db.collection("redirect_rules").countDocuments(),
    db.collection("redirect_rules").countDocuments({ is_paused: false }),
    db.collection("partner_applications").countDocuments({ status: "pending" }),
    db.collection("partner_applications").countDocuments(),
    db.collection("click_logs").find().sort({ clicked_at: -1 }).limit(5).toArray(),
  ]);

  // Clicks in last 7 days
  const sevenDaysAgo = new Date(Date.now() - 7 * 24 * 60 * 60 * 1000);
  const clicksLast7d = await db
    .collection("click_logs")
    .countDocuments({ clicked_at: { $gte: sevenDaysAgo } });

  return NextResponse.json({
    summary: {
      clicks: { total: totalClicks, last_7_days: clicksLast7d },
      partners: { total: totalPartners, active: activePartners },
      offers: { total: totalOffers, active: activeOffers },
      domains: { total: totalDomains, active: activeDomains },
      rules: { total: totalRules, active: activeRules },
      applications: { total: totalApplications, pending: pendingApplications },
    },
    recent_clicks: recentClicks.map((c) => ({
      _id: c._id,
      ip_address: c.ip_address,
      redirect_url: c.redirect_url,
      referer: c.referer,
      clicked_at: c.clicked_at,
    })),
  });
}
