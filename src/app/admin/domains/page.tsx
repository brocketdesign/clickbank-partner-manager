import { getDb } from "@/lib/mongodb";
import { formatDate } from "@/lib/utils";
import { Globe, Plus, Pencil, Power } from "lucide-react";
import Link from "next/link";
import { toggleDomain, deleteDomain } from "./actions";
import { revalidatePath } from "next/cache";
import { DeleteDomainButton } from "./DeleteDomainButton";

export default async function DomainsPage() {
  const db = await getDb();

  const domains = await db
    .collection("domains")
    .find()
    .sort({ created_at: -1 })
    .toArray();

  const count = domains.length;

  return (
    <div className="space-y-6 animate-in">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-3">
          <div className="flex items-center justify-center h-10 w-10 rounded-xl bg-gradient-to-br from-sky-500 to-blue-600 text-white shadow-sm">
            <Globe className="h-5 w-5" />
          </div>
          <div>
            <h1 className="text-2xl font-bold text-foreground">Domains</h1>
            <p className="text-sm text-muted-foreground">{count} domain{count !== 1 ? "s" : ""}</p>
          </div>
        </div>
        <Link
          href="/admin/domains/new"
          className="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors shadow-sm"
        >
          <Plus className="h-4 w-4" />
          Add Domain
        </Link>
      </div>

      {/* Table */}
      <div className="rounded-xl bg-card border border-border overflow-hidden shadow-sm">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="bg-muted/50 text-left">
                <th className="px-6 py-3 font-medium text-muted-foreground whitespace-nowrap">Domain Name</th>
                <th className="px-6 py-3 font-medium text-muted-foreground whitespace-nowrap">Status</th>
                <th className="px-6 py-3 font-medium text-muted-foreground whitespace-nowrap">Created</th>
                <th className="px-6 py-3 font-medium text-muted-foreground whitespace-nowrap">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-border">
              {domains.length === 0 ? (
                <tr>
                  <td colSpan={4} className="px-6 py-16 text-center">
                    <Globe className="h-10 w-10 text-muted-foreground/40 mx-auto mb-3" />
                    <p className="text-muted-foreground font-medium">No domains yet</p>
                    <p className="text-sm text-muted-foreground/60 mt-1">
                      Add your first domain to get started
                    </p>
                    <Link
                      href="/admin/domains/new"
                      className="inline-flex items-center gap-2 mt-4 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors"
                    >
                      <Plus className="h-4 w-4" />
                      Add Domain
                    </Link>
                  </td>
                </tr>
              ) : (
                domains.map((domain) => {
                  const domainId = domain._id.toString();
                  return (
                    <tr key={domainId} className="hover:bg-muted/30 transition-colors">
                      <td className="px-6 py-3.5 whitespace-nowrap font-medium text-foreground">
                        {domain.domain_name}
                      </td>
                      <td className="px-6 py-3.5 whitespace-nowrap">
                        {domain.is_active ? (
                          <span className="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-emerald-50 text-emerald-700">
                            Active
                          </span>
                        ) : (
                          <span className="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-gray-100 text-gray-600">
                            Inactive
                          </span>
                        )}
                      </td>
                      <td className="px-6 py-3.5 whitespace-nowrap text-muted-foreground">
                        {domain.created_at ? formatDate(domain.created_at) : "—"}
                      </td>
                      <td className="px-6 py-3.5 whitespace-nowrap">
                        <div className="flex items-center gap-1.5">
                          {/* Edit */}
                          <Link
                            href={`/admin/domains/${domainId}`}
                            className="inline-flex items-center justify-center h-8 w-8 rounded-lg text-muted-foreground hover:text-foreground hover:bg-muted transition-colors"
                            title="Edit domain"
                          >
                            <Pencil className="h-4 w-4" />
                          </Link>

                          {/* Toggle Active */}
                          <form
                            action={async () => {
                              "use server";
                              await toggleDomain(domainId);
                              revalidatePath("/admin/domains");
                            }}
                          >
                            <button
                              type="submit"
                              className={`inline-flex items-center justify-center h-8 w-8 rounded-lg transition-colors ${
                                domain.is_active
                                  ? "text-emerald-600 hover:bg-emerald-50 hover:text-emerald-700"
                                  : "text-muted-foreground hover:bg-muted hover:text-foreground"
                              }`}
                              title={domain.is_active ? "Deactivate domain" : "Activate domain"}
                            >
                              <Power className="h-4 w-4" />
                            </button>
                          </form>

                          {/* Delete */}
                          <DeleteDomainButton domainId={domainId} />
                        </div>
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
