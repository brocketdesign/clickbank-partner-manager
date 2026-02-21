"use client";

import { useState, useTransition } from "react";
import { Loader2 } from "lucide-react";
import { toast } from "sonner";
import { saveOffer } from "./actions";

interface OfferFormProps {
  initialData?: {
    _id: string;
    offer_name: string;
    clickbank_vendor: string;
    clickbank_hoplink: string;
    is_active: boolean;
  };
}

export default function OfferForm({ initialData }: OfferFormProps) {
  const [offerName, setOfferName] = useState(initialData?.offer_name ?? "");
  const [clickbankVendor, setClickbankVendor] = useState(
    initialData?.clickbank_vendor ?? ""
  );
  const [clickbankHoplink, setClickbankHoplink] = useState(
    initialData?.clickbank_hoplink ?? ""
  );
  const [isActive, setIsActive] = useState(initialData?.is_active ?? true);
  const [isPending, startTransition] = useTransition();

  function handleSubmit(formData: FormData) {
    startTransition(async () => {
      try {
        await saveOffer(formData);
        toast.success(initialData ? "Offer updated" : "Offer created");
      } catch (err) {
        const message =
          err instanceof Error ? err.message : "Failed to save offer";
        toast.error(message);
      }
    });
  }

  return (
    <form action={handleSubmit} className="space-y-6 max-w-lg">
      {initialData && (
        <input type="hidden" name="id" value={initialData._id} />
      )}

      {/* Offer Name */}
      <div className="space-y-2">
        <label
          htmlFor="offer_name"
          className="block text-sm font-medium text-foreground"
        >
          Offer Name
        </label>
        <input
          type="text"
          id="offer_name"
          name="offer_name"
          value={offerName}
          onChange={(e) => setOfferName(e.target.value)}
          required
          placeholder="e.g. Keto Weight Loss Guide"
          className="w-full rounded-lg border border-border bg-background px-4 py-2.5 text-sm text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 transition-colors"
        />
      </div>

      {/* ClickBank Vendor */}
      <div className="space-y-2">
        <label
          htmlFor="clickbank_vendor"
          className="block text-sm font-medium text-foreground"
        >
          ClickBank Vendor
        </label>
        <input
          type="text"
          id="clickbank_vendor"
          name="clickbank_vendor"
          value={clickbankVendor}
          onChange={(e) => setClickbankVendor(e.target.value)}
          required
          placeholder="e.g. ketovendor"
          className="w-full rounded-lg border border-border bg-background px-4 py-2.5 text-sm font-mono text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 transition-colors"
        />
        <p className="text-xs text-muted-foreground">
          The ClickBank vendor/merchant nickname for this offer.
        </p>
      </div>

      {/* ClickBank Hoplink */}
      <div className="space-y-2">
        <label
          htmlFor="clickbank_hoplink"
          className="block text-sm font-medium text-foreground"
        >
          ClickBank Hoplink
        </label>
        <input
          type="url"
          id="clickbank_hoplink"
          name="clickbank_hoplink"
          value={clickbankHoplink}
          onChange={(e) => setClickbankHoplink(e.target.value)}
          required
          placeholder="https://hop.clickbank.net/?affiliate=AFF&vendor=VENDOR"
          className="w-full rounded-lg border border-border bg-background px-4 py-2.5 text-sm text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 transition-colors"
        />
        <p className="text-xs text-muted-foreground">
          The full ClickBank hoplink URL for this offer.
        </p>
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
          {initialData ? "Update Offer" : "Create Offer"}
        </button>
      </div>
    </form>
  );
}
