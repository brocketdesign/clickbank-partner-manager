"use client";

import { useTransition } from "react";
import { Trash2, Loader2 } from "lucide-react";
import { toast } from "sonner";
import { deleteDomain } from "./actions";

interface DeleteDomainButtonProps {
  domainId: string;
}

export function DeleteDomainButton({ domainId }: DeleteDomainButtonProps) {
  const [isPending, startTransition] = useTransition();

  function handleDelete() {
    if (!confirm("Are you sure you want to delete this domain? This will also remove associated redirect rules.")) {
      return;
    }

    startTransition(async () => {
      try {
        await deleteDomain(domainId);
        toast.success("Domain deleted");
      } catch {
        toast.error("Failed to delete domain");
      }
    });
  }

  return (
    <button
      type="button"
      onClick={handleDelete}
      disabled={isPending}
      className="inline-flex items-center justify-center h-8 w-8 rounded-lg text-muted-foreground hover:text-red-600 hover:bg-red-50 disabled:opacity-50 transition-colors"
      title="Delete domain"
    >
      {isPending ? <Loader2 className="h-4 w-4 animate-spin" /> : <Trash2 className="h-4 w-4" />}
    </button>
  );
}
