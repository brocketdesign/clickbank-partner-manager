import { getDb } from "@/lib/mongodb";
import { ObjectId } from "mongodb";
import { GitBranch, Plus, Pencil, Info } from "lucide-react";
import Link from "next/link";
import { TogglePauseButton } from "./TogglePauseButton";
import { DeleteRuleButton } from "./DeleteRuleButton";

export default async function RulesPage() {
  const db = await getDb();

  const rules = await db
    .collection("redirect_rules")
    .find()
    .sort({ priority: 1 })
    .toArray();

  const count = rules.length;

  // Collect unique foreign key IDs
  const offerIds = [
    ...new Set(rules.filter((r) => r.offer_id).map((r) => r.offer_id.toString())),
  ];
  const domainIds = [
    ...new Set(rules.filter((r) => r.domain_id).map((r) => r.domain_id.toString())),
  ];
  const partnerIds = [
    ...new Set(rules.filter((r) => r.partner_id).map((r) => r.partner_id.toString())),
  ];

  // Batch fetch related docs
  const [offersRaw, domainsRaw, partnersRaw] = await Promise.all([
    offerIds.length > 0
      ? db
          .collection("offers")
          .find({ _id: { $in: offerIds.map((id) => new ObjectId(id)) } })
          .toArray()
      : Promise.resolve([]),
    domainIds.length > 0
      ? db
          .collection("domains")
          .find({ _id: { $in: domainIds.map((id) => new ObjectId(id)) } })
          .toArray()
      : Promise.resolve([]),
    partnerIds.length > 0
      ? db
          .collection("partners")
          .find({ _id: { $in: partnerIds.map((id) => new ObjectId(id)) } })
          .toArray()
      : Promise.resolve([]),
  ]);

  // Build lookup maps
  const offerMap = new Map(
    offersRaw.map((o) => [o._id.toString(), o.offer_name as string])
  );
  const domainMap = new Map(
    domainsRaw.map((d) => [d._id.toString(), d.domain_name as string])
  );
  const partnerMap = new Map(
    partnersRaw.map((p) => [p._id.toString(), p.aff_id as string])
  );

  function typePill(type: string) {
    switch (type) {
      case "partner":
        return (
          <span className="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-blue-50 text-blue-700">
            Partner
          </span>
        );
      case "domain":
        return (
          <span className="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-rose-50 text-rose-700">
            Domain
          </span>
        );
      default:
        return (
          <span className="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-purple-50 text-purple-700">
            Global
          </span>
        );
    }
  }

  function scopeLabel(rule: (typeof rules)[number]) {
    if (rule.rule_type === "partner" && rule.partner_id) {
      const affId = partnerMap.get(rule.partner_id.toString());
      return (
        <span className="text-muted-foreground">
          Partner:{" "}
          <span className="inline-block rounded bg-slate-100 px-1.5 py-0.5 font-mono text-xs text-foreground">
            {affId ?? "Unknown"}
          </span>
        </span>
      );
    }
    if (rule.rule_type === "domain" && rule.domain_id) {
      const domainName = domainMap.get(rule.domain_id.toString());
      return (
        <span className="text-muted-foreground">
          Domain:{" "}
          <span className="font-medium text-foreground">
            {domainName ?? "Unknown"}
          </span>
        </span>
      );
    }
    return <span className="text-muted-foreground">All Traffic</span>;
  }

  return (
    <div className="space-y-6 animate-in">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-3">
          <div className="flex items-center justify-center h-10 w-10 rounded-xl bg-gradient-to-br from-teal-500 to-emerald-600 text-white shadow-sm">
            <GitBranch className="h-5 w-5" />
          </div>
          <div>
            <h1 className="text-2xl font-bold text-foreground">
              Redirect Rules
            </h1>
            <p className="text-sm text-muted-foreground">
              {count} rule{count !== 1 ? "s" : ""}
            </p>
          </div>
        </div>
        <Link
          href="/admin/rules/new"
          className="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors shadow-sm"
        >
          <Plus className="h-4 w-4" />
          Add Rule
        </Link>
      </div>

      {/* Info Banner */}
      <div className="flex items-start gap-3 rounded-xl bg-blue-50 border border-blue-200 px-4 py-3">
        <Info className="h-5 w-5 text-blue-600 mt-0.5 shrink-0" />
        <p className="text-sm text-blue-800">
          Rules are matched by priority (lowest number first). Partner-specific
          rules override Domain rules, which override Global rules.
        </p>
      </div>

      {/* Table */}
      <div className="rounded-xl bg-card border border-border overflow-hidden shadow-sm">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="bg-muted/50 text-left">
                <th className="px-6 py-3 font-medium text-muted-foreground whitespace-nowrap">
                  Priority
                </th>
                <th className="px-6 py-3 font-medium text-muted-foreground whitespace-nowrap">
                  Rule Name
                </th>
                <th className="px-6 py-3 font-medium text-muted-foreground whitespace-nowrap">
                  Type
                </th>
                <th className="px-6 py-3 font-medium text-muted-foreground whitespace-nowrap">
                  Scope
                </th>
                <th className="px-6 py-3 font-medium text-muted-foreground whitespace-nowrap">
                  Offer
                </th>
                <th className="px-6 py-3 font-medium text-muted-foreground whitespace-nowrap">
                  Status
                </th>
                <th className="px-6 py-3 font-medium text-muted-foreground whitespace-nowrap">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody className="divide-y divide-border">
              {rules.length === 0 ? (
                <tr>
                  <td colSpan={7} className="px-6 py-16 text-center">
                    <GitBranch className="h-10 w-10 text-muted-foreground/40 mx-auto mb-3" />
                    <p className="text-muted-foreground font-medium">
                      No redirect rules yet
                    </p>
                    <p className="text-sm text-muted-foreground/60 mt-1">
                      Add your first rule to control traffic routing
                    </p>
                    <Link
                      href="/admin/rules/new"
                      className="inline-flex items-center gap-2 mt-4 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors"
                    >
                      <Plus className="h-4 w-4" />
                      Add Rule
                    </Link>
                  </td>
                </tr>
              ) : (
                rules.map((rule) => {
                  const ruleId = rule._id.toString();
                  const offerName = rule.offer_id
                    ? offerMap.get(rule.offer_id.toString()) ?? "Unknown"
                    : "—";

                  return (
                    <tr
                      key={ruleId}
                      className="hover:bg-muted/30 transition-colors"
                    >
                      {/* Priority */}
                      <td className="px-6 py-3.5 whitespace-nowrap">
                        <span className="inline-flex items-center justify-center h-7 w-7 rounded-full bg-slate-100 text-xs font-semibold text-foreground">
                          {rule.priority}
                        </span>
                      </td>

                      {/* Rule Name */}
                      <td className="px-6 py-3.5 whitespace-nowrap font-medium text-foreground">
                        {rule.rule_name}
                      </td>

                      {/* Type */}
                      <td className="px-6 py-3.5 whitespace-nowrap">
                        {typePill(rule.rule_type as string)}
                      </td>

                      {/* Scope */}
                      <td className="px-6 py-3.5 whitespace-nowrap text-sm">
                        {scopeLabel(rule)}
                      </td>

                      {/* Offer */}
                      <td className="px-6 py-3.5 whitespace-nowrap text-foreground">
                        {offerName}
                      </td>

                      {/* Status */}
                      <td className="px-6 py-3.5 whitespace-nowrap">
                        {rule.is_paused ? (
                          <span className="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-amber-50 text-amber-700">
                            Paused
                          </span>
                        ) : (
                          <span className="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-emerald-50 text-emerald-700">
                            Active
                          </span>
                        )}
                      </td>

                      {/* Actions */}
                      <td className="px-6 py-3.5 whitespace-nowrap">
                        <div className="flex items-center gap-1.5">
                          <Link
                            href={`/admin/rules/${ruleId}`}
                            className="inline-flex items-center justify-center h-8 w-8 rounded-lg text-muted-foreground hover:text-foreground hover:bg-muted transition-colors"
                            title="Edit rule"
                          >
                            <Pencil className="h-4 w-4" />
                          </Link>

                          <TogglePauseButton
                            id={ruleId}
                            isPaused={rule.is_paused as boolean}
                          />

                          <DeleteRuleButton id={ruleId} />
                        </div>
                      </td>
                    </tr>
                  );
                })
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
