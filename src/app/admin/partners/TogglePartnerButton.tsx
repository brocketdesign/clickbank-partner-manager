"use client";

import { useTransition } from "react";
import { Power, Loader2 } from "lucide-react";
import { toast } from "sonner";
import { togglePartner } from "./actions";

interface TogglePartnerButtonProps {
  partnerId: string;
  isActive: boolean;
}

export function TogglePartnerButton({
  partnerId,
  isActive,
}: TogglePartnerButtonProps) {
  const [isPending, startTransition] = useTransition();

  function handleToggle() {
    startTransition(async () => {
      try {
        await togglePartner(partnerId);
        toast.success(isActive ? "Partner deactivated" : "Partner activated");
      } catch {
        toast.error("Failed to toggle partner status");
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
      title={isActive ? "Deactivate partner" : "Activate partner"}
    >
      {isPending ? (
        <Loader2 className="h-4 w-4 animate-spin" />
      ) : (
        <Power className="h-4 w-4" />
      )}
    </button>
  );
}
