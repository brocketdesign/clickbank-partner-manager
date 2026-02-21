import { getDb } from "@/lib/mongodb";
import { ObjectId } from "mongodb";
import { notFound } from "next/navigation";
import { ArrowLeft, Users } from "lucide-react";
import Link from "next/link";
import PartnerForm from "../PartnerForm";

interface Props {
  params: Promise<{ id: string }>;
}

export default async function PartnerEditPage({ params }: Props) {
  const { id } = await params;

  const isNew = id === "new";
  let partnerData:
    | { _id: string; aff_id: string; partner_name: string; is_active: boolean }
    | undefined;

  if (!isNew) {
    if (!ObjectId.isValid(id)) {
      notFound();
    }

    const db = await getDb();
    const partner = await db
      .collection("partners")
      .findOne({ _id: new ObjectId(id) });

    if (!partner) {
      notFound();
    }

    partnerData = {
      _id: partner._id.toString(),
      aff_id: partner.aff_id as string,
      partner_name: partner.partner_name as string,
      is_active: partner.is_active as boolean,
    };
  }

  return (
    <div className="space-y-6 animate-in max-w-2xl">
      {/* Back Link */}
      <Link
        href="/admin/partners"
        className="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground transition-colors"
      >
        <ArrowLeft className="h-4 w-4" />
        Back to Partners
      </Link>

      {/* Header */}
      <div className="flex items-center gap-3">
        <div className="flex items-center justify-center h-10 w-10 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 text-white shadow-sm">
          <Users className="h-5 w-5" />
        </div>
        <div>
          <h1 className="text-2xl font-bold text-foreground">
            {isNew ? "Add Partner" : "Edit Partner"}
          </h1>
          {!isNew && partnerData && (
            <p className="text-sm text-muted-foreground">
              {partnerData.partner_name}
            </p>
          )}
        </div>
      </div>

      {/* Form Card */}
      <div className="rounded-xl bg-card border border-border shadow-sm overflow-hidden">
        <div className="px-6 py-5">
          <PartnerForm initialData={partnerData} />
        </div>
      </div>
    </div>
  );
}
