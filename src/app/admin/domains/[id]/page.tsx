import { getDb } from "@/lib/mongodb";
import { ObjectId } from "mongodb";
import { notFound } from "next/navigation";
import { ArrowLeft, Globe } from "lucide-react";
import Link from "next/link";
import DomainForm from "../DomainForm";

interface Props {
  params: Promise<{ id: string }>;
}

export default async function DomainEditPage({ params }: Props) {
  const { id } = await params;

  const isNew = id === "new";
  let domainData: { _id: string; domain_name: string; is_active: boolean } | undefined;

  if (!isNew) {
    if (!ObjectId.isValid(id)) {
      notFound();
    }

    const db = await getDb();
    const domain = await db.collection("domains").findOne({ _id: new ObjectId(id) });

    if (!domain) {
      notFound();
    }

    domainData = {
      _id: domain._id.toString(),
      domain_name: domain.domain_name as string,
      is_active: domain.is_active as boolean,
    };
  }

  return (
    <div className="space-y-6 animate-in max-w-2xl">
      {/* Back Link */}
      <Link
        href="/admin/domains"
        className="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground transition-colors"
      >
        <ArrowLeft className="h-4 w-4" />
        Back to Domains
      </Link>

      {/* Header */}
      <div className="flex items-center gap-3">
        <div className="flex items-center justify-center h-10 w-10 rounded-xl bg-gradient-to-br from-sky-500 to-blue-600 text-white shadow-sm">
          <Globe className="h-5 w-5" />
        </div>
        <div>
          <h1 className="text-2xl font-bold text-foreground">
            {isNew ? "Add Domain" : "Edit Domain"}
          </h1>
          {!isNew && domainData && (
            <p className="text-sm text-muted-foreground">{domainData.domain_name}</p>
          )}
        </div>
      </div>

      {/* Form Card */}
      <div className="rounded-xl bg-card border border-border shadow-sm overflow-hidden">
        <div className="px-6 py-5">
          <DomainForm initialData={domainData} />
        </div>
      </div>
    </div>
  );
}
