import { getDb } from "@/lib/mongodb";
import { ObjectId } from "mongodb";
import { notFound } from "next/navigation";
import { formatDate, formatDateTime } from "@/lib/utils";
import {
  ArrowLeft,
  FileText,
  Mail,
  Globe,
  BarChart3,
  ShieldCheck,
  ShieldX,
  Clock,
  MapPin,
  MessageSquare,
  CheckCircle,
  XCircle,
  Info,
} from "lucide-react";
import Link from "next/link";
import { ApplicationActions } from "../ApplicationActions";

interface Props {
  params: Promise<{ id: string }>;
}

const STATUS_CONFIG: Record<
  string,
  { label: string; bg: string; text: string; icon: React.ComponentType<{ className?: string }> }
> = {
  pending: { label: "Pending", bg: "bg-amber-50", text: "text-amber-700", icon: Clock },
  approved: { label: "Approved", bg: "bg-emerald-50", text: "text-emerald-700", icon: CheckCircle },
  rejected: { label: "Rejected", bg: "bg-red-50", text: "text-red-700", icon: XCircle },
  info_requested: { label: "Info Requested", bg: "bg-blue-50", text: "text-blue-700", icon: Info },
};

const MSG_TYPE_CONFIG: Record<string, { label: string; bg: string; text: string; icon: React.ComponentType<{ className?: string }> }> = {
  approve: { label: "Approved", bg: "bg-emerald-50", text: "text-emerald-700", icon: CheckCircle },
  reject: { label: "Rejected", bg: "bg-red-50", text: "text-red-700", icon: XCircle },
  request_info: { label: "Info Requested", bg: "bg-blue-50", text: "text-blue-700", icon: Info },
};

export default async function ApplicationDetailPage({ params }: Props) {
  const { id } = await params;

  if (!ObjectId.isValid(id)) {
    notFound();
  }

  const db = await getDb();
  const application = await db
    .collection("partner_applications")
    .findOne({ _id: new ObjectId(id) });

  if (!application) {
    notFound();
  }

  const status = STATUS_CONFIG[application.status] ?? STATUS_CONFIG.pending;
  const StatusIcon = status.icon;
  const messages = (application.messages as Array<{
    _id?: unknown;
    message_type: string;
    message_text: string;
    created_at: Date | string;
  }>) ?? [];

  return (
    <div className="space-y-6 animate-in max-w-4xl">
      {/* Back Link */}
      <Link
        href="/admin/applications"
        className="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground transition-colors"
      >
        <ArrowLeft className="h-4 w-4" />
        Back to Applications
      </Link>

      {/* Header */}
      <div className="flex items-start justify-between flex-wrap gap-4">
        <div className="flex items-center gap-3">
          <div className="flex items-center justify-center h-10 w-10 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 text-white shadow-sm">
            <FileText className="h-5 w-5" />
          </div>
          <div>
            <h1 className="text-2xl font-bold text-foreground">{application.name}</h1>
            <p className="text-sm text-muted-foreground">
              Submitted {application.created_at ? formatDate(application.created_at) : "—"}
            </p>
          </div>
        </div>
        <span
          className={`inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-medium ${status.bg} ${status.text}`}
        >
          <StatusIcon className="h-3.5 w-3.5" />
          {status.label}
        </span>
      </div>

      {/* Details Card */}
      <div className="rounded-xl bg-card border border-border shadow-sm overflow-hidden">
        <div className="px-6 py-4 border-b border-border bg-muted/30">
          <h2 className="text-sm font-semibold text-foreground">Application Details</h2>
        </div>
        <div className="px-6 py-5 space-y-5">
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-5">
            {/* Name */}
            <div className="flex items-start gap-3">
              <div className="flex items-center justify-center h-8 w-8 rounded-lg bg-muted shrink-0">
                <FileText className="h-4 w-4 text-muted-foreground" />
              </div>
              <div>
                <p className="text-xs font-medium text-muted-foreground">Full Name</p>
                <p className="text-sm font-medium text-foreground mt-0.5">{application.name}</p>
              </div>
            </div>

            {/* Email */}
            <div className="flex items-start gap-3">
              <div className="flex items-center justify-center h-8 w-8 rounded-lg bg-muted shrink-0">
                <Mail className="h-4 w-4 text-muted-foreground" />
              </div>
              <div>
                <p className="text-xs font-medium text-muted-foreground">Email</p>
                <a
                  href={`mailto:${application.email}`}
                  className="text-sm font-medium text-primary hover:underline mt-0.5 inline-block"
                >
                  {application.email}
                </a>
              </div>
            </div>

            {/* Blog URL */}
            <div className="flex items-start gap-3">
              <div className="flex items-center justify-center h-8 w-8 rounded-lg bg-muted shrink-0">
                <Globe className="h-4 w-4 text-muted-foreground" />
              </div>
              <div>
                <p className="text-xs font-medium text-muted-foreground">Blog URL</p>
                <a
                  href={
                    application.blog_url?.startsWith("http")
                      ? application.blog_url
                      : `https://${application.blog_url}`
                  }
                  target="_blank"
                  rel="noopener noreferrer"
                  className="text-sm font-medium text-primary hover:underline mt-0.5 inline-block break-all"
                >
                  {application.blog_url}
                </a>
              </div>
            </div>

            {/* Traffic */}
            <div className="flex items-start gap-3">
              <div className="flex items-center justify-center h-8 w-8 rounded-lg bg-muted shrink-0">
                <BarChart3 className="h-4 w-4 text-muted-foreground" />
              </div>
              <div>
                <p className="text-xs font-medium text-muted-foreground">Traffic Estimate</p>
                <p className="text-sm font-medium text-foreground mt-0.5">
                  {application.traffic_estimate || "Not provided"}
                </p>
              </div>
            </div>

            {/* Verification */}
            <div className="flex items-start gap-3">
              <div className="flex items-center justify-center h-8 w-8 rounded-lg bg-muted shrink-0">
                {application.domain_verified ? (
                  <ShieldCheck className="h-4 w-4 text-emerald-500" />
                ) : (
                  <ShieldX className="h-4 w-4 text-muted-foreground" />
                )}
              </div>
              <div>
                <p className="text-xs font-medium text-muted-foreground">Domain Verification</p>
                <p className="text-sm font-medium text-foreground mt-0.5">
                  {application.domain_verified ? (
                    <span className="text-emerald-600">Verified</span>
                  ) : (
                    <span className="text-muted-foreground">Not verified</span>
                  )}
                </p>
              </div>
            </div>

            {/* IP Address */}
            <div className="flex items-start gap-3">
              <div className="flex items-center justify-center h-8 w-8 rounded-lg bg-muted shrink-0">
                <MapPin className="h-4 w-4 text-muted-foreground" />
              </div>
              <div>
                <p className="text-xs font-medium text-muted-foreground">IP Address</p>
                <p className="text-sm font-medium text-foreground mt-0.5 font-mono">
                  {application.ip || "Unknown"}
                </p>
              </div>
            </div>
          </div>

          {/* Notes */}
          {application.notes && (
            <div className="border-t border-border pt-5">
              <div className="flex items-start gap-3">
                <div className="flex items-center justify-center h-8 w-8 rounded-lg bg-muted shrink-0">
                  <MessageSquare className="h-4 w-4 text-muted-foreground" />
                </div>
                <div>
                  <p className="text-xs font-medium text-muted-foreground">Notes</p>
                  <p className="text-sm text-foreground mt-0.5 whitespace-pre-wrap leading-relaxed">
                    {application.notes}
                  </p>
                </div>
              </div>
            </div>
          )}

          {/* Timestamps */}
          <div className="border-t border-border pt-5">
            <div className="flex items-start gap-3">
              <div className="flex items-center justify-center h-8 w-8 rounded-lg bg-muted shrink-0">
                <Clock className="h-4 w-4 text-muted-foreground" />
              </div>
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <p className="text-xs font-medium text-muted-foreground">Created</p>
                  <p className="text-sm text-foreground mt-0.5">
                    {application.created_at ? formatDateTime(application.created_at) : "—"}
                  </p>
                </div>
                {application.updated_at && (
                  <div>
                    <p className="text-xs font-medium text-muted-foreground">Last Updated</p>
                    <p className="text-sm text-foreground mt-0.5">
                      {formatDateTime(application.updated_at)}
                    </p>
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Actions */}
      <ApplicationActions
        applicationId={application._id.toString()}
        currentStatus={application.status}
      />

      {/* Messages History */}
      {messages.length > 0 && (
        <div className="rounded-xl bg-card border border-border shadow-sm overflow-hidden">
          <div className="px-6 py-4 border-b border-border bg-muted/30">
            <h2 className="text-sm font-semibold text-foreground flex items-center gap-2">
              <MessageSquare className="h-4 w-4 text-muted-foreground" />
              Messages History
              <span className="text-xs text-muted-foreground font-normal">({messages.length})</span>
            </h2>
          </div>
          <div className="divide-y divide-border">
            {messages.map((msg, idx) => {
              const msgConfig = MSG_TYPE_CONFIG[msg.message_type] ?? {
                label: msg.message_type,
                bg: "bg-muted",
                text: "text-muted-foreground",
                icon: MessageSquare,
              };
              const MsgIcon = msgConfig.icon;
              return (
                <div key={idx} className="px-6 py-4 flex items-start gap-3">
                  <div
                    className={`flex items-center justify-center h-8 w-8 rounded-lg shrink-0 ${msgConfig.bg}`}
                  >
                    <MsgIcon className={`h-4 w-4 ${msgConfig.text}`} />
                  </div>
                  <div className="min-w-0 flex-1">
                    <div className="flex items-center gap-2 flex-wrap">
                      <span
                        className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${msgConfig.bg} ${msgConfig.text}`}
                      >
                        {msgConfig.label}
                      </span>
                      <span className="text-xs text-muted-foreground">
                        {msg.created_at ? formatDateTime(msg.created_at) : ""}
                      </span>
                    </div>
                    <p className="text-sm text-foreground mt-1 whitespace-pre-wrap">
                      {msg.message_text}
                    </p>
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      )}
    </div>
  );
}
