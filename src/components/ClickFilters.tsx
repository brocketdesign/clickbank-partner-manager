"use client";

import { useRouter, useSearchParams } from "next/navigation";
import { useCallback } from "react";
import { Search, X, Filter } from "lucide-react";

interface FilterOption {
  id: string;
  name: string;
}

interface ClickFiltersProps {
  domains: FilterOption[];
  partners: FilterOption[];
  offers: FilterOption[];
}

export function ClickFilters({ domains, partners, offers }: ClickFiltersProps) {
  const router = useRouter();
  const searchParams = useSearchParams();

  const domainId = searchParams.get("domain") ?? "";
  const partnerId = searchParams.get("partner") ?? "";
  const offerId = searchParams.get("offer") ?? "";
  const date = searchParams.get("date") ?? "";

  const activeCount = [domainId, partnerId, offerId, date].filter(Boolean).length;

  const pushParams = useCallback(
    (key: string, value: string) => {
      const params = new URLSearchParams(searchParams.toString());
      if (value) {
        params.set(key, value);
      } else {
        params.delete(key);
      }
      params.delete("page");
      router.push(`/admin/clicks?${params.toString()}`);
    },
    [router, searchParams]
  );

  const clearAll = useCallback(() => {
    router.push("/admin/clicks");
  }, [router]);

  return (
    <div className="space-y-3">
      <div className="flex flex-wrap items-center gap-3">
        <div className="flex items-center gap-2 text-sm font-medium text-muted-foreground">
          <Filter className="h-4 w-4" />
          Filters
          {activeCount > 0 && (
            <span className="inline-flex items-center justify-center h-5 w-5 rounded-full bg-primary text-primary-foreground text-xs font-bold">
              {activeCount}
            </span>
          )}
        </div>

        <select
          value={domainId}
          onChange={(e) => pushParams("domain", e.target.value)}
          className="h-9 rounded-lg border border-border bg-white px-3 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring/30 transition-colors"
        >
          <option value="">All Domains</option>
          {domains.map((d) => (
            <option key={d.id} value={d.id}>
              {d.name}
            </option>
          ))}
        </select>

        <select
          value={partnerId}
          onChange={(e) => pushParams("partner", e.target.value)}
          className="h-9 rounded-lg border border-border bg-white px-3 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring/30 transition-colors"
        >
          <option value="">All Partners</option>
          {partners.map((p) => (
            <option key={p.id} value={p.id}>
              {p.name}
            </option>
          ))}
        </select>

        <select
          value={offerId}
          onChange={(e) => pushParams("offer", e.target.value)}
          className="h-9 rounded-lg border border-border bg-white px-3 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring/30 transition-colors"
        >
          <option value="">All Offers</option>
          {offers.map((o) => (
            <option key={o.id} value={o.id}>
              {o.name}
            </option>
          ))}
        </select>

        <div className="relative">
          <input
            type="date"
            value={date}
            onChange={(e) => pushParams("date", e.target.value)}
            className="h-9 rounded-lg border border-border bg-white px-3 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-ring/30 transition-colors"
          />
        </div>

        {activeCount > 0 && (
          <button
            onClick={clearAll}
            className="inline-flex items-center gap-1.5 h-9 rounded-lg border border-border bg-white px-3 text-sm text-muted-foreground hover:text-foreground hover:bg-secondary transition-colors"
          >
            <X className="h-3.5 w-3.5" />
            Clear
          </button>
        )}
      </div>

      {activeCount > 0 && (
        <div className="flex flex-wrap gap-2">
          {domainId && (
            <span className="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700">
              <Search className="h-3 w-3" />
              Domain: {domains.find((d) => d.id === domainId)?.name ?? domainId}
              <button
                onClick={() => pushParams("domain", "")}
                className="ml-0.5 hover:text-blue-900"
              >
                <X className="h-3 w-3" />
              </button>
            </span>
          )}
          {partnerId && (
            <span className="inline-flex items-center gap-1.5 rounded-full bg-green-50 px-3 py-1 text-xs font-medium text-green-700">
              <Search className="h-3 w-3" />
              Partner:{" "}
              {partners.find((p) => p.id === partnerId)?.name ?? partnerId}
              <button
                onClick={() => pushParams("partner", "")}
                className="ml-0.5 hover:text-green-900"
              >
                <X className="h-3 w-3" />
              </button>
            </span>
          )}
          {offerId && (
            <span className="inline-flex items-center gap-1.5 rounded-full bg-purple-50 px-3 py-1 text-xs font-medium text-purple-700">
              <Search className="h-3 w-3" />
              Offer: {offers.find((o) => o.id === offerId)?.name ?? offerId}
              <button
                onClick={() => pushParams("offer", "")}
                className="ml-0.5 hover:text-purple-900"
              >
                <X className="h-3 w-3" />
              </button>
            </span>
          )}
          {date && (
            <span className="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-xs font-medium text-amber-700">
              <Search className="h-3 w-3" />
              Date: {date}
              <button
                onClick={() => pushParams("date", "")}
                className="ml-0.5 hover:text-amber-900"
              >
                <X className="h-3 w-3" />
              </button>
            </span>
          )}
        </div>
      )}
    </div>
  );
}
