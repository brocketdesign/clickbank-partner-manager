"use client";

import { useState, useTransition } from "react";
import { Loader2 } from "lucide-react";
import { toast } from "sonner";
import { saveDomain } from "./actions";

interface DomainFormProps {
  initialData?: {
    _id: string;
    domain_name: string;
    is_active: boolean;
  };
}

export default function DomainForm({ initialData }: DomainFormProps) {
  const [domainName, setDomainName] = useState(initialData?.domain_name ?? "");
  const [isActive, setIsActive] = useState(initialData?.is_active ?? true);
  const [isPending, startTransition] = useTransition();

  function handleSubmit(formData: FormData) {
    startTransition(async () => {
      try {
        await saveDomain(formData);
        toast.success(initialData ? "Domain updated" : "Domain created");
      } catch {
        toast.error("Failed to save domain. Please try again.");
      }
    });
  }

  return (
    <form action={handleSubmit} className="space-y-6 max-w-lg">
      {initialData && <input type="hidden" name="id" value={initialData._id} />}

      {/* Domain Name */}
      <div className="space-y-2">
        <label htmlFor="domain_name" className="block text-sm font-medium text-foreground">
          Domain Name
        </label>
        <input
          type="text"
          id="domain_name"
          name="domain_name"
          value={domainName}
          onChange={(e) => setDomainName(e.target.value)}
          required
          placeholder="example.com"
          className="w-full rounded-lg border border-border bg-background px-4 py-2.5 text-sm text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 transition-colors"
        />
        <p className="text-xs text-muted-foreground">
          Enter the domain without http:// or https:// — it will be stripped automatically.
        </p>
      </div>

      {/* Active Toggle */}
      <div className="space-y-2">
        <label className="block text-sm font-medium text-foreground">Status</label>
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
          <input type="hidden" name="is_active" value={isActive ? "on" : "off"} />
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
          {initialData ? "Update Domain" : "Create Domain"}
        </button>
      </div>
    </form>
  );
}
