import { getDb } from "@/lib/mongodb";
import { formatDate } from "@/lib/utils";
import { Users, Plus, Pencil } from "lucide-react";
import Link from "next/link";
import { TogglePartnerButton } from "./TogglePartnerButton";
import { DeletePartnerButton } from "./DeletePartnerButton";

export default async function PartnersPage() {
  const db = await getDb();

  const partners = await db
    .collection("partners")
    .find()
    .sort({ created_at: -1 })
    .toArray();

  const count = partners.length;

  return (
    <div className="space-y-6 animate-in">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-3">
          <div className="flex items-center justify-center h-10 w-10 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 text-white shadow-sm">
            <Users className="h-5 w-5" />
          </div>
          <div>
            <h1 className="text-2xl font-bold text-foreground">Partners</h1>
            <p className="text-sm text-muted-foreground">
              {count} partner{count !== 1 ? "s" : ""}
            </p>
          </div>
        </div>
        <Link
          href="/admin/partners/new"
          className="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors shadow-sm"
        >
          <Plus className="h-4 w-4" />
          Add Partner
        </Link>
      </div>

      {/* Table */}
      <div className="rounded-xl bg-card border border-border overflow-hidden shadow-sm">
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="bg-muted/50 text-left">
                <th className="px-6 py-3 font-medium text-muted-foreground whitespace-nowrap">
                  Affiliate ID
                </th>
                <th className="px-6 py-3 font-medium text-muted-foreground whitespace-nowrap">
                  Partner Name
                </th>
                <th className="px-6 py-3 font-medium text-muted-foreground whitespace-nowrap">
                  Status
                </th>
                <th className="px-6 py-3 font-medium text-muted-foreground whitespace-nowrap">
                  Created
                </th>
                <th className="px-6 py-3 font-medium text-muted-foreground whitespace-nowrap">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody className="divide-y divide-border">
              {partners.length === 0 ? (
                <tr>
                  <td colSpan={5} className="px-6 py-16 text-center">
                    <Users className="h-10 w-10 text-muted-foreground/40 mx-auto mb-3" />
                    <p className="text-muted-foreground font-medium">
                      No partners yet
                    </p>
                    <p className="text-sm text-muted-foreground/60 mt-1">
                      Add your first partner to get started
                    </p>
                    <Link
                      href="/admin/partners/new"
                      className="inline-flex items-center gap-2 mt-4 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors"
                    >
                      <Plus className="h-4 w-4" />
                      Add Partner
                    </Link>
                  </td>
                </tr>
              ) : (
                partners.map((partner) => {
                  const partnerId = partner._id.toString();
                  return (
                    <tr
                      key={partnerId}
                      className="hover:bg-muted/30 transition-colors"
                    >
                      <td className="px-6 py-3.5 whitespace-nowrap">
                        <span className="inline-block rounded bg-slate-100 px-2 py-0.5 font-mono text-xs text-foreground">
                          {partner.aff_id}
                        </span>
                      </td>
                      <td className="px-6 py-3.5 whitespace-nowrap font-medium text-foreground">
                        {partner.partner_name}
                      </td>
                      <td className="px-6 py-3.5 whitespace-nowrap">
                        {partner.is_active ? (
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
                        {partner.created_at
                          ? formatDate(partner.created_at)
                          : "—"}
                      </td>
                      <td className="px-6 py-3.5 whitespace-nowrap">
                        <div className="flex items-center gap-1.5">
                          {/* Edit */}
                          <Link
                            href={`/admin/partners/${partnerId}`}
                            className="inline-flex items-center justify-center h-8 w-8 rounded-lg text-muted-foreground hover:text-foreground hover:bg-muted transition-colors"
                            title="Edit partner"
                          >
                            <Pencil className="h-4 w-4" />
                          </Link>

                          {/* Toggle Active */}
                          <TogglePartnerButton
                            partnerId={partnerId}
                            isActive={partner.is_active as boolean}
                          />

                          {/* Delete */}
                          <DeletePartnerButton partnerId={partnerId} />
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
