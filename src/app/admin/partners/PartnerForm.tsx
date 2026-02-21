"use client";

import { useState, useTransition } from "react";
import { Loader2, RefreshCw } from "lucide-react";
import { toast } from "sonner";
import { savePartner } from "./actions";

interface PartnerFormProps {
  initialData?: {
    _id: string;
    aff_id: string;
    partner_name: string;
    is_active: boolean;
  };
}

function generateRandomHex(): string {
  const bytes = new Uint8Array(8);
  crypto.getRandomValues(bytes);
  return Array.from(bytes)
    .map((b) => b.toString(16).padStart(2, "0"))
    .join("");
}

export default function PartnerForm({ initialData }: PartnerFormProps) {
  const [affId, setAffId] = useState(initialData?.aff_id ?? "");
  const [partnerName, setPartnerName] = useState(
    initialData?.partner_name ?? ""
  );
  const [isActive, setIsActive] = useState(initialData?.is_active ?? true);
  const [isPending, startTransition] = useTransition();

  function handleGenerateAffId() {
    setAffId(generateRandomHex());
  }

  function handleSubmit(formData: FormData) {
    startTransition(async () => {
      try {
        await savePartner(formData);
        toast.success(initialData ? "Partner updated" : "Partner created");
      } catch (err) {
        const message =
          err instanceof Error ? err.message : "Failed to save partner";
        toast.error(message);
      }
    });
  }

  return (
    <form action={handleSubmit} className="space-y-6 max-w-lg">
      {initialData && (
        <input type="hidden" name="id" value={initialData._id} />
      )}

      {/* Affiliate ID */}
      <div className="space-y-2">
        <label
          htmlFor="aff_id"
          className="block text-sm font-medium text-foreground"
        >
          Affiliate ID
        </label>
        <div className="flex items-center gap-2">
          <input
            type="text"
            id="aff_id"
            name="aff_id"
            value={affId}
            onChange={(e) => setAffId(e.target.value)}
            required
            placeholder="e.g. a1b2c3d4e5f6a7b8"
            className="flex-1 rounded-lg border border-border bg-background px-4 py-2.5 text-sm font-mono text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 transition-colors"
          />
          <button
            type="button"
            onClick={handleGenerateAffId}
            className="inline-flex items-center gap-1.5 rounded-lg border border-border bg-muted/50 px-3 py-2.5 text-sm font-medium text-foreground hover:bg-muted transition-colors"
            title="Generate random Affiliate ID"
          >
            <RefreshCw className="h-4 w-4" />
            Generate
          </button>
        </div>
        <p className="text-xs text-muted-foreground">
          Unique identifier used for tracking. Click Generate for a random
          16-char hex string.
        </p>
      </div>

      {/* Partner Name */}
      <div className="space-y-2">
        <label
          htmlFor="partner_name"
          className="block text-sm font-medium text-foreground"
        >
          Partner Name
        </label>
        <input
          type="text"
          id="partner_name"
          name="partner_name"
          value={partnerName}
          onChange={(e) => setPartnerName(e.target.value)}
          required
          placeholder="e.g. John's Health Blog"
          className="w-full rounded-lg border border-border bg-background px-4 py-2.5 text-sm text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 transition-colors"
        />
      </div>

      {/* Active Toggle */}
      <div className="space-y-2">
        <label className="block text-sm font-medium text-foreground">
          Status
        </label>
        <div className="flex items-center gap-3">
          <button
            type="button"
            role="switch"
            aria-checked={isActive}
            onClick={() => setIsActive(!isActive)}
            className={`relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary/20 focus:ring-offset-2 ${
              isActive ? "bg-emerald-500" : "bg-muted-foreground/30"
            }`}
          >
            <span
              className={`pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out ${
                isActive ? "translate-x-5" : "translate-x-0"
              }`}
            />
          </button>
          <span className="text-sm text-muted-foreground">
            {isActive ? "Active" : "Inactive"}
          </span>
          <input
            type="hidden"
            name="is_active"
            value={isActive ? "on" : "off"}
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
          {initialData ? "Update Partner" : "Create Partner"}
        </button>
      </div>
    </form>
  );
}
