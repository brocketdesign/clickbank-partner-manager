import { getDb } from "@/lib/mongodb";
import { formatDate } from "@/lib/utils";
import { FileText, Eye, CheckCircle, ShieldCheck } from "lucide-react";
import Link from "next/link";

interface Props {
  searchParams: Promise<{ status?: string }>;
}

const STATUS_CONFIG: Record<string, { label: string; bg: string; text: string }> = {
  pending: { label: "Pending", bg: "bg-amber-50", text: "text-amber-700" },
  approved: { label: "Approved", bg: "bg-emerald-50", text: "text-emerald-700" },
  rejected: { label: "Rejected", bg: "bg-red-50", text: "text-red-700" },
  info_requested: { label: "Info Requested", bg: "bg-blue-50", text: "text-blue-700" },
};

const TABS = [
  { key: "all", label: "All" },
  { key: "pending", label: "Pending" },
  { key: "approved", label: "Approved" },
  { key: "rejected", label: "Rejected" },
];

export default async function ApplicationsPage({ searchParams }: Props) {
  const params = await searchParams;
  const filterStatus = params.status ?? "all";
  const db = await getDb();

  const query: Record<string, unknown> = {};
  if (filterStatus !== "all") {
    query.status = filterStatus;
  }

  const applications = await db
    .collection("partner_applications")
    .find(query)
    .sort({ created_at: -1 })
    .toArray();

  const totalCount = await db.collection("partner_applications").countDocuments();

  return (
    <div className="space-y-6 animate-in">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-3">
          <div className="flex items-center justify-center h-10 w-10 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 text-white shadow-sm">
            <FileText className="h-5 w-5" />
          </div>
          <div>
            <h1 className="text-2xl font-bold text-foreground">Applications</h1>
            <p className="text-sm text-muted-foreground">{totalCount} total applications</p>
          </div>
        </div>
      </div>

      {/* Filter Tabs */}
      <div className="flex gap-2">
        {TABS.map((tab) => {
          const isActive = filterStatus === tab.key;
          return (
            <Link
              key={tab.key}
              href={tab.key === "all" ? "/admin/applications" : `/admin/applications?status=${tab.key}`}
              className={`px-4 py-2 rounded-lg text-sm font-medium transition-all ${
                isActive
                  ? "bg-primary text-primary-foreground shadow-sm"
                  : "bg-muted text-muted-foreground hover:bg-muted/80"
              }`}
            >
              {tab.label}
            </Link>
          );
        })}
      </div>

      {/* Table */}
      <div className="rounded-xl bg-card border border-border overflow-hidden shadow-sm">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="bg-muted/50 text-left">
                <th className="px-6 py-3 font-medium text-muted-foreground whitespace-nowrap">Name</th>
                <th className="px-6 py-3 font-medium text-muted-foreground whitespace-nowrap">Email</th>
                <th className="px-6 py-3 font-medium text-muted-foreground whitespace-nowrap">Blog URL</th>
                <th className="px-6 py-3 font-medium text-muted-foreground whitespace-nowrap">Traffic</th>
                <th className="px-6 py-3 font-medium text-muted-foreground whitespace-nowrap">Verified</th>
                <th className="px-6 py-3 font-medium text-muted-foreground whitespace-nowrap">Status</th>
                <th className="px-6 py-3 font-medium text-muted-foreground whitespace-nowrap">Submitted</th>
                <th className="px-6 py-3 font-medium text-muted-foreground whitespace-nowrap">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-border">
              {applications.length === 0 ? (
                <tr>
                  <td colSpan={8} className="px-6 py-16 text-center">
                    <FileText className="h-10 w-10 text-muted-foreground/40 mx-auto mb-3" />
                    <p className="text-muted-foreground font-medium">No applications found</p>
                    <p className="text-sm text-muted-foreground/60 mt-1">
                      {filterStatus !== "all" ? "Try changing the filter" : "Applications will appear here when submitted"}
                    </p>
                  </td>
                </tr>
              ) : (
                applications.map((app) => {
                  const st = STATUS_CONFIG[app.status] ?? STATUS_CONFIG.pending;
                  return (
                    <tr key={app._id.toString()} className="hover:bg-muted/30 transition-colors">
                      <td className="px-6 py-3.5 whitespace-nowrap font-medium text-foreground">
                        {app.name}
                      </td>
                      <td className="px-6 py-3.5 whitespace-nowrap">
                        <a href={`mailto:${app.email}`} className="text-primary hover:underline">
                          {app.email}
                        </a>
                      </td>
                      <td className="px-6 py-3.5 whitespace-nowrap">
                        <a
                          href={app.blog_url?.startsWith("http") ? app.blog_url : `https://${app.blog_url}`}
                          target="_blank"
                          rel="noopener noreferrer"
                          className="text-primary hover:underline max-w-[200px] truncate inline-block"
                        >
                          {app.blog_url}
                        </a>
                      </td>
                      <td className="px-6 py-3.5 whitespace-nowrap text-muted-foreground">
                        {app.traffic_estimate || "—"}
                      </td>
                      <td className="px-6 py-3.5 whitespace-nowrap">
                        {app.domain_verified ? (
                          <ShieldCheck className="h-4.5 w-4.5 text-emerald-500" />
                        ) : (
                          <span className="text-muted-foreground/50 text-xs">—</span>
                        )}
                      </td>
                      <td className="px-6 py-3.5 whitespace-nowrap">
                        <span className={`inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium ${st.bg} ${st.text}`}>
                          {st.label}
                        </span>
                      </td>
                      <td className="px-6 py-3.5 whitespace-nowrap text-muted-foreground">
                        {app.created_at ? formatDate(app.created_at) : "—"}
                      </td>
                      <td className="px-6 py-3.5 whitespace-nowrap">
                        <Link
                          href={`/admin/applications/${app._id.toString()}`}
                          className="inline-flex items-center gap-1.5 rounded-lg bg-primary/10 px-3 py-1.5 text-xs font-medium text-primary hover:bg-primary/20 transition-colors"
                        >
                          <Eye className="h-3.5 w-3.5" />
                          View
                        </Link>
                      </td>
                    </tr>
                  );
                })
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
