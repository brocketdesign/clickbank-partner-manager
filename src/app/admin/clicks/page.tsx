import { Suspense } from "react";
import { getDb } from "@/lib/mongodb";
import { ObjectId } from "mongodb";
import {
  MousePointerClick,
  ChevronLeft,
  ChevronRight,
} from "lucide-react";
import Link from "next/link";
import { ClickFilters } from "@/components/ClickFilters";

const PER_PAGE = 50;

interface ClickRow {
  _id: string;
  clicked_at: string;
  domain_name: string;
  partner_aff_id: string;
  partner_name: string;
  offer_name: string;
  rule_name: string;
  ip_address: string;
}

interface ClicksPageProps {
  searchParams: Promise<{
    domain?: string;
    partner?: string;
    offer?: string;
    date?: string;
    page?: string;
  }>;
}

export default async function ClicksPage({ searchParams }: ClicksPageProps) {
  const params = await searchParams;
  const db = await getDb();

  const page = Math.max(1, parseInt(params.page ?? "1", 10) || 1);
  const domainFilter = params.domain ?? "";
  const partnerFilter = params.partner ?? "";
  const offerFilter = params.offer ?? "";
  const dateFilter = params.date ?? "";

  // --- Build match stage ---
  const match: Record<string, unknown> = {};
  if (domainFilter && ObjectId.isValid(domainFilter)) {
    match.domain_id = new ObjectId(domainFilter);
  }
  if (partnerFilter && ObjectId.isValid(partnerFilter)) {
    match.partner_id = new ObjectId(partnerFilter);
  }
  if (offerFilter && ObjectId.isValid(offerFilter)) {
    match.offer_id = new ObjectId(offerFilter);
  }
  if (dateFilter) {
    const dayStart = new Date(dateFilter + "T00:00:00.000Z");
    const dayEnd = new Date(dateFilter + "T23:59:59.999Z");
    if (!isNaN(dayStart.getTime())) {
      match.clicked_at = { $gte: dayStart, $lte: dayEnd };
    }
  }

  // --- Fetch filter options in parallel with data ---
  const [domainsRaw, partnersRaw, offersRaw, totalCount, clicksRaw] =
    await Promise.all([
      db
        .collection("domains")
        .find({}, { projection: { domain_name: 1 } })
        .sort({ domain_name: 1 })
        .toArray(),
      db
        .collection("partners")
        .find({}, { projection: { aff_id: 1, partner_name: 1 } })
        .sort({ partner_name: 1 })
        .toArray(),
      db
        .collection("offers")
        .find({}, { projection: { offer_name: 1 } })
        .sort({ offer_name: 1 })
        .toArray(),
      db.collection("click_logs").countDocuments(match),
      db
        .collection("click_logs")
        .find(match)
        .sort({ clicked_at: -1 })
        .skip((page - 1) * PER_PAGE)
        .limit(PER_PAGE)
        .toArray(),
    ]);

  // --- Build lookup maps from clicked data ---
  const domainIdSet = new Set<string>();
  const partnerIdSet = new Set<string>();
  const offerIdSet = new Set<string>();
  const ruleIdSet = new Set<string>();

  for (const c of clicksRaw) {
    if (c.domain_id) domainIdSet.add(c.domain_id.toString());
    if (c.partner_id) partnerIdSet.add(c.partner_id.toString());
    if (c.offer_id) offerIdSet.add(c.offer_id.toString());
    if (c.rule_id) ruleIdSet.add(c.rule_id.toString());
  }

  const [domainMap, partnerMap, partnerNameMap, offerMap, ruleMap] =
    await Promise.all([
      buildMap(db, "domains", domainIdSet, "domain_name"),
      buildMap(db, "partners", partnerIdSet, "aff_id"),
      buildMap(db, "partners", partnerIdSet, "partner_name"),
      buildMap(db, "offers", offerIdSet, "offer_name"),
      buildMap(db, "redirect_rules", ruleIdSet, "rule_name"),
    ]);

  const clicks: ClickRow[] = clicksRaw.map((c) => ({
    _id: c._id.toString(),
    clicked_at: c.clicked_at ? new Date(c.clicked_at).toISOString() : "",
    domain_name: c.domain_id
      ? domainMap.get(c.domain_id.toString()) ?? "—"
      : "—",
    partner_aff_id: c.partner_id
      ? partnerMap.get(c.partner_id.toString()) ?? "—"
      : "—",
    partner_name: c.partner_id
      ? partnerNameMap.get(c.partner_id.toString()) ?? ""
      : "",
    offer_name: c.offer_id
      ? offerMap.get(c.offer_id.toString()) ?? "—"
      : "—",
    rule_name: c.rule_id
      ? ruleMap.get(c.rule_id.toString()) ?? "—"
      : "—",
    ip_address: c.ip_address ?? "",
  }));

  const totalPages = Math.max(1, Math.ceil(totalCount / PER_PAGE));

  // --- Filter options ---
  const domains = domainsRaw.map((d) => ({
    id: d._id.toString(),
    name: d.domain_name,
  }));
  const partners = partnersRaw.map((p) => ({
    id: p._id.toString(),
    name: p.aff_id
      ? `${p.aff_id}${p.partner_name ? " — " + p.partner_name : ""}`
      : p.partner_name ?? p._id.toString(),
  }));
  const offers = offersRaw.map((o) => ({
    id: o._id.toString(),
    name: o.offer_name,
  }));

  // --- Build pagination URL helper ---
  function pageUrl(p: number) {
    const sp = new URLSearchParams();
    if (domainFilter) sp.set("domain", domainFilter);
    if (partnerFilter) sp.set("partner", partnerFilter);
    if (offerFilter) sp.set("offer", offerFilter);
    if (dateFilter) sp.set("date", dateFilter);
    if (p > 1) sp.set("page", String(p));
    const qs = sp.toString();
    return `/admin/clicks${qs ? `?${qs}` : ""}`;
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center gap-3">
        <div className="inline-flex items-center justify-center h-10 w-10 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 text-white shadow-sm">
          <MousePointerClick className="h-5 w-5" />
        </div>
        <div>
          <h1 className="text-2xl font-bold text-foreground">Click Logs</h1>
          <p className="text-sm text-muted-foreground">
            {totalCount.toLocaleString()} total
            {totalCount === 1 ? " click" : " clicks"}
          </p>
        </div>
      </div>

      {/* Filters */}
      <div className="rounded-xl bg-card border border-border p-4">
        <Suspense fallback={null}>
          <ClickFilters
            domains={domains}
            partners={partners}
            offers={offers}
          />
        </Suspense>
      </div>

      {/* Table */}
      <div className="rounded-xl bg-card border border-border overflow-hidden">
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
                  Rule
                </th>
                <th className="px-6 py-3 font-medium text-muted-foreground whitespace-nowrap">
                  IP Address
                </th>
              </tr>
            </thead>
            <tbody className="divide-y divide-border">
              {clicks.length === 0 ? (
                <tr>
                  <td
                    colSpan={6}
                    className="px-6 py-16 text-center text-muted-foreground"
                  >
                    No clicks found matching the current filters.
                  </td>
                </tr>
              ) : (
                clicks.map((click) => (
                  <tr
                    key={click._id}
                    className="hover:bg-muted/30 transition-colors"
                  >
                    <td className="px-6 py-3 whitespace-nowrap text-muted-foreground">
                      {click.clicked_at
                        ? new Date(click.clicked_at).toLocaleString("en-US", {
                            month: "short",
                            day: "numeric",
                            year: "numeric",
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
                      <div className="flex flex-col">
                        <span className="inline-flex items-center rounded-md bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700 w-fit">
                          {click.partner_aff_id}
                        </span>
                        {click.partner_name && (
                          <span className="text-xs text-muted-foreground mt-0.5">
                            {click.partner_name}
                          </span>
                        )}
                      </div>
                    </td>
                    <td className="px-6 py-3 whitespace-nowrap">
                      <span className="inline-flex items-center rounded-md bg-purple-50 px-2 py-0.5 text-xs font-medium text-purple-700">
                        {click.offer_name}
                      </span>
                    </td>
                    <td className="px-6 py-3 whitespace-nowrap">
                      <span className="inline-flex items-center rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">
                        {click.rule_name}
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

        {/* Pagination */}
        {totalPages > 1 && (
          <div className="flex items-center justify-between px-6 py-4 border-t border-border bg-muted/30">
            <p className="text-sm text-muted-foreground">
              Page {page} of {totalPages}
            </p>
            <div className="flex items-center gap-1">
              {page > 1 ? (
                <Link
                  href={pageUrl(page - 1)}
                  className="inline-flex items-center gap-1 h-8 rounded-lg border border-border bg-white px-3 text-sm text-foreground hover:bg-secondary transition-colors"
                >
                  <ChevronLeft className="h-4 w-4" />
                  Previous
                </Link>
              ) : (
                <span className="inline-flex items-center gap-1 h-8 rounded-lg border border-border bg-muted px-3 text-sm text-muted-foreground cursor-not-allowed">
                  <ChevronLeft className="h-4 w-4" />
                  Previous
                </span>
              )}

              {paginationRange(page, totalPages).map((p, i) =>
                p === "..." ? (
                  <span
                    key={`ellipsis-${i}`}
                    className="px-2 text-muted-foreground text-sm"
                  >
                    …
                  </span>
                ) : (
                  <Link
                    key={p}
                    href={pageUrl(p as number)}
                    className={`inline-flex items-center justify-center h-8 w-8 rounded-lg text-sm font-medium transition-colors ${
                      p === page
                        ? "bg-primary text-primary-foreground shadow-sm"
                        : "border border-border bg-white text-foreground hover:bg-secondary"
                    }`}
                  >
                    {p}
                  </Link>
                )
              )}

              {page < totalPages ? (
                <Link
                  href={pageUrl(page + 1)}
                  className="inline-flex items-center gap-1 h-8 rounded-lg border border-border bg-white px-3 text-sm text-foreground hover:bg-secondary transition-colors"
                >
                  Next
                  <ChevronRight className="h-4 w-4" />
                </Link>
              ) : (
                <span className="inline-flex items-center gap-1 h-8 rounded-lg border border-border bg-muted px-3 text-sm text-muted-foreground cursor-not-allowed">
                  Next
                  <ChevronRight className="h-4 w-4" />
                </span>
              )}
            </div>
          </div>
        )}
      </div>
    </div>
  );
}

// --- Helpers ---

async function buildMap(
  db: Awaited<ReturnType<typeof getDb>>,
  collection: string,
  idSet: Set<string>,
  field: string
): Promise<Map<string, string>> {
  if (idSet.size === 0) return new Map();
  const ids = [...idSet].map((id) => new ObjectId(id));
  const docs = await db
    .collection(collection)
    .find({ _id: { $in: ids } }, { projection: { [field]: 1 } })
    .toArray();
  const map = new Map<string, string>();
  for (const doc of docs) {
    map.set(doc._id.toString(), (doc[field] as string) ?? "");
  }
  return map;
}

function paginationRange(
  current: number,
  total: number
): (number | "...")[] {
  const delta = 2;
  const range: (number | "...")[] = [];
  const rangeWithDots: (number | "...")[] = [];
  let l: number | undefined;

  for (let i = 1; i <= total; i++) {
    if (i === 1 || i === total || (i >= current - delta && i <= current + delta)) {
      range.push(i);
    }
  }

  for (const i of range) {
    if (l !== undefined) {
      if ((i as number) - l === 2) {
        rangeWithDots.push(l + 1);
      } else if ((i as number) - l > 2) {
        rangeWithDots.push("...");
      }
    }
    rangeWithDots.push(i);
    l = i as number;
  }

  return rangeWithDots;
}
