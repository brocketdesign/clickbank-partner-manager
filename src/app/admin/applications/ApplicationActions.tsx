"use client";

import { useState, useTransition } from "react";
import { CheckCircle, XCircle, MessageCircle, Loader2, X } from "lucide-react";
import { toast } from "sonner";
import { approveApplication, rejectApplication, requestInfo } from "./actions";

interface ApplicationActionsProps {
  applicationId: string;
  currentStatus: string;
}

export function ApplicationActions({ applicationId, currentStatus }: ApplicationActionsProps) {
  const [isPending, startTransition] = useTransition();
  const [modalType, setModalType] = useState<"reject" | "request_info" | null>(null);
  const [modalText, setModalText] = useState("");

  function handleApprove() {
    startTransition(async () => {
      try {
        await approveApplication(applicationId);
        toast.success("Application approved successfully");
      } catch {
        toast.error("Failed to approve application");
      }
    });
  }

  function handleModalSubmit() {
    if (!modalText.trim()) {
      toast.error(modalType === "reject" ? "Please provide a reason" : "Please enter a message");
      return;
    }

    startTransition(async () => {
      try {
        if (modalType === "reject") {
          await rejectApplication(applicationId, modalText.trim());
          toast.success("Application rejected");
        } else {
          await requestInfo(applicationId, modalText.trim());
          toast.success("Info request sent");
        }
        setModalType(null);
        setModalText("");
      } catch {
        toast.error("Action failed. Please try again.");
      }
    });
  }

  function closeModal() {
    if (!isPending) {
      setModalType(null);
      setModalText("");
    }
  }

  const showApprove = currentStatus !== "approved";
  const showReject = currentStatus !== "rejected";
  const showRequestInfo = currentStatus !== "info_requested";

  return (
    <>
      <div className="rounded-xl bg-card border border-border shadow-sm overflow-hidden">
        <div className="px-6 py-4 border-b border-border bg-muted/30">
          <h2 className="text-sm font-semibold text-foreground">Actions</h2>
        </div>
        <div className="px-6 py-4 flex flex-wrap gap-3">
          {showApprove && (
            <button
              onClick={handleApprove}
              disabled={isPending}
              className="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors shadow-sm"
            >
              {isPending ? (
                <Loader2 className="h-4 w-4 animate-spin" />
              ) : (
                <CheckCircle className="h-4 w-4" />
              )}
              Approve
            </button>
          )}

          {showReject && (
            <button
              onClick={() => setModalType("reject")}
              disabled={isPending}
              className="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors shadow-sm"
            >
              <XCircle className="h-4 w-4" />
              Reject
            </button>
          )}

          {showRequestInfo && (
            <button
              onClick={() => setModalType("request_info")}
              disabled={isPending}
              className="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors shadow-sm"
            >
              <MessageCircle className="h-4 w-4" />
              Request Info
            </button>
          )}

          {!showApprove && !showReject && !showRequestInfo && (
            <p className="text-sm text-muted-foreground py-1">No actions available for this status.</p>
          )}
        </div>
      </div>

      {/* Modal Overlay */}
      {modalType && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
          {/* Backdrop */}
          <div
            className="absolute inset-0 bg-black/50 backdrop-blur-sm"
            onClick={closeModal}
          />

          {/* Dialog */}
          <div className="relative w-full max-w-md rounded-xl bg-card border border-border shadow-xl animate-in">
            <div className="flex items-center justify-between px-6 py-4 border-b border-border">
              <h3 className="text-base font-semibold text-foreground">
                {modalType === "reject" ? "Reject Application" : "Request Information"}
              </h3>
              <button
                onClick={closeModal}
                disabled={isPending}
                className="rounded-lg p-1 text-muted-foreground hover:text-foreground hover:bg-muted transition-colors"
              >
                <X className="h-4 w-4" />
              </button>
            </div>

            <div className="px-6 py-4 space-y-4">
              <div>
                <label
                  htmlFor="modal-text"
                  className="block text-sm font-medium text-foreground mb-1.5"
                >
                  {modalType === "reject" ? "Rejection Reason" : "Message to Applicant"}
                </label>
                <textarea
                  id="modal-text"
                  value={modalText}
                  onChange={(e) => setModalText(e.target.value)}
                  placeholder={
                    modalType === "reject"
                      ? "Explain why the application is being rejected..."
                      : "What additional information do you need?"
                  }
                  rows={4}
                  disabled={isPending}
                  className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent resize-none disabled:opacity-50"
                />
              </div>
            </div>

            <div className="flex items-center justify-end gap-2 px-6 py-4 border-t border-border bg-muted/30">
              <button
                onClick={closeModal}
                disabled={isPending}
                className="rounded-lg px-4 py-2 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted transition-colors disabled:opacity-50"
              >
                Cancel
              </button>
              <button
                onClick={handleModalSubmit}
                disabled={isPending}
                className={`inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed ${
                  modalType === "reject"
                    ? "bg-red-600 hover:bg-red-700"
                    : "bg-blue-600 hover:bg-blue-700"
                }`}
              >
                {isPending && <Loader2 className="h-4 w-4 animate-spin" />}
                {modalType === "reject" ? "Reject" : "Send Request"}
              </button>
            </div>
          </div>
        </div>
      )}
    </>
  );
}
