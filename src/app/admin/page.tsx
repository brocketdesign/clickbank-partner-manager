import { getDb } from "@/lib/mongodb";
import { ObjectId } from "mongodb";
import {
  MousePointerClick,
  Zap,
  Globe,
  Users,
  Tag,
  GitBranch,
  FileText,
  Clock,
  TrendingUp,
} from "lucide-react";
import { ClickChart } from "@/components/ClickChart";

interface ClickLogJoined {
  _id: string;
  clicked_at: string;
  ip_address: string;
  domain_name: string;
  partner_aff_id: string;
  offer_name: string;
}

export default async function AdminDashboard() {
  const db = await getDb();

  // --- Parallel data fetching ---
  const now = new Date();
  const todayStart = new Date(now);
  todayStart.setHours(0, 0, 0, 0);

  const sevenDaysAgo = new Date(now);
  sevenDaysAgo.setDate(sevenDaysAgo.getDate() - 6);
  sevenDaysAgo.setHours(0, 0, 0, 0);

  const [
    totalClicks,
    clicksToday,
    activeDomains,
    activePartners,
    activeOffers,
    activeRules,
    pendingApps,
    clickTrends,
    recentClicksRaw,
  ] = await Promise.all([
    db.collection("click_logs").countDocuments(),
    db
      .collection("click_logs")
      .countDocuments({ clicked_at: { $gte: todayStart } }),
    db.collection("domains").countDocuments({ is_active: true }),
    db.collection("partners").countDocuments({ is_active: true }),
    db.collection("offers").countDocuments({ is_active: true }),
    db.collection("redirect_rules").countDocuments({ is_paused: false }),
    db
      .collection("partner_applications")
      .countDocuments({ status: "pending" }),
    db
      .collection("click_logs")
      .aggregate([
        { $match: { clicked_at: { $gte: sevenDaysAgo } } },
        {
          $group: {
            _id: {
              $dateToString: { format: "%Y-%m-%d", date: "$clicked_at" },
            },
            clicks: { $sum: 1 },
          },
        },
        { $sort: { _id: 1 } },
      ])
      .toArray(),
    db
      .collection("click_logs")
      .find()
      .sort({ clicked_at: -1 })
      .limit(10)
      .toArray(),
  ]);

  // --- Build 7-day chart data (fill in missing days with 0) ---
  const trendMap = new Map<string, number>();
  for (const row of clickTrends) {
    trendMap.set(row._id as string, row.clicks as number);
  }
  const chartData: Array<{ date: string; clicks: number }> = [];
  for (let i = 0; i < 7; i++) {
    const d = new Date(sevenDaysAgo);
    d.setDate(d.getDate() + i);
    const key = d.toISOString().slice(0, 10);
    const label = d.toLocaleDateString("en-US", {
      month: "short",
      day: "numeric",
    });
    chartData.push({ date: label, clicks: trendMap.get(key) ?? 0 });
  }

  // --- Resolve recent clicks references ---
  const domainIds = new Set<string>();
  const partnerIds = new Set<string>();
  const offerIds = new Set<string>();

  for (const click of recentClicksRaw) {
    if (click.domain_id) domainIds.add(click.domain_id.toString());
    if (click.partner_id) partnerIds.add(click.partner_id.toString());
    if (click.offer_id) offerIds.add(click.offer_id.toString());
  }

  const [domainsMap, partnersMap, offersMap] = await Promise.all([
    domainIds.size > 0
      ? db
          .collection("domains")
          .find({
            _id: { $in: [...domainIds].map((id) => new ObjectId(id)) },
          })
          .toArray()
          .then((docs) => {
            const map = new Map<string, string>();
            docs.forEach((d) => map.set(d._id.toString(), d.domain_name));
            return map;
          })
      : Promise.resolve(new Map<string, string>()),
    partnerIds.size > 0
      ? db
          .collection("partners")
          .find({
            _id: { $in: [...partnerIds].map((id) => new ObjectId(id)) },
          })
          .toArray()
          .then((docs) => {
            const map = new Map<string, string>();
            docs.forEach((d) => map.set(d._id.toString(), d.aff_id ?? d.partner_name));
            return map;
          })
      : Promise.resolve(new Map<string, string>()),
    offerIds.size > 0
      ? db
          .collection("offers")
          .find({
            _id: { $in: [...offerIds].map((id) => new ObjectId(id)) },
          })
          .toArray()
          .then((docs) => {
            const map = new Map<string, string>();
            docs.forEach((d) => map.set(d._id.toString(), d.offer_name));
            return map;
          })
      : Promise.resolve(new Map<string, string>()),
  ]);

  const recentClicks: ClickLogJoined[] = recentClicksRaw.map((click) => ({
    _id: click._id.toString(),
    clicked_at: click.clicked_at
      ? new Date(click.clicked_at).toISOString()
      : "",
    ip_address: click.ip_address ?? "",
    domain_name: click.domain_id
      ? domainsMap.get(click.domain_id.toString()) ?? "—"
      : "—",
    partner_aff_id: click.partner_id
      ? partnersMap.get(click.partner_id.toString()) ?? "—"
      : "—",
    offer_name: click.offer_id
      ? offersMap.get(click.offer_id.toString()) ?? "—"
      : "—",
  }));

  // --- Stats cards config ---
  const stats = [
    {
      label: "Total Clicks",
      value: totalClicks,
      icon: MousePointerClick,
      gradient: "from-blue-500 to-blue-600",
      bg: "bg-blue-50",
      text: "text-blue-700",
    },
    {
      label: "Clicks Today",
      value: clicksToday,
      icon: Zap,
      gradient: "from-amber-500 to-orange-500",
      bg: "bg-amber-50",
      text: "text-amber-700",
    },
    {
      label: "Active Domains",
      value: activeDomains,
      icon: Globe,
      gradient: "from-emerald-500 to-teal-500",
      bg: "bg-emerald-50",
      text: "text-emerald-700",
    },
    {
      label: "Active Partners",
      value: activePartners,
      icon: Users,
      gradient: "from-violet-500 to-purple-500",
      bg: "bg-violet-50",
      text: "text-violet-700",
    },
    {
      label: "Active Offers",
      value: activeOffers,
      icon: Tag,
      gradient: "from-pink-500 to-rose-500",
      bg: "bg-pink-50",
      text: "text-pink-700",
    },
    {
      label: "Active Rules",
      value: activeRules,
      icon: GitBranch,
      gradient: "from-cyan-500 to-sky-500",
      bg: "bg-cyan-50",
      text: "text-cyan-700",
    },
    {
      label: "Pending Apps",
      value: pendingApps,
      icon: FileText,
      gradient: "from-red-500 to-rose-600",
      bg: "bg-red-50",
      text: "text-red-700",
    },
  ];

  return (
    <div className="space-y-8">
      {/* Header */}
      <div>
        <h1 className="text-2xl font-bold text-foreground">Dashboard</h1>
        <p className="text-sm text-muted-foreground mt-1">
          Overview of your ClickBank partner network
        </p>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-7 gap-4">
        {stats.map((stat, i) => (
          <div
            key={stat.label}
            className="rounded-xl bg-card border border-border p-4 flex flex-col gap-3 animate-in"
            style={{ animationDelay: `${i * 60}ms`, animationFillMode: "both" }}
          >
            <div className="flex items-center justify-between">
              <span
                className={`inline-flex items-center justify-center h-9 w-9 rounded-lg bg-gradient-to-br ${stat.gradient} text-white shadow-sm`}
              >
                <stat.icon className="h-4.5 w-4.5" />
              </span>
            </div>
            <div>
              <p className="text-2xl font-bold text-foreground tabular-nums">
                {stat.value.toLocaleString()}
              </p>
              <p className="text-xs text-muted-foreground mt-0.5">
                {stat.label}
              </p>
            </div>
          </div>
        ))}
      </div>

      {/* Click Trends Chart */}
      <div className="rounded-xl bg-card border border-border p-6">
        <div className="flex items-center gap-2 mb-6">
          <TrendingUp className="h-5 w-5 text-primary" />
          <h2 className="text-lg font-semibold text-foreground">
            Click Trends
          </h2>
          <span className="text-sm text-muted-foreground">— Last 7 days</span>
        </div>
        <ClickChart data={chartData} />
      </div>

      {/* Recent Clicks Table */}
      <div className="rounded-xl bg-card border border-border overflow-hidden">
        <div className="flex items-center gap-2 px-6 py-4 border-b border-border">
          <Clock className="h-5 w-5 text-primary" />
          <h2 className="text-lg font-semibold text-foreground">
            Recent Clicks
          </h2>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="bg-muted/50 text-left">
                <th className="px-6 py-3 font-medium text-muted-foreground whitespace-nowrap">
                  Time
                </th>
                <th className="px-6 py-3 font-medium text-muted-foreground whitespace-nowrap">
                  Domain
                </th>
                <th className="px-6 py-3 font-medium text-muted-foreground whitespace-nowrap">
                  Partner
                </th>
                <th className="px-6 py-3 font-medium text-muted-foreground whitespace-nowrap">
                  Offer
                </th>
                <th className="px-6 py-3 font-medium text-muted-foreground whitespace-nowrap">
                  IP Address
                </th>
              </tr>
            </thead>
            <tbody className="divide-y divide-border">
              {recentClicks.length === 0 ? (
                <tr>
                  <td
                    colSpan={5}
                    className="px-6 py-12 text-center text-muted-foreground"
                  >
                    No clicks recorded yet.
                  </td>
                </tr>
              ) : (
                recentClicks.map((click) => (
                  <tr
                    key={click._id}
                    className="hover:bg-muted/30 transition-colors"
                  >
                    <td className="px-6 py-3 whitespace-nowrap text-muted-foreground">
                      {click.clicked_at
                        ? new Date(click.clicked_at).toLocaleString("en-US", {
                            month: "short",
                            day: "numeric",
                            hour: "2-digit",
                            minute: "2-digit",
                            second: "2-digit",
                          })
                        : "—"}
                    </td>
                    <td className="px-6 py-3 whitespace-nowrap">
                      <span className="inline-flex items-center rounded-md bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700">
                        {click.domain_name}
                      </span>
                    </td>
                    <td className="px-6 py-3 whitespace-nowrap">
                      <span className="inline-flex items-center rounded-md bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700">
                        {click.partner_aff_id}
                      </span>
                    </td>
                    <td className="px-6 py-3 whitespace-nowrap">
                      <span className="inline-flex items-center rounded-md bg-purple-50 px-2 py-0.5 text-xs font-medium text-purple-700">
                        {click.offer_name}
                      </span>
                    </td>
                    <td className="px-6 py-3 whitespace-nowrap font-mono text-xs text-muted-foreground">
                      {click.ip_address}
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
