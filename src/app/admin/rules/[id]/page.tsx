import { getDb } from "@/lib/mongodb";
import { ObjectId } from "mongodb";
import { notFound } from "next/navigation";
import { ArrowLeft, GitBranch } from "lucide-react";
import Link from "next/link";
import RuleForm from "../RuleForm";

interface Props {
  params: Promise<{ id: string }>;
}

export default async function RuleEditPage({ params }: Props) {
  const { id } = await params;

  const isNew = id === "new";
  const db = await getDb();

  let ruleData:
    | {
        _id: string;
        rule_name: string;
        rule_type: string;
        domain_id: string;
        partner_id: string;
        offer_id: string;
        priority: number;
        is_paused: boolean;
      }
    | undefined;

  if (!isNew) {
    if (!ObjectId.isValid(id)) {
      notFound();
    }

    const rule = await db
      .collection("redirect_rules")
      .findOne({ _id: new ObjectId(id) });

    if (!rule) {
      notFound();
    }

    ruleData = {
      _id: rule._id.toString(),
      rule_name: rule.rule_name as string,
      rule_type: rule.rule_type as string,
      domain_id: rule.domain_id ? rule.domain_id.toString() : "",
      partner_id: rule.partner_id ? rule.partner_id.toString() : "",
      offer_id: rule.offer_id ? rule.offer_id.toString() : "",
      priority: (rule.priority as number) ?? 100,
      is_paused: rule.is_paused as boolean,
    };
  }

  // Load dropdowns
  const [domainsRaw, partnersRaw, offersRaw] = await Promise.all([
    db.collection("domains").find().sort({ domain_name: 1 }).toArray(),
    db.collection("partners").find().sort({ partner_name: 1 }).toArray(),
    db.collection("offers").find().sort({ offer_name: 1 }).toArray(),
  ]);

  const domains = domainsRaw.map((d) => ({
    _id: d._id.toString(),
    domain_name: d.domain_name as string,
  }));

  const partners = partnersRaw.map((p) => ({
    _id: p._id.toString(),
    aff_id: p.aff_id as string,
    partner_name: p.partner_name as string,
  }));

  const offers = offersRaw.map((o) => ({
    _id: o._id.toString(),
    offer_name: o.offer_name as string,
  }));

  return (
    <div className="space-y-6 animate-in max-w-2xl">
      {/* Back Link */}
      <Link
        href="/admin/rules"
        className="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground transition-colors"
      >
        <ArrowLeft className="h-4 w-4" />
        Back to Rules
      </Link>

      {/* Header */}
      <div className="flex items-center gap-3">
        <div className="flex items-center justify-center h-10 w-10 rounded-xl bg-gradient-to-br from-teal-500 to-emerald-600 text-white shadow-sm">
          <GitBranch className="h-5 w-5" />
        </div>
        <div>
          <h1 className="text-2xl font-bold text-foreground">
            {isNew ? "Add Rule" : "Edit Rule"}
          </h1>
          {!isNew && ruleData && (
            <p className="text-sm text-muted-foreground">
              {ruleData.rule_name}
            </p>
          )}
        </div>
      </div>

      {/* Form Card */}
      <div className="rounded-xl bg-card border border-border shadow-sm overflow-hidden">
        <div className="px-6 py-5">
          <RuleForm
            initialData={ruleData}
            domains={domains}
            partners={partners}
            offers={offers}
          />
        </div>
      </div>
    </div>
  );
}
