"use client";

import { useState, useTransition } from "react";
import { Loader2 } from "lucide-react";
import { toast } from "sonner";
import { saveRule } from "./actions";

interface RuleFormProps {
  initialData?: {
    _id: string;
    rule_name: string;
    rule_type: string;
    domain_id: string;
    partner_id: string;
    offer_id: string;
    priority: number;
    is_paused: boolean;
  };
  domains: Array<{ _id: string; domain_name: string }>;
  partners: Array<{ _id: string; aff_id: string; partner_name: string }>;
  offers: Array<{ _id: string; offer_name: string }>;
}

export default function RuleForm({
  initialData,
  domains,
  partners,
  offers,
}: RuleFormProps) {
  const [ruleName, setRuleName] = useState(initialData?.rule_name ?? "");
  const [ruleType, setRuleType] = useState(initialData?.rule_type ?? "global");
  const [domainId, setDomainId] = useState(initialData?.domain_id ?? "");
  const [partnerId, setPartnerId] = useState(initialData?.partner_id ?? "");
  const [offerId, setOfferId] = useState(initialData?.offer_id ?? "");
  const [priority, setPriority] = useState(initialData?.priority ?? 100);
  const [isPaused, setIsPaused] = useState(initialData?.is_paused ?? false);
  const [isPending, startTransition] = useTransition();

  function handleSubmit(formData: FormData) {
    startTransition(async () => {
      try {
        await saveRule(formData);
        toast.success(initialData ? "Rule updated" : "Rule created");
      } catch (err) {
        const message =
          err instanceof Error ? err.message : "Failed to save rule";
        toast.error(message);
      }
    });
  }

  return (
    <form action={handleSubmit} className="space-y-6 max-w-lg">
      {initialData && (
        <input type="hidden" name="id" value={initialData._id} />
      )}

      {/* Rule Name */}
      <div className="space-y-2">
        <label
          htmlFor="rule_name"
          className="block text-sm font-medium text-foreground"
        >
          Rule Name
        </label>
        <input
          type="text"
          id="rule_name"
          name="rule_name"
          value={ruleName}
          onChange={(e) => setRuleName(e.target.value)}
          required
          placeholder="e.g. Default Global Rule"
          className="w-full rounded-lg border border-border bg-background px-4 py-2.5 text-sm text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 transition-colors"
        />
      </div>

      {/* Rule Type */}
      <div className="space-y-2">
        <label
          htmlFor="rule_type"
          className="block text-sm font-medium text-foreground"
        >
          Rule Type
        </label>
        <select
          id="rule_type"
          name="rule_type"
          value={ruleType}
          onChange={(e) => {
            setRuleType(e.target.value);
            if (e.target.value !== "domain") setDomainId("");
            if (e.target.value !== "partner") setPartnerId("");
          }}
          className="w-full rounded-lg border border-border bg-background px-4 py-2.5 text-sm text-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 transition-colors"
        >
          <option value="global">Global</option>
          <option value="domain">Domain</option>
          <option value="partner">Partner</option>
        </select>
        <p className="text-xs text-muted-foreground">
          Partner rules override Domain rules, which override Global rules.
        </p>
      </div>

      {/* Domain (visible when type=domain) */}
      <div
        className={`space-y-2 overflow-hidden transition-all duration-200 ${
          ruleType === "domain"
            ? "max-h-40 opacity-100"
            : "max-h-0 opacity-0"
        }`}
      >
        <label
          htmlFor="domain_id"
          className="block text-sm font-medium text-foreground"
        >
          Domain
        </label>
        <select
          id="domain_id"
          name="domain_id"
          value={domainId}
          onChange={(e) => setDomainId(e.target.value)}
          className="w-full rounded-lg border border-border bg-background px-4 py-2.5 text-sm text-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 transition-colors"
        >
          <option value="">Select a domain…</option>
          {domains.map((d) => (
            <option key={d._id} value={d._id}>
              {d.domain_name}
            </option>
          ))}
        </select>
      </div>

      {/* Partner (visible when type=partner) */}
      <div
        className={`space-y-2 overflow-hidden transition-all duration-200 ${
          ruleType === "partner"
            ? "max-h-40 opacity-100"
            : "max-h-0 opacity-0"
        }`}
      >
        <label
          htmlFor="partner_id"
          className="block text-sm font-medium text-foreground"
        >
          Partner
        </label>
        <select
          id="partner_id"
          name="partner_id"
          value={partnerId}
          onChange={(e) => setPartnerId(e.target.value)}
          className="w-full rounded-lg border border-border bg-background px-4 py-2.5 text-sm text-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 transition-colors"
        >
          <option value="">Select a partner…</option>
          {partners.map((p) => (
            <option key={p._id} value={p._id}>
              {p.partner_name} ({p.aff_id})
            </option>
          ))}
        </select>
      </div>

      {/* Offer */}
      <div className="space-y-2">
        <label
          htmlFor="offer_id"
          className="block text-sm font-medium text-foreground"
        >
          Offer
        </label>
        <select
          id="offer_id"
          name="offer_id"
          value={offerId}
          onChange={(e) => setOfferId(e.target.value)}
          required
          className="w-full rounded-lg border border-border bg-background px-4 py-2.5 text-sm text-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 transition-colors"
        >
          <option value="">Select an offer…</option>
          {offers.map((o) => (
            <option key={o._id} value={o._id}>
              {o.offer_name}
            </option>
          ))}
        </select>
      </div>

      {/* Priority */}
      <div className="space-y-2">
        <label
          htmlFor="priority"
          className="block text-sm font-medium text-foreground"
        >
          Priority
        </label>
        <input
          type="number"
          id="priority"
          name="priority"
          value={priority}
          onChange={(e) => setPriority(parseInt(e.target.value, 10) || 0)}
          min={0}
          className="w-full rounded-lg border border-border bg-background px-4 py-2.5 text-sm text-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 transition-colors"
        />
        <p className="text-xs text-muted-foreground">
          Lower number = higher priority. Rules are matched from lowest to
          highest.
        </p>
      </div>

      {/* Paused Toggle */}
      <div className="space-y-2">
        <label className="block text-sm font-medium text-foreground">
          Status
        </label>
        <div className="flex items-center gap-3">
          <button
            type="button"
            role="switch"
            aria-checked={!isPaused}
            onClick={() => setIsPaused(!isPaused)}
            className={`relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary/20 focus:ring-offset-2 ${
              !isPaused ? "bg-emerald-500" : "bg-muted-foreground/30"
            }`}
          >
            <span
              className={`pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out ${
                !isPaused ? "translate-x-5" : "translate-x-0"
              }`}
            />
          </button>
          <span className="text-sm text-muted-foreground">
            {isPaused ? "Paused" : "Active"}
          </span>
          <input
            type="hidden"
            name="is_paused"
            value={isPaused ? "on" : "off"}
          />
        </div>
      </div>

      {/* Submit */}
      <div className="flex items-center gap-3 pt-2">
        <button
          type="submit"
          disabled={isPending}
          className="inline-flex items-center gap-2 rounded-lg bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50 disabled:cursor-not-allowed transition-colors shadow-sm"
        >
          {isPending && <Loader2 className="h-4 w-4 animate-spin" />}
          {initialData ? "Update Rule" : "Create Rule"}
        </button>
      </div>
    </form>
  );
}
