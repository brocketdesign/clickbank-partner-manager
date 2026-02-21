"use client";

import { useTransition } from "react";
import { Pause, Play, Loader2 } from "lucide-react";
import { toast } from "sonner";
import { togglePauseRule } from "./actions";

interface TogglePauseButtonProps {
  id: string;
  isPaused: boolean;
}

export function TogglePauseButton({ id, isPaused }: TogglePauseButtonProps) {
  const [isPending, startTransition] = useTransition();

  function handleToggle() {
    startTransition(async () => {
      try {
        await togglePauseRule(id);
        toast.success(isPaused ? "Rule activated" : "Rule paused");
      } catch {
        toast.error("Failed to toggle rule status");
      }
    });
  }

  return (
    <button
      type="button"
      onClick={handleToggle}
      disabled={isPending}
      className={`inline-flex items-center justify-center h-8 w-8 rounded-lg transition-colors disabled:opacity-50 ${
        isPaused
          ? "text-muted-foreground hover:bg-muted hover:text-foreground"
          : "text-emerald-600 hover:bg-emerald-50 hover:text-emerald-700"
      }`}
      title={isPaused ? "Activate rule" : "Pause rule"}
    >
      {isPending ? (
        <Loader2 className="h-4 w-4 animate-spin" />
      ) : isPaused ? (
        <Play className="h-4 w-4" />
      ) : (
        <Pause className="h-4 w-4" />
      )}
    </button>
  );
}
