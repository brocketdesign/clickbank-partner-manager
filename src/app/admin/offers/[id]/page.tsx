import { getDb } from "@/lib/mongodb";
import { ObjectId } from "mongodb";
import { notFound } from "next/navigation";
import { ArrowLeft, Tag } from "lucide-react";
import Link from "next/link";
import OfferForm from "../OfferForm";

interface Props {
  params: Promise<{ id: string }>;
}

export default async function OfferEditPage({ params }: Props) {
  const { id } = await params;

  const isNew = id === "new";
  let offerData:
    | {
        _id: string;
        offer_name: string;
        clickbank_vendor: string;
        clickbank_hoplink: string;
        is_active: boolean;
      }
    | undefined;

  if (!isNew) {
    if (!ObjectId.isValid(id)) {
      notFound();
    }

    const db = await getDb();
    const offer = await db
      .collection("offers")
      .findOne({ _id: new ObjectId(id) });

    if (!offer) {
      notFound();
    }

    offerData = {
      _id: offer._id.toString(),
      offer_name: offer.offer_name as string,
      clickbank_vendor: offer.clickbank_vendor as string,
      clickbank_hoplink: offer.clickbank_hoplink as string,
      is_active: offer.is_active as boolean,
    };
  }

  return (
    <div className="space-y-6 animate-in max-w-2xl">
      {/* Back Link */}
      <Link
        href="/admin/offers"
        className="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground transition-colors"
      >
        <ArrowLeft className="h-4 w-4" />
        Back to Offers
      </Link>

      {/* Header */}
      <div className="flex items-center gap-3">
        <div className="flex items-center justify-center h-10 w-10 rounded-xl bg-gradient-to-br from-orange-500 to-amber-600 text-white shadow-sm">
          <Tag className="h-5 w-5" />
        </div>
        <div>
          <h1 className="text-2xl font-bold text-foreground">
            {isNew ? "Add Offer" : "Edit Offer"}
          </h1>
          {!isNew && offerData && (
            <p className="text-sm text-muted-foreground">
              {offerData.offer_name}
            </p>
          )}
        </div>
      </div>

      {/* Form Card */}
      <div className="rounded-xl bg-card border border-border shadow-sm overflow-hidden">
        <div className="px-6 py-5">
          <OfferForm initialData={offerData} />
        </div>
      </div>
    </div>
  );
}
