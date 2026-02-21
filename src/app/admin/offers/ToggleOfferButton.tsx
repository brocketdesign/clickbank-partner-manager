"use client";

import { useTransition } from "react";
import { Power, Loader2 } from "lucide-react";
import { toast } from "sonner";
import { toggleOffer } from "./actions";

interface ToggleOfferButtonProps {
  id: string;
  isActive: boolean;
}

export function ToggleOfferButton({ id, isActive }: ToggleOfferButtonProps) {
  const [isPending, startTransition] = useTransition();

  function handleToggle() {
    startTransition(async () => {
      try {
        await toggleOffer(id);
        toast.success(isActive ? "Offer deactivated" : "Offer activated");
      } catch {
        toast.error("Failed to toggle offer status");
      }
    });
  }

  return (
    <button
      type="button"
      onClick={handleToggle}
      disabled={isPending}
      className={`inline-flex items-center justify-center h-8 w-8 rounded-lg transition-colors disabled:opacity-50 ${
        isActive
          ? "text-emerald-600 hover:bg-emerald-50 hover:text-emerald-700"
          : "text-muted-foreground hover:bg-muted hover:text-foreground"
      }`}
      title={isActive ? "Deactivate offer" : "Activate offer"}
    >
      {isPending ? (
        <Loader2 className="h-4 w-4 animate-spin" />
      ) : (
        <Power className="h-4 w-4" />
      )}
    </button>
  );
}
