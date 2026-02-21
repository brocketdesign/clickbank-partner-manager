"use client";

import { useTransition } from "react";
import { Trash2, Loader2 } from "lucide-react";
import { toast } from "sonner";
import { deleteOffer } from "./actions";

interface DeleteOfferButtonProps {
  id: string;
}

export function DeleteOfferButton({ id }: DeleteOfferButtonProps) {
  const [isPending, startTransition] = useTransition();

  function handleDelete() {
    if (
      !confirm(
        "Are you sure you want to delete this offer? This will also remove associated redirect rules."
      )
    ) {
      return;
    }

    startTransition(async () => {
      try {
        await deleteOffer(id);
        toast.success("Offer deleted");
      } catch {
        toast.error("Failed to delete offer");
      }
    });
  }

  return (
    <button
      type="button"
      onClick={handleDelete}
      disabled={isPending}
      className="inline-flex items-center justify-center h-8 w-8 rounded-lg text-muted-foreground hover:text-red-600 hover:bg-red-50 disabled:opacity-50 transition-colors"
      title="Delete offer"
    >
      {isPending ? (
        <Loader2 className="h-4 w-4 animate-spin" />
      ) : (
        <Trash2 className="h-4 w-4" />
      )}
    </button>
  );
}
